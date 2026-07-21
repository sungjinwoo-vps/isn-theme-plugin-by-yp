<?php
/**
 * Private update channel integration.
 *
 * @package InfoSecNexusToolkit
 */

declare(strict_types=1);

namespace InfoSecNexus\Toolkit;

/**
 * Adds WordPress update checks for the companion plugin.
 */
final class Updater {
	private const DEFAULT_MANIFEST_URL = 'https://infosecnexus.com/updates/infosecnexus-releases.json';
	private const MANIFEST_TRANSIENT   = 'infosecnexus_update_manifest';

	/**
	 * Register hooks.
	 */
	public static function boot(): void {
		if ( ! is_admin() && ! ( defined( 'WP_CLI' ) && WP_CLI ) ) {
			return;
		}

		add_filter( 'pre_set_site_transient_update_plugins', array( __CLASS__, 'check_plugin_update' ) );
		add_filter( 'plugins_api', array( __CLASS__, 'plugin_information' ), 10, 3 );
		add_action( 'admin_init', array( __CLASS__, 'handle_check_now' ) );
		add_action( 'upgrader_process_complete', array( __CLASS__, 'clear_cache' ) );
	}

	/**
	 * Default manifest endpoint.
	 */
	public static function default_manifest_url(): string {
		return self::DEFAULT_MANIFEST_URL;
	}

	/**
	 * URL for the manual update check action.
	 */
	public static function check_now_url(): string {
		return wp_nonce_url( admin_url( 'options-general.php?page=infosecnexus-toolkit&infosecnexus_check_updates=1' ), 'infosecnexus_check_updates' );
	}

	/**
	 * Handle manual cache clearing from the settings page.
	 */
	public static function handle_check_now(): void {
		if ( empty( $_GET['infosecnexus_check_updates'] ) || ! current_user_can( 'update_plugins' ) ) {
			return;
		}

		check_admin_referer( 'infosecnexus_check_updates' );
		self::clear_cache();
		wp_safe_redirect( admin_url( 'options-general.php?page=infosecnexus-toolkit&infosecnexus_updates_checked=1' ) );
		exit;
	}

	/**
	 * Clear updater caches.
	 */
	public static function clear_cache(): void {
		delete_site_transient( self::MANIFEST_TRANSIENT );
		delete_site_transient( 'infosecnexus_theme_update_manifest' );
		delete_site_transient( 'update_plugins' );
		delete_site_transient( 'update_themes' );
	}

	/**
	 * Add plugin update data to the WordPress update transient.
	 *
	 * @param mixed $transient Update transient.
	 * @return mixed
	 */
	public static function check_plugin_update( $transient ) {
		if ( ! is_object( $transient ) || ! self::updates_enabled() ) {
			return $transient;
		}

		$release = self::release( 'plugin' );
		$version = self::release_value( $release, 'version' );
		$package = self::release_value( $release, 'package' );

		if ( '' === $version || '' === $package || ! version_compare( INFOSECNEXUS_TOOLKIT_VERSION, $version, '<' ) ) {
			return $transient;
		}

		$plugin_file = plugin_basename( INFOSECNEXUS_TOOLKIT_FILE );

		if ( ! isset( $transient->response ) || ! is_array( $transient->response ) ) {
			$transient->response = array();
		}

		$transient->response[ $plugin_file ] = (object) array(
			'id'            => 'infosecnexus-toolkit',
			'slug'          => 'infosecnexus-toolkit',
			'plugin'        => $plugin_file,
			'new_version'   => $version,
			'url'           => self::release_value( $release, 'homepage', home_url( '/' ) ),
			'package'       => $package,
			'tested'        => self::release_value( $release, 'tested', '7.0' ),
			'requires'      => self::release_value( $release, 'requires', '6.5' ),
			'requires_php'  => self::release_value( $release, 'requires_php', '8.1' ),
			'last_updated'  => self::release_value( $release, 'last_updated' ),
			'upgrade_notice' => self::release_value( $release, 'upgrade_notice' ),
		);

		return $transient;
	}

