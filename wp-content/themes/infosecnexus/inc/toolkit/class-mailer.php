<?php
/**
 * Outbound mail delivery and diagnostics.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

namespace InfoSecNexus\Theme\Toolkit;

/**
 * Configure WordPress mail without requiring a separate SMTP plugin.
 */
final class Mailer {
	private const STATUS_OPTION            = 'infosecnexus_mail_delivery_status';
	private const PRIMARY_EMAIL            = 'yashpatel@infosecnexus.com';
	private const LEGACY_EMAIL             = 'contact@infosecnexus.com';
	private const EMAIL_MIGRATION_OPTION   = 'infosecnexus_primary_email_migration';
	private const EMAIL_MIGRATION_VERSION  = '1';

	/**
	 * Register mail hooks.
	 */
	public static function boot(): void {
		add_action( 'init', array( __CLASS__, 'migrate_legacy_email' ), 5 );
		add_filter( 'wp_mail_from', array( __CLASS__, 'filter_from_email' ) );
		add_filter( 'wp_mail_from_name', array( __CLASS__, 'filter_from_name' ) );
		add_action( 'phpmailer_init', array( __CLASS__, 'configure_phpmailer' ) );
		add_action( 'wp_mail_failed', array( __CLASS__, 'record_failure' ) );
		add_action( 'wp_mail_succeeded', array( __CLASS__, 'record_success' ), 10, 0 );
		add_action( 'admin_post_infosecnexus_test_mail', array( __CLASS__, 'handle_test_mail' ) );
	}

	/**
	 * Send a plain-text message.
	 *
	 * @param string          $to Recipient.
	 * @param string          $subject Subject.
	 * @param string          $message Body.
	 * @param string|string[] $headers Extra headers.
	 */
	public static function send( string $to, string $subject, string $message, $headers = array() ): bool {
		if ( ! is_email( $to ) ) {
			return false;
		}

		$headers   = is_array( $headers ) ? $headers : array( $headers );
		$headers[] = 'Content-Type: text/plain; charset=UTF-8';

		$sent = wp_mail(
			$to,
			wp_strip_all_tags( $subject ),
			$message,
			array_values( array_unique( array_filter( $headers ) ) )
		);

		// API-based transports may replace wp_mail() without firing the
		// wp_mail_succeeded action after their service accepts a message.
		if ( $sent ) {
			self::record_success();
		} elseif ( 'failed' !== ( self::last_status()['status'] ?? '' ) ) {
			update_option(
				self::STATUS_OPTION,
				array(
					'status'  => 'failed',
					'time'    => current_time( 'mysql' ),
					'message' => __( 'The configured mail transport rejected the message.', 'infosecnexus' ),
				),
				false
			);
		}

		return $sent;
	}

	/**
	 * Recipient for contact and subscription notifications.
	 */
	public static function recipient_email(): string {
		$configured = self::constant_or_option( 'INFOSECNEXUS_CONTACT_RECIPIENT', 'contact_recipient', '' );
		if ( self::LEGACY_EMAIL === strtolower( $configured ) ) {
			return self::PRIMARY_EMAIL;
		}
		if ( is_email( $configured ) ) {
			return sanitize_email( $configured );
		}

		$admin_email = sanitize_email( (string) get_option( 'admin_email', '' ) );
		if ( self::LEGACY_EMAIL === strtolower( $admin_email ) ) {
			return self::PRIMARY_EMAIL;
		}
		return is_email( $admin_email ) ? $admin_email : self::default_from_email();
	}

	/**
	 * Sender address used by WordPress mail.
	 */
	public static function from_email(): string {
		$configured = self::constant_or_option( 'INFOSECNEXUS_MAIL_FROM_EMAIL', 'mail_from_email', '' );
		if ( self::LEGACY_EMAIL === strtolower( $configured ) ) {
			return self::PRIMARY_EMAIL;
		}
		return is_email( $configured ) ? sanitize_email( $configured ) : self::default_from_email();
	}

