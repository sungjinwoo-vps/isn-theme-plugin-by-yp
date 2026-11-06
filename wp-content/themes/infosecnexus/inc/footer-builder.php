<?php
/**
 * Original footer builder.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

namespace InfoSecNexus\Theme\Footer_Builder;

use function InfoSecNexus\Theme\Customizer\get_value;
use function InfoSecNexus\Theme\Header_Builder\render_logo;
use function InfoSecNexus\Theme\Header_Builder\render_social_links;

/**
 * Register hooks.
 */
function bootstrap(): void {
	add_action( 'infosecnexus_footer', __NAMESPACE__ . '\\render' );
}

/**
 * Render footer.
 */
function render(): void {
	if ( function_exists( 'infosecnexus_toolkit_render_location' ) && infosecnexus_toolkit_render_location( 'footer' ) ) {
		return;
	}
	?>
	<footer class="site-footer">
		<div class="cinematic-footer__main">
			<div class="cinematic-footer__identity">
				<?php render_logo(); ?>
				<p><?php esc_html_e( 'A clearer view of cybersecurity. Source-backed analysis and practical guidance for your next informed decision.', 'infosecnexus' ); ?></p>
				<?php render_social_links(); ?>
			</div>
			<nav class="cinematic-footer__links" aria-label="<?php esc_attr_e( 'Security topics', 'infosecnexus' ); ?>">
				<h2><?php esc_html_e( 'Explore intelligence', 'infosecnexus' ); ?></h2>
				<?php
				$topics = array(
					'critical-cves'           => __( 'Critical CVEs', 'infosecnexus' ),
					'cloud-security'          => __( 'Cloud Security', 'infosecnexus' ),
					'linux-administration'    => __( 'Linux & DevOps', 'infosecnexus' ),
					'artificial-intelligence' => __( 'AI Security', 'infosecnexus' ),
					'network-security'        => __( 'Network Security', 'infosecnexus' ),
				);
				foreach ( $topics as $slug => $label ) :
					?>
					<a href="<?php echo esc_url( \InfoSecNexus\Theme\Header_Builder\category_url( $slug ) ); ?>"><?php echo esc_html( $label ); ?></a>
				<?php endforeach; ?>
			</nav>
			<div class="cinematic-footer__links">
				<h2><?php esc_html_e( 'InfoSecNexus', 'infosecnexus' ); ?></h2>
				<?php if ( has_nav_menu( 'footer' ) ) : ?>
					<?php render_menu( 'footer' ); ?>
				<?php else : ?>
					<nav aria-label="<?php esc_attr_e( 'About InfoSecNexus', 'infosecnexus' ); ?>">
						<a href="<?php echo esc_url( \InfoSecNexus\Theme\Header_Builder\page_url( 'blog' ) ); ?>"><?php esc_html_e( 'All intelligence', 'infosecnexus' ); ?></a>
						<a href="<?php echo esc_url( \InfoSecNexus\Theme\Header_Builder\page_url( 'about' ) ); ?>"><?php esc_html_e( 'About', 'infosecnexus' ); ?></a>
						<a href="<?php echo esc_url( \InfoSecNexus\Theme\Header_Builder\page_url( 'contact' ) ); ?>"><?php esc_html_e( 'Contact', 'infosecnexus' ); ?></a>
					</nav>
				<?php endif; ?>
				<a class="cinematic-footer__brief" href="<?php echo esc_url( home_url( '/#daily-cyber-brief' ) ); ?>"><?php esc_html_e( 'Get the Daily Brief', 'infosecnexus' ); ?><span aria-hidden="true"> &rarr;</span></a>
			</div>
		</div>
		<div class="cinematic-footer__bottom">
			<?php render_element( 'copyright' ); ?>
			<?php if ( has_nav_menu( 'legal' ) ) : ?>
				<?php render_menu( 'legal' ); ?>
			<?php else : ?>
				<nav class="footer-nav footer-nav--legal" aria-label="<?php esc_attr_e( 'Legal information', 'infosecnexus' ); ?>">
					<?php foreach ( array( 'privacy-policy' => __( 'Privacy Policy', 'infosecnexus' ), 'terms-and-conditions' => __( 'Terms', 'infosecnexus' ), 'disclaimer' => __( 'Disclaimer', 'infosecnexus' ) ) as $slug => $label ) : ?>
						<a href="<?php echo esc_url( \InfoSecNexus\Theme\Header_Builder\page_url( $slug ) ); ?>"><?php echo esc_html( $label ); ?></a>
					<?php endforeach; ?>
				</nav>
			<?php endif; ?>
		</div>
	</footer>
	<?php
}

