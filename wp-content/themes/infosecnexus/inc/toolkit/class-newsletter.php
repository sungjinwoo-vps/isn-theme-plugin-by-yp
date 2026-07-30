<?php
/**
 * Newsletter subscriptions, confirmation, and daily delivery.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

namespace InfoSecNexus\Theme\Toolkit;

use WP_REST_Request;
use WP_REST_Response;

/**
 * Newsletter module.
 */
final class Newsletter {
	private const POST_TYPE          = 'isnx_subscription';
	private const SUBSCRIBE_ACTION   = 'infosecnexus_newsletter_subscribe';
	private const CONFIRM_ACTION     = 'infosecnexus_newsletter_confirm';
	private const UNSUBSCRIBE_ACTION = 'infosecnexus_newsletter_unsubscribe';
	private const DIGEST_HOOK        = 'infosecnexus_send_newsletter_digest';
	private const CONFIRMATION_TTL   = 172800;

	/**
	 * Register hooks.
	 */
	public static function boot(): void {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_action( 'init', array( __CLASS__, 'schedule_digest' ), 30 );
		add_action( self::DIGEST_HOOK, array( __CLASS__, 'send_daily_digest' ) );
		add_action( 'admin_post_' . self::CONFIRM_ACTION, array( __CLASS__, 'confirm' ) );
		add_action( 'admin_post_nopriv_' . self::CONFIRM_ACTION, array( __CLASS__, 'confirm' ) );
		add_action( 'admin_post_' . self::UNSUBSCRIBE_ACTION, array( __CLASS__, 'unsubscribe' ) );
		add_action( 'admin_post_nopriv_' . self::UNSUBSCRIBE_ACTION, array( __CLASS__, 'unsubscribe' ) );

		if ( ! module_enabled( 'newsletter' ) ) {
			return;
		}

		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
		add_action( 'admin_post_' . self::SUBSCRIBE_ACTION, array( __CLASS__, 'handle_fallback_submission' ) );
		add_action( 'admin_post_nopriv_' . self::SUBSCRIBE_ACTION, array( __CLASS__, 'handle_fallback_submission' ) );
		add_shortcode( 'infosecnexus_newsletter', array( __CLASS__, 'shortcode' ) );
	}

