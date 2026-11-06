<?php
/**
 * Responsive media for the cinematic editorial design.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

namespace InfoSecNexus\Theme\Cinematic_Media;

/**
 * Resolve an artwork key without changing editorial attachment selections.
 *
 * @param string $key Requested illustration.
 * @return string Canonical asset key.
 */
function normalize_key( string $key ): string {
	$aliases = array( 'critical' => 'shield', 'windows' => 'shield', 'team' => 'network', 'devops' => 'linux', 'wordpress' => 'shield' );
	$key = $aliases[ $key ] ?? $key;
	return in_array( $key, array( 'hero', 'shield', 'cloud', 'linux', 'ai', 'network', 'newsletter' ), true ) ? $key : 'shield';
}

/**
 * Return a public URL for the closest suitable source width.
 *
 * @param string $key   Illustration key.
 * @param int    $width Requested source width.
 * @return string Escapable public URL.
 */
function asset_url( string $key, int $width = 1280 ): string {
	if ( function_exists( '\\InfoSecNexus\\Theme\\Ivory_Media\\asset_url' ) ) {
		return \InfoSecNexus\Theme\Ivory_Media\asset_url( $key, $width );
	}
	$key = normalize_key( $key );
	$widths = 'hero' === $key ? array( 640, 800, 1280, 1600 ) : array( 256, 512, 960 );
	$selected = (int) end( $widths );
	foreach ( $widths as $candidate ) {
		if ( $candidate >= $width ) {
			$selected = $candidate;
			break;
		}
	}
	return get_template_directory_uri() . '/assets/cinematic/' . $key . '-' . $selected . '.webp';
}

/**
 * Return descriptive alternative text; callers may pass an empty decorative alt.
 *
 * @param string $key Illustration key.
 * @return string Localized description.
 */
function alt_text( string $key ): string {
	if ( function_exists( '\\InfoSecNexus\\Theme\\Ivory_Media\\alt_text' ) ) {
		return \InfoSecNexus\Theme\Ivory_Media\alt_text( $key );
	}
	$labels = array(
		'hero'       => __( 'Blue and cyan light flowing through optical fibers', 'infosecnexus' ),
		'shield'     => __( 'Layered blue security shield and padlock on a raised platform', 'infosecnexus' ),
		'cloud'      => __( 'Blue cloud protected by a padlock', 'infosecnexus' ),
		'linux'      => __( 'Blue terminal console and deployment gears', 'infosecnexus' ),
		'ai'         => __( 'Blue processor with connected neural nodes', 'infosecnexus' ),
		'network'    => __( 'Blue network switch with connected nodes', 'infosecnexus' ),
		'newsletter' => __( 'Blue envelope and a security briefing letter', 'infosecnexus' ),
	);
	return $labels[ normalize_key( $key ) ];
}

/**
 * Build escaped image attributes from theme-owned values.
 *
 * @param array<string,mixed> $attrs Attributes.
 * @return string Escaped HTML attributes.
 */
function image_attributes( array $attrs ): string {
	$html = '';
	foreach ( $attrs as $name => $value ) {
		if ( false === $value || null === $value || ( '' === $value && 'alt' !== $name ) ) {
			continue;
		}
		if ( true === $value ) {
			$html .= ' ' . esc_attr( (string) $name );
			continue;
		}
		$html .= ' ' . esc_attr( (string) $name ) . '="' . esc_attr( (string) $value ) . '"';
	}
	return $html;
}

/**
 * Render a responsive transparent illustration or wide fiber background.
 *
 * @param string              $key   Artwork key.
 * @param array<string,mixed> $attrs Image attributes.
 * @return string Escaped image markup.
 */
function image( string $key, array $attrs = array() ): string {
	if ( function_exists( '\\InfoSecNexus\\Theme\\Ivory_Media\\image' ) ) {
		return \InfoSecNexus\Theme\Ivory_Media\image( $key, $attrs );
	}
	$key = normalize_key( $key );
	$is_hero = 'hero' === $key;
	$widths = $is_hero ? array( 640, 800, 1280, 1600 ) : array( 256, 512, 960 );
	$natural_width = $is_hero ? 1600 : 960;
	$natural_height = $is_hero ? 900 : ( 'shield' === $key ? 1020 : 960 );
	$srcset = array();
	foreach ( $widths as $width ) {
		$srcset[] = asset_url( $key, $width ) . ' ' . $width . 'w';
	}
	$attrs = array_merge(
		array(
			'alt'      => alt_text( $key ),
			'class'    => 'cinematic-art cinematic-art--' . $key,
			'width'    => $natural_width,
			'height'   => $natural_height,
			'loading'  => 'lazy',
			'decoding' => 'async',
			'sizes'    => $is_hero ? '100vw' : '(max-width: 640px) 160px, 240px',
		),
		$attrs
	);
	$attrs['src'] = asset_url( $key, $natural_width );
	$attrs['srcset'] = implode( ', ', $srcset );
	return '<img' . image_attributes( $attrs ) . '>';
}

/**
 * Return an exact 16:9 source for existing article and social-image consumers.
 *
 * @param string $key   Artwork key.
 * @param int    $width Requested source width.
 * @return string Source URL.
 */
function card_url( string $key, int $width = 1280 ): string {
	if ( function_exists( '\\InfoSecNexus\\Theme\\Ivory_Media\\card_url' ) ) {
		return \InfoSecNexus\Theme\Ivory_Media\card_url( $key, $width );
	}
	$key = normalize_key( $key );
	$width = $width <= 640 ? 640 : 1280;
	if ( 'hero' === $key ) {
		return asset_url( $key, $width );
	}
	return get_template_directory_uri() . '/assets/cinematic/' . $key . '-card-' . $width . '.webp';
}

/**
 * Render 16:9 artwork while retaining existing attachment-independent callers.
 *
 * @param string              $key   Artwork key.
 * @param array<string,mixed> $attrs Image attributes.
 * @return string Escaped image markup.
 */
function card_image( string $key, array $attrs = array() ): string {
	if ( function_exists( '\\InfoSecNexus\\Theme\\Ivory_Media\\card_image' ) ) {
		return \InfoSecNexus\Theme\Ivory_Media\card_image( $key, $attrs );
	}
	$attrs = array_merge(
		array(
			'alt'      => alt_text( $key ),
			'class'    => 'isn-editorial-image cinematic-card-art',
			'width'    => 1280,
			'height'   => 720,
			'loading'  => 'lazy',
			'decoding' => 'async',
			'sizes'    => '(max-width: 720px) calc(100vw - 32px), 640px',
		),
		$attrs
	);
	$attrs['src'] = card_url( $key );
	$attrs['srcset'] = card_url( $key, 640 ) . ' 640w, ' . card_url( $key, 1280 ) . ' 1280w';
	return '<img' . image_attributes( $attrs ) . '>';
}
