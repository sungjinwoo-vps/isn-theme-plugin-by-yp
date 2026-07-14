<?php
/**
 * Original responsive header builder.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

namespace InfoSecNexus\Theme\Header_Builder;

use function InfoSecNexus\Theme\Customizer\get_value;

/**
 * Register hooks.
 */
function bootstrap(): void {
	add_action( 'infosecnexus_header', __NAMESPACE__ . '\\render' );
}

/**
 * Render the active header.
 */
function render(): void {
	if ( function_exists( 'infosecnexus_toolkit_render_location' ) && infosecnexus_toolkit_render_location( 'header' ) ) {
		return;
	}

	$classes = array( 'site-header' );
	if ( get_value( 'header_shrink' ) ) {
		$classes[] = 'site-header--shrink';
	}
	if ( get_value( 'header_reveal' ) ) {
		$classes[] = 'site-header--reveal';
	}
	?>
	<header class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" data-site-header>
		<?php do_action( 'infosecnexus_before_header_rows' ); ?>
		<?php render_row( 'top', (string) get_value( 'header_top_elements' ) ); ?>
		<?php render_row( 'main', (string) get_value( 'header_main_elements' ) ); ?>
		<?php render_row( 'bottom', (string) get_value( 'header_bottom_elements' ) ); ?>
		<?php do_action( 'infosecnexus_after_header_rows' ); ?>
	</header>
	<?php render_mobile_panel(); ?>
	<?php
}

/**
 * Render a row.
 *
 * @param string $row Row key.
 * @param string $elements Comma-separated elements.
 */
function render_row( string $row, string $elements ): void {
	$items = array_filter( array_map( 'sanitize_key', array_map( 'trim', explode( ',', $elements ) ) ) );
	if ( empty( $items ) ) {
		return;
	}
	?>
	<div class="site-header__row site-header__row--<?php echo esc_attr( $row ); ?>">
		<div class="site-header__inner">
			<?php foreach ( $items as $item ) : ?>
				<?php render_element( $item ); ?>
			<?php endforeach; ?>
		</div>
	</div>
	<?php
}

/**
 * Render a header element.
 *
 * @param string $item Element key.
 */
function render_element( string $item ): void {
	echo '<div class="header-element header-element--' . esc_attr( $item ) . '">';
	switch ( $item ) {
		case 'logo':
			render_logo();
			break;
		case 'site_title':
			render_site_title();
			break;
		case 'tagline':
			render_tagline();
			break;
		case 'primary_menu':
			render_menu( 'primary' );
			break;
		case 'secondary_menu':
			render_menu( 'secondary' );
			break;
		case 'button':
			render_button();
			break;
		case 'search':
			get_search_form();
			break;
		case 'live_search_trigger':
			echo '<button class="icon-button" type="button" data-live-search-open aria-label="' . esc_attr__( 'Open live search', 'infosecnexus' ) . '">ÃƒÂ¢Ã…â€™Ã¢â‚¬Â¢</button>';
			break;
		case 'social_links':
			render_social_links();
			break;
		case 'html':
			echo wp_kses_post( (string) get_value( 'header_html' ) );
			break;
		case 'contact':
			echo esc_html( (string) get_value( 'contact_text' ) );
			break;
		case 'widget_area':
			if ( is_active_sidebar( 'header-builder' ) ) {
				dynamic_sidebar( 'header-builder' );
			}
			break;
		case 'divider':
			echo '<span class="builder-divider" aria-hidden="true"></span>';
			break;
		case 'spacer':
			echo '<span class="builder-spacer" aria-hidden="true"></span>';
			break;
		case 'user_account':
			render_user_account();
			break;
		case 'mobile_trigger':
			echo '<button class="icon-button mobile-menu-toggle" type="button" data-mobile-menu-toggle aria-controls="infosecnexus-mobile-panel" aria-expanded="false"><span class="screen-reader-text">' . esc_html__( 'Open menu', 'infosecnexus' ) . '</span><span aria-hidden="true">ÃƒÂ¢Ã‹Å“Ã‚Â°</span></button>';
			break;
		case 'color_mode_switch':
			echo '<button class="color-mode-toggle" type="button" data-color-mode-toggle aria-label="' . esc_attr__( 'Toggle color mode', 'infosecnexus' ) . '"><span aria-hidden="true">ÃƒÂ¢Ã¢â‚¬â€Ã‚Â</span></button>';
			break;
		case 'cart':
			render_cart();
			break;
		case 'wishlist':
			if ( function_exists( 'infosecnexus_toolkit_wishlist_url' ) ) {
				echo '<a class="header-link" href="' . esc_url( infosecnexus_toolkit_wishlist_url() ) . '">' . esc_html__( 'Wishlist', 'infosecnexus' ) . '</a>';
			}
			break;
		case 'comparison':
			if ( function_exists( 'infosecnexus_toolkit_compare_url' ) ) {
				echo '<a class="header-link" href="' . esc_url( infosecnexus_toolkit_compare_url() ) . '">' . esc_html__( 'Compare', 'infosecnexus' ) . '</a>';
			}
			break;
	}
	echo '</div>';
}

