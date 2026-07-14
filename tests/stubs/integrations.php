<?php
/**
 * Static-analysis stubs for optional integrations.
 *
 * @package InfoSecNexus
 */

namespace {
	if ( ! defined( 'ABSPATH' ) ) {
		define( 'ABSPATH', __DIR__ );
	}

	if ( ! defined( 'INFOSECNEXUS_VERSION' ) ) {
		define( 'INFOSECNEXUS_VERSION', '0.1.0' );
	}

	if ( ! defined( 'INFOSECNEXUS_TOOLKIT_VERSION' ) ) {
		define( 'INFOSECNEXUS_TOOLKIT_VERSION', '0.1.0' );
	}

	if ( ! defined( 'INFOSECNEXUS_TOOLKIT_URL' ) ) {
		define( 'INFOSECNEXUS_TOOLKIT_URL', 'https://example.test/wp-content/plugins/infosecnexus-toolkit/' );
	}

	if ( ! defined( 'INFOSECNEXUS_TOOLKIT_DIR' ) ) {
		define( 'INFOSECNEXUS_TOOLKIT_DIR', __DIR__ . '/../../wp-content/plugins/infosecnexus-toolkit/' );
	}

	if ( ! defined( 'MONTH_IN_SECONDS' ) ) {
		define( 'MONTH_IN_SECONDS', 2592000 );
	}

	if ( ! defined( 'COOKIEPATH' ) ) {
		define( 'COOKIEPATH', '/' );
	}

	if ( ! defined( 'COOKIE_DOMAIN' ) ) {
		define( 'COOKIE_DOMAIN', '' );
	}

	if ( ! class_exists( 'WooCommerce' ) ) {
		class WooCommerce {}
	}

	if ( ! class_exists( 'WC_Product' ) ) {
		class WC_Product {
			/**
			 * Sale state.
			 *
			 * @return bool
			 */
			public function is_on_sale(): bool {
				return false;
			}

			/**
			 * Stock state.
			 *
			 * @return bool
			 */
			public function is_in_stock(): bool {
				return true;
			}
		}
	}

	if ( ! function_exists( 'WC' ) ) {
		/**
		 * WooCommerce container.
		 *
		 * @return object
		 */
		function WC(): object {
			return (object) array(
				'cart' => new class() {
					/**
					 * Cart count.
					 *
					 * @return int
					 */
					public function get_cart_contents_count(): int {
						return 0;
					}
				},
			);
		}
	}

	if ( ! function_exists( 'wc_get_cart_url' ) ) {
		/**
		 * Cart URL.
		 *
		 * @return string
		 */
		function wc_get_cart_url(): string {
			return 'https://example.test/cart/';
		}
	}

	if ( ! function_exists( 'wc_get_page_permalink' ) ) {
		/**
		 * WooCommerce page URL.
		 *
		 * @param string $page Page key.
		 * @return string
		 */
		function wc_get_page_permalink( string $page ): string {
			return 'https://example.test/' . $page . '/';
		}
	}

	if ( ! function_exists( 'woocommerce_mini_cart' ) ) {
		/**
		 * Mini cart output.
		 */
		function woocommerce_mini_cart(): void {}
	}

	if ( ! function_exists( 'woocommerce_content' ) ) {
		/**
		 * WooCommerce template output.
		 */
		function woocommerce_content(): void {}
	}
}

namespace Elementor {
	if ( ! class_exists( Widget_Base::class ) ) {
		/**
		 * Elementor widget base stub.
		 */
		abstract class Widget_Base {
			/**
			 * Categories.
			 *
			 * @return string[]
			 */
			public function get_categories(): array {
				return array();
			}

			/**
			 * Icon.
			 *
			 * @return string
			 */
			public function get_icon(): string {
				return '';
			}

			/**
			 * Start controls section.
			 *
			 * @param string              $section_id Section ID.
			 * @param array<string,mixed> $args Args.
			 */
			public function start_controls_section( string $section_id, array $args = array() ): void {}

			/**
			 * Add control.
			 *
			 * @param string              $control_id Control ID.
			 * @param array<string,mixed> $args Args.
			 */
			public function add_control( string $control_id, array $args = array() ): void {}

			/**
			 * End controls section.
			 */
			public function end_controls_section(): void {}

			/**
			 * Settings for display.
			 *
			 * @return array<string,mixed>
			 */
			protected function get_settings_for_display(): array {
				return array();
			}
		}
	}

	if ( ! class_exists( Controls_Manager::class ) ) {
		/**
		 * Elementor control manager stub.
		 */
		final class Controls_Manager {
			public const NUMBER = 'number';
			public const TEXT   = 'text';
		}
	}
}
