<?php
/**
 * Front page template.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

get_header();

$asset_image = static function ( string $file, array $attrs = array() ): void {
	echo \InfoSecNexus\Theme\Template_Tags\asset_image( $file, $attrs ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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

$post_card_data = static function ( \WP_Post $post, string $severity = '', string $fallback_image = 'hero-shield.png' ): array {
	$categories = get_the_category( $post->ID );
	$category   = ! empty( $categories ) ? $categories[0]->name : __( 'Cyber Security', 'infosecnexus' );
	$excerpt    = has_excerpt( $post->ID ) ? get_the_excerpt( $post ) : wp_trim_words( wp_strip_all_tags( (string) $post->post_content ), 18 );

	return array(
		'post_id'  => (int) $post->ID,
		'title'    => get_the_title( $post ),
		'excerpt'  => $excerpt,
		'category' => $category,
		'image'    => $fallback_image,
		'url'      => get_permalink( $post ),
		'date'     => get_the_date( '', $post ),
		'read'     => sprintf(
			/* translators: %d: reading time in minutes. */
			_n( '%d min read', '%d min read', \InfoSecNexus\Theme\Template_Tags\reading_time( (int) $post->ID ), 'infosecnexus' ),
			\InfoSecNexus\Theme\Template_Tags\reading_time( (int) $post->ID )
		),
		'severity' => $severity,
	);
};

$hero_url = (string) \InfoSecNexus\Theme\Customizer\get_value( 'home_hero_button_url' );
if ( '' === $hero_url ) {
	$hero_url = $critical_url;
}

$latest_cards = array(
	array(
		'title'    => __( 'Security Operations Metrics That Actually Reduce Risk', 'infosecnexus' ),
		'excerpt'  => __( 'Track owner, exposure, age, and verification so security work becomes measurable.', 'infosecnexus' ),
		'category' => __( 'Cyber Security', 'infosecnexus' ),
		'image'    => 'hero-shield.png',
		'url'      => $post_url( 'security-operations-metrics-that-reduce-risk', $cyber_url ),
		'date'     => __( 'July 21, 2026', 'infosecnexus' ),
		'read'     => __( '2 min read', 'infosecnexus' ),
	),
	array(
		'title'    => __( 'Cloud Storage Exposure Checklist', 'infosecnexus' ),
		'excerpt'  => __( 'Public access, encryption, logging, retention, and ownership checks for cloud teams.', 'infosecnexus' ),
		'category' => __( 'Cloud Security', 'infosecnexus' ),
		'image'    => 'cloud-security.png',
		'url'      => $post_url( 'cloud-storage-exposure-checklist', $cloud_url ),
		'date'     => __( 'July 21, 2026', 'infosecnexus' ),
		'read'     => __( '2 min read', 'infosecnexus' ),
	),
	array(
		'title'    => __( 'AI Data Leakage Controls for Internal Tools', 'infosecnexus' ),
		'excerpt'  => __( 'Reduce prompt, file, connector, retrieval, and logging exposure in AI workflows.', 'infosecnexus' ),
		'category' => __( 'AI Security', 'infosecnexus' ),
		'image'    => 'data-center.png',
		'url'      => $post_url( 'ai-data-leakage-controls-internal-tools', $ai_url ),
		'date'     => __( 'July 21, 2026', 'infosecnexus' ),
		'read'     => __( '2 min read', 'infosecnexus' ),
	),
);

$side_stories = array(
	array(
		'title'    => __( 'CVE Triage Checklist for High-Risk Vulnerabilities', 'infosecnexus' ),
		'excerpt'  => __( 'Rank exploited vulnerabilities by exposure, blast radius, and patch urgency.', 'infosecnexus' ),
		'image'    => 'lock-chip.png',
		'url'      => $post_url( 'cve-triage-checklist-high-risk-vulnerabilities', $critical_url ),
		'date'     => __( 'July 21, 2026', 'infosecnexus' ),
		'severity' => 'Critical',
	),
	array(
		'title'    => __( 'Linux Kernel Patch Runbook for Production Servers', 'infosecnexus' ),
		'excerpt'  => __( 'Plan reboot windows, module checks, validation, and visible exceptions.', 'infosecnexus' ),
		'image'    => 'linux-circuit.png',
		'url'      => $post_url( 'linux-kernel-patch-runbook-production-servers', $linux_url ),
		'date'     => __( 'July 21, 2026', 'infosecnexus' ),
		'severity' => 'High',
	),
);

$cves = array(
	array( 'id' => 'CVE Triage', 'name' => __( 'High-risk vulnerability review workflow', 'infosecnexus' ), 'severity' => 'Critical', 'score' => '9.8' ),
	array( 'id' => 'Zero-Day', 'name' => __( 'First 24 hours response checklist', 'infosecnexus' ), 'severity' => 'High', 'score' => '8.6' ),
	array( 'id' => 'Exploit Signals', 'name' => __( 'Early indicators before patch windows', 'infosecnexus' ), 'severity' => 'High', 'score' => '8.1' ),
	array( 'id' => 'Patch Ops', 'name' => __( 'Owner, deadline, and verification tracking', 'infosecnexus' ), 'severity' => 'Medium', 'score' => '6.9' ),
	array( 'id' => 'Exceptions', 'name' => __( 'Temporary mitigation review cadence', 'infosecnexus' ), 'severity' => 'Low', 'score' => '4.2' ),
);

