<?php
/**
 * Admin settings.
 *
 * @package InfoSecNexusToolkit
 */

declare(strict_types=1);

namespace InfoSecNexus\Toolkit;

/**
 * Settings UI.
 */
final class Settings {
	/**
	 * Register hooks.
	 */
	public static function boot(): void {
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
	}

	/**
	 * Register settings page.
	 */
	public static function admin_menu(): void {
		add_options_page(
			__( 'InfoSecNexus Toolkit', 'infosecnexus-toolkit' ),
			__( 'InfoSecNexus Toolkit', 'infosecnexus-toolkit' ),
			'manage_options',
			'infosecnexus-toolkit',
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Register option.
	 */
	public static function register_settings(): void {
		register_setting(
			'infosecnexus_toolkit',
			OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
			)
		);
	}

	/**
	 * Sanitize settings.
	 *
	 * @param mixed $input Raw input.
	 * @return array<string,mixed>
	 */
	public static function sanitize( $input ): array {
		$input   = is_array( $input ) ? $input : array();
		$current = options();
		$output  = array();

		$output['modules'] = array();
		foreach ( Plugin::default_modules() as $module => $default ) {
			$output['modules'][ $module ] = ! empty( $input['modules'][ $module ] );
		}

		$output['cookie_text']         = sanitize_text_field( (string) ( $input['cookie_text'] ?? '' ) );
		$output['newsletter_heading']  = sanitize_text_field( (string) ( $input['newsletter_heading'] ?? '' ) );
		$output['newsletter_intro']    = sanitize_textarea_field( (string) ( $input['newsletter_intro'] ?? '' ) );
		$output['sidebar_conditions']  = self::sanitize_json_textarea( (string) ( $input['sidebar_conditions'] ?? '' ) );
		$output['maintenance_enabled'] = ! empty( $input['maintenance_enabled'] );
		$output['updates_enabled']     = ! empty( $input['updates_enabled'] );
		$output['update_manifest_url'] = esc_url_raw( (string) ( $input['update_manifest_url'] ?? Updater::default_manifest_url() ) );

		$custom_css = (string) ( $input['custom_css'] ?? '' );
		$custom_js  = (string) ( $input['custom_js'] ?? '' );
		if ( current_user_can( 'unfiltered_html' ) ) {
			$output['custom_css'] = str_replace( array( '<script', '</script' ), '', wp_unslash( $custom_css ) );
			$output['custom_js']  = str_replace( array( '<?php', '<script', '</script' ), '', wp_unslash( $custom_js ) );
		} else {
			$output['custom_css'] = (string) ( $current['custom_css'] ?? '' );
			$output['custom_js']  = (string) ( $current['custom_js'] ?? '' );
		}

		return $output;
	}

	/**
	 * Keep valid JSON textareas or empty.
	 *
	 * @param string $json JSON string.
	 * @return string
	 */
	private static function sanitize_json_textarea( string $json ): string {
		$json = trim( wp_unslash( $json ) );
		if ( '' === $json ) {
			return '';
		}
		$decoded = json_decode( $json, true );
		return is_array( $decoded ) ? wp_json_encode( $decoded, JSON_PRETTY_PRINT ) : '';
	}

	/**
	 * Render settings page.
	 */
	public static function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$options = options();
		$modules = is_array( $options['modules'] ?? null ) ? $options['modules'] : Plugin::default_modules();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'InfoSecNexus Toolkit', 'infosecnexus-toolkit' ); ?></h1>
			<?php if ( ! empty( $_GET['infosecnexus_updates_checked'] ) ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Private update cache cleared. Open Dashboard > Updates and run Check Again if WordPress has not refreshed yet.', 'infosecnexus-toolkit' ); ?></p></div>
			<?php endif; ?>
			<p><?php esc_html_e( 'Modules load only when enabled and when their dependencies are available.', 'infosecnexus-toolkit' ); ?></p>
			<form method="post" action="options.php">
				<?php settings_fields( 'infosecnexus_toolkit' ); ?>
				<h2><?php esc_html_e( 'Private Updates', 'infosecnexus-toolkit' ); ?></h2>
				<p><?php esc_html_e( 'Use a self-hosted release manifest so WordPress can show update buttons for the InfoSecNexus theme and toolkit plugin.', 'infosecnexus-toolkit' ); ?></p>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Enable Update Channel', 'infosecnexus-toolkit' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( OPTION_KEY ); ?>[updates_enabled]" value="1" <?php checked( (bool) option( 'updates_enabled', true ) ); ?>>
								<?php esc_html_e( 'Show private updates when a newer release is published.', 'infosecnexus-toolkit' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="isnx-update-manifest-url"><?php esc_html_e( 'Manifest URL', 'infosecnexus-toolkit' ); ?></label></th>
						<td>
							<input id="isnx-update-manifest-url" class="regular-text code" type="url" name="<?php echo esc_attr( OPTION_KEY ); ?>[update_manifest_url]" value="<?php echo esc_attr( (string) option( 'update_manifest_url', Updater::default_manifest_url() ) ); ?>">
							<p class="description"><?php esc_html_e( 'Default: https://github.com/sungjinwoo-vps/isn-theme-plugin-by-yp/releases/latest/download/infosecnexus-releases.json', 'infosecnexus-toolkit' ); ?></p>
							<p><a class="button" href="<?php echo esc_url( Updater::check_now_url() ); ?>"><?php esc_html_e( 'Check Private Updates Now', 'infosecnexus-toolkit' ); ?></a></p>
						</td>
					</tr>
				</table>
				<h2><?php esc_html_e( 'Modules', 'infosecnexus-toolkit' ); ?></h2>
				<table class="form-table" role="presentation">
					<tbody>
					<?php foreach ( Plugin::default_modules() as $module => $default ) : ?>
						<tr>
							<th scope="row"><?php echo esc_html( ucwords( str_replace( '_', ' ', $module ) ) ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="<?php echo esc_attr( OPTION_KEY ); ?>[modules][<?php echo esc_attr( $module ); ?>]" value="1" <?php checked( ! empty( $modules[ $module ] ) ); ?>>
									<?php esc_html_e( 'Enabled', 'infosecnexus-toolkit' ); ?>
								</label>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
				<h2><?php esc_html_e( 'Newsletter', 'infosecnexus-toolkit' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="isnx-newsletter-heading"><?php esc_html_e( 'Heading', 'infosecnexus-toolkit' ); ?></label></th>
						<td><input id="isnx-newsletter-heading" class="regular-text" name="<?php echo esc_attr( OPTION_KEY ); ?>[newsletter_heading]" value="<?php echo esc_attr( (string) option( 'newsletter_heading', '' ) ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="isnx-newsletter-intro"><?php esc_html_e( 'Intro', 'infosecnexus-toolkit' ); ?></label></th>
						<td><textarea id="isnx-newsletter-intro" class="large-text" rows="3" name="<?php echo esc_attr( OPTION_KEY ); ?>[newsletter_intro]"><?php echo esc_textarea( (string) option( 'newsletter_intro', '' ) ); ?></textarea></td>
					</tr>
				</table>
				<h2><?php esc_html_e( 'Cookie Banner', 'infosecnexus-toolkit' ); ?></h2>
				<p><?php esc_html_e( 'This banner stores a local preference only. It is not a legal compliance guarantee.', 'infosecnexus-toolkit' ); ?></p>
				<textarea class="large-text" rows="3" name="<?php echo esc_attr( OPTION_KEY ); ?>[cookie_text]"><?php echo esc_textarea( (string) option( 'cookie_text', '' ) ); ?></textarea>
				<h2><?php esc_html_e( 'Conditional Sidebars JSON', 'infosecnexus-toolkit' ); ?></h2>
				<p><?php esc_html_e( 'Example: [{"sidebar":"infosecnexus-conditional-1","conditions":{"include":[{"type":"category","value":"critical-cves"}]}}]', 'infosecnexus-toolkit' ); ?></p>
				<textarea class="large-text code" rows="7" name="<?php echo esc_attr( OPTION_KEY ); ?>[sidebar_conditions]"><?php echo esc_textarea( (string) option( 'sidebar_conditions', '' ) ); ?></textarea>
				<h2><?php esc_html_e( 'Custom CSS and JavaScript', 'infosecnexus-toolkit' ); ?></h2>
				<p><strong><?php esc_html_e( 'Warning:', 'infosecnexus-toolkit' ); ?></strong> <?php esc_html_e( 'JavaScript can change frontend behavior. PHP execution and script tags are blocked.', 'infosecnexus-toolkit' ); ?></p>
				<textarea class="large-text code" rows="7" name="<?php echo esc_attr( OPTION_KEY ); ?>[custom_css]"><?php echo esc_textarea( (string) option( 'custom_css', '' ) ); ?></textarea>
				<textarea class="large-text code" rows="7" name="<?php echo esc_attr( OPTION_KEY ); ?>[custom_js]"><?php echo esc_textarea( (string) option( 'custom_js', '' ) ); ?></textarea>
				<h2><?php esc_html_e( 'Maintenance', 'infosecnexus-toolkit' ); ?></h2>
				<label>
					<input type="checkbox" name="<?php echo esc_attr( OPTION_KEY ); ?>[maintenance_enabled]" value="1" <?php checked( (bool) option( 'maintenance_enabled', false ) ); ?>>
					<?php esc_html_e( 'Serve a temporary 503 page to logged-out visitors.', 'infosecnexus-toolkit' ); ?>
				</label>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
