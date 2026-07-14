<?php
/**
 * Posts Grid Elementor widget.
 *
 * @package InfoSecNexusToolkit
 */

declare(strict_types=1);

namespace InfoSecNexus\Toolkit\Widgets;

/**
 * Posts Grid widget.
 */
class Posts_Grid extends Query_Widget {
	/**
	 * Get widget name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'infosecnexus_posts_grid';
	}

	/**
	 * Get widget title.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return __( 'Posts Grid', 'infosecnexus-toolkit' );
	}

	/**
	 * Register controls.
	 */
	protected function register_controls(): void {
		$this->register_query_controls();
	}

	/**
	 * Render widget.
	 */
	protected function render(): void {
		$this->render_cards( new \WP_Query( $this->query_args( $this->get_settings_for_display() ) ) );
	}
}