	/**
	 * Move the former site mailbox to the current authorized Zoho sender.
	 */
	public static function migrate_legacy_email(): void {
		if ( self::EMAIL_MIGRATION_VERSION === (string) get_option( self::EMAIL_MIGRATION_OPTION, '' ) ) {
			return;
		}

		$options = options();
		$changed = false;
		foreach ( array( 'contact_recipient', 'mail_from_email' ) as $key ) {
			if ( self::LEGACY_EMAIL === strtolower( trim( (string) ( $options[ $key ] ?? '' ) ) ) ) {
				$options[ $key ] = self::PRIMARY_EMAIL;
				$changed         = true;
			}
		}

		if ( $changed ) {
			update_option( OPTION_KEY, $options, false );
		}

		if ( self::LEGACY_EMAIL === strtolower( sanitize_email( (string) get_option( 'admin_email', '' ) ) ) ) {
			update_option( 'admin_email', self::PRIMARY_EMAIL, false );
		}

		$query = new \WP_Query(
			array(
				'post_type'      => 'any',
				'post_status'    => array( 'publish', 'future', 'draft', 'pending', 'private', 'trash', 'inherit' ),
				'posts_per_page' => -1,
				'fields'         => 'ids',
				's'              => self::LEGACY_EMAIL,
				'no_found_rows'  => true,
			)
		);

		foreach ( array_map( 'intval', $query->posts ) as $post_id ) {
			$post = get_post( $post_id );
			if ( ! $post ) {
				continue;
			}

			$content = str_ireplace( self::LEGACY_EMAIL, self::PRIMARY_EMAIL, (string) $post->post_content );
			$excerpt = str_ireplace( self::LEGACY_EMAIL, self::PRIMARY_EMAIL, (string) $post->post_excerpt );
			if ( $content === $post->post_content && $excerpt === $post->post_excerpt ) {
				continue;
			}

			wp_update_post(
				wp_slash(
					array(
						'ID'           => $post_id,
						'post_content' => $content,
						'post_excerpt' => $excerpt,
					)
				)
			);
		}

		update_option( self::EMAIL_MIGRATION_OPTION, self::EMAIL_MIGRATION_VERSION, false );
	}

	/**
	 * Sender name used by WordPress mail.
	 */
	public static function from_name(): string {
		$configured = self::constant_or_option( 'INFOSECNEXUS_MAIL_FROM_NAME', 'mail_from_name', '' );
		return '' !== trim( $configured ) ? sanitize_text_field( $configured ) : 'InfoSecNexus';
	}

	/**
	 * Replace WordPress's generic sender when the configured address is valid.
	 *
	 * @param string $email Existing sender.
	 */
	public static function filter_from_email( string $email ): string {
		$from = self::from_email();
		return is_email( $from ) ? $from : $email;
	}

	/**
	 * Replace WordPress's generic sender name.
	 *
	 * @param string $name Existing sender name.
	 */
	public static function filter_from_name( string $name ): string {
		$from = self::from_name();
		return '' !== $from ? $from : $name;
	}

