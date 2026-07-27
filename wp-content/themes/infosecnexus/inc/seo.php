<?php
/**
 * Lightweight SEO metadata for sites without a dedicated SEO plugin.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

namespace InfoSecNexus\Theme\SEO;

/**
 * Register frontend metadata.
 */
function bootstrap(): void {
	add_action( 'wp_head', __NAMESPACE__ . '\\render_metadata', 4 );
}

/**
 * Print article metadata when another SEO plugin is not responsible for it.
 */
function render_metadata(): void {
	if ( is_admin() || ! is_singular() || seo_plugin_active() ) {
		return;
	}

	$post = get_post();
	if ( ! $post ) {
		return;
	}

	$title       = wp_get_document_title();
	$description = description( (int) $post->ID );
	$url         = get_permalink( $post );
	$image       = image_url( (int) $post->ID );
	$type        = 'post' === $post->post_type ? 'article' : 'website';

	echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
	echo '<meta property="og:type" content="' . esc_attr( $type ) . '">' . "\n";
	echo '<meta property="og:title" content="' . esc_attr( $title ) . '">' . "\n";
	echo '<meta property="og:description" content="' . esc_attr( $description ) . '">' . "\n";
	echo '<meta property="og:url" content="' . esc_url( $url ) . '">' . "\n";
	echo '<meta property="og:site_name" content="' . esc_attr( get_bloginfo( 'name' ) ) . '">' . "\n";
	echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
	echo '<meta name="twitter:title" content="' . esc_attr( $title ) . '">' . "\n";
	echo '<meta name="twitter:description" content="' . esc_attr( $description ) . '">' . "\n";

	if ( '' !== $image ) {
		echo '<meta property="og:image" content="' . esc_url( $image ) . '">' . "\n";
		echo '<meta name="twitter:image" content="' . esc_url( $image ) . '">' . "\n";
	}

	if ( 'post' !== $post->post_type ) {
		return;
	}

	$schema = array(
		'@context'         => 'https://schema.org',
		'@type'            => 'NewsArticle',
		'headline'         => get_the_title( $post ),
		'description'      => $description,
		'datePublished'    => get_post_time( 'c', true, $post ),
		'dateModified'     => get_post_modified_time( 'c', true, $post ),
		'mainEntityOfPage' => $url,
		'author'           => array(
			'@type' => 'Organization',
			'name'  => get_bloginfo( 'name' ),
			'url'   => home_url( '/' ),
		),
		'publisher'        => array(
			'@type' => 'Organization',
			'name'  => get_bloginfo( 'name' ),
			'url'   => home_url( '/' ),
		),
	);

	if ( '' !== $image ) {
		$schema['image'] = array( $image );
	}

	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
}

/**
 * Whether a common SEO plugin is already active.
 */
function seo_plugin_active(): bool {
	return defined( 'WPSEO_VERSION' )
		|| defined( 'RANK_MATH_VERSION' )
		|| defined( 'SEOPRESS_VERSION' )
		|| defined( 'AIOSEO_VERSION' );
}

/**
 * Return a concise page description.
 *
 * @param int $post_id Post ID.
 */
function description( int $post_id ): string {
	$value = (string) get_post_meta( $post_id, '_infosecnexus_seo_description', true );
	if ( '' === trim( $value ) ) {
		$value = (string) get_the_excerpt( $post_id );
	}
	if ( '' === trim( $value ) ) {
		$value = (string) get_post_field( 'post_content', $post_id );
	}

	$value = wp_strip_all_tags( strip_shortcodes( $value ), true );
	$value = preg_replace( '/\s+/u', ' ', $value );
	$value = is_string( $value ) ? trim( $value ) : '';

	return wp_html_excerpt( $value, 158, '...' );
}

/**
 * Return the featured or category fallback image URL.
 *
 * @param int $post_id Post ID.
 */
function image_url( int $post_id ): string {
	$image = (string) get_the_post_thumbnail_url( $post_id, 'large' );
	if ( '' !== $image ) {
		return $image;
	}

	if ( function_exists( '\InfoSecNexus\Theme\Template_Tags\fallback_image_url' ) ) {
		return \InfoSecNexus\Theme\Template_Tags\fallback_image_url( $post_id );
	}

	return '';
}
