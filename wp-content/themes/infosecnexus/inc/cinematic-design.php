<?php
/**
 * Cinematic presentation and source-backed discovery helpers.
 *
 * @package InfoSecNexus
 */
declare(strict_types=1);

namespace InfoSecNexus\Theme\Cinematic_Design;

function bootstrap(): void {
	add_filter( 'body_class', static function ( array $classes ): array {
		$classes[] = 'cinematic-editorial';
		$classes[] = 'ivory-editorial';
		return $classes;
	} );
	add_filter( 'query_vars', static function ( array $vars ): array {
		$vars[] = 'isn_severity';
		return $vars;
	} );
	add_action( 'pre_get_posts', __NAMESPACE__ . '\\filter_search', 15 );
	add_filter( 'posts_where', __NAMESPACE__ . '\\priority_where', 15, 2 );
}

/** Apply the visible priority control to real published-post search. */
function filter_search( \WP_Query $query ): void {
	if ( is_admin() || ! $query->is_main_query() || ! $query->is_search() ) {
		return;
	}
	$priority = sanitize_key( (string) $query->get( 'isn_severity' ) );
	if ( ! in_array( $priority, array( 'critical', 'high', 'medium', 'low', 'none', 'known-exploited' ), true ) ) {
		return;
	}
	$query->set( 'isn_severity', $priority );
}

/**
 * Use indexed evidence lookups without multiplying the postmeta join rows.
 * Existing publication, taxonomy, search and retirement clauses remain intact.
 */
function priority_where( string $where, \WP_Query $query ): string {
	if ( is_admin() || ! $query->is_main_query() || ! $query->is_search() ) {
		return $where;
	}
	$priority = sanitize_key( (string) $query->get( 'isn_severity' ) );
	$ranges = array( 'critical' => array( 9, 10 ), 'high' => array( 7, 8.999 ), 'medium' => array( 4, 6.999 ), 'low' => array( 0.1, 3.999 ), 'none' => array( 0, 0 ) );
	if ( 'known-exploited' !== $priority && ! isset( $ranges[ $priority ] ) ) {
		return $where;
	}
	global $wpdb;
	$label = 'known-exploited' === $priority ? 'KNOWN EXPLOITED' : strtoupper( $priority );
	$label_exists = $wpdb->prepare(
		"EXISTS (SELECT 1 FROM {$wpdb->postmeta} cg_label WHERE cg_label.post_id = {$wpdb->posts}.ID AND cg_label.meta_key = %s AND UPPER(TRIM(cg_label.meta_value)) = %s)",
		'_infosecnexus_live_severity',
		$label
	);
	if ( 'known-exploited' === $priority ) {
		return $where . ' AND (' . $label_exists . ')';
	}
	$score_exists = static function ( float $minimum, float $maximum ) use ( $wpdb ): string {
		return (string) $wpdb->prepare(
			"EXISTS (SELECT 1 FROM {$wpdb->postmeta} cg_score WHERE cg_score.post_id = {$wpdb->posts}.ID AND cg_score.meta_key = %s AND cg_score.meta_value REGEXP %s AND CAST(cg_score.meta_value AS DECIMAL(5,3)) BETWEEN %f AND %f)",
			'_infosecnexus_live_score',
			'^([0-9]+([.][0-9]+)?|[.][0-9]+)$',
			$minimum,
			$maximum
		);
	};
	$matching_score = $score_exists( (float) $ranges[ $priority ][0], (float) $ranges[ $priority ][1] );
	$valid_score = $score_exists( 0, 10 );
	return $where . ' AND (' . $matching_score . ' OR (NOT ' . $valid_score . ' AND ' . $label_exists . '))';
}

/** Return one copy of each real story; no fallback headlines or made-up scores. */
function unique_posts( array $posts, int $limit, array $excluded = array() ): array {
	$seen = array();
	$result = array();
	foreach ( $posts as $post ) {
		if ( ! $post instanceof \WP_Post || in_array( (int) $post->ID, $excluded, true ) || post_password_required( $post ) ) {
			continue;
		}
		if ( '' !== (string) get_post_meta( $post->ID, '_infosecnexus_retirement_state', true ) || (int) get_post_meta( $post->ID, '_infosecnexus_newsroom_superseded_by', true ) > 0 ) {
			continue;
		}
		$title = wp_strip_all_tags( get_the_title( $post ) );
		$key = preg_match( '/\bCVE-\d{4}-\d{4,}\b/i', $title, $match ) ? strtolower( $match[0] ) : (string) get_post_meta( $post->ID, '_infosecnexus_story_key', true );
		$key = '' !== $key ? $key : 'post:' . $post->ID;
		if ( isset( $seen[ $key ] ) ) {
			continue;
		}
		$seen[ $key ] = true;
		$result[] = $post;
		if ( count( $result ) >= $limit ) {
			break;
		}
	}
	return $result;
}

