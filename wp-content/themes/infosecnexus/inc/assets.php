<?php
/**
 * Theme assets.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

namespace InfoSecNexus\Theme\Assets;

use function InfoSecNexus\Theme\Customizer\custom_properties;
use function InfoSecNexus\Theme\Customizer\get_value;

/**
 * Register hooks.
 */
function bootstrap(): void {
	add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue' );
	add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\dequeue_unused_front_page_styles', 100 );
	add_action( 'enqueue_block_editor_assets', __NAMESPACE__ . '\\enqueue_editor' );
	add_action( 'wp_head', __NAMESPACE__ . '\\preload_critical_image', 1 );
}

/**
 * Enqueue frontend assets.
 */
function enqueue(): void {
	wp_enqueue_style( 'infosecnexus-style', get_template_directory_uri() . '/assets/css/theme.css', array(), INFOSECNEXUS_VERSION );
	wp_add_inline_style( 'infosecnexus-style', custom_properties() );
	wp_enqueue_style( 'infosecnexus-anime-editorial', get_template_directory_uri() . '/assets/css/anime-editorial.css', array( 'infosecnexus-style' ), INFOSECNEXUS_VERSION );

	wp_enqueue_script( 'infosecnexus-theme', get_template_directory_uri() . '/assets/js/theme.js', array(), INFOSECNEXUS_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
	wp_localize_script(
		'infosecnexus-theme',
		'infosecnexusTheme',
		array(
			'defaultColorMode' => get_value( 'default_color_mode' ),
			'scrollTop'        => (bool) get_value( 'enable_scroll_top' ),
			'homeUrl'          => esc_url_raw( home_url( '/' ) ),
		)
	);
}

/**
 * The custom homepage contains no Gutenberg blocks, so core block CSS is waste.
 */
function dequeue_unused_front_page_styles(): void {
	if ( ! is_front_page() ) {
		return;
	}

	wp_dequeue_style( 'wp-block-library' );
	wp_dequeue_style( 'wp-block-library-theme' );
	wp_dequeue_style( 'classic-theme-styles' );
	wp_dequeue_style( 'global-styles' );
}

/**
 * Preload the image most likely to become the LCP element.
 */
function preload_critical_image(): void {
	if ( is_admin() ) {
		return;
	}

	$href   = '';
	$srcset = '';
	$sizes  = '';

	if ( is_front_page() ) {
		$rolling = get_page_by_path( 'live-cybersecurity-brief', OBJECT, 'post' );
		$data    = $rolling instanceof \WP_Post && \InfoSecNexus\Theme\Anime_Design\manual_featured_image_id( (int) $rolling->ID ) > 0
			? \InfoSecNexus\Theme\Anime_Design\post_image_data( (int) $rolling->ID )
			: array(
				'url'    => \InfoSecNexus\Theme\Anime_Design\asset_url( 'hero' ),
				'srcset' => \InfoSecNexus\Theme\Anime_Design\asset_url( 'hero', 640 ) . ' 640w, ' . \InfoSecNexus\Theme\Anime_Design\asset_url( 'hero' ) . ' 1280w',
			);
		$href   = (string) $data['url'];
		$srcset = (string) $data['srcset'];
		$sizes  = '(max-width: 760px) calc(100vw - 32px), (max-width: 1180px) 64vw, 860px';
	} elseif ( is_singular( 'post' ) ) {
		$post_id = get_queried_object_id();
		$data    = \InfoSecNexus\Theme\Anime_Design\post_image_data( $post_id );
		$href    = (string) $data['url'];
		$srcset  = (string) $data['srcset'];
		$sizes   = '(max-width: 1000px) calc(100vw - 32px), 920px';
	}

	if ( '' === $href ) {
		return;
	}

	echo '<link rel="preload" as="image" href="' . esc_url( $href ) . '"';
	if ( '' !== $srcset ) {
		echo ' imagesrcset="' . esc_attr( $srcset ) . '"';
	}
	if ( '' !== $sizes ) {
		echo ' imagesizes="' . esc_attr( $sizes ) . '"';
	}
	echo ' fetchpriority="high">' . "\n";
}

/**
 * Enqueue block editor styles.
 */
function enqueue_editor(): void {
	wp_enqueue_style( 'infosecnexus-editor-style', get_template_directory_uri() . '/assets/css/editor.css', array(), INFOSECNEXUS_VERSION );
}
