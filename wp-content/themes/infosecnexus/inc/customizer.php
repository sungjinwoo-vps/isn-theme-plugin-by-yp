<?php
/**
 * Customizer settings and design token output.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

namespace InfoSecNexus\Theme\Customizer;

use WP_Customize_Color_Control;
use WP_Customize_Manager;

const DEFAULTS = array(
	'accent_color'           => '#00a8c8',
	'critical_color'         => '#d92d20',
	'high_color'             => '#b7791f',
	'info_color'             => '#2563eb',
	'body_font'              => 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
	'heading_font'           => 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
	'base_font_size'         => '17px',
	'content_width'          => '1120px',
	'wide_width'             => '1360px',
	'default_color_mode'     => 'system',
	'enable_scroll_top'      => true,
	'alert_enabled'          => false,
	'alert_label'            => 'Security Alert:',
	'alert_text'             => 'Active exploitation reported for multiple vulnerabilities. Apply patches immediately.',
	'alert_link_label'       => 'View Alerts',
	'alert_link_url'         => '/category/critical-cves/',
	'header_top_elements'    => 'tagline,spacer,social_links',
	'header_main_elements'   => 'logo,primary_menu,spacer,search,color_mode_switch,mobile_trigger',
	'header_bottom_elements' => 'secondary_menu',
	'header_sticky'          => true,
	'header_shrink'          => true,
	'header_reveal'          => true,
	'header_transparent'     => false,
	'header_button_label'    => 'Daily Cyber Brief',
	'header_button_url'      => '/#daily-cyber-brief',
	'home_hero_badge'        => 'Critical Brief',
	'home_hero_title'        => 'July Patch Shockwave: Enterprise EOL & Active Zero-Days',
	'home_hero_excerpt'      => 'Critical updates, end-of-life notices, and zero-day activity shaping risk this month.',
	'home_hero_button_label' => 'Read Full Brief',
	'home_hero_button_url'   => '/category/critical-cves/',
	'home_latest_title'      => 'Latest Intelligence',
	'home_cve_title'         => 'Critical CVEs',
	'newsletter_title'       => 'Get the Daily Cyber Brief',
	'newsletter_intro'       => 'Top stories, critical alerts, and expert analysis delivered to your inbox every morning.',
	'header_html'            => '',
	'contact_text'           => '',
	'footer_top_elements'    => 'logo',
	'footer_main_elements'   => 'widgets',
	'footer_bottom_elements' => 'copyright,spacer,legal_navigation',
	'copyright'              => 'Copyright {year} InfoSecNexus. All rights reserved.',
	'social_x'               => '',
	'social_github'          => '',
	'social_linkedin'        => '',
	'social_youtube'         => '',
	'archive_layout_width'   => 'content-sidebar',
);

/**
 * Register hooks.
 */
function bootstrap(): void {
	add_action( 'customize_register', __NAMESPACE__ . '\\register' );
	add_action( 'customize_preview_init', __NAMESPACE__ . '\\enqueue_preview' );
}

/**
 * Get default value.
 *
 * @param string $key Setting key.
 * @return mixed
 */
function default_value( string $key ) {
	return DEFAULTS[ $key ] ?? '';
}

/**
 * Allowed settings for import/export.
 *
 * @return string[]
 */
function allowed_setting_keys(): array {
	return array_keys( DEFAULTS );
}

/**
 * Get a theme setting with default.
 *
 * @param string $key Setting key.
 * @return mixed
 */
function get_value( string $key ) {
	return get_theme_mod( $key, default_value( $key ) );
}

/**
 * Register Customizer controls.
 *
 * @param WP_Customize_Manager $wp_customize Customizer instance.
 */
function register( WP_Customize_Manager $wp_customize ): void {
	$wp_customize->add_panel(
		'infosecnexus_design',
		array(
			'title'    => __( 'InfoSecNexus Design', 'infosecnexus' ),
			'priority' => 30,
		)
	);

	register_design_section( $wp_customize );
	register_alert_section( $wp_customize );
	register_homepage_section( $wp_customize );
	register_header_section( $wp_customize );
	register_footer_section( $wp_customize );
	register_social_section( $wp_customize );
}

/**
 * Register design token controls.
 *
 * @param WP_Customize_Manager $wp_customize Customizer instance.
 */