	/**
	 * Provide plugin details in the update modal.
	 *
	 * @param mixed  $result Existing result.
	 * @param string $action API action.
	 * @param mixed  $args Plugin API args.
	 * @return mixed
	 */
	public static function plugin_information( $result, string $action, $args ) {
		if ( 'plugin_information' !== $action || empty( $args->slug ) || 'infosecnexus-toolkit' !== $args->slug || ! self::updates_enabled() ) {
			return $result;
		}

		$release = self::release( 'plugin' );
		if ( empty( $release ) ) {
			return $result;
		}

		return (object) array(
			'name'          => 'InfoSecNexus Toolkit',
			'slug'          => 'infosecnexus-toolkit',
			'version'       => self::release_value( $release, 'version', INFOSECNEXUS_TOOLKIT_VERSION ),
			'author'        => '<a href="https://infosecnexus.com/">InfoSecNexus</a>',
			'homepage'      => self::release_value( $release, 'homepage', home_url( '/' ) ),
			'requires'      => self::release_value( $release, 'requires', '6.5' ),
			'tested'        => self::release_value( $release, 'tested', '7.0' ),
			'requires_php'  => self::release_value( $release, 'requires_php', '8.1' ),
			'last_updated'  => self::release_value( $release, 'last_updated' ),
			'download_link' => self::release_value( $release, 'package' ),
			'sections'      => self::sections( $release ),
		);
	}

	/**
	 * Whether the private update channel is enabled.
	 */
	private static function updates_enabled(): bool {
		return (bool) option( 'updates_enabled', true );
	}

	/**
	 * Manifest URL from constants or toolkit settings.
	 */
	private static function manifest_url(): string {
		$url = defined( 'INFOSECNEXUS_UPDATE_MANIFEST_URL' ) ? (string) constant( 'INFOSECNEXUS_UPDATE_MANIFEST_URL' ) : (string) option( 'update_manifest_url', self::DEFAULT_MANIFEST_URL );
		$url = esc_url_raw( $url );

		return '' !== $url ? $url : self::DEFAULT_MANIFEST_URL;
	}

	/**
	 * Read the release manifest.
	 *
	 * @return array<string,mixed>
	 */
	private static function manifest(): array {
		$cached = get_site_transient( self::MANIFEST_TRANSIENT );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$response = wp_remote_get(
			self::manifest_url(),
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

		set_site_transient( self::MANIFEST_TRANSIENT, $manifest, HOUR_IN_SECONDS );

		return $manifest;
	}

	/**
	 * Get one release object from the manifest.
	 *
	 * @param string $type Release type.
	 * @return array<string,mixed>
	 */
	private static function release( string $type ): array {
		$manifest = self::manifest();
		$release  = $manifest[ $type ] ?? array();

		if ( ! is_array( $release ) ) {
			return array();
		}

		if ( empty( $release['package'] ) && ! empty( $release['download_url'] ) ) {
			$release['package'] = $release['download_url'];
		}

		return $release;
	}

	/**
	 * Sanitize a scalar release field.
	 *
	 * @param array<string,mixed> $release Release data.
	 * @param string              $key Field key.
	 * @param string              $fallback Fallback.
	 */
	private static function release_value( array $release, string $key, string $fallback = '' ): string {
		$value = isset( $release[ $key ] ) && is_scalar( $release[ $key ] ) ? (string) $release[ $key ] : $fallback;

		return in_array( $key, array( 'package', 'homepage' ), true ) ? esc_url_raw( $value ) : sanitize_text_field( $value );
	}

	/**
	 * Release modal sections.
	 *
	 * @param array<string,mixed> $release Release data.
	 * @return array<string,string>
	 */
	private static function sections( array $release ): array {
		$sections = isset( $release['sections'] ) && is_array( $release['sections'] ) ? $release['sections'] : array();

		return array(
			'description' => wp_kses_post( (string) ( $sections['description'] ?? 'Companion functionality for the InfoSecNexus theme.' ) ),
			'changelog'   => wp_kses_post( (string) ( $sections['changelog'] ?? 'See the InfoSecNexus release notes for details.' ) ),
		);
	}
}
