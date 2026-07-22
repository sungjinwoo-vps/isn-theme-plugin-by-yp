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
	add_action( 'send_headers', __NAMESPACE__ . '\\send_security_headers', 9 );
}

/**
 * Add pragmatic security headers for public pages.
 *
 * @param array<string,string> $headers Existing headers.
 * @return array<string,string>
 */
function headers( array $headers ): array {
	if ( should_skip_headers() ) {
		return $headers;
	}

	return array_merge( $headers, security_headers() );
}

/**
 * Send the same headers for WordPress responses that bypass wp_headers.
 */
function send_security_headers(): void {
	if ( headers_sent() || should_skip_headers() ) {
		return;
	}

	foreach ( security_headers() as $name => $value ) {
		header( $name . ': ' . $value, true );
	}
}

/**
 * Whether frontend security headers should be skipped.
 */
function should_skip_headers(): bool {
	if ( is_admin() || is_customize_preview() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return true;
	}

	return false;
}

/**
 * Build the active security header set.
 *
 * @return array<string,string>
 */
function security_headers(): array {
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

	if ( request_is_https() ) {
		$csp[] = 'upgrade-insecure-requests';
	}

	$headers = array(
		'X-Frame-Options'           => 'DENY',
		'X-Content-Type-Options'    => 'nosniff',
		'Referrer-Policy'           => 'strict-origin-when-cross-origin',
		'Permissions-Policy'        => 'camera=(), microphone=(), geolocation=(), payment=(), usb=()',
		'Content-Security-Policy'   => implode( '; ', $csp ),
		'X-Permitted-Cross-Domain-Policies' => 'none',
	);

	if ( request_is_https() ) {
		$headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains; preload';
	}

	return $headers;
}

/**
 * Detect HTTPS reliably behind reverse proxies and CDN layers.
 */
function request_is_https(): bool {
	if ( is_ssl() ) {
		return true;
	}

	$forwarded_proto = strtolower( (string) ( $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '' ) );
	if ( 'https' === $forwarded_proto ) {
		return true;
	}

	$forwarded_ssl = strtolower( (string) ( $_SERVER['HTTP_X_FORWARDED_SSL'] ?? '' ) );
	if ( 'on' === $forwarded_ssl ) {
		return true;
	}

	$cf_visitor = (string) ( $_SERVER['HTTP_CF_VISITOR'] ?? '' );
	return str_contains( $cf_visitor, '"scheme":"https"' );
}
