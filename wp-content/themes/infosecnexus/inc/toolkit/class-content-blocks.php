<?php
/**
 * Content blocks and hook manager.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

namespace InfoSecNexus\Theme\Toolkit;

use WP_Query;

/**
 * Content blocks module.
 */
final class Content_Blocks {
	/**
	 * Register hooks.
	 */
	public static function boot(): void {
		if ( ! module_enabled( 'content_blocks' ) ) {
			return;
		}

		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
		add_action( 'save_post_isnx_content_block', array( __CLASS__, 'save_meta' ) );
		add_action( 'wp', array( __CLASS__, 'register_hook_blocks' ) );
		add_action( 'wp_footer', array( __CLASS__, 'render_popups' ) );
	}

	/**
	 * Register post type.
	 */
	public static function register_post_type(): void {
		register_post_type(
			'isnx_content_block',
			array(
				'labels'       => array(
					'name'          => __( 'Content Blocks', 'infosecnexus' ),
					'singular_name' => __( 'Content Block', 'infosecnexus' ),
				),
				'public'       => false,
				'show_ui'      => true,
				'show_in_rest' => true,
				'menu_icon'    => 'dashicons-layout',
				'supports'     => array( 'title', 'editor', 'revisions' ),
			)
		);
	}

	/**
	 * Add metaboxes.
	 */
	public static function add_meta_boxes(): void {
		add_meta_box( 'isnx-content-block-settings', __( 'Display Settings', 'infosecnexus' ), array( __CLASS__, 'render_meta_box' ), 'isnx_content_block', 'side' );
	}

