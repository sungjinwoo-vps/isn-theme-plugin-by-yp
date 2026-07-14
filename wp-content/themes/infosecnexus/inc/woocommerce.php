<?php
/**
 * WooCommerce presentation hooks.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

namespace InfoSecNexus\Theme\WooCommerce;

/**
 * Register hooks.
 */
function bootstrap(): void {
	add_action( 'after_setup_theme', __NAMESPACE__ . '\\theme_support' );
	add_filter( 'woocommerce_output_related_products_args', __NAMESPACE__ . '\\related_products_args' );
}

/**
 * Add WooCommerce support.
 */
function theme_support(): void {
	add_theme_support(
		'woocommerce',
		array(
			'thumbnail_image_width' => 480,
			'single_image_width'    => 760,
			'product_grid'          => array(
				'default_rows'    => 3,
				'min_rows'        => 2,
				'max_rows'        => 6,
				'default_columns' => 3,
				'min_columns'     => 2,
				'max_columns'     => 4,
			),
		)
	);
}

/**
 * Tune related product presentation.
 *
 * @param array<string,mixed> $args Args.
 * @return array<string,mixed>
 */
function related_products_args( array $args ): array {
	$args['posts_per_page'] = 3;
	$args['columns']        = 3;
	return $args;
}
