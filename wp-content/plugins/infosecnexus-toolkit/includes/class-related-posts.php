<?php
/**
 * Related posts module.
 *
 * @package InfoSecNexusToolkit
 */

declare(strict_types=1);

namespace InfoSecNexus\Toolkit;

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
				'posts_per_page'      => 4,
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
			)
		);

		if ( ! $query->have_posts() ) {
			return;
		}

		echo '<section class="related-posts"><h2>' . esc_html__( 'Related briefings', 'infosecnexus-toolkit' ) . '</h2><div class="isnx-related-grid">';
		$rendered = 0;
		while ( $query->have_posts() ) {
			$query->the_post();
			if ( get_the_ID() === get_queried_object_id() ) {
				continue;
			}
			++$rendered;
			echo '<article class="isnx-related-card">';
			if ( has_post_thumbnail() ) {
				echo '<a href="' . esc_url( get_permalink() ) . '">' . get_the_post_thumbnail( get_the_ID(), 'medium', array( 'loading' => 'lazy' ) ) . '</a>';
			}
			echo '<h3><a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a></h3>';
			echo '</article>';
			if ( 3 <= $rendered ) {
				break;
			}
		}
		wp_reset_postdata();
		echo '</div></section>';
	}
}
