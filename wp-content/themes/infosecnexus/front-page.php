<?php
/**
 * Front page template.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

get_header();

$asset = static function ( string $file ): string {
	$webp = preg_replace( '/\.(png|jpg|jpeg)$/', '.webp', $file );
	if ( is_string( $webp ) && file_exists( get_template_directory() . '/assets/images/' . $webp ) ) {
		$file = $webp;
	}

	return get_template_directory_uri() . '/assets/images/' . $file;
};

$post_url = static function ( string $slug, string $fallback ): string {
	$post = get_page_by_path( $slug, OBJECT, 'post' );
	return $post ? get_permalink( $post ) : $fallback;
};

$critical_url = \InfoSecNexus\Theme\Header_Builder\category_url( 'critical-cves' );
$cyber_url    = \InfoSecNexus\Theme\Header_Builder\category_url( 'cybersecurity' );
$linux_url    = \InfoSecNexus\Theme\Header_Builder\category_url( 'linux-administration' );
$ai_url       = \InfoSecNexus\Theme\Header_Builder\category_url( 'artificial-intelligence' );
$cloud_url    = \InfoSecNexus\Theme\Header_Builder\category_url( 'cloud-security' );

$hero_url = (string) \InfoSecNexus\Theme\Customizer\get_value( 'home_hero_button_url' );
if ( '' === $hero_url ) {
	$hero_url = $critical_url;
}

$latest_cards = array(
	array(
		'title'    => __( 'Security Operations Metrics That Actually Reduce Risk', 'infosecnexus' ),
		'excerpt'  => __( 'Track owner, exposure, age, and verification so security work becomes measurable.', 'infosecnexus' ),
		'category' => __( 'Cyber Security', 'infosecnexus' ),
		'image'    => $asset( 'hero-shield.png' ),
		'url'      => $post_url( 'security-operations-metrics-that-reduce-risk', $cyber_url ),
		'date'     => __( 'July 21, 2026', 'infosecnexus' ),
		'read'     => __( '2 min read', 'infosecnexus' ),
	),
	array(
		'title'    => __( 'Cloud Storage Exposure Checklist', 'infosecnexus' ),
		'excerpt'  => __( 'Public access, encryption, logging, retention, and ownership checks for cloud teams.', 'infosecnexus' ),
		'category' => __( 'Cloud Security', 'infosecnexus' ),
		'image'    => $asset( 'cloud-security.png' ),
		'url'      => $post_url( 'cloud-storage-exposure-checklist', $cloud_url ),
		'date'     => __( 'July 21, 2026', 'infosecnexus' ),
		'read'     => __( '2 min read', 'infosecnexus' ),
	),
	array(
		'title'    => __( 'AI Data Leakage Controls for Internal Tools', 'infosecnexus' ),
		'excerpt'  => __( 'Reduce prompt, file, connector, retrieval, and logging exposure in AI workflows.', 'infosecnexus' ),
		'category' => __( 'AI Security', 'infosecnexus' ),
		'image'    => $asset( 'data-center.png' ),
		'url'      => $post_url( 'ai-data-leakage-controls-internal-tools', $ai_url ),
		'date'     => __( 'July 21, 2026', 'infosecnexus' ),
		'read'     => __( '2 min read', 'infosecnexus' ),
	),
);

$cves = array(
	array( 'id' => 'CVE Triage', 'name' => __( 'High-risk vulnerability review workflow', 'infosecnexus' ), 'severity' => 'Critical', 'score' => '9.8' ),
	array( 'id' => 'Zero-Day', 'name' => __( 'First 24 hours response checklist', 'infosecnexus' ), 'severity' => 'High', 'score' => '8.6' ),
	array( 'id' => 'Exploit Signals', 'name' => __( 'Early indicators before patch windows', 'infosecnexus' ), 'severity' => 'High', 'score' => '8.1' ),
	array( 'id' => 'Patch Ops', 'name' => __( 'Owner, deadline, and verification tracking', 'infosecnexus' ), 'severity' => 'Medium', 'score' => '6.9' ),
	array( 'id' => 'Exceptions', 'name' => __( 'Temporary mitigation review cadence', 'infosecnexus' ), 'severity' => 'Low', 'score' => '4.2' ),
);
?>
<main id="primary" class="site-main">
	<section class="home-hero layout-wide-shell" aria-label="<?php esc_attr_e( 'Featured cybersecurity briefings', 'infosecnexus' ); ?>">
		<a class="home-hero__lead" href="<?php echo esc_url( $hero_url ); ?>">
			<img src="<?php echo esc_url( $asset( 'hero-shield.png' ) ); ?>" alt="" loading="eager">
			<span class="home-hero__shade" aria-hidden="true"></span>
			<span class="home-hero__content">
				<span class="severity-pill severity-pill--critical"><?php echo esc_html( (string) \InfoSecNexus\Theme\Customizer\get_value( 'home_hero_badge' ) ); ?></span>
				<h1 class="home-hero__title"><?php echo esc_html( (string) \InfoSecNexus\Theme\Customizer\get_value( 'home_hero_title' ) ); ?></h1>
				<span class="home-hero__excerpt"><?php echo esc_html( (string) \InfoSecNexus\Theme\Customizer\get_value( 'home_hero_excerpt' ) ); ?></span>
				<span class="button button--hero"><?php echo esc_html( (string) \InfoSecNexus\Theme\Customizer\get_value( 'home_hero_button_label' ) ); ?><?php \InfoSecNexus\Theme\Header_Builder\icon( 'arrow-right' ); ?></span>
			</span>
		</a>

		<div class="home-hero__side">
			<a class="side-story" href="<?php echo esc_url( $post_url( 'cve-triage-checklist-high-risk-vulnerabilities', $critical_url ) ); ?>">
				<span class="side-story__copy">
					<strong><?php esc_html_e( 'CVE Triage Checklist for High-Risk Vulnerabilities', 'infosecnexus' ); ?></strong>
					<span><?php esc_html_e( 'Rank exploited vulnerabilities by exposure, blast radius, and patch urgency.', 'infosecnexus' ); ?></span>
					<span class="story-meta"><?php esc_html_e( 'July 21, 2026', 'infosecnexus' ); ?> <span class="severity-tag severity-tag--critical"><?php esc_html_e( 'Critical', 'infosecnexus' ); ?></span></span>
				</span>
				<img src="<?php echo esc_url( $asset( 'lock-chip.png' ) ); ?>" alt="" loading="lazy">
			</a>
			<a class="side-story" href="<?php echo esc_url( $post_url( 'linux-kernel-patch-runbook-production-servers', $linux_url ) ); ?>">
				<span class="side-story__copy">
					<strong><?php esc_html_e( 'Linux Kernel Patch Runbook for Production Servers', 'infosecnexus' ); ?></strong>
					<span><?php esc_html_e( 'Plan reboot windows, module checks, validation, and visible exceptions.', 'infosecnexus' ); ?></span>
					<span class="story-meta"><?php esc_html_e( 'July 21, 2026', 'infosecnexus' ); ?> <span class="severity-tag severity-tag--high"><?php esc_html_e( 'High', 'infosecnexus' ); ?></span></span>
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
							<span class="intel-card__readmore"><?php esc_html_e( 'Read More', 'infosecnexus' ); ?> <span aria-hidden="true">-></span></span>
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
