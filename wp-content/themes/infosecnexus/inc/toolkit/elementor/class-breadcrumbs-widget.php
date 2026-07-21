<?php
/**
 * Breadcrumbs Elementor widget.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

namespace InfoSecNexus\Theme\Toolkit\Widgets;

use Elementor\Widget_Base;

/**
 * Breadcrumbs widget.
 */
final class Breadcrumbs_Widget extends Widget_Base {
	/**
	 * Get widget name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'infosecnexus_breadcrumbs';
	}

	/**
	 * Get widget title.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return __( 'Breadcrumbs', 'infosecnexus' );
	}

	/**
	 * Get widget icon.
	 *
	 * @return string
	 */
	public function get_icon(): string {
		return 'eicon-navigation-horizontal';
	}

	/**
	 * Get widget categories.
	 *
	 * @return string[]
	 */
	public function get_categories(): array {
		return array( 'infosecnexus' );
	}

	/**
	 * Register controls.
	 */
	protected function register_controls(): void {}

	/**
	 * Render widget.
	 */
	protected function render(): void {
		if ( function_exists( '\InfoSecNexus\Theme\Breadcrumbs\render' ) ) {
			\InfoSecNexus\Theme\Breadcrumbs\render();
		}
	}
}
