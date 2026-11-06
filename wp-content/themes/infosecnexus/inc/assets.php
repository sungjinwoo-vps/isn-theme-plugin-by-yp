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
	add_action( 'wp_head', __NAMESPACE__ . '\\preload_editorial_font', 1 );
}

/** Start the small self-hosted variable font before CSS discovery. */
function preload_editorial_font(): void {
	if ( ! is_admin() ) {
		echo '<link rel="preload" href="' . esc_url( get_template_directory_uri() . '/assets/fonts/nunito-sans-latin.woff2' ) . '" as="font" type="font/woff2" crossorigin>' . "\n";
	}
}

/**
 * Enqueue frontend assets.
 */
function enqueue(): void {
	wp_enqueue_style( 'infosecnexus-style', get_template_directory_uri() . '/assets/css/theme.css', array(), INFOSECNEXUS_VERSION );
	wp_add_inline_style( 'infosecnexus-style', custom_properties() );
	wp_enqueue_style( 'infosecnexus-anime-editorial', get_template_directory_uri() . '/assets/css/anime-editorial.css', array( 'infosecnexus-style' ), INFOSECNEXUS_VERSION );
	wp_enqueue_style( 'infosecnexus-neumorphic-editorial', get_template_directory_uri() . '/assets/css/neumorphic-editorial.css', array( 'infosecnexus-anime-editorial' ), INFOSECNEXUS_VERSION );

	wp_enqueue_script( 'infosecnexus-theme', get_template_directory_uri() . '/assets/js/theme.js', array(), INFOSECNEXUS_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	if ( is_singular( 'post' ) ) {
		wp_enqueue_style( 'infosecnexus-article-content', get_template_directory_uri() . '/assets/css/article-content.css', array( 'infosecnexus-neumorphic-editorial' ), INFOSECNEXUS_VERSION );
		wp_enqueue_script( 'infosecnexus-article-content', get_template_directory_uri() . '/assets/js/article-content.js', array( 'infosecnexus-theme' ), INFOSECNEXUS_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_localize_script(
			'infosecnexus-article-content',
			'infosecnexusArticle',
			array(
				'copy'       => __( 'Copy code', 'infosecnexus' ),
				'copied'     => __( 'Copied', 'infosecnexus' ),
				'copyFailed' => __( 'Select the code and copy it manually.', 'infosecnexus' ),
				'table'      => __( 'Article table: scroll horizontally to see all columns', 'infosecnexus' ),
			)
		);
	}
	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
	$cinematic_dependencies = array( 'infosecnexus-neumorphic-editorial' );
	if ( is_singular( 'post' ) ) {
		$cinematic_dependencies[] = 'infosecnexus-article-content';
	}
	wp_enqueue_style( 'infosecnexus-cinematic-glass', get_template_directory_uri() . '/assets/css/cinematic-glass.css', $cinematic_dependencies, (string) filemtime( get_template_directory() . '/assets/css/cinematic-glass.css' ) );
	wp_enqueue_style( 'infosecnexus-cinematic-pages', get_template_directory_uri() . '/assets/css/cinematic-pages.css', array( 'infosecnexus-cinematic-glass' ), (string) filemtime( get_template_directory() . '/assets/css/cinematic-pages.css' ) );
	wp_enqueue_style( 'infosecnexus-ivory-home', get_template_directory_uri() . '/assets/css/ivory-home.css', array( 'infosecnexus-cinematic-pages' ), (string) filemtime( get_template_directory() . '/assets/css/ivory-home.css' ) );
	wp_enqueue_style( 'infosecnexus-ivory-pages', get_template_directory_uri() . '/assets/css/ivory-pages.css', array( 'infosecnexus-ivory-home' ), (string) filemtime( get_template_directory() . '/assets/css/ivory-pages.css' ) );
	wp_enqueue_style( 'infosecnexus-ivory-preferences', get_template_directory_uri() . '/assets/css/ivory-preferences.css', array( 'infosecnexus-ivory-pages' ), INFOSECNEXUS_VERSION );
	wp_enqueue_script( 'infosecnexus-cinematic-glass', get_template_directory_uri() . '/assets/js/cinematic-glass.js', array( 'infosecnexus-theme' ), (string) filemtime( get_template_directory() . '/assets/js/cinematic-glass.js' ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
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
		$data = array(
			'url' => \InfoSecNexus\Theme\Cinematic_Media\asset_url( 'hero', 1600 ),
			'srcset' => \InfoSecNexus\Theme\Cinematic_Media\asset_url( 'hero', 640 ) . ' 640w, ' . \InfoSecNexus\Theme\Cinematic_Media\asset_url( 'hero', 800 ) . ' 800w, ' . \InfoSecNexus\Theme\Cinematic_Media\asset_url( 'hero', 1280 ) . ' 1280w, ' . \InfoSecNexus\Theme\Cinematic_Media\asset_url( 'hero', 1600 ) . ' 1600w',
		);
		$href   = (string) $data['url'];
		$srcset = (string) $data['srcset'];
		$sizes  = '(max-width: 600px) 600px, 1600px';
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
