<?php
/**
 * Breadcrumb compatibility layer.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

namespace InfoSecNexus\Theme\Breadcrumbs;

/**
 * Render breadcrumbs from common SEO plugins or native fallback.
 */
function render(): void {
	echo '<nav class="breadcrumbs" aria-label="' . esc_attr__( 'Breadcrumbs', 'infosecnexus' ) . '">';

	if ( function_exists( 'yoast_breadcrumb' ) ) {
		yoast_breadcrumb( '<span>', '</span>' );
		echo '</nav>';
		return;
	}

	if ( function_exists( 'rank_math_the_breadcrumbs' ) ) {
		rank_math_the_breadcrumbs();
		echo '</nav>';
		return;
	}

	if ( function_exists( 'seopress_display_breadcrumbs' ) ) {
		seopress_display_breadcrumbs();
		echo '</nav>';
		return;
	}

	if ( function_exists( 'bcn_display' ) ) {
		bcn_display();
		echo '</nav>';
		return;
	}

	echo '<ol>';
	echo '<li><a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Home', 'infosecnexus' ) . '</a></li>';

	if ( is_singular() ) {
		$post_type = get_post_type();
		if ( 'post' === $post_type ) {
			$categories = get_the_category();
			if ( $categories ) {
				echo '<li><a href="' . esc_url( get_category_link( $categories[0] ) ) . '">' . esc_html( $categories[0]->name ) . '</a></li>';
			}
		} elseif ( $post_type && 'page' !== $post_type ) {
			$object = get_post_type_object( $post_type );
			if ( $object ) {
				echo '<li>' . esc_html( $object->labels->name ) . '</li>';
			}
		}
		echo '<li aria-current="page">' . esc_html( get_the_title() ) . '</li>';
	} elseif ( is_archive() ) {
		echo '<li aria-current="page">' . esc_html( archive_label() ) . '</li>';
	} elseif ( is_search() ) {
		echo '<li aria-current="page">' . esc_html__( 'Search', 'infosecnexus' ) . '</li>';
	} elseif ( is_404() ) {
		echo '<li aria-current="page">' . esc_html__( 'Not Found', 'infosecnexus' ) . '</li>';
	}

	echo '</ol>';
	echo '</nav>';
}

/**
 * Return a clean archive label without WordPress archive prefixes or HTML markup.
 *
 * @return string
 */
function archive_label(): string {
	if ( is_category() || is_tag() || is_tax() ) {
		return single_term_title( '', false );
	}

	if ( is_author() ) {
		return get_the_author();
	}

	if ( is_post_type_archive() ) {
		return post_type_archive_title( '', false );
	}

	if ( is_year() ) {
		return get_the_date( 'Y' );
	}

	if ( is_month() ) {
		return get_the_date( 'F Y' );
	}

	if ( is_day() ) {
		return get_the_date();
	}

	return wp_strip_all_tags( get_the_archive_title() );
}