function register_design_section( WP_Customize_Manager $wp_customize ): void {
	$wp_customize->add_section(
		'infosecnexus_tokens',
		array(
			'title' => __( 'Global Design Tokens', 'infosecnexus' ),
			'panel' => 'infosecnexus_design',
		)
	);

	$colors = array(
		'accent_color'   => __( 'Primary Accent', 'infosecnexus' ),
		'critical_color' => __( 'Critical Severity', 'infosecnexus' ),
		'high_color'     => __( 'High Severity', 'infosecnexus' ),
		'info_color'     => __( 'Informational Label', 'infosecnexus' ),
	);

	foreach ( $colors as $key => $label ) {
		$wp_customize->add_setting(
			$key,
			array(
				'default'           => default_value( $key ),
				'sanitize_callback' => 'sanitize_hex_color',
				'transport'         => 'postMessage',
			)
		);
		$wp_customize->add_control(
			new WP_Customize_Color_Control(
				$wp_customize,
				$key,
				array(
					'label'   => $label,
					'section' => 'infosecnexus_tokens',
				)
			)
		);
	}

	$text_settings = array(
		'body_font'      => __( 'Body Font Stack', 'infosecnexus' ),
		'heading_font'   => __( 'Heading Font Stack', 'infosecnexus' ),
		'base_font_size' => __( 'Base Font Size', 'infosecnexus' ),
		'content_width'  => __( 'Content Width', 'infosecnexus' ),
		'wide_width'     => __( 'Wide Width', 'infosecnexus' ),
	);

	foreach ( $text_settings as $key => $label ) {
		$wp_customize->add_setting(
			$key,
			array(
				'default'           => default_value( $key ),
				'sanitize_callback' => in_array( $key, array( 'base_font_size', 'content_width', 'wide_width' ), true ) ? __NAMESPACE__ . '\\sanitize_css_size' : 'sanitize_text_field',
				'transport'         => 'postMessage',
			)
		);
		$wp_customize->add_control(
			$key,
			array(
				'label'   => $label,
				'section' => 'infosecnexus_tokens',
				'type'    => 'text',
			)
		);
	}

	add_choice_control(
		$wp_customize,
		'default_color_mode',
		__( 'Default Color Mode', 'infosecnexus' ),
		'infosecnexus_tokens',
		array(
			'system' => __( 'System preference', 'infosecnexus' ),
			'light'  => __( 'Light', 'infosecnexus' ),
			'dark'   => __( 'Dark', 'infosecnexus' ),
		)
	);

	add_checkbox_control( $wp_customize, 'enable_scroll_top', __( 'Enable scroll-to-top control', 'infosecnexus' ), 'infosecnexus_tokens' );
	add_choice_control(
		$wp_customize,
		'archive_layout_width',
		__( 'Default Archive Layout', 'infosecnexus' ),
		'infosecnexus_tokens',
		array(
			'content-sidebar' => __( 'Content with right sidebar', 'infosecnexus' ),
			'sidebar-content' => __( 'Left sidebar with content', 'infosecnexus' ),
			'wide'            => __( 'Wide content', 'infosecnexus' ),
			'narrow'          => __( 'Narrow content', 'infosecnexus' ),
			'full-width'      => __( 'Full width', 'infosecnexus' ),
			'no-sidebar'      => __( 'No sidebar', 'infosecnexus' ),
		)
	);
}

/**
 * Register security alert controls.
 *
 * @param WP_Customize_Manager $wp_customize Customizer instance.
 */
function register_alert_section( WP_Customize_Manager $wp_customize ): void {
	$wp_customize->add_section(
		'infosecnexus_alert',
		array(
			'title' => __( 'Security Alert Bar', 'infosecnexus' ),
			'panel' => 'infosecnexus_design',
		)
	);

	add_checkbox_control( $wp_customize, 'alert_enabled', __( 'Show alert bar', 'infosecnexus' ), 'infosecnexus_alert' );
	add_text_control( $wp_customize, 'alert_label', __( 'Alert Label', 'infosecnexus' ), 'infosecnexus_alert' );
	add_text_control( $wp_customize, 'alert_text', __( 'Alert Text', 'infosecnexus' ), 'infosecnexus_alert' );
	add_text_control( $wp_customize, 'alert_link_label', __( 'Alert Link Label', 'infosecnexus' ), 'infosecnexus_alert' );
	add_url_control( $wp_customize, 'alert_link_url', __( 'Alert Link URL', 'infosecnexus' ), 'infosecnexus_alert' );
}

