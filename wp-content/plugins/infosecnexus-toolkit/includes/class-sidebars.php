<?php
/**
 * Conditional sidebars.
 *
 * @package InfoSecNexusToolkit
 */

declare(strict_types=1);

namespace InfoSecNexus\Toolkit;

/**
 * Sidebar module.
 */
final class Sidebars {
	/**
	 * Register hooks.
	 */
	public static function boot(): void {
		if ( ! module_enabled( 'sidebars' ) ) {
			return;
		}
		add_action( 'widgets_init', array( __CLASS__, 'register' ) );
	}

	/**
	 * Register sidebars.
	 */
	public static function register(): void {
		for ( $index = 1; $index <= 3; $index++ ) {
			register_sidebar(
				array(
					'name'          => sprintf(
						/* translators: %d: sidebar number. */
						__( 'InfoSecNexus Conditional Sidebar %d', 'infosecnexus-toolkit' ),
						$index
					),
					'id'            => 'infosecnexus-conditional-' . $index,
					'description'   => __( 'Rendered when its JSON condition set matches the current request.', 'infosecnexus-toolkit' ),
					'before_widget' => '<section id="%1$s" class="widget %2$s">',
					'after_widget'  => '</section>',
					'before_title'  => '<h2 class="widget-title">',
					'after_title'   => '</h2>',
				)
			);
		}
	}

	/**
	 * Render first matching sidebar.
	 *
	 * @return bool True when rendered.
	 */
	public static function render(): bool {
		$json = (string) option( 'sidebar_conditions', '' );
		$sets = json_decode( $json, true );
		if ( ! is_array( $sets ) ) {
			return false;
		}

		foreach ( $sets as $set ) {
			if ( ! is_array( $set ) ) {
				continue;
			}
			$sidebar = sanitize_key( (string) ( $set['sidebar'] ?? '' ) );
			if ( ! $sidebar || ! is_active_sidebar( $sidebar ) ) {
				continue;
			}
			if ( Conditions::matches( $set['conditions'] ?? array() ) ) {
				echo '<aside id="secondary" class="widget-area widget-area--conditional" aria-label="' . esc_attr__( 'Conditional sidebar', 'infosecnexus-toolkit' ) . '">';
				dynamic_sidebar( $sidebar );
				echo '</aside>';
				return true;
			}
		}

		return false;
	}
}
