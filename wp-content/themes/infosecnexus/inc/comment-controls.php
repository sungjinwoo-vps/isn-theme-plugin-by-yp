<?php
/**
 * Comment display controls.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

namespace InfoSecNexus\Theme\Comment_Controls;

/**
 * Register hooks.
 */
function bootstrap(): void {
	add_filter( 'comments_open', __NAMESPACE__ . '\\disable_post_comments', 10, 2 );
	add_filter( 'pings_open', __NAMESPACE__ . '\\disable_post_comments', 10, 2 );
}

/**
 * Disable public comment and ping forms on blog posts.
 *
 * @param bool $open Whether comments/pings are open.
 * @param int  $post_id Post ID.
 * @return bool
 */
function disable_post_comments( bool $open, int $post_id ): bool {
	if ( 'post' === get_post_type( $post_id ) ) {
		return false;
	}

	return $open;
}