/**
 * Register homepage content controls.
 *
 * @param WP_Customize_Manager $wp_customize Customizer instance.
 */
function register_homepage_section( WP_Customize_Manager $wp_customize ): void {
	$wp_customize->add_section(
		'infosecnexus_homepage',
		array(
			'title' => __( 'Homepage Content', 'infosecnexus' ),
			'panel' => 'infosecnexus_design',
		)
	);

	add_text_control( $wp_customize, 'home_hero_badge', __( 'Hero Badge', 'infosecnexus' ), 'infosecnexus_homepage' );
	add_textarea_control( $wp_customize, 'home_hero_title', __( 'Hero Title', 'infosecnexus' ), 'infosecnexus_homepage' );
	add_textarea_control( $wp_customize, 'home_hero_excerpt', __( 'Hero Excerpt', 'infosecnexus' ), 'infosecnexus_homepage' );
	add_text_control( $wp_customize, 'home_hero_button_label', __( 'Hero Button Label', 'infosecnexus' ), 'infosecnexus_homepage' );
	add_url_control( $wp_customize, 'home_hero_button_url', __( 'Hero Button URL', 'infosecnexus' ), 'infosecnexus_homepage' );
	add_text_control( $wp_customize, 'home_latest_title', __( 'Latest Section Title', 'infosecnexus' ), 'infosecnexus_homepage' );
	add_text_control( $wp_customize, 'home_cve_title', __( 'CVE Section Title', 'infosecnexus' ), 'infosecnexus_homepage' );
	add_text_control( $wp_customize, 'newsletter_title', __( 'Newsletter Title', 'infosecnexus' ), 'infosecnexus_homepage' );
	add_textarea_control( $wp_customize, 'newsletter_intro', __( 'Newsletter Intro', 'infosecnexus' ), 'infosecnexus_homepage' );
}

/**
 * Register header builder controls.
 *
 * @param WP_Customize_Manager $wp_customize Customizer instance.
 */
function register_header_section( WP_Customize_Manager $wp_customize ): void {
	$wp_customize->add_section(
		'infosecnexus_header_builder',
		array(
			'title' => __( 'Header Builder', 'infosecnexus' ),
			'panel' => 'infosecnexus_design',
		)
	);

	$rows = array(
		'header_top_elements'    => __( 'Top Row Elements', 'infosecnexus' ),
		'header_main_elements'   => __( 'Main Row Elements', 'infosecnexus' ),
		'header_bottom_elements' => __( 'Bottom Row Elements', 'infosecnexus' ),
	);

	foreach ( $rows as $key => $label ) {
		$wp_customize->add_setting(
			$key,
			array(
				'default'           => default_value( $key ),
				'sanitize_callback' => __NAMESPACE__ . '\\sanitize_elements',
			)
		);
		$wp_customize->add_control(
			$key,
			array(
				'label'       => $label,
				'description' => __( 'Comma-separated: logo, site_title, tagline, primary_menu, secondary_menu, button, search, live_search_trigger, social_links, html, contact, widget_area, divider, spacer, user_account, mobile_trigger, color_mode_switch, cart, wishlist, comparison.', 'infosecnexus' ),
				'section'     => 'infosecnexus_header_builder',
				'type'        => 'text',
			)
		);
	}

	foreach ( array( 'header_sticky', 'header_shrink', 'header_reveal', 'header_transparent' ) as $key ) {
		add_checkbox_control( $wp_customize, $key, ucwords( str_replace( '_', ' ', $key ) ), 'infosecnexus_header_builder' );
	}

	add_text_control( $wp_customize, 'header_button_label', __( 'Button Label', 'infosecnexus' ), 'infosecnexus_header_builder' );
	add_url_control( $wp_customize, 'header_button_url', __( 'Button URL', 'infosecnexus' ), 'infosecnexus_header_builder' );
	add_textarea_control( $wp_customize, 'header_html', __( 'HTML/Text Element', 'infosecnexus' ), 'infosecnexus_header_builder' );
	add_text_control( $wp_customize, 'contact_text', __( 'Contact Text', 'infosecnexus' ), 'infosecnexus_header_builder' );
}

/**
 * Register footer builder controls.
 *
 * @param WP_Customize_Manager $wp_customize Customizer instance.
 */
