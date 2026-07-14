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
	add_action( 'enqueue_block_editor_assets', __NAMESPACE__ . '\\enqueue_editor' );
}

/**
 * Enqueue frontend assets.
 */
function enqueue(): void {
	wp_enqueue_style( 'infosecnexus-style', get_template_directory_uri() . '/assets/css/theme.css', array(), INFOSECNEXUS_VERSION );
	wp_add_inline_style( 'infosecnexus-style', custom_properties() );

	wp_enqueue_script( 'infosecnexus-theme', get_template_directory_uri() . '/assets/js/theme.js', array(), INFOSECNEXUS_VERSION, true );
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
 * Enqueue block editor styles.
 */
function enqueue_editor(): void {
	wp_enqueue_style( 'infosecnexus-editor-style', get_template_directory_uri() . '/assets/css/editor.css', array(), INFOSECNEXUS_VERSION );
}
