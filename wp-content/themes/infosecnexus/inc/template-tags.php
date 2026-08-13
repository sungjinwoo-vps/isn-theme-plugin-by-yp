<?php
/**
 * Template helpers.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

namespace InfoSecNexus\Theme\Template_Tags;

/**
 * Estimate reading time.
 *
 * @param int|null $post_id Post ID.
 * @return int
 */
function reading_time( ?int $post_id = null ): int {
	if ( ! $post_id ) {
		$post_id = get_the_ID();
	}
	$text  = wp_strip_all_tags( (string) get_post_field( 'post_content', $post_id ) );
	$words = str_word_count( $text );
	return max( 1, (int) ceil( $words / 220 ) );
}

/**
 * Return the public date metadata for a post.
 *
 * Rolling briefings expose their latest revision while ordinary posts retain
 * their publication date.
 *
 * @param int|null $post_id Post ID.
 * @return array{datetime:string,label:string}
 */
function post_date_data( ?int $post_id = null ): array {
	if ( ! $post_id ) {
		$post_id = (int) get_the_ID();
	}

	$is_live = 'rolling' === (string) get_post_meta( $post_id, '_infosecnexus_newsroom_kind', true );
	if ( $is_live ) {
		return array(
			'datetime' => (string) get_post_modified_time( DATE_W3C, false, $post_id ),
			/* translators: %s: date when the rolling briefing was last updated. */
			'label'    => sprintf( __( 'Updated %s', 'infosecnexus' ), get_the_modified_date( '', $post_id ) ),
		);
	}

	return array(
		'datetime' => (string) get_the_date( DATE_W3C, $post_id ),
		'label'    => (string) get_the_date( '', $post_id ),
	);
}

/**
 * Render post meta.
 */
function post_meta(): void {
	$date = post_date_data( (int) get_the_ID() );

	echo '<div class="entry-meta">';
	echo '<time datetime="' . esc_attr( $date['datetime'] ) . '">' . esc_html( $date['label'] ) . '</time>';
	echo '<span>' . esc_html( get_the_author() ) . '</span>';
	/* translators: %d: estimated reading time in minutes. */
	echo '<span>' . esc_html( sprintf( _n( '%d min read', '%d min read', reading_time(), 'infosecnexus' ), reading_time() ) ) . '</span>';
	echo '</div>';
}

/**
 * Render category badges.
 */
function category_badges(): void {
	$categories = get_the_category();
	if ( empty( $categories ) ) {
		return;
	}

	echo '<div class="category-badges">';
	foreach ( array_slice( $categories, 0, 3 ) as $category ) {
		$slug = sanitize_html_class( $category->slug );
		echo '<a class="category-badge category-badge--' . esc_attr( $slug ) . '" href="' . esc_url( get_category_link( $category ) ) . '">' . esc_html( $category->name ) . '</a>';
	}
	echo '</div>';
}

/**
 * Return a theme image asset URL.
 *
 * @param string $file Asset file name.
 * @return string
 */
function asset_url( string $file ): string {
	$webp = preg_replace( '/\.(png|jpg|jpeg)$/', '.webp', $file );
	if ( is_string( $webp ) && file_exists( get_template_directory() . '/assets/images/' . $webp ) ) {
		$file = $webp;
	}

	return get_template_directory_uri() . '/assets/images/' . $file;
}

/**
 * Return a responsive srcset for theme image assets.
 *
 * @param string $file Asset file name.
 * @return string
 */
function asset_srcset( string $file ): string {
	$base  = (string) preg_replace( '/\.(png|jpg|jpeg|webp)$/i', '', $file );
	$items = array();
	foreach ( array( 480, 720, 960, 1280 ) as $width ) {
		$candidate = $base . '-' . $width . '.webp';
		if ( file_exists( get_template_directory() . '/assets/images/' . $candidate ) ) {
			$items[] = esc_url( get_template_directory_uri() . '/assets/images/' . $candidate ) . ' ' . $width . 'w';
		}
	}

	$full = $base . '.webp';
	if ( file_exists( get_template_directory() . '/assets/images/' . $full ) ) {
		$items[] = esc_url( get_template_directory_uri() . '/assets/images/' . $full ) . ' 1672w';
	}

	return implode( ', ', $items );
}

