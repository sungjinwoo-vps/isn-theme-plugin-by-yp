<?php
/**
 * Live cybersecurity source collection and daily briefing generation.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

namespace InfoSecNexus\Theme\Toolkit;

/**
 * Collect structured security data from authoritative public sources.
 */
final class Live_Intelligence {
	private const CACHE_KEY = 'infosecnexus_live_intelligence_cache';
	private const LAST_GOOD_OPTION = 'infosecnexus_live_intelligence_last_good';
	private const STATUS_OPTION = 'infosecnexus_live_intelligence_status';
	private const CACHE_TTL = 4 * HOUR_IN_SECONDS;
	private const LOOKBACK_DAYS = 10;
	private const MAX_ITEMS = 260;

	/**
	 * Collect and normalize current source data.
	 *
	 * @param bool $force Ignore the short-lived source cache.
	 * @return array<string,mixed>
	 */
	public static function collect( bool $force = false ): array {
		if ( ! $force ) {
			$cached = get_transient( self::CACHE_KEY );
			if ( is_array( $cached ) && ! empty( $cached['items'] ) ) {
				return $cached;
			}
		}

		$items    = array();
		$statuses = array();
		$sources  = array(
			'verified_context' => array( 'Verified Current Context', array( __CLASS__, 'collect_verified_context' ) ),
			'cisa_kev'       => array( 'CISA Known Exploited Vulnerabilities', array( __CLASS__, 'collect_cisa_kev' ) ),
			'nvd'            => array( 'NIST National Vulnerability Database', array( __CLASS__, 'collect_nvd' ) ),
			'github_advisory' => array( 'GitHub Advisory Database', array( __CLASS__, 'collect_github_advisories' ) ),
			'cisa_advisory'  => array(
				'CISA Cybersecurity Advisories',
				static fn() => self::collect_feed(
					'cisa_advisory',
					'CISA Cybersecurity Advisories',
					'https://www.cisa.gov/cybersecurity-advisories/all.xml',
					10
				),
			),
			'ubuntu'         => array(
				'Ubuntu Security Notices',
				static fn() => self::collect_feed(
					'ubuntu',
					'Ubuntu Security Notices',
					'https://ubuntu.com/security/notices/rss.xml',
					12
				),
			),
			'github_security' => array(
				'GitHub Security Blog',
				static fn() => self::collect_feed(
					'github_security',
					'GitHub Security Blog',
					'https://github.blog/security/feed/',
					10
				),
			),
			'microsoft_security' => array(
				'Microsoft Security Blog',
				static fn() => self::collect_feed(
					'microsoft_security',
					'Microsoft Security Blog',
					'https://www.microsoft.com/en-us/security/blog/feed/',
					10
				),
			),
			'openai'         => array(
				'OpenAI News',
				static fn() => self::collect_feed(
					'openai',
					'OpenAI News',
					'https://openai.com/news/rss.xml',
					8,
					'/\b(security|cyber|vulnerab|exploit|incident|safety|alignment|red[- ]?team|sandbox|containment|risk)\b/i'
				),
			),
		);

		foreach ( $sources as $key => $source ) {
			$result = call_user_func( $source[1] );
			if ( is_wp_error( $result ) ) {
				$statuses[ $key ] = array(
					'label'   => $source[0],
					'ok'      => false,
					'count'   => 0,
					'message' => $result->get_error_message(),
				);
				continue;
			}

			$result = is_array( $result ) ? $result : array();
			$items  = array_merge( $items, $result );
			$statuses[ $key ] = array(
				'label'   => $source[0],
				'ok'      => true,
				'count'   => count( $result ),
				'message' => '',
			);
		}

		$items = self::normalize_items( $items );
		if ( empty( $items ) ) {
			$last_good = get_option( self::LAST_GOOD_OPTION, array() );
			if ( is_array( $last_good ) && ! empty( $last_good['items'] ) ) {
				$last_good['stale']          = true;
				$last_good['source_status']  = $statuses;
				$last_good['refresh_failed'] = true;
				self::save_status( $last_good );
				return $last_good;
			}

			$failed = array(
				'checked_at'     => gmdate( 'Y-m-d H:i:s' ),
				'items'          => array(),
				'source_status'  => $statuses,
				'stale'          => true,
				'refresh_failed' => true,
				'fingerprint'    => '',
			);
			self::save_status( $failed );
			return $failed;
		}

		$data = array(
			'checked_at'     => gmdate( 'Y-m-d H:i:s' ),
			'items'          => array_slice( $items, 0, self::MAX_ITEMS ),
			'source_status'  => $statuses,
			'stale'          => false,
			'refresh_failed' => false,
		);
		$data['fingerprint'] = hash(
			'sha256',
			(string) wp_json_encode(
				array_map(
					static function ( array $item ): array {
						return array(
							'id'          => (string) ( $item['id'] ?? '' ),
							'title'       => (string) ( $item['title'] ?? '' ),
							'description' => (string) ( $item['description'] ?? '' ),
							'url'         => (string) ( $item['url'] ?? '' ),
							'published'   => (string) ( $item['published'] ?? '' ),
							'severity'    => (string) ( $item['severity'] ?? '' ),
							'score'       => $item['score'] ?? null,
							'due_date'    => (string) ( $item['due_date'] ?? '' ),
							'action'      => (string) ( $item['action'] ?? '' ),
							'categories'  => (array) ( $item['categories'] ?? array() ),
						);
					},
					$data['items']
				)
			)
		);

		set_transient( self::CACHE_KEY, $data, self::CACHE_TTL );
		update_option( self::LAST_GOOD_OPTION, $data, false );
		self::save_status( $data );

		return $data;
	}

	/**
	 * Return the latest source status for the setup screen.
	 *
	 * @return array<string,mixed>
	 */
	public static function status(): array {
		$status = get_option( self::STATUS_OPTION, array() );
		return is_array( $status ) ? $status : array();
	}

	/**
	 * Build long-form, source-backed posts for every newsroom category.
	 *
	 * @param string              $date Human-readable site date.
	 * @param array<string,mixed> $data Normalized live source data.
	 * @return array<int,array<string,mixed>>
	 */
	public static function daily_posts( string $date, array $data ): array {
		$items = is_array( $data['items'] ?? null ) ? $data['items'] : array();
		if ( empty( $items ) ) {
			return array();
		}

		$configs = self::category_configs();
		$posts   = array();

		foreach ( $configs as $slug => $config ) {
			$selected = self::items_for_category( $items, $slug, (int) $config['item_limit'] );
			if ( empty( $selected ) ) {
				continue;
			}

			$top_titles = array_values(
				array_filter(
					array_map(
						static fn( array $item ): string => (string) ( $item['title'] ?? '' ),
						array_slice( $selected, 0, 2 )
					)
				)
			);
			$excerpt = sprintf(
				'Live %1$s intelligence for %2$s, verified from current public advisories and official security feeds. Top items include %3$s.',
				(string) $config['label'],
				$date,
				implode( ' and ', $top_titles )
			);
			$excerpt = wp_trim_words( $excerpt, 34, '.' );

			$item_ids = array_map(
				static fn( array $item ): string => (string) ( $item['id'] ?? '' ),
				$selected
			);
			$fingerprint = hash(
				'sha256',
				(string) ( $data['fingerprint'] ?? '' ) . '|' . $slug . '|' . implode( '|', $item_ids )
			);

			$posts[] = array(
				'title'       => sprintf( (string) $config['title'], $date ),
				'slug'        => (string) $config['post_slug'],
				'categories'  => array( $slug ),
				'excerpt'     => $excerpt,
				'content'     => self::render_article( $date, $config, $selected, $data ),
				'sources'     => self::source_pairs( $selected ),
				'fingerprint' => $fingerprint,
				'source_ids'  => array_values( array_filter( $item_ids ) ),
			);
		}

		return $posts;
	}

