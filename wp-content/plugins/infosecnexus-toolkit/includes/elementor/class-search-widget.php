<?php
/**
 * Search Elementor widget.
 *
 * @package InfoSecNexusToolkit
 */

declare(strict_types=1);

namespace InfoSecNexus\Toolkit\Widgets;

use Elementor\Widget_Base;

/**
 * Search widget.
 */
final class Search_Widget extends Widget_Base {
	/**
	 * Get widget name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'infosecnexus_search';
	}

	/**
	 * Get widget title.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return __( 'Search', 'infosecnexus-toolkit' );
	}

	/**
	 * Get widget icon.
	 *
	 * @return string
	 */
	public function get_icon(): string {
		return 'eicon-search';
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
		get_search_form();
	}
}
