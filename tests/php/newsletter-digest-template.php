<?php
/** Standalone rendering checks for the Daily Cyber Brief email. */
declare(strict_types=1);

use InfoSecNexus\Theme\Toolkit\Newsletter_Digest_Template;

function __( string $copy, string $domain = '' ): string {
	return $copy;
}

function esc_html( string $value ): string {
	return htmlspecialchars( $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false );
}

function esc_attr( string $value ): string {
	return esc_html( $value );
}

function esc_url_raw( string $url ): string {
	return preg_match( '~^https?://~i', $url ) ? $url : '';
}

function esc_url( string $url ): string {
	return esc_attr( esc_url_raw( $url ) );
}

function wp_strip_all_tags( string $text, bool $remove_breaks = false ): string {
	$text = strip_tags( preg_replace( '~<(script|style)\b[^>]*>.*?</\1>~is', '', $text ) ?? '' );
	return trim( $remove_breaks ? preg_replace( '/[\r\n\t ]+/', ' ', $text ) ?? '' : $text );
}

function wp_specialchars_decode( string $text, int $quote_style = ENT_NOQUOTES ): string {
	return htmlspecialchars_decode( $text, $quote_style );
}

function get_template_directory_uri(): string {
	return 'https://example.test/wp-content/themes/infosecnexus';
}

function check( bool $passed, string $description ): void {
	if ( ! $passed ) {
		throw new RuntimeException( $description );
	}
}

function link_targets( string $html ): array {
	preg_match_all( '/<a\b[^>]*\bhref=["\']([^"\']*)["\']/i', $html, $matches );
	return array_map( static fn( string $href ): string => html_entity_decode( $href, ENT_QUOTES, 'UTF-8' ), $matches[1] );
}

require $argv[1] ?? dirname( __DIR__, 2 ) . '/wp-content/themes/infosecnexus/inc/toolkit/class-newsletter-digest-template.php';

$date        = 'November 19, 2026';
$brief       = 'https://example.test/live-cybersecurity-brief/';
$unsubscribe = 'https://example.test/unsubscribe/?subscriber=fixture&token=synthetic';
$entries     = array(
	array(
		'title' => 'OpenSSH & Linux <img src=x onerror="alert(1)">',
		'url'   => 'https://example.test/openssh/?channel=digest&id=42',
	),
	array(
		'title' => 'Cloud &amp; network guidance',
		'url'   => 'https://example.test/cloud/',
	),
);

foreach ( array( 1, 2 ) as $entry_count ) {
	$included = array_slice( $entries, 0, $entry_count );
	$html     = Newsletter_Digest_Template::render( $date, $included, $brief, $unsubscribe );
	$text     = html_entity_decode( wp_strip_all_tags( $html ), ENT_QUOTES, 'UTF-8' );
	$links    = array_count_values( link_targets( $html ) );

	check( str_contains( $text, 'Daily Cyber Brief' ) && str_contains( $text, $date ), 'The name and edition date must remain visible with images blocked.' );
	check( str_contains( $html, 'OpenSSH &amp; Linux' ), 'The HTML must preserve and escape ampersands in headlines.' );
	check( ! preg_match( '/<img\b[^>]*\bonerror\s*=/i', $html ), 'An article title must never inject an active HTML element.' );
	check( preg_match( '/channel=digest&(?:amp;|#0*38;|#x26;)id=42/i', $html ) === 1, 'A query string must be escaped in HTML.' );
	check( str_contains( $text, 'OpenSSH & Linux' ), 'Article headlines must remain visible with images blocked.' );
	check( ( $links[ $brief ] ?? 0 ) === 1, 'The HTML must include one Daily Brief call to action.' );
	check( ( $links[ $unsubscribe ] ?? 0 ) === 1, 'The HTML must retain one exact unsubscribe destination.' );
	check( str_contains( $text, 'Unsubscribe' ), 'The unsubscribe action must be a readable text link.' );
	foreach ( $included as $entry ) {
		check( isset( $links[ $entry['url'] ] ), 'Each article must retain its original destination in the HTML.' );
	}
	if ( 2 === $entry_count ) {
		check( str_contains( $html, 'Cloud &amp; network guidance' ), 'Headlines received as entities must not be double escaped.' );
	} else {
		check( ! isset( $links[ $entries[1]['url'] ] ), 'A single-story edition must not include a phantom second article.' );
	}
}

echo "PASS: Daily Cyber Brief HTML rendering for one and two articles.\n";
