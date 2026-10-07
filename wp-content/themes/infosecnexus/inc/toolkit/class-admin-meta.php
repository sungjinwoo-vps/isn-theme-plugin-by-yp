<?php
/**
 * Per-content presentation controls.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

namespace InfoSecNexus\Theme\Toolkit;

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
		add_filter( 'manage_post_posts_columns', array( __CLASS__, 'post_columns' ) );
		add_action( 'manage_post_posts_custom_column', array( __CLASS__, 'render_post_column' ), 10, 2 );
	}

	/**
	 * Add metabox.
	 */
	public static function add_meta_boxes(): void {
		$post_types = get_post_types( array( 'public' => true ), 'names' );
		foreach ( $post_types as $post_type ) {
			add_meta_box( 'isnx-presentation', __( 'InfoSecNexus Presentation', 'infosecnexus' ), array( __CLASS__, 'render' ), $post_type, 'side' );
		}

		add_meta_box( 'isnx-seo-targeting', __( 'InfoSecNexus SEO Targeting', 'infosecnexus' ), array( __CLASS__, 'render_seo' ), 'post', 'normal', 'high' );
	}

	/**
	 * Render the post keyword ownership controls.
	 *
	 * @param \WP_Post $post Post.
	 */
	public static function render_seo( \WP_Post $post ): void {
		wp_nonce_field( 'isnx_seo_targeting_meta', 'isnx_seo_targeting_nonce' );
		$primary    = (string) get_post_meta( $post->ID, '_infosecnexus_seo_primary_keyword', true );
		$stored     = (string) get_post_meta( $post->ID, '_infosecnexus_seo_keywords', true );
		$keywords   = self::sanitize_keywords( $stored );
		$primary    = '' !== $primary ? $primary : (string) ( $keywords[0] ?? '' );
		$manual     = '1' === (string) get_post_meta( $post->ID, '_infosecnexus_seo_keywords_manual', true );
		$supporting = array_values(
			array_filter(
				$keywords,
				static function ( string $keyword ) use ( $primary ): bool {
					return 0 !== strcasecmp( $keyword, $primary );
				}
			)
		);
		?>
		<p><?php esc_html_e( 'Assign one specific search phrase to this URL. Supporting phrases should be close variants, products, vendors, or vulnerability classes used naturally in the article.', 'infosecnexus' ); ?></p>
		<p>
			<label for="isnx-seo-primary"><strong><?php esc_html_e( 'Primary search phrase', 'infosecnexus' ); ?></strong></label>
			<input class="widefat" id="isnx-seo-primary" type="text" name="isnx_seo_primary_keyword" maxlength="120" value="<?php echo esc_attr( $primary ); ?>" placeholder="<?php esc_attr_e( 'Example: Microsoft WinSock use-after-free vulnerability', 'infosecnexus' ); ?>">
		</p>
		<p>
			<label for="isnx-seo-supporting"><strong><?php esc_html_e( 'Supporting phrases', 'infosecnexus' ); ?></strong></label>
			<textarea class="widefat" id="isnx-seo-supporting" name="isnx_seo_supporting_keywords" rows="3" placeholder="<?php esc_attr_e( 'Comma-separated, up to 9 phrases', 'infosecnexus' ); ?>"><?php echo esc_textarea( implode( ', ', $supporting ) ); ?></textarea>
		</p>
		<p><label><input type="checkbox" name="isnx_seo_keywords_manual" value="1" <?php checked( $manual ); ?>> <?php esc_html_e( 'Lock this mapping so newsroom automation cannot overwrite it', 'infosecnexus' ); ?></label></p>
		<?php
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
			<label for="isnx-layout"><?php esc_html_e( 'Layout', 'infosecnexus' ); ?></label>
			<select class="widefat" id="isnx-layout" name="isnx_layout">
				<?php foreach ( self::layouts() as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $layout, $key ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<p><label><input type="checkbox" name="isnx_hide_title" value="1" <?php checked( $hide_title ); ?>> <?php esc_html_e( 'Hide page title', 'infosecnexus' ); ?></label></p>
		<p><label><input type="checkbox" name="isnx_hide_header" value="1" <?php checked( $hide_header ); ?>> <?php esc_html_e( 'Hide header', 'infosecnexus' ); ?></label></p>
		<p><label><input type="checkbox" name="isnx_hide_footer" value="1" <?php checked( $hide_footer ); ?>> <?php esc_html_e( 'Hide footer', 'infosecnexus' ); ?></label></p>
		<p>
			<label for="isnx-featured-video"><?php esc_html_e( 'Featured video URL', 'infosecnexus' ); ?></label>
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
			'content-sidebar' => __( 'Right sidebar', 'infosecnexus' ),
			'sidebar-content' => __( 'Left sidebar', 'infosecnexus' ),
			'no-sidebar'      => __( 'No sidebar', 'infosecnexus' ),
			'narrow'          => __( 'Narrow', 'infosecnexus' ),
			'wide'            => __( 'Wide', 'infosecnexus' ),
			'full-width'      => __( 'Full width', 'infosecnexus' ),
		);
	}

	/**
	 * Save metadata.
	 *
	 * @param int $post_id Post ID.
	 */
	public static function save( int $post_id ): void {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$presentation_nonce = isset( $_POST['isnx_presentation_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['isnx_presentation_nonce'] ) ) : '';
		if ( '' !== $presentation_nonce && wp_verify_nonce( $presentation_nonce, 'isnx_presentation_meta' ) ) {
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

		$seo_nonce = isset( $_POST['isnx_seo_targeting_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['isnx_seo_targeting_nonce'] ) ) : '';
		if ( 'post' === get_post_type( $post_id ) && '' !== $seo_nonce && wp_verify_nonce( $seo_nonce, 'isnx_seo_targeting_meta' ) ) {
			self::save_seo( $post_id );
		}
	}

	/**
	 * Add keyword ownership to the Posts screen.
	 *
	 * @param array<string,string> $columns Existing columns.
	 * @return array<string,string>
	 */
	public static function post_columns( array $columns ): array {
		$updated = array();
		foreach ( $columns as $key => $label ) {
			$updated[ $key ] = $label;
			if ( 'title' === $key ) {
				$updated['isnx_primary_keyword'] = __( 'Primary SEO target', 'infosecnexus' );
			}
		}

		return $updated;
	}

	/**
	 * Render the keyword ownership column.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Post ID.
	 */
	public static function render_post_column( string $column, int $post_id ): void {
		if ( 'isnx_primary_keyword' !== $column ) {
			return;
		}

		$primary = (string) get_post_meta( $post_id, '_infosecnexus_seo_primary_keyword', true );
		if ( '' === $primary ) {
			$keywords = self::sanitize_keywords( (string) get_post_meta( $post_id, '_infosecnexus_seo_keywords', true ) );
			$primary  = (string) ( $keywords[0] ?? '' );
		}
		if ( '' === $primary ) {
			echo '<span aria-hidden="true">&mdash;</span><span class="screen-reader-text">' . esc_html__( 'Not mapped', 'infosecnexus' ) . '</span>';
			return;
		}

		echo '<strong>' . esc_html( $primary ) . '</strong>';
		if ( '1' === (string) get_post_meta( $post_id, '_infosecnexus_seo_keywords_manual', true ) ) {
			echo '<br><small>' . esc_html__( 'Manual lock', 'infosecnexus' ) . '</small>';
		}
	}

	/**
	 * Save focused keywords and common SEO-plugin compatibility fields.
	 *
	 * @param int $post_id Post ID.
	 */
	private static function save_seo( int $post_id ): void {
		$primary    = self::limit_keyword( sanitize_text_field( wp_unslash( $_POST['isnx_seo_primary_keyword'] ?? '' ) ) );
		$supporting = self::sanitize_keywords( (string) wp_unslash( $_POST['isnx_seo_supporting_keywords'] ?? '' ) );
		$keywords   = self::sanitize_keywords( implode( ', ', array_merge( array( $primary ), $supporting ) ) );
		$primary    = (string) ( $keywords[0] ?? '' );
		$joined     = implode( ', ', $keywords );

		update_post_meta( $post_id, '_infosecnexus_seo_primary_keyword', $primary );
		update_post_meta( $post_id, '_infosecnexus_seo_keywords', $joined );
		update_post_meta( $post_id, '_infosecnexus_seo_keywords_manual', ! empty( $_POST['isnx_seo_keywords_manual'] ) ? '1' : '' );
		update_post_meta( $post_id, '_yoast_wpseo_focuskw', $primary );
		update_post_meta( $post_id, 'rank_math_focus_keyword', $joined );
		update_post_meta( $post_id, '_seopress_analysis_target_kw', $primary );
		wp_set_post_terms( $post_id, $keywords, 'post_tag', false );
	}

	/**
	 * Normalize a comma/newline separated keyword list.
	 *
	 * @param string $raw Raw keyword input.
	 * @return array<int,string>
	 */
	private static function sanitize_keywords( string $raw ): array {
		$parts    = preg_split( '/[,\r\n]+/u', $raw, -1, PREG_SPLIT_NO_EMPTY );
		$keywords = array();
		$seen     = array();
		foreach ( is_array( $parts ) ? $parts : array() as $part ) {
			$keyword = self::limit_keyword( sanitize_text_field( $part ) );
			$key     = strtolower( $keyword );
			if ( '' === $keyword || isset( $seen[ $key ] ) ) {
				continue;
			}
			$seen[ $key ] = true;
			$keywords[]   = $keyword;
			if ( 10 <= count( $keywords ) ) {
				break;
			}
		}

		return $keywords;
	}

	/**
	 * Keep one target phrase within the editor and plugin field limits.
	 *
	 * @param string $keyword Keyword.
	 */
	private static function limit_keyword( string $keyword ): string {
		return sanitize_text_field( wp_html_excerpt( trim( $keyword ), 120, '' ) );
	}
}
