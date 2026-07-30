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
	private const ACTION    = 'infosecnexus_contact_submit';
	private const POST_TYPE = 'isnx_contact';

	/**
	 * Register hooks.
	 */
	public static function boot(): void {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_action( 'admin_post_' . self::ACTION, array( __CLASS__, 'handle_submission' ) );
		add_action( 'admin_post_nopriv_' . self::ACTION, array( __CLASS__, 'handle_submission' ) );
		add_shortcode( 'infosecnexus_contact_form', array( __CLASS__, 'shortcode' ) );
		add_filter( 'the_content', array( __CLASS__, 'replace_legacy_form' ), 8 );
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
	 * Render the secure same-site form.
	 */
	public static function render_form(): void {
		$token = public_form_token( self::ACTION );
		// Display-only status returned by the verified form handler.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$status = isset( $_GET['isnx_contact'] ) ? sanitize_key( wp_unslash( (string) $_GET['isnx_contact'] ) ) : '';
		$notice = self::status_notice( $status );
		?>
		<form class="isnx-contact-form" id="contact-message-form" action="<?php echo esc_url( public_form_action_url() ); ?>" method="post" accept-charset="UTF-8" autocomplete="on">
			<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION ); ?>">
			<input type="hidden" name="isnx_issued" value="<?php echo esc_attr( (string) $token['issued'] ); ?>">
			<input type="hidden" name="isnx_token" value="<?php echo esc_attr( $token['token'] ); ?>">
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
			<button type="submit"><?php esc_html_e( 'Send Message', 'infosecnexus' ); ?></button>
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

		$honeypot = isset( $_POST['website'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['website'] ) ) : '';
		if ( '' !== $honeypot ) {
			self::redirect( 'sent' );
		}

		if ( ! rate_limit( 'contact', 4, 900 ) ) {
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
}