	/**
	 * Add time-limited context that was individually checked against its source.
	 *
	 * These records supplement structured feeds for important current stories that
	 * do not have a stable machine-readable endpoint.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private static function collect_verified_context(): array {
		$records = array(
			array(
				'id'          => 'verified-linux-432-cves-july-2026',
				'source_key'  => 'verified_context',
				'source'      => 'The Register',
				'type'        => 'reported_news',
				'title'       => 'Linux kernel team published 432 CVE records across two days',
				'description' => 'The publication burst covered hundreds of kernel CVE records. Administrators should map fixed kernel versions to their distributions instead of treating the count as proof that every host is exposed.',
				'url'         => 'https://www.theregister.com/security/2026/07/22/linux-kernel-team-publishes-432-cves-in-two-days/5276497',
				'published'   => '2026-07-22T17:58:00Z',
				'severity'    => '',
				'score'       => null,
				'cve'         => '',
				'vendor'      => 'Linux',
				'product'     => 'Linux kernel',
				'due_date'    => '',
				'action'      => '',
				'priority'    => 62,
				'active_until' => '2026-08-10',
			),
			array(
				'id'          => 'verified-first-2026-vulnerability-forecast',
				'source_key'  => 'verified_context',
				'source'      => 'Forum of Incident Response and Security Teams',
				'type'        => 'official_context',
				'title'       => 'FIRST raises its 2026 vulnerability forecast to about 66,000 CVEs',
				'description' => 'FIRST reported that disclosures were running above its original forecast and linked the wider uncertainty range partly to AI-assisted vulnerability discovery.',
				'url'         => 'https://www.first.org/newsroom/releases/20260615',
				'published'   => '2026-06-15T09:00:00Z',
				'severity'    => '',
				'score'       => null,
				'cve'         => '',
				'vendor'      => 'FIRST',
				'product'     => '2026 Vulnerability Forecast',
				'due_date'    => '',
				'action'      => '',
				'priority'    => 63,
				'active_until' => '2026-08-31',
			),
			array(
				'id'          => 'verified-cve-2026-55255-langflow',
				'source_key'  => 'verified_context',
				'source'      => 'NIST NVD and CISA KEV',
				'type'        => 'kev',
				'title'       => 'CVE-2026-55255: Langflow cross-user flow authorization bypass',
				'description' => 'Before version 1.9.1, an authenticated attacker could specify another user\'s flow ID and execute that flow. The CNA rates the issue 8.4 High, and CISA lists active exploitation.',
				'url'         => 'https://nvd.nist.gov/vuln/detail/CVE-2026-55255',
				'published'   => '2026-07-07T17:45:10Z',
				'severity'    => 'Known Exploited',
				'score'       => 8.4,
				'cve'         => 'CVE-2026-55255',
				'vendor'      => 'Langflow',
				'product'     => 'Langflow before 1.9.1',
				'due_date'    => '2026-07-10',
				'action'      => 'Apply the current Langflow fix and follow CISA KEV remediation guidance.',
				'priority'    => 121,
				'active_until' => '2026-08-15',
			),
			array(
				'id'          => 'verified-cve-2026-57092-windows-vmswitch',
				'source_key'  => 'verified_context',
				'source'      => 'NIST NVD and Microsoft',
				'type'        => 'vulnerability',
				'title'       => 'CVE-2026-57092: Windows VMSwitch use-after-free privilege escalation',
				'description' => 'Microsoft describes a network-reachable VMSwitch use-after-free that lets an authorized attacker elevate privileges. The Microsoft CNA rates it 9.9 Critical.',
				'url'         => 'https://nvd.nist.gov/vuln/detail/CVE-2026-57092',
				'published'   => '2026-07-14T18:45:52Z',
				'severity'    => 'CRITICAL',
				'score'       => 9.9,
				'cve'         => 'CVE-2026-57092',
				'vendor'      => 'Microsoft',
				'product'     => 'Windows VMSwitch',
				'due_date'    => '',
				'action'      => '',
				'priority'    => 105,
				'active_until' => '2026-08-15',
			),
			array(
				'id'          => 'verified-github-bounty-restructure-july-2026',
				'source_key'  => 'verified_context',
				'source'      => 'GitHub Security Blog',
				'type'        => 'official_news',
				'title'       => 'GitHub restructures public and VIP bug bounty payouts',
				'description' => 'GitHub says reports submitted from July 27 use a new static public payout table, while qualified VIP researchers receive higher rates and closer program access.',
				'url'         => 'https://github.blog/security/next-chapter-restructuring-githubs-bug-bounty-program/',
				'published'   => '2026-07-22T12:00:00Z',
				'severity'    => '',
				'score'       => null,
				'cve'         => '',
				'vendor'      => 'GitHub',
				'product'     => 'Bug Bounty Program',
				'due_date'    => '',
				'action'      => '',
				'priority'    => 61,
				'active_until' => '2026-08-10',
			),
			array(
				'id'          => 'verified-openai-hugging-face-incident-july-2026',
				'source_key'  => 'verified_context',
				'source'      => 'OpenAI',
				'type'        => 'official_news',
				'title'       => 'OpenAI and Hugging Face address a model-evaluation security incident',
				'description' => 'OpenAI reported that evaluation models chained vulnerabilities across research and production systems to reach test solutions, prompting stronger containment, monitoring, and evaluation controls.',
				'url'         => 'https://openai.com/index/hugging-face-model-evaluation-security-incident/',
				'published'   => '2026-07-21T12:00:00Z',
				'severity'    => '',
				'score'       => null,
				'cve'         => '',
				'vendor'      => 'OpenAI',
				'product'     => 'Model evaluation infrastructure',
				'due_date'    => '',
				'action'      => '',
				'priority'    => 64,
				'active_until' => '2026-08-10',
			),
			array(
				'id'          => 'verified-kimi-k3-aisi-july-2026',
				'source_key'  => 'verified_context',
				'source'      => 'UK AI Security Institute',
				'type'        => 'official_news',
				'title'       => 'UK AISI and CAISI publish a preliminary Kimi K3 cyber assessment',
				'description' => 'The joint assessment found Kimi K3 below leading frontier models and reported zero arbitrary-code-execution successes across 41 ExploitBench samples.',
				'url'         => 'https://www.aisi.gov.uk/blog/preliminary-assessment-of-kimi-k3s-cyber-capabilities',
				'published'   => '2026-07-24T12:00:00Z',
				'severity'    => '',
				'score'       => null,
				'cve'         => '',
				'vendor'      => 'Moonshot AI',
				'product'     => 'Kimi K3',
				'due_date'    => '',
				'action'      => '',
				'priority'    => 59,
				'active_until' => '2026-08-15',
			),
		);

		$today = current_time( 'Y-m-d' );
		$items = array();
		foreach ( $records as $record ) {
			if ( $today > (string) $record['active_until'] ) {
				continue;
			}
			unset( $record['active_until'] );
			$items[] = self::item( $record );
		}

		return $items;
	}

	/**
	 * Fetch CISA KEV data.
	 *
	 * @return array<int,array<string,mixed>>|\WP_Error
	 */
	private static function collect_cisa_kev() {
		$data = self::fetch_json( 'https://www.cisa.gov/sites/default/files/feeds/known_exploited_vulnerabilities.json' );
		if ( is_wp_error( $data ) ) {
			return $data;
		}

		$rows = is_array( $data['vulnerabilities'] ?? null ) ? $data['vulnerabilities'] : array();
		usort(
			$rows,
			static fn( array $left, array $right ): int => strcmp( (string) ( $right['dateAdded'] ?? '' ), (string) ( $left['dateAdded'] ?? '' ) )
		);

		$cutoff = strtotime( '-' . self::LOOKBACK_DAYS . ' days' );
		$items  = array();
		foreach ( $rows as $row ) {
			$published = strtotime( (string) ( $row['dateAdded'] ?? '' ) );
			if ( false === $published || $published < $cutoff ) {
				continue;
			}

			$cve = sanitize_text_field( (string) ( $row['cveID'] ?? '' ) );
			if ( '' === $cve ) {
				continue;
			}

			$items[] = self::item(
				array(
					'id'          => 'cisa-kev-' . strtolower( $cve ),
					'source_key'  => 'cisa_kev',
					'source'      => 'CISA Known Exploited Vulnerabilities',
					'type'        => 'kev',
					'title'       => trim( $cve . ': ' . (string) ( $row['vulnerabilityName'] ?? '' ) ),
					'description' => (string) ( $row['shortDescription'] ?? '' ),
					'url'         => 'https://www.cisa.gov/known-exploited-vulnerabilities-catalog?field_cve=' . rawurlencode( $cve ),
					'published'   => gmdate( 'c', $published ),
					'severity'    => 'Known Exploited',
					'score'       => null,
					'cve'         => $cve,
					'vendor'      => (string) ( $row['vendorProject'] ?? '' ),
					'product'     => (string) ( $row['product'] ?? '' ),
					'due_date'    => (string) ( $row['dueDate'] ?? '' ),
					'action'      => (string) ( $row['requiredAction'] ?? '' ),
					'priority'    => 120,
				)
			);
		}

		return array_slice( $items, 0, 30 );
	}

