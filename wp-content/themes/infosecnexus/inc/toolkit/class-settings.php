<?php
/**
 * Admin settings.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

namespace InfoSecNexus\Theme\Toolkit;

/**
 * Settings UI.
 */
final class Settings {
	private const DEFAULT_MANIFEST_URL = 'https://github.com/sungjinwoo-vps/isn-theme-plugin-by-yp/releases/latest/download/infosecnexus-releases.json';

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
		add_theme_page(
			__( 'InfoSecNexus Features', 'infosecnexus' ),
			__( 'InfoSecNexus Features', 'infosecnexus' ),
			'edit_theme_options',
			'infosecnexus-features',
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Register option.
	 */
	public static function register_settings(): void {
		register_setting(
			'infosecnexus_theme_features',
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

		$output['cookie_text']               = sanitize_text_field( (string) ( $input['cookie_text'] ?? '' ) );
		$output['newsletter_heading']        = sanitize_text_field( (string) ( $input['newsletter_heading'] ?? '' ) );
		$output['newsletter_intro']          = sanitize_textarea_field( (string) ( $input['newsletter_intro'] ?? '' ) );
		$output['newsletter_digest_enabled'] = ! empty( $input['newsletter_digest_enabled'] );
		$output['contact_recipient']         = sanitize_email( (string) ( $input['contact_recipient'] ?? '' ) );
		$output['mail_from_name']            = sanitize_text_field( (string) ( $input['mail_from_name'] ?? '' ) );
		$output['mail_from_email']           = sanitize_email( (string) ( $input['mail_from_email'] ?? '' ) );
		$output['smtp_enabled']              = ! empty( $input['smtp_enabled'] );
		$output['smtp_host']                 = sanitize_text_field( (string) ( $input['smtp_host'] ?? '' ) );
		$output['smtp_port']                 = min( 65535, max( 1, absint( $input['smtp_port'] ?? 587 ) ) );
		$smtp_encryption                     = sanitize_key( (string) ( $input['smtp_encryption'] ?? 'tls' ) );
		$output['smtp_encryption']           = in_array( $smtp_encryption, array( 'tls', 'ssl', 'none' ), true ) ? $smtp_encryption : 'tls';
		$output['smtp_username']             = sanitize_text_field( (string) ( $input['smtp_username'] ?? '' ) );
		$smtp_password                       = trim( (string) ( $input['smtp_password'] ?? '' ) );
		if ( '' === $smtp_password ) {
			$output['smtp_password'] = (string) ( $current['smtp_password'] ?? '' );
		} else {
			$encrypted_password      = Mailer::encrypt_secret( wp_unslash( $smtp_password ) );
			$output['smtp_password'] = '' !== $encrypted_password ? $encrypted_password : (string) ( $current['smtp_password'] ?? '' );
		}
		$output['sidebar_conditions']     = self::sanitize_json_textarea( (string) ( $input['sidebar_conditions'] ?? '' ) );
		$output['maintenance_enabled']    = ! empty( $input['maintenance_enabled'] );
		$output['updates_enabled']        = ! empty( $input['updates_enabled'] );
		$output['update_manifest_url']    = esc_url_raw( (string) ( $input['update_manifest_url'] ?? self::default_manifest_url() ) );
		$output['daily_content_enabled']  = ! empty( $input['daily_content_enabled'] );
		$output['adsense_enabled']        = ! empty( $input['adsense_enabled'] );
		$output['adsense_side_rails']     = ! empty( $input['adsense_side_rails'] );
		$output['adsense_post_gate']      = ! empty( $input['adsense_post_gate'] );
		$output['adsense_client']         = self::sanitize_adsense_client( (string) ( $input['adsense_client'] ?? '' ) );
		$output['adsense_left_slot']      = self::sanitize_adsense_slot( (string) ( $input['adsense_left_slot'] ?? '' ) );
		$output['adsense_right_slot']     = self::sanitize_adsense_slot( (string) ( $input['adsense_right_slot'] ?? '' ) );
		$output['adsense_inarticle_slot'] = self::sanitize_adsense_slot( (string) ( $input['adsense_inarticle_slot'] ?? '' ) );

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
	 * Sanitize an AdSense client ID.
	 *
	 * @param string $client Raw client ID.
	 */
	private static function sanitize_adsense_client( string $client ): string {
		$client = trim( sanitize_text_field( $client ) );
		return preg_match( '/^ca-pub-\d{10,}$/', $client ) ? $client : '';
	}

	/**
	 * Sanitize an AdSense slot ID.
	 *
	 * @param string $slot Raw slot ID.
	 */
	private static function sanitize_adsense_slot( string $slot ): string {
		$slot = preg_replace( '/\D+/', '', $slot );
		return is_string( $slot ) ? $slot : '';
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
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			return;
		}

		$options     = options();
		$modules     = is_array( $options['modules'] ?? null ) ? $options['modules'] : Plugin::default_modules();
		$mail_status = Mailer::last_status();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'InfoSecNexus Features', 'infosecnexus' ); ?></h1>
			<p><?php esc_html_e( 'Theme-bundled content blocks, search, newsletter, sidebars, Elementor widgets, and presentation helpers. The separate toolkit plugin is no longer required.', 'infosecnexus' ); ?></p>
			<?php // Display-only result from the nonce-protected test-mail action. ?>
			<?php // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
			<?php if ( isset( $_GET['infosecnexus_mail_test'] ) ) : ?>
				<?php // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<?php $mail_test = sanitize_key( wp_unslash( (string) $_GET['infosecnexus_mail_test'] ) ); ?>
				<div class="notice <?php echo 'accepted' === $mail_test ? 'notice-success' : 'notice-error'; ?> is-dismissible">
					<p>
						<?php
						echo 'accepted' === $mail_test
							? esc_html__( 'WordPress accepted the test email for delivery. Check the inbox and spam folder.', 'infosecnexus' )
							: esc_html__( 'The test email failed. Review the SMTP settings and the delivery status below.', 'infosecnexus' );
						?>
					</p>
				</div>
			<?php endif; ?>
			<form method="post" action="options.php">
				<?php settings_fields( 'infosecnexus_theme_features' ); ?>
				<h2><?php esc_html_e( 'Theme Updates', 'infosecnexus' ); ?></h2>
				<p><?php esc_html_e( 'Use the GitHub release manifest so WordPress can show update buttons for the InfoSecNexus theme.', 'infosecnexus' ); ?></p>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Enable Update Channel', 'infosecnexus' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( OPTION_KEY ); ?>[updates_enabled]" value="1" <?php checked( (bool) option( 'updates_enabled', true ) ); ?>>
								<?php esc_html_e( 'Show private updates when a newer release is published.', 'infosecnexus' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="isnx-update-manifest-url"><?php esc_html_e( 'Manifest URL', 'infosecnexus' ); ?></label></th>
						<td>
							<input id="isnx-update-manifest-url" class="regular-text code" type="url" name="<?php echo esc_attr( OPTION_KEY ); ?>[update_manifest_url]" value="<?php echo esc_attr( (string) option( 'update_manifest_url', self::default_manifest_url() ) ); ?>">
							<p class="description"><?php esc_html_e( 'Default: https://github.com/sungjinwoo-vps/isn-theme-plugin-by-yp/releases/latest/download/infosecnexus-releases.json', 'infosecnexus' ); ?></p>
							<p><a class="button" href="<?php echo esc_url( \InfoSecNexus\Theme\Updater\check_now_url() ); ?>"><?php esc_html_e( 'Check Theme Updates Now', 'infosecnexus' ); ?></a></p>
						</td>
					</tr>
				</table>
				<h2><?php esc_html_e( 'Daily Blog Publisher', 'infosecnexus' ); ?></h2>
				<p><?php esc_html_e( 'Automatically refresh long, source-backed cybersecurity briefings from current CISA, NIST NVD, GitHub, Ubuntu, Microsoft, OpenAI, and official advisory feeds. Existing posts are preserved and source IDs are deduplicated.', 'infosecnexus' ); ?></p>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Enable Daily Content', 'infosecnexus' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( OPTION_KEY ); ?>[daily_content_enabled]" value="1" <?php checked( (bool) option( 'daily_content_enabled', true ) ); ?>>
								<?php esc_html_e( 'Refresh today\'s live category briefings near 6:30 AM and 6:30 PM in the WordPress site timezone.', 'infosecnexus' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Use Appearance > InfoSecNexus Setup to force a source refresh immediately. WordPress cron runs when the site receives traffic, so the exact minute can vary.', 'infosecnexus' ); ?></p>
						</td>
					</tr>
				</table>
				<h2><?php esc_html_e( 'Google AdSense', 'infosecnexus' ); ?></h2>
				<p><?php esc_html_e( 'Add your approved AdSense publisher and ad unit IDs. The theme will render labelled ad spaces only after valid IDs are saved.', 'infosecnexus' ); ?></p>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Enable AdSense', 'infosecnexus' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( OPTION_KEY ); ?>[adsense_enabled]" value="1" <?php checked( (bool) option( 'adsense_enabled', false ) ); ?>>
								<?php esc_html_e( 'Render configured AdSense slots on the frontend.', 'infosecnexus' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="isnx-adsense-client"><?php esc_html_e( 'Publisher ID', 'infosecnexus' ); ?></label></th>
						<td>
							<input id="isnx-adsense-client" class="regular-text code" placeholder="ca-pub-6550916382964760" name="<?php echo esc_attr( OPTION_KEY ); ?>[adsense_client]" value="<?php echo esc_attr( (string) option( 'adsense_client', 'ca-pub-6550916382964760' ) ); ?>">
							<p class="description"><?php esc_html_e( 'Auto ads use ca-pub-6550916382964760 by default and the loader is printed in the head on every frontend page.', 'infosecnexus' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Sticky Side Ads', 'infosecnexus' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( OPTION_KEY ); ?>[adsense_side_rails]" value="1" <?php checked( (bool) option( 'adsense_side_rails', true ) ); ?>>
								<?php esc_html_e( 'Show left and right desktop ad rails when there is enough screen width.', 'infosecnexus' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="isnx-adsense-left-slot"><?php esc_html_e( 'Left Rail Slot ID', 'infosecnexus' ); ?></label></th>
						<td><input id="isnx-adsense-left-slot" class="regular-text code" name="<?php echo esc_attr( OPTION_KEY ); ?>[adsense_left_slot]" value="<?php echo esc_attr( (string) option( 'adsense_left_slot', '' ) ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="isnx-adsense-right-slot"><?php esc_html_e( 'Right Rail Slot ID', 'infosecnexus' ); ?></label></th>
						<td><input id="isnx-adsense-right-slot" class="regular-text code" name="<?php echo esc_attr( OPTION_KEY ); ?>[adsense_right_slot]" value="<?php echo esc_attr( (string) option( 'adsense_right_slot', '' ) ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Post Read More Gate', 'infosecnexus' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( OPTION_KEY ); ?>[adsense_post_gate]" value="1" <?php checked( (bool) option( 'adsense_post_gate', true ) ); ?>>
								<?php esc_html_e( 'Show an in-post ad slot before the Read More unlock button on blog posts.', 'infosecnexus' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="isnx-adsense-inarticle-slot"><?php esc_html_e( 'In-Post Slot ID', 'infosecnexus' ); ?></label></th>
						<td><input id="isnx-adsense-inarticle-slot" class="regular-text code" name="<?php echo esc_attr( OPTION_KEY ); ?>[adsense_inarticle_slot]" value="<?php echo esc_attr( (string) option( 'adsense_inarticle_slot', '' ) ); ?>"></td>
					</tr>
				</table>
				<h2><?php esc_html_e( 'Modules', 'infosecnexus' ); ?></h2>
				<table class="form-table" role="presentation">
					<tbody>
					<?php foreach ( Plugin::default_modules() as $module => $default ) : ?>
						<tr>
							<th scope="row"><?php echo esc_html( ucwords( str_replace( '_', ' ', $module ) ) ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="<?php echo esc_attr( OPTION_KEY ); ?>[modules][<?php echo esc_attr( $module ); ?>]" value="1" <?php checked( ! empty( $modules[ $module ] ) ); ?>>
									<?php esc_html_e( 'Enabled', 'infosecnexus' ); ?>
								</label>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
				<h2><?php esc_html_e( 'Newsletter', 'infosecnexus' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="isnx-newsletter-heading"><?php esc_html_e( 'Heading', 'infosecnexus' ); ?></label></th>
						<td><input id="isnx-newsletter-heading" class="regular-text" name="<?php echo esc_attr( OPTION_KEY ); ?>[newsletter_heading]" value="<?php echo esc_attr( (string) option( 'newsletter_heading', '' ) ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="isnx-newsletter-intro"><?php esc_html_e( 'Intro', 'infosecnexus' ); ?></label></th>
						<td><textarea id="isnx-newsletter-intro" class="large-text" rows="3" name="<?php echo esc_attr( OPTION_KEY ); ?>[newsletter_intro]"><?php echo esc_textarea( (string) option( 'newsletter_intro', '' ) ); ?></textarea></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Daily Email Digest', 'infosecnexus' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( OPTION_KEY ); ?>[newsletter_digest_enabled]" value="1" <?php checked( (bool) option( 'newsletter_digest_enabled', true ) ); ?>>
								<?php esc_html_e( 'Email confirmed subscribers after the morning briefing refresh.', 'infosecnexus' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'The digest is scheduled near 7:15 AM in the WordPress site timezone and includes unsubscribe links.', 'infosecnexus' ); ?></p>
						</td>
					</tr>
				</table>
				<h2><?php esc_html_e( 'Email Delivery', 'infosecnexus' ); ?></h2>
				<p><?php esc_html_e( 'Contact messages are always stored privately in Tools > Contact Messages. Email notifications use WordPress mail or the optional built-in SMTP transport below.', 'infosecnexus' ); ?></p>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="isnx-contact-recipient"><?php esc_html_e( 'Admin Recipient', 'infosecnexus' ); ?></label></th>
						<td><input id="isnx-contact-recipient" class="regular-text" type="email" autocomplete="email" name="<?php echo esc_attr( OPTION_KEY ); ?>[contact_recipient]" value="<?php echo esc_attr( (string) option( 'contact_recipient', Mailer::recipient_email() ) ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="isnx-mail-from-name"><?php esc_html_e( 'Sender Name', 'infosecnexus' ); ?></label></th>
						<td><input id="isnx-mail-from-name" class="regular-text" name="<?php echo esc_attr( OPTION_KEY ); ?>[mail_from_name]" value="<?php echo esc_attr( (string) option( 'mail_from_name', 'InfoSecNexus' ) ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="isnx-mail-from-email"><?php esc_html_e( 'Sender Email', 'infosecnexus' ); ?></label></th>
						<td>
							<input id="isnx-mail-from-email" class="regular-text" type="email" autocomplete="email" name="<?php echo esc_attr( OPTION_KEY ); ?>[mail_from_email]" value="<?php echo esc_attr( (string) option( 'mail_from_email', Mailer::from_email() ) ); ?>">
							<p class="description"><?php esc_html_e( 'Use an address on this site domain that your mail provider authorizes.', 'infosecnexus' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Use SMTP', 'infosecnexus' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( OPTION_KEY ); ?>[smtp_enabled]" value="1" <?php checked( (bool) option( 'smtp_enabled', false ) ); ?>>
								<?php esc_html_e( 'Send through an authenticated SMTP mailbox instead of the server mail command.', 'infosecnexus' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="isnx-smtp-host"><?php esc_html_e( 'SMTP Host', 'infosecnexus' ); ?></label></th>
						<td><input id="isnx-smtp-host" class="regular-text code" autocomplete="off" name="<?php echo esc_attr( OPTION_KEY ); ?>[smtp_host]" value="<?php echo esc_attr( (string) option( 'smtp_host', '' ) ); ?>" placeholder="smtp.example.com"></td>
					</tr>
					<tr>
						<th scope="row"><label for="isnx-smtp-port"><?php esc_html_e( 'SMTP Port', 'infosecnexus' ); ?></label></th>
						<td><input id="isnx-smtp-port" class="small-text" type="number" min="1" max="65535" name="<?php echo esc_attr( OPTION_KEY ); ?>[smtp_port]" value="<?php echo esc_attr( (string) option( 'smtp_port', 587 ) ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="isnx-smtp-encryption"><?php esc_html_e( 'Encryption', 'infosecnexus' ); ?></label></th>
						<td>
							<select id="isnx-smtp-encryption" name="<?php echo esc_attr( OPTION_KEY ); ?>[smtp_encryption]">
								<option value="tls" <?php selected( (string) option( 'smtp_encryption', 'tls' ), 'tls' ); ?>>TLS</option>
								<option value="ssl" <?php selected( (string) option( 'smtp_encryption', 'tls' ), 'ssl' ); ?>>SSL</option>
								<option value="none" <?php selected( (string) option( 'smtp_encryption', 'tls' ), 'none' ); ?>><?php esc_html_e( 'None', 'infosecnexus' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="isnx-smtp-username"><?php esc_html_e( 'SMTP Username', 'infosecnexus' ); ?></label></th>
						<td><input id="isnx-smtp-username" class="regular-text" autocomplete="username" name="<?php echo esc_attr( OPTION_KEY ); ?>[smtp_username]" value="<?php echo esc_attr( (string) option( 'smtp_username', '' ) ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="isnx-smtp-password"><?php esc_html_e( 'SMTP Password', 'infosecnexus' ); ?></label></th>
						<td>
							<input id="isnx-smtp-password" class="regular-text" type="password" autocomplete="new-password" name="<?php echo esc_attr( OPTION_KEY ); ?>[smtp_password]" value="" placeholder="<?php echo Mailer::has_saved_password() ? esc_attr__( 'Saved securely - leave blank to keep it', 'infosecnexus' ) : ''; ?>">
							<p class="description"><?php esc_html_e( 'The password is encrypted with installation-specific WordPress salts before storage. Leave blank to keep the saved value.', 'infosecnexus' ); ?></p>
						</td>
					</tr>
				</table>
				<?php if ( ! empty( $mail_status ) ) : ?>
					<p>
						<strong><?php esc_html_e( 'Last mail transport result:', 'infosecnexus' ); ?></strong>
						<?php echo esc_html( ucfirst( (string) ( $mail_status['status'] ?? '' ) ) ); ?>
						<?php if ( ! empty( $mail_status['time'] ) ) : ?>
							<?php echo esc_html( ' - ' . (string) $mail_status['time'] ); ?>
						<?php endif; ?>
						<?php if ( ! empty( $mail_status['message'] ) ) : ?>
							<?php echo esc_html( ' - ' . (string) $mail_status['message'] ); ?>
						<?php endif; ?>
					</p>
				<?php endif; ?>
				<p><a class="button" href="<?php echo esc_url( Mailer::test_url() ); ?>"><?php esc_html_e( 'Send Test Email', 'infosecnexus' ); ?></a></p>
				<p class="description"><?php esc_html_e( 'For infrastructure-managed secrets, wp-config.php constants INFOSECNEXUS_SMTP_HOST, INFOSECNEXUS_SMTP_PORT, INFOSECNEXUS_SMTP_ENCRYPTION, INFOSECNEXUS_SMTP_USERNAME, INFOSECNEXUS_SMTP_PASSWORD, INFOSECNEXUS_MAIL_FROM_EMAIL, and INFOSECNEXUS_CONTACT_RECIPIENT override these fields.', 'infosecnexus' ); ?></p>
				<h2><?php esc_html_e( 'Cookie Banner', 'infosecnexus' ); ?></h2>
				<p><?php esc_html_e( 'This banner stores a local preference only. It is not a legal compliance guarantee.', 'infosecnexus' ); ?></p>
				<textarea class="large-text" rows="3" name="<?php echo esc_attr( OPTION_KEY ); ?>[cookie_text]"><?php echo esc_textarea( (string) option( 'cookie_text', '' ) ); ?></textarea>
				<h2><?php esc_html_e( 'Conditional Sidebars JSON', 'infosecnexus' ); ?></h2>
				<p><?php esc_html_e( 'Example: [{"sidebar":"infosecnexus-conditional-1","conditions":{"include":[{"type":"category","value":"critical-cves"}]}}]', 'infosecnexus' ); ?></p>
				<textarea class="large-text code" rows="7" name="<?php echo esc_attr( OPTION_KEY ); ?>[sidebar_conditions]"><?php echo esc_textarea( (string) option( 'sidebar_conditions', '' ) ); ?></textarea>
				<h2><?php esc_html_e( 'Custom CSS and JavaScript', 'infosecnexus' ); ?></h2>
				<p><strong><?php esc_html_e( 'Warning:', 'infosecnexus' ); ?></strong> <?php esc_html_e( 'JavaScript can change frontend behavior. PHP execution and script tags are blocked.', 'infosecnexus' ); ?></p>
				<textarea class="large-text code" rows="7" name="<?php echo esc_attr( OPTION_KEY ); ?>[custom_css]"><?php echo esc_textarea( (string) option( 'custom_css', '' ) ); ?></textarea>
				<textarea class="large-text code" rows="7" name="<?php echo esc_attr( OPTION_KEY ); ?>[custom_js]"><?php echo esc_textarea( (string) option( 'custom_js', '' ) ); ?></textarea>
				<h2><?php esc_html_e( 'Maintenance', 'infosecnexus' ); ?></h2>
				<label>
					<input type="checkbox" name="<?php echo esc_attr( OPTION_KEY ); ?>[maintenance_enabled]" value="1" <?php checked( (bool) option( 'maintenance_enabled', false ) ); ?>>
					<?php esc_html_e( 'Serve a temporary 503 page to logged-out visitors.', 'infosecnexus' ); ?>
				</label>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Return the theme release manifest URL.
	 */
	private static function default_manifest_url(): string {
		if ( defined( 'InfoSecNexus\\Theme\\Updater\\DEFAULT_MANIFEST_URL' ) ) {
			return (string) constant( 'InfoSecNexus\\Theme\\Updater\\DEFAULT_MANIFEST_URL' );
		}

		return self::DEFAULT_MANIFEST_URL;
	}
}
