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
	$post = get_page_by_path( $slug, 'OBJECT', 'post' );
	return $post && 'staged' !== (string) get_post_meta( $post->ID, '_infosecnexus_retirement_state', true ) ? get_permalink( $post ) : $fallback;
};

$critical_url = \InfoSecNexus\Theme\Header_Builder\category_url( 'critical-cves' );
$cyber_url    = \InfoSecNexus\Theme\Header_Builder\category_url( 'cybersecurity' );
$linux_url    = \InfoSecNexus\Theme\Header_Builder\category_url( 'linux-administration' );
$ai_url       = \InfoSecNexus\Theme\Header_Builder\category_url( 'artificial-intelligence' );
$cloud_url    = \InfoSecNexus\Theme\Header_Builder\category_url( 'cloud-security' );

$post_card_data = static function ( \WP_Post $post, string $severity = '', string $fallback_image = 'hero-shield.png' ): array {
	$categories  = get_the_category( $post->ID );
	$category    = ! empty( $categories ) ? $categories[0]->name : __( 'Cyber Security', 'infosecnexus' );
	$full_title  = get_the_title( $post );
	$raw_excerpt = has_excerpt( $post->ID ) ? get_the_excerpt( $post ) : wp_strip_all_tags( (string) $post->post_content );

	return array(
		'post_id'    => (int) $post->ID,
		'title'      => wp_trim_words( $full_title, 10, '...' ),
		'full_title' => $full_title,
		'excerpt'    => wp_trim_words( $raw_excerpt, 18, '...' ),
		'category'   => $category,
		'image'      => $fallback_image,
		'url'        => get_permalink( $post ),
		'date'       => get_the_date( '', $post ),
		'read'       => sprintf(
			/* translators: %d: reading time in minutes. */
			_n( '%d min read', '%d min read', \InfoSecNexus\Theme\Template_Tags\reading_time( (int) $post->ID ), 'infosecnexus' ),
			\InfoSecNexus\Theme\Template_Tags\reading_time( (int) $post->ID )
		),
		'severity'   => $severity,
	);
};

$normalize_severity = static function ( string $severity ): string {
	return match ( strtoupper( trim( $severity ) ) ) {
		'KNOWN EXPLOITED', 'CRITICAL' => 'Critical',
		'HIGH'                         => 'High',
		'MEDIUM', 'MODERATE'           => 'Medium',
		'LOW'                          => 'Low',
		default                        => 'High',
	};
};

$hero_url = (string) \InfoSecNexus\Theme\Customizer\get_value( 'home_hero_button_url' );
if ( '' === $hero_url ) {
	$hero_url = $critical_url;
}
$hero_badge        = (string) \InfoSecNexus\Theme\Customizer\get_value( 'home_hero_badge' );
$hero_title        = (string) \InfoSecNexus\Theme\Customizer\get_value( 'home_hero_title' );
$hero_excerpt      = (string) \InfoSecNexus\Theme\Customizer\get_value( 'home_hero_excerpt' );
$hero_button_label = (string) \InfoSecNexus\Theme\Customizer\get_value( 'home_hero_button_label' );
$hero_post_id      = 0;

$rolling_posts = get_posts(
	array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => 1,
		'ignore_sticky_posts' => true,
		'meta_query'          => array(
			array(
				'key'   => '_infosecnexus_newsroom_kind',
				'value' => 'rolling',
			),
		),
	)
);
if ( ! empty( $rolling_posts ) && $rolling_posts[0] instanceof \WP_Post ) {
	$hero_post         = $rolling_posts[0];
	$hero_post_id      = (int) $hero_post->ID;
	$hero_url          = get_permalink( $hero_post );
	$hero_badge        = __( 'Live Brief', 'infosecnexus' );
	$hero_title        = get_the_title( $hero_post );
	$hero_excerpt      = wp_trim_words( has_excerpt( $hero_post_id ) ? get_the_excerpt( $hero_post ) : wp_strip_all_tags( (string) $hero_post->post_content ), 28, '...' );
	$hero_button_label = __( 'Read Live Brief', 'infosecnexus' );
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
		'posts_per_page'      => 5,
		'ignore_sticky_posts' => true,
		'meta_query'          => array(
			array(
				'key'   => '_infosecnexus_newsroom_kind',
				'value' => 'breaking',
			),
		),
	)
);