/**
 * Render a responsive theme image.
 *
 * @param string              $file  Asset file name.
 * @param array<string,mixed> $attrs Image attributes.
 * @return string
 */
function asset_image( string $file, array $attrs = array() ): string {
	$attrs = array_merge(
		array(
			'alt'      => '',
			'width'    => '1672',
			'height'   => '941',
			'loading'  => 'lazy',
			'decoding' => 'async',
			'sizes'    => '(max-width: 760px) calc(100vw - 32px), 480px',
		),
		$attrs
	);

	$attrs['src'] = asset_url( $file );
	$srcset       = asset_srcset( $file );
	if ( '' !== $srcset ) {
		$attrs['srcset'] = $srcset;
	}

	$output = '<img';
	foreach ( $attrs as $name => $value ) {
		if ( false === $value || null === $value || ( '' === $value && 'alt' !== $name ) ) {
			continue;
		}
		$output .= ' ' . esc_attr( (string) $name ) . '="' . esc_attr( (string) $value ) . '"';
	}
	$output .= '>';

	return $output;
}

/**
 * Return a category-aware fallback image file.
 *
 * @param int|null $post_id Post ID.
 * @return string
 */
function fallback_image_file( ?int $post_id = null ): string {
	if ( ! $post_id ) {
		$post_id = get_the_ID();
	}

	$slugs = wp_get_post_categories(
		$post_id,
		array(
			'fields' => 'slugs',
		)
	);
	if ( is_wp_error( $slugs ) ) {
		$slugs = array();
	}

	if ( in_array( 'critical-cves', $slugs, true ) ) {
		return 'lock-chip.png';
	}

	if ( in_array( 'linux-administration', $slugs, true ) || in_array( 'devops', $slugs, true ) ) {
		return 'linux-circuit.png';
	}

	if ( in_array( 'artificial-intelligence', $slugs, true ) ) {
		return 'data-center.png';
	}

	if ( in_array( 'cybersecurity', $slugs, true ) ) {
		return 'cloud-security.png';
	}

	return 'hero-shield.png';
}

/**
 * Return a category-aware fallback image URL.
 *
 * @param int|null $post_id Post ID.
 * @return string
 */
function fallback_image_url( ?int $post_id = null ): string {
	return asset_url( fallback_image_file( $post_id ) );
}

/**
 * Render a category-aware fallback image.
 *
 * @param int|null            $post_id Post ID.
 * @param array<string,mixed> $attrs   Image attributes.
 * @return string
 */
function fallback_image( ?int $post_id = null, array $attrs = array() ): string {
	return asset_image( fallback_image_file( $post_id ), $attrs );
}

/**
 * Render pagination.
 */
function pagination(): void {
	the_posts_pagination(
		array(
			'mid_size'           => 2,
			'prev_text'          => __( 'Previous', 'infosecnexus' ),
			'next_text'          => __( 'Next', 'infosecnexus' ),
			'screen_reader_text' => __( 'Posts navigation', 'infosecnexus' ),
		)
	);
}

/**
 * Render a post card.
 *
 * @param string $variant Card variant.
 * @param string $heading Heading level.
 */
function post_card( string $variant = 'grid', string $heading = 'h2' ): void {
	get_template_part(
		'template-parts/card/post-card',
		null,
		array(
			'post_id' => (int) get_the_ID(),
			'variant' => $variant,
			'heading' => in_array( $heading, array( 'h2', 'h3' ), true ) ? $heading : 'h2',
		)
	);
}

/**
 * Render featured video if configured.
 *
 * @param int|null $post_id Post ID.
 */
function featured_video( ?int $post_id = null ): void {
	if ( ! $post_id ) {
		$post_id = get_the_ID();
	}
	$url = (string) get_post_meta( $post_id, '_infosecnexus_featured_video_url', true );
	if ( ! $url ) {
		return;
	}

	$embed = wp_oembed_get( esc_url_raw( $url ) );
	if ( $embed ) {
		echo '<div class="featured-video">' . wp_kses_post( $embed ) . '</div>';
	}
}

/**
 * Render native table of contents placeholder from post headings.
 *
 * @param string $content Post content.
 */
