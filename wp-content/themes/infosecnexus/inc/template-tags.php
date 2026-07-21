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
 * Render post meta.
 */
function post_meta(): void {
	echo '<div class="entry-meta">';
	echo '<span>' . esc_html( get_the_date() ) . '</span>';
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
	return get_template_directory_uri() . '/assets/images/' . $file;
}

/**
 * Return a category-aware fallback image URL.
 *
 * @param int|null $post_id Post ID.
 * @return string
 */
function fallback_image_url( ?int $post_id = null ): string {
	if ( ! $post_id ) {
		$post_id = get_the_ID();
	}

	$slugs = wp_get_post_categories(
		$post_id,
		array(
			'fields' => 'slugs',
		)
	);

	if ( in_array( 'critical-cves', $slugs, true ) ) {
		return asset_url( 'lock-chip.png' );
	}

	if ( in_array( 'linux-administration', $slugs, true ) || in_array( 'devops', $slugs, true ) ) {
		return asset_url( 'linux-circuit.png' );
	}

	if ( in_array( 'artificial-intelligence', $slugs, true ) ) {
		return asset_url( 'data-center.png' );
	}

	if ( in_array( 'cybersecurity', $slugs, true ) ) {
		return asset_url( 'cloud-security.png' );
	}

	return asset_url( 'hero-shield.png' );
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
 */
function post_card( string $variant = 'grid' ): void {
	$image_url = fallback_image_url();
	?>
	<article id="post-<?php the_ID(); ?>" <?php post_class( 'post-card post-card--' . sanitize_html_class( $variant ) ); ?>>
		<a class="post-card__image" href="<?php the_permalink(); ?>" aria-label="<?php the_title_attribute(); ?>">
			<?php if ( has_post_thumbnail() ) : ?>
				<?php the_post_thumbnail( 'large', array( 'loading' => 'lazy' ) ); ?>
			<?php else : ?>
				<img src="<?php echo esc_url( $image_url ); ?>" alt="" loading="lazy">
			<?php endif; ?>
		</a>
		<div class="post-card__body">
			<?php category_badges(); ?>
			<h2 class="post-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
			<?php post_meta(); ?>
			<div class="post-card__excerpt"><?php the_excerpt(); ?></div>
		</div>
	</article>
	<?php
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
 */
function table_of_contents(): void {
	$content = get_post_field( 'post_content', get_the_ID() );
	if ( ! preg_match_all( '/<h([2-3])[^>]*>(.*?)<\/h[2-3]>/', (string) $content, $matches, PREG_SET_ORDER ) ) {
		return;
	}

	echo '<nav class="toc" aria-label="' . esc_attr__( 'Table of contents', 'infosecnexus' ) . '"><span class="toc__eyebrow">' . esc_html__( 'Article guide', 'infosecnexus' ) . '</span><h2>' . esc_html__( 'On this page', 'infosecnexus' ) . '</h2><ol>';
	foreach ( $matches as $index => $match ) {
		$label = wp_strip_all_tags( $match[2] );
		$id    = 'section-' . ( $index + 1 );
		echo '<li><a href="#' . esc_attr( $id ) . '">' . esc_html( $label ) . '</a></li>';
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
	if ( empty( $categories ) ) {
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
		post_card( 'compact' );
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
