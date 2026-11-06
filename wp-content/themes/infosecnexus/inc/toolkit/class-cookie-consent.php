<?php
/**
 * Cookie consent preference banner.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

namespace InfoSecNexus\Theme\Toolkit;

/**
 * Cookie consent.
 */
final class Cookie_Consent {
	/**
	 * Register hooks.
	 */
	public static function boot(): void {
		if ( ! module_enabled( 'cookie_consent' ) ) {
			return;
		}
		add_action( 'wp_footer', array( __CLASS__, 'render' ) );
	}

	/**
	 * Render banner.
	 */
	public static function render(): void {
		?>
		<div class="isnx-cookie" data-isnx-cookie role="region" aria-label="<?php esc_attr_e( 'Cookie preferences', 'infosecnexus' ); ?>" hidden>
			<p><?php echo esc_html( (string) option( 'cookie_text', '' ) ); ?></p>
			<div class="isnx-cookie__actions">
			<button type="button" data-isnx-cookie-accept><?php esc_html_e( 'Accept preferences', 'infosecnexus' ); ?></button>
			<button class="isnx-cookie__dismiss" type="button" data-isnx-cookie-dismiss><?php esc_html_e( 'Not now', 'infosecnexus' ); ?></button>
			</div>
		</div>
		<?php
	}
}
