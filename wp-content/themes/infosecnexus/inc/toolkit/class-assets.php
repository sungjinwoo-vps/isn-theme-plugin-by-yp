<?php
/**
 * Frontend assets.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

namespace InfoSecNexus\Theme\Toolkit;

/**
 * Assets class.
 */
final class Assets {
	/**
	 * Register hooks.
	 */
	public static function boot(): void {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ), 20 );
	}

	/**
	 * Enqueue assets when a frontend module needs them.
	 */
	public static function enqueue(): void {
		$needs_js = module_enabled( 'live_search' ) || module_enabled( 'newsletter' ) || module_enabled( 'cookie_consent' ) || module_enabled( 'content_blocks' ) || module_enabled( 'snippets' );
		$css_file = INFOSECNEXUS_THEME_TOOLKIT_DIR . 'assets/css/toolkit.css';
		if ( is_readable( $css_file ) ) {
			$css = file_get_contents( $css_file );
			if ( is_string( $css ) && '' !== trim( $css ) ) {
				wp_add_inline_style( 'infosecnexus-style', $css );
			}
		}

		if ( $needs_js ) {
			wp_enqueue_script(
				'infosecnexus-toolkit',
				INFOSECNEXUS_THEME_TOOLKIT_URL . 'assets/js/toolkit.js',
				array(),
				INFOSECNEXUS_THEME_TOOLKIT_VERSION,
				array(
					'in_footer' => true,
					'strategy'  => 'defer',
				)
			);
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
