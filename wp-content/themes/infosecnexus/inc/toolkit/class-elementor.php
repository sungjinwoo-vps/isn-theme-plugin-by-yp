<?php
/**
 * Elementor integration.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

namespace InfoSecNexus\Theme\Toolkit;

/**
 * Elementor module.
 */
final class Elementor {
	/**
	 * Register hooks.
	 */
	public static function boot(): void {
		if ( ! module_enabled( 'elementor_widgets' ) ) {
			return;
		}
		add_action( 'elementor/widgets/register', array( __CLASS__, 'register_widgets' ) );
		add_action( 'elementor/elements/categories_registered', array( __CLASS__, 'register_category' ) );
	}

	/**
	 * Register widget category.
	 *
	 * @param object $manager Category manager.
	 */
	public static function register_category( $manager ): void {
		if ( method_exists( $manager, 'add_category' ) ) {
			$manager->add_category(
				'infosecnexus',
				array(
					'title' => __( 'InfoSecNexus', 'infosecnexus' ),
					'icon'  => 'fa fa-shield-alt',
				)
			);
		}
	}

	/**
	 * Register widgets.
	 *
	 * @param object $widgets_manager Widgets manager.
	 */
	public static function register_widgets( $widgets_manager ): void {
		if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
			return;
		}

		require_once INFOSECNEXUS_THEME_TOOLKIT_DIR . 'inc/toolkit/elementor/registry.php';

		foreach ( Widgets\registry() as $class ) {
			if ( class_exists( $class ) ) {
				$widgets_manager->register( new $class() );
			}
		}
	}
}
