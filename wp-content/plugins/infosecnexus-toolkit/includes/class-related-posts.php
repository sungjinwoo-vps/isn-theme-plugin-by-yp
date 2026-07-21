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
				'post__not_in'        => array( get_the_ID() ),
				'posts_per_page'      => 3,
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
			)
		);

		if ( ! $query->have_posts() ) {
			return;
		}

		echo '<section class="related-posts"><h2>' . esc_html__( 'Related briefings', 'infosecnexus-toolkit' ) . '</h2><div class="post-grid post-grid--3">';
		while ( $query->have_posts() ) {
			$query->the_post();
			$post_id = get_the_ID();
			echo '<article class="' . esc_attr( implode( ' ', get_post_class( 'post-card post-card--compact', $post_id ) ) ) . '">';
			self::render_image( $post_id );
			echo '<div class="post-card__body">';
			self::render_category_badges( $post_id );
			echo '<h3 class="post-card__title"><a href="' . esc_url( get_permalink( $post_id ) ) . '">' . esc_html( get_the_title( $post_id ) ) . '</a></h3>';
			self::render_meta( $post_id );
			echo '<p class="post-card__excerpt">' . esc_html( wp_trim_words( get_the_excerpt( $post_id ), 18 ) ) . '</p>';
			echo '</div>';
			echo '</article>';
		}
		wp_reset_postdata();
		echo '</div></section>';
	}

	/**
	 * Render a related post image with a category-aware fallback.
	 *
	 * @param int $post_id Post ID.
	 */
	private static function render_image( int $post_id ): void {
		echo '<a class="post-card__image" href="' . esc_url( get_permalink( $post_id ) ) . '" aria-label="' . esc_attr( get_the_title( $post_id ) ) . '">';
		if ( has_post_thumbnail( $post_id ) ) {
			echo get_the_post_thumbnail( $post_id, 'large', array( 'loading' => 'lazy' ) );
		} else {
			$image_url = self::fallback_image_url( $post_id );
			if ( $image_url ) {
				echo '<img src="' . esc_url( $image_url ) . '" alt="" loading="lazy">';
			}
		}
		echo '</a>';
	}

	/**
	 * Render category badges using the active theme markup.
	 *
	 * @param int $post_id Post ID.
	 */
	private static function render_category_badges( int $post_id ): void {
		$categories = get_the_category( $post_id );
		if ( empty( $categories ) ) {
			return;
		}

		echo '<div class="category-badges">';
		foreach ( array_slice( $categories, 0, 2 ) as $category ) {
			$slug = sanitize_html_class( $category->slug );
			echo '<a class="category-badge category-badge--' . esc_attr( $slug ) . '" href="' . esc_url( get_category_link( $category ) ) . '">' . esc_html( $category->name ) . '</a>';
		}
		echo '</div>';
	}

	/**
	 * Render compact related post meta.
	 *
	 * @param int $post_id Post ID.
	 */
	private static function render_meta( int $post_id ): void {
		$author_id = (int) get_post_field( 'post_author', $post_id );
		echo '<div class="entry-meta">';
		echo '<span>' . esc_html( get_the_date( '', $post_id ) ) . '</span>';
		echo '<span>' . esc_html( get_the_author_meta( 'display_name', $author_id ) ) . '</span>';
		/* translators: %d: estimated reading time in minutes. */
		echo '<span>' . esc_html( sprintf( _n( '%d min read', '%d min read', self::reading_time( $post_id ), 'infosecnexus-toolkit' ), self::reading_time( $post_id ) ) ) . '</span>';
		echo '</div>';
	}

	/**
	 * Estimate reading time for related cards.
	 *
	 * @param int $post_id Post ID.
	 * @return int
	 */
	private static function reading_time( int $post_id ): int {
		if ( function_exists( '\\InfoSecNexus\\Theme\\Template_Tags\\reading_time' ) ) {
			return \InfoSecNexus\Theme\Template_Tags\reading_time( $post_id );
		}

		$text  = wp_strip_all_tags( (string) get_post_field( 'post_content', $post_id ) );
		$words = str_word_count( $text );
		return max( 1, (int) ceil( $words / 220 ) );
	}

	/**
	 * Return a category-aware fallback image URL from the active theme.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	private static function fallback_image_url( int $post_id ): string {
		if ( function_exists( '\\InfoSecNexus\\Theme\\Template_Tags\\fallback_image_url' ) ) {
			return \InfoSecNexus\Theme\Template_Tags\fallback_image_url( $post_id );
		}

		$template_dir = get_template_directory();
		$template_uri = get_template_directory_uri();
		$slugs        = wp_get_post_categories(
			$post_id,
			array(
				'fields' => 'slugs',
			)
		);

		$file = 'hero-shield.png';
		if ( in_array( 'critical-cves', $slugs, true ) ) {
			$file = 'lock-chip.png';
		} elseif ( in_array( 'linux-administration', $slugs, true ) || in_array( 'devops', $slugs, true ) ) {
			$file = 'linux-circuit.png';
		} elseif ( in_array( 'artificial-intelligence', $slugs, true ) ) {
			$file = 'data-center.png';
		} elseif ( in_array( 'cybersecurity', $slugs, true ) ) {
			$file = 'cloud-security.png';
		}

		if ( file_exists( $template_dir . '/assets/images/' . $file ) ) {
			return $template_uri . '/assets/images/' . $file;
		}

		return '';
	}
}