	/**
	 * Register private subscription post type.
	 */
	public static function register_post_type(): void {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'          => array(
					'name'          => __( 'Newsletter Subscriptions', 'infosecnexus' ),
					'singular_name' => __( 'Newsletter Subscription', 'infosecnexus' ),
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => 'tools.php',
				'capability_type' => 'post',
				'capabilities'    => array(
					'create_posts' => 'do_not_allow',
				),
				'map_meta_cap'    => true,
				'supports'        => array( 'title', 'custom-fields' ),
			)
		);
	}

	/**
	 * Register public newsletter route.
	 */
	public static function routes(): void {
		register_rest_route(
			'infosecnexus/v1',
			'/newsletter',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'subscribe' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * REST subscription callback.
	 *
	 * @param WP_REST_Request $request Request.
	 */
	public static function subscribe( WP_REST_Request $request ): WP_REST_Response {
		$result = self::process_subscription( $request->get_params() );
		return new WP_REST_Response(
			array(
				'message' => $result['message'],
				'status'  => $result['status'],
			),
			$result['code']
		);
	}

	/**
	 * Non-JavaScript subscription fallback.
	 */
	public static function handle_fallback_submission(): void {
		$request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_key( wp_unslash( (string) $_SERVER['REQUEST_METHOD'] ) ) : '';
		if ( 'post' !== $request_method ) {
			self::redirect_home( 'invalid' );
		}

		// process_subscription verifies the embedded nonce or signed cache-safe token before reading fields.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$params = wp_unslash( $_POST );
		$result = self::process_subscription( is_array( $params ) ? $params : array() );
		self::redirect_home( $result['status'] );
	}

	/**
	 * Validate and store a subscription request.
	 *
	 * @param array<string,mixed> $params Request parameters.
	 * @return array{message:string,status:string,code:int}
	 */
	private static function process_subscription( array $params ): array {
		if ( ! request_origin_is_local() ) {
			return self::result( 'blocked', __( 'That subscription request could not be accepted.', 'infosecnexus' ), 403 );
		}

		$nonce        = sanitize_text_field( (string) ( $params['isnx_newsletter_nonce'] ?? '' ) );
		$issued       = absint( $params['isnx_issued'] ?? 0 );
		$token        = sanitize_text_field( (string) ( $params['isnx_token'] ?? '' ) );
		$nonce_valid  = '' !== $nonce && false !== wp_verify_nonce( $nonce, self::SUBSCRIBE_ACTION );
		$signed_valid = verify_public_form_token( self::SUBSCRIBE_ACTION, $issued, $token );
		if ( ! $nonce_valid && ! $signed_valid ) {
			return self::result( 'expired', __( 'This form expired. Refresh the page and try again.', 'infosecnexus' ), 403 );
		}

		$honeypot = sanitize_text_field( (string) ( $params['website'] ?? '' ) );
		if ( '' !== $honeypot ) {
			return self::result( 'pending', __( 'Check your inbox to confirm your subscription.', 'infosecnexus' ), 200 );
		}

		if ( ! rate_limit( 'newsletter', 5, 900 ) ) {
			return self::result( 'blocked', __( 'Please wait before trying again.', 'infosecnexus' ), 429 );
		}

		$email = sanitize_email( (string) ( $params['email'] ?? '' ) );
		if ( ! is_email( $email ) ) {
			return self::result( 'invalid', __( 'Enter a valid email address.', 'infosecnexus' ), 400 );
		}

		$subscription_id = self::find_subscription( $email );
		if ( $subscription_id > 0 ) {
			$current_status = (string) get_post_meta( $subscription_id, '_isnx_subscription_status', true );
			if ( '' === $current_status || 'active' === $current_status ) {
				return self::result( 'active', __( 'This email is already subscribed to the Daily Cyber Brief.', 'infosecnexus' ), 200 );
			}
		} else {
			$subscription_id = wp_insert_post(
				array(
					'post_type'   => self::POST_TYPE,
					'post_status' => 'private',
					'post_title'  => $email,
					'meta_input'  => array(
						'_isnx_consent_time'             => current_time( 'mysql' ),
						'_isnx_subscription_fingerprint' => request_fingerprint(),
					),
				),
				true
			);
			if ( is_wp_error( $subscription_id ) ) {
				return self::result( 'error', __( 'The subscription could not be saved. Please try again.', 'infosecnexus' ), 500 );
			}
		}

		$confirmation_token = wp_generate_password( 48, false, false );
		$expires            = time() + self::CONFIRMATION_TTL;
		update_post_meta( (int) $subscription_id, '_isnx_subscription_status', 'pending' );
		update_post_meta( (int) $subscription_id, '_isnx_confirmation_hash', self::token_hash( $confirmation_token ) );
		update_post_meta( (int) $subscription_id, '_isnx_confirmation_expires', $expires );
		update_post_meta( (int) $subscription_id, '_isnx_confirmation_requested_at', current_time( 'mysql' ) );

		$confirmation_url = add_query_arg(
			array(
				'action'       => self::CONFIRM_ACTION,
				'subscription' => (int) $subscription_id,
				'token'        => $confirmation_token,
			),
			public_form_action_url()
		);
		$body             = sprintf(
			"Confirm your InfoSecNexus Daily Cyber Brief subscription:\n\n%s\n\nThis link expires in 48 hours. If you did not request this subscription, ignore this email.\n\nInfoSecNexus\n%s",
			$confirmation_url,
			home_url( '/' )
		);
		$sent             = Mailer::send(
			$email,
			__( 'Confirm your InfoSecNexus subscription', 'infosecnexus' ),
			$body
		);

		update_post_meta( (int) $subscription_id, '_isnx_confirmation_mail_status', $sent ? 'accepted' : 'failed' );
		if ( ! $sent ) {
			return self::result(
				'mail-delayed',
				__( 'Your request was saved, but the confirmation email could not be sent. Please try again after mail delivery is configured.', 'infosecnexus' ),
				503
			);
		}

		return self::result( 'pending', __( 'Check your inbox and click the confirmation link to join the Daily Cyber Brief.', 'infosecnexus' ), 200 );
	}

	/**
	 * Confirm a subscriber using the emailed one-time token.
	 */
	public static function confirm(): void {
		// The one-time emailed token below is the authorization credential.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$subscription_id = isset( $_GET['subscription'] ) ? absint( $_GET['subscription'] ) : 0;
		$token           = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['token'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		$post = get_post( $subscription_id );
		if ( ! $post || self::POST_TYPE !== $post->post_type || '' === $token ) {
			self::redirect_home( 'invalid' );
		}

		$stored_hash = (string) get_post_meta( $subscription_id, '_isnx_confirmation_hash', true );
		$expires     = (int) get_post_meta( $subscription_id, '_isnx_confirmation_expires', true );
		if ( $expires < time() || '' === $stored_hash || ! hash_equals( $stored_hash, self::token_hash( $token ) ) ) {
			self::redirect_home( 'expired' );
		}

		$email = sanitize_email( $post->post_title );
		update_post_meta( $subscription_id, '_isnx_subscription_status', 'active' );
		update_post_meta( $subscription_id, '_isnx_confirmed_at', current_time( 'mysql' ) );
		delete_post_meta( $subscription_id, '_isnx_confirmation_hash' );
		delete_post_meta( $subscription_id, '_isnx_confirmation_expires' );

		$unsubscribe_url = self::unsubscribe_url( $subscription_id, $email );
		$user_sent       = Mailer::send(
			$email,
			__( 'Welcome to the InfoSecNexus Daily Cyber Brief', 'infosecnexus' ),
			sprintf(
				"Your subscription is confirmed.\n\nYou will receive a concise daily roundup when new InfoSecNexus briefings are published.\n\nVisit InfoSecNexus: %s\nUnsubscribe: %s",
				home_url( '/' ),
				$unsubscribe_url
			)
		);
		$admin_sent      = Mailer::send(
			Mailer::recipient_email(),
			__( 'New confirmed Daily Cyber Brief subscriber', 'infosecnexus' ),
			sprintf(
				"A reader confirmed a Daily Cyber Brief subscription.\n\nEmail: %s\nConfirmed: %s\nSubscription ID: %d",
				$email,
				current_time( 'mysql' ),
				$subscription_id
			)
		);
		update_post_meta( $subscription_id, '_isnx_welcome_mail_status', $user_sent ? 'accepted' : 'failed' );
		update_post_meta( $subscription_id, '_isnx_admin_mail_status', $admin_sent ? 'accepted' : 'failed' );

		self::redirect_home( $user_sent ? 'confirmed' : 'confirmed-mail-delayed' );
	}

	/**
	 * Unsubscribe using a stable per-subscriber signed token.
	 */
	public static function unsubscribe(): void {
		// The per-subscriber HMAC below authorizes this request without a login cookie.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$subscription_id = isset( $_GET['subscription'] ) ? absint( $_GET['subscription'] ) : 0;
		$token           = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['token'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		$post = get_post( $subscription_id );
		if ( ! $post || self::POST_TYPE !== $post->post_type ) {
			self::redirect_home( 'invalid' );
		}

		$email    = sanitize_email( $post->post_title );
		$expected = self::unsubscribe_token( $subscription_id, $email );
		if ( '' === $token || ! hash_equals( $expected, $token ) ) {
			self::redirect_home( 'invalid' );
		}

		update_post_meta( $subscription_id, '_isnx_subscription_status', 'unsubscribed' );
		update_post_meta( $subscription_id, '_isnx_unsubscribed_at', current_time( 'mysql' ) );
		self::redirect_home( 'unsubscribed' );
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
	 * Render subscription form.
	 */
	public static function render_form(): void {
		if ( ! module_enabled( 'newsletter' ) ) {
			return;
		}

		$token = public_form_token( self::SUBSCRIBE_ACTION );
		// Display-only status returned by a signed newsletter action.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$status = isset( $_GET['isnx_newsletter'] ) ? sanitize_key( wp_unslash( (string) $_GET['isnx_newsletter'] ) ) : '';
		$notice = self::status_message( $status );
		?>
		<form class="newsletter-card" action="<?php echo esc_url( public_form_action_url() ); ?>" method="post" accept-charset="UTF-8" autocomplete="on" data-isnx-newsletter>
			<h2><?php echo esc_html( (string) option( 'newsletter_heading', __( 'Get the daily security briefing', 'infosecnexus' ) ) ); ?></h2>
			<p><?php echo esc_html( (string) option( 'newsletter_intro', '' ) ); ?></p>
			<input type="hidden" name="action" value="<?php echo esc_attr( self::SUBSCRIBE_ACTION ); ?>">
			<input type="hidden" name="isnx_issued" value="<?php echo esc_attr( (string) $token['issued'] ); ?>">
			<input type="hidden" name="isnx_token" value="<?php echo esc_attr( $token['token'] ); ?>">
			<?php wp_nonce_field( self::SUBSCRIBE_ACTION, 'isnx_newsletter_nonce' ); ?>
			<label>
				<span class="screen-reader-text"><?php esc_html_e( 'Email address', 'infosecnexus' ); ?></span>
				<input type="email" name="email" required maxlength="190" autocomplete="email" inputmode="email" placeholder="<?php esc_attr_e( 'you@example.com', 'infosecnexus' ); ?>">
			</label>
			<label class="isnx-hp" aria-hidden="true">
				<span><?php esc_html_e( 'Website', 'infosecnexus' ); ?></span>
				<input type="text" name="website" tabindex="-1" autocomplete="off">
			</label>
			<button type="submit"><?php esc_html_e( 'Subscribe', 'infosecnexus' ); ?></button>
			<p class="newsletter-card__status<?php echo 'error' === $notice['type'] ? ' is-error' : ''; ?>" data-isnx-newsletter-status aria-live="polite">
				<?php echo esc_html( $notice['message'] ); ?>
			</p>
		</form>
		<?php
	}

	/**
	 * Schedule the daily subscriber digest after the morning content refresh.
	 */
	public static function schedule_digest(): void {
		$enabled = module_enabled( 'newsletter' ) && (bool) option( 'newsletter_digest_enabled', true );
		if ( ! $enabled ) {
			wp_clear_scheduled_hook( self::DIGEST_HOOK );
			return;
		}

		if ( wp_next_scheduled( self::DIGEST_HOOK ) ) {
			return;
		}

		$now  = current_datetime();
		$next = $now->setTime( 7, 15, 0 );
		if ( $next <= $now ) {
			$next = $next->modify( '+1 day' );
		}
		wp_schedule_event( $next->getTimestamp(), 'daily', self::DIGEST_HOOK );
	}

	/**
	 * Send today's newest briefings to confirmed subscribers.
	 */
	public static function send_daily_digest(): void {
		if ( ! module_enabled( 'newsletter' ) || ! (bool) option( 'newsletter_digest_enabled', true ) ) {
			return;
		}

		$today = current_time( 'Y-m-d' );
		$posts = get_posts(
			array(
				'post_type'           => 'post',
				'post_status'         => 'publish',
				'posts_per_page'      => 6,
				'ignore_sticky_posts' => true,
				'date_query'          => array(
					array(
						'after'     => $today . ' 00:00:00',
						'inclusive' => true,
					),
				),
			)
		);
		if ( empty( $posts ) ) {
			return;
		}

		$subscriptions = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'private',
				'posts_per_page' => 100,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);

		foreach ( array_map( 'intval', $subscriptions ) as $subscription_id ) {
			$subscription_status = (string) get_post_meta( $subscription_id, '_isnx_subscription_status', true );
			if ( ! in_array( $subscription_status, array( '', 'active' ), true ) ) {
				continue;
			}

			if ( (string) get_post_meta( $subscription_id, '_isnx_last_digest_date', true ) === $today ) {
				continue;
			}

			$email = sanitize_email( (string) get_post_field( 'post_title', $subscription_id ) );
			if ( ! is_email( $email ) ) {
				continue;
			}

			$lines = array(
				sprintf( 'InfoSecNexus Daily Cyber Brief - %s', wp_date( 'F j, Y' ) ),
				'',
			);
			foreach ( $posts as $post ) {
				$lines[] = '- ' . get_the_title( $post );
				$lines[] = '  ' . get_permalink( $post );
			}
			$lines[] = '';
			$lines[] = 'Unsubscribe: ' . self::unsubscribe_url( $subscription_id, $email );

			$sent = Mailer::send(
				$email,
				sprintf(
					/* translators: %s: Date. */
					__( 'Daily Cyber Brief - %s', 'infosecnexus' ),
					wp_date( 'F j, Y' )
				),
				implode( "\n", $lines )
			);
			if ( $sent ) {
				update_post_meta( $subscription_id, '_isnx_last_digest_date', $today );
				update_post_meta( $subscription_id, '_isnx_last_digest_status', 'accepted' );
			} else {
				update_post_meta( $subscription_id, '_isnx_last_digest_status', 'failed' );
			}
		}
	}

	/**
	 * Find an existing subscription by normalized email title.
	 *
	 * @param string $email Email.
	 */
	private static function find_subscription( string $email ): int {
		$existing = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'private',
				'title'          => $email,
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);

		return empty( $existing ) ? 0 : (int) $existing[0];
	}

	/**
	 * Build an unsubscribe URL.
	 *
	 * @param int    $subscription_id Subscription post ID.
	 * @param string $email Subscriber email.
	 */
	private static function unsubscribe_url( int $subscription_id, string $email ): string {
		return add_query_arg(
			array(
				'action'       => self::UNSUBSCRIBE_ACTION,
				'subscription' => $subscription_id,
				'token'        => self::unsubscribe_token( $subscription_id, $email ),
			),
			public_form_action_url()
		);
	}

	/**
	 * Stable unsubscribe signature.
	 *
	 * @param int    $subscription_id Subscription post ID.
	 * @param string $email Subscriber email.
	 */
	private static function unsubscribe_token( int $subscription_id, string $email ): string {
		return hash_hmac( 'sha256', $subscription_id . '|' . strtolower( $email ), wp_salt( 'auth' ) );
	}

	/**
	 * One-time confirmation token hash.
	 *
	 * @param string $token Raw token.
	 */
	private static function token_hash( string $token ): string {
		return hash_hmac( 'sha256', $token, wp_salt( 'auth' ) );
	}

	/**
	 * Redirect to the newsletter section.
	 *
	 * @param string $status Status key.
	 */
	private static function redirect_home( string $status ): void {
		$url = add_query_arg( 'isnx_newsletter', sanitize_key( $status ), home_url( '/' ) );
		wp_safe_redirect( $url . '#daily-cyber-brief' );
		exit;
	}

	/**
	 * Build a consistent result.
	 *
	 * @param string $status Status.
	 * @param string $message Message.
	 * @param int    $code HTTP status.
	 * @return array{message:string,status:string,code:int}
	 */
	private static function result( string $status, string $message, int $code ): array {
		return array(
			'message' => $message,
			'status'  => $status,
			'code'    => $code,
		);
	}

	/**
	 * Frontend status copy.
	 *
	 * @param string $status Status.
	 * @return array{type:string,message:string}
	 */
	private static function status_message( string $status ): array {
		$messages = array(
			'pending'                => array(
				'type'    => 'success',
				'message' => __( 'Check your inbox and confirm your subscription.', 'infosecnexus' ),
			),
			'active'                 => array(
				'type'    => 'success',
				'message' => __( 'This email is already subscribed.', 'infosecnexus' ),
			),
			'confirmed'              => array(
				'type'    => 'success',
				'message' => __( 'Subscription confirmed. Welcome to the Daily Cyber Brief.', 'infosecnexus' ),
			),
			'confirmed-mail-delayed' => array(
				'type'    => 'warning',
				'message' => __( 'Subscription confirmed. The welcome email is delayed.', 'infosecnexus' ),
			),
			'unsubscribed'           => array(
				'type'    => 'success',
				'message' => __( 'You have been unsubscribed from the Daily Cyber Brief.', 'infosecnexus' ),
			),
			'mail-delayed'           => array(
				'type'    => 'error',
				'message' => __( 'The request was saved, but confirmation email delivery is unavailable.', 'infosecnexus' ),
			),
			'invalid'                => array(
				'type'    => 'error',
				'message' => __( 'Enter a valid email address and try again.', 'infosecnexus' ),
			),
			'expired'                => array(
				'type'    => 'error',
				'message' => __( 'That confirmation link or form expired. Please subscribe again.', 'infosecnexus' ),
			),
			'blocked'                => array(
				'type'    => 'error',
				'message' => __( 'Please wait a few minutes before trying again.', 'infosecnexus' ),
			),
			'error'                  => array(
				'type'    => 'error',
				'message' => __( 'The subscription could not be saved. Please try again.', 'infosecnexus' ),
			),
		);

		return $messages[ $status ] ?? array(
			'type'    => 'info',
			'message' => '',
		);
	}
}
