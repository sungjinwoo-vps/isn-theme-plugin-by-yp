<?php
/**
 * Frontend security headers.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

namespace InfoSecNexus\Theme\Security_Headers;

/**
 * Register hooks.
 */
function bootstrap(): void {
	add_filter( 'wp_headers', __NAMESPACE__ . '\\headers' );
}

/**
 * Add pragmatic security headers for public pages.
 *
 * @param array<string,string> $headers Existing headers.
 * @return array<string,string>
 */
function headers( array $headers ): array {
	if ( is_admin() || is_customize_preview() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return $headers;
	}

	$headers['X-Frame-Options']        = 'DENY';
	$headers['X-Content-Type-Options'] = 'nosniff';
	$headers['Referrer-Policy']        = 'strict-origin-when-cross-origin';
	$headers['Permissions-Policy']     = 'camera=(), microphone=(), geolocation=(), payment=(), usb=()';

	$host        = (string) ( $_SERVER['HTTP_HOST'] ?? '' );
	$is_local   = (bool) preg_match( '/^(localhost|127\.0\.0\.1)(:\d+)?$/', $host );
	$dev_source = $is_local ? ' http://localhost:* http://127.0.0.1:*' : '';
	$csp        = array(
		"default-src 'self'{$dev_source}",
		"script-src 'self' 'unsafe-inline' 'unsafe-eval' https: blob:{$dev_source}",
		"style-src 'self' 'unsafe-inline' https:{$dev_source}",
		"img-src 'self' data: https:{$dev_source}",
		"font-src 'self' data: https:{$dev_source}",
		"connect-src 'self' https:{$dev_source}",
		"object-src 'none'",
		"base-uri 'self'",
		"frame-ancestors 'none'",
		"frame-src 'self' https:",
		"form-action 'self'",
	);

	if ( is_ssl() ) {
		$csp[] = 'upgrade-insecure-requests';
	}

	$headers['Content-Security-Policy'] = implode( '; ', $csp );

	if ( is_ssl() ) {
		$headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
	}

	return $headers;
}
