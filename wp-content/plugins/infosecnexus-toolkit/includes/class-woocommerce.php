<?php
/**
 * WooCommerce presentation extensions.
 *
 * @package InfoSecNexusToolkit
 */

declare(strict_types=1);

namespace InfoSecNexus\Toolkit;

/**
 * WooCommerce module.
 */
final class WooCommerce {
	/**
	 * Register hooks.
	 */
	public static function boot(): void {
		if ( ! module_enabled( 'woocommerce' ) ) {
			return;
		}
		add_action( 'wp', array( __CLASS__, 'hooks' ) );
	}

	/**
	 * Add WooCommerce hooks only when active.
	 */
	public static function hooks(): void {
		if ( ! woocommerce_active() ) {
			return;
		}
		add_action( 'woocommerce_before_shop_loop_item_title', array( __CLASS__, 'badge' ), 9 );
		add_action( 'template_redirect', array( __CLASS__, 'recently_viewed' ) );
		add_shortcode( 'infosecnexus_mini_cart', array( __CLASS__, 'mini_cart_shortcode' ) );
	}

	/**
	 * Product badge.
	 */
	public static function badge(): void {
		global $product;
		if ( ! $product instanceof \WC_Product ) {
			return;
		}
		if ( $product->is_on_sale() ) {
			echo '<span class="isnx-product-badge">' . esc_html__( 'Sale', 'infosecnexus-toolkit' ) . '</span>';
		} elseif ( ! $product->is_in_stock() ) {
			echo '<span class="isnx-product-badge isnx-product-badge--muted">' . esc_html__( 'Out of stock', 'infosecnexus-toolkit' ) . '</span>';
		}
	}

	/**
	 * Track recently viewed products using a bounded cookie.
	 */
	public static function recently_viewed(): void {
		if ( ! is_singular( 'product' ) ) {
			return;
		}
		$product_id = get_queried_object_id();
		$ids        = isset( $_COOKIE['isnx_recent_products'] ) ? array_map( 'absint', explode( ',', sanitize_text_field( wp_unslash( $_COOKIE['isnx_recent_products'] ) ) ) ) : array();
		$ids        = array_values( array_unique( array_filter( array_merge( array( $product_id ), $ids ) ) ) );
		$ids        = array_slice( $ids, 0, 8 );
		setcookie( 'isnx_recent_products', implode( ',', $ids ), time() + MONTH_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
	}

	/**
	 * Mini cart shortcode.
	 *
	 * @return string
	 */
	public static function mini_cart_shortcode(): string {
		if ( ! woocommerce_active() ) {
			return '';
		}
		ob_start();
		woocommerce_mini_cart();
		return (string) ob_get_clean();
	}

	/**
	 * Wishlist URL placeholder based on a real page if present.
	 *
	 * @return string
	 */
	public static function wishlist_url(): string {
		$page = get_page_by_path( 'wishlist' );
		return $page ? get_permalink( $page ) : wc_get_page_permalink( 'shop' );
	}

	/**
	 * Compare URL placeholder based on a real page if present.
	 *
	 * @return string
	 */
	public static function compare_url(): string {
		$page = get_page_by_path( 'compare' );
		return $page ? get_permalink( $page ) : wc_get_page_permalink( 'shop' );
	}
}
