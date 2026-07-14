<?php
/**
 * Category Posts Elementor widget.
 *
 * @package InfoSecNexusToolkit
 */

declare(strict_types=1);

namespace InfoSecNexus\Toolkit\Widgets;

/**
 * Category Posts widget.
 */
final class Category_Posts extends Posts_Grid {
	/**
	 * Get widget name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'infosecnexus_category_posts';
	}

	/**
	 * Get widget title.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return __( 'Category Posts', 'infosecnexus-toolkit' );
	}
}
