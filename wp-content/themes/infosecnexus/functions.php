<?php
/**
 * Theme bootstrap.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'INFOSECNEXUS_VERSION', '0.1.14' );
define( 'INFOSECNEXUS_DIR', get_template_directory() );
define( 'INFOSECNEXUS_URI', get_template_directory_uri() );

$infosecnexus_includes = array(
	'inc/setup.php',
	'inc/customizer.php',
	'inc/customizer-tools.php',
	'inc/toolkit.php',
	'inc/updater.php',
	'inc/assets.php',
	'inc/header-builder.php',
	'inc/footer-builder.php',
	'inc/breadcrumbs.php',
	'inc/template-tags.php',
	'inc/elementor.php',
	'inc/woocommerce.php',
);

foreach ( $infosecnexus_includes as $infosecnexus_file ) {
	require_once INFOSECNEXUS_DIR . '/' . $infosecnexus_file;
}

\InfoSecNexus\Theme\Setup\bootstrap();
\InfoSecNexus\Theme\Customizer\bootstrap();
\InfoSecNexus\Theme\Customizer_Tools\bootstrap();
\InfoSecNexus\Theme\Toolkit\bootstrap();
\InfoSecNexus\Theme\Updater\bootstrap();
\InfoSecNexus\Theme\Assets\bootstrap();
\InfoSecNexus\Theme\Header_Builder\bootstrap();
\InfoSecNexus\Theme\Footer_Builder\bootstrap();
\InfoSecNexus\Theme\Elementor\bootstrap();
\InfoSecNexus\Theme\WooCommerce\bootstrap();
