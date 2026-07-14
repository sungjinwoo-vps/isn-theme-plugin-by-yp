<?php
/**
 * Per-content presentation controls.
 *
 * @package InfoSecNexusToolkit
 */

declare(strict_types=1);

namespace InfoSecNexus\Toolkit;

/**
 * Admin metadata.
 */
final class Admin_Meta {
	/**
	 * Register hooks.
	 */
	public static function boot(): void {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
		add_action( 'save_post', array( __CLASS__, 'save' ) );
	}

	/**
	 * Add metabox.
	 */
	public static function add_meta_boxes(): void {
		$post_types = get_post_types( array( 'public' => true ), 'names' );
		foreach ( $post_types as $post_type ) {
			add_meta_box( 'isnx-presentation', __( 'InfoSecNexus Presentation', 'infosecnexus-toolkit' ), array( __CLASS__, 'render' ), $post_type, 'side' );
		}
	}

	/**
	 * Render metabox.
	 *
	 * @param \WP_Post $post Post.
	 */
	public static function render( \WP_Post $post ): void {
		wp_nonce_field( 'isnx_presentation_meta', 'isnx_presentation_nonce' );
		$layout      = (string) get_post_meta( $post->ID, '_infosecnexus_layout', true );
		$video       = (string) get_post_meta( $post->ID, '_infosecnexus_featured_video_url', true );
		$hide_title  = (bool) get_post_meta( $post->ID, '_infosecnexus_hide_title', true );
		$hide_header = (bool) get_post_meta( $post->ID, '_infosecnexus_hide_header', true );
		$hide_footer = (bool) get_post_meta( $post->ID, '_infosecnexus_hide_footer', true );
		$layout      = $layout ? $layout : 'content-sidebar';
		?>
		<p>
			<label for="isnx-layout"><?php esc_html_e( 'Layout', 'infosecnexus-toolkit' ); ?></label>
			<select class="widefat" id="isnx-layout" name="isnx_layout">
				<?php foreach ( self::layouts() as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $layout, $key ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<p><label><input type="checkbox" name="isnx_hide_title" value="1" <?php checked( $hide_title ); ?>> <?php esc_html_e( 'Hide page title', 'infosecnexus-toolkit' ); ?></label></p>
		<p><label><input type="checkbox" name="isnx_hide_header" value="1" <?php checked( $hide_header ); ?>> <?php esc_html_e( 'Hide header', 'infosecnexus-toolkit' ); ?></label></p>
		<p><label><input type="checkbox" name="isnx_hide_footer" value="1" <?php checked( $hide_footer ); ?>> <?php esc_html_e( 'Hide footer', 'infosecnexus-toolkit' ); ?></label></p>
		<p>
			<label for="isnx-featured-video"><?php esc_html_e( 'Featured video URL', 'infosecnexus-toolkit' ); ?></label>
			<input class="widefat" id="isnx-featured-video" type="url" name="isnx_featured_video_url" value="<?php echo esc_url( $video ); ?>">
		</p>
		<?php
	}

	/**
	 * Layout choices.
	 *
	 * @return array<string,string>
	 */
	private static function layouts(): array {
		return array(
			'content-sidebar' => __( 'Right sidebar', 'infosecnexus-toolkit' ),
			'sidebar-content' => __( 'Left sidebar', 'infosecnexus-toolkit' ),
			'no-sidebar'      => __( 'No sidebar', 'infosecnexus-toolkit' ),
			'narrow'          => __( 'Narrow', 'infosecnexus-toolkit' ),
			'wide'            => __( 'Wide', 'infosecnexus-toolkit' ),
			'full-width'      => __( 'Full width', 'infosecnexus-toolkit' ),
		);
	}

	/**
	 * Save metadata.
	 *
	 * @param int $post_id Post ID.
	 */
	public static function save( int $post_id ): void {
		if ( ! isset( $_POST['isnx_presentation_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['isnx_presentation_nonce'] ) ), 'isnx_presentation_meta' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$layout = sanitize_key( (string) ( $_POST['isnx_layout'] ?? 'content-sidebar' ) );
		if ( ! array_key_exists( $layout, self::layouts() ) ) {
			$layout = 'content-sidebar';
		}

		update_post_meta( $post_id, '_infosecnexus_layout', $layout );
		update_post_meta( $post_id, '_infosecnexus_hide_title', ! empty( $_POST['isnx_hide_title'] ) ? '1' : '' );
		update_post_meta( $post_id, '_infosecnexus_hide_header', ! empty( $_POST['isnx_hide_header'] ) ? '1' : '' );
		update_post_meta( $post_id, '_infosecnexus_hide_footer', ! empty( $_POST['isnx_hide_footer'] ) ? '1' : '' );
		update_post_meta( $post_id, '_infosecnexus_featured_video_url', esc_url_raw( (string) wp_unslash( $_POST['isnx_featured_video_url'] ?? '' ) ) );
	}
}
