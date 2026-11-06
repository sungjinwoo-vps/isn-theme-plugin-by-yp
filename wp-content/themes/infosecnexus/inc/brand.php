<?php
/**
 * Original InfoSecNexus identity for the ivory editorial design.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

namespace InfoSecNexus\Theme\Brand;

/** Register the fallback only; installing an attachment is an explicit action. */
function bootstrap(): void {
	add_filter( 'get_site_icon_url', __NAMESPACE__ . '\\site_icon_url', 10, 3 );
}

/** Return a packaged identity asset URL. */
function asset_url( string $file ): string {
	$files = array( 'monogram.svg', 'logo.svg', 'logo.png', 'site-icon.svg', 'site-icon-512.png', 'site-icon-192.png', 'site-icon-180.png', 'site-icon-48.png', 'site-icon-32.png', 'site-icon-16.png', 'favicon.ico' );
	$file  = in_array( $file, $files, true ) ? $file : 'monogram.svg';
	return get_template_directory_uri() . '/assets/brand/' . $file;
}

/** Print the original vector monogram; its parent link supplies the name. */
function mark(): void {
	echo '<svg class="site-brand__monogram" xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 64 64" fill="none" aria-hidden="true" focusable="false"><path fill="currentColor" d="M7 23h9v31H7V23Zm20-13h9v44h-9V10Zm22 0h9v44h-9V10Z"/><path fill="#fa622d" d="M7 10h9v8H7v-8Zm20 0h10l21 33v11H48L27 21V10Z"/></svg>';
}

/** Return a standalone, accessible full wordmark image. */
function logo_image(): string {
	return '<img class="infosecnexus-brand-logo" src="' . esc_url( asset_url( 'logo.svg' ) ) . '" alt="' . esc_attr( get_bloginfo( 'name' ) ) . '" width="520" height="96" decoding="async">';
}

/** Honor a configured WordPress Site Icon, and cover fresh installs too. */
function site_icon_url( string $url, int $size, int $blog_id ): string {
	if ( $blog_id > 0 && $blog_id !== get_current_blog_id() ) {
		return $url;
	}
	$managed = (int) get_option( 'infosecnexus_brand_site_icon', 0 );
	if ( '' !== $url && ( $managed <= 0 || $managed !== (int) get_option( 'site_icon', 0 ) ) ) {
		return $url;
	}
	$width = $size > 192 ? 512 : ( $size > 180 ? 192 : ( $size > 48 ? 180 : ( $size > 32 ? 48 : ( $size > 16 ? 32 : 16 ) ) ) );
	return asset_url( 'site-icon-' . $width . '.png' );
}

/**
 * Install the supplied Site Icon into native WordPress media, only via WP-CLI.
 * This is idempotent and does not read or modify any other attachment.
 *
 * @return int The native attachment ID.
 */
function install_site_icon(): int {
	$id = install_media_asset( 'site-icon-512.png', 'ivory-in-20261106', 'InfoSecNexus site icon' );
	update_post_meta( $id, '_wp_attachment_context', 'site-icon' );
	update_post_meta( $id, '_wp_attachment_image_alt', 'InfoSecNexus IN monogram' );
	update_option( 'site_icon', $id );
	update_option( 'infosecnexus_brand_site_icon', $id, false );
	return $id;
}

/** Install the matching publisher logo and native Customizer selection. */
function install_logo(): int {
	$id = install_media_asset( 'logo.png', 'ivory-wordmark-20261106', 'InfoSecNexus logo' );
	update_post_meta( $id, '_wp_attachment_image_alt', 'InfoSecNexus' );
	set_theme_mod( 'custom_logo', $id );
	set_theme_mod( 'infosecnexus_brand_logo_attachment', $id );
	return $id;
}

/** Import a packaged identity into the media library without network requests. */
function install_media_asset( string $file, string $marker, string $title ): int {
	if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
		throw new \RuntimeException( 'Brand installation is available only to the explicit WP-CLI deployment step.' );
	}
	if ( ! in_array( $file, array( 'logo.png', 'site-icon-512.png' ), true ) ) {
		throw new \RuntimeException( 'Only the packaged logo and site icon can be installed.' );
	}
	$existing = get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'posts_per_page' => 1, 'fields' => 'ids', 'meta_key' => '_infosecnexus_brand_asset', 'meta_value' => $marker, 'suppress_filters' => true ) );
	$id       = $existing ? (int) $existing[0] : 0;
	if ( $id <= 0 ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$source = get_template_directory() . '/assets/brand/' . $file;
		$temp   = wp_tempnam( 'infosecnexus-' . $file );
		if ( ! $temp || ! copy( $source, $temp ) ) {
			throw new \RuntimeException( 'Could not prepare the packaged site icon.' );
		}
		$attachment = media_handle_sideload( array( 'name' => 'infosecnexus-' . $file, 'tmp_name' => $temp, 'type' => 'image/png', 'error' => 0, 'size' => (int) filesize( $source ) ), 0, $title );
		if ( is_wp_error( $attachment ) ) {
			wp_delete_file( $temp );
			throw new \RuntimeException( $attachment->get_error_message() );
		}
		$id = (int) $attachment;
		update_post_meta( $id, '_infosecnexus_brand_asset', $marker );
	}
	return $id;
}
