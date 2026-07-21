<?php
/**
 * Private theme update channel.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

namespace InfoSecNexus\Theme\Updater;

const DEFAULT_MANIFEST_URL = 'https://github.com/sungjinwoo-vps/isn-theme-plugin-by-yp/releases/latest/download/infosecnexus-releases.json';
const MANIFEST_TRANSIENT   = 'infosecnexus_theme_update_manifest';
const TOOLKIT_OPTION_KEY   = 'infosecnexus_toolkit_options';
const UPDATE_URI           = 'https://github.com/sungjinwoo-vps/isn-theme-plugin-by-yp';

/**
 * Register update hooks.
 */
function bootstrap(): void {
	if ( ! is_admin() && ! ( defined( 'WP_CLI' ) && WP_CLI ) ) {
		return;
	}

	add_filter( 'pre_set_site_transient_update_themes', __NAMESPACE__ . '\\check_theme_update' );
	add_filter( 'update_themes_github.com', __NAMESPACE__ . '\\hosted_theme_update', 10, 4 );
	add_filter( 'themes_api', __NAMESPACE__ . '\\theme_information', 10, 3 );
	add_filter( 'site_transient_update_themes', __NAMESPACE__ . '\\check_theme_update' );
	add_action( 'admin_init', __NAMESPACE__ . '\\handle_check_now' );
	add_action( 'upgrader_process_complete', __NAMESPACE__ . '\\clear_cache' );
}

/**
 * URL for a manual cache clear and update check.
 */
function check_now_url(): string {
	return wp_nonce_url( admin_url( 'themes.php?page=infosecnexus-features&infosecnexus_theme_check_updates=1' ), 'infosecnexus_theme_check_updates' );
}

/**
 * Clear cached update data before opening the WordPress Updates screen.
 */
function handle_check_now(): void {
	if ( empty( $_GET['infosecnexus_theme_check_updates'] ) || ! current_user_can( 'update_themes' ) ) {
		return;
	}

	check_admin_referer( 'infosecnexus_theme_check_updates' );
	clear_cache();
	wp_safe_redirect( admin_url( 'update-core.php?force-check=1' ) );
	exit;
}

/**
 * Add theme update data to the WordPress update transient.
 *
 * @param mixed $transient Update transient.
 * @return mixed
 */
function check_theme_update( $transient ) {
	if ( ! is_object( $transient ) || ! updates_enabled() ) {
		return $transient;
	}

	$item = update_item();
	if ( null === $item ) {
		return $transient;
	}

	if ( ! isset( $transient->response ) || ! is_array( $transient->response ) ) {
		$transient->response = array();
	}

	if ( ! isset( $transient->no_update ) || ! is_array( $transient->no_update ) ) {
		$transient->no_update = array();
	}

	unset( $transient->response['infosecnexus'], $transient->no_update['infosecnexus'] );

	if ( version_compare( INFOSECNEXUS_VERSION, $item['new_version'], '<' ) ) {
		$transient->response['infosecnexus'] = $item;
	} else {
		$transient->no_update['infosecnexus'] = $item;
	}

	return $transient;
}

/**
 * Provide update data for WordPress' native Update URI host filter.
 *
 * @param array|false $update Existing update data.
 * @param array       $theme_data Theme headers.
 * @param string      $theme_stylesheet Theme stylesheet.
 * @param string[]    $locales Installed locales.
 * @return array|false
 */
function hosted_theme_update( $update, array $theme_data, string $theme_stylesheet, array $locales ) {
	unset( $theme_data, $locales );

	if ( 'infosecnexus' !== $theme_stylesheet || ! updates_enabled() ) {
		return $update;
	}

	$item = update_item();

	return null === $item ? $update : $item;
}

/**
 * Provide theme details in the update modal.
 *
 * @param mixed  $result Existing result.
 * @param string $action API action.
 * @param mixed  $args Theme API args.
 * @return mixed
 */
function theme_information( $result, string $action, $args ) {
	$slug = isset( $args->slug ) ? (string) $args->slug : '';
	if ( 'theme_information' !== $action || 'infosecnexus' !== $slug || ! updates_enabled() ) {
		return $result;
	}

	$release = release();
	if ( empty( $release ) ) {
		return $result;
	}

	return (object) array(
		'name'          => 'InfoSecNexus',
		'slug'          => 'infosecnexus',
		'version'       => release_value( $release, 'version', INFOSECNEXUS_VERSION ),
		'author'        => 'InfoSecNexus',
		'homepage'      => release_value( $release, 'homepage', home_url( '/' ) ),
		'requires'      => release_value( $release, 'requires', '6.5' ),
		'tested'        => release_value( $release, 'tested', '7.0' ),
		'requires_php'  => release_value( $release, 'requires_php', '8.1' ),
		'last_updated'  => release_value( $release, 'last_updated' ),
		'download_link' => release_value( $release, 'package' ),
		'sections'      => sections( $release ),
	);
}

/**
 * Clear theme update caches.
 */
