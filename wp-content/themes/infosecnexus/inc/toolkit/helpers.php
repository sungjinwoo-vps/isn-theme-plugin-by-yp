<?php
/**
 * Shared helpers.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

namespace InfoSecNexus\Theme\Toolkit;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const OPTION_KEY = 'infosecnexus_toolkit_options';

/**
 * Get toolkit options.
 *
 * @return array<string,mixed>
 */
function options(): array {
	$options = get_option( OPTION_KEY, array() );
	return is_array( $options ) ? $options : array();
}

/**
 * Get option value.
 *
 * @param string $key Option key.
 * @param mixed  $fallback Fallback.
 * @return mixed
 */
function option( string $key, $fallback = null ) {
	$options = options();
	return array_key_exists( $key, $options ) ? $options[ $key ] : $fallback;
}

/**
 * Check module setting.
 *
 * @param string $module Module key.
 * @return bool
 */
function module_enabled( string $module ): bool {
	$modules = option( 'modules', Plugin::default_modules() );
	return is_array( $modules ) ? ! empty( $modules[ $module ] ) : true;
}

/**
 * Get a privacy-aware IP hash for rate limiting.
 *
 * @return string
 */
function request_fingerprint(): string {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
	return hash_hmac( 'sha256', $ip, wp_salt( 'nonce' ) );
}

/**
 * Basic per-action rate limiter.
 *
 * @param string $action Action key.
 * @param int    $limit Limit.
 * @param int    $window Window in seconds.
 * @return bool True when allowed.
 */
function rate_limit( string $action, int $limit = 30, int $window = 60 ): bool {
	$key   = 'isnx_rate_' . md5( $action . request_fingerprint() );
	$count = (int) get_transient( $key );
	if ( $count >= $limit ) {
		return false;
	}
	set_transient( $key, $count + 1, $window );
	return true;
}

/**
 * Create a cache-tolerant signed token for a public form.
 *
 * WordPress nonces are still rendered with the forms, but long-lived full-page
 * caches can outlive a normal nonce. This signed timestamp keeps cached public
 * forms usable without accepting blind cross-site submissions.
 *
 * @param string $action Form action.
 * @return array{issued:int,token:string}
 */
function public_form_token( string $action ): array {
	$issued = time();
	$token  = hash_hmac( 'sha256', $action . '|' . $issued, wp_salt( 'nonce' ) );

	return array(
		'issued' => $issued,
		'token'  => $token,
	);
}

/**
 * Verify a cache-tolerant public form token.
 *
 * @param string $action Form action.
 * @param int    $issued Token issue timestamp.
 * @param string $token Token value.
 * @param int    $max_age Maximum token age.
 */
function verify_public_form_token( string $action, int $issued, string $token, int $max_age = 691200 ): bool {
	$age = time() - $issued;
	if ( $issued <= 0 || $age < 1 || $age > $max_age || strlen( $token ) !== 64 ) {
		return false;
	}

	$expected = hash_hmac( 'sha256', $action . '|' . $issued, wp_salt( 'nonce' ) );
	return hash_equals( $expected, $token );
}

/**
 * Check a supplied browser origin or referrer against this WordPress site.
 *
 * Requests without either header remain valid because privacy tools may remove
 * both. When a header is present, a foreign host is rejected.
 */
function request_origin_is_local(): bool {
	$site_host = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
	$site_host = preg_replace( '/^www\./', '', $site_host );

	foreach ( array( 'HTTP_ORIGIN', 'HTTP_REFERER' ) as $server_key ) {
		if ( empty( $_SERVER[ $server_key ] ) ) {
			continue;
		}

		$url  = esc_url_raw( wp_unslash( (string) $_SERVER[ $server_key ] ) );
		$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		$host = preg_replace( '/^www\./', '', $host );
		if ( '' !== $host && ! hash_equals( $site_host, $host ) ) {
			return false;
		}
	}

	return true;
}

/**
 * Return the same-site WordPress public form endpoint.
 */
function public_form_action_url(): string {
	$url         = admin_url( 'admin-post.php' );
	$home_scheme = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_SCHEME ) );
	return is_ssl() || 'https' === $home_scheme ? set_url_scheme( $url, 'https' ) : $url;
}

/**
 * Check whether WooCommerce is active.
 *
 * @return bool
 */
function woocommerce_active(): bool {
	return class_exists( 'WooCommerce' );
}
