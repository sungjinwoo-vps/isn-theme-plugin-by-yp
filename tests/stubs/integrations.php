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

	if ( ! defined( 'INFOSECNEXUS_DIR' ) ) {
		define( 'INFOSECNEXUS_DIR', __DIR__ . '/../../wp-content/themes/infosecnexus/' );
	}

	if ( ! defined( 'INFOSECNEXUS_URI' ) ) {
		define( 'INFOSECNEXUS_URI', 'https://example.test/wp-content/themes/infosecnexus/' );
	}

	if ( ! defined( 'INFOSECNEXUS_THEME_TOOLKIT_VERSION' ) ) {
		define( 'INFOSECNEXUS_THEME_TOOLKIT_VERSION', '0.1.0' );
	}

	if ( ! defined( 'INFOSECNEXUS_THEME_TOOLKIT_URL' ) ) {
		define( 'INFOSECNEXUS_THEME_TOOLKIT_URL', 'https://example.test/wp-content/themes/infosecnexus/inc/toolkit/' );
	}

	if ( ! defined( 'INFOSECNEXUS_THEME_TOOLKIT_DIR' ) ) {
		define( 'INFOSECNEXUS_THEME_TOOLKIT_DIR', __DIR__ . '/../../wp-content/themes/infosecnexus/inc/toolkit/' );
	}

	if ( ! defined( 'WPINC' ) ) {
		define( 'WPINC', 'wp-includes' );
	}

	if ( ! defined( 'OBJECT' ) ) {
		define( 'OBJECT', 'OBJECT' );
	}

	if ( ! defined( 'MINUTE_IN_SECONDS' ) ) {
		define( 'MINUTE_IN_SECONDS', 60 );
	}

	if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
		define( 'HOUR_IN_SECONDS', 3600 );
	}

	if ( ! defined( 'DAY_IN_SECONDS' ) ) {
		define( 'DAY_IN_SECONDS', 86400 );
	}

	if ( ! defined( 'MB_IN_BYTES' ) ) {
		define( 'MB_IN_BYTES', 1048576 );
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

	if ( ! class_exists( 'WP_CLI' ) ) {
		/**
		 * WP-CLI facade stub.
		 */
		final class WP_CLI {
			/**
			 * Register a command.
			 *
			 * @param string   $name     Command name.
			 * @param callable $callback Command callback.
			 */
			public static function add_command( string $name, callable $callback ): void {}

			/**
			 * Report command success.
			 *
			 * @param string $message Message.
			 */
			public static function success( string $message ): void {}

			/**
			 * Report a command error.
			 *
			 * @param string $message Message.
			 */
			public static function error( string $message ): void {}
		}
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

namespace WP_CLI\Utils {
	if ( ! function_exists( __NAMESPACE__ . '\\format_items' ) ) {
		/**
		 * Render a WP-CLI item table.
		 *
		 * @param string                    $format Format name.
		 * @param array<int,array<string,mixed>> $items Items.
		 * @param string[]                  $fields Fields.
		 */
		function format_items( string $format, array $items, array $fields ): void {}
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
