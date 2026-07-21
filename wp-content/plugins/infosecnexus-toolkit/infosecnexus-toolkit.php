<?php
/**
 * Plugin Name: InfoSecNexus Toolkit
 * Plugin URI: https://infosecnexus.com/
 * Description: Companion functionality for the InfoSecNexus theme: content blocks, conditions, Elementor widgets, live search, sidebars, newsletter, and optional modules.
 * Version: 0.1.13
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * Author: InfoSecNexus
 * Author URI: https://infosecnexus.com/
 * Update URI: https://github.com/sungjinwoo-vps/isn-theme-plugin-by-yp
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: infosecnexus-toolkit
 *
 * @package InfoSecNexusToolkit
 */

declare(strict_types=1);

namespace InfoSecNexus\Toolkit;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'INFOSECNEXUS_TOOLKIT_VERSION', '0.1.13' );
define( 'INFOSECNEXUS_TOOLKIT_FILE', __FILE__ );
define( 'INFOSECNEXUS_TOOLKIT_DIR', plugin_dir_path( __FILE__ ) );
define( 'INFOSECNEXUS_TOOLKIT_URL', plugin_dir_url( __FILE__ ) );

require_once INFOSECNEXUS_TOOLKIT_DIR . 'includes/helpers.php';
require_once INFOSECNEXUS_TOOLKIT_DIR . 'includes/class-updater.php';
require_once INFOSECNEXUS_TOOLKIT_DIR . 'includes/class-plugin.php';
require_once INFOSECNEXUS_TOOLKIT_DIR . 'includes/class-settings.php';
require_once INFOSECNEXUS_TOOLKIT_DIR . 'includes/class-assets.php';
require_once INFOSECNEXUS_TOOLKIT_DIR . 'includes/class-conditions.php';
require_once INFOSECNEXUS_TOOLKIT_DIR . 'includes/class-content-blocks.php';
require_once INFOSECNEXUS_TOOLKIT_DIR . 'includes/class-admin-meta.php';
require_once INFOSECNEXUS_TOOLKIT_DIR . 'includes/class-sidebars.php';
require_once INFOSECNEXUS_TOOLKIT_DIR . 'includes/class-search.php';
require_once INFOSECNEXUS_TOOLKIT_DIR . 'includes/class-newsletter.php';
require_once INFOSECNEXUS_TOOLKIT_DIR . 'includes/class-cookie-consent.php';
require_once INFOSECNEXUS_TOOLKIT_DIR . 'includes/class-related-posts.php';
require_once INFOSECNEXUS_TOOLKIT_DIR . 'includes/class-demo-content.php';
require_once INFOSECNEXUS_TOOLKIT_DIR . 'includes/class-snippets.php';
require_once INFOSECNEXUS_TOOLKIT_DIR . 'includes/class-maintenance.php';
require_once INFOSECNEXUS_TOOLKIT_DIR . 'includes/class-woocommerce.php';
require_once INFOSECNEXUS_TOOLKIT_DIR . 'includes/class-elementor.php';
require_once INFOSECNEXUS_TOOLKIT_DIR . 'includes/global-functions.php';

register_activation_hook( __FILE__, array( Plugin::class, 'activate' ) );

Plugin::instance()->boot();
