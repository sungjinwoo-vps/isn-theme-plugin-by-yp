<?php
/**
 * Original featured artwork for posts without uploaded media.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

namespace InfoSecNexus\Theme\Toolkit;

/**
 * Generate deterministic, lightweight WebP artwork and attach it to posts.
 */
final class Post_Artwork {
	private const VERSION              = '1';
	private const BACKFILL_HOOK        = 'infosecnexus_backfill_post_artwork';
	private const BACKFILL_OPTION      = 'infosecnexus_post_artwork_backfill_version';
	private const GENERATED_META       = '_infosecnexus_generated_artwork';
	private const ATTACHMENT_POST_META = '_infosecnexus_artwork_post_id';
	private const BATCH_SIZE           = 6;

	/**
	 * Post IDs queued for one cache purge at request shutdown.
	 *
	 * @var int[]
	 */
	private static array $changed_post_ids = array();

	/**
	 * Register artwork generation and migration hooks.
	 */
	public static function boot(): void {
		add_action( 'init', array( __CLASS__, 'schedule_backfill' ), 30 );
		add_action( self::BACKFILL_HOOK, array( __CLASS__, 'backfill_batch' ) );
		add_action( 'save_post_post', array( __CLASS__, 'generate_for_saved_post' ), 30, 3 );
		add_action( 'shutdown', array( __CLASS__, 'flush_cache_purge' ) );
	}

	/**
	 * Generate artwork when a post is published without a featured image.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 * @param bool     $update  Whether this is an update.
	 */
	public static function generate_for_saved_post( int $post_id, \WP_Post $post, bool $update ): void {
		unset( $update );

		if (
			'publish' !== $post->post_status
			|| wp_is_post_revision( $post_id )
			|| wp_is_post_autosave( $post_id )
			|| has_post_thumbnail( $post_id )
		) {
			return;
		}

		if ( self::ensure( $post_id ) > 0 ) {
			self::queue_cache_purge( array( $post_id ) );
		}
	}

	/**
	 * Schedule a small background migration for existing posts.
	 */
	public static function schedule_backfill(): void {
		if ( self::VERSION === (string) get_option( self::BACKFILL_OPTION, '' ) ) {
			return;
		}

		if ( ! self::supported() ) {
			update_option( self::BACKFILL_OPTION, self::VERSION, false );
			return;
		}

		if ( ! wp_next_scheduled( self::BACKFILL_HOOK ) ) {
			wp_schedule_single_event( time(), self::BACKFILL_HOOK );
		}
	}

	/**
	 * Generate a limited number of missing images per cron request.
	 */
	public static function backfill_batch(): void {
		$post_ids = self::posts_missing_artwork( self::BATCH_SIZE );
		if ( empty( $post_ids ) ) {
			update_option( self::BACKFILL_OPTION, self::VERSION, false );
			return;
		}

		$generated = 0;
		$generated_ids = array();
		foreach ( $post_ids as $post_id ) {
			if ( self::ensure( $post_id ) > 0 ) {
				++$generated;
				$generated_ids[] = $post_id;
			}
		}

		if ( ! empty( $generated_ids ) ) {
			self::queue_cache_purge( $generated_ids );
		}

		if ( empty( self::posts_missing_artwork( 1 ) ) ) {
			update_option( self::BACKFILL_OPTION, self::VERSION, false );
			return;
		}

		$delay = $generated > 0 ? 2 : HOUR_IN_SECONDS;
		wp_schedule_single_event( time() + $delay, self::BACKFILL_HOOK );
	}

	/**
	 * Purge public HTML once for all artwork generated during this request.
	 */
	public static function flush_cache_purge(): void {
		if ( empty( self::$changed_post_ids ) ) {
			return;
		}

		$post_ids = self::$changed_post_ids;
		self::$changed_post_ids = array();
		do_action( 'infosecnexus_post_artwork_changed', $post_ids );
	}

