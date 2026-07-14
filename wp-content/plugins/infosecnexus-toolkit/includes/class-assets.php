<?php
/**
 * Frontend assets.
 *
 * @package InfoSecNexusToolkit
 */

declare(strict_types=1);

namespace InfoSecNexus\Toolkit;

/**
 * Assets class.
 */
final class Assets {
	/**
	 * Register hooks.
	 */
	public static function boot(): void {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
	}

	/**
	 * Enqueue assets when a frontend module needs them.
	 */
	public static function enqueue(): void {
		$needs_js = module_enabled( 'live_search' ) || module_enabled( 'newsletter' ) || module_enabled( 'cookie_consent' ) || module_enabled( 'content_blocks' ) || module_enabled( 'snippets' );
		wp_enqueue_style( 'infosecnexus-toolkit', INFOSECNEXUS_TOOLKIT_URL . 'assets/css/toolkit.css', array(), INFOSECNEXUS_TOOLKIT_VERSION );

		if ( $needs_js ) {
			wp_enqueue_script( 'infosecnexus-toolkit', INFOSECNEXUS_TOOLKIT_URL . 'assets/js/toolkit.js', array(), INFOSECNEXUS_TOOLKIT_VERSION, true );
			wp_localize_script(
				'infosecnexus-toolkit',
				'infosecnexusToolkit',
				array(
					'restUrl'   => esc_url_raw( rest_url( 'infosecnexus/v1/' ) ),
					'restNonce' => wp_create_nonce( 'wp_rest' ),
				)
			);
		}
	}
}
