<?php
/**
 * Table of Contents Elementor widget.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

namespace InfoSecNexus\Theme\Toolkit\Widgets;

use Elementor\Widget_Base;

/**
 * Table of Contents widget.
 */
final class Table_Of_Contents extends Widget_Base {
	/**
	 * Get widget name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'infosecnexus_table_of_contents';
	}

	/**
	 * Get widget title.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return __( 'Table of Contents', 'infosecnexus' );
	}

	/**
	 * Get widget icon.
	 *
	 * @return string
	 */
	public function get_icon(): string {
		return 'eicon-table-of-contents';
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
		if ( function_exists( '\InfoSecNexus\Theme\Template_Tags\table_of_contents' ) ) {
			\InfoSecNexus\Theme\Template_Tags\table_of_contents();
		}
	}
}
