<?php
/**
 * Shared helpers.
 *
 * @package InfoSecNexusToolkit
 */

declare(strict_types=1);

namespace InfoSecNexus\Toolkit;

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
 * Check whether WooCommerce is active.
 *
 * @return bool
 */
function woocommerce_active(): bool {
	return class_exists( 'WooCommerce' );
}