if ( ! empty( $critical_posts ) ) {
	$cves = array();
	foreach ( $critical_posts as $critical_post ) {
		$critical_title = get_the_title( $critical_post );
		$match  = array();
		$label  = preg_match( '/CVE-\d{4}-\d+/i', $critical_title, $match ) ? strtoupper( $match[0] ) : wp_trim_words( $critical_title, 3, '' );
		$severity = $normalize_severity( sanitize_text_field( (string) get_post_meta( $critical_post->ID, '_infosecnexus_live_severity', true ) ) );
		$score    = get_post_meta( $critical_post->ID, '_infosecnexus_live_score', true );
		$cves[] = array(
			'id'       => $label,
			'name'     => wp_trim_words( $critical_title, 8, '' ),
			'severity' => $severity,
			'score'    => is_numeric( $score ) ? number_format_i18n( (float) $score, 1 ) : '--',
			'url'      => get_permalink( $critical_post ),
		);
	}

	$priority_severity = $normalize_severity( sanitize_text_field( (string) get_post_meta( $critical_posts[0]->ID, '_infosecnexus_live_severity', true ) ) );
	$side_stories[0]   = $post_card_data( $critical_posts[0], $priority_severity, 'lock-chip.png' );
}

$secondary_breaking_posts = get_posts(
	array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => 2,
		'ignore_sticky_posts' => true,
		'meta_query'          => array(
			array(
				'key'   => '_infosecnexus_newsroom_kind',
				'value' => 'breaking',
			),
		),
	)
);

if ( count( $secondary_breaking_posts ) > 1 ) {
	$secondary_severity = $normalize_severity( sanitize_text_field( (string) get_post_meta( $secondary_breaking_posts[1]->ID, '_infosecnexus_live_severity', true ) ) );
	$side_stories[1]    = $post_card_data( $secondary_breaking_posts[1], $secondary_severity, 'linux-circuit.png' );
}
?>
<main id="primary" class="site-main">
	<section class="home-hero layout-wide-shell" aria-label="<?php esc_attr_e( 'Featured cybersecurity briefings', 'infosecnexus' ); ?>">
		<a class="home-hero__lead" href="<?php echo esc_url( $hero_url ); ?>">
			<?php if ( $hero_post_id > 0 && has_post_thumbnail( $hero_post_id ) ) : ?>
				<?php echo get_the_post_thumbnail( $hero_post_id, 'large', array( 'loading' => 'eager', 'fetchpriority' => 'high', 'decoding' => 'async', 'sizes' => '(max-width: 760px) calc(100vw - 32px), (max-width: 1180px) 64vw, 860px' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php else : ?>
				<?php $asset_image( 'hero-shield.png', array( 'loading' => 'eager', 'fetchpriority' => 'high', 'sizes' => '(max-width: 760px) calc(100vw - 32px), (max-width: 1180px) 64vw, 860px' ) ); ?>
			<?php endif; ?>
			<span class="home-hero__shade" aria-hidden="true"></span>
			<span class="home-hero__content">
				<span class="severity-pill severity-pill--critical"><?php echo esc_html( $hero_badge ); ?></span>
				<h1 class="home-hero__title"><?php echo esc_html( $hero_title ); ?></h1>
				<span class="home-hero__excerpt"><?php echo esc_html( $hero_excerpt ); ?></span>
				<span class="button button--hero"><?php echo esc_html( $hero_button_label ); ?><?php \InfoSecNexus\Theme\Header_Builder\icon( 'arrow-right' ); ?></span>
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
