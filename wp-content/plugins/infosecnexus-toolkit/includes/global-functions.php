<?php
/**
 * Public integration functions.
 *
 * @package InfoSecNexusToolkit
 */

declare(strict_types=1);

use InfoSecNexus\Toolkit\Content_Blocks;
use InfoSecNexus\Toolkit\Newsletter;
use InfoSecNexus\Toolkit\Sidebars;
use InfoSecNexus\Toolkit\WooCommerce;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'infosecnexus_toolkit_render_location' ) ) {
	/**
	 * Render a toolkit location.
	 *
	 * @param string $location Location.
	 * @return bool
	 */
	function infosecnexus_toolkit_render_location( string $location ): bool {
		return Content_Blocks::render_location( $location );
	}
}

if ( ! function_exists( 'infosecnexus_toolkit_render_sidebar' ) ) {
	/**
	 * Render conditional sidebar.
	 *
	 * @return bool
	 */
	function infosecnexus_toolkit_render_sidebar(): bool {
		return Sidebars::render();
	}
}

if ( ! function_exists( 'infosecnexus_toolkit_newsletter_form' ) ) {
	/**
	 * Render newsletter form.
	 */
	function infosecnexus_toolkit_newsletter_form(): void {
		Newsletter::render_form();
	}
}

if ( ! function_exists( 'infosecnexus_toolkit_render_related_posts' ) ) {
	/**
	 * Render related posts through toolkit.
	 */
	function infosecnexus_toolkit_render_related_posts(): void {
		\InfoSecNexus\Toolkit\Related_Posts::render();
	}
}

if ( ! function_exists( 'infosecnexus_toolkit_wishlist_url' ) ) {
	/**
	 * Wishlist URL.
	 *
	 * @return string
	 */
	function infosecnexus_toolkit_wishlist_url(): string {
		return WooCommerce::wishlist_url();
	}
}

if ( ! function_exists( 'infosecnexus_toolkit_compare_url' ) ) {
	/**
	 * Compare URL.
	 *
	 * @return string
	 */
	function infosecnexus_toolkit_compare_url(): string {
		return WooCommerce::compare_url();
	}
}