function table_of_contents( string $content = '' ): void {
	if ( '' === $content ) {
		$content = (string) get_post_field( 'post_content', get_the_ID() );
	}
	$content = \InfoSecNexus\Theme\Anime_Design\without_seeded_page_hero( $content );
	$content = apply_filters( 'the_content', $content );
	$outline = \InfoSecNexus\Theme\Anime_Design\heading_outline( $content );
	if ( empty( $outline ) ) {
		return;
	}

	echo '<nav class="toc" aria-label="' . esc_attr__( 'Table of contents', 'infosecnexus' ) . '"><span class="toc__eyebrow">' . esc_html__( 'Article guide', 'infosecnexus' ) . '</span><h2>' . esc_html__( 'On this page', 'infosecnexus' ) . '</h2><ol>';
	foreach ( $outline as $heading ) {
		echo '<li><a href="#' . esc_attr( $heading['id'] ) . '">' . esc_html( $heading['label'] ) . '</a></li>';
	}
	echo '</ol></nav>';
}

/**
 * Render related posts by first category.
 */
function related_posts(): void {
	if ( function_exists( 'infosecnexus_toolkit_render_related_posts' ) ) {
		infosecnexus_toolkit_render_related_posts();
		return;
	}

	$categories = wp_get_post_categories( get_the_ID() );
	if ( is_wp_error( $categories ) || empty( $categories ) ) {
		return;
	}

	$query = new \WP_Query(
		array(
			'category__in'        => array( (int) $categories[0] ),
			'post__not_in'        => array( get_the_ID() ),
			'posts_per_page'      => 3,
			'ignore_sticky_posts' => true,
		)
	);

	if ( ! $query->have_posts() ) {
		return;
	}

	echo '<section class="related-posts"><h2>' . esc_html__( 'Related briefings', 'infosecnexus' ) . '</h2><div class="post-grid post-grid--3">';
	while ( $query->have_posts() ) {
		$query->the_post();
		post_card( 'compact', 'h3' );
	}
	wp_reset_postdata();
	echo '</div></section>';
}

/**
 * Render social share links without remote scripts.
 */
function social_share(): void {
	$url   = rawurlencode( get_permalink() );
	$title = rawurlencode( get_the_title() );
	echo '<nav class="share-links" aria-label="' . esc_attr__( 'Share this post', 'infosecnexus' ) . '"><span class="share-links__eyebrow">' . esc_html__( 'Share briefing', 'infosecnexus' ) . '</span><div class="share-links__items">';
	echo '<a href="' . esc_url( 'https://www.linkedin.com/shareArticle?mini=true&url=' . $url . '&title=' . $title ) . '" rel="noopener noreferrer" target="_blank"><span>in</span><b>' . esc_html__( 'LinkedIn', 'infosecnexus' ) . '</b></a>';
	echo '<a href="' . esc_url( 'https://x.com/intent/tweet?url=' . $url . '&text=' . $title ) . '" rel="noopener noreferrer" target="_blank"><span>X</span><b>' . esc_html__( 'Post', 'infosecnexus' ) . '</b></a>';
	echo '<a href="mailto:?subject=' . esc_attr( get_the_title() ) . '&body=' . esc_url( get_permalink() ) . '"><span>@</span><b>' . esc_html__( 'Email', 'infosecnexus' ) . '</b></a>';
	echo '</div>';
	echo '</nav>';
}

/**
 * Render a contact callout instead of a comment form.
 */
function contact_cta(): void {
	$page = get_page_by_path( 'contact' );
	$url  = $page ? get_permalink( $page ) : home_url( '/contact/' );

	echo '<section class="post-contact-cta">';
	echo '<div><span class="post-contact-cta__eyebrow">' . esc_html__( 'Corrections and tips', 'infosecnexus' ) . '</span>';
	echo '<h2>' . esc_html__( 'Need to add context to this briefing?', 'infosecnexus' ) . '</h2>';
	echo '<p>' . esc_html__( 'Send corrections, security tips, source updates, or collaboration notes through the contact page so the editorial team can review them properly.', 'infosecnexus' ) . '</p></div>';
	echo '<a class="button" href="' . esc_url( $url ) . '">' . esc_html__( 'Contact InfoSecNexus', 'infosecnexus' ) . '</a>';
	echo '</section>';
}
