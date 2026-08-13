<?php
/**
 * Related posts module.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

namespace InfoSecNexus\Theme\Toolkit;

/**
 * Related posts.
 */
final class Related_Posts {
	/**
	 * Register hooks.
	 */
	public static function boot(): void {
		if ( ! module_enabled( 'related_posts' ) ) {
			return;
		}
		add_shortcode( 'infosecnexus_related_posts', array( __CLASS__, 'shortcode' ) );
	}

	/**
	 * Shortcode callback.
	 *
	 * @return string
	 */
	public static function shortcode(): string {
		ob_start();
		self::render();
		return (string) ob_get_clean();
	}

	/**
	 * Render related posts.
	 */
	public static function render(): void {
		if ( ! is_singular( 'post' ) ) {
			return;
		}

		$terms = wp_get_post_terms( get_the_ID(), 'category', array( 'fields' => 'ids' ) );
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return;
		}

		$query = new \WP_Query(
			array(
				'category__in'        => array_map( 'absint', $terms ),
				'post__not_in'        => array( get_the_ID() ),
				'posts_per_page'      => 3,
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
			)
		);

		if ( ! $query->have_posts() ) {
			return;
		}

		echo '<section class="related-posts"><h2>' . esc_html__( 'Related briefings', 'infosecnexus' ) . '</h2><div class="post-grid post-grid--3">';
		while ( $query->have_posts() ) {
			$query->the_post();
			\InfoSecNexus\Theme\Template_Tags\post_card( 'compact', 'h3' );
		}
		wp_reset_postdata();
		echo '</div></section>';
	}
}
