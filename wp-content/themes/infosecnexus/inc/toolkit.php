<?php
/**
 * Theme-bundled toolkit features.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

namespace InfoSecNexus\Theme\Toolkit;

/**
 * Register the built-in toolkit.
 */
function bootstrap(): void {
	if ( ! defined( 'INFOSECNEXUS_THEME_HAS_BUILTIN_TOOLKIT' ) ) {
		define( 'INFOSECNEXUS_THEME_HAS_BUILTIN_TOOLKIT', true );
	}
	if ( ! defined( 'INFOSECNEXUS_THEME_TOOLKIT_VERSION' ) ) {
		define( 'INFOSECNEXUS_THEME_TOOLKIT_VERSION', INFOSECNEXUS_VERSION );
	}
	if ( ! defined( 'INFOSECNEXUS_THEME_TOOLKIT_DIR' ) ) {
		define( 'INFOSECNEXUS_THEME_TOOLKIT_DIR', INFOSECNEXUS_DIR . '/' );
	}
	if ( ! defined( 'INFOSECNEXUS_THEME_TOOLKIT_URL' ) ) {
		define( 'INFOSECNEXUS_THEME_TOOLKIT_URL', INFOSECNEXUS_URI . '/' );
	}

	if ( defined( 'INFOSECNEXUS_TOOLKIT_VERSION' ) && ! defined( 'INFOSECNEXUS_TOOLKIT_BRIDGED_TO_THEME' ) ) {
		return;
	}

	$files = array(
		'inc/toolkit/helpers.php',
		'inc/toolkit/class-mailer.php',
		'inc/toolkit/class-contact-form.php',
		'inc/toolkit/class-plugin.php',
		'inc/toolkit/class-settings.php',
		'inc/toolkit/class-assets.php',
		'inc/toolkit/class-conditions.php',
		'inc/toolkit/class-content-blocks.php',
		'inc/toolkit/class-admin-meta.php',
		'inc/toolkit/class-sidebars.php',
		'inc/toolkit/class-search.php',
		'inc/toolkit/class-newsletter.php',
		'inc/toolkit/class-cookie-consent.php',
		'inc/toolkit/class-related-posts.php',
		'inc/toolkit/class-live-intelligence.php',
		'inc/toolkit/class-post-artwork.php',
		'inc/toolkit/class-demo-content.php',
		'inc/toolkit/class-content-retirement.php',
		'inc/toolkit/class-snippets.php',
		'inc/toolkit/class-maintenance.php',
		'inc/toolkit/class-woocommerce.php',
		'inc/toolkit/class-elementor.php',
		'inc/toolkit/global-functions.php',
	);

	foreach ( $files as $file ) {
		require_once INFOSECNEXUS_DIR . '/' . $file;
	}

	add_action( 'after_switch_theme', array( Plugin::class, 'activate' ) );
	Plugin::instance()->boot();
}
