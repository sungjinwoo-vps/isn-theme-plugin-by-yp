<?php
/**
 * Elementor widget registry.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

namespace InfoSecNexus\Theme\Toolkit\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/class-query-widget.php';
require_once __DIR__ . '/class-featured-story.php';
require_once __DIR__ . '/class-posts-grid.php';
require_once __DIR__ . '/class-category-posts.php';
require_once __DIR__ . '/class-news-ticker.php';
require_once __DIR__ . '/class-critical-cve-list.php';
require_once __DIR__ . '/class-latest-updates.php';
require_once __DIR__ . '/class-author-box.php';
require_once __DIR__ . '/class-related-posts-widget.php';
require_once __DIR__ . '/class-table-of-contents.php';
require_once __DIR__ . '/class-search-widget.php';
require_once __DIR__ . '/class-breadcrumbs-widget.php';

/**
 * Widget class registry.
 *
 * @return string[]
 */
function registry(): array {
	return array(
		Featured_Story::class,
		Posts_Grid::class,
		Category_Posts::class,
		News_Ticker::class,
		Critical_CVE_List::class,
		Latest_Updates::class,
		Author_Box::class,
		Related_Posts_Widget::class,
		Table_Of_Contents::class,
		Search_Widget::class,
		Breadcrumbs_Widget::class,
	);
}