function register_footer_section( WP_Customize_Manager $wp_customize ): void {
	$wp_customize->add_section(
		'infosecnexus_footer_builder',
		array(
			'title' => __( 'Footer Builder', 'infosecnexus' ),
			'panel' => 'infosecnexus_design',
		)
	);

	foreach (
		array(
			'footer_top_elements'    => __( 'Top Row Elements', 'infosecnexus' ),
			'footer_main_elements'   => __( 'Main Row Elements', 'infosecnexus' ),
			'footer_bottom_elements' => __( 'Bottom Row Elements', 'infosecnexus' ),
		) as $key => $label
	) {
		$wp_customize->add_setting(
			$key,
			array(
				'default'           => default_value( $key ),
				'sanitize_callback' => __NAMESPACE__ . '\\sanitize_footer_elements',
			)
		);
		$wp_customize->add_control(
			$key,
			array(
				'label'       => $label,
				'description' => __( 'Comma-separated: logo, site_identity, copyright, navigation, legal_navigation, social_links, contact, search, html, widgets, button, elementor_template, spacer, divider.', 'infosecnexus' ),
				'section'     => 'infosecnexus_footer_builder',
				'type'        => 'text',
			)
		);
	}

	add_text_control( $wp_customize, 'copyright', __( 'Copyright Text', 'infosecnexus' ), 'infosecnexus_footer_builder' );
}

/**
 * Register social profile controls.
 *
 * @param WP_Customize_Manager $wp_customize Customizer instance.
 */
function register_social_section( WP_Customize_Manager $wp_customize ): void {
	$wp_customize->add_section(
		'infosecnexus_social',
		array(
			'title' => __( 'Social Profiles', 'infosecnexus' ),
			'panel' => 'infosecnexus_design',
		)
	);

	foreach (
		array(
			'social_x'        => 'X',
			'social_github'   => 'GitHub',
			'social_linkedin' => 'LinkedIn',
			'social_youtube'  => 'YouTube',
		) as $key => $label
	) {
		add_url_control( $wp_customize, $key, $label, 'infosecnexus_social' );
	}
}

/**
 * Add text control.
 *
 * @param WP_Customize_Manager $wp_customize Customizer instance.
 * @param string               $key Setting key.
 * @param string               $label Label.
 * @param string               $section Section ID.
 */
function add_text_control( WP_Customize_Manager $wp_customize, string $key, string $label, string $section ): void {
	$wp_customize->add_setting(
		$key,
		array(
			'default'           => default_value( $key ),
			'sanitize_callback' => 'sanitize_text_field',
		)
	);
	$wp_customize->add_control(
		$key,
		array(
			'label'   => $label,
			'section' => $section,
			'type'    => 'text',
		)
	);
}

/**
 * Add textarea control.
 *
 * @param WP_Customize_Manager $wp_customize Customizer instance.
 * @param string               $key Setting key.
 * @param string               $label Label.
 * @param string               $section Section ID.
 */
function add_textarea_control( WP_Customize_Manager $wp_customize, string $key, string $label, string $section ): void {
	$wp_customize->add_setting(
		$key,
		array(
			'default'           => default_value( $key ),
			'sanitize_callback' => 'wp_kses_post',
		)
	);
	$wp_customize->add_control(
		$key,
		array(
			'label'   => $label,
			'section' => $section,
			'type'    => 'textarea',
		)
	);
}

/**
 * Add URL control.
 *
 * @param WP_Customize_Manager $wp_customize Customizer instance.
 * @param string               $key Setting key.
 * @param string               $label Label.
 * @param string               $section Section ID.
 */
function add_url_control( WP_Customize_Manager $wp_customize, string $key, string $label, string $section ): void {
	$wp_customize->add_setting(
		$key,
		array(
			'default'           => default_value( $key ),
			'sanitize_callback' => 'esc_url_raw',
		)
	);
	$wp_customize->add_control(
		$key,
		array(
			'label'   => $label,
			'section' => $section,
			'type'    => 'url',
		)
	);
}

/**
 * Add checkbox control.
 *
 * @param WP_Customize_Manager $wp_customize Customizer instance.
 * @param string               $key Setting key.
 * @param string               $label Label.
 * @param string               $section Section ID.
 */
function add_checkbox_control( WP_Customize_Manager $wp_customize, string $key, string $label, string $section ): void {
	$wp_customize->add_setting(
		$key,
		array(
			'default'           => default_value( $key ),
			'sanitize_callback' => __NAMESPACE__ . '\\sanitize_bool',
		)
	);
	$wp_customize->add_control(
		$key,
		array(
			'label'   => $label,
			'section' => $section,
			'type'    => 'checkbox',
		)
	);
}

