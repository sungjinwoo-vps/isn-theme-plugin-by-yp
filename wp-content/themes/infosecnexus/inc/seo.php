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
	$image_data  = $post instanceof \WP_Post ? image_data( (int) $post->ID ) : default_image_data();
	$image       = $image_data['url'];
	$type        = $post instanceof \WP_Post && 'post' === $post->post_type ? 'article' : 'website';

	if ( '' === $description ) {
		return;
	}

	echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
	if ( ! is_singular() && ! is_404() ) {
		echo '<link rel="canonical" href="' . esc_url( $url ) . '">' . "\n";
	}
	echo '<meta property="og:type" content="' . esc_attr( $type ) . '">' . "\n";
	echo '<meta property="og:title" content="' . esc_attr( $title ) . '">' . "\n";
	echo '<meta property="og:description" content="' . esc_attr( $description ) . '">' . "\n";
	echo '<meta property="og:url" content="' . esc_url( $url ) . '">' . "\n";
	echo '<meta property="og:site_name" content="' . esc_attr( get_bloginfo( 'name' ) ) . '">' . "\n";
	if ( $post instanceof \WP_Post && 'post' === $post->post_type ) {
		echo '<meta property="article:published_time" content="' . esc_attr( get_post_time( 'c', true, $post ) ) . '">' . "\n";
		echo '<meta property="article:modified_time" content="' . esc_attr( get_post_modified_time( 'c', true, $post ) ) . '">' . "\n";
	}
	echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
	echo '<meta name="twitter:title" content="' . esc_attr( $title ) . '">' . "\n";
	echo '<meta name="twitter:description" content="' . esc_attr( $description ) . '">' . "\n";

	if ( '' !== $image ) {
		echo '<meta property="og:image" content="' . esc_url( $image ) . '">' . "\n";
		echo '<meta property="og:image:width" content="' . esc_attr( (string) $image_data['width'] ) . '">' . "\n";
		echo '<meta property="og:image:height" content="' . esc_attr( (string) $image_data['height'] ) . '">' . "\n";
		echo '<meta property="og:image:alt" content="' . esc_attr( $image_data['alt'] ) . '">' . "\n";
		echo '<meta name="twitter:image" content="' . esc_url( $image ) . '">' . "\n";
		echo '<meta name="twitter:image:alt" content="' . esc_attr( $image_data['alt'] ) . '">' . "\n";
	}

	render_breadcrumb_schema();

	if ( ! $post instanceof \WP_Post || 'post' !== $post->post_type ) {
		return;
	}

	$author = get_userdata( (int) $post->post_author );

	$schema = array(
		'@context'         => 'https://schema.org',
		'@type'            => 'NewsArticle',
		'headline'         => get_the_title( $post ),
		'description'      => $description,
		'datePublished'    => get_post_time( 'c', true, $post ),
		'dateModified'     => get_post_modified_time( 'c', true, $post ),
		'mainEntityOfPage' => $url,
		'author'           => array(
			'@type' => 'Person',
			'name'  => $author instanceof \WP_User ? $author->display_name : get_bloginfo( 'name' ),
			'url'   => $author instanceof \WP_User ? get_author_posts_url( (int) $author->ID ) : home_url( '/' ),
		),
		'publisher'        => array(
			'@type' => 'Organization',
			'name'  => get_bloginfo( 'name' ),
			'url'   => home_url( '/' ),
		),
	);

	if ( '' !== $image ) {
		$schema['image'] = array(
			'@type'  => 'ImageObject',
			'url'    => $image,
			'width'  => $image_data['width'],
			'height' => $image_data['height'],
		);
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
	if ( is_search() ) {
		return (string) get_search_link( get_search_query() );
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
	return image_data( $post_id )['url'];
}

/**
 * Return complete social-image data for a post.
 *
 * @param int $post_id Post ID.
 * @return array{url:string,width:int,height:int,alt:string}
 */
function image_data( int $post_id ): array {
	if ( function_exists( 'InfoSecNexus\\Theme\\Anime_Design\\post_image_data' ) ) {
		$data = \InfoSecNexus\Theme\Anime_Design\post_image_data( $post_id );
		return array(
			'url'    => (string) $data['url'],
			'width'  => (int) $data['width'],
			'height' => (int) $data['height'],
			'alt'    => (string) $data['alt'],
		);
	}

	$attachment_id = (int) get_post_thumbnail_id( $post_id );
	$source        = $attachment_id > 0 ? wp_get_attachment_image_src( $attachment_id, 'full' ) : false;
	$url           = is_array( $source ) ? (string) $source[0] : '';

	return array(
		'url'    => $url,
		'width'  => is_array( $source ) ? (int) $source[1] : 1280,
		'height' => is_array( $source ) ? (int) $source[2] : 720,
		'alt'    => '' !== $url ? (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) : get_the_title( $post_id ),
	);
}

/**
 * Return a reliable social preview for non-singular requests.
 */
function default_image_url(): string {
	return default_image_data()['url'];
}

/**
 * Return the default social preview and intrinsic dimensions.
 *
 * @return array{url:string,width:int,height:int,alt:string}
 */
function default_image_data(): array {
	if ( function_exists( 'InfoSecNexus\\Theme\\Anime_Design\\asset_url' ) ) {
		return array(
			'url'    => \InfoSecNexus\Theme\Anime_Design\asset_url( 'hero' ),
			'width'  => 1280,
			'height' => 720,
			'alt'    => \InfoSecNexus\Theme\Anime_Design\asset_alt( 'hero' ),
		);
	}

	return array(
		'url'    => '',
		'width'  => 1280,
		'height' => 720,
		'alt'    => get_bloginfo( 'name' ),
	);
}

/**
 * Print a BreadcrumbList matching the theme's visible breadcrumb trail.
 */
function render_breadcrumb_schema(): void {
	if ( is_front_page() || is_404() ) {
		return;
	}

	$items = array(
		array(
			'@type'    => 'ListItem',
			'position' => 1,
			'name'     => __( 'Home', 'infosecnexus' ),
			'item'     => home_url( '/' ),
		),
	);
	$position = 2;

	if ( is_singular() ) {
		$post = get_post();
		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		if ( $post instanceof \WP_Post && 'post' === $post->post_type ) {
			$categories = get_the_category( $post->ID );
			if ( ! empty( $categories ) ) {
				$category_link = get_category_link( $categories[0] );
				if ( ! is_wp_error( $category_link ) ) {
					$items[] = array(
						'@type'    => 'ListItem',
						'position' => $position++,
						'name'     => $categories[0]->name,
						'item'     => $category_link,
					);
				}
			}
		} elseif ( 'page' === $post->post_type ) {
			foreach ( array_reverse( get_post_ancestors( $post ) ) as $ancestor_id ) {
				$items[] = array(
					'@type'    => 'ListItem',
					'position' => $position++,
					'name'     => get_the_title( $ancestor_id ),
					'item'     => get_permalink( $ancestor_id ),
				);
			}
		}
		$items[] = array(
			'@type'    => 'ListItem',
			'position' => $position,
			'name'     => get_the_title( $post ),
			'item'     => get_permalink( $post ),
		);
	} else {
		$label = is_search()
			? sprintf( __( 'Search results for %s', 'infosecnexus' ), get_search_query() )
			: \InfoSecNexus\Theme\Breadcrumbs\archive_label();
		$items[] = array(
			'@type'    => 'ListItem',
			'position' => $position,
			'name'     => $label,
			'item'     => current_url(),
		);
	}

	$schema = array(
		'@context'        => 'https://schema.org',
		'@type'           => 'BreadcrumbList',
		'itemListElement' => $items,
	);

	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
}
