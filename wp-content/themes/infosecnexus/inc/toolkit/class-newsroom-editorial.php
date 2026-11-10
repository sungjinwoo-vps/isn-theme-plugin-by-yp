<?php
/**
 * Evidence persistence and guarded upgrades for generated newsroom articles.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

namespace InfoSecNexus\Theme\Toolkit;

/**
 * Keep source evidence and real changes alongside managed WordPress posts.
 */
final class Newsroom_Editorial {

	private const HASH_META = '_infosecnexus_editorial_body_hash';
	private const EVIDENCE_META = '_infosecnexus_editorial_evidence';
	private const HISTORY_META = '_infosecnexus_editorial_history';

	/** @var array<string,array<string,mixed>>|null Complete official KEV lookup. */
	private static ?array $kev_catalog = null;

	/** Register the explicit, dry-run-first migration command. */
	public static function boot(): void {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'infosecnexus newsroom upgrade-articles', array( __CLASS__, 'cli_upgrade_articles' ) );
		}
	}

	/**
	 * Whether automation still owns the current body.
	 *
	 * Previously generated articles have no body hash. Only their known legacy
	 * template, managed flags and generator fingerprint permit the first upgrade.
	 */
	public static function can_update( \WP_Post $post ): bool {
		if ( ! self::is_active( $post ) || '1' === (string) get_post_meta( $post->ID, '_infosecnexus_editorial_manual', true ) ) {
			return false;
		}
		$hash = (string) get_post_meta( $post->ID, self::HASH_META, true );
		if ( '' !== $hash ) {
			return hash_equals( $hash, hash( 'sha256', (string) $post->post_content ) );
		}
		if ( (int) get_post_meta( $post->ID, '_edit_last', true ) > 0 ) {
			return false;
		}
		if ( '1' !== (string) get_post_meta( $post->ID, '_infosecnexus_demo_content', true ) || '' === (string) get_post_meta( $post->ID, '_infosecnexus_live_fingerprint', true ) ) {
			return false;
		}
		preg_match_all( '/<h2\b[^>]*>(.*?)<\/h2>/is', (string) $post->post_content, $matches );
		$headings = array_map( 'wp_strip_all_tags', $matches[1] ?? array() );
		$legacy = array( 'What changed', 'Why this matters', 'Immediate response plan', 'Official remediation direction', 'Detection and validation', 'Accuracy note', 'Primary sources and references' );
		$without_action = array_values( array_diff( $legacy, array( 'Official remediation direction' ) ) );
		if ( $headings === $legacy || $headings === $without_action ) {
			return false !== strpos( (string) $post->post_content, '<p class="isnx-live-deck">An official source reports active exploitation.' )
				&& false !== strpos( (string) $post->post_content, 'Severity, affected-version ranges, and remediation details can change as the vendor and vulnerability databases add evidence.' );
		}
		$rolling = array( 'Executive security snapshot', 'Top developments for ', 'Coverage by security desk', 'Priority actions for today', 'Primary sources and references' );
		return 'rolling' === (string) get_post_meta( $post->ID, '_infosecnexus_newsroom_kind', true )
			&& count( $headings ) === count( $rolling )
			&& $headings[0] === $rolling[0]
			&& 0 === strpos( (string) $headings[1], $rolling[1] )
			&& array_slice( $headings, 2 ) === array_slice( $rolling, 2 );
	}

	/** Preserve all prior history; source checks alone never create an entry. */
	public static function record_update( int $post_id, array $evidence, string $previous_content ): void {
		$post = get_post( $post_id );
		if ( ! $post instanceof \WP_Post ) {
			return;
		}
		$hash = hash( 'sha256', (string) $post->post_content );
		$prior_evidence = self::evidence( $post_id );
		if ( '' !== $previous_content && ! hash_equals( hash( 'sha256', $previous_content ), $hash ) ) {
			$history = get_post_meta( $post_id, self::HISTORY_META, true );
			$history = is_array( $history ) ? $history : array();
			$history[] = array(
				'at'      => gmdate( 'c' ),
				'summary' => self::change_summary( $prior_evidence, $evidence ),
				'hash'    => $hash,
				'schema'  => Live_Intelligence::content_schema_version(),
			);
			update_post_meta( $post_id, self::HISTORY_META, $history );
		}
		update_post_meta( $post_id, self::HASH_META, $hash );
		update_post_meta( $post_id, self::EVIDENCE_META, wp_slash( (string) wp_json_encode( array_values( $evidence ) ) ) );
		update_post_meta( $post_id, '_infosecnexus_editorial_schema', Live_Intelligence::content_schema_version() );
	}

	/** @return array<int,array<string,mixed>> Previously collected source facts. */
	public static function evidence( int $post_id ): array {
		$value = get_post_meta( $post_id, self::EVIDENCE_META, true );
		$value = is_string( $value ) ? json_decode( $value, true ) : $value;
		return is_array( $value ) ? array_values( array_filter( $value, 'is_array' ) ) : array();
	}

	/** Render outside the gated content so the teaser and rest cannot duplicate it. */
	public static function history_html( int $post_id ): string {
		$history = get_post_meta( $post_id, self::HISTORY_META, true );
		if ( ! is_array( $history ) || empty( $history ) ) {
			return '';
		}
		$html = '<section class="article-history"><h2 id="article-updates">Updates to this article</h2><ol>';
		foreach ( array_reverse( $history ) as $entry ) {
			$at = (string) ( $entry['at'] ?? '' );
			$timestamp = strtotime( $at );
			if ( false === $timestamp || empty( $entry['summary'] ) ) {
				continue;
			}
			$html .= '<li><time datetime="' . esc_attr( $at ) . '">' . esc_html( wp_date( 'F j, Y, g:i a T', $timestamp ) ) . '</time><p>' . esc_html( (string) $entry['summary'] ) . '</p></li>';
		}
		return $html . '</ol></section>';
	}

	/**
	 * Upgrade active generated articles while preserving editorial changes.
	 *
	 * ## OPTIONS
	 *
	 * [--apply]
	 * : Write the reviewed changes. Without this flag, inspect only.
	 *
	 * [--limit=<number>]
	 * : Inspect at most this many managed posts; default is all active posts.
	 *
	 * [--post-id=<id>]
	 * : Inspect one active managed post.
	 *
	 * @param string[]            $args Positional arguments.
	 * @param array<string,mixed> $assoc_args Named arguments.
	 */
	public static function cli_upgrade_articles( array $args, array $assoc_args ): void {
		unset( $args );
		$apply = isset( $assoc_args['apply'] );
		$limit = isset( $assoc_args['limit'] ) ? absint( $assoc_args['limit'] ) : -1;
		$post_id = isset( $assoc_args['post-id'] ) ? absint( $assoc_args['post-id'] ) : 0;
		if ( 0 === $limit || ( isset( $assoc_args['post-id'] ) && 0 === $post_id ) ) {
			\WP_CLI::error( 'Use a positive --limit or --post-id.' );
		}
		$result = self::upgrade_articles( $apply, $limit, $post_id );
		\WP_CLI\Utils\format_items( 'table', $result['rows'], array( 'id', 'status', 'reason' ) );
		$counts = $result['counts'];
		$message = sprintf(
			'%s: checked=%d eligible=%d updated=%d unchanged=%d skipped=%d failed=%d.',
			$apply ? 'Applied' : 'Dry run', $counts['checked'], $counts['eligible'], $counts['updated'], $counts['unchanged'], $counts['skipped'], $counts['failed']
		);
		if ( $counts['failed'] > 0 ) {
			\WP_CLI::error( $message );
		} else {
			\WP_CLI::success( $message );
		}
	}

	/** @return array<string,mixed> Migration totals and per-post review rows. */
	public static function upgrade_articles( bool $apply = false, int $limit = -1, int $post_id = 0 ): array {
		$query = array(
			'post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => $limit,
			'orderby' => 'ID', 'order' => 'ASC', 'suppress_filters' => true, 'no_found_rows' => true,
			'meta_query' => array(
				'relation' => 'AND',
				array( 'key' => '_infosecnexus_newsroom_post', 'value' => '1' ),
				array(
					'relation' => 'OR',
					array( 'key' => '_infosecnexus_retirement_state', 'compare' => 'NOT EXISTS' ),
					array( 'key' => '_infosecnexus_retirement_state', 'value' => '' ),
				),
				array(
					'relation' => 'OR',
					array( 'key' => '_infosecnexus_newsroom_superseded_by', 'compare' => 'NOT EXISTS' ),
					array( 'key' => '_infosecnexus_newsroom_superseded_by', 'value' => array( '', '0' ), 'compare' => 'IN' ),
				),
			),
		);
		if ( $post_id > 0 ) {
			$query['p'] = $post_id;
		}
		$counts = array_fill_keys( array( 'checked', 'eligible', 'updated', 'unchanged', 'skipped', 'failed' ), 0 );
		$rows = array();
		$changed = array();
		foreach ( get_posts( $query ) as $post ) {
			if ( ! self::is_active( $post ) ) {
				continue;
			}
			++$counts['checked'];
			$status = 'skipped';
			$reason = 'Custom or manually edited content is preserved.';
			if ( self::can_update( $post ) ) {
				$cve = self::post_cve( $post );
				$evidence = self::evidence( (int) $post->ID );
				if ( 'rolling' === (string) get_post_meta( $post->ID, '_infosecnexus_newsroom_kind', true ) ) {
					$reason = 'Rolling content uses the normal source-backed publication flow.';
				} else {
					$item = ! empty( $evidence[0] ) ? $evidence[0] : ( '' !== $cve ? self::historical_item( $cve ) : self::historical_news_item( $post ) );
					$item = '' !== $cve ? Live_Intelligence::enrich_cve_item( $item ) : $item;
					if ( empty( $item['description'] ) && empty( $item['technical_description'] ) ) {
						$status = 'failed';
						$reason = 'No usable primary-source description is available.';
					} else {
						$date = wp_date( 'F j, Y', (int) strtotime( (string) $post->post_date_gmt . ' UTC' ) );
						$article = Live_Intelligence::article_for_item( $item, $date );
						$body = (string) ( $article['content'] ?? '' );
						if ( '' === $body ) {
							$status = 'failed';
							$reason = 'The source-backed renderer returned an empty article.';
						} elseif ( hash_equals( hash( 'sha256', (string) $post->post_content ), hash( 'sha256', $body ) ) ) {
							$status = 'unchanged';
							$reason = 'The generated article body is already current.';
						} else {
							++$counts['eligible'];
							$status = 'eligible';
							$reason = 'Rebuild ' . ( '' !== $cve ? $cve : 'the vendor advisory' ) . ' from its primary-source evidence.';
							if ( $apply ) {
								$updated = wp_update_post( wp_slash( array( 'ID' => $post->ID, 'post_content' => $body ) ), true );
								if ( is_wp_error( $updated ) || ! $updated ) {
									$status = 'failed';
									$reason = is_wp_error( $updated ) ? $updated->get_error_message() : 'WordPress did not save the article.';
								} else {
									$status = 'updated';
									self::record_update( (int) $post->ID, array( (array) $article['item'] ), (string) $post->post_content );
									update_post_meta( $post->ID, '_infosecnexus_source_urls', wp_slash( (string) wp_json_encode( $article['sources'] ) ) );
									update_post_meta( $post->ID, '_infosecnexus_live_fingerprint', (string) $article['fingerprint'] );
									$score = $article['item']['score'] ?? null;
									update_post_meta( $post->ID, '_infosecnexus_live_score', is_numeric( $score ) ? (string) $score : '' );
									$changed[] = (int) $post->ID;
								}
							}
						}
					}
				}
			}
			if ( isset( $counts[ $status ] ) && 'eligible' !== $status ) {
				++$counts[ $status ];
			}
			$rows[] = array( 'id' => $post->ID, 'status' => $status, 'reason' => $reason );
		}
		if ( ! empty( $changed ) ) {
			Demo_Content::purge_public_cache( $changed );
		}
		return array( 'counts' => $counts, 'rows' => $rows );
	}

	/** Exclude historical redirects and every retirement state. */
	private static function is_active( \WP_Post $post ): bool {
		return 'post' === $post->post_type && 'publish' === $post->post_status
			&& '1' === (string) get_post_meta( $post->ID, '_infosecnexus_newsroom_post', true )
			&& '' === (string) get_post_meta( $post->ID, '_infosecnexus_retirement_state', true )
			&& 0 === (int) get_post_meta( $post->ID, '_infosecnexus_newsroom_superseded_by', true );
	}

	/** Identify the article's one CVE without selecting a related-story CVE. */
	private static function post_cve( \WP_Post $post ): string {
		$text = (string) get_post_meta( $post->ID, '_infosecnexus_story_key', true ) . ' ' . (string) $post->post_title;
		return 1 === preg_match( '/\bCVE-\d{4}-\d{4,}\b/i', $text, $matches ) ? strtoupper( $matches[0] ) : '';
	}

	/** Seed old CVEs from the entire KEV catalog rather than today's feed window. */
	private static function historical_item( string $cve ): array {
		if ( null === self::$kev_catalog ) {
			self::$kev_catalog = array();
			$response = wp_remote_get( 'https://www.cisa.gov/sites/default/files/feeds/known_exploited_vulnerabilities.json', array( 'timeout' => 25, 'redirection' => 2, 'limit_response_size' => 12 * MB_IN_BYTES ) );
			if ( ! is_wp_error( $response ) && 200 === (int) wp_remote_retrieve_response_code( $response ) ) {
				$data = json_decode( (string) wp_remote_retrieve_body( $response ), true );
				foreach ( (array) ( $data['vulnerabilities'] ?? array() ) as $row ) {
					if ( is_array( $row ) && 1 === preg_match( '/^CVE-\d{4}-\d{4,}$/', (string) ( $row['cveID'] ?? '' ) ) ) {
						self::$kev_catalog[ (string) $row['cveID'] ] = $row;
					}
				}
			}
		}
		$row = self::$kev_catalog[ $cve ] ?? array();
		$url = 'https://www.cve.org/CVERecord?id=' . rawurlencode( $cve );
		if ( empty( $row ) ) {
			return array( 'id' => 'cve-' . strtolower( $cve ), 'cve' => $cve, 'type' => 'vulnerability', 'title' => $cve, 'source' => 'CVE Program', 'source_key' => 'cve', 'url' => $url, 'description' => '' );
		}
		$notes = (string) ( $row['notes'] ?? '' );
		$added = strtotime( (string) ( $row['dateAdded'] ?? '' ) );
		$references = array();
		if ( preg_match_all( '~https?://[^\s<>"\']+~i', $notes, $matches ) ) {
			foreach ( $matches[0] as $link ) {
				$link = esc_url_raw( rtrim( (string) $link, '.,;)' ) );
				if ( '' !== $link ) {
					$references[] = array( 'source' => 'CISA-linked vendor guidance', 'title' => (string) wp_parse_url( $link, PHP_URL_HOST ), 'url' => $link );
				}
			}
		}
		return array(
			'id' => 'cisa-kev-' . strtolower( $cve ), 'source_key' => 'cisa_kev', 'source' => 'CISA Known Exploited Vulnerabilities',
			'type' => 'kev', 'title' => $cve . ': ' . (string) ( $row['vulnerabilityName'] ?? $cve ),
			'description' => (string) ( $row['shortDescription'] ?? '' ), 'url' => 'https://www.cisa.gov/known-exploited-vulnerabilities-catalog?field_cve=' . rawurlencode( $cve ),
			'published' => false !== $added ? gmdate( 'c', $added ) : '', 'kev_added' => (string) ( $row['dateAdded'] ?? '' ),
			'severity' => 'Known Exploited', 'score' => null, 'cve' => $cve,
			'vendor' => (string) ( $row['vendorProject'] ?? '' ), 'product' => (string) ( $row['product'] ?? '' ),
			'due_date' => (string) ( $row['dueDate'] ?? '' ), 'action' => (string) ( $row['requiredAction'] ?? '' ),
			'notes' => $notes, 'references' => $references, 'active_exploitation' => true,
			'kev_url' => 'https://www.cisa.gov/known-exploited-vulnerabilities-catalog?field_cve=' . rawurlencode( $cve ),
		);
	}

	/** Recover the exact source summary of the known legacy Cisco news template. */
	private static function historical_news_item( \WP_Post $post ): array {
		$sources = json_decode( (string) get_post_meta( $post->ID, '_infosecnexus_source_urls', true ), true );
		if ( ! is_array( $sources ) ) {
			return array();
		}
		foreach ( $sources as $source ) {
			$url = is_array( $source ) ? esc_url_raw( (string) ( $source[1] ?? '' ) ) : '';
			$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
			if ( 'https' !== wp_parse_url( $url, PHP_URL_SCHEME ) || ( 'cisco.com' !== $host && ! str_ends_with( $host, '.cisco.com' ) ) ) {
				continue;
			}
			preg_match_all( '/<p\b[^>]*>(.*?)<\/p>/is', (string) $post->post_content, $paragraphs );
			$description = html_entity_decode( wp_strip_all_tags( (string) ( $paragraphs[1][1] ?? '' ) ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			if ( '' === trim( $description ) ) {
				return array();
			}
			$ids = json_decode( (string) get_post_meta( $post->ID, '_infosecnexus_live_source_ids', true ), true );
			return array(
				'id' => is_array( $ids ) ? (string) ( $ids[0] ?? 'cisco-' . md5( $url ) ) : 'cisco-' . md5( $url ),
				'source_key' => 'cisco_psirt', 'source' => 'Cisco Product Security Incident Response Team',
				'type' => 'vendor_advisory', 'title' => (string) $post->post_title, 'description' => $description,
				'url' => $url, 'published' => '', 'cve' => '', 'score' => null, 'severity' => '',
				'active_exploitation' => false,
			);
		}
		return array();
	}

	/** Describe the facts that actually changed, without invented historical dates. */
	private static function change_summary( array $before, array $after ): string {
		if ( empty( $before ) ) {
			return 'Expanded this briefing with attributed primary-source facts, affected-version details, and remediation guidance.';
		}
		$labels = array( 'affected_versions' => 'affected and fixed versions', 'metrics' => 'severity assessments', 'action' => 'remediation guidance', 'solutions' => 'remediation guidance', 'workarounds' => 'temporary mitigations', 'active_exploitation' => 'exploitation status', 'technical_description' => 'technical analysis', 'vendor_review' => 'vendor-specific remediation guidance' );
		$changes = array();
		foreach ( $labels as $key => $label ) {
			if ( wp_json_encode( array_column( $before, $key ) ) !== wp_json_encode( array_column( $after, $key ) ) ) {
				$changes[] = $label;
			}
		}
		return empty( $changes ) ? 'Revised the article structure and source-backed analysis.' : 'Updated ' . implode( ', ', array_unique( $changes ) ) . ' from the linked primary records.';
	}
}