function clear_cache(): void {
	delete_site_transient( MANIFEST_TRANSIENT );
	delete_site_transient( 'update_themes' );
}

/**
 * Whether private updates are enabled.
 */
function updates_enabled(): bool {
	$options = get_option( TOOLKIT_OPTION_KEY, array() );
	if ( is_array( $options ) && array_key_exists( 'updates_enabled', $options ) ) {
		return (bool) $options['updates_enabled'];
	}

	return true;
}

/**
 * Manifest URL from constants or toolkit settings.
 */
function manifest_url(): string {
	if ( defined( 'INFOSECNEXUS_UPDATE_MANIFEST_URL' ) ) {
		$url = (string) constant( 'INFOSECNEXUS_UPDATE_MANIFEST_URL' );
	} else {
		$options = get_option( TOOLKIT_OPTION_KEY, array() );
		$url     = is_array( $options ) && ! empty( $options['update_manifest_url'] ) ? (string) $options['update_manifest_url'] : DEFAULT_MANIFEST_URL;

		if ( should_migrate_manifest_url( $url ) ) {
			$url = DEFAULT_MANIFEST_URL;
			if ( is_array( $options ) ) {
				$options['update_manifest_url'] = $url;
				update_option( TOOLKIT_OPTION_KEY, $options, false );
			}
		}
	}

	$url = esc_url_raw( $url );

	return '' !== $url ? $url : DEFAULT_MANIFEST_URL;
}

/**
 * Whether an older endpoint should move to the latest release manifest.
 *
 * @param string $url Manifest URL.
 * @return bool
 */
function should_migrate_manifest_url( string $url ): bool {
	return in_array(
		$url,
		array(
			'https://infosecnexus.com/updates/infosecnexus-releases.json',
			'https://raw.githubusercontent.com/sungjinwoo-vps/isn-theme-plugin-by-yp/stable/dist/infosecnexus-releases.json',
		),
		true
	);
}

/**
 * Read the release manifest.
 *
 * @return array<string,mixed>
 */
function manifest(): array {
	$cached = get_site_transient( MANIFEST_TRANSIENT );
	if ( is_array( $cached ) ) {
		return $cached;
	}

	$response = wp_remote_get(
		manifest_url(),
		array(
			'timeout' => 8,
			'headers' => array(
				'Accept' => 'application/json',
			),
		)
	);

	if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
		return array();
	}

	$manifest = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( ! is_array( $manifest ) ) {
		return array();
	}

	set_site_transient( MANIFEST_TRANSIENT, $manifest, HOUR_IN_SECONDS );

	return $manifest;
}

/**
 * Get theme release data.
 *
 * @return array<string,mixed>
 */
function release(): array {
	$manifest = manifest();
	$release  = $manifest['theme'] ?? array();

	if ( ! is_array( $release ) ) {
		return array();
	}

	if ( empty( $release['package'] ) && ! empty( $release['download_url'] ) ) {
		$release['package'] = $release['download_url'];
	}

	return $release;
}

/**
 * Build a WordPress update payload for this theme.
 *
 * @return array<string,mixed>|null
 */
function update_item(): ?array {
	$release = release();
	$version = release_value( $release, 'version' );
	$package = release_value( $release, 'package' );

	if ( '' === $version || '' === $package ) {
		return null;
	}

	return array(
		'id'             => UPDATE_URI,
		'theme'          => 'infosecnexus',
		'version'        => $version,
		'new_version'    => $version,
		'url'            => release_value( $release, 'homepage', home_url( '/' ) ),
		'package'        => $package,
		'tested'         => release_value( $release, 'tested', '7.0' ),
		'requires'       => release_value( $release, 'requires', '6.5' ),
		'requires_php'   => release_value( $release, 'requires_php', '8.1' ),
		'last_updated'   => release_value( $release, 'last_updated' ),
		'upgrade_notice' => release_value( $release, 'upgrade_notice' ),
		'autoupdate'     => true,
	);
}

/**
 * Sanitize one release value.
 *
 * @param array<string,mixed> $release Release data.
 * @param string              $key Field key.
 * @param string              $fallback Fallback.
 */
function release_value( array $release, string $key, string $fallback = '' ): string {
	$value = isset( $release[ $key ] ) && is_scalar( $release[ $key ] ) ? (string) $release[ $key ] : $fallback;

	return in_array( $key, array( 'package', 'homepage' ), true ) ? esc_url_raw( $value ) : sanitize_text_field( $value );
}

/**
 * Release modal sections.
 *
 * @param array<string,mixed> $release Release data.
 * @return array<string,string>
 */
function sections( array $release ): array {
	$sections = isset( $release['sections'] ) && is_array( $release['sections'] ) ? $release['sections'] : array();

	return array(
		'description' => wp_kses_post( (string) ( $sections['description'] ?? 'Cybersecurity newsroom theme for InfoSecNexus.' ) ),
		'changelog'   => wp_kses_post( (string) ( $sections['changelog'] ?? 'See the InfoSecNexus release notes for details.' ) ),
	);
}
