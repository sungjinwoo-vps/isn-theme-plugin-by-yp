<?php
/**
 * Topic-matched editorial photography for posts without uploaded media.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

namespace InfoSecNexus\Theme\Toolkit;

/**
 * Download licensed editorial photography, optimize it to WebP, and attach it.
 */
final class Post_Artwork {
	private const VERSION                = '9';
	private const BACKFILL_HOOK          = 'infosecnexus_backfill_post_artwork';
	private const BACKFILL_OPTION        = 'infosecnexus_post_artwork_backfill_version';
	private const GENERATED_META         = '_infosecnexus_generated_artwork';
	private const ATTACHMENT_POST_META   = '_infosecnexus_artwork_post_id';
	private const ATTACHMENT_SOURCE_META = '_infosecnexus_artwork_source';
	private const RETIREMENT_STATE_META  = '_infosecnexus_retirement_state';
	private const SUPERSEDED_BY_META     = '_infosecnexus_newsroom_superseded_by';
	private const BATCH_SIZE             = 6;

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
		) {
			return;
		}

		if ( has_post_thumbnail( $post_id ) && '' === (string) get_post_meta( $post_id, self::GENERATED_META, true ) ) {
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
		$post_ids = self::posts_requiring_artwork( self::BATCH_SIZE );
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

		if ( empty( self::posts_requiring_artwork( 1 ) ) ) {
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
		$generated    = (string) get_post_meta( $post_id, self::GENERATED_META, true );
		if ( $thumbnail_id > 0 && '' === $generated ) {
			return $thumbnail_id;
		}

		$post = get_post( $post_id );
		if ( ! $post instanceof \WP_Post || 'post' !== $post->post_type || ! self::supported() ) {
			return 0;
		}

		if ( $thumbnail_id > 0 && str_starts_with( $generated, self::VERSION . ':' ) ) {
			$thumbnail_path = get_attached_file( $thumbnail_id );
			if (
				is_string( $thumbnail_path )
				&& file_exists( $thumbnail_path )
				&& ! self::preferred_artwork_needs_refresh( $post, $thumbnail_id )
			) {
				return $thumbnail_id;
			}
		}

		$upload = wp_upload_dir();
		if ( ! empty( $upload['error'] ) ) {
			return 0;
		}

		$directory = trailingslashit( (string) $upload['basedir'] ) . 'infosecnexus-artwork';
		if ( ! wp_mkdir_p( $directory ) ) {
			return 0;
		}

		$photo = self::select_photo( $post );
		if ( empty( $photo['id'] ) ) {
			return 0;
		}
		$provider    = sanitize_text_field( (string) ( $photo['provider'] ?? 'Unsplash' ) );
		$license     = sanitize_text_field( (string) ( $photo['license'] ?? 'Unsplash License' ) );
		$creator     = sanitize_text_field( (string) ( $photo['creator'] ?? '' ) );
		$credit      = '' !== $creator ? $creator . ' via ' . $provider : $provider;
		$description = 'Editorial photo by ' . $credit . '. Source: ' . esc_url_raw( (string) $photo['page'] );

		$signature = substr( hash( 'sha256', self::VERSION . '|' . $post_id . '|' . $post->post_title . '|' . $photo['id'] ), 0, 12 );
		$filename  = 'editorial-' . $post_id . '-' . $signature . '.webp';
		$path      = trailingslashit( $directory ) . $filename;

		if ( ! file_exists( $path ) && ! self::download_and_render_photo( $path, $photo ) ) {
			return 0;
		}

		$old_thumbnail_path = $thumbnail_id > 0 ? get_attached_file( $thumbnail_id ) : '';
		$reuse_attachment   = $thumbnail_id > 0
			&& is_string( $old_thumbnail_path )
			&& wp_normalize_path( $old_thumbnail_path ) === wp_normalize_path( $path );

		if ( $reuse_attachment ) {
			$attachment_id = $thumbnail_id;
			wp_update_post(
				wp_slash(
					array(
						'ID'           => $attachment_id,
						'post_title'   => sanitize_text_field( $post->post_title . ' featured photo' ),
						'post_content' => $description,
						'post_excerpt' => 'Editorial photo by ' . $credit . '.',
						'post_parent'  => $post_id,
					)
				)
			);
		} else {
			$attachment_id = wp_insert_attachment(
				array(
					'guid'           => trailingslashit( (string) $upload['baseurl'] ) . 'infosecnexus-artwork/' . $filename,
					'post_mime_type' => 'image/webp',
					'post_title'     => sanitize_text_field( $post->post_title . ' featured photo' ),
					'post_content'   => $description,
					'post_excerpt'   => 'Editorial photo by ' . $credit . '.',
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
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';
		$metadata = wp_generate_attachment_metadata( $attachment_id, $path );
		if ( is_array( $metadata ) ) {
			wp_update_attachment_metadata( $attachment_id, $metadata );
		}

		update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( $post->post_title ) );
		update_post_meta( $attachment_id, self::ATTACHMENT_POST_META, $post_id );
		update_post_meta( $attachment_id, self::ATTACHMENT_SOURCE_META, sanitize_text_field( (string) $photo['id'] ) );
		update_post_meta( $attachment_id, '_infosecnexus_artwork_source_url', esc_url_raw( (string) $photo['page'] ) );
		update_post_meta( $attachment_id, '_infosecnexus_artwork_license', $license );
		update_post_meta( $attachment_id, '_infosecnexus_artwork_creator', $creator );
		update_post_meta( $attachment_id, '_infosecnexus_artwork_provider', $provider );
		update_post_meta( $post_id, self::GENERATED_META, self::VERSION . ':' . $signature );
		set_post_thumbnail( $post_id, $attachment_id );

		if (
			$thumbnail_id > 0
			&& $thumbnail_id !== $attachment_id
			&& (int) get_post_meta( $thumbnail_id, self::ATTACHMENT_POST_META, true ) === $post_id
			&& (
				! is_string( $old_thumbnail_path )
				|| wp_normalize_path( $old_thumbnail_path ) !== wp_normalize_path( $path )
			)
		) {
			wp_delete_attachment( $thumbnail_id, true );
		}

		return $attachment_id;
	}

	/**
	 * Refresh generated artwork when a stronger title-specific photo is available.
	 *
	 * @param \WP_Post $post         Post being checked.
	 * @param int      $thumbnail_id Current featured-image attachment ID.
	 * @return bool
	 */
	private static function preferred_artwork_needs_refresh( \WP_Post $post, int $thumbnail_id ): bool {
		$title      = strtolower( $post->post_title );
		$current    = (string) get_post_meta( $thumbnail_id, self::ATTACHMENT_SOURCE_META, true );
		$categories = array(
			'windows-security',
			'network-security',
			'linux-administration',
			'devops',
			'artificial-intelligence',
			'cloud-security',
			'web-security',
			'critical-cves',
			'tutorials',
			'cybersecurity',
		);
		$expected   = '';
		foreach ( $categories as $category ) {
			$preferred = self::preferred_photo_ids( $category, $title );
			if ( ! empty( $preferred[0] ) ) {
				$expected = (string) $preferred[0];
				break;
			}
		}

		return '' !== $expected
			&& $expected !== $current
			&& ! self::photo_in_use( $expected, $post->ID );
	}

	/**
	 * Query published posts missing an image or carrying older generated artwork.
	 *
	 * @param int $limit Maximum IDs.
	 * @return int[]
	 */
	private static function posts_requiring_artwork( int $limit ): array {
		$query = new \WP_Query(
			array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => max( 1, $limit ),
				'orderby'        => array(
					'date' => 'DESC',
					'ID'   => 'DESC',
				),
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'relation' => 'OR',
						array(
							'key'     => self::RETIREMENT_STATE_META,
							'compare' => 'NOT EXISTS',
						),
						array(
							'key'     => self::RETIREMENT_STATE_META,
							'value'   => 'staged',
							'compare' => '!=',
						),
					),
					array(
						'key'     => self::SUPERSEDED_BY_META,
						'compare' => 'NOT EXISTS',
					),
					array(
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
						array(
							'key'     => self::GENERATED_META,
							'value'   => self::VERSION . ':',
							'compare' => 'NOT LIKE',
						),
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
		return function_exists( 'wp_get_image_editor' );
	}

	/**
	 * Pick an unused real photo that fits the article topic.
	 *
	 * @param \WP_Post $post Post needing artwork.
	 * @return array<string,string>
	 */
	private static function select_photo( \WP_Post $post ): array {
		$pools         = self::photo_library();
		$categories    = wp_get_post_categories( $post->ID, array( 'fields' => 'slugs' ) );
		$category      = '';
		$newsroom_kind = sanitize_key( (string) get_post_meta( $post->ID, '_infosecnexus_newsroom_kind', true ) );
		if ( is_array( $categories ) ) {
			foreach ( $categories as $category_slug ) {
				if ( isset( $pools[ $category_slug ] ) ) {
					$category = (string) $category_slug;
					break;
				}
			}
		}
		$context    = strtolower(
			$post->post_title . ' ' .
			wp_strip_all_tags( $post->post_excerpt ) . ' ' .
			wp_trim_words( wp_strip_all_tags( $post->post_content ), 90, '' )
		);

		$keyword_pools = array(
			'windows-security'        => array( 'windows', 'microsoft', 'winsock', 'ancillary function driver', 'sharepoint', 'exchange', 'active directory', 'vmswitch' ),
			'linux-administration'    => array( 'linux', 'ubuntu', 'kernel', 'gnu', 'inetutils', 'snap-confine' ),
			'artificial-intelligence' => array( ' ai ', 'agent', 'model', 'prompt', 'langflow', 'openai', 'hugging face' ),
			'network-security'        => array( 'network', 'router', 'firewall', 'vpn', 'edge device', 'dns', 'fortinet', 'cisco', 'check point', 'd-link' ),
			'cloud-security'          => array( 'cloud', 'azure', 'aws', 'kubernetes', 'cluster', 'managed service' ),
			'devops'                  => array( 'devops', 'pipeline', 'github', 'docker', 'container', 'runner', 'teamcity', 'jetbrains', 'jenkins', 'continuous integration', 'build server', 'ci/cd' ),
			'web-security'            => array( 'web', 'wordpress', 'api', 'application', 'browser', 'apache', 'nginx', 'php' ),
			'critical-cves'           => array( 'cve-', 'vulnerability', 'exploit', 'zero-day' ),
			'tutorials'               => array( 'tutorial', 'guide', 'checklist', 'runbook', 'how to' ),
		);

		if ( 'rolling' === $newsroom_kind ) {
			$category = 'cybersecurity';
		} elseif ( 'breaking' === $newsroom_kind || '' === $category || 'cybersecurity' === $category ) {
			$keyword_contexts = array(
				' ' . strtolower( $post->post_title ) . ' ',
				' ' . $context . ' ',
			);
			foreach ( $keyword_contexts as $keyword_context ) {
				foreach ( $keyword_pools as $pool => $keywords ) {
					foreach ( $keywords as $keyword ) {
						if ( false !== strpos( $keyword_context, $keyword ) ) {
							$category = $pool;
							break 3;
						}
					}
				}
			}
		}

		if ( '' === $category ) {
			$category = 'cybersecurity';
		}

		$index        = self::photo_index( $pools );
		$preferred    = self::preferred_photo_ids( $category, strtolower( $post->post_title ) );
		$reserved     = self::reserved_photo_ids();
		foreach ( $preferred as $photo_id ) {
			if ( isset( $index[ $photo_id ] ) && ! self::photo_in_use( $photo_id, $post->ID ) ) {
				return $index[ $photo_id ];
			}
		}

		$candidates = $pools[ $category ] ?? $pools['cybersecurity'];
		$offset     = self::seed( $post->ID, $post->post_title ) % max( 1, count( $candidates ) );
		$candidates = array_merge( array_slice( $candidates, $offset ), array_slice( $candidates, 0, $offset ) );

		foreach ( $candidates as $candidate ) {
			$photo_id = (string) $candidate['id'];
			if ( in_array( $photo_id, $reserved, true ) || self::photo_in_use( $photo_id, $post->ID ) ) {
				continue;
			}
			return $candidate;
		}

		foreach ( $candidates as $candidate ) {
			$photo_id = (string) $candidate['id'];
			if ( ! self::photo_in_use( $photo_id, $post->ID ) ) {
				return $candidate;
			}
		}

		$openverse = self::find_openverse_photo( $post, $category );
		if ( ! empty( $openverse ) ) {
			return $openverse;
		}

		return $candidates[0] ?? array();
	}

	/**
	 * Flatten the curated library by source ID.
	 *
	 * @param array<string,array<int,array<string,string>>> $pools Photo pools.
	 * @return array<string,array<string,string>>
	 */
	private static function photo_index( array $pools ): array {
		$index = array();
		foreach ( $pools as $photos ) {
			foreach ( $photos as $photo ) {
				$photo_id = (string) ( $photo['id'] ?? '' );
				if ( '' !== $photo_id ) {
					$index[ $photo_id ] = $photo;
				}
			}
		}

		return $index;
	}

	/**
	 * Reserve strong title-to-photo matches before generic daily posts are migrated.
	 *
	 * @param string $category Selected topic pool.
	 * @param string $title    Lowercase post title.
	 * @return string[]
	 */
	private static function preferred_photo_ids( string $category, string $title ): array {
		$matches = array(
			'critical-cves' => array(
				'critical cve live watch' => '347I_P4ZQ0M',
				'cve triage checklist'  => 'TB7aNN4blTQ',
				'zero-day response'     => 'KdCJ1nIkgOU',
				'exploitability signals' => '9SoCnyQmkzI',
			),
			'cybersecurity' => array(
				'live cybersecurity brief'      => 'commons:cyber-shield-9107702',
				'live cybersecurity news brief' => 'commons:cyber-shield-9107702',
				'security operations metrics' => 'Fa9b57hffnM',
				'phishing defense'            => 'LPZy4da9aRo',
				'threat intelligence triage'  => '0aRycsfH57A',
			),
			'linux-administration' => array(
				'live linux security brief' => '4Mw7nkQDByk',
				'linux kernel patch runbook' => 'M5tzZtFCOfs',
				'ssh hardening'              => 'vII7qKAk-9A',
				'linux log review'           => 'Qpj1LAgh4bY',
			),
			'devops' => array(
				'teamcity'              => 'oYzjGQ7LCVE',
				'jetbrains'             => 'oYzjGQ7LCVE',
				'build server'          => 'oYzjGQ7LCVE',
				'ci/cd secrets'          => 'EHn4lNPnsbA',
				'container image scanning' => 'zUrEj_OwLQM',
				'infrastructure as code' => '2ruZpB0SkbU',
			),
			'artificial-intelligence' => array(
				'prompt injection monitoring' => 'Sz5vOCNCDMg',
				'model dependency'            => 'sNt81Whsncg',
				'ai data leakage'             => 'FO7JIlwjOtU',
			),
			'tutorials' => array(
				'weekly vulnerability review' => 'VKnmszzzTig',
				'temporary security exceptions' => 'xjyHDnA93Pk',
				'asset exposure register'      => 'l_pGKO3rVx4',
			),
			'cloud-security' => array(
				'iam least privilege'     => '3Nwt6w-KU3E',
				'cloud storage exposure'  => 'vSprjjDbu60',
				'cloud logging baseline'  => '2JJ3wBHu4_0',
			),
			'windows-security' => array(
				'ancillary function driver' => '-jCY4oEMA3o',
				'winsock'                   => '-jCY4oEMA3o',
				'microsoft windows'         => '-jCY4oEMA3o',
				'live windows security brief' => 'commons:windows-bsod-dell',
				'windows endpoint hardening' => '-jCY4oEMA3o',
				'active directory review'    => 'FlPc9_VocJ4',
				'powershell logging'          => '-Z8cI1gs4zk',
			),
			'network-security' => array(
				'cisco secure firewall'       => 'commons:cisco-asa-5510',
				'cisco asa'                   => 'commons:cisco-asa-5510',
				'secure firewall threat defense' => 'commons:cisco-asa-5510',
				'network segmentation checks' => 'y4GHs9GEFdM',
				'vpn access review'            => 'vE5AKQRUs7c',
				'dns monitoring ideas'         => 'w0aMCZIW6Qc',
			),
			'web-security' => array(
				'api authentication mistakes'   => 'edMu3cQKrho',
				'web application security headers' => 'gEtJoCN1qpM',
				'login security checklist'       => 'IXHNBGTKJfw',
			),
		);

		$preferred = array();
		foreach ( $matches[ $category ] ?? array() as $needle => $photo_id ) {
			if ( false !== strpos( $title, $needle ) ) {
				$preferred[] = $photo_id;
			}
		}

		return $preferred;
	}

	/**
	 * List all source IDs held for known title-specific articles.
	 *
	 * @return string[]
	 */
	private static function reserved_photo_ids(): array {
		$categories = array(
			'critical-cves',
			'cybersecurity',
			'linux-administration',
			'devops',
			'artificial-intelligence',
			'tutorials',
			'cloud-security',
			'windows-security',
			'network-security',
			'web-security',
		);
		$ids = array();
		foreach ( $categories as $category ) {
			$ids = array_merge( $ids, self::preferred_photo_ids( $category, implode( ' ', array(
				'cve triage checklist',
				'critical cve live watch',
				'zero-day response',
				'exploitability signals',
				'live cybersecurity news brief',
				'security operations metrics',
				'phishing defense',
				'threat intelligence triage',
				'live linux security brief',
				'linux kernel patch runbook',
				'ssh hardening',
				'linux log review',
				'ci/cd secrets',
				'container image scanning',
				'infrastructure as code',
				'prompt injection monitoring',
				'model dependency',
				'ai data leakage',
				'weekly vulnerability review',
				'temporary security exceptions',
				'asset exposure register',
				'iam least privilege',
				'cloud storage exposure',
				'cloud logging baseline',
				'live windows security brief',
				'ancillary function driver',
				'winsock',
				'microsoft windows',
				'windows endpoint hardening',
				'active directory review',
				'powershell logging',
				'network segmentation checks',
				'vpn access review',
				'dns monitoring ideas',
				'cisco secure firewall',
				'cisco asa',
				'secure firewall threat defense',
				'api authentication mistakes',
				'web application security headers',
				'login security checklist',
			) ) ) );
		}

		return array_values( array_unique( $ids ) );
	}

	/**
	 * Find a new wide real-world photograph when the curated category pool is full.
	 *
	 * @return array<string,string>
	 */
	private static function find_openverse_photo( \WP_Post $post, string $category ): array {
		$queries = array(
			'critical-cves'           => 'software update checklist laptop',
			'cybersecurity'           => 'security analyst computer office',
			'linux-administration'    => 'server room maintenance',
			'devops'                  => 'software developer coding laptop',
			'artificial-intelligence' => 'artificial intelligence computer lab',
			'tutorials'               => 'technology checklist notebook',
			'cloud-security'          => 'data center server room',
			'windows-security'        => 'desktop computer office',
			'network-security'        => 'network cables server rack',
			'web-security'            => 'web developer laptop office',
		);
		$query = (string) ( $queries[ $category ] ?? 'cybersecurity computer office' );
		$context = strtolower( $post->post_title . ' ' . $post->post_excerpt );
		$topic_queries = array(
			'firewall network appliance server rack' => array( 'sonicwall', 'sonicos', 'fortinet', 'fortios', 'palo alto', 'pan-os', 'check point' ),
			'network switch data center'              => array( 'cisco', 'router', 'switch', 'vpn', 'gateway' ),
			'enterprise windows computer'             => array( 'microsoft', 'windows', 'sharepoint', 'exchange', 'active directory' ),
			'linux server terminal data center'       => array( 'linux', 'ubuntu', 'kernel', 'gnu', 'debian', 'red hat' ),
			'software developer code laptop'          => array( 'wordpress', 'github', 'teamcity', 'docker', 'kubernetes', 'jenkins', 'devops' ),
			'artificial intelligence computer lab'    => array( 'openai', 'langflow', 'artificial intelligence', ' ai ', 'model', 'prompt' ),
			'cloud server data center'                 => array( 'aws', 'azure', 'cloud', 'cluster' ),
		);
		foreach ( $topic_queries as $topic_query => $needles ) {
			foreach ( $needles as $needle ) {
				if ( false !== strpos( ' ' . $context . ' ', $needle ) ) {
					$query = $topic_query;
					break 2;
				}
			}
		}
		$page  = 1 + ( self::seed( $post->ID, $post->post_title ) % 3 );
		$key   = 'isnx_openverse_' . md5( $query . '|' . $page );
		$items = get_transient( $key );

		if ( ! is_array( $items ) ) {
			$url = add_query_arg(
				array(
					'q'            => $query,
					'page'         => $page,
					'page_size'    => 30,
					'category'     => 'photograph',
					'aspect_ratio' => 'wide',
					'size'         => 'large',
					'license'      => 'cc0,pdm,by,by-sa',
					'mature'       => 'false',
				),
				'https://api.openverse.org/v1/images/'
			);
			$response = wp_safe_remote_get(
				$url,
				array(
					'timeout'     => 10,
					'redirection' => 3,
				)
			);
			if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
				return array();
			}

			$decoded = json_decode( wp_remote_retrieve_body( $response ), true );
			$items   = is_array( $decoded['results'] ?? null ) ? $decoded['results'] : array();
			set_transient( $key, $items, 12 * HOUR_IN_SECONDS );
		}

		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$raw_id   = sanitize_text_field( (string) ( $item['id'] ?? '' ) );
			$download = esc_url_raw( (string) ( $item['url'] ?? '' ) );
			$page_url = esc_url_raw( (string) ( $item['foreign_landing_url'] ?? '' ) );
			$width    = (int) ( $item['width'] ?? 0 );
			$height   = (int) ( $item['height'] ?? 0 );
			$label    = strtolower( (string) ( $item['title'] ?? '' ) . ' ' . (string) ( $item['description'] ?? '' ) );
			$source_id = 'openverse:' . $raw_id;

			if (
				'' === $raw_id
				|| '' === $download
				|| '' === $page_url
				|| $width < 1200
				|| $height < 600
				|| $width / max( 1, $height ) < 1.25
				|| preg_match( '/illustration|vector|render|generated|clipart|wallpaper/', $label )
				|| self::photo_in_use( $source_id, $post->ID )
			) {
				continue;
			}

			$license = strtoupper( sanitize_text_field( (string) ( $item['license'] ?? 'CC' ) ) );
			$version = sanitize_text_field( (string) ( $item['license_version'] ?? '' ) );
			if ( '' !== $version ) {
				$license .= ' ' . $version;
			}

			return array(
				'id'       => $source_id,
				'page'     => $page_url,
				'download' => $download,
				'provider' => 'Openverse',
				'creator'  => sanitize_text_field( (string) ( $item['creator'] ?? '' ) ),
				'license'  => $license,
			);
		}

		return array();
	}

	/**
	 * Check whether another active public post already uses a source photo.
	 *
	 * @param string $source_id Artwork source identifier.
	 * @param int    $post_id   Post currently selecting artwork.
	 * @return bool
	 */
	private static function photo_in_use( string $source_id, int $post_id ): bool {
		$attachments = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'posts_per_page' => -1,
				'post_parent__not_in' => array( $post_id ),
				'meta_key'       => self::ATTACHMENT_SOURCE_META,
				'meta_value'     => $source_id,
				'no_found_rows'  => true,
			)
		);

		foreach ( $attachments as $attachment ) {
			$parent_id = $attachment instanceof \WP_Post ? (int) $attachment->post_parent : 0;
			if ( $parent_id > 0 && self::post_reserves_photo( $parent_id ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Retired and superseded posts remain published for crawler responses, but
	 * must not reserve editorial photography from current reader-facing posts.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	private static function post_reserves_photo( int $post_id ): bool {
		if ( 'publish' !== get_post_status( $post_id ) ) {
			return false;
		}

		if ( 'staged' === (string) get_post_meta( $post_id, self::RETIREMENT_STATE_META, true ) ) {
			return false;
		}

		return (int) get_post_meta( $post_id, self::SUPERSEDED_BY_META, true ) <= 0;
	}

	/**
	 * Download one source photo and create a consistently cropped local WebP.
	 *
	 * @param string               $path  Destination WebP path.
	 * @param array<string,string> $photo Photo record.
	 * @return bool
	 */
	private static function download_and_render_photo( string $path, array $photo ): bool {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$url = (string) $photo['download'];
		if ( false !== strpos( $url, 'images.unsplash.com/' ) ) {
			$url = add_query_arg(
				array(
					'auto' => 'format',
					'fit'  => 'crop',
					'w'    => 1600,
					'q'    => 82,
				),
				$url
			);
		} elseif ( false !== strpos( $url, 'unsplash.com/photos/' ) ) {
			$url = add_query_arg(
				array(
					'force' => 'true',
					'w'     => 1600,
				),
				$url
			);
		}

		$temporary = download_url( $url, 15 );
		if ( is_wp_error( $temporary ) ) {
			return false;
		}

		$editor = wp_get_image_editor( $temporary );
		if ( is_wp_error( $editor ) ) {
			wp_delete_file( $temporary );
			return false;
		}

		$resized = $editor->resize( 1280, 720, true );
		if ( is_wp_error( $resized ) ) {
			wp_delete_file( $temporary );
			return false;
		}

		$saved = $editor->save( $path, 'image/webp' );
		wp_delete_file( $temporary );

		return ! is_wp_error( $saved ) && file_exists( $path ) && filesize( $path ) > 0;
	}

	/**
	 * Curated free-to-use Unsplash photographs grouped by editorial topic.
	 *
	 * @return array<string,array<int,array<string,string>>>
	 */
	private static function photo_library(): array {
		$downloads = array(
			'8hcIIZ8SoyU' => 'https://images.unsplash.com/photo-1684430598409-750ae3284704',
			'W4lcqyH9r8c' => 'https://images.unsplash.com/photo-1555589228-5dc844368071',
			'UjqhyXs504o' => 'https://images.unsplash.com/photo-1662819202032-d3f07430724a',
			'jXd2FSvcRr8' => 'https://images.unsplash.com/photo-1562408590-e32931084e23',
			'lVF2HLzjopw' => 'https://images.unsplash.com/photo-1620825937374-87fc7d6bddc2',
			'k27hkqXuveo' => 'https://images.unsplash.com/photo-1775519520461-6b6e068d9250',
			'vII7qKAk-9A' => 'https://images.unsplash.com/photo-1532522750741-628fde798c73',
			'hI08TetYw0g' => 'https://images.unsplash.com/photo-1771189958069-a6b00817825c',
			'N4pwMINNNL8' => 'https://images.unsplash.com/photo-1774901128275-dcba96786383',
			'Qpj1LAgh4bY' => 'https://images.unsplash.com/photo-1763568258672-7f0b6d2aeaf2',
			'ZOS4XDaMjR0' => 'https://images.unsplash.com/photo-1672307974995-cd253f7f7eeb',
			'oYzjGQ7LCVE' => 'https://images.unsplash.com/photo-1778146476147-5f8d4bd03c79',
			'xaWYIbNIOdw' => 'https://images.unsplash.com/photo-1780253256194-34e5867ccb8c',
			'2ruZpB0SkbU' => 'https://images.unsplash.com/photo-1764182130428-01fcf5f1b068',
			'sNt81Whsncg' => 'https://images.unsplash.com/photo-1749006590639-e749e6b7d84c',
			'Sz5vOCNCDMg' => 'https://images.unsplash.com/photo-1751887687443-ec4dc66e230d',
			'FO7JIlwjOtU' => 'https://images.unsplash.com/photo-1518770660439-4636190af475',
			'V_LLeXrAhpQ' => 'https://images.unsplash.com/photo-1651720602149-7789f159b028',
			'HNjWq8WPyoY' => 'https://images.unsplash.com/photo-1769794371055-54436b54577e',
			'2w0IdiEI-hg' => 'https://images.unsplash.com/photo-1701889297494-16eb5bc8dca6',
			'l_pGKO3rVx4' => 'https://images.unsplash.com/photo-1758611971270-89ce7ed506e1',
			'xjyHDnA93Pk' => 'https://images.unsplash.com/photo-1758640920659-0bb864175983',
			'2JJ3wBHu4_0' => 'https://images.unsplash.com/photo-1695668548342-c0c1ad479aee',
			'lVZjvw-u9V8' => 'https://images.unsplash.com/photo-1584169417032-d34e8d805e8b',
			'T-IN5o3kxyA' => 'https://images.unsplash.com/photo-1682559736721-c2e77ff4c650',
			'-jCY4oEMA3o' => 'https://images.unsplash.com/photo-1620843002805-05a08cb72f57',
			'fvl0zO_q0_k' => 'https://images.unsplash.com/photo-1633113088092-3460c3c9b13f',
			'FlPc9_VocJ4' => 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3',
			'hcjoTJMWCzs' => 'https://images.unsplash.com/photo-1759752394516-f3d0e9524f7f',
			'w0aMCZIW6Qc' => 'https://images.unsplash.com/photo-1578016980868-197203ff4b02',
			'KzUCuqTTAVw' => 'https://images.unsplash.com/photo-1750710583720-8b3bdd0f658a',
			'oZgzVU_B3sE' => 'https://images.unsplash.com/photo-1750711158632-5273ec9b9b86',
			'pPbz6dFruuo' => 'https://images.unsplash.com/photo-1688561807381-05137151978f',
			'1p_o11Fly9o' => 'https://images.unsplash.com/photo-1700654063682-2ab9ceef93e5',
			'ma3ypTuD88Y' => 'https://images.unsplash.com/photo-1742811631376-6e6a72f29181',
			'ISP9CdRYS28' => 'https://images.unsplash.com/photo-1752742111841-f490c48aa668',
			'edMu3cQKrho' => 'https://images.unsplash.com/photo-1535341000823-01aa350a4fbb',
			'IXHNBGTKJfw' => 'https://images.unsplash.com/photo-1461988625982-7e46a099bf4f',
			'wGlgRXVax5c' => 'https://images.unsplash.com/photo-1758873272808-5580ed7deb44',
			'ohxs8oPgQ9k' => 'https://images.unsplash.com/photo-1764755932155-dabbee87df7e',
			'KdCJ1nIkgOU' => 'https://images.unsplash.com/photo-1768839721176-2fa91fdce725',
			'lD1nt9ePX0s' => 'https://images.unsplash.com/photo-1739168283356-d1b9bd1c0954',
			'S4jSvcHYcOs' => 'https://images.unsplash.com/photo-1494083306499-e22e4a457632',
			'Qqb7MJuGp0k' => 'https://images.unsplash.com/photo-1657875984407-55472ee47990',
			'AaEQmoufHLk' => 'https://images.unsplash.com/photo-1504164996022-09080787b6b3',
			'Ek9Znm8lQ1U' => 'https://images.unsplash.com/photo-1542831371-d531d36971e6',
			'EHn4lNPnsbA' => 'https://images.unsplash.com/photo-1778370183481-ee9755da50e0',
			'zUrEj_OwLQM' => 'https://images.unsplash.com/photo-1753998943918-dd2dfc4ee6ed',
			'v-jFS1AsHXo' => 'https://images.unsplash.com/photo-1754039984985-ef607d80113a',
			'A9jp72Owzvs' => 'https://images.unsplash.com/photo-1774901128281-a884cd447af5',
			'w69Z8K-HGQU' => 'https://images.unsplash.com/photo-1744640326166-433469d102f2',
			'weJ7qyjHYwk' => 'https://images.unsplash.com/photo-1754039985008-a15410211b67',
			'VKnmszzzTig' => 'https://images.unsplash.com/photo-1753715613651-749ef230482c',
			'vSprjjDbu60' => 'https://images.unsplash.com/photo-1680992044138-ce4864c2b962',
			'3Nwt6w-KU3E' => 'https://images.unsplash.com/photo-1667264501379-c1537934c7ab',
			'-Z8cI1gs4zk' => 'https://images.unsplash.com/photo-1770681381576-fe1ca0178da1',
			'1H0zGBPOiDY' => 'https://images.unsplash.com/photo-1759752393718-7b57f6da3caa',
			'y4GHs9GEFdM' => 'https://images.unsplash.com/photo-1783683783819-e6cb806bba69',
			'vE5AKQRUs7c' => 'https://images.unsplash.com/photo-1750711731797-25c3f2551ff8',
			'TfzeRFtlkFA' => 'https://images.unsplash.com/photo-1719253480609-579ad1622c65',
			'gEtJoCN1qpM' => 'https://images.unsplash.com/photo-1773349807434-374473797148',
			'LPZy4da9aRo' => 'https://images.unsplash.com/photo-1596526131083-e8c633c948d2',
			'0aRycsfH57A' => 'https://images.unsplash.com/photo-1584438784894-089d6a62b8fa',
			'9SoCnyQmkzI' => 'https://unsplash.com/photos/9SoCnyQmkzI/download',
			'Fa9b57hffnM' => 'https://unsplash.com/photos/Fa9b57hffnM/download',
			'M5tzZtFCOfs' => 'https://unsplash.com/photos/M5tzZtFCOfs/download',
			'TB7aNN4blTQ' => 'https://unsplash.com/photos/TB7aNN4blTQ/download',
			'347I_P4ZQ0M' => 'https://unsplash.com/photos/347I_P4ZQ0M/download',
			'Bd7gNnWJBkU' => 'https://unsplash.com/photos/Bd7gNnWJBkU/download',
			'4Mw7nkQDByk' => 'https://unsplash.com/photos/4Mw7nkQDByk/download',
		);
		$creators = array(
			'LPZy4da9aRo' => 'Brett Jordan',
			'0aRycsfH57A' => 'Maxim Ilyahov',
			'9SoCnyQmkzI' => 'Jefferson Santos',
			'Fa9b57hffnM' => 'Compagnons',
			'M5tzZtFCOfs' => 'Taylor Vick',
			'347I_P4ZQ0M' => 'Herry Sucahya',
			'Bd7gNnWJBkU' => 'Andras Vas',
			'4Mw7nkQDByk' => 'Gabriel Heinzer',
		);
		$photo = static fn( string $id ): array => array(
			'id'       => $id,
			'page'     => 'https://unsplash.com/photos/' . $id,
			'download' => (string) ( $downloads[ $id ] ?? '' ),
			'provider' => 'Unsplash',
			'creator'  => (string) ( $creators[ $id ] ?? '' ),
			'license'  => 'Unsplash License',
		);
		$windows_bsod = array(
			'id'       => 'commons:windows-bsod-dell',
			'page'     => 'https://commons.wikimedia.org/wiki/File:Blue_screen_of_death_on_a_Dell_laptop.jpg',
			'download' => 'https://commons.wikimedia.org/wiki/Special:Redirect/file/Blue_screen_of_death_on_a_Dell_laptop.jpg?width=1600',
			'provider' => 'Wikimedia Commons',
			'creator'  => 'QueenBarenziah',
			'license'  => 'CC BY-SA 4.0',
		);
		$cyber_shield = array(
			'id'       => 'commons:cyber-shield-9107702',
			'page'     => 'https://commons.wikimedia.org/wiki/File:900-plus_take_part_in_Virginia_Guard-hosted_exercise_Cyber_Shield_(9107702).jpg',
			'download' => 'https://commons.wikimedia.org/wiki/Special:Redirect/file/900-plus_take_part_in_Virginia_Guard-hosted_exercise_Cyber_Shield_(9107702).jpg?width=1600',
			'provider' => 'Wikimedia Commons',
			'creator'  => 'U.S. Army photo by Sgt. 1st Class Jon Soucy',
			'license'  => 'Public domain',
		);
		$cisco_asa    = array(
			'id'       => 'commons:cisco-asa-5510',
			'page'     => 'https://commons.wikimedia.org/wiki/File:Cisco_ASA_5510.jpg',
			'download' => 'https://commons.wikimedia.org/wiki/Special:Redirect/file/Cisco_ASA_5510.jpg?width=1600',
			'provider' => 'Wikimedia Commons',
			'creator'  => 'ShakataGaNai',
			'license'  => 'CC BY-SA 3.0',
		);

		return array(
			'critical-cves' => array(
				$photo( '347I_P4ZQ0M' ),
				$photo( 'TB7aNN4blTQ' ),
				$photo( '9SoCnyQmkzI' ),
				$photo( '8hcIIZ8SoyU' ),
				$photo( 'W4lcqyH9r8c' ),
				$photo( 'UjqhyXs504o' ),
				$photo( 'jXd2FSvcRr8' ),
				$photo( 'KdCJ1nIkgOU' ),
				$photo( 'lD1nt9ePX0s' ),
			),
			'cybersecurity' => array(
				$cyber_shield,
				$photo( 'Bd7gNnWJBkU' ),
				$photo( 'Fa9b57hffnM' ),
				$photo( 'LPZy4da9aRo' ),
				$photo( '0aRycsfH57A' ),
				$photo( 'lVF2HLzjopw' ),
				$photo( 'hI08TetYw0g' ),
				$photo( '1p_o11Fly9o' ),
				$photo( 'ma3ypTuD88Y' ),
				$photo( 'S4jSvcHYcOs' ),
				$photo( 'Qqb7MJuGp0k' ),
			),
			'linux-administration' => array(
				$photo( '4Mw7nkQDByk' ),
				$photo( 'M5tzZtFCOfs' ),
				$photo( 'vII7qKAk-9A' ),
				$photo( 'N4pwMINNNL8' ),
				$photo( 'Qpj1LAgh4bY' ),
				$photo( 'ZOS4XDaMjR0' ),
				$photo( 'AaEQmoufHLk' ),
				$photo( 'Ek9Znm8lQ1U' ),
			),
			'devops' => array(
				$photo( 'oYzjGQ7LCVE' ),
				$photo( 'xaWYIbNIOdw' ),
				$photo( '2ruZpB0SkbU' ),
				$photo( 'EHn4lNPnsbA' ),
				$photo( 'zUrEj_OwLQM' ),
				$photo( 'v-jFS1AsHXo' ),
			),
			'artificial-intelligence' => array(
				$photo( 'sNt81Whsncg' ),
				$photo( 'Sz5vOCNCDMg' ),
				$photo( 'FO7JIlwjOtU' ),
				$photo( 'V_LLeXrAhpQ' ),
				$photo( 'A9jp72Owzvs' ),
				$photo( 'w69Z8K-HGQU' ),
			),
			'tutorials' => array(
				$photo( 'HNjWq8WPyoY' ),
				$photo( '2w0IdiEI-hg' ),
				$photo( 'l_pGKO3rVx4' ),
				$photo( 'xjyHDnA93Pk' ),
				$photo( 'weJ7qyjHYwk' ),
				$photo( 'VKnmszzzTig' ),
			),
			'cloud-security' => array(
				$photo( '2JJ3wBHu4_0' ),
				$photo( 'lVZjvw-u9V8' ),
				$photo( 'k27hkqXuveo' ),
				$photo( 'ISP9CdRYS28' ),
				$photo( 'vSprjjDbu60' ),
				$photo( '3Nwt6w-KU3E' ),
			),
			'windows-security' => array(
				$windows_bsod,
				$photo( '-jCY4oEMA3o' ),
				$photo( 'fvl0zO_q0_k' ),
				$photo( 'FlPc9_VocJ4' ),
				$photo( '-Z8cI1gs4zk' ),
			),
			'network-security' => array(
				$cisco_asa,
				$photo( 'w0aMCZIW6Qc' ),
				$photo( 'KzUCuqTTAVw' ),
				$photo( 'oZgzVU_B3sE' ),
				$photo( 'T-IN5o3kxyA' ),
				$photo( 'y4GHs9GEFdM' ),
				$photo( 'vE5AKQRUs7c' ),
			),
			'web-security' => array(
				$photo( 'edMu3cQKrho' ),
				$photo( 'IXHNBGTKJfw' ),
				$photo( 'wGlgRXVax5c' ),
				$photo( 'ohxs8oPgQ9k' ),
				$photo( 'TfzeRFtlkFA' ),
				$photo( 'gEtJoCN1qpM' ),
			),
		);
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
