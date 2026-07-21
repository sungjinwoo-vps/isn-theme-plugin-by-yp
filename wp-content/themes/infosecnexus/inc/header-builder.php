<?php
/**
 * Responsive newsroom header.
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
	?>
	<header class="site-header" data-site-header>
		<div class="site-header__main">
			<div class="site-header__inner">
				<?php render_logo(); ?>
				<?php render_primary_nav( 'desktop' ); ?>
				<div class="site-header__actions">
					<a class="button header-brief-button" href="<?php echo esc_url( (string) get_value( 'header_button_url' ) ); ?>"><span class="button-label-full"><?php echo esc_html( (string) get_value( 'header_button_label' ) ); ?></span><span class="button-label-short"><?php esc_html_e( 'Daily Brief', 'infosecnexus' ); ?></span></a>
					<button class="icon-button color-mode-toggle" type="button" data-color-mode-toggle aria-label="<?php esc_attr_e( 'Switch to dark mode', 'infosecnexus' ); ?>">
						<span class="color-mode-toggle__icon color-mode-toggle__icon--moon"><?php icon( 'moon' ); ?></span>
						<span class="color-mode-toggle__icon color-mode-toggle__icon--sun"><?php icon( 'sun' ); ?></span>
					</button>
					<button class="icon-button header-search-toggle" type="button" data-search-toggle aria-controls="infosecnexus-search-modal" aria-expanded="false" aria-label="<?php esc_attr_e( 'Open search', 'infosecnexus' ); ?>">
						<?php icon( 'search' ); ?>
					</button>
					<button class="icon-button mobile-menu-toggle" type="button" data-mobile-menu-toggle aria-controls="infosecnexus-mobile-panel" aria-expanded="false" aria-label="<?php esc_attr_e( 'Open menu', 'infosecnexus' ); ?>">
						<?php icon( 'menu' ); ?>
					</button>
				</div>
			</div>
		</div>
	</header>
	<?php render_search_modal(); ?>
	<?php render_mobile_panel(); ?>
	<?php
}

/**
 * Render logo/identity.
 */
function render_logo(): void {
	?>
	<a class="site-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
		<span class="site-brand__mark" aria-hidden="true"><?php icon( 'shield' ); ?></span>
		<span class="site-brand__text">
			<span class="site-title"><?php bloginfo( 'name' ); ?></span>
			<span class="site-description"><?php bloginfo( 'description' ); ?></span>
		</span>
	</a>
	<?php
}

/**
 * Render primary navigation.
 *
 * @param string $variant Navigation variant.
 */