	/**
	 * Fetch recent high and critical NVD records.
	 *
	 * @return array<int,array<string,mixed>>|\WP_Error
	 */
	private static function collect_nvd() {
		$items  = array();
		$errors = array();

		foreach ( array( 'CRITICAL', 'HIGH' ) as $severity ) {
			$start = gmdate( 'Y-m-d\TH:i:s.000', time() - ( self::LOOKBACK_DAYS * DAY_IN_SECONDS ) );
			$end   = gmdate( 'Y-m-d\TH:i:s.000' );
			$url   = add_query_arg(
				array(
					'pubStartDate'   => $start,
					'pubEndDate'     => $end,
					'cvssV3Severity' => $severity,
					'resultsPerPage' => 100,
				),
				'https://services.nvd.nist.gov/rest/json/cves/2.0/'
			);
			$data = self::fetch_json(
				$url,
				array(
					'User-Agent' => 'InfoSecNexus/' . INFOSECNEXUS_VERSION . ' (' . home_url( '/' ) . ')',
				)
			);
			if ( is_wp_error( $data ) ) {
				$errors[] = $data->get_error_message();
				continue;
			}

			$records = is_array( $data['vulnerabilities'] ?? null ) ? $data['vulnerabilities'] : array();
			foreach ( $records as $record ) {
				$cve = is_array( $record['cve'] ?? null ) ? $record['cve'] : array();
				$id  = sanitize_text_field( (string) ( $cve['id'] ?? '' ) );
				if ( '' === $id ) {
					continue;
				}

				$description = '';
				foreach ( (array) ( $cve['descriptions'] ?? array() ) as $candidate ) {
					if ( 'en' === (string) ( $candidate['lang'] ?? '' ) ) {
						$description = (string) ( $candidate['value'] ?? '' );
						break;
					}
				}

				$metric = self::nvd_metric( $cve );
				$items[] = self::item(
					array(
						'id'          => 'nvd-' . strtolower( $id ),
						'source_key'  => 'nvd',
						'source'      => 'NIST National Vulnerability Database',
						'type'        => 'vulnerability',
						'title'       => $id . ': ' . self::description_subject( $description ),
						'description' => $description,
						'url'         => 'https://nvd.nist.gov/vuln/detail/' . rawurlencode( $id ),
						'published'   => (string) ( $cve['published'] ?? '' ),
						'severity'    => (string) ( $metric['severity'] ?? $severity ),
						'score'       => $metric['score'] ?? null,
						'cve'         => $id,
						'vendor'      => '',
						'product'     => '',
						'due_date'    => '',
						'action'      => '',
						'priority'    => 'CRITICAL' === $severity ? 100 : 90,
					)
				);
			}
		}

		if ( empty( $items ) && ! empty( $errors ) ) {
			return new \WP_Error( 'infosecnexus_nvd_unavailable', implode( ' ', array_unique( $errors ) ) );
		}

		return $items;
	}

	/**
	 * Fetch recent GitHub Security Advisories.
	 *
	 * @return array<int,array<string,mixed>>|\WP_Error
	 */
	private static function collect_github_advisories() {
		$data = self::fetch_json(
			'https://api.github.com/advisories?per_page=50&sort=published&direction=desc',
			array(
				'Accept'               => 'application/vnd.github+json',
				'X-GitHub-Api-Version' => '2022-11-28',
				'User-Agent'           => 'InfoSecNexus/' . INFOSECNEXUS_VERSION,
			)
		);
		if ( is_wp_error( $data ) ) {
			return $data;
		}

		$cutoff = time() - ( self::LOOKBACK_DAYS * DAY_IN_SECONDS );
		$items  = array();
		foreach ( $data as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$published = strtotime( (string) ( $row['published_at'] ?? '' ) );
			if ( false === $published || $published < $cutoff ) {
				continue;
			}

			$severity = strtoupper( sanitize_text_field( (string) ( $row['severity'] ?? '' ) ) );
			if ( ! in_array( $severity, array( 'CRITICAL', 'HIGH', 'MEDIUM' ), true ) ) {
				continue;
			}

			$ghsa = sanitize_text_field( (string) ( $row['ghsa_id'] ?? '' ) );
			$cve  = sanitize_text_field( (string) ( $row['cve_id'] ?? '' ) );
			if ( '' === $ghsa ) {
				continue;
			}

			$packages = array();
			foreach ( (array) ( $row['vulnerabilities'] ?? array() ) as $vulnerability ) {
				$package = is_array( $vulnerability['package'] ?? null ) ? $vulnerability['package'] : array();
				$name    = sanitize_text_field( (string) ( $package['name'] ?? '' ) );
				if ( '' !== $name ) {
					$packages[] = $name;
				}
			}

			$score = isset( $row['cvss']['score'] ) && is_numeric( $row['cvss']['score'] ) ? (float) $row['cvss']['score'] : null;
			$items[] = self::item(
				array(
					'id'          => 'github-' . strtolower( $ghsa ),
					'source_key'  => 'github_advisory',
					'source'      => 'GitHub Advisory Database',
					'type'        => 'advisory',
					'title'       => trim( ( '' !== $cve ? $cve . ': ' : '' ) . (string) ( $row['summary'] ?? $ghsa ) ),
					'description' => (string) ( $row['description'] ?? '' ),
					'url'         => (string) ( $row['html_url'] ?? 'https://github.com/advisories/' . $ghsa ),
					'published'   => gmdate( 'c', $published ),
					'severity'    => $severity,
					'score'       => $score,
					'cve'         => $cve,
					'vendor'      => 'GitHub Advisory Database',
					'product'     => implode( ', ', array_slice( array_unique( $packages ), 0, 3 ) ),
					'due_date'    => '',
					'action'      => '',
					'priority'    => 'CRITICAL' === $severity ? 95 : ( 'HIGH' === $severity ? 85 : 65 ),
				)
			);
		}

		return $items;
	}

