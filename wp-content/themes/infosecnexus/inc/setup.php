<?php
/**
 * Theme setup.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

namespace InfoSecNexus\Theme\Setup;

/**
 * Register WordPress hooks.
 */
function bootstrap(): void {
	add_action( 'after_setup_theme', __NAMESPACE__ . '\\setup_theme' );
	add_action( 'init', __NAMESPACE__ . '\\register_block_enhancements' );
	add_action( 'widgets_init', __NAMESPACE__ . '\\register_widget_areas' );
	add_filter( 'body_class', __NAMESPACE__ . '\\body_classes' );
	add_filter( 'excerpt_more', __NAMESPACE__ . '\\excerpt_more' );
}

/**
 * Configure theme support.
 */
function setup_theme(): void {
	load_theme_textdomain( 'infosecnexus', get_template_directory() . '/languages' );

	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'custom-line-height' );
	add_theme_support( 'custom-spacing' );
	add_theme_support( 'custom-units', array( 'px', 'rem', 'em', '%', 'vw', 'vh' ) );
	add_theme_support(
		'html5',
		array(
			'caption',
			'comment-form',
			'comment-list',
			'gallery',
			'navigation-widgets',
			'script',
			'search-form',
			'style',
		)
	);
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 96,
			'width'       => 320,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);
	add_theme_support( 'custom-background' );
	add_theme_support( 'custom-header' );
	add_theme_support( 'editor-styles' );
	add_editor_style( 'assets/css/editor.css' );
	add_theme_support( 'woocommerce' );
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );

	register_nav_menus(
		array(
			'primary'   => __( 'Primary Menu', 'infosecnexus' ),
			'secondary' => __( 'Secondary Menu', 'infosecnexus' ),
			'footer'    => __( 'Footer Menu', 'infosecnexus' ),
			'legal'     => __( 'Legal Menu', 'infosecnexus' ),
		)
	);
}

/**
 * Register block styles and patterns.
 */
function register_block_enhancements(): void {
	if ( function_exists( 'register_block_style' ) ) {
		register_block_style(
			'core/quote',
			array(
				'name'  => 'infosecnexus-briefing',
				'label' => __( 'Briefing Note', 'infosecnexus' ),
			)
		);
	}

	if ( function_exists( 'register_block_pattern' ) ) {
		register_block_pattern(
			'infosecnexus/security-alert',
			array(
				'title'      => __( 'Security Alert', 'infosecnexus' ),
				'categories' => array( 'featured' ),
				'content'    => '<!-- wp:group {"className":"alert-strip"} --><div class="wp-block-group alert-strip"><!-- wp:paragraph --><p><strong>' . esc_html__( 'Security alert', 'infosecnexus' ) . '</strong> ' . esc_html__( 'Add a concise operational update.', 'infosecnexus' ) . '</p><!-- /wp:paragraph --></div><!-- /wp:group -->',
			)
		);
	}
}

/**
 * Register widget areas.
 */
function register_widget_areas(): void {
	$areas = array(
		'sidebar-1'       => __( 'Primary Sidebar', 'infosecnexus' ),
		'header-builder'  => __( 'Header Builder Widget Area', 'infosecnexus' ),
		'offcanvas-panel' => __( 'Mobile Off Canvas Panel', 'infosecnexus' ),
		'footer-1'        => __( 'Footer Column 1', 'infosecnexus' ),
		'footer-2'        => __( 'Footer Column 2', 'infosecnexus' ),
		'footer-3'        => __( 'Footer Column 3', 'infosecnexus' ),
		'footer-4'        => __( 'Footer Column 4', 'infosecnexus' ),
	);

	foreach ( $areas as $id => $name ) {
		register_sidebar(
			array(
				'name'          => $name,
				'id'            => $id,
				'description'   => __( 'InfoSecNexus widget area.', 'infosecnexus' ),
				'before_widget' => '<section id="%1$s" class="widget %2$s">',
				'after_widget'  => '</section>',
				'before_title'  => '<h2 class="widget-title">',
				'after_title'   => '</h2>',
			)
		);
	}
}

/**
 * Add theme body classes.
 *
 * @param string[] $classes Existing body classes.
 * @return string[]
 */
function body_classes( array $classes ): array {
	$classes[] = 'infosecnexus';

	if ( get_theme_mod( 'header_sticky', true ) ) {
		$classes[] = 'has-sticky-header';
	}

	if ( get_theme_mod( 'header_transparent', false ) && ( is_front_page() || is_page() ) ) {
		$classes[] = 'has-transparent-header';
	}

	$layout = 'content-sidebar';
	if ( is_singular() ) {
		$post_layout = get_post_meta( get_queried_object_id(), '_infosecnexus_layout', true );
		if ( $post_layout ) {
			$layout = sanitize_key( $post_layout );
		}
	} else {
		$layout = sanitize_key( (string) get_theme_mod( 'archive_layout_width', 'content-sidebar' ) );
	}

	$classes[] = 'layout-' . $layout;

	return $classes;
}

/**
 * Accessible excerpt continuation.
 *
 * @return string
 */
function excerpt_more(): string {
	return '&hellip;';
}