$latest_posts = get_posts(
	array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => 3,
		'ignore_sticky_posts' => true,
	)
);

if ( ! empty( $latest_posts ) ) {
	$latest_cards = array_map( $post_card_data, $latest_posts );
}

$critical_posts = get_posts(
	array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'category_name'       => 'critical-cves',
		'posts_per_page'      => 5,
		'ignore_sticky_posts' => true,
	)
);

if ( ! empty( $critical_posts ) ) {
	$cves = array();
	foreach ( $critical_posts as $index => $post ) {
		$title  = get_the_title( $post );
		$match  = array();
		$label  = preg_match( '/CVE-\d{4}-\d+/i', $title, $match ) ? strtoupper( $match[0] ) : wp_trim_words( $title, 3, '' );
		if ( false !== stripos( $title, 'Daily CVE Watch' ) ) {
			$label = 'Daily CVE Watch';
		}
		$cves[] = array(
			'id'       => $label,
			'name'     => wp_trim_words( $title, 8, '' ),
			'severity' => 0 === $index ? 'Critical' : ( $index < 3 ? 'High' : 'Medium' ),
			'score'    => array( '9.8', '8.6', '8.1', '6.9', '5.8' )[ $index ] ?? '5.8',
			'url'      => get_permalink( $post ),
		);
	}

	$side_stories[0] = $post_card_data( $critical_posts[0], 'Critical', 'lock-chip.png' );
}

$linux_posts = get_posts(
	array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'category_name'       => 'linux-administration',
		'posts_per_page'      => 1,
		'ignore_sticky_posts' => true,
	)
);

if ( ! empty( $linux_posts ) ) {
	$side_stories[1] = $post_card_data( $linux_posts[0], 'High', 'linux-circuit.png' );
}
?>
<main id="primary" class="site-main">
	<section class="home-hero layout-wide-shell" aria-label="<?php esc_attr_e( 'Featured cybersecurity briefings', 'infosecnexus' ); ?>">
		<a class="home-hero__lead" href="<?php echo esc_url( $hero_url ); ?>">
			<?php $asset_image( 'hero-shield.png', array( 'loading' => 'eager', 'fetchpriority' => 'high', 'sizes' => '(max-width: 760px) calc(100vw - 32px), (max-width: 1180px) 64vw, 860px' ) ); ?>
			<span class="home-hero__shade" aria-hidden="true"></span>
			<span class="home-hero__content">
				<span class="severity-pill severity-pill--critical"><?php echo esc_html( (string) \InfoSecNexus\Theme\Customizer\get_value( 'home_hero_badge' ) ); ?></span>
				<h1 class="home-hero__title"><?php echo esc_html( (string) \InfoSecNexus\Theme\Customizer\get_value( 'home_hero_title' ) ); ?></h1>
				<span class="home-hero__excerpt"><?php echo esc_html( (string) \InfoSecNexus\Theme\Customizer\get_value( 'home_hero_excerpt' ) ); ?></span>
				<span class="button button--hero"><?php echo esc_html( (string) \InfoSecNexus\Theme\Customizer\get_value( 'home_hero_button_label' ) ); ?><?php \InfoSecNexus\Theme\Header_Builder\icon( 'arrow-right' ); ?></span>
			</span>
		</a>

		<div class="home-hero__side">
			<?php foreach ( $side_stories as $story ) : ?>
				<a class="side-story" href="<?php echo esc_url( $story['url'] ); ?>">
					<span class="side-story__copy">
						<strong><?php echo esc_html( $story['title'] ); ?></strong>
						<span><?php echo esc_html( $story['excerpt'] ); ?></span>
						<span class="story-meta"><?php echo esc_html( $story['date'] ); ?> <span class="severity-tag severity-tag--<?php echo esc_attr( strtolower( $story['severity'] ) ); ?>"><?php echo esc_html( $story['severity'] ); ?></span></span>
					</span>
					<?php if ( ! empty( $story['post_id'] ) && has_post_thumbnail( (int) $story['post_id'] ) ) : ?>
						<?php echo get_the_post_thumbnail( (int) $story['post_id'], 'medium_large', array( 'loading' => 'lazy', 'decoding' => 'async', 'sizes' => '(max-width: 760px) calc(100vw - 32px), (max-width: 1180px) 38vw, 320px' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php else : ?>
						<?php $asset_image( $story['image'], array( 'sizes' => '(max-width: 760px) calc(100vw - 32px), (max-width: 1180px) 38vw, 320px' ) ); ?>
					<?php endif; ?>
				</a>
			<?php endforeach; ?>
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
						<?php if ( ! empty( $card['post_id'] ) && has_post_thumbnail( (int) $card['post_id'] ) ) : ?>
							<?php echo get_the_post_thumbnail( (int) $card['post_id'], 'medium_large', array( 'loading' => 'lazy', 'decoding' => 'async', 'sizes' => '(max-width: 760px) calc(100vw - 32px), (max-width: 1180px) 29vw, 320px' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php else : ?>
							<?php $asset_image( $card['image'], array( 'sizes' => '(max-width: 760px) calc(100vw - 32px), (max-width: 1180px) 29vw, 320px' ) ); ?>
						<?php endif; ?>
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
					<a class="cve-row" href="<?php echo esc_url( $cve['url'] ?? $critical_url ); ?>">
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
