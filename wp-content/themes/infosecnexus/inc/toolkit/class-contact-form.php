<?php
/**
 * Secure contact form handling.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

namespace InfoSecNexus\Theme\Toolkit;

/**
 * Store contact messages and send delivery notifications.
 */
final class Contact_Form {
	private const ACTION             = 'infosecnexus_contact_submit';
	private const POST_TYPE          = 'isnx_contact';
	private const GUARD_ROUTE        = '/contact-challenge';
	private const GUARD_MIN_AGE      = 3;
	private const GUARD_MAX_AGE      = 3600;
	private const SPAM_STATS_OPTION  = 'infosecnexus_contact_spam_stats';
	private const DUPLICATE_LIFETIME = 604800;

	/**
	 * Register hooks.
	 */
	public static function boot(): void {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'register_guard_route' ) );
		add_action( 'admin_post_' . self::ACTION, array( __CLASS__, 'handle_submission' ) );
		add_action( 'admin_post_nopriv_' . self::ACTION, array( __CLASS__, 'handle_submission' ) );
		add_shortcode( 'infosecnexus_contact_form', array( __CLASS__, 'shortcode' ) );
		add_filter( 'the_content', array( __CLASS__, 'replace_legacy_form' ), 8 );
		add_filter( 'the_content', array( __CLASS__, 'normalize_form_markup' ), 12 );
	}

	/**
	 * Register a cache-safe browser challenge for the public contact form.
	 */
	public static function register_guard_route(): void {
		register_rest_route(
			'infosecnexus/v1',
			self::GUARD_ROUTE,
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'issue_guard_challenge' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Issue a short-lived challenge that cannot be copied from a cached page.
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function issue_guard_challenge() {
		if ( ! rate_limit( 'contact_challenge', 20, 300 ) ) {
			return new \WP_Error(
				'isnx_contact_challenge_limited',
				__( 'Please wait a moment before trying again.', 'infosecnexus' ),
				array( 'status' => 429 )
			);
		}

		$issued = time();
		$nonce  = bin2hex( random_bytes( 16 ) );
		$token  = self::guard_signature( $issued, $nonce );
		$result = new \WP_REST_Response(
			array(
				'issued' => $issued,
				'nonce'  => $nonce,
				'token'  => $token,
			)
		);
		$result->header( 'Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0' );

		return $result;
	}

	/**
	 * Register the private contact inbox.
	 */
	public static function register_post_type(): void {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'          => array(
					'name'          => __( 'Contact Messages', 'infosecnexus' ),
					'singular_name' => __( 'Contact Message', 'infosecnexus' ),
					'menu_name'     => __( 'Contact Messages', 'infosecnexus' ),
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => 'tools.php',
				'capability_type' => 'post',
				'capabilities'    => array(
					'create_posts' => 'do_not_allow',
				),
				'map_meta_cap'    => true,
				'supports'        => array( 'title', 'editor', 'custom-fields' ),
			)
		);
	}

	/**
	 * Shortcode callback.
	 */
	public static function shortcode(): string {
		ob_start();
		self::render_form();
		return (string) ob_get_clean();
	}

	/**
	 * Replace the old mailto form while cached/demo page content is upgraded.
	 *
	 * @param string $content Post content.
	 */
	public static function replace_legacy_form( string $content ): string {
		if ( ! is_singular() || false === strpos( $content, 'isnx-contact-form' ) || false === strpos( $content, 'mailto:' ) ) {
			return $content;
		}

		$updated = preg_replace(
			'#<form\b[^>]*class=(["\'])[^"\']*isnx-contact-form[^"\']*\1[^>]*>.*?</form>#is',
			'[infosecnexus_contact_form]',
			$content,
			1
		);

		return is_string( $updated ) ? $updated : $content;
	}

	/**
	 * Remove a paragraph that wpautop can place before the block-level form.
	 *
	 * @param string $content Filtered post content.
	 */
	public static function normalize_form_markup( string $content ): string {
		if ( false === strpos( $content, 'isnx-contact-form' ) ) {
			return $content;
		}

		$updated = preg_replace(
			'#<p>\s*(<form\b[^>]*class=(["\'])[^"\']*isnx-contact-form[^"\']*\2[^>]*>)#i',
			'$1',
			$content,
			1
		);

		return is_string( $updated ) ? $updated : $content;
	}

	/**
	 * Render the secure same-site form.
	 */
	public static function render_form(): void {
		$token = public_form_token( self::ACTION );
		// Display-only status returned by the verified form handler.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$status = isset( $_GET['isnx_contact'] ) ? sanitize_key( wp_unslash( (string) $_GET['isnx_contact'] ) ) : '';
		$notice = self::status_notice( $status );
		?>
		<form class="isnx-contact-form" id="contact-message-form" action="<?php echo esc_url( public_form_action_url() ); ?>" method="post" accept-charset="UTF-8" autocomplete="on" data-isnx-contact-guard-url="<?php echo esc_url( rest_url( 'infosecnexus/v1' . self::GUARD_ROUTE ) ); ?>">
			<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION ); ?>">
			<input type="hidden" name="isnx_issued" value="<?php echo esc_attr( (string) $token['issued'] ); ?>">
			<input type="hidden" name="isnx_token" value="<?php echo esc_attr( $token['token'] ); ?>">
			<input type="hidden" name="isnx_guard_issued" value="">
			<input type="hidden" name="isnx_guard_nonce" value="">
			<input type="hidden" name="isnx_guard_token" value="">
			<?php wp_nonce_field( self::ACTION, 'isnx_contact_nonce' ); ?>
			<?php if ( '' !== $notice['message'] ) : ?>
				<p class="isnx-form-notice isnx-form-notice--<?php echo esc_attr( $notice['type'] ); ?>" role="<?php echo 'error' === $notice['type'] ? 'alert' : 'status'; ?>">
					<?php echo esc_html( $notice['message'] ); ?>
				</p>
			<?php endif; ?>
			<label for="isnx-contact-name">
				<?php esc_html_e( 'Your Name', 'infosecnexus' ); ?>
				<input id="isnx-contact-name" type="text" name="name" required minlength="2" maxlength="100" autocomplete="name" placeholder="<?php esc_attr_e( 'Your name', 'infosecnexus' ); ?>">
			</label>
			<label for="isnx-contact-email">
				<?php esc_html_e( 'Work Email', 'infosecnexus' ); ?>
				<input id="isnx-contact-email" type="email" name="email" required maxlength="190" autocomplete="email" inputmode="email" placeholder="<?php esc_attr_e( 'you@example.com', 'infosecnexus' ); ?>">
			</label>
			<label for="isnx-contact-subject">
				<?php esc_html_e( 'Subject', 'infosecnexus' ); ?>
				<input id="isnx-contact-subject" type="text" name="subject" required minlength="4" maxlength="160" autocomplete="off" placeholder="<?php esc_attr_e( 'How can we help?', 'infosecnexus' ); ?>">
			</label>
			<label for="isnx-contact-message">
				<?php esc_html_e( 'Message', 'infosecnexus' ); ?>
				<textarea id="isnx-contact-message" name="message" rows="6" required minlength="20" maxlength="5000" placeholder="<?php esc_attr_e( 'Share the context we should know.', 'infosecnexus' ); ?>"></textarea>
			</label>
			<label class="isnx-hp" aria-hidden="true">
				<span><?php esc_html_e( 'Website', 'infosecnexus' ); ?></span>
				<input type="text" name="website" tabindex="-1" autocomplete="off">
			</label>
			<label class="isnx-hp" aria-hidden="true">
				<span><?php esc_html_e( 'Company URL', 'infosecnexus' ); ?></span>
				<input type="url" name="company_url" tabindex="-1" autocomplete="off">
			</label>
			<button type="submit" disabled data-isnx-contact-submit><?php esc_html_e( 'Send Message', 'infosecnexus' ); ?></button>
			<noscript><p class="isnx-form-notice isnx-form-notice--warning"><?php esc_html_e( 'Please enable JavaScript to send this protected form.', 'infosecnexus' ); ?></p></noscript>
			<p class="isnx-form-privacy"><?php esc_html_e( 'Protected by same-site validation, spam controls, and encrypted HTTPS transport.', 'infosecnexus' ); ?></p>
		</form>
		<?php
	}

	/**
	 * Validate, store, and deliver a contact submission.
	 */
	public static function handle_submission(): void {
		$status         = 'invalid';
		$request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_key( wp_unslash( (string) $_SERVER['REQUEST_METHOD'] ) ) : '';

		if ( 'post' !== $request_method || ! request_origin_is_local() ) {
			self::redirect( 'blocked' );
		}

		$nonce        = isset( $_POST['isnx_contact_nonce'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['isnx_contact_nonce'] ) ) : '';
		$issued       = isset( $_POST['isnx_issued'] ) ? absint( $_POST['isnx_issued'] ) : 0;
		$token        = isset( $_POST['isnx_token'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['isnx_token'] ) ) : '';
		$nonce_valid  = '' !== $nonce && false !== wp_verify_nonce( $nonce, self::ACTION );
		$signed_valid = verify_public_form_token( self::ACTION, $issued, $token );
		if ( ! $nonce_valid && ! $signed_valid ) {
			self::redirect( 'expired' );
		}

		$honeypot     = isset( $_POST['website'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['website'] ) ) : '';
		$company_trap = isset( $_POST['company_url'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['company_url'] ) ) : '';
		if ( '' !== $honeypot || '' !== $company_trap ) {
			self::record_spam_block( 'honeypot' );
			self::redirect( 'sent' );
		}

		if ( ! self::verify_guard_challenge() ) {
			self::record_spam_block( 'browser_challenge' );
			self::redirect( 'blocked' );
		}

		if ( ! rate_limit( 'contact', 3, 3600 ) ) {
			self::record_spam_block( 'ip_rate_limit' );
			self::redirect( 'blocked' );
		}

		$name    = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['name'] ) ) : '';
		$email   = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( (string) $_POST['email'] ) ) : '';
		$subject = isset( $_POST['subject'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['subject'] ) ) : '';
		$message = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( (string) $_POST['message'] ) ) : '';

		if (
			self::text_length( $name ) < 2 ||
			self::text_length( $name ) > 100 ||
			! is_email( $email ) ||
			self::text_length( $subject ) < 4 ||
			self::text_length( $subject ) > 160 ||
			self::text_length( $message ) < 20 ||
			self::text_length( $message ) > 5000
		) {
			self::redirect( $status );
		}

		if ( ! self::identifier_rate_limit( 'email', strtolower( $email ), 3, 86400 ) ) {
			self::record_spam_block( 'email_rate_limit' );
			self::redirect( 'blocked' );
		}

		$spam_reason = self::spam_reason( $name, $email, $subject, $message );
		if ( '' !== $spam_reason ) {
			self::record_spam_block( $spam_reason );
			self::redirect( 'sent' );
		}

		$duplicate_key = self::duplicate_key( $email, $subject, $message );
		if ( false !== get_transient( $duplicate_key ) ) {
			self::record_spam_block( 'duplicate' );
			self::redirect( 'sent' );
		}
		set_transient( $duplicate_key, 1, self::DUPLICATE_LIFETIME );

		$message_id = wp_insert_post(
			wp_slash(
				array(
					'post_type'    => self::POST_TYPE,
					'post_status'  => 'private',
					'post_title'   => sprintf( '%s - %s', $subject, current_time( 'Y-m-d H:i' ) ),
					'post_content' => $message,
					'meta_input'   => array(
						'_isnx_contact_name'        => $name,
						'_isnx_contact_email'       => $email,
						'_isnx_contact_subject'     => $subject,
						'_isnx_contact_received_at' => current_time( 'mysql' ),
						'_isnx_contact_fingerprint' => request_fingerprint(),
					),
				)
			),
			true
		);

		if ( is_wp_error( $message_id ) ) {
			self::redirect( 'error' );
		}

		$reference  = 'ISN-' . str_pad( (string) $message_id, 6, '0', STR_PAD_LEFT );
		$admin_body = sprintf(
			"New InfoSecNexus contact message\n\nReference: %s\nName: %s\nEmail: %s\nSubject: %s\nReceived: %s\n\nMessage:\n%s\n\nOpen in WordPress:\n%s",
			$reference,
			$name,
			$email,
			$subject,
			current_time( 'mysql' ),
			$message,
			admin_url( 'post.php?action=edit&post=' . (int) $message_id )
		);
		$reply_to   = sprintf( 'Reply-To: %s <%s>', str_replace( array( "\r", "\n" ), '', $name ), $email );
		$admin_sent = Mailer::send(
			Mailer::recipient_email(),
			sprintf( '[%s] %s', $reference, $subject ),
			$admin_body,
			array( $reply_to )
		);

		$user_body = sprintf(
			"Hello %s,\n\nWe securely received your message about \"%s\".\n\nReference: %s\n\nThe InfoSecNexus team normally replies within two business days. Please do not send passwords, private keys, tokens, or other sensitive credentials by email.\n\nInfoSecNexus\n%s",
			$name,
			$subject,
			$reference,
			home_url( '/' )
		);
		$user_sent = Mailer::send(
			$email,
			sprintf( 'We received your InfoSecNexus message (%s)', $reference ),
			$user_body
		);

		update_post_meta( (int) $message_id, '_isnx_admin_mail_status', $admin_sent ? 'accepted' : 'failed' );
		update_post_meta( (int) $message_id, '_isnx_sender_mail_status', $user_sent ? 'accepted' : 'failed' );

		self::redirect( $admin_sent && $user_sent ? 'sent' : 'saved' );
	}

	/**
	 * Redirect back to the contact page with a non-sensitive status.
	 *
	 * @param string $status Status key.
	 */
	private static function redirect( string $status ): void {
		$page = get_page_by_path( 'contact' );
		$url  = $page ? get_permalink( $page ) : home_url( '/contact/' );
		$url  = add_query_arg( 'isnx_contact', sanitize_key( $status ), $url );
		wp_safe_redirect( $url . '#contact-message-form' );
		exit;
	}

	/**
	 * Frontend status message.
	 *
	 * @param string $status Status key.
	 * @return array{type:string,message:string}
	 */
	private static function status_notice( string $status ): array {
		$notices = array(
			'sent'    => array(
				'type'    => 'success',
				'message' => __( 'Thanks. Your message was received and a confirmation email is on its way.', 'infosecnexus' ),
			),
			'saved'   => array(
				'type'    => 'warning',
				'message' => __( 'Your message was securely saved, but email delivery is delayed. The WordPress inbox still has your submission.', 'infosecnexus' ),
			),
			'invalid' => array(
				'type'    => 'error',
				'message' => __( 'Please complete every field with a valid email address and a message of at least 20 characters.', 'infosecnexus' ),
			),
			'blocked' => array(
				'type'    => 'error',
				'message' => __( 'That request could not be accepted. Please wait a few minutes and try again.', 'infosecnexus' ),
			),
			'expired' => array(
				'type'    => 'error',
				'message' => __( 'This form session expired. Refresh the page and submit again.', 'infosecnexus' ),
			),
			'error'   => array(
				'type'    => 'error',
				'message' => __( 'The message could not be saved. Please try again shortly.', 'infosecnexus' ),
			),
		);

		return $notices[ $status ] ?? array(
			'type'    => 'info',
			'message' => '',
		);
	}

	/**
	 * Multibyte-safe text length.
	 *
	 * @param string $text Text.
	 */
	private static function text_length( string $text ): int {
		return function_exists( 'mb_strlen' ) ? mb_strlen( $text ) : strlen( $text );
	}

	/**
	 * Create the expected signature for a browser challenge.
	 *
	 * @param int    $issued Challenge issue timestamp.
	 * @param string $nonce Random challenge value.
	 */
	private static function guard_signature( int $issued, string $nonce ): string {
		return hash_hmac( 'sha256', self::ACTION . '|browser|' . $issued . '|' . $nonce, wp_salt( 'nonce' ) );
	}

	/**
	 * Verify and consume a browser challenge.
	 */
	private static function verify_guard_challenge(): bool {
		$issued = isset( $_POST['isnx_guard_issued'] ) ? absint( $_POST['isnx_guard_issued'] ) : 0;
		$nonce  = isset( $_POST['isnx_guard_nonce'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['isnx_guard_nonce'] ) ) : '';
		$token  = isset( $_POST['isnx_guard_token'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['isnx_guard_token'] ) ) : '';
		$age    = time() - $issued;

		if (
			$age < self::GUARD_MIN_AGE ||
			$age > self::GUARD_MAX_AGE ||
			1 !== preg_match( '/^[a-f0-9]{32}$/', $nonce ) ||
			1 !== preg_match( '/^[a-f0-9]{64}$/', $token ) ||
			! hash_equals( self::guard_signature( $issued, $nonce ), $token )
		) {
			return false;
		}

		$replay_key = 'isnx_contact_guard_' . md5( $nonce );
		if ( false !== get_transient( $replay_key ) ) {
			return false;
		}

		set_transient( $replay_key, 1, self::GUARD_MAX_AGE );
		return true;
	}

	/**
	 * Apply a privacy-safe rate limit to an arbitrary identifier.
	 *
	 * @param string $bucket Rate-limit bucket.
	 * @param string $value Identifier value.
	 * @param int    $limit Maximum submissions.
	 * @param int    $window Window in seconds.
	 */
	private static function identifier_rate_limit( string $bucket, string $value, int $limit, int $window ): bool {
		$identifier = hash_hmac( 'sha256', $bucket . '|' . $value, wp_salt( 'nonce' ) );
		$key        = 'isnx_contact_limit_' . md5( $identifier );
		$count      = (int) get_transient( $key );

		if ( $count >= $limit ) {
			return false;
		}

		set_transient( $key, $count + 1, $window );
		return true;
	}

	/**
	 * Detect high-confidence automated contact campaigns.
	 *
	 * @param string $name Sender name.
	 * @param string $email Sender email.
	 * @param string $subject Message subject.
	 * @param string $message Message body.
	 */
	private static function spam_reason( string $name, string $email, string $subject, string $message ): string {
		$subject_text = strtolower( remove_accents( $subject ) );
		$combined     = strtolower( remove_accents( $name . ' ' . $email . ' ' . $subject . ' ' . $message ) );

		$price_campaign = '/\b(?:hi|hello|hallo|aloha)\b[\s,!.]*(?:i\s+)?(?:am\s+)?(?:write|wrote|writing)\s+about\s+(?:your\s+)?(?:the\s+)?prices?(?:\s+for\s+(?:a\s+)?reseller)?\b/i';
		if ( 1 === preg_match( $price_campaign, $subject_text ) ) {
			return 'known_price_campaign';
		}

		$url_count = preg_match_all( '#(?:https?://|www\.)#i', $combined );
		if ( false !== $url_count && $url_count > 3 ) {
			return 'excessive_links';
		}

		$solicitation = '/\b(?:buy\s+backlinks?|guest\s+posts?|link\s+insertion|casino\s+links?|crypto\s+promotion|seo\s+packages?)\b/i';
		if ( self::text_length( $message ) < 500 && 1 === preg_match( $solicitation, $combined ) ) {
			return 'unsolicited_promotion';
		}

		return '';
	}

	/**
	 * Build a non-reversible key for exact duplicate suppression.
	 *
	 * @param string $email Sender email.
	 * @param string $subject Message subject.
	 * @param string $message Message body.
	 */
	private static function duplicate_key( string $email, string $subject, string $message ): string {
		$payload = strtolower( trim( $email ) . '|' . trim( $subject ) . '|' . trim( $message ) );
		$digest  = hash_hmac( 'sha256', $payload, wp_salt( 'nonce' ) );
		return 'isnx_contact_duplicate_' . md5( $digest );
	}

	/**
	 * Store only aggregate spam diagnostics, never submitted personal data.
	 *
	 * @param string $reason Block reason.
	 */
	private static function record_spam_block( string $reason ): void {
		$stats   = get_option( self::SPAM_STATS_OPTION, array() );
		$stats   = is_array( $stats ) ? $stats : array();
		$reasons = isset( $stats['reasons'] ) && is_array( $stats['reasons'] ) ? $stats['reasons'] : array();

		$stats['total']        = isset( $stats['total'] ) ? absint( $stats['total'] ) + 1 : 1;
		$stats['last_blocked'] = current_time( 'mysql' );
		$reasons[ $reason ]    = isset( $reasons[ $reason ] ) ? absint( $reasons[ $reason ] ) + 1 : 1;
		$stats['reasons']      = $reasons;

		update_option( self::SPAM_STATS_OPTION, $stats, false );
	}
}