	/**
	 * Fetch a trusted RSS/Atom source using WordPress' feed parser.
	 *
	 * @param string $source_key Internal source key.
	 * @param string $source Source label.
	 * @param string $url Feed URL.
	 * @param int    $limit Maximum accepted items.
	 * @param string $filter Optional title/summary filter regex.
	 * @return array<int,array<string,mixed>>|\WP_Error
	 */
	private static function collect_feed( string $source_key, string $source, string $url, int $limit, string $filter = '' ) {
		require_once ABSPATH . WPINC . '/feed.php';

		$cache_filter = static fn(): int => self::CACHE_TTL;
		add_filter( 'wp_feed_cache_transient_lifetime', $cache_filter );
		$feed = fetch_feed( $url );
		remove_filter( 'wp_feed_cache_transient_lifetime', $cache_filter );

		if ( is_wp_error( $feed ) ) {
			return $feed;
		}

		$cutoff = time() - ( self::LOOKBACK_DAYS * DAY_IN_SECONDS );
		$items  = array();
		foreach ( $feed->get_items( 0, min( 40, $limit * 4 ) ) as $entry ) {
			$title       = self::clean_text( (string) $entry->get_title(), 30 );
			$description = self::clean_text( (string) $entry->get_description(), 90 );
			$search_text = $title . ' ' . $description;
			if ( '' !== $filter && 1 !== preg_match( $filter, $search_text ) ) {
				continue;
			}

			$published = (int) $entry->get_date( 'U' );
			if ( $published > 0 && $published < $cutoff ) {
				continue;
			}

			$link = esc_url_raw( (string) $entry->get_link() );
			if ( '' === $title || '' === $link ) {
				continue;
			}

			$cve = '';
			if ( preg_match( '/CVE-\d{4}-\d{4,}/i', $search_text, $matches ) ) {
				$cve = strtoupper( $matches[0] );
			}

			$items[] = self::item(
				array(
					'id'          => $source_key . '-' . md5( $link ),
					'source_key'  => $source_key,
					'source'      => $source,
					'type'        => 'official_news',
					'title'       => $title,
					'description' => $description,
					'url'         => $link,
					'published'   => $published > 0 ? gmdate( 'c', $published ) : '',
					'severity'    => '',
					'score'       => null,
					'cve'         => $cve,
					'vendor'      => $source,
					'product'     => '',
					'due_date'    => '',
					'action'      => '',
					'priority'    => 55,
				)
			);

			if ( count( $items ) >= $limit ) {
				break;
			}
		}

		return $items;
	}

