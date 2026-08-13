<?php
/**
 * Front page template.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

get_header();

$asset_image = static function ( string $key, array $attrs = array() ): void {
	echo \InfoSecNexus\Theme\Anime_Design\asset_image( $key, $attrs ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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

$post_card_data = static function ( \WP_Post $post, string $severity = '', string $fallback_image = 'hero' ): array {
	$categories  = get_the_category( $post->ID );
	$category    = ! empty( $categories ) ? $categories[0]->name : __( 'Cyber Security', 'infosecnexus' );
	$full_title  = get_the_title( $post );
	$raw_excerpt = has_excerpt( $post->ID ) ? get_the_excerpt( $post ) : wp_strip_all_tags( (string) $post->post_content );
	$date        = \InfoSecNexus\Theme\Template_Tags\post_date_data( (int) $post->ID );

	return array(
		'post_id'    => (int) $post->ID,
		'title'      => wp_trim_words( $full_title, 10, '...' ),
		'full_title' => $full_title,
		'excerpt'    => wp_trim_words( $raw_excerpt, 18, '...' ),
		'category'   => $category,
		'image'      => $fallback_image,
		'url'        => get_permalink( $post ),
		'date'       => $date['label'],
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
		'name'                => 'live-cybersecurity-brief',
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
		'title'    => __( 'Cybersecurity Operations and Threat Intelligence', 'infosecnexus' ),
		'excerpt'  => __( 'Follow confirmed threats, active exploitation, defensive guidance, and response priorities.', 'infosecnexus' ),
		'category' => __( 'Cyber Security', 'infosecnexus' ),
		'image'    => 'hero',
		'url'      => $cyber_url,
		'date'     => __( 'Live desk', 'infosecnexus' ),
		'read'     => __( 'Updated daily', 'infosecnexus' ),
	),
	array(
		'title'    => __( 'Cloud, Network, and Infrastructure Security', 'infosecnexus' ),
		'excerpt'  => __( 'Review vendor advisories, exposed services, platform changes, and practical hardening steps.', 'infosecnexus' ),
		'category' => __( 'Cloud Security', 'infosecnexus' ),
		'image'    => 'cloud',
		'url'      => $cloud_url,
		'date'     => __( 'Live desk', 'infosecnexus' ),
		'read'     => __( 'Updated daily', 'infosecnexus' ),
	),
	array(
		'title'    => __( 'AI, Application, and Software Supply Chain Risk', 'infosecnexus' ),
		'excerpt'  => __( 'Track model, agent, application, dependency, and build-pipeline security developments.', 'infosecnexus' ),
		'category' => __( 'AI Security', 'infosecnexus' ),
		'image'    => 'ai',
		'url'      => $ai_url,
		'date'     => __( 'Live desk', 'infosecnexus' ),
		'read'     => __( 'Updated daily', 'infosecnexus' ),
	),
);

$side_stories = array(
	array(
		'title'    => __( 'CVE Triage Checklist for High-Risk Vulnerabilities', 'infosecnexus' ),
		'excerpt'  => __( 'Rank exploited vulnerabilities by exposure, blast radius, and patch urgency.', 'infosecnexus' ),
		'image'    => 'critical',
		'url'      => $post_url( 'cve-triage-checklist-high-risk-vulnerabilities', $critical_url ),
		'date'     => __( 'July 21, 2026', 'infosecnexus' ),
		'severity' => 'Critical',
	),
	array(
		'title'    => __( 'Linux Kernel Patch Runbook for Production Servers', 'infosecnexus' ),
		'excerpt'  => __( 'Plan reboot windows, module checks, validation, and visible exceptions.', 'infosecnexus' ),
		'image'    => 'linux',
		'url'      => $post_url( 'linux-kernel-patch-runbook-production-servers', $linux_url ),
		'date'     => __( 'July 21, 2026', 'infosecnexus' ),
		'severity' => 'High',
	),
);

$cves = array(
	array(
		'id'       => 'CVE Triage',
		'name'     => __( 'High-risk vulnerability review workflow', 'infosecnexus' ),
		'severity' => 'Critical',
		'score'    => '9.8',
	),
	array(
		'id'       => 'Zero-Day',
		'name'     => __( 'First 24 hours response checklist', 'infosecnexus' ),
		'severity' => 'High',
		'score'    => '8.6',
	),
	array(
		'id'       => 'Exploit Signals',
		'name'     => __( 'Early indicators before patch windows', 'infosecnexus' ),
		'severity' => 'High',
		'score'    => '8.1',
	),
	array(
		'id'       => 'Patch Ops',
		'name'     => __( 'Owner, deadline, and verification tracking', 'infosecnexus' ),
		'severity' => 'Medium',
		'score'    => '6.9',
	),
	array(
		'id'       => 'Exceptions',
		'name'     => __( 'Temporary mitigation review cadence', 'infosecnexus' ),
		'severity' => 'Low',
		'score'    => '4.2',
	),
);

$critical_posts = get_posts(
	array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => 10,
		'ignore_sticky_posts' => true,
		'orderby'             => array(
			'date' => 'DESC',
			'ID'   => 'DESC',
		),
		'meta_query'          => array(
			array(
				'key'   => '_infosecnexus_newsroom_kind',
				'value' => 'breaking',
			),
		),
	)
);

$unique_critical_posts = array();
$critical_story_keys   = array();
foreach ( $critical_posts as $critical_post ) {
	$title_key = strtolower( trim( wp_strip_all_tags( get_the_title( $critical_post ) ) ) );
	if ( preg_match( '/\bCVE-\d{4}-\d{4,}\b/i', $title_key, $cve_match ) ) {
		$story_key = 'cve:' . strtolower( $cve_match[0] );
	} else {
		$story_key = sanitize_text_field( (string) get_post_meta( $critical_post->ID, '_infosecnexus_story_key', true ) );
	}
	if ( '' === $story_key ) {
		$story_key = '' !== $title_key
			? 'title:' . md5( preg_replace( '/\s+/', ' ', $title_key ) )
			: 'post:' . (int) $critical_post->ID;
	}
	if ( isset( $critical_story_keys[ $story_key ] ) ) {
		continue;
	}
	$critical_story_keys[ $story_key ] = true;
	$unique_critical_posts[]           = $critical_post;
	if ( count( $unique_critical_posts ) >= 5 ) {
		break;
	}
}
$critical_posts = $unique_critical_posts;

if ( ! empty( $critical_posts ) ) {
	$cves = array();
	foreach ( $critical_posts as $critical_post ) {
		$critical_title = get_the_title( $critical_post );
		$match          = array();
		$label          = preg_match( '/CVE-\d{4}-\d+/i', $critical_title, $match ) ? strtoupper( $match[0] ) : wp_trim_words( $critical_title, 3, '' );
		$severity       = $normalize_severity( sanitize_text_field( (string) get_post_meta( $critical_post->ID, '_infosecnexus_live_severity', true ) ) );
		$score          = get_post_meta( $critical_post->ID, '_infosecnexus_live_score', true );
		$cves[]         = array(
			'id'       => $label,
			'name'     => wp_trim_words( $critical_title, 8, '' ),
			'severity' => $severity,
			'score'    => is_numeric( $score ) ? number_format_i18n( (float) $score, 1 ) : '--',
			'url'      => get_permalink( $critical_post ),
		);
	}

	$priority_severity = $normalize_severity( sanitize_text_field( (string) get_post_meta( $critical_posts[0]->ID, '_infosecnexus_live_severity', true ) ) );
	$side_stories[0]   = $post_card_data( $critical_posts[0], $priority_severity, 'critical' );
}

if ( count( $critical_posts ) > 1 ) {
	$secondary_severity = $normalize_severity( sanitize_text_field( (string) get_post_meta( $critical_posts[1]->ID, '_infosecnexus_live_severity', true ) ) );
	$side_stories[1]    = $post_card_data( $critical_posts[1], $secondary_severity, 'network' );
}

$featured_post_ids = array_values(
	array_unique(
		array_filter(
			array_merge(
				array( $hero_post_id ),
				array_map( static fn( \WP_Post $post ): int => (int) $post->ID, array_slice( $critical_posts, 0, 2 ) )
			)
		)
	)
);
$latest_posts      = get_posts(
	array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => 3,
		'post__not_in'        => $featured_post_ids,
		'ignore_sticky_posts' => true,
		'meta_query'          => array(
			'relation' => 'OR',
			array(
				'key'     => '_infosecnexus_newsroom_kind',
				'compare' => 'NOT EXISTS',
			),
			array(
				'key'     => '_infosecnexus_newsroom_kind',
				'value'   => 'rolling',
				'compare' => '!=',
			),
		),
	)
);

if ( ! empty( $latest_posts ) ) {
	$latest_cards = array_slice( array_merge( array_map( $post_card_data, $latest_posts ), $latest_cards ), 0, 3 );
}
?>
<main id="primary" class="site-main">
	<section class="home-hero layout-wide-shell" aria-label="<?php esc_attr_e( 'Featured cybersecurity briefings', 'infosecnexus' ); ?>">
		<article class="home-hero__feature">
			<?php \InfoSecNexus\Theme\Anime_Design\hero_media( $hero_post_id ); ?>
			<span class="home-hero__shade" aria-hidden="true"></span>
			<div class="home-hero__content">
				<span class="severity-pill severity-pill--critical"><?php echo esc_html( $hero_badge ); ?></span>
				<h1 class="home-hero__title"><?php echo esc_html( $hero_title ); ?></h1>
				<p class="home-hero__excerpt"><?php echo esc_html( $hero_excerpt ); ?></p>
				<a class="button button--hero home-hero__lead" href="<?php echo esc_url( $hero_url ); ?>"><?php echo esc_html( $hero_button_label ); ?><?php \InfoSecNexus\Theme\Header_Builder\icon( 'arrow-right' ); ?></a>
			</div>
		</article>

		<div class="home-hero__side">
			<?php foreach ( $side_stories as $story ) : ?>
				<article class="side-story<?php echo 'Critical' === $story['severity'] ? ' side-story--critical' : ''; ?>">
					<a class="side-story__link" href="<?php echo esc_url( $story['url'] ); ?>">
					<div class="side-story__copy">
						<h2><?php echo esc_html( $story['title'] ); ?></h2>
						<p><?php echo esc_html( $story['excerpt'] ); ?></p>
						<div class="story-meta"><?php echo esc_html( $story['date'] ); ?> <span class="severity-tag severity-tag--<?php echo esc_attr( strtolower( $story['severity'] ) ); ?>"><?php echo esc_html( $story['severity'] ); ?></span></div>
					</div>
					<?php if ( ! empty( $story['post_id'] ) ) : ?>
						<?php echo \InfoSecNexus\Theme\Anime_Design\post_image( (int) $story['post_id'], array( 'sizes' => '(max-width: 760px) calc(100vw - 32px), (max-width: 1180px) 38vw, 320px' ), 'medium_large' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php else : ?>
						<?php $asset_image( $story['image'], array( 'sizes' => '(max-width: 760px) calc(100vw - 32px), (max-width: 1180px) 38vw, 320px' ) ); ?>
					<?php endif; ?>
					</a>
				</article>
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
					<article class="intel-card">
						<a class="intel-card__link" href="<?php echo esc_url( $card['url'] ); ?>">
						<?php if ( ! empty( $card['post_id'] ) ) : ?>
							<?php echo \InfoSecNexus\Theme\Anime_Design\post_image( (int) $card['post_id'], array( 'sizes' => '(max-width: 760px) calc(100vw - 32px), (max-width: 1180px) 29vw, 320px' ), 'medium_large' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php else : ?>
							<?php $asset_image( $card['image'], array( 'sizes' => '(max-width: 760px) calc(100vw - 32px), (max-width: 1180px) 29vw, 320px' ) ); ?>
						<?php endif; ?>
						<div class="intel-card__body">
							<span class="category-chip"><?php echo esc_html( $card['category'] ); ?></span>
							<h3><?php echo esc_html( $card['title'] ); ?></h3>
							<p><?php echo esc_html( $card['excerpt'] ); ?></p>
							<div class="story-meta"><?php echo esc_html( $card['date'] ); ?> <span aria-hidden="true">-</span> <?php echo esc_html( $card['read'] ); ?></div>
							<span class="intel-card__readmore"><?php esc_html_e( 'Read briefing', 'infosecnexus' ); ?> <span aria-hidden="true">&rarr;</span></span>
						</div>
						</a>
					</article>
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