function render_primary_nav( string $variant = 'desktop' ): void {
	$topic_slugs = array(
		'cybersecurity',
		'critical-cves',
		'linux-administration',
		'devops',
		'artificial-intelligence',
		'tutorials',
		'cloud-security',
		'web-security',
		'windows-security',
		'network-security',
	);
	$topics     = array(
		array( 'label' => __( 'Cyber Security', 'infosecnexus' ), 'url' => category_url( 'cybersecurity' ), 'active' => is_category( 'cybersecurity' ) ),
		array( 'label' => __( 'Critical CVEs', 'infosecnexus' ), 'url' => category_url( 'critical-cves' ), 'active' => is_category( 'critical-cves' ) ),
		array( 'label' => __( 'Linux & DevOps', 'infosecnexus' ), 'url' => category_url( 'linux-administration' ), 'active' => is_category( array( 'linux-administration', 'devops' ) ) ),
		array( 'label' => __( 'AI Security', 'infosecnexus' ), 'url' => category_url( 'artificial-intelligence' ), 'active' => is_category( 'artificial-intelligence' ) ),
		array( 'label' => __( 'Tutorials', 'infosecnexus' ), 'url' => category_url( 'tutorials' ), 'active' => is_category( 'tutorials' ) ),
		array( 'label' => __( 'Cloud Security', 'infosecnexus' ), 'url' => category_url( 'cloud-security' ), 'active' => is_category( 'cloud-security' ) ),
		array( 'label' => __( 'Web Security', 'infosecnexus' ), 'url' => category_url( 'web-security' ), 'active' => is_category( 'web-security' ) ),
		array( 'label' => __( 'Windows Security', 'infosecnexus' ), 'url' => category_url( 'windows-security' ), 'active' => is_category( 'windows-security' ) ),
		array( 'label' => __( 'Network Security', 'infosecnexus' ), 'url' => category_url( 'network-security' ), 'active' => is_category( 'network-security' ) ),
	);
	$items = array(
		array( 'label' => __( 'Home', 'infosecnexus' ), 'url' => home_url( '/' ), 'active' => is_front_page() ),
		array( 'label' => __( 'Topics', 'infosecnexus' ), 'url' => category_url( 'cybersecurity' ), 'active' => is_category( $topic_slugs ), 'children' => $topics ),
		array( 'label' => __( 'About', 'infosecnexus' ), 'url' => page_url( 'about' ), 'active' => is_page( 'about' ) ),
		array( 'label' => __( 'Contact', 'infosecnexus' ), 'url' => page_url( 'contact' ), 'active' => is_page( 'contact' ) ),
	);
	?>
	<nav class="site-nav site-nav--<?php echo esc_attr( $variant ); ?>" aria-label="<?php esc_attr_e( 'Primary navigation', 'infosecnexus' ); ?>">
		<ul>
			<?php foreach ( $items as $item ) : ?>
				<?php $children = isset( $item['children'] ) && is_array( $item['children'] ) ? $item['children'] : array(); ?>
				<li class="<?php echo $children ? 'has-submenu' : ''; ?>">
					<a class="<?php echo $item['active'] ? 'is-active' : ''; ?>" href="<?php echo esc_url( $item['url'] ); ?>"<?php echo $children ? ' aria-haspopup="true"' : ''; ?>>
						<?php echo esc_html( $item['label'] ); ?>
					</a>
					<?php if ( $children ) : ?>
						<ul class="sub-menu">
							<?php foreach ( $children as $child ) : ?>
								<li>
									<a class="<?php echo $child['active'] ? 'is-active' : ''; ?>" href="<?php echo esc_url( $child['url'] ); ?>">
										<?php echo esc_html( $child['label'] ); ?>
									</a>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	</nav>
	<?php
}

/**
 * Return a category URL with a stable fallback.
 *
 * @param string $slug Category slug.
 * @return string
 */
function category_url( string $slug ): string {
	$category = get_category_by_slug( $slug );
	if ( $category ) {
		return get_category_link( $category );
	}

	return add_query_arg( 's', rawurlencode( str_replace( '-', ' ', $slug ) ), home_url( '/' ) );
}

/**
 * Return a page URL with a stable fallback.
 *
 * @param string $slug Page slug.
 * @return string
 */
function page_url( string $slug ): string {
	$page = get_page_by_path( $slug );
	if ( $page ) {
		return get_permalink( $page );
	}

	return home_url( '/' . trim( $slug, '/' ) . '/' );
}

/**
 * Render social links.
 */
function render_social_links(): void {
	$links = array_filter(
		array(
			'X'        => (string) get_value( 'social_x' ),
			'GitHub'   => (string) get_value( 'social_github' ),
			'LinkedIn' => (string) get_value( 'social_linkedin' ),
			'YouTube'  => (string) get_value( 'social_youtube' ),
		)
	);

	if ( empty( $links ) ) {
		return;
	}
	?>
	<nav class="social-links" aria-label="<?php esc_attr_e( 'Social profiles', 'infosecnexus' ); ?>">
		<?php foreach ( $links as $label => $url ) : ?>
			<a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $label ); ?></a>
		<?php endforeach; ?>
	</nav>
	<?php
}

/**
 * Render mobile panel.
 */
function render_mobile_panel(): void {
	?>
	<div class="mobile-panel" id="infosecnexus-mobile-panel" hidden data-mobile-panel>
		<div class="mobile-panel__backdrop" data-mobile-menu-close></div>
		<div class="mobile-panel__dialog" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Mobile menu', 'infosecnexus' ); ?>">
			<button class="icon-button mobile-panel__close" type="button" data-mobile-menu-close aria-label="<?php esc_attr_e( 'Close menu', 'infosecnexus' ); ?>">
				<?php icon( 'close' ); ?>
			</button>
			<?php render_logo(); ?>
			<?php render_primary_nav( 'mobile' ); ?>
			<button class="color-mode-toggle color-mode-toggle--mobile" type="button" data-color-mode-toggle aria-label="<?php esc_attr_e( 'Switch to dark mode', 'infosecnexus' ); ?>">
				<span class="color-mode-toggle__icon color-mode-toggle__icon--moon"><?php icon( 'moon' ); ?></span>
				<span class="color-mode-toggle__icon color-mode-toggle__icon--sun"><?php icon( 'sun' ); ?></span>
				<span data-color-mode-label><?php esc_html_e( 'Dark mode', 'infosecnexus' ); ?></span>
			</button>
			<a class="button mobile-panel__brief" href="<?php echo esc_url( (string) get_value( 'header_button_url' ) ); ?>"><?php echo esc_html( (string) get_value( 'header_button_label' ) ); ?></a>
			<div class="mobile-panel__search">
				<?php get_search_form(); ?>
			</div>
		</div>
	</div>
	<?php
}

