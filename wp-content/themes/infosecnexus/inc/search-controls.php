<?php
/**
 * Search behavior controls.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

namespace InfoSecNexus\Theme\Search_Controls;

use WP_Query;

/**
 * Register hooks.
 */
function bootstrap(): void {
	add_action( 'pre_get_posts', __NAMESPACE__ . '\\restrict_public_search_to_posts' );
}

/**
 * Keep public site search focused on published blog posts.
 *
 * @param WP_Query $query Query object.
 */
function restrict_public_search_to_posts( WP_Query $query ): void {
	if ( is_admin() || ! $query->is_main_query() || ! $query->is_search() ) {
		return;
	}

	$query->set( 'post_type', 'post' );
	$query->set( 'post_status', 'publish' );
}
