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
	add_action( 'pre_get_posts', __NAMESPACE__ . '\\exclude_rolling_brief_from_category_pagination' );
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

/**
 * Keep the permanent rolling brief out of date-based category pagination.
 *
 * The archive template pins it once on page one. Excluding it from the normal
 * query prevents it from reappearing on a later page as the archive grows.
 *
 * @param WP_Query $query Query object.
 */
function exclude_rolling_brief_from_category_pagination( WP_Query $query ): void {
	if ( is_admin() || ! $query->is_main_query() || ! $query->is_category() ) {
		return;
	}

	$rolling_brief = \InfoSecNexus\Theme\Template_Tags\rolling_brief_post();
	if ( ! $rolling_brief ) {
		return;
	}

	$excluded   = array_map( 'absint', (array) $query->get( 'post__not_in' ) );
	$excluded[] = (int) $rolling_brief->ID;
	$query->set( 'post__not_in', array_values( array_unique( $excluded ) ) );
}
