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
	$brief_url = trim( (string) get_value( 'header_button_url' ) );
	$brief_url = '' !== $brief_url ? $brief_url : home_url( '/#daily-cyber-brief' );
	?>
	<header class="site-header" data-site-header>
		<div class="site-header__main">
			<div class="site-header__inner">
				<?php render_logo(); ?>
				<?php render_primary_nav( 'desktop' ); ?>
				<div class="site-header__actions">
					<button class="icon-button color-mode-toggle header-color-mode-toggle" type="button" data-color-mode-toggle aria-label="<?php esc_attr_e( 'Switch to dark mode', 'infosecnexus' ); ?>" title="<?php esc_attr_e( 'Switch to dark mode', 'infosecnexus' ); ?>">
						<span class="color-mode-toggle__icon color-mode-toggle__icon--moon"><?php icon( 'moon' ); ?></span>
						<span class="color-mode-toggle__icon color-mode-toggle__icon--sun"><?php icon( 'sun' ); ?></span>
					</button>
					<a class="icon-button header-search-toggle" href="<?php echo esc_url( add_query_arg( 's', '', home_url( '/' ) ) ); ?>" data-search-toggle aria-controls="infosecnexus-search-modal" aria-expanded="false" aria-label="<?php esc_attr_e( 'Open search', 'infosecnexus' ); ?>">
						<?php icon( 'search' ); ?>
					</a>
					<a class="button header-brief-button" href="<?php echo esc_url( $brief_url ); ?>" aria-label="<?php esc_attr_e( 'Daily Brief', 'infosecnexus' ); ?>"><span class="header-brief-full"><?php esc_html_e( 'Daily Brief', 'infosecnexus' ); ?></span><span class="header-brief-compact" aria-hidden="true"><?php esc_html_e( 'Brief', 'infosecnexus' ); ?></span><span class="button-arrow" aria-hidden="true"><?php icon( 'arrow-right' ); ?></span></a>
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
	$custom_logo_id = (int) get_theme_mod( 'custom_logo' );
	$brand_logo_id  = (int) get_theme_mod( 'infosecnexus_brand_logo_attachment' );
	$custom_override = $brand_logo_id > 0 && $custom_logo_id > 0 && $custom_logo_id !== $brand_logo_id;
	?>
	<a class="site-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
		<?php if ( $custom_override ) : ?>
			<?php echo wp_get_attachment_image( $custom_logo_id, 'full', false, array( 'class' => 'site-brand__custom-logo', 'loading' => 'eager', 'decoding' => 'async', 'style' => 'width:auto;height:auto;max-width:180px;max-height:42px' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php else : ?>
		<span class="site-brand__mark" aria-hidden="true">
			<?php \InfoSecNexus\Theme\Brand\mark(); ?>
		</span>
		<span class="site-brand__text">
			<span class="site-title"><?php if ( 'InfoSecNexus' === get_bloginfo( 'name' ) ) : ?>InfoSec<span class="site-title__accent">Nexus</span><?php else : ?><?php bloginfo( 'name' ); ?><?php endif; ?></span>
			<span class="site-description"><?php bloginfo( 'description' ); ?></span>
		</span>
		<?php endif; ?>
	</a>
	<?php
}

/**
 * Render the configured primary call-to-action button.
 */
function render_button(): void {
	$label = trim( (string) get_value( 'header_button_label' ) );
	$url   = trim( (string) get_value( 'header_button_url' ) );
	if ( '' === $label || '' === $url ) {
		return;
	}

	echo '<a class="button" href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
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
		array( 'label' => __( 'All Briefings', 'infosecnexus' ), 'url' => page_url( 'blog' ), 'active' => is_page( 'blog' ) ),
	);
	$topic_labels = array(
		'cybersecurity' => __( 'Cyber Security', 'infosecnexus' ),
		'critical-cves' => __( 'Critical CVEs', 'infosecnexus' ),
		'linux-administration' => __( 'Linux & DevOps', 'infosecnexus' ),
		'devops' => __( 'DevSecOps', 'infosecnexus' ),
		'artificial-intelligence' => __( 'AI Security', 'infosecnexus' ),
		'tutorials' => __( 'Tutorials', 'infosecnexus' ),
		'cloud-security' => __( 'Cloud Security', 'infosecnexus' ),
		'web-security' => __( 'Web Security', 'infosecnexus' ),
		'windows-security' => __( 'Windows Security', 'infosecnexus' ),
		'network-security' => __( 'Network Security', 'infosecnexus' ),
	);
	foreach ( $topic_labels as $slug => $label ) {
		$category = get_category_by_slug( $slug );
		if ( $category instanceof \WP_Term && \InfoSecNexus\Theme\SEO\category_has_active_posts( (int) $category->term_id ) ) {
			$topics[] = array( 'label' => $label, 'url' => category_url( $slug ), 'active' => is_category( $slug ) );
		}
	}
	$items = array(
		array( 'label' => __( 'Home', 'infosecnexus' ), 'url' => home_url( '/' ), 'active' => is_front_page() ),
		array( 'label' => __( 'Intelligence', 'infosecnexus' ), 'url' => page_url( 'blog' ), 'active' => is_page( 'blog' ) || is_category( $topic_slugs ), 'children' => $topics ),
		array( 'label' => __( 'Critical CVEs', 'infosecnexus' ), 'url' => category_url( 'critical-cves' ), 'active' => is_category( 'critical-cves' ) ),
		array( 'label' => __( 'About', 'infosecnexus' ), 'url' => page_url( 'about' ), 'active' => is_page( 'about' ) ),
		array( 'label' => __( 'Contact', 'infosecnexus' ), 'url' => page_url( 'contact' ), 'active' => is_page( 'contact' ) ),
	);
	?>
	<nav class="site-nav site-nav--<?php echo esc_attr( $variant ); ?>" aria-label="<?php esc_attr_e( 'Primary navigation', 'infosecnexus' ); ?>">
		<ul>
			<?php foreach ( $items as $item ) : ?>
				<?php
				$children   = isset( $item['children'] ) && is_array( $item['children'] ) ? $item['children'] : array();
				$submenu_id = 'infosecnexus-' . $variant . '-' . sanitize_title( $item['label'] ) . '-topics';
				?>
				<li class="<?php echo $children ? 'has-submenu' : ''; ?>">
					<a class="<?php echo $item['active'] ? 'is-active' : ''; ?>" href="<?php echo esc_url( $item['url'] ); ?>"<?php echo $children && 'desktop' === $variant ? ' aria-haspopup="true"' : ''; ?><?php echo $item['active'] && ( ! $children || is_page( 'blog' ) ) ? ' aria-current="page"' : ''; ?>>
						<?php echo esc_html( $item['label'] ); ?>
					</a>
					<?php if ( $children ) : ?>
						<?php if ( 'mobile' === $variant ) : ?>
							<button class="icon-button mobile-submenu-toggle" type="button" data-mobile-submenu-toggle aria-expanded="false" aria-controls="<?php echo esc_attr( $submenu_id ); ?>" aria-label="<?php esc_attr_e( 'Toggle intelligence topics', 'infosecnexus' ); ?>" hidden>
								<?php icon( 'chevron-down' ); ?>
							</button>
						<?php endif; ?>
						<ul class="sub-menu" id="<?php echo esc_attr( $submenu_id ); ?>">
							<?php foreach ( $children as $child ) : ?>
								<li>
									<a class="<?php echo $child['active'] ? 'is-active' : ''; ?>" href="<?php echo esc_url( $child['url'] ); ?>"<?php echo $child['active'] ? ' aria-current="page"' : ''; ?>>
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
 * Render configured social links.
 *
 * @param string $class_name Optional additional navigation class.
 */
function render_social_links( string $class_name = '' ): void {
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
	<nav class="social-links<?php echo '' !== $class_name ? ' ' . esc_attr( sanitize_html_class( $class_name ) ) : ''; ?>" aria-label="<?php esc_attr_e( 'Social profiles', 'infosecnexus' ); ?>">
		<?php foreach ( $links as $label => $url ) : ?>
			<a href="<?php echo esc_url( $url ); ?>" rel="me noopener noreferrer"><?php echo esc_html( $label ); ?></a>
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
	$quick_links = array();
	foreach ( \InfoSecNexus\Theme\Cinematic_Design\topics() as $topic ) {
		$quick_links[ $topic['label'] ] = category_url( $topic['slug'] );
		if ( count( $quick_links ) >= 4 ) { break; }
	}
	?>
	<div class="search-modal" id="infosecnexus-search-modal" hidden data-search-modal>
		<button class="search-modal__backdrop" type="button" data-search-close aria-label="<?php esc_attr_e( 'Close search', 'infosecnexus' ); ?>"></button>
		<div class="search-modal__panel" role="dialog" aria-modal="true" aria-labelledby="infosecnexus-search-title">
			<button class="icon-button search-modal__close" type="button" data-search-close aria-label="<?php esc_attr_e( 'Close search', 'infosecnexus' ); ?>">
				<?php icon( 'close' ); ?>
			</button>
			<div class="search-modal__layout">
				<div class="search-modal__copy">
					<p class="search-modal__eyebrow"><?php esc_html_e( 'Search InfoSecNexus', 'infosecnexus' ); ?></p>
					<h2 id="infosecnexus-search-title"><?php esc_html_e( 'Find actionable security intelligence', 'infosecnexus' ); ?></h2>
					<p><?php esc_html_e( 'Search published blog posts, CVE notes, guides, and live briefings.', 'infosecnexus' ); ?></p>
					<?php get_search_form(); ?>
					<div class="search-modal__quick">
						<span><?php esc_html_e( 'Explore:', 'infosecnexus' ); ?></span>
						<?php foreach ( $quick_links as $label => $url ) : ?>
							<a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $label ); ?></a>
						<?php endforeach; ?>
					</div>
					<div class="search-modal__recent">
						<strong><?php esc_html_e( 'Recent searches', 'infosecnexus' ); ?></strong>
						<div data-recent-searches hidden></div>
					</div>
				</div>
				<div class="search-modal__visual" aria-hidden="true">
					<?php echo \InfoSecNexus\Theme\Anime_Design\asset_image( 'ai', array( 'alt' => '', 'sizes' => '(max-width: 760px) 0px, 380px' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span><?php esc_html_e( 'Threat research desk', 'infosecnexus' ); ?></span>
				</div>
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
			echo '<svg ' . $common . ' viewBox="0 0 24 24"><path d="m12 3 8 3v6c0 5-4 8-8 10-4-2-8-5-8-10V6l8-3Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="m8 12 3 3 5-6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';
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
		case 'chevron-down':
			echo '<svg ' . $common . ' viewBox="0 0 24 24"><path d="m6 9 6 6 6-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
			break;
		case 'layers':
			echo '<svg ' . $common . ' viewBox="0 0 24 24"><path d="m12 3 10 5-10 5L2 8l10-5zM2 12l10 5 10-5M2 16l10 5 10-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';
			break;
		case 'book':
			echo '<svg ' . $common . ' viewBox="0 0 24 24"><path d="M12 5c-3-2-6-2-10-1v15c4-1 7-1 10 1 3-2 6-2 10-1V4c-4-1-7-1-10 1zm0 0v15" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';
			break;
		case 'calendar':
			echo '<svg ' . $common . ' viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="16" rx="3" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M7 3v4M17 3v4M3 10h18M7 14h3M14 14h3M7 17h3" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>';
			break;
	}
}