	/**
	 * Fetch and decode JSON with safe defaults.
	 *
	 * @param string              $url Source URL.
	 * @param array<string,string> $headers Request headers.
	 * @return array<mixed>|\WP_Error
	 */
	private static function fetch_json( string $url, array $headers = array() ) {
		$response = wp_remote_get(
			$url,
			array(
				'timeout'     => 14,
				'redirection' => 3,
				'headers'     => $headers,
				'user-agent'  => 'InfoSecNexus/' . INFOSECNEXUS_VERSION . '; ' . home_url( '/' ),
			)
		);
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status = (int) wp_remote_retrieve_response_code( $response );
		if ( $status < 200 || $status >= 300 ) {
			return new \WP_Error(
				'infosecnexus_live_source_http',
				sprintf( 'Source returned HTTP %d.', $status )
			);
		}

		$data = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $data ) ) {
			return new \WP_Error( 'infosecnexus_live_source_json', 'Source returned invalid JSON.' );
		}

		return $data;
	}

	/**
	 * Normalize, categorize, deduplicate, and sort source items.
	 *
	 * @param array<int,array<string,mixed>> $items Raw items.
	 * @return array<int,array<string,mixed>>
	 */
	private static function normalize_items( array $items ): array {
		$unique = array();
		foreach ( $items as $item ) {
			if ( ! is_array( $item ) || empty( $item['title'] ) || empty( $item['url'] ) ) {
				continue;
			}

			$key = ! empty( $item['cve'] ) ? strtolower( (string) $item['cve'] ) : strtolower( (string) $item['url'] );
			if ( isset( $unique[ $key ] ) ) {
				continue;
			}

			$item['categories'] = self::item_categories( $item );
			$unique[ $key ]     = $item;
		}

		$items = array_values( $unique );
		usort(
			$items,
			static function ( array $left, array $right ): int {
				$priority = (int) ( $right['priority'] ?? 0 ) <=> (int) ( $left['priority'] ?? 0 );
				if ( 0 !== $priority ) {
					return $priority;
				}
				return strtotime( (string) ( $right['published'] ?? '' ) ) <=> strtotime( (string) ( $left['published'] ?? '' ) );
			}
		);

		return $items;
	}

	/**
	 * Return a normalized source item.
	 *
	 * @param array<string,mixed> $item Raw item.
	 * @return array<string,mixed>
	 */
	private static function item( array $item ): array {
		$item['title']       = self::clean_text( (string) ( $item['title'] ?? '' ), 36 );
		$item['description'] = self::clean_text( (string) ( $item['description'] ?? '' ), 100 );
		$item['source']      = sanitize_text_field( (string) ( $item['source'] ?? '' ) );
		$item['vendor']      = sanitize_text_field( (string) ( $item['vendor'] ?? '' ) );
		$item['product']     = sanitize_text_field( (string) ( $item['product'] ?? '' ) );
		$item['url']         = esc_url_raw( (string) ( $item['url'] ?? '' ) );
		return $item;
	}

	/**
	 * Derive newsroom categories from structured fields and keywords.
	 *
	 * @param array<string,mixed> $item Source item.
	 * @return string[]
	 */
	private static function item_categories( array $item ): array {
		$text       = strtolower(
			implode(
				' ',
				array(
					(string) ( $item['title'] ?? '' ),
					(string) ( $item['description'] ?? '' ),
					(string) ( $item['vendor'] ?? '' ),
					(string) ( $item['product'] ?? '' ),
					(string) ( $item['source'] ?? '' ),
				)
			)
		);
		$categories = array( 'cybersecurity' );
		$patterns   = array(
			'linux-administration'   => '/\b(linux(?: kernel)?|kernel\.org|ubuntu|debian|red hat|rhel|gnu|systemd|snapd|snap-confine|sudo|openssh|telnetd)\b/i',
			'devops'                 => '/\b(devops|github|gitlab|ci\/cd|pipeline|runner|docker|container|kubernetes|helm|jenkins|build|dependency|package|npm|pypi|maven|etcd|supply chain)\b/i',
			'artificial-intelligence' => '/\b(ai|artificial intelligence|agent|agentic|llm|langflow|openai|hugging face|model|copilot|mcp|prompt|bedrock)\b/i',
			'cloud-security'         => '/\b(cloud|aws|amazon web services|azure|google cloud|gcp|iam|kubernetes|container|tenant|service account)\b/i',
			'windows-security'       => '/\b(microsoft|windows|active directory|ad fs|sharepoint|exchange|office|bitlocker|entra|vmswitch)\b/i',
			'network-security'       => '/\b(router|firewall|vpn|sonicwall|fortinet|fortigate|dd-wrt|dns|network|switch|gateway|edge device|tcp|udp|ble|mesh)\b/i',
			'web-security'           => '/\b(wordpress|web|browser|http|api|sql injection|xss|cross-site|csrf|ssrf|nginx|apache|php|codeigniter|elementor)\b/i',
		);

		foreach ( $patterns as $category => $pattern ) {
			if ( 1 === preg_match( $pattern, $text ) ) {
				$categories[] = $category;
			}
		}

		$severity = strtoupper( (string) ( $item['severity'] ?? '' ) );
		if (
			'kev' === (string) ( $item['type'] ?? '' )
			|| in_array( $severity, array( 'CRITICAL', 'HIGH', 'KNOWN EXPLOITED' ), true )
		) {
			$categories[] = 'critical-cves';
		}

		return array_values( array_unique( $categories ) );
	}

	/**
	 * Return the most relevant items for a category.
	 *
	 * @param array<int,array<string,mixed>> $items All source items.
	 * @param string                         $category Category slug.
	 * @param int                            $limit Maximum items.
	 * @return array<int,array<string,mixed>>
	 */
	private static function items_for_category( array $items, string $category, int $limit ): array {
		$relevant = array();
		foreach ( $items as $item ) {
			$categories = is_array( $item['categories'] ?? null ) ? $item['categories'] : array();
			if ( 'tutorials' === $category || in_array( $category, $categories, true ) ) {
				$relevant[] = $item;
			}
		}

		$buckets = array(
			'kev'   => array(),
			'risk'  => array(),
			'news'  => array(),
		);
		foreach ( $relevant as $item ) {
			$type = (string) ( $item['type'] ?? '' );
			if ( 'kev' === $type ) {
				$buckets['kev'][] = $item;
				continue;
			}
			if ( in_array( $type, array( 'vulnerability', 'advisory' ), true ) ) {
				$buckets['risk'][] = $item;
				continue;
			}
			$buckets['news'][] = $item;
		}

		if ( 'critical-cves' === $category ) {
			$quotas = array(
				'kev'  => min( 4, $limit ),
				'risk' => max( 1, $limit - min( 4, $limit ) - 1 ),
				'news' => 1,
			);
		} else {
			$news_quota = max( 2, (int) ceil( $limit * 0.35 ) );
			$kev_quota  = min( 2, max( 0, $limit - $news_quota - 2 ) );
			$quotas     = array(
				'kev'  => $kev_quota,
				'risk' => max( 2, $limit - $news_quota - $kev_quota ),
				'news' => $news_quota,
			);
		}

		$selected = array();
		foreach ( $quotas as $bucket => $quota ) {
			$selected = array_merge( $selected, array_slice( $buckets[ $bucket ], 0, $quota ) );
		}

		$selected_ids = array_column( $selected, 'id' );
		foreach ( $relevant as $item ) {
			$id = (string) ( $item['id'] ?? '' );
			if ( in_array( $id, $selected_ids, true ) ) {
				continue;
			}
			$selected[]    = $item;
			$selected_ids[] = $id;
			if ( count( $selected ) >= $limit ) {
				break;
			}
		}

		if ( count( $selected ) < min( $limit, 6 ) ) {
			foreach ( $items as $item ) {
				$id = (string) ( $item['id'] ?? '' );
				if ( in_array( $id, $selected_ids, true ) ) {
					continue;
				}
				$selected[]    = $item;
				$selected_ids[] = $id;
				if ( count( $selected ) >= min( $limit, 6 ) ) {
					break;
				}
			}
		}

		return array_slice( $selected, 0, $limit );
	}

	/**
	 * Render a long, source-backed briefing.
	 *
	 * @param string                         $date Human date.
	 * @param array<string,mixed>            $config Category configuration.
	 * @param array<int,array<string,mixed>> $items Selected source items.
	 * @param array<string,mixed>            $data Source collection metadata.
	 * @return string
	 */
	private static function render_article( string $date, array $config, array $items, array $data ): string {
		$checked_at = (string) ( $data['checked_at'] ?? '' );
		$checked    = '' !== $checked_at ? get_date_from_gmt( $checked_at, 'F j, Y \a\t g:i a T' ) : $date;
		$counts     = self::summary_counts( $items );
		$sources    = self::source_pairs( $items );
		$notes      = is_array( $config['review_notes'] ?? null ) ? $config['review_notes'] : array();

		$content  = '<p class="isnx-live-deck">' . esc_html( (string) $config['intro'] ) . '</p>';
		$content .= '<p><strong>Live verification:</strong> This briefing was assembled from public CISA, NIST NVD, GitHub, Ubuntu, Microsoft, and other official publisher feeds checked on ' . esc_html( $checked ) . '. Existing posts are preserved and repeated source IDs are deduplicated.</p>';
		$content .= '<!--more-->';
		$content .= '<h2>Executive summary</h2>';
		$content .= '<p>The current source set produced ' . esc_html( (string) count( $items ) ) . ' relevant updates for this briefing. It includes ' . esc_html( (string) $counts['kev'] ) . ' CISA Known Exploited Vulnerabilities, ' . esc_html( (string) $counts['critical'] ) . ' critical records, ' . esc_html( (string) $counts['high'] ) . ' high-severity records, and ' . esc_html( (string) $counts['news'] ) . ' official publisher updates. Severity alone is not treated as proof of exposure: teams should verify products, versions, reachability, privileges, and available mitigations.</p>';
		$content .= '<p>' . esc_html( (string) $config['context'] ) . '</p>';
		$content .= '<h2>Top verified developments</h2>';

		foreach ( $items as $index => $item ) {
			$content .= self::render_item( $item, (string) $config['label'], (string) ( $notes[ $index % max( 1, count( $notes ) ) ] ?? $config['context'] ) );
		}

		$content .= '<h2>What teams should do next</h2>';
		$content .= '<p>Use the items above as a review queue, not as an automatic statement that every environment is vulnerable. Match each product or service against a current asset inventory, confirm the installed version, and identify whether an attacker can reach the affected path. CISA KEV entries deserve special attention because their inclusion is based on evidence of exploitation in the wild.</p>';
		$content .= '<ul>';
		foreach ( (array) $config['actions'] as $action ) {
			$content .= '<li>' . esc_html( (string) $action ) . '</li>';
		}
		$content .= '</ul>';
		$content .= '<h2>Prioritization method</h2>';
		$content .= '<p>Start with active exploitation, then combine internet exposure, privilege level, sensitive data access, business criticality, and recovery difficulty. A lower-scored issue on a public administrative service can be more urgent than a higher-scored issue in an unreachable component. Record why an item was accelerated, deferred, mitigated, or found not applicable so the decision can be reviewed later.</p>';
		$content .= '<p>For software updates, validate the vendor-fixed version and test the change in a representative environment. For cloud and managed services, confirm whether the provider has already deployed a platform-side fix or whether customer configuration is still required. For AI and automation systems, include connector permissions, stored credentials, tool execution, and untrusted input in the exposure review.</p>';
		$content .= '<h2>Validation checklist</h2>';
		$content .= '<ol><li>Confirm the source advisory, publication date, affected product, and fixed version.</li><li>Locate internet-facing, privileged, and business-critical instances before broad backlog work.</li><li>Apply the vendor patch or documented mitigation and keep an owner on every exception.</li><li>Verify the running version, service restart or reboot state, and control health after the change.</li><li>Review logs and alerts for exploitation indicators appropriate to the affected component.</li><li>Record evidence and schedule a follow-up for systems that cannot be remediated immediately.</li></ol>';
		$content .= '<h2>Accuracy and source notes</h2>';
		$content .= '<p>Automated feeds can be revised after initial publication. NVD enrichment, CVSS scores, affected-version ranges, and vendor guidance may change as maintainers add evidence. This page therefore shows the source and check time and links readers to the current advisory. Claims without a matching trusted source are not added to the live briefing.</p>';
		$content .= '<p>Items described as Known Exploited come from the CISA KEV catalog. Other vulnerability severities reflect the value reported by NVD or the publishing CNA or advisory database at collection time. An official news post confirms what its publisher announced; it does not automatically prove broader third-party claims.</p>';
		$content .= '<h2>Sources</h2><ul>';
		foreach ( $sources as $source ) {
			$content .= '<li><a href="' . esc_url( (string) $source[1] ) . '" rel="nofollow noopener" target="_blank">' . esc_html( (string) $source[0] ) . '</a></li>';
		}
		$content .= '</ul>';
		$content .= '<h2>Frequently asked questions</h2>';
		$content .= '<h3>Is every item listed here exploitable in my environment?</h3><p>No. Only CISA KEV placement is treated as an active-exploitation signal, and even then your own exposure depends on product use, version, configuration, and reachability. Verify inventory and vendor guidance before making a final decision.</p>';
		$content .= '<h3>Why can a score change after publication?</h3><p>CVE records are often enriched over time. NVD, a CNA, or a vendor may add a vector, change an affected range, or revise analysis when new evidence becomes available. The linked source remains the authority for the latest record.</p>';
		$content .= '<h3>How often is this briefing refreshed?</h3><p>The theme checks its live source cache twice daily through WordPress cron and whenever an administrator requests a manual refresh. WordPress cron runs when the site receives a request, so the exact minute can vary on low-traffic sites.</p>';

		return $content;
	}

	/**
	 * Render one source item without reproducing long source passages.
	 *
	 * @param array<string,mixed> $item Source item.
	 * @param string              $category_label Category label.
	 * @param string              $review_note Category-specific review note.
	 * @return string
	 */
	private static function render_item( array $item, string $category_label, string $review_note ): string {
		$published = strtotime( (string) ( $item['published'] ?? '' ) );
		$date      = false !== $published ? wp_date( 'F j, Y', $published ) : 'Date not supplied';
		$severity  = trim( (string) ( $item['severity'] ?? '' ) );
		$score     = isset( $item['score'] ) && is_numeric( $item['score'] ) ? number_format_i18n( (float) $item['score'], 1 ) : '';
		$product   = trim( implode( ' ', array_filter( array( (string) ( $item['vendor'] ?? '' ), (string) ( $item['product'] ?? '' ) ) ) ) );
		$meta      = array_filter(
			array(
				(string) ( $item['source'] ?? '' ),
				$date,
				$severity,
				'' !== $score ? 'CVSS ' . $score : '',
				$product,
			)
		);

		$content  = '<section class="isnx-live-item">';
		$content .= '<h3>' . esc_html( (string) $item['title'] ) . '</h3>';
		$content .= '<p class="isnx-live-item__meta">' . esc_html( implode( ' | ', array_unique( $meta ) ) ) . '</p>';

		$description = wp_trim_words( (string) ( $item['description'] ?? '' ), 20, '&hellip;' );
		if ( '' !== $description ) {
			$content .= '<p><strong>Source summary:</strong> ' . esc_html( $description ) . '</p>';
		}

		if ( 'kev' === (string) ( $item['type'] ?? '' ) ) {
			$content .= '<p>CISA lists this issue in the Known Exploited Vulnerabilities catalog, which makes confirmed exploitation the leading prioritization signal. Review the catalog due date and required action, then identify exposed assets before normal severity-only backlog work.</p>';
			if ( ! empty( $item['due_date'] ) ) {
				$content .= '<p><strong>CISA due date:</strong> ' . esc_html( (string) $item['due_date'] ) . '. ';
				$content .= ! empty( $item['action'] ) ? esc_html( wp_trim_words( (string) $item['action'], 24, '&hellip;' ) ) : 'Follow the current catalog action.';
				$content .= '</p>';
			}
		} elseif ( 'vulnerability' === (string) ( $item['type'] ?? '' ) || 'advisory' === (string) ( $item['type'] ?? '' ) ) {
			$content .= '<p>This record is a current vulnerability or package advisory. Confirm the affected version range and vendor fix before deployment, then prioritize instances that are public, privileged, or connected to sensitive data and production workflows.</p>';
		} else {
			$content .= '<p>This is an official publisher update rather than a standalone proof of customer exposure. Read the linked announcement for its exact scope, then translate any required product, policy, or operational change into an owned task.</p>';
		}

		$content .= '<p><strong>' . esc_html( $category_label ) . ' review:</strong> ' . esc_html( $review_note ) . '</p>';
		$content .= '<p><a href="' . esc_url( (string) $item['url'] ) . '" rel="nofollow noopener" target="_blank">Read the current source record</a></p>';
		$content .= '</section>';

		return $content;
	}

	/**
	 * Category titles and editorial guidance.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	private static function category_configs(): array {
		return array(
			'critical-cves' => array(
				'label'        => 'Critical CVE',
				'title'        => 'Critical CVE Live Watch for %s: Exploited and High-Risk Vulnerabilities',
				'post_slug'    => 'daily-cve-watch-kev-nvd-patch-priority',
				'item_limit'   => 10,
				'intro'        => 'A live vulnerability watch focused on exploited flaws, critical and high-severity records, remediation deadlines, and practical patch priority.',
				'context'      => 'The fastest way to reduce vulnerability risk is to combine exploitation evidence with your own exposure. This briefing separates CISA KEV entries from newly published high-severity records so patch teams can see which signals carry the strongest urgency.',
				'review_notes' => array(
					'Check internet-facing and administrative instances first, then confirm the fixed version from the vendor.',
					'Map the affected product to asset owners and set a validation deadline before closing remediation.',
					'Look for exploitation indicators while patching, especially where the service was publicly reachable.',
				),
				'actions'      => array(
					'Compare every CISA KEV item with the external asset inventory and emergency patch queue.',
					'Confirm affected versions from vendor guidance instead of relying on scanner titles alone.',
					'Assign same-day owners to public, privileged, or business-critical matches.',
					'Preserve logs and review detection coverage while remediation is in progress.',
					'Document compensating controls and expiry dates for systems that cannot be patched immediately.',
				),
			),
			'cybersecurity' => array(
				'label'        => 'Cybersecurity',
				'title'        => 'Live Cybersecurity News Brief for %s: Exploits, Platform Security, and Response',
				'post_slug'    => 'cyber-security-brief-exploitation-signals-response-focus',
				'item_limit'   => 10,
				'intro'        => 'A source-backed daily cybersecurity briefing covering active exploitation, major advisories, platform security changes, and defensive priorities.',
				'context'      => 'The current threat picture is shaped by both exploit activity and a growing volume of vulnerability disclosures. Useful triage therefore starts with evidence of abuse, affected business systems, and recovery impact rather than a raw CVE count.',
				'review_notes' => array(
					'Translate the update into an asset, owner, decision, and verification step rather than leaving it as awareness-only news.',
					'Separate confirmed exposure from industry-wide reporting so response resources stay focused.',
					'Review whether identity, public access, sensitive data, or recovery paths increase the operational impact.',
				),
				'actions'      => array(
					'Start the daily review with CISA KEV additions and official vendor advisories.',
					'Map relevant items to internet-facing services, identity systems, remote access, and admin tooling.',
					'Create detection or hunting tasks for exposed products while patching is underway.',
					'Escalate decisions that affect customer data, domain control, or production availability.',
					'Publish a short internal update listing facts, owners, deadlines, and remaining uncertainty.',
				),
			),
			'linux-administration' => array(
				'label'        => 'Linux Security',
				'title'        => 'Live Linux Security Brief for %s: Kernel, Packages, and Service Risk',
				'post_slug'    => 'linux-security-brief-kernel-package-service-checks',
				'item_limit'   => 8,
				'intro'        => 'Current Linux security intelligence for kernel updates, distribution notices, exposed services, package risk, and post-patch verification.',
				'context'      => 'Linux teams are handling a high disclosure volume, but the operational question remains specific: which running kernels, packages, services, containers, or appliance components are affected and reachable in this environment?',
				'review_notes' => array(
					'Compare the advisory with distribution package versions and the kernel actually loaded after reboot.',
					'Check whether the affected component is exposed through SSH, web, network, container, or management paths.',
					'Verify service restarts, loaded modules, and live-patch state after the package change.',
				),
				'actions'      => array(
					'Review Ubuntu and vendor notices against installed package and kernel versions.',
					'Prioritize public services, hypervisors, container hosts, and privileged administration systems.',
					'Check reboot-required state and confirm the fixed kernel or library is running.',
					'Use temporary isolation or service controls when maintenance cannot happen immediately.',
					'Keep version output, reboot evidence, and monitoring checks with the change record.',
				),
			),
			'devops' => array(
				'label'        => 'DevOps Security',
				'title'        => 'Live DevOps Security Brief for %s: Pipelines, Dependencies, and Secrets',
				'post_slug'    => 'devops-security-brief-pipelines-secrets-build-dependencies',
				'item_limit'   => 8,
				'intro'        => 'Live DevSecOps coverage for build systems, source control, dependencies, automation agents, containers, and credential exposure.',
				'context'      => 'Pipeline security now includes both conventional package risk and agent-driven workflows that can act on untrusted pull requests, comments, repositories, and build output. Permissions and secret boundaries matter as much as scanner results.',
				'review_notes' => array(
					'Identify whether untrusted repository content can reach privileged runners, tokens, or deployment tools.',
					'Check dependency reachability and fixed versions before blocking or approving a release.',
					'Reduce persistent credentials and keep build jobs isolated from production management paths.',
				),
				'actions'      => array(
					'Review current GitHub advisories against dependencies used by builds and internal services.',
					'Protect pull-request workflows from untrusted code execution and over-scoped tokens.',
					'Rotate secrets exposed in logs, artifacts, caches, or compromised runner workspaces.',
					'Use reviewed branches, isolated runners, pinned actions, and least-privilege deployment identities.',
					'Validate container base images and package lock files after security updates.',
				),
			),
			'artificial-intelligence' => array(
				'label'        => 'AI Security',
				'title'        => 'Live AI Security Brief for %s: Agents, Prompt Injection, and Model Risk',
				'post_slug'    => 'ai-security-brief-prompt-injection-connectors-data-boundaries',
				'item_limit'   => 8,
				'intro'        => 'Verified AI security news and advisories covering agent frameworks, prompt injection, tool access, sandboxing, model evaluation, and sensitive data boundaries.',
				'context'      => 'AI security incidents increasingly cross the boundary between model output and real tools. The key review is not only what a model can say, but what its agent harness, connectors, credentials, network access, and execution environment allow it to do.',
				'review_notes' => array(
					'Inventory tool permissions, connector access, stored credentials, and network reach available to the agent.',
					'Treat repository text, retrieved documents, web pages, and user prompts as untrusted input.',
					'Require approvals and durable logs before an agent changes data or invokes sensitive tools.',
				),
				'actions'      => array(
					'List AI systems that can access code, tickets, cloud services, documents, email, or production tools.',
					'Test indirect prompt injection through retrieved content and collaboration workflows.',
					'Separate evaluation sandboxes from production credentials and unrestricted network access.',
					'Add human approval for high-impact actions and monitor unexpected tool sequences.',
					'Rotate credentials and investigate reachable systems after any agent containment failure.',
				),
			),
			'tutorials' => array(
				'label'        => 'Security Tutorial',
				'title'        => 'Tutorial for %s: Turn Today\'s Security Advisories into an Action Plan',
				'post_slug'    => 'tutorial-run-daily-vulnerability-standup',
				'item_limit'   => 6,
				'intro'        => 'A practical tutorial for converting today\'s verified advisories into a prioritized queue with owners, evidence, and follow-up.',
				'context'      => 'A daily security review should be short enough to run consistently and detailed enough to drive real work. The source items below are used as examples for an exploit-first triage workflow.',
				'review_notes' => array(
					'Write down the affected asset, owner, current exposure, decision, and proof required for closure.',
					'Use patch-now, mitigate-now, investigate, monitor, or not-applicable as explicit outcomes.',
					'End the review with deadlines and unresolved questions rather than a list of links.',
				),
				'actions'      => array(
					'Open the source record and confirm the product, version, severity, and exploitation status.',
					'Search the asset inventory and identify the service owner before assigning priority.',
					'Choose patch, mitigation, isolation, monitoring, or documented non-applicability.',
					'Define the exact evidence needed to verify completion.',
					'Review urgent exceptions again at the next daily standup.',
				),
			),
			'cloud-security' => array(
				'label'        => 'Cloud Security',
				'title'        => 'Live Cloud Security Brief for %s: IAM, Managed Services, and Exposure',
				'post_slug'    => 'cloud-security-brief-bulletins-iam-public-exposure',
				'item_limit'   => 8,
				'intro'        => 'Current cloud security developments for managed services, IAM, public exposure, containers, workload identity, and provider-side advisories.',
				'context'      => 'Cloud risk depends on both provider updates and tenant configuration. Teams need to distinguish platform-side fixes from customer actions involving IAM, network exposure, images, service accounts, and logging.',
				'review_notes' => array(
					'Confirm whether the provider has remediated the platform or whether tenant configuration remains exposed.',
					'Review public endpoints, privileged identities, service accounts, and cross-account trust.',
					'Keep audit logs outside the workload account and verify they cover the affected control plane.',
				),
				'actions'      => array(
					'Map provider and package advisories to accounts, projects, regions, clusters, and managed services in use.',
					'Review public storage, load balancers, admin ports, and broad network rules.',
					'Remove stale keys, broad roles, unused service accounts, and persistent administrative access.',
					'Patch worker nodes, container images, agents, and self-managed control-plane components.',
					'Confirm centralized audit logging and alerting after every remediation.',
				),
			),
			'windows-security' => array(
				'label'        => 'Windows Security',
				'title'        => 'Live Windows Security Brief for %s: Microsoft Updates and Identity Risk',
				'post_slug'    => 'windows-security-brief-update-review-identity-controls',
				'item_limit'   => 8,
				'intro'        => 'Live Windows and Microsoft security coverage for Patch Tuesday, identity systems, SharePoint, Exchange, endpoints, servers, and privilege exposure.',
				'context'      => 'Microsoft remediation should connect the Security Update Guide and active-exploitation signals to the specific products and builds deployed across endpoints, servers, identity platforms, and collaboration infrastructure.',
				'review_notes' => array(
					'Check supported builds, update installation, restart state, and the current running version.',
					'Prioritize domain, federation, collaboration, and internet-facing servers before normal endpoint queues.',
					'Review privileged access and endpoint telemetry for signs of abuse before and after patching.',
				),
				'actions'      => array(
					'Match Microsoft and CISA records to Windows builds and server products in inventory.',
					'Prioritize identity, SharePoint, Exchange, remote access, and domain-privileged systems.',
					'Test monthly updates, install promptly, and validate reboot or service restart completion.',
					'Review EDR health, tamper protection, authentication logs, and privileged group changes.',
					'Give every patch exception a business owner, mitigation, and expiry date.',
				),
			),
			'network-security' => array(
				'label'        => 'Network Security',
				'title'        => 'Live Network Security Brief for %s: Edge Devices, VPNs, and Segmentation',
				'post_slug'    => 'network-security-brief-edge-devices-dns-segmentation',
				'item_limit'   => 8,
				'intro'        => 'Current network security intelligence for edge appliances, routers, firewalls, VPNs, DNS, management planes, and segmentation controls.',
				'context'      => 'Edge systems often combine public reachability with privileged access to internal networks. Inventory accuracy, supported firmware, restricted management paths, and independent logging are therefore central to response.',
				'review_notes' => array(
					'Identify exposed management interfaces and confirm the exact firmware or software version.',
					'Restrict administrative access to trusted networks and rotate credentials after suspected compromise.',
					'Use flow, DNS, authentication, and configuration-change logs to validate containment.',
				),
				'actions'      => array(
					'Compare KEV and vendor advisories with firewalls, routers, VPNs, gateways, and switches in inventory.',
					'Remove public management exposure and require approved administrative paths.',
					'Patch or replace unsupported edge devices and preserve configurations before changes.',
					'Rotate device credentials and review new accounts, routes, policies, and tunnels.',
					'Validate segmentation and centralized logging after remediation.',
				),
			),
			'web-security' => array(
				'label'        => 'Web Security',
				'title'        => 'Live Web Security Brief for %s: APIs, WordPress, and Application Risk',
				'post_slug'    => 'web-security-brief-apis-headers-wordpress-attack-surface',
				'item_limit'   => 8,
				'intro'        => 'Live web application intelligence for WordPress, APIs, authentication, authorization, injection flaws, dependencies, and browser-facing controls.',
				'context'      => 'Web exposure is determined by reachable routes, roles, data paths, plugin and framework versions, and compensating controls. Public exploitability and authentication requirements should guide the first response.',
				'review_notes' => array(
					'Confirm whether the vulnerable route, plugin, framework, or API behavior is enabled and public.',
					'Patch the component, test authentication and authorization boundaries, and review suspicious requests.',
					'Use a WAF as temporary risk reduction where appropriate, but keep the permanent software fix owned.',
				),
				'actions'      => array(
					'Inventory WordPress core, plugins, themes, frameworks, and public API versions.',
					'Prioritize unauthenticated injection, authorization bypass, file access, and remote execution paths.',
					'Patch affected components and remove unused or abandoned extensions.',
					'Review web, application, authentication, and administrative change logs for abuse.',
					'Validate security headers, least-privilege roles, backups, and recovery after remediation.',
				),
			),
		);
	}

	/**
	 * Extract a useful NVD metric.
	 *
	 * @param array<string,mixed> $cve NVD CVE data.
	 * @return array{score:float|null,severity:string}
	 */
	private static function nvd_metric( array $cve ): array {
		$metrics = is_array( $cve['metrics'] ?? null ) ? $cve['metrics'] : array();
		$best    = array(
			'score'    => null,
			'severity' => '',
		);
		foreach ( array( 'cvssMetricV40', 'cvssMetricV31', 'cvssMetricV30' ) as $key ) {
			$rows = is_array( $metrics[ $key ] ?? null ) ? $metrics[ $key ] : array();
			foreach ( $rows as $row ) {
				if ( empty( $row['cvssData'] ) || ! is_array( $row['cvssData'] ) ) {
					continue;
				}
				$data  = $row['cvssData'];
				$score = isset( $data['baseScore'] ) && is_numeric( $data['baseScore'] ) ? (float) $data['baseScore'] : null;
				if ( null === $score || ( null !== $best['score'] && $score <= $best['score'] ) ) {
					continue;
				}
				$best = array(
					'score'    => $score,
					'severity' => strtoupper( sanitize_text_field( (string) ( $data['baseSeverity'] ?? '' ) ) ),
				);
			}
		}

		return $best;
	}

	/**
	 * Create a short subject from an advisory description.
	 *
	 * @param string $description Description.
	 * @return string
	 */
	private static function description_subject( string $description ): string {
		$description = self::clean_text( $description, 12 );
		if ( '' === $description ) {
			return 'New vulnerability record';
		}
		return ucfirst( rtrim( $description, '. ' ) );
	}

	/**
	 * Strip markup and limit text.
	 *
	 * @param string $text Raw text.
	 * @param int    $words Word limit.
	 * @return string
	 */
	private static function clean_text( string $text, int $words ): string {
		$text = html_entity_decode( wp_strip_all_tags( $text, true ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$text = preg_replace( '/\s+/u', ' ', $text );
		$text = is_string( $text ) ? trim( $text ) : '';
		return wp_trim_words( $text, $words, '&hellip;' );
	}

	/**
	 * Count source item types.
	 *
	 * @param array<int,array<string,mixed>> $items Items.
	 * @return array{kev:int,critical:int,high:int,news:int}
	 */
	private static function summary_counts( array $items ): array {
		$counts = array(
			'kev'      => 0,
			'critical' => 0,
			'high'     => 0,
			'news'     => 0,
		);
		foreach ( $items as $item ) {
			$type     = (string) ( $item['type'] ?? '' );
			$severity = strtoupper( (string) ( $item['severity'] ?? '' ) );
			if ( 'kev' === $type ) {
				++$counts['kev'];
			}
			if ( 'CRITICAL' === $severity ) {
				++$counts['critical'];
			}
			if ( 'HIGH' === $severity ) {
				++$counts['high'];
			}
			if ( 'official_news' === $type ) {
				++$counts['news'];
			}
		}
		return $counts;
	}

	/**
	 * Return unique source title and URL pairs.
	 *
	 * @param array<int,array<string,mixed>> $items Items.
	 * @return array<int,string[]>
	 */
	private static function source_pairs( array $items ): array {
		$sources = array();
		foreach ( $items as $item ) {
			$url   = (string) ( $item['url'] ?? '' );
			$title = (string) ( $item['source'] ?? '' );
			if ( '' === $url || '' === $title ) {
				continue;
			}
			$sources[ $url ] = array( $title . ': ' . (string) ( $item['title'] ?? '' ), $url );
		}
		return array_values( $sources );
	}

	/**
	 * Save compact source status.
	 *
	 * @param array<string,mixed> $data Collected data.
	 */
	private static function save_status( array $data ): void {
		$source_status = is_array( $data['source_status'] ?? null ) ? $data['source_status'] : array();
		update_option(
			self::STATUS_OPTION,
			array(
				'checked_at'     => (string) ( $data['checked_at'] ?? '' ),
				'item_count'     => count( (array) ( $data['items'] ?? array() ) ),
				'stale'          => ! empty( $data['stale'] ),
				'refresh_failed' => ! empty( $data['refresh_failed'] ),
				'sources'        => $source_status,
			),
			false
		);
	}
}
