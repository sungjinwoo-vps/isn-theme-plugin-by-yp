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
	add_filter( 'wp_robots', __NAMESPACE__ . '\\noindex_tag_archives' );
	add_filter( 'wp_sitemaps_taxonomies', __NAMESPACE__ . '\\exclude_post_tags_from_sitemaps' );
	add_filter( 'pre_get_document_title', __NAMESPACE__ . '\\document_title' );
	add_filter( 'the_generator', '__return_empty_string' );
	remove_action( 'wp_head', 'wp_generator' );
}

/**
 * Keep keyword tag archives from creating low-value indexable pages.
 *
 * @param array<string,mixed> $robots Existing robots directives.
 * @return array<string,mixed>
 */
function noindex_tag_archives( array $robots ): array {
	if ( is_tag() || ( is_search() && '' === trim( get_search_query() ) ) ) {
		$robots['noindex'] = true;
		$robots['follow']  = true;
		unset( $robots['index'], $robots['nofollow'] );
	}

	return $robots;
}

/**
 * Give the homepage a descriptive search title without changing the visible brand.
 *
 * @param string $title Existing document title.
 */
function document_title( string $title ): string {
	if ( ! is_front_page() || seo_plugin_active() ) {
		return $title;
	}

	return __( 'Cybersecurity News, Critical CVEs & Threat Intelligence | InfoSecNexus', 'infosecnexus' );
}

/**
 * Exclude post tags from core XML sitemaps while keeping category links crawlable.
 *
 * @param array<string,\WP_Taxonomy> $taxonomies Public sitemap taxonomies.
 * @return array<string,\WP_Taxonomy>
 */
function exclude_post_tags_from_sitemaps( array $taxonomies ): array {
	unset( $taxonomies['post_tag'] );
	return $taxonomies;
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

	render_site_schema();
	render_breadcrumb_schema();

	if ( ! $post instanceof \WP_Post || 'post' !== $post->post_type ) {
		return;
	}

	$author     = get_userdata( (int) $post->post_author );
	$keywords   = post_keywords( (int) $post->ID );
	$categories = wp_get_post_terms( (int) $post->ID, 'category', array( 'fields' => 'names' ) );

	$schema = array(
		'@context'         => 'https://schema.org',
		'@type'            => 'NewsArticle',
		'headline'         => get_the_title( $post ),
		'description'      => $description,
		'datePublished'    => get_post_time( 'c', true, $post ),
		'dateModified'     => get_post_modified_time( 'c', true, $post ),
		'mainEntityOfPage' => array(
			'@type' => 'WebPage',
			'@id'   => $url,
		),
		'author'           => array(
			'@type' => 'Person',
			'name'  => $author instanceof \WP_User ? $author->display_name : get_bloginfo( 'name' ),
			'url'   => $author instanceof \WP_User ? get_author_posts_url( (int) $author->ID ) : home_url( '/' ),
		),
		'publisher'        => array(
			'@id' => home_url( '/#organization' ),
		),
	);

	if ( ! empty( $keywords ) ) {
		$schema['keywords'] = $keywords;
	}
	if ( ! is_wp_error( $categories ) && ! empty( $categories ) ) {
		$schema['articleSection'] = array_values( $categories );
	}

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
	$paged = max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );
	if ( $paged > 1 && ( is_archive() || is_home() || is_search() || is_page_template( 'page-blog.php' ) ) ) {
		return (string) get_pagenum_link( $paged );
	}

	if ( is_singular() ) {
		return (string) get_permalink();
	}
	if ( is_search() ) {
		if ( '' === trim( get_search_query() ) ) {
			return home_url( '/' );
		}
		return (string) get_search_link( get_search_query() );
	}

	$request_path = (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '/' ), PHP_URL_PATH );
	return home_url( '/' . ltrim( $request_path, '/' ) );
}

/**
 * Return the focused keyword list stored for an article.
 *
 * @param int $post_id Post ID.
 * @return array<int,string>
 */
function post_keywords( int $post_id ): array {
	$raw      = (string) get_post_meta( $post_id, '_infosecnexus_seo_keywords', true );
	$keywords = preg_split( '/\s*,\s*/u', $raw, -1, PREG_SPLIT_NO_EMPTY );
	$keywords = is_array( $keywords ) ? array_map( 'sanitize_text_field', $keywords ) : array();

	if ( empty( $keywords ) ) {
		$keywords = wp_get_post_tags( $post_id, array( 'fields' => 'names' ) );
	}

	return is_array( $keywords ) ? array_values( array_unique( array_filter( $keywords ) ) ) : array();
}

/**
 * Print the site and publisher entities used by homepage and article schema.
 */
function render_site_schema(): void {
	$organization = array(
		'@type' => 'Organization',
		'@id'   => home_url( '/#organization' ),
		'name'  => get_bloginfo( 'name' ),
		'url'   => home_url( '/' ),
	);
	$logo_id      = (int) get_theme_mod( 'custom_logo' );
	$logo         = $logo_id > 0 ? wp_get_attachment_image_src( $logo_id, 'full' ) : false;

	if ( is_array( $logo ) ) {
		$organization['logo'] = array(
			'@type'  => 'ImageObject',
			'url'    => (string) $logo[0],
			'width'  => (int) $logo[1],
			'height' => (int) $logo[2],
		);
	}

	$website = array(
		'@type'       => 'WebSite',
		'@id'         => home_url( '/#website' ),
		'url'         => home_url( '/' ),
		'name'        => get_bloginfo( 'name' ),
		'description' => current_description(),
		'publisher'   => array( '@id' => home_url( '/#organization' ) ),
		'potentialAction' => array(
			'@type'       => 'SearchAction',
			'target'      => array(
				'@type'       => 'EntryPoint',
				'urlTemplate' => home_url( '/?s={search_term_string}' ),
			),
			'query-input' => 'required name=search_term_string',
		),
	);

	$schema = array(
		'@context' => 'https://schema.org',
		'@graph'   => array( $organization, $website ),
	);

	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
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