/**
 * Render search modal.
 */
function render_search_modal(): void {
	$quick_links = array(
		__( 'Critical CVEs', 'infosecnexus' ) => category_url( 'critical-cves' ),
		__( 'Cyber Security', 'infosecnexus' ) => category_url( 'cybersecurity' ),
		__( 'Linux & DevOps', 'infosecnexus' ) => category_url( 'linux-administration' ),
		__( 'AI Security', 'infosecnexus' ) => category_url( 'artificial-intelligence' ),
	);
	?>
	<div class="search-modal" id="infosecnexus-search-modal" hidden data-search-modal>
		<button class="search-modal__backdrop" type="button" data-search-close aria-label="<?php esc_attr_e( 'Close search', 'infosecnexus' ); ?>"></button>
		<div class="search-modal__panel" role="dialog" aria-modal="true" aria-labelledby="infosecnexus-search-title">
			<button class="icon-button search-modal__close" type="button" data-search-close aria-label="<?php esc_attr_e( 'Close search', 'infosecnexus' ); ?>">
				<?php icon( 'close' ); ?>
			</button>
			<p class="search-modal__eyebrow"><?php esc_html_e( 'Search InfoSecNexus', 'infosecnexus' ); ?></p>
			<h2 id="infosecnexus-search-title"><?php esc_html_e( 'Search blog posts, CVE notes, guides, and briefings', 'infosecnexus' ); ?></h2>
			<?php get_search_form(); ?>
			<div class="search-modal__quick">
				<span><?php esc_html_e( 'Popular:', 'infosecnexus' ); ?></span>
				<?php foreach ( $quick_links as $label => $url ) : ?>
					<a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $label ); ?></a>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
	<?php
}

/**
 * Render an inline SVG icon.
 *
 * @param string $name Icon name.
 */
function icon( string $name ): void {
	$common = 'aria-hidden="true" focusable="false"';

	switch ( $name ) {
		case 'shield':
			echo '<svg ' . $common . ' viewBox="0 0 48 56"><path fill="#0b1d4d" d="M24 2 5 9v16c0 13 8 23 19 29 11-6 19-16 19-29V9L24 2z"/><path fill="#0b72ff" d="M24 8 11 13v12c0 9 5 17 13 22 8-5 13-13 13-22V13L24 8z"/><path fill="#fff" d="m21 32-7-7 4-4 4 4 9-11 5 4-14 16z"/></svg>';
			break;
		case 'alert':
			echo '<svg ' . $common . ' viewBox="0 0 24 24"><path d="M12 3 2 21h20L12 3z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M12 9v5M12 17h.01" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>';
			break;
		case 'search':
			echo '<svg ' . $common . ' viewBox="0 0 24 24"><circle cx="11" cy="11" r="7" fill="none" stroke="currentColor" stroke-width="2"/><path d="m16.5 16.5 4 4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>';
			break;
		case 'moon':
			echo '<svg ' . $common . ' viewBox="0 0 24 24"><path d="M20.5 14.3A7.7 7.7 0 0 1 9.7 3.5a8.5 8.5 0 1 0 10.8 10.8z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>';
			break;
		case 'sun':
			echo '<svg ' . $common . ' viewBox="0 0 24 24"><circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="2"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>';
			break;
		case 'menu':
			echo '<svg ' . $common . ' viewBox="0 0 24 24"><path d="M4 7h16M4 12h16M4 17h16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>';
			break;
		case 'close':
			echo '<svg ' . $common . ' viewBox="0 0 24 24"><path d="m6 6 12 12M18 6 6 18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>';
			break;
		case 'arrow-right':
			echo '<svg ' . $common . ' viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
			break;
	}
}