/**
 * Render footer row.
 *
 * @param string $row Row key.
 * @param string $elements Element list.
 */
function render_row( string $row, string $elements ): void {
	$items = array_filter( array_map( 'sanitize_key', array_map( 'trim', explode( ',', $elements ) ) ) );
	if ( empty( $items ) ) {
		return;
	}
	?>
	<div class="site-footer__row site-footer__row--<?php echo esc_attr( $row ); ?>">
		<div class="site-footer__inner">
			<?php foreach ( $items as $item ) : ?>
				<div class="footer-element footer-element--<?php echo esc_attr( $item ); ?>">
					<?php render_element( $item ); ?>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
	<?php
}

/**
 * Render footer element.
 *
 * @param string $item Element key.
 */
function render_element( string $item ): void {
	switch ( $item ) {
		case 'logo':
			render_logo();
			break;
		case 'site_identity':
			echo '<strong>' . esc_html( get_bloginfo( 'name' ) ) . '</strong><span>' . esc_html( get_bloginfo( 'description' ) ) . '</span>';
			break;
		case 'copyright':
			$text = str_replace( '{year}', gmdate( 'Y' ), (string) get_value( 'copyright' ) );
			echo '<p>' . esc_html( $text ) . '</p>';
			break;
		case 'navigation':
			render_menu( 'footer' );
			break;
		case 'legal_navigation':
			render_menu( 'legal' );
			break;
		case 'social_links':
			render_social_links();
			break;
		case 'contact':
			echo esc_html( (string) get_value( 'contact_text' ) );
			break;
		case 'search':
			get_search_form();
			break;
		case 'html':
			echo wp_kses_post( (string) get_value( 'header_html' ) );
			break;
		case 'widgets':
			render_widgets();
			break;
		case 'button':
			\InfoSecNexus\Theme\Header_Builder\render_button();
			break;
		case 'elementor_template':
			if ( function_exists( 'infosecnexus_toolkit_render_location' ) ) {
				infosecnexus_toolkit_render_location( 'footer-fragment' );
			}
			break;
		case 'back_to_top':
			break;
		case 'spacer':
			echo '<span class="builder-spacer" aria-hidden="true"></span>';
			break;
		case 'divider':
			echo '<span class="builder-divider" aria-hidden="true"></span>';
			break;
	}
}

/**
 * Render a footer navigation location.
 *
 * @param string $location Menu location.
 */
function render_menu( string $location ): void {
	wp_nav_menu(
		array(
			'theme_location'  => $location,
			'container'       => 'nav',
			'container_class' => 'footer-nav footer-nav--' . sanitize_html_class( $location ),
			'container_aria_label' => 'legal' === $location ? __( 'Legal information', 'infosecnexus' ) : __( 'Footer navigation', 'infosecnexus' ),
			'fallback_cb'     => false,
			'depth'           => 1,
		)
	);
}

/**
 * Render footer widgets.
 */
function render_widgets(): void {
	echo '<div class="footer-widgets">';
	for ( $index = 1; $index <= 4; $index++ ) {
		$sidebar = 'footer-' . $index;
		if ( is_active_sidebar( $sidebar ) ) {
			echo '<div class="footer-widgets__column">';
			dynamic_sidebar( $sidebar );
			echo '</div>';
		}
	}
	echo '</div>';
}
