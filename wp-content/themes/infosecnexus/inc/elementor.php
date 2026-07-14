<?php
/**
 * Elementor theme location compatibility.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

namespace InfoSecNexus\Theme\Elementor;

/**
 * Register hooks.
 */
function bootstrap(): void {
	add_action( 'after_setup_theme', __NAMESPACE__ . '\\support' );
	add_action( 'elementor/theme/register_locations', __NAMESPACE__ . '\\register_locations' );
}

/**
 * Add Elementor support.
 */
function support(): void {
	add_theme_support( 'elementor' );
}

/**
 * Register Elementor locations.
 *
 * @param object $manager Elementor locations manager.
 */
function register_locations( $manager ): void {
	if ( method_exists( $manager, 'register_all_core_location' ) ) {
		$manager->register_all_core_location();
		return;
	}

	foreach ( array( 'header', 'footer', 'single', 'archive' ) as $location ) {
		if ( method_exists( $manager, 'register_location' ) ) {
			$manager->register_location( $location );
		}
	}
}

/**
 * Render Elementor location if available.
 *
 * @param string $location Location.
 * @return bool
 */
function render_location( string $location ): bool {
	if ( function_exists( 'elementor_theme_do_location' ) && elementor_theme_do_location( $location ) ) {
		return true;
	}

	return false;
}
