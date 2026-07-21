<?php
/**
 * Custom CSS/JS snippets.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

namespace InfoSecNexus\Theme\Toolkit;

/**
 * Snippets module.
 */
final class Snippets {
	/**
	 * Register hooks.
	 */
	public static function boot(): void {
		if ( ! module_enabled( 'snippets' ) ) {
			return;
		}
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ), 20 );
	}

	/**
	 * Add inline snippets through WordPress asset APIs.
	 */
	public static function enqueue(): void {
		$css = trim( (string) option( 'custom_css', '' ) );
		if ( '' !== $css ) {
			wp_add_inline_style( 'infosecnexus', wp_strip_all_tags( $css ) );
		}

		$js = trim( (string) option( 'custom_js', '' ) );
		if ( '' !== $js ) {
			wp_add_inline_script( 'infosecnexus', str_replace( array( '<script', '</script', '<?php' ), '', $js ) );
		}
	}
}
