<?php
/**
 * Related Posts Elementor widget.
 *
 * @package InfoSecNexusToolkit
 */

declare(strict_types=1);

namespace InfoSecNexus\Toolkit\Widgets;

use Elementor\Widget_Base;

/**
 * Related Posts widget.
 */
final class Related_Posts_Widget extends Widget_Base {
	/**
	 * Get widget name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'infosecnexus_related_posts';
	}

	/**
	 * Get widget title.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return __( 'Related Posts', 'infosecnexus-toolkit' );
	}

	/**
	 * Get widget icon.
	 *
	 * @return string
	 */
	public function get_icon(): string {
		return 'eicon-posts-group';
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
		\InfoSecNexus\Toolkit\Related_Posts::render();
	}
}
