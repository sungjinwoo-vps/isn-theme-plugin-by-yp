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
 * Print public metadata when another SEO plugin is not responsible for it.
 */
function render_metadata(): void {
	if ( is_admin() || is_feed() || seo_plugin_active() ) {
		return;
	}

	$post        = is_singular() ? get_post() : null;
	$title       = wp_get_document_title();
	$description = current_description( $post instanceof \WP_Post ? (int) $post->ID : 0 );
	$url         = current_url();
	$image       = $post instanceof \WP_Post ? image_url( (int) $post->ID ) : default_image_url();
	$type        = $post instanceof \WP_Post && 'post' === $post->post_type ? 'article' : 'website';

	if ( '' === $description ) {
		return;
	}

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

	if ( ! $post instanceof \WP_Post || 'post' !== $post->post_type ) {
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
 * Return metadata text for the current public request.
 *
 * @param int $post_id Singular post ID, or zero.
 */
function current_description( int $post_id = 0 ): string {
	if ( $post_id > 0 ) {
		return description( $post_id );
	}

	if ( is_category() || is_tag() || is_tax() ) {
		$term = get_queried_object();
		if ( $term instanceof \WP_Term ) {
			$value = wp_strip_all_tags( term_description( $term->term_id ), true );
			if ( '' !== trim( $value ) ) {
				return concise_text( $value );
			}

			return concise_text(
				sprintf(
					/* translators: %s: archive name. */
					__( 'Latest %s cybersecurity briefings, practical analysis, risk context, and defensive actions from InfoSecNexus.', 'infosecnexus' ),
					$term->name
				)
			);
		}
	}

	if ( is_search() ) {
		return concise_text(
			sprintf(
				/* translators: %s: search query. */
				__( 'InfoSecNexus cybersecurity articles matching %s, including vulnerability analysis, operational guidance, and security updates.', 'infosecnexus' ),
				get_search_query()
			)
		);
	}

	if ( is_404() ) {
		return __( 'The requested InfoSecNexus page could not be found. Search the latest cybersecurity briefings, vulnerability analysis, and defensive guidance.', 'infosecnexus' );
	}

	$tagline = trim( (string) get_bloginfo( 'description' ) );
	if ( strlen( $tagline ) >= 70 ) {
		return concise_text( $tagline );
	}

	return __( 'InfoSecNexus publishes practical cybersecurity briefings, critical CVE analysis, Linux and DevOps updates, AI security news, and defensive guidance.', 'infosecnexus' );
}

/**
 * Return the canonical public URL for the current request.
 */
function current_url(): string {
	if ( is_singular() ) {
		return (string) get_permalink();
	}

	$request_path = (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '/' ), PHP_URL_PATH );
	return home_url( '/' . ltrim( $request_path, '/' ) );
}

/**
 * Normalize metadata text to a useful search snippet.
 *
 * @param string $value Raw description.
 */
function concise_text( string $value ): string {
	$value = wp_strip_all_tags( strip_shortcodes( $value ), true );
	$value = preg_replace( '/\s+/u', ' ', $value );
	$value = is_string( $value ) ? trim( $value ) : '';
	return wp_html_excerpt( $value, 158, '...' );
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

	return concise_text( $value );
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

/**
 * Return a reliable social preview for non-singular requests.
 */
function default_image_url(): string {
	if ( function_exists( '\InfoSecNexus\Theme\Template_Tags\asset_url' ) ) {
		return \InfoSecNexus\Theme\Template_Tags\asset_url( 'hero-shield.png' );
	}

	return '';
}
