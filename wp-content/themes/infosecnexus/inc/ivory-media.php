<?php
/** Original responsive artwork for the warm editorial design. */
declare(strict_types=1);

namespace InfoSecNexus\Theme\Ivory_Media;

function normalize_key( string $key ): string {
	$aliases = array( 'shield' => 'critical', 'windows' => 'critical', 'team' => 'network', 'newsletter' => 'hero' );
	$key = $aliases[ $key ] ?? $key;
	return in_array( $key, array( 'hero', 'critical', 'cloud', 'linux', 'ai', 'network' ), true ) ? $key : 'network';
}

function asset_url( string $key, int $width = 1280 ): string {
	$key = normalize_key( $key );
	$widths = 'hero' === $key ? array( 640, 800, 1280, 1600 ) : array( 320, 640, 1280 );
	$selected = end( $widths );
	foreach ( $widths as $candidate ) {
		if ( $candidate >= $width ) { $selected = $candidate; break; }
	}
	return get_template_directory_uri() . '/assets/ivory/' . $key . '-' . $selected . '.webp';
}

function alt_text( string $key ): string {
	$alts = array(
		'hero' => 'A warm ivory workspace with an oval view into a security operations environment',
		'critical' => 'An editorial illustration of security exposure and access protection',
		'cloud' => 'A sunlit cloud and data infrastructure illustration',
		'linux' => 'A clean development and systems administration workspace',
		'ai' => 'An editorial illustration of a processor and artificial intelligence',
		'network' => 'A connected network and infrastructure illustration',
	);
	return $alts[ normalize_key( $key ) ];
}

function image( string $key, array $attrs = array() ): string {
	$key = normalize_key( $key );
	$hero = 'hero' === $key;
	$widths = $hero ? array( 640, 800, 1280, 1600 ) : array( 320, 640, 1280 );
	$attrs = array_merge( array(
		'alt' => alt_text( $key ), 'class' => 'ivory-art ivory-art--' . $key,
		'width' => $hero ? 1600 : 1280, 'height' => $hero ? 697 : 720,
		'loading' => 'lazy', 'decoding' => 'async',
		'sizes' => $hero ? '100vw' : '(max-width: 640px) 44vw, 260px',
	), $attrs );
	$attrs['src'] = asset_url( $key, $hero ? 1600 : 1280 );
	$attrs['srcset'] = implode( ', ', array_map( static fn( int $width ): string => asset_url( $key, $width ) . ' ' . $width . 'w', $widths ) );
	return markup( $attrs );
}

function markup( array $attrs ): string {
	$html = '';
	foreach ( $attrs as $name => $value ) {
		if ( null !== $value && false !== $value ) { $html .= ' ' . esc_attr( $name ) . '="' . esc_attr( (string) $value ) . '"'; }
	}
	return '<img' . $html . '>';
}

function card_url( string $key, int $width = 1280 ): string {
	if ( 'hero' === normalize_key( $key ) ) {
		return get_template_directory_uri() . '/assets/ivory/hero-card-' . ( $width <= 640 ? 640 : 1280 ) . '.webp';
	}
	return asset_url( $key, $width );
}

function card_image( string $key, array $attrs = array() ): string {
	if ( 'hero' === normalize_key( $key ) ) {
		return markup( array_merge( array( 'alt' => alt_text( $key ), 'class' => 'ivory-art', 'width' => 1280, 'height' => 720, 'loading' => 'lazy', 'decoding' => 'async', 'sizes' => '(max-width: 720px) calc(100vw - 32px), 640px', 'src' => card_url( $key ), 'srcset' => card_url( $key, 640 ) . ' 640w, ' . card_url( $key, 1280 ) . ' 1280w' ), $attrs ) );
	}
	return image( $key, array_merge( array( 'sizes' => '(max-width: 720px) calc(100vw - 32px), 640px' ), $attrs ) );
}

function icon_image( string $key, array $attrs = array() ): string {
	$key = normalize_key( $key );
	$base = get_template_directory_uri() . '/assets/ivory/icon-' . $key . '-';
	return markup( array_merge( array( 'alt' => '', 'width' => 512, 'height' => 512, 'loading' => 'lazy', 'decoding' => 'async', 'sizes' => '130px', 'src' => $base . '512.webp', 'srcset' => $base . '256.webp 256w, ' . $base . '512.webp 512w' ), $attrs ) );
}