	/**
	 * Queue changed posts for a single cache purge.
	 *
	 * @param int[] $post_ids Changed post IDs.
	 */
	private static function queue_cache_purge( array $post_ids ): void {
		self::$changed_post_ids = array_values(
			array_unique(
				array_merge( self::$changed_post_ids, array_filter( array_map( 'absint', $post_ids ) ) )
			)
		);
	}

	/**
	 * Ensure one post has original featured artwork.
	 *
	 * @param int $post_id Post ID.
	 * @return int Attachment ID, or zero when artwork could not be created.
	 */
	public static function ensure( int $post_id ): int {
		$thumbnail_id = get_post_thumbnail_id( $post_id );
		if ( $thumbnail_id > 0 ) {
			return $thumbnail_id;
		}

		$post = get_post( $post_id );
		if ( ! $post instanceof \WP_Post || 'post' !== $post->post_type || ! self::supported() ) {
			return 0;
		}

		$existing = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => self::ATTACHMENT_POST_META,
				'meta_value'     => (string) $post_id,
				'no_found_rows'  => true,
			)
		);
		if ( ! empty( $existing ) ) {
			$attachment_id = (int) $existing[0];
			set_post_thumbnail( $post_id, $attachment_id );
			return $attachment_id;
		}

		$upload = wp_upload_dir();
		if ( ! empty( $upload['error'] ) ) {
			return 0;
		}

		$directory = trailingslashit( (string) $upload['basedir'] ) . 'infosecnexus-artwork';
		if ( ! wp_mkdir_p( $directory ) ) {
			return 0;
		}

		$signature = substr( hash( 'sha256', $post_id . '|' . $post->post_title ), 0, 12 );
		$filename  = 'briefing-' . $post_id . '-' . $signature . '.webp';
		$path      = trailingslashit( $directory ) . $filename;

		if ( ! file_exists( $path ) && ! self::render( $path, $post ) ) {
			return 0;
		}

		$attachment_id = wp_insert_attachment(
			array(
				'guid'           => trailingslashit( (string) $upload['baseurl'] ) . 'infosecnexus-artwork/' . $filename,
				'post_mime_type' => 'image/webp',
				'post_title'     => sanitize_text_field( $post->post_title . ' featured artwork' ),
				'post_content'   => '',
				'post_excerpt'   => '',
				'post_status'    => 'inherit',
				'post_parent'    => $post_id,
			),
			$path,
			$post_id,
			true
		);
		if ( is_wp_error( $attachment_id ) ) {
			return 0;
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';
		$metadata = wp_generate_attachment_metadata( $attachment_id, $path );
		if ( is_array( $metadata ) ) {
			wp_update_attachment_metadata( $attachment_id, $metadata );
		}

		update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( $post->post_title ) );
		update_post_meta( $attachment_id, self::ATTACHMENT_POST_META, $post_id );
		update_post_meta( $post_id, self::GENERATED_META, self::VERSION . ':' . $signature );
		set_post_thumbnail( $post_id, $attachment_id );

		return $attachment_id;
	}

	/**
	 * Query published posts that still have no featured image.
	 *
	 * @param int $limit Maximum IDs.
	 * @return int[]
	 */
	private static function posts_missing_artwork( int $limit ): array {
		$query = new \WP_Query(
			array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => max( 1, $limit ),
				'orderby'        => 'date',
				'order'          => 'DESC',
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'meta_query'     => array(
					'relation' => 'OR',
					array(
						'key'     => '_thumbnail_id',
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'     => '_thumbnail_id',
						'value'   => '0',
						'compare' => '=',
					),
				),
			)
		);

		return array_map( 'intval', $query->posts );
	}

	/**
	 * Whether this server can create WebP images.
	 */
	private static function supported(): bool {
		return function_exists( 'imagecreatetruecolor' ) && function_exists( 'imagewebp' );
	}

	/**
	 * Render deterministic cybersecurity artwork for one post.
	 *
	 * @param string   $path Output path.
	 * @param \WP_Post $post Post object.
	 */
	private static function render( string $path, \WP_Post $post ): bool {
		$width  = 1280;
		$height = 720;
		$image  = imagecreatetruecolor( $width, $height );
		if ( false === $image ) {
			return false;
		}

		imagealphablending( $image, true );
		if ( function_exists( 'imageantialias' ) ) {
			imageantialias( $image, true );
		}

		$seed       = self::seed( (int) $post->ID, $post->post_title );
		$style      = self::style( (int) $post->ID );
		$background = $style['background'];
		$deep       = self::vary( $style['deep'], $seed, 16 );
		$accent     = self::vary( $style['accent'], $seed >> 4, 20 );
		$secondary  = self::vary( $style['secondary'], $seed >> 8, 18 );

		for ( $y = 0; $y < $height; ++$y ) {
			$ratio = $y / max( 1, $height - 1 );
			$color = self::mix( $background, $deep, $ratio );
			imageline( $image, 0, $y, $width, $y, self::color( $image, $color ) );
		}

		self::draw_grid( $image, $width, $height, $accent );
		self::draw_circuits( $image, $width, $height, $accent, $secondary, $seed );

		$icon_x    = 760 + self::random( $seed, -80, 140 );
		$icon_y    = 350 + self::random( $seed, -45, 45 );
		$icon_size = 210 + self::random( $seed, -30, 45 );
		$icon      = ( $style['icon'] + ( $seed % 3 ) ) % 5;

		self::draw_glow( $image, $icon_x, $icon_y, $icon_size, $accent );
		switch ( $icon ) {
			case 0:
				self::draw_shield( $image, $icon_x, $icon_y, $icon_size, $accent, $secondary );
				break;
			case 1:
				self::draw_lock( $image, $icon_x, $icon_y, $icon_size, $accent, $secondary );
				break;
			case 2:
				self::draw_network( $image, $icon_x, $icon_y, $icon_size, $accent, $secondary, $seed );
				break;
			case 3:
				self::draw_terminal( $image, $icon_x, $icon_y, $icon_size, $accent, $secondary );
				break;
			default:
				self::draw_cloud( $image, $icon_x, $icon_y, $icon_size, $accent, $secondary );
				break;
		}

		self::draw_frame( $image, $width, $height, $accent );

		$temporary = $path . '.tmp';
		$saved     = imagewebp( $image, $temporary, 68 );
		imagedestroy( $image );
		if ( ! $saved ) {
			return false;
		}

		if ( ! @rename( $temporary, $path ) ) {
			@unlink( $temporary );
			return false;
		}

		return true;
	}

	/**
	 * Category visual language.
	 *
	 * @param int $post_id Post ID.
	 * @return array{background:int[],deep:int[],accent:int[],secondary:int[],icon:int}
	 */
	private static function style( int $post_id ): array {
		$slugs = wp_get_post_categories( $post_id, array( 'fields' => 'slugs' ) );
		$slug  = is_array( $slugs ) && ! empty( $slugs ) ? (string) reset( $slugs ) : 'cybersecurity';
		$styles = array(
			'critical-cves'           => array( 'background' => array( 8, 14, 31 ), 'deep' => array( 45, 8, 25 ), 'accent' => array( 255, 45, 72 ), 'secondary' => array( 255, 174, 67 ), 'icon' => 1 ),
			'linux-administration'    => array( 'background' => array( 5, 18, 35 ), 'deep' => array( 4, 53, 75 ), 'accent' => array( 32, 190, 255 ), 'secondary' => array( 112, 255, 214 ), 'icon' => 3 ),
			'devops'                  => array( 'background' => array( 6, 20, 39 ), 'deep' => array( 13, 47, 84 ), 'accent' => array( 44, 148, 255 ), 'secondary' => array( 93, 246, 199 ), 'icon' => 3 ),
			'artificial-intelligence' => array( 'background' => array( 15, 12, 38 ), 'deep' => array( 40, 20, 82 ), 'accent' => array( 154, 96, 255 ), 'secondary' => array( 61, 220, 255 ), 'icon' => 2 ),
			'cloud-security'          => array( 'background' => array( 4, 24, 43 ), 'deep' => array( 9, 59, 92 ), 'accent' => array( 54, 177, 255 ), 'secondary' => array( 123, 244, 255 ), 'icon' => 4 ),
			'network-security'        => array( 'background' => array( 4, 24, 36 ), 'deep' => array( 4, 68, 76 ), 'accent' => array( 37, 224, 205 ), 'secondary' => array( 83, 170, 255 ), 'icon' => 2 ),
			'windows-security'        => array( 'background' => array( 5, 20, 45 ), 'deep' => array( 12, 48, 104 ), 'accent' => array( 54, 149, 255 ), 'secondary' => array( 104, 227, 255 ), 'icon' => 0 ),
			'web-security'            => array( 'background' => array( 9, 20, 40 ), 'deep' => array( 14, 54, 75 ), 'accent' => array( 40, 205, 255 ), 'secondary' => array( 98, 255, 171 ), 'icon' => 1 ),
			'tutorials'               => array( 'background' => array( 13, 20, 39 ), 'deep' => array( 30, 53, 78 ), 'accent' => array( 74, 151, 255 ), 'secondary' => array( 246, 194, 75 ), 'icon' => 3 ),
			'cybersecurity'           => array( 'background' => array( 5, 18, 38 ), 'deep' => array( 8, 51, 88 ), 'accent' => array( 36, 145, 255 ), 'secondary' => array( 70, 235, 255 ), 'icon' => 0 ),
		);

		return $styles[ $slug ] ?? $styles['cybersecurity'];
	}

	/**
	 * Draw a receding technology grid.
	 *
	 * @param resource|\GdImage $image Image.
	 * @param int               $width Width.
	 * @param int               $height Height.
	 * @param int[]             $accent Accent RGB.
	 */
	private static function draw_grid( $image, int $width, int $height, array $accent ): void {
		$grid = self::color( $image, $accent, 102 );
		for ( $x = -200; $x <= $width + 200; $x += 90 ) {
			imageline( $image, (int) ( $width / 2 ), 345, $x, $height, $grid );
		}
		for ( $y = 390; $y < $height; $y += 42 ) {
			imageline( $image, 0, $y, $width, $y, $grid );
		}
	}

	/**
	 * Draw seed-specific traces and nodes.
	 *
	 * @param resource|\GdImage $image Image.
	 * @param int               $width Width.
	 * @param int               $height Height.
	 * @param int[]             $accent Accent RGB.
	 * @param int[]             $secondary Secondary RGB.
	 * @param int               $seed Random state.
	 */
	private static function draw_circuits( $image, int $width, int $height, array $accent, array $secondary, int &$seed ): void {
		for ( $i = 0; $i < 34; ++$i ) {
			$x1 = self::random( $seed, 20, $width - 20 );
			$y1 = self::random( $seed, 40, $height - 40 );
			$x2 = max( 15, min( $width - 15, $x1 + self::random( $seed, -240, 240 ) ) );
			$y2 = max( 15, min( $height - 15, $y1 + self::random( $seed, -130, 130 ) ) );
			$rgb = 0 === $i % 3 ? $secondary : $accent;
			$line = self::color( $image, $rgb, self::random( $seed, 55, 92 ) );

			imagesetthickness( $image, self::random( $seed, 1, 3 ) );
			imageline( $image, $x1, $y1, $x2, $y1, $line );
			imageline( $image, $x2, $y1, $x2, $y2, $line );
			imagefilledellipse( $image, $x2, $y2, 6, 6, self::color( $image, $rgb, 25 ) );
		}
		imagesetthickness( $image, 1 );
	}

	/**
	 * Draw a soft emblem glow.
	 *
	 * @param resource|\GdImage $image Image.
	 * @param int               $x Center X.
	 * @param int               $y Center Y.
	 * @param int               $size Size.
	 * @param int[]             $accent Accent RGB.
	 */
	private static function draw_glow( $image, int $x, int $y, int $size, array $accent ): void {
		for ( $ring = 6; $ring > 0; --$ring ) {
			$diameter = $size + ( $ring * 58 );
			imagefilledellipse( $image, $x, $y, $diameter, $diameter, self::color( $image, $accent, 106 + ( $ring * 3 ) ) );
		}
	}

	/**
	 * Draw a shield/check emblem.
	 *
	 * @param resource|\GdImage $image Image.
	 * @param int               $x Center X.
	 * @param int               $y Center Y.
	 * @param int               $size Size.
	 * @param int[]             $accent Accent RGB.
	 * @param int[]             $secondary Secondary RGB.
	 */
	private static function draw_shield( $image, int $x, int $y, int $size, array $accent, array $secondary ): void {
		$half   = (int) ( $size * 0.45 );
		$points = array( $x, $y - $half, $x + $half, $y - (int) ( $half * 0.55 ), $x + (int) ( $half * 0.36 ), $y + (int) ( $half * 0.58 ), $x, $y + $half, $x - (int) ( $half * 0.36 ), $y + (int) ( $half * 0.58 ), $x - $half, $y - (int) ( $half * 0.55 ) );
		imagefilledpolygon( $image, $points, self::color( $image, $accent, 104 ) );
		imagesetthickness( $image, 7 );
		imagepolygon( $image, $points, self::color( $image, $accent ) );
		imagesetthickness( $image, 10 );
		imageline( $image, $x - (int) ( $half * 0.42 ), $y, $x - (int) ( $half * 0.08 ), $y + (int) ( $half * 0.32 ), self::color( $image, $secondary ) );
		imageline( $image, $x - (int) ( $half * 0.08 ), $y + (int) ( $half * 0.32 ), $x + (int) ( $half * 0.52 ), $y - (int) ( $half * 0.34 ), self::color( $image, $secondary ) );
		imagesetthickness( $image, 1 );
	}

	/**
	 * Draw a lock emblem.
	 *
	 * @param resource|\GdImage $image Image.
	 * @param int               $x Center X.
	 * @param int               $y Center Y.
	 * @param int               $size Size.
	 * @param int[]             $accent Accent RGB.
	 * @param int[]             $secondary Secondary RGB.
	 */
	private static function draw_lock( $image, int $x, int $y, int $size, array $accent, array $secondary ): void {
		$body_w = (int) ( $size * 0.72 );
		$body_h = (int) ( $size * 0.58 );
		$left   = $x - (int) ( $body_w / 2 );
		$top    = $y - (int) ( $body_h * 0.05 );
		imagesetthickness( $image, 9 );
		imagearc( $image, $x, $top, (int) ( $size * 0.48 ), (int) ( $size * 0.58 ), 180, 360, self::color( $image, $accent ) );
		imagefilledrectangle( $image, $left, $top, $left + $body_w, $top + $body_h, self::color( $image, $accent, 84 ) );
		imagerectangle( $image, $left, $top, $left + $body_w, $top + $body_h, self::color( $image, $accent ) );
		imagefilledellipse( $image, $x, $top + (int) ( $body_h * 0.46 ), 22, 22, self::color( $image, $secondary ) );
		imagefilledrectangle( $image, $x - 5, $top + (int) ( $body_h * 0.48 ), $x + 5, $top + (int) ( $body_h * 0.73 ), self::color( $image, $secondary ) );
		imagesetthickness( $image, 1 );
	}

	/**
	 * Draw a connected-node emblem.
	 *
	 * @param resource|\GdImage $image Image.
	 * @param int               $x Center X.
	 * @param int               $y Center Y.
	 * @param int               $size Size.
	 * @param int[]             $accent Accent RGB.
	 * @param int[]             $secondary Secondary RGB.
	 * @param int               $seed Random state.
	 */
	private static function draw_network( $image, int $x, int $y, int $size, array $accent, array $secondary, int &$seed ): void {
		$nodes = array( array( $x, $y ) );
		for ( $i = 0; $i < 8; ++$i ) {
			$angle   = ( 2 * M_PI * $i / 8 ) + ( self::random( $seed, -15, 15 ) / 100 );
			$radius  = (int) ( $size * ( 0.32 + self::random( $seed, 0, 12 ) / 100 ) );
			$nodes[] = array( $x + (int) ( cos( $angle ) * $radius ), $y + (int) ( sin( $angle ) * $radius ) );
		}
		imagesetthickness( $image, 5 );
		foreach ( array_slice( $nodes, 1 ) as $node ) {
			imageline( $image, $x, $y, $node[0], $node[1], self::color( $image, $accent, 38 ) );
			imagefilledellipse( $image, $node[0], $node[1], 22, 22, self::color( $image, $secondary ) );
		}
		imagefilledellipse( $image, $x, $y, 46, 46, self::color( $image, $accent ) );
		imagesetthickness( $image, 1 );
	}

	/**
	 * Draw a terminal emblem.
	 *
	 * @param resource|\GdImage $image Image.
	 * @param int               $x Center X.
	 * @param int               $y Center Y.
	 * @param int               $size Size.
	 * @param int[]             $accent Accent RGB.
	 * @param int[]             $secondary Secondary RGB.
	 */
	private static function draw_terminal( $image, int $x, int $y, int $size, array $accent, array $secondary ): void {
		$left   = $x - (int) ( $size * 0.48 );
		$top    = $y - (int) ( $size * 0.34 );
		$right  = $x + (int) ( $size * 0.48 );
		$bottom = $y + (int) ( $size * 0.34 );
		imagefilledrectangle( $image, $left, $top, $right, $bottom, self::color( $image, $accent, 92 ) );
		imagesetthickness( $image, 7 );
		imagerectangle( $image, $left, $top, $right, $bottom, self::color( $image, $accent ) );
		imageline( $image, $left + 38, $y - 10, $left + 72, $y + 22, self::color( $image, $secondary ) );
		imageline( $image, $left + 72, $y + 22, $left + 38, $y + 52, self::color( $image, $secondary ) );
		imageline( $image, $left + 92, $y + 52, $right - 38, $y + 52, self::color( $image, $secondary ) );
		imagesetthickness( $image, 1 );
	}

	/**
	 * Draw a cloud emblem.
	 *
	 * @param resource|\GdImage $image Image.
	 * @param int               $x Center X.
	 * @param int               $y Center Y.
	 * @param int               $size Size.
	 * @param int[]             $accent Accent RGB.
	 * @param int[]             $secondary Secondary RGB.
	 */
	private static function draw_cloud( $image, int $x, int $y, int $size, array $accent, array $secondary ): void {
		$fill = self::color( $image, $accent, 76 );
		imagefilledellipse( $image, $x - (int) ( $size * 0.25 ), $y, (int) ( $size * 0.48 ), (int) ( $size * 0.42 ), $fill );
		imagefilledellipse( $image, $x, $y - (int) ( $size * 0.13 ), (int) ( $size * 0.58 ), (int) ( $size * 0.56 ), $fill );
		imagefilledellipse( $image, $x + (int) ( $size * 0.29 ), $y + 4, (int) ( $size * 0.42 ), (int) ( $size * 0.38 ), $fill );
		imagefilledrectangle( $image, $x - (int) ( $size * 0.43 ), $y, $x + (int) ( $size * 0.47 ), $y + (int) ( $size * 0.2 ), $fill );
		imagesetthickness( $image, 8 );
		imageline( $image, $x - (int) ( $size * 0.2 ), $y + (int) ( $size * 0.34 ), $x, $y + (int) ( $size * 0.48 ), self::color( $image, $secondary ) );
		imageline( $image, $x, $y + (int) ( $size * 0.48 ), $x + (int) ( $size * 0.25 ), $y + (int) ( $size * 0.25 ), self::color( $image, $secondary ) );
		imagesetthickness( $image, 1 );
	}

	/**
	 * Add a restrained technical frame.
	 *
	 * @param resource|\GdImage $image Image.
	 * @param int               $width Width.
	 * @param int               $height Height.
	 * @param int[]             $accent Accent RGB.
	 */
	private static function draw_frame( $image, int $width, int $height, array $accent ): void {
		$line   = self::color( $image, $accent, 75 );
		$length = 105;
		$inset  = 28;
		imagesetthickness( $image, 3 );
		imageline( $image, $inset, $inset, $inset + $length, $inset, $line );
		imageline( $image, $inset, $inset, $inset, $inset + $length, $line );
		imageline( $image, $width - $inset, $inset, $width - $inset - $length, $inset, $line );
		imageline( $image, $width - $inset, $inset, $width - $inset, $inset + $length, $line );
		imageline( $image, $inset, $height - $inset, $inset + $length, $height - $inset, $line );
		imageline( $image, $inset, $height - $inset, $inset, $height - $inset - $length, $line );
		imageline( $image, $width - $inset, $height - $inset, $width - $inset - $length, $height - $inset, $line );
		imageline( $image, $width - $inset, $height - $inset, $width - $inset, $height - $inset - $length, $line );
		imagesetthickness( $image, 1 );
	}

	/**
	 * Build an integer seed.
	 */
	private static function seed( int $post_id, string $title ): int {
		return (int) hexdec( substr( hash( 'sha256', $post_id . '|' . $title ), 0, 7 ) );
	}

	/**
	 * Deterministic integer random number.
	 *
	 * @param int $seed Mutable state.
	 * @param int $min Minimum.
	 * @param int $max Maximum.
	 */
	private static function random( int &$seed, int $min, int $max ): int {
		$seed = (int) ( ( ( $seed * 1103515245 ) + 12345 ) & 0x7fffffff );
		return $min + ( $seed % max( 1, $max - $min + 1 ) );
	}

	/**
	 * Allocate an RGB color with optional GD alpha.
	 *
	 * @param resource|\GdImage $image Image.
	 * @param int[]             $rgb RGB.
	 * @param int               $alpha GD alpha.
	 */
	private static function color( $image, array $rgb, int $alpha = 0 ): int {
		return imagecolorallocatealpha( $image, $rgb[0], $rgb[1], $rgb[2], max( 0, min( 127, $alpha ) ) );
	}

	/**
	 * Mix two RGB colors.
	 *
	 * @param int[] $first First RGB.
	 * @param int[] $second Second RGB.
	 * @param float $ratio Mix ratio.
	 * @return int[]
	 */
	private static function mix( array $first, array $second, float $ratio ): array {
		return array(
			(int) round( $first[0] + ( ( $second[0] - $first[0] ) * $ratio ) ),
			(int) round( $first[1] + ( ( $second[1] - $first[1] ) * $ratio ) ),
			(int) round( $first[2] + ( ( $second[2] - $first[2] ) * $ratio ) ),
		);
	}

	/**
	 * Add a deterministic color variation.
	 *
	 * @param int[] $rgb Base RGB.
	 * @param int   $seed Seed.
	 * @param int   $amount Maximum shift.
	 * @return int[]
	 */
	private static function vary( array $rgb, int $seed, int $amount ): array {
		return array(
			(int) max( 0, min( 255, $rgb[0] + ( ( $seed & 15 ) - 7 ) * $amount / 8 ) ),
			(int) max( 0, min( 255, $rgb[1] + ( ( ( $seed >> 4 ) & 15 ) - 7 ) * $amount / 8 ) ),
			(int) max( 0, min( 255, $rgb[2] + ( ( ( $seed >> 8 ) & 15 ) - 7 ) * $amount / 8 ) ),
		);
	}
}