/** Prioritize an actual recorded score without treating KEV membership as CVSS. */
function priority( int $post_id ): array {
	$severity = strtoupper( trim( (string) get_post_meta( $post_id, '_infosecnexus_live_severity', true ) ) );
	$score = get_post_meta( $post_id, '_infosecnexus_live_score', true );
	if ( is_numeric( $score ) && (float) $score >= 0 && (float) $score <= 10 ) {
		$number = (float) $score;
		return array( 'label' => $number >= 9 ? 'Critical' : ( $number >= 7 ? 'High' : ( $number >= 4 ? 'Medium' : ( $number > 0 ? 'Low' : 'None' ) ) ), 'critical' => $number >= 9, 'score' => number_format_i18n( $number, 1 ) );
	}
	$labels = array( 'CRITICAL' => 'Critical', 'HIGH' => 'High', 'MEDIUM' => 'Medium', 'LOW' => 'Low', 'NONE' => 'None', 'KNOWN EXPLOITED' => 'Known exploited' );
	return array( 'label' => $labels[ $severity ] ?? 'Security briefing', 'critical' => 'CRITICAL' === $severity, 'score' => '' );
}

/** Keep homepage excerpts free from headings and generator implementation details. */
function excerpt( \WP_Post $post, int $words = 22 ): string {
	$text = '' !== trim( (string) $post->post_excerpt ) ? (string) $post->post_excerpt : (string) $post->post_content;
	return wp_trim_words( wp_strip_all_tags( strip_shortcodes( $text ) ), $words, '…' );
}

/** Promote only topic routes with published, discoverable articles. */
function topics(): array {
	$topics = array(
		array( 'slug' => 'critical-cves', 'key' => 'critical', 'label' => 'Critical CVEs', 'description' => 'Exposure, impact, and a clear path to remediation.' ),
		array( 'slug' => 'cloud-security', 'key' => 'cloud', 'label' => 'Cloud Security', 'description' => 'Protect identities, workloads, and the cloud boundary.' ),
		array( 'slug' => 'linux-administration', 'key' => 'linux', 'label' => 'Linux & DevOps', 'description' => 'Stronger systems. More resilient delivery.' ),
		array( 'slug' => 'artificial-intelligence', 'key' => 'ai', 'label' => 'AI Security', 'description' => 'Understand risk across models, agents, and tools.' ),
		array( 'slug' => 'network-security', 'key' => 'network', 'label' => 'Network Security', 'description' => 'Practical defence for connected infrastructure.' ),
		array( 'slug' => 'web-security', 'key' => 'hero', 'icon' => 'critical', 'label' => 'Web Security', 'description' => 'Vulnerabilities and defensive context for web platforms.' ),
		array( 'slug' => 'windows-security', 'key' => 'ai', 'label' => 'Windows Security', 'description' => 'Microsoft platform advisories and remediation context.' ),
		array( 'slug' => 'devops', 'key' => 'linux', 'label' => 'DevSecOps', 'description' => 'Security context for delivery pipelines and infrastructure.' ),
		array( 'slug' => 'cybersecurity', 'key' => 'critical', 'label' => 'Cyber Security', 'description' => 'Current threats, vendor advisories, and security analysis.' ),
		array( 'slug' => 'tutorials', 'key' => 'linux', 'label' => 'Security Tutorials', 'description' => 'Practical guides for security and infrastructure teams.' ),
	);
	$topics = array_filter( $topics, static function ( array $topic ): bool {
		$category = get_category_by_slug( $topic['slug'] );
		return $category instanceof \WP_Term && \InfoSecNexus\Theme\SEO\category_has_active_posts( (int) $category->term_id );
	} );
	return array_slice( array_values( $topics ), 0, 5 );
}