/**
 * Add select control.
 *
 * @param WP_Customize_Manager $wp_customize Customizer instance.
 * @param string               $key Setting key.
 * @param string               $label Label.
 * @param string               $section Section ID.
 * @param array<string,string> $choices Choices.
 */
function add_choice_control( WP_Customize_Manager $wp_customize, string $key, string $label, string $section, array $choices ): void {
	$wp_customize->add_setting(
		$key,
		array(
			'default'           => default_value( $key ),
			'sanitize_callback' => static function ( $value ) use ( $choices, $key ) {
				return array_key_exists( $value, $choices ) ? $value : default_value( $key );
			},
		)
	);
	$wp_customize->add_control(
		$key,
		array(
			'label'   => $label,
			'section' => $section,
			'type'    => 'select',
			'choices' => $choices,
		)
	);
}

/**
 * Sanitize boolean.
 *
 * @param mixed $value Raw value.
 * @return bool
 */
function sanitize_bool( $value ): bool {
	return (bool) $value;
}

/**
 * Sanitize a CSS size token.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function sanitize_css_size( $value ): string {
	$value = trim( (string) $value );
	return preg_match( '/^\d+(\.\d+)?(px|rem|em|%|vw|vh|ch)$/', $value ) ? $value : '1120px';
}

/**
 * Allowed header elements.
 *
 * @return string[]
 */
function allowed_header_elements(): array {
	return array( 'logo', 'site_title', 'tagline', 'primary_menu', 'secondary_menu', 'button', 'search', 'live_search_trigger', 'social_links', 'html', 'contact', 'widget_area', 'divider', 'spacer', 'user_account', 'mobile_trigger', 'color_mode_switch', 'cart', 'wishlist', 'comparison' );
}

/**
 * Allowed footer elements.
 *
 * @return string[]
 */
function allowed_footer_elements(): array {
	return array( 'logo', 'site_identity', 'copyright', 'navigation', 'legal_navigation', 'social_links', 'contact', 'search', 'html', 'widgets', 'button', 'elementor_template', 'back_to_top', 'spacer', 'divider' );
}

/**
 * Sanitize header element list.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function sanitize_elements( $value ): string {
	return sanitize_element_list( (string) $value, allowed_header_elements() );
}

/**
 * Sanitize footer element list.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function sanitize_footer_elements( $value ): string {
	return sanitize_element_list( (string) $value, allowed_footer_elements() );
}

/**
 * Sanitize element list.
 *
 * @param string   $value Raw list.
 * @param string[] $allowed Allowed values.
 * @return string
 */
function sanitize_element_list( string $value, array $allowed ): string {
	$items = array_filter( array_map( 'sanitize_key', array_map( 'trim', explode( ',', $value ) ) ) );
	$items = array_values( array_intersect( $items, $allowed ) );
	return implode( ',', $items );
}

/**
 * Output custom properties.
 *
 * @return string
 */
function custom_properties(): string {
	$css  = ':root{';
	$css .= '--isnx-accent:' . esc_html( (string) get_value( 'accent_color' ) ) . ';';
	$css .= '--isnx-critical:' . esc_html( (string) get_value( 'critical_color' ) ) . ';';
	$css .= '--isnx-high:' . esc_html( (string) get_value( 'high_color' ) ) . ';';
	$css .= '--isnx-info:' . esc_html( (string) get_value( 'info_color' ) ) . ';';
	$css .= '--isnx-body-font:' . esc_html( (string) get_value( 'body_font' ) ) . ';';
	$css .= '--isnx-heading-font:' . esc_html( (string) get_value( 'heading_font' ) ) . ';';
	$css .= '--isnx-base-font-size:' . esc_html( (string) get_value( 'base_font_size' ) ) . ';';
	$css .= '--isnx-content-width:' . esc_html( (string) get_value( 'content_width' ) ) . ';';
	$css .= '--isnx-wide-width:' . esc_html( (string) get_value( 'wide_width' ) ) . ';';
	$css .= '}';

	return $css;
}

/**
 * Enqueue Customizer preview script.
 */
function enqueue_preview(): void {
	wp_enqueue_script( 'infosecnexus-customizer-preview', get_template_directory_uri() . '/assets/js/customizer-preview.js', array( 'customize-preview' ), INFOSECNEXUS_VERSION, true );
}
