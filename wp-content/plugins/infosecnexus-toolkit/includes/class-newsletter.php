<?php
/**
 * Newsletter module.
 *
 * @package InfoSecNexusToolkit
 */

declare(strict_types=1);

namespace InfoSecNexus\Toolkit;

use WP_REST_Request;
use WP_REST_Response;

/**
 * Newsletter module.
 */
final class Newsletter {
	/**
	 * Register hooks.
	 */
	public static function boot(): void {
		if ( ! module_enabled( 'newsletter' ) ) {
			return;
		}
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
		add_shortcode( 'infosecnexus_newsletter', array( __CLASS__, 'shortcode' ) );
	}

	/**
	 * Register private subscription post type.
	 */
	public static function register_post_type(): void {
		register_post_type(
			'isnx_subscription',
			array(
				'labels'          => array(
					'name'          => __( 'Newsletter Subscriptions', 'infosecnexus-toolkit' ),
					'singular_name' => __( 'Newsletter Subscription', 'infosecnexus-toolkit' ),
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
	 * Register routes.
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
	 * Subscribe callback.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function subscribe( WP_REST_Request $request ): WP_REST_Response {
		if ( ! rate_limit( 'newsletter', 5, 300 ) ) {
			return new WP_REST_Response( array( 'message' => __( 'Please wait before trying again.', 'infosecnexus-toolkit' ) ), 429 );
		}

		$honeypot = (string) $request->get_param( 'website' );
		if ( '' !== $honeypot ) {
			return new WP_REST_Response( array( 'message' => __( 'Thanks.', 'infosecnexus-toolkit' ) ) );
		}

		$email = sanitize_email( (string) $request->get_param( 'email' ) );
		if ( ! is_email( $email ) ) {
			return new WP_REST_Response( array( 'message' => __( 'Enter a valid email address.', 'infosecnexus-toolkit' ) ), 400 );
		}

		$existing = get_posts(
			array(
				'post_type'      => 'isnx_subscription',
				'post_status'    => 'private',
				'title'          => $email,
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);

		if ( empty( $existing ) ) {
			wp_insert_post(
				array(
					'post_type'   => 'isnx_subscription',
					'post_status' => 'private',
					'post_title'  => $email,
					'meta_input'  => array(
						'_isnx_consent_time' => current_time( 'mysql' ),
					),
				)
			);
		}

		return new WP_REST_Response( array( 'message' => __( 'You are on the list.', 'infosecnexus-toolkit' ) ) );
	}

	/**
	 * Shortcode callback.
	 *
	 * @return string
	 */
	public static function shortcode(): string {
		ob_start();
		self::render_form();
		return (string) ob_get_clean();
	}

	/**
	 * Render form.
	 */
	public static function render_form(): void {
		?>
		<form class="newsletter-card" data-isnx-newsletter>
			<h2><?php echo esc_html( (string) option( 'newsletter_heading', __( 'Get the daily security briefing', 'infosecnexus-toolkit' ) ) ); ?></h2>
			<p><?php echo esc_html( (string) option( 'newsletter_intro', '' ) ); ?></p>
			<label>
				<span class="screen-reader-text"><?php esc_html_e( 'Email address', 'infosecnexus-toolkit' ); ?></span>
				<input type="email" name="email" required placeholder="<?php esc_attr_e( 'you@example.com', 'infosecnexus-toolkit' ); ?>">
			</label>
			<label class="isnx-hp" aria-hidden="true">
				<span><?php esc_html_e( 'Website', 'infosecnexus-toolkit' ); ?></span>
				<input type="text" name="website" tabindex="-1" autocomplete="off">
			</label>
			<button type="submit"><?php esc_html_e( 'Subscribe', 'infosecnexus-toolkit' ); ?></button>
			<p class="newsletter-card__status" data-isnx-newsletter-status aria-live="polite"></p>
		</form>
		<?php
	}
}