	/**
	 * Configure PHPMailer when built-in SMTP is enabled.
	 *
	 * @param object $phpmailer WordPress PHPMailer instance.
	 */
	public static function configure_phpmailer( $phpmailer ): void {
		if ( ! self::smtp_enabled() ) {
			return;
		}

		$host = self::constant_or_option( 'INFOSECNEXUS_SMTP_HOST', 'smtp_host', '' );
		if ( '' === trim( $host ) || ! method_exists( $phpmailer, 'isSMTP' ) ) {
			return;
		}

		$port       = (int) self::constant_or_option( 'INFOSECNEXUS_SMTP_PORT', 'smtp_port', '587' );
		$encryption = strtolower( self::constant_or_option( 'INFOSECNEXUS_SMTP_ENCRYPTION', 'smtp_encryption', 'tls' ) );
		$username   = self::constant_or_option( 'INFOSECNEXUS_SMTP_USERNAME', 'smtp_username', '' );
		$password   = defined( 'INFOSECNEXUS_SMTP_PASSWORD' )
			? (string) constant( 'INFOSECNEXUS_SMTP_PASSWORD' )
			: self::decrypt_secret( (string) option( 'smtp_password', '' ) );

		$phpmailer->isSMTP();
		// PHPMailer exposes these public properties with its own naming API.
		// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$phpmailer->Host        = sanitize_text_field( $host );
		$phpmailer->Port        = $port > 0 ? $port : 587;
		$phpmailer->SMTPAuth    = '' !== $username;
		$phpmailer->Username    = sanitize_text_field( $username );
		$phpmailer->Password    = $password;
		$phpmailer->Timeout     = 15;
		$phpmailer->SMTPAutoTLS = 'none' !== $encryption;

		if ( in_array( $encryption, array( 'tls', 'ssl' ), true ) ) {
			$phpmailer->SMTPSecure = $encryption;
		} else {
			$phpmailer->SMTPSecure = '';
		}
		// phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	}

	/**
	 * Whether SMTP should replace the server's default mail transport.
	 */
	public static function smtp_enabled(): bool {
		if ( defined( 'INFOSECNEXUS_SMTP_HOST' ) && '' !== trim( (string) constant( 'INFOSECNEXUS_SMTP_HOST' ) ) ) {
			return true;
		}

		return (bool) option( 'smtp_enabled', false );
	}

	/**
	 * Encrypt an SMTP password before writing it to wp_options.
	 *
	 * @param string $secret Plain-text secret.
	 */
	public static function encrypt_secret( string $secret ): string {
		if ( '' === $secret || ! function_exists( 'openssl_encrypt' ) ) {
			return '';
		}

		try {
			$iv         = random_bytes( 12 );
			$tag        = '';
			$ciphertext = openssl_encrypt(
				$secret,
				'aes-256-gcm',
				self::encryption_key(),
				OPENSSL_RAW_DATA,
				$iv,
				$tag
			);
		} catch ( \Throwable $error ) {
			return '';
		}

		if ( false === $ciphertext || strlen( $tag ) !== 16 ) {
			return '';
		}

		// Binary ciphertext is encoded for safe wp_options storage, not code obfuscation.
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		return 'v1:' . base64_encode( $iv . $tag . $ciphertext );
	}

	/**
	 * Decrypt a stored SMTP password.
	 *
	 * @param string $encrypted Encrypted value.
	 */
	public static function decrypt_secret( string $encrypted ): string {
		if ( ! str_starts_with( $encrypted, 'v1:' ) || ! function_exists( 'openssl_decrypt' ) ) {
			return '';
		}

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		$payload = base64_decode( substr( $encrypted, 3 ), true );
		if ( false === $payload || strlen( $payload ) < 29 ) {
			return '';
		}

		$iv         = substr( $payload, 0, 12 );
		$tag        = substr( $payload, 12, 16 );
		$ciphertext = substr( $payload, 28 );
		$plaintext  = openssl_decrypt(
			$ciphertext,
			'aes-256-gcm',
			self::encryption_key(),
			OPENSSL_RAW_DATA,
			$iv,
			$tag
		);

		return is_string( $plaintext ) ? $plaintext : '';
	}

	/**
	 * Whether an encrypted SMTP password is already stored.
	 */
	public static function has_saved_password(): bool {
		return str_starts_with( (string) option( 'smtp_password', '' ), 'v1:' );
	}

	/**
	 * Last transport result for the settings screen.
	 *
	 * @return array<string,string>
	 */
	public static function last_status(): array {
		$status = get_option( self::STATUS_OPTION, array() );
		return is_array( $status ) ? array_map( 'strval', $status ) : array();
	}

	/**
	 * Record a failed wp_mail call.
	 *
	 * @param \WP_Error $error Mail error.
	 */
	public static function record_failure( \WP_Error $error ): void {
		update_option(
			self::STATUS_OPTION,
			array(
				'status'  => 'failed',
				'time'    => current_time( 'mysql' ),
				'message' => sanitize_text_field( $error->get_error_message() ),
			),
			false
		);
	}

	/**
	 * Record that the configured transport accepted a message.
	 */
	public static function record_success(): void {
		update_option(
			self::STATUS_OPTION,
			array(
				'status'  => 'accepted',
				'time'    => current_time( 'mysql' ),
				'message' => __( 'The mail transport accepted the message for delivery.', 'infosecnexus' ),
			),
			false
		);
	}

	/**
	 * Admin URL for a real delivery test.
	 */
	public static function test_url(): string {
		return wp_nonce_url(
			admin_url( 'admin-post.php?action=infosecnexus_test_mail' ),
			'infosecnexus_test_mail'
		);
	}

	/**
	 * Send a test message to the current administrator.
	 */
	public static function handle_test_mail(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to test mail delivery.', 'infosecnexus' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( 'infosecnexus_test_mail' );
		$user      = wp_get_current_user();
		$recipient = is_email( $user->user_email ) ? $user->user_email : self::recipient_email();
		$sent      = self::send(
			$recipient,
			__( 'InfoSecNexus mail delivery test', 'infosecnexus' ),
			sprintf(
				/* translators: %s: Site URL. */
				__( "This test was sent by the InfoSecNexus theme from %s.\n\nIf it reached your inbox, WordPress outbound mail is working. Also check spam or quarantine folders.", 'infosecnexus' ),
				home_url( '/' )
			)
		);

		wp_safe_redirect(
			add_query_arg(
				'infosecnexus_mail_test',
				$sent ? 'accepted' : 'failed',
				admin_url( 'themes.php?page=infosecnexus-features' )
			)
		);
		exit;
	}

	/**
	 * Read a constant first, then a theme option.
	 *
	 * @param string $constant Constant name.
	 * @param string $key Option key.
	 * @param string $fallback Fallback.
	 */
	private static function constant_or_option( string $constant, string $key, string $fallback ): string {
		if ( defined( $constant ) ) {
			return trim( (string) constant( $constant ) );
		}

		return trim( (string) option( $key, $fallback ) );
	}

	/**
	 * Default sender on the site's own domain.
	 */
	private static function default_from_email(): string {
		$host  = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
		$host  = preg_replace( '/^www\./', '', $host );
		if ( 'infosecnexus.com' === $host ) {
			return self::PRIMARY_EMAIL;
		}

		$email = 'wordpress@' . $host;

		if ( is_email( $email ) ) {
			return $email;
		}

		return sanitize_email( (string) get_option( 'admin_email', '' ) );
	}

	/**
	 * Derive an installation-specific encryption key from WordPress salts.
	 */
	private static function encryption_key(): string {
		return hash( 'sha256', wp_salt( 'auth' ) . wp_salt( 'secure_auth' ), true );
	}
}