	/**
	 * Render metabox.
	 *
	 * @param \WP_Post $post Post.
	 */
	public static function render_meta_box( \WP_Post $post ): void {
		wp_nonce_field( 'isnx_content_block_meta', 'isnx_content_block_nonce' );
		$type       = (string) get_post_meta( $post->ID, '_isnx_block_type', true );
		$hook       = (string) get_post_meta( $post->ID, '_isnx_hook', true );
		$priority   = (int) get_post_meta( $post->ID, '_isnx_priority', true );
		$conditions = (string) get_post_meta( $post->ID, '_isnx_conditions', true );
		$start      = (string) get_post_meta( $post->ID, '_isnx_start', true );
		$end        = (string) get_post_meta( $post->ID, '_isnx_end', true );
		$type       = $type ? $type : 'hook';
		$priority   = $priority ? $priority : 10;
		?>
		<p>
			<label for="isnx-block-type"><?php esc_html_e( 'Type', 'infosecnexus' ); ?></label>
			<select id="isnx-block-type" name="isnx_block_type" class="widefat">
				<?php foreach ( self::block_types() as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $type, $key ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<p>
			<label for="isnx-hook"><?php esc_html_e( 'Hook or location', 'infosecnexus' ); ?></label>
			<input id="isnx-hook" class="widefat" name="isnx_hook" value="<?php echo esc_attr( $hook ); ?>" placeholder="infosecnexus_before_footer">
		</p>
		<p>
			<label for="isnx-priority"><?php esc_html_e( 'Priority', 'infosecnexus' ); ?></label>
			<input id="isnx-priority" type="number" class="widefat" name="isnx_priority" value="<?php echo esc_attr( (string) $priority ); ?>">
		</p>
		<p>
			<label for="isnx-start"><?php esc_html_e( 'Start date/time', 'infosecnexus' ); ?></label>
			<input id="isnx-start" class="widefat" name="isnx_start" value="<?php echo esc_attr( $start ); ?>" placeholder="2026-07-14 09:00">
		</p>
		<p>
			<label for="isnx-end"><?php esc_html_e( 'End date/time', 'infosecnexus' ); ?></label>
			<input id="isnx-end" class="widefat" name="isnx_end" value="<?php echo esc_attr( $end ); ?>" placeholder="2026-07-15 09:00">
		</p>
		<p>
			<label for="isnx-conditions"><?php esc_html_e( 'Conditions JSON', 'infosecnexus' ); ?></label>
			<textarea id="isnx-conditions" class="widefat code" rows="8" name="isnx_conditions"><?php echo esc_textarea( $conditions ); ?></textarea>
		</p>
		<?php
	}

	/**
	 * Block types.
	 *
	 * @return array<int|string,string>
	 */
	private static function block_types(): array {
		return array(
			'hook'            => __( 'Hook', 'infosecnexus' ),
			'header'          => __( 'Header', 'infosecnexus' ),
			'footer'          => __( 'Footer', 'infosecnexus' ),
			'single'          => __( 'Single Template', 'infosecnexus' ),
			'archive'         => __( 'Archive Template', 'infosecnexus' ),
			'search'          => __( 'Search Template', 'infosecnexus' ),
			'no-results'      => __( 'No Results Template', 'infosecnexus' ),
			'404'             => __( '404 Template', 'infosecnexus' ),
			'popup'           => __( 'Popup', 'infosecnexus' ),
			'maintenance'     => __( 'Maintenance Page', 'infosecnexus' ),
			'before-content'  => __( 'Before Content', 'infosecnexus' ),
			'after-content'   => __( 'After Content', 'infosecnexus' ),
			'before-comments' => __( 'Before Comments', 'infosecnexus' ),
			'after-comments'  => __( 'After Comments', 'infosecnexus' ),
			'footer-fragment' => __( 'Footer Fragment', 'infosecnexus' ),
		);
	}

	/**
	 * Save meta.
	 *
	 * @param int $post_id Post ID.
	 */
	public static function save_meta( int $post_id ): void {
		if ( ! isset( $_POST['isnx_content_block_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['isnx_content_block_nonce'] ) ), 'isnx_content_block_meta' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$type = sanitize_key( (string) ( $_POST['isnx_block_type'] ?? 'hook' ) );
		if ( ! array_key_exists( $type, self::block_types() ) ) {
			$type = 'hook';
		}

		update_post_meta( $post_id, '_isnx_block_type', $type );
		update_post_meta( $post_id, '_isnx_hook', sanitize_key( (string) ( $_POST['isnx_hook'] ?? '' ) ) );
		update_post_meta( $post_id, '_isnx_priority', max( 1, min( 999, absint( $_POST['isnx_priority'] ?? 10 ) ) ) );
		update_post_meta( $post_id, '_isnx_start', sanitize_text_field( (string) wp_unslash( $_POST['isnx_start'] ?? '' ) ) );
		update_post_meta( $post_id, '_isnx_end', sanitize_text_field( (string) wp_unslash( $_POST['isnx_end'] ?? '' ) ) );

		$conditions = trim( sanitize_textarea_field( (string) wp_unslash( $_POST['isnx_conditions'] ?? '' ) ) );
		if ( '' !== $conditions && null === json_decode( $conditions, true ) ) {
			$conditions = '';
		}
		update_post_meta( $post_id, '_isnx_conditions', $conditions );
	}

	/**
	 * Register hook blocks against their configured hook.
	 */
	public static function register_hook_blocks(): void {
		$blocks = self::query_blocks( 'hook' );
		foreach ( $blocks as $block ) {
			$hook     = (string) get_post_meta( $block->ID, '_isnx_hook', true );
			$priority = (int) get_post_meta( $block->ID, '_isnx_priority', true );
			if ( ! $hook || ! preg_match( '/^[a-z0-9_]+$/', $hook ) ) {
				continue;
			}
			add_action(
				$hook,
				static function () use ( $block ): void {
					self::render_post( $block );
				},
				$priority ? $priority : 10
			);
		}
	}

	/**
	 * Render a location.
	 *
	 * @param string $location Location key.
	 * @return bool True if rendered.
	 */
	public static function render_location( string $location ): bool {
		$blocks = self::query_blocks( sanitize_key( $location ) );
		if ( empty( $blocks ) ) {
			return false;
		}
		foreach ( $blocks as $block ) {
			self::render_post( $block );
		}
		return true;
	}

	/**
	 * Render popup blocks.
	 */
	public static function render_popups(): void {
		foreach ( self::query_blocks( 'popup' ) as $block ) {
			echo '<div class="isnx-popup" data-isnx-popup hidden><div class="isnx-popup__panel" role="dialog" aria-modal="true" aria-labelledby="isnx-popup-title-' . esc_attr( (string) $block->ID ) . '">';
			echo '<button type="button" class="isnx-popup__close" data-isnx-popup-close aria-label="' . esc_attr__( 'Close popup', 'infosecnexus' ) . '">&times;</button>';
			echo '<h2 id="isnx-popup-title-' . esc_attr( (string) $block->ID ) . '">' . esc_html( get_the_title( $block ) ) . '</h2>';
			self::render_post( $block );
			echo '</div></div>';
		}
	}

	/**
	 * Query matching blocks.
	 *
	 * @param string $type Block type.
	 * @return \WP_Post[]
	 */
	private static function query_blocks( string $type ): array {
		$query = new WP_Query(
			array(
				'post_type'      => 'isnx_content_block',
				'post_status'    => 'publish',
				'posts_per_page' => 50,
				'orderby'        => 'menu_order date',
				'order'          => 'ASC',
			)
		);

		$blocks = array();
		foreach ( $query->posts as $post ) {
			if ( get_post_meta( $post->ID, '_isnx_block_type', true ) !== $type ) {
				continue;
			}
			if ( self::is_scheduled( $post->ID ) && Conditions::matches( get_post_meta( $post->ID, '_isnx_conditions', true ) ) ) {
				$blocks[] = $post;
			}
		}

		return $blocks;
	}

	/**
	 * Check schedule window.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	private static function is_scheduled( int $post_id ): bool {
		$start = (string) get_post_meta( $post_id, '_isnx_start', true );
		$end   = (string) get_post_meta( $post_id, '_isnx_end', true );
		$now   = current_datetime()->getTimestamp();

		if ( $start && $now < strtotime( $start ) ) {
			return false;
		}
		if ( $end && $now > strtotime( $end ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Render one content block.
	 *
	 * @param \WP_Post $post Post.
	 */
	private static function render_post( \WP_Post $post ): void {
		$content = do_blocks( $post->post_content );
		$content = wptexturize( $content );
		$content = convert_smilies( $content );
		$content = wpautop( $content );
		$content = shortcode_unautop( $content );
		$content = do_shortcode( $content );
		$content = apply_filters( 'infosecnexus_toolkit_content_block_content', $content, $post );
		echo '<div class="isnx-content-block isnx-content-block--' . esc_attr( (string) get_post_meta( $post->ID, '_isnx_block_type', true ) ) . '">';
		echo wp_kses_post( $content );
		echo '</div>';
	}
}
