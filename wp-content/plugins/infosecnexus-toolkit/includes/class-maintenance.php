<?php
/**
 * Maintenance mode.
 *
 * @package InfoSecNexusToolkit
 */

declare(strict_types=1);

namespace InfoSecNexus\Toolkit;

/**
 * Maintenance module.
 */
final class Maintenance {
	/**
	 * Register hooks.
	 */
	public static function boot(): void {
		if ( ! module_enabled( 'maintenance' ) ) {
			return;
		}
		add_action( 'template_redirect', array( __CLASS__, 'maybe_render' ), 0 );
	}

	/**
	 * Render maintenance page for public visitors.
	 */
	public static function maybe_render(): void {
		if ( is_user_logged_in() || is_admin() || ! (bool) option( 'maintenance_enabled', false ) ) {
			return;
		}

		status_header( 503 );
		header( 'Retry-After: 3600' );
		get_header();
		echo '<main id="primary" class="site-main"><section class="not-found"><h1>' . esc_html__( 'Maintenance in progress', 'infosecnexus-toolkit' ) . '</h1>';
		if ( ! Content_Blocks::render_location( 'maintenance' ) ) {
			echo '<p>' . esc_html__( 'InfoSecNexus is being updated. Please check back soon.', 'infosecnexus-toolkit' ) . '</p>';
		}
		echo '</section></main>';
		get_footer();
		exit;
	}
}
