<?php
/**
 * Front page template.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

get_header();

$asset = static function ( string $file ): string {
	return get_template_directory_uri() . '/assets/images/' . $file;
};

$critical_url = \InfoSecNexus\Theme\Header_Builder\category_url( 'critical-cves' );
$cyber_url    = \InfoSecNexus\Theme\Header_Builder\category_url( 'cybersecurity' );
$linux_url    = \InfoSecNexus\Theme\Header_Builder\category_url( 'linux-administration' );
$ai_url       = \InfoSecNexus\Theme\Header_Builder\category_url( 'artificial-intelligence' );

$hero_url = (string) \InfoSecNexus\Theme\Customizer\get_value( 'home_hero_button_url' );
if ( '' === $hero_url ) {
	$hero_url = $critical_url;
}

$latest_cards = array(
	array(
		'title'    => __( 'Microsoft Products Reach End of Support', 'infosecnexus' ),
		'excerpt'  => __( 'Review exposure and mitigation options before unsupported systems become easy targets.', 'infosecnexus' ),
		'category' => __( 'Cyber Security', 'infosecnexus' ),
		'image'    => $asset( 'hero-shield.png' ),
		'url'      => $cyber_url,
		'date'     => __( 'July 16, 2026', 'infosecnexus' ),
		'read'     => __( '5 min read', 'infosecnexus' ),
	),
	array(
		'title'    => __( 'Secure Your Cloud: 5 Misconfigurations to Fix', 'infosecnexus' ),
		'excerpt'  => __( 'Common cloud configuration mistakes continue to be a leading cause of breaches.', 'infosecnexus' ),
		'category' => __( 'Cloud Security', 'infosecnexus' ),
		'image'    => $asset( 'cloud-security.png' ),
		'url'      => $cyber_url,
		'date'     => __( 'July 15, 2026', 'infosecnexus' ),
		'read'     => __( '6 min read', 'infosecnexus' ),
	),
	array(
		'title'    => __( 'AI Model Supply Chain Risks on the Rise', 'infosecnexus' ),
		'excerpt'  => __( 'New research reveals vulnerabilities in model dependencies and third-party components.', 'infosecnexus' ),
		'category' => __( 'AI Security', 'infosecnexus' ),
		'image'    => $asset( 'data-center.png' ),
		'url'      => $ai_url,
		'date'     => __( 'July 14, 2026', 'infosecnexus' ),
		'read'     => __( '4 min read', 'infosecnexus' ),
	),
);

$cves = array(
	array( 'id' => 'CVE-2026-35880', 'name' => __( 'Januscape KVM Escape Vulnerability', 'infosecnexus' ), 'severity' => 'Critical', 'score' => '9.8' ),
	array( 'id' => 'CVE-2026-31324', 'name' => __( 'Windows Win32k Privilege Escalation', 'infosecnexus' ), 'severity' => 'High', 'score' => '8.1' ),
	array( 'id' => 'CVE-2026-29927', 'name' => __( 'Apache HTTP Server HTTP/2 Rapid Reset', 'infosecnexus' ), 'severity' => 'High', 'score' => '7.5' ),
	array( 'id' => 'CVE-2026-27130', 'name' => __( 'Linux Kernel Use-After-Free in Netfilter', 'infosecnexus' ), 'severity' => 'Medium', 'score' => '6.5' ),
	array( 'id' => 'CVE-2026-24712', 'name' => __( 'VMware Tools Information Disclosure', 'infosecnexus' ), 'severity' => 'Low', 'score' => '3.7' ),
);
?>
<main id="primary" class="site-main">
	<section class="home-hero layout-wide-shell" aria-label="<?php esc_attr_e( 'Featured cybersecurity briefings', 'infosecnexus' ); ?>">
		<a class="home-hero__lead" href="<?php echo esc_url( $hero_url ); ?>">
			<img src="<?php echo esc_url( $asset( 'hero-shield.png' ) ); ?>" alt="" loading="eager">
			<span class="home-hero__shade" aria-hidden="true"></span>
			<span class="home-hero__content">
				<span class="severity-pill severity-pill--critical"><?php echo esc_html( (string) \InfoSecNexus\Theme\Customizer\get_value( 'home_hero_badge' ) ); ?></span>
				<span class="home-hero__title"><?php echo esc_html( (string) \InfoSecNexus\Theme\Customizer\get_value( 'home_hero_title' ) ); ?></span>
				<span class="home-hero__excerpt"><?php echo esc_html( (string) \InfoSecNexus\Theme\Customizer\get_value( 'home_hero_excerpt' ) ); ?></span>
				<span class="button button--hero"><?php echo esc_html( (string) \InfoSecNexus\Theme\Customizer\get_value( 'home_hero_button_label' ) ); ?><?php \InfoSecNexus\Theme\Header_Builder\icon( 'arrow-right' ); ?></span>
			</span>
		</a>

		<div class="home-hero__side">
			<a class="side-story" href="<?php echo esc_url( $critical_url ); ?>">
				<span class="side-story__copy">
					<strong><?php esc_html_e( 'Januscape KVM Vulnerability Requires Immediate Patching', 'infosecnexus' ); ?></strong>
					<span><?php esc_html_e( 'A critical flaw could allow attackers to escape the virtual environment.', 'infosecnexus' ); ?></span>
					<span class="story-meta"><?php esc_html_e( 'July 18, 2026', 'infosecnexus' ); ?> <span class="severity-tag severity-tag--critical"><?php esc_html_e( 'Critical', 'infosecnexus' ); ?></span></span>
				</span>
				<img src="<?php echo esc_url( $asset( 'lock-chip.png' ) ); ?>" alt="" loading="lazy">
			</a>
			<a class="side-story" href="<?php echo esc_url( $linux_url ); ?>">
				<span class="side-story__copy">
					<strong><?php esc_html_e( 'GhostLock Kernel Fixes Released', 'infosecnexus' ); ?></strong>
					<span><?php esc_html_e( 'Security patches are now available for supported Linux kernels.', 'infosecnexus' ); ?></span>
					<span class="story-meta"><?php esc_html_e( 'July 17, 2026', 'infosecnexus' ); ?> <span class="severity-tag severity-tag--high"><?php esc_html_e( 'High', 'infosecnexus' ); ?></span></span>
				</span>
				<img src="<?php echo esc_url( $asset( 'linux-circuit.png' ) ); ?>" alt="" loading="lazy">
			</a>
		</div>
	</section>

	<section class="home-news layout-wide-shell">
		<div class="news-panel news-panel--latest">
			<header class="section-header">
				<h2><?php echo esc_html( (string) \InfoSecNexus\Theme\Customizer\get_value( 'home_latest_title' ) ); ?></h2>
				<a href="<?php echo esc_url( $cyber_url ); ?>"><?php esc_html_e( 'View All', 'infosecnexus' ); ?><?php \InfoSecNexus\Theme\Header_Builder\icon( 'arrow-right' ); ?></a>
			</header>
			<div class="latest-grid">
				<?php foreach ( $latest_cards as $card ) : ?>
					<a class="intel-card" href="<?php echo esc_url( $card['url'] ); ?>">
						<img src="<?php echo esc_url( $card['image'] ); ?>" alt="" loading="lazy">
						<span class="intel-card__body">
							<span class="category-chip"><?php echo esc_html( $card['category'] ); ?></span>
							<strong><?php echo esc_html( $card['title'] ); ?></strong>
							<span><?php echo esc_html( $card['excerpt'] ); ?></span>
							<span class="story-meta"><?php echo esc_html( $card['date'] ); ?> <span aria-hidden="true">-</span> <?php echo esc_html( $card['read'] ); ?></span>
						</span>
					</a>
				<?php endforeach; ?>
			</div>
		</div>

		<div class="news-panel news-panel--cves">
			<header class="section-header">
				<h2><?php echo esc_html( (string) \InfoSecNexus\Theme\Customizer\get_value( 'home_cve_title' ) ); ?></h2>
				<a href="<?php echo esc_url( $critical_url ); ?>"><?php esc_html_e( 'View All', 'infosecnexus' ); ?><?php \InfoSecNexus\Theme\Header_Builder\icon( 'arrow-right' ); ?></a>
			</header>
			<div class="cve-list">
				<?php foreach ( $cves as $cve ) : ?>
					<a class="cve-row" href="<?php echo esc_url( $critical_url ); ?>">
						<span>
							<strong><?php echo esc_html( $cve['id'] ); ?></strong>
							<small><?php echo esc_html( $cve['name'] ); ?></small>
						</span>
						<span class="severity-tag severity-tag--<?php echo esc_attr( strtolower( $cve['severity'] ) ); ?>"><?php echo esc_html( $cve['severity'] ); ?></span>
						<b><?php echo esc_html( $cve['score'] ); ?></b>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="newsletter-section layout-wide-shell" id="daily-cyber-brief">
		<div class="newsletter-section__icon" aria-hidden="true">
			<svg viewBox="0 0 24 24" focusable="false"><path d="M4 6h16v12H4z" fill="none" stroke="currentColor" stroke-width="2"/><path d="m4 7 8 6 8-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
		</div>
		<div>
			<h2><?php echo esc_html( (string) \InfoSecNexus\Theme\Customizer\get_value( 'newsletter_title' ) ); ?></h2>
			<p><?php echo esc_html( (string) \InfoSecNexus\Theme\Customizer\get_value( 'newsletter_intro' ) ); ?></p>
		</div>
		<?php if ( function_exists( 'infosecnexus_toolkit_newsletter_form' ) ) : ?>
			<?php infosecnexus_toolkit_newsletter_form(); ?>
		<?php else : ?>
			<form class="newsletter-card">
				<label>
					<span class="screen-reader-text"><?php esc_html_e( 'Email address', 'infosecnexus' ); ?></span>
					<input type="email" placeholder="<?php esc_attr_e( 'Enter your email address', 'infosecnexus' ); ?>">
				</label>
				<button type="submit"><?php esc_html_e( 'Subscribe Now', 'infosecnexus' ); ?><?php \InfoSecNexus\Theme\Header_Builder\icon( 'arrow-right' ); ?></button>
			</form>
		<?php endif; ?>
	</section>
</main>
<?php
get_footer();