/**
 * Render logo.
 */
function render_logo(): void {
	if ( has_custom_logo() ) {
		the_custom_logo();
		return;
	}
	render_site_title();
}

/**
 * Render site title.
 */
function render_site_title(): void {
	$tag = is_front_page() && is_home() ? 'h1' : 'p';
	printf(
		'<%1$s class="site-title"><a href="%2$s" rel="home">%3$s</a></%1$s>',
		tag_escape( $tag ),
		esc_url( home_url( '/' ) ),
		esc_html( get_bloginfo( 'name' ) )
	);
}

/**
 * Render tagline.
 */
function render_tagline(): void {
	$description = get_bloginfo( 'description', 'display' );
	if ( $description ) {
		echo '<p class="site-description">' . esc_html( $description ) . '</p>';
	}
}

/**
 * Render a menu.
 *
 * @param string $location Menu location.
 */
function render_menu( string $location ): void {
	wp_nav_menu(
		array(
			'theme_location'  => $location,
			'container'       => 'nav',
			'container_class' => 'site-nav site-nav--' . $location,
			'fallback_cb'     => false,
			'depth'           => 3,
		)
	);
}

/**
 * Render CTA button.
 */
function render_button(): void {
	$label = (string) get_value( 'header_button_label' );
	$url   = (string) get_value( 'header_button_url' );
	if ( $label && $url ) {
		echo '<a class="button button--small" href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
	}
}

/**
 * Render social links.
 */
function render_social_links(): void {
	$profiles = array(
		'social_x'        => 'X',
		'social_github'   => 'GitHub',
		'social_linkedin' => 'LinkedIn',
		'social_youtube'  => 'YouTube',
		'social_rss'      => 'RSS',
	);

	echo '<nav class="social-links" aria-label="' . esc_attr__( 'Social profiles', 'infosecnexus' ) . '">';
	foreach ( $profiles as $key => $label ) {
		$url = (string) get_value( $key );
		if ( $url ) {
			echo '<a href="' . esc_url( $url ) . '" rel="me noopener">' . esc_html( $label ) . '</a>';
		}
	}
	echo '</nav>';
}

/**
 * Render account link.
 */
function render_user_account(): void {
	if ( is_user_logged_in() ) {
		echo '<a class="header-link" href="' . esc_url( admin_url( 'profile.php' ) ) . '">' . esc_html__( 'Account', 'infosecnexus' ) . '</a>';
		return;
	}
	echo '<a class="header-link" href="' . esc_url( wp_login_url( get_permalink() ) ) . '">' . esc_html__( 'Log in', 'infosecnexus' ) . '</a>';
}

/**
 * Render WooCommerce cart link.
 */
function render_cart(): void {
	if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'wc_get_cart_url' ) ) {
		return;
	}
	$count = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
	echo '<a class="header-link header-cart" href="' . esc_url( wc_get_cart_url() ) . '">' . esc_html__( 'Cart', 'infosecnexus' ) . ' <span aria-label="' . esc_attr__( 'Cart item count', 'infosecnexus' ) . '">' . esc_html( (string) $count ) . '</span></a>';
}

/**
 * Render mobile panel.
 */
function render_mobile_panel(): void {
	?>
	<div class="mobile-panel" id="infosecnexus-mobile-panel" hidden data-mobile-panel>
		<div class="mobile-panel__backdrop" data-mobile-menu-close></div>
		<div class="mobile-panel__dialog" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Mobile menu', 'infosecnexus' ); ?>">
			<button class="icon-button mobile-panel__close" type="button" data-mobile-menu-close aria-label="<?php esc_attr_e( 'Close menu', 'infosecnexus' ); ?>">ÃƒÆ’Ã¢â‚¬â€</button>
			<?php render_logo(); ?>
			<?php render_menu( 'primary' ); ?>
			<?php if ( is_active_sidebar( 'offcanvas-panel' ) ) : ?>
				<div class="mobile-panel__widgets"><?php dynamic_sidebar( 'offcanvas-panel' ); ?></div>
			<?php endif; ?>
		</div>
	</div>
	<?php
}
