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
	private const CONTENT_SCHEMA_VERSION = '7';

	/**
	 * Hide retired generator notes immediately while the database migration runs.
	 */
	public static function boot(): void {
		add_filter( 'the_content', array( __CLASS__, 'filter_reader_content' ), 8 );
	}

	/**
	 * Keep internal collection and validation notes out of reader-facing posts.
	 *
	 * @param string $content Filtered post content.
	 */
	public static function filter_reader_content( string $content ): string {
		if ( is_admin() || ! is_singular( 'post' ) ) {
			return $content;
		}

		return self::clean_legacy_article( $content );
	}

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
	 * Return the reader-facing article schema version.
	 */
	public static function content_schema_version(): string {
		return self::CONTENT_SCHEMA_VERSION;
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
				'%1$s Updated for %2$s. Lead coverage includes %3$s.',
				(string) $config['intro'],
				$date,
				implode( ' and ', $top_titles )
			);
			$excerpt = wp_trim_words( $excerpt, 38, '.' );

			$item_ids = array_map(
				static fn( array $item ): string => (string) ( $item['id'] ?? '' ),
				$selected
			);
			$fingerprint = hash(
				'sha256',
				self::CONTENT_SCHEMA_VERSION . '|' . (string) ( $data['fingerprint'] ?? '' ) . '|' . $slug . '|' . implode( '|', $item_ids )
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
		usort(
			$relevant,
			static function ( array $left, array $right ) use ( $category ): int {
				$score = self::category_relevance_score( $right, $category ) <=> self::category_relevance_score( $left, $category );
				if ( 0 !== $score ) {
					return $score;
				}

				$priority = (int) ( $right['priority'] ?? 0 ) <=> (int) ( $left['priority'] ?? 0 );
				if ( 0 !== $priority ) {
					return $priority;
				}

				return strtotime( (string) ( $right['published'] ?? '' ) ) <=> strtotime( (string) ( $left['published'] ?? '' ) );
			}
		);

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
		} elseif ( 'cybersecurity' === $category ) {
			$news_quota = min( 4, $limit );
			$kev_quota  = min( 1, max( 0, $limit - $news_quota ) );
			$quotas     = array(
				'news' => $news_quota,
				'kev'  => $kev_quota,
				'risk' => max( 0, $limit - $news_quota - $kev_quota ),
			);
		} elseif ( 'tutorials' === $category ) {
			$risk_quota = min( 3, $limit );
			$news_quota = min( 2, max( 0, $limit - $risk_quota ) );
			$quotas     = array(
				'risk' => $risk_quota,
				'news' => $news_quota,
				'kev'  => max( 0, $limit - $risk_quota - $news_quota ),
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

		return array_slice( $selected, 0, $limit );
	}

	/**
	 * Rank records by the desk they genuinely belong to instead of feed order.
	 *
	 * @param array<string,mixed> $item Source item.
	 */
	private static function category_relevance_score( array $item, string $category ): int {
		$text = strtolower(
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
		$patterns = array(
			'linux-administration' => array(
				'/\b(linux kernel|ubuntu|debian|red hat|rhel|gnu|systemd|snap-confine|openssh|sudo)\b/i' => 110,
				'/\b(linux|kernel|package|distribution)\b/i' => 35,
			),
			'devops' => array(
				'/\b(github|gitlab|ci\/cd|pipeline|runner|jenkins|docker|container|kubernetes|helm|npm|pypi|maven)\b/i' => 100,
				'/\b(build|dependency|artifact|repository|supply chain|secret)\b/i' => 35,
			),
			'artificial-intelligence' => array(
				'/\b(langflow|openai|hugging face|llm|ai agent|agentic|prompt injection|model context protocol|mcp)\b/i' => 115,
				'/\b(artificial intelligence|model|agent|copilot|sandbox)\b/i' => 40,
			),
			'cloud-security' => array(
				'/\b(aws|amazon web services|azure|google cloud|gcp|iam|service account|tenant|managed service)\b/i' => 105,
				'/\b(cloud|kubernetes|cluster|container|workload identity)\b/i' => 35,
			),
			'windows-security' => array(
				'/\b(microsoft|windows|sharepoint|exchange|active directory|ad fs|entra|vmswitch|bitlocker)\b/i' => 110,
				'/\b(patch tuesday|office|endpoint|domain controller)\b/i' => 35,
			),
			'network-security' => array(
				'/\b(fortinet|fortios|fortigate|sonicwall|palo alto|cisco|check point|router|firewall|vpn|gateway)\b/i' => 115,
				'/\b(network|switch|dns|tcp|udp|firmware|edge device|management interface)\b/i' => 35,
			),
			'web-security' => array(
				'/\b(wordpress|woocommerce|elementor|apache|nginx|php|codeigniter|web application|rest api)\b/i' => 115,
				'/\b(api|browser|http|sql injection|xss|cross-site|csrf|ssrf|authentication bypass|authorization bypass)\b/i' => 40,
			),
		);

		$score = 0;
		foreach ( $patterns[ $category ] ?? array() as $pattern => $weight ) {
			if ( 1 === preg_match( (string) $pattern, $text ) ) {
				$score += (int) $weight;
			}
		}

		$type = (string) ( $item['type'] ?? '' );
		if ( 'critical-cves' === $category ) {
			$score += 'kev' === $type ? 150 : 0;
			$score += (int) round( (float) ( $item['score'] ?? 0 ) * 10 );
		} elseif ( 'cybersecurity' === $category ) {
			$score += in_array( $type, array( 'reported_news', 'official_context', 'feed' ), true ) ? 120 : 0;
			$score += in_array( (string) ( $item['source_key'] ?? '' ), array( 'cisa_advisory', 'github_security', 'microsoft_security', 'verified_context' ), true ) ? 45 : 0;
		} elseif ( 'tutorials' === $category ) {
			$score += in_array( $type, array( 'vulnerability', 'advisory' ), true ) ? 50 : 0;
			$score += false !== strpos( $text, 'guide' ) || false !== strpos( $text, 'checklist' ) ? 40 : 0;
		}

		if ( 'web-security' === $category && 1 === preg_match( '/\b(fortinet|fortios|fortigate|router|firewall|vpn)\b/i', $text ) ) {
			$score -= 90;
		}
		if ( 'network-security' === $category && 1 === preg_match( '/\b(sharepoint|wordpress|woocommerce|elementor)\b/i', $text ) ) {
			$score -= 65;
		}

		return $score;
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
		unset( $data );

		$label       = (string) $config['label'];
		$headings    = self::article_headings( $label );
		$notes       = is_array( $config['review_notes'] ?? null ) ? $config['review_notes'] : array();
		$sources     = self::source_pairs( $items );
		$lead        = $items[0] ?? array();
		$lead_title  = trim( (string) ( $lead['title'] ?? '' ) );
		$lead_product = trim( implode( ' ', array_filter( array( (string) ( $lead['vendor'] ?? '' ), (string) ( $lead['product'] ?? '' ) ) ) ) );

		$content  = '<p class="isnx-live-deck">' . esc_html( (string) $config['intro'] ) . '</p>';
		$content .= '<!--more-->';
		$content .= '<h2>' . esc_html( $headings['overview'] ) . '</h2>';
		$content .= '<p>' . esc_html( (string) $config['context'] ) . '</p>';
		if ( '' !== $lead_title ) {
			$content .= '<p>For ' . esc_html( $date ) . ', the lead development is <strong>' . esc_html( $lead_title ) . '</strong>. ';
			if ( '' !== $lead_product ) {
				$content .= 'Start by confirming where ' . esc_html( $lead_product ) . ' is deployed, who owns it, and whether the affected path is reachable. ';
			}
			$content .= 'The remaining items below add the product-specific context needed to turn the headline into an owned security decision.</p>';
		}

		$content .= '<h2>' . esc_html( $headings['developments'] ) . '</h2>';
		foreach ( $items as $index => $item ) {
			$note = (string) ( $notes[ $index % max( 1, count( $notes ) ) ] ?? $config['context'] );
			$content .= self::render_item( $item, $label, $note, (int) $index );
		}

		$content .= '<h2>' . esc_html( $headings['response'] ) . '</h2>';
		$content .= '<p>' . esc_html( self::response_intro( $label ) ) . '</p><ul>';
		foreach ( (array) $config['actions'] as $action ) {
			$content .= '<li>' . esc_html( (string) $action ) . '</li>';
		}
		$content .= '</ul>';

		$content .= '<h2>' . esc_html( $headings['signals'] ) . '</h2>';
		$content .= '<p>' . esc_html( self::signal_intro( $label ) ) . '</p><ul>';
		foreach ( $notes as $note ) {
			$content .= '<li>' . esc_html( (string) $note ) . '</li>';
		}
		$content .= '</ul>';

		$content .= '<h2>' . esc_html( $headings['closing'] ) . '</h2>';
		$content .= '<p>' . esc_html( self::closing_note( $label ) ) . '</p>';

		if ( ! empty( $sources ) ) {
			$content .= '<details class="isnx-references"><summary>References used in this briefing</summary><ul>';
			foreach ( $sources as $source ) {
				$content .= '<li><a href="' . esc_url( (string) $source[1] ) . '" rel="nofollow noopener" target="_blank">' . esc_html( (string) $source[0] ) . '</a></li>';
			}
			$content .= '</ul></details>';
		}

		return $content;
	}

	/**
	 * Render one source item without reproducing long source passages.
	 *
	 * @param array<string,mixed> $item Source item.
	 * @param string              $category_label Category label.
	 * @param string              $review_note Category-specific review note.
	 * @param int                 $position Item position in the briefing.
	 * @return string
	 */
	private static function render_item( array $item, string $category_label, string $review_note, int $position ): string {
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

		$description = wp_trim_words( (string) ( $item['description'] ?? '' ), 58, '&hellip;' );
		if ( '' !== $description ) {
			$content .= '<p>' . esc_html( $description ) . '</p>';
		}

		$insight = self::item_insight( $item, $category_label, $position );
		$content .= '<p><strong>Why it matters:</strong> ' . esc_html( $insight['why'] ) . '</p>';
		$content .= '<p><strong>What to verify:</strong> ' . esc_html( $insight['verify'] ) . '</p>';

		if ( 'kev' === (string) ( $item['type'] ?? '' ) ) {
			if ( ! empty( $item['due_date'] ) ) {
				$content .= '<p><strong>CISA remediation date: ' . esc_html( (string) $item['due_date'] ) . '.</strong> ';
				$content .= ! empty( $item['action'] ) ? esc_html( wp_trim_words( (string) $item['action'], 24, '&hellip;' ) ) : 'Follow the current catalog action.';
				$content .= '</p>';
			}
		}

		$content .= '<p class="isnx-live-item__analysis"><strong>Operational focus:</strong> ' . esc_html( $review_note ) . '</p>';
		$content .= '<p><a href="' . esc_url( (string) $item['url'] ) . '" rel="nofollow noopener" target="_blank">Open the original ' . esc_html( (string) ( $item['source'] ?? 'source' ) ) . ' record</a></p>';
		$content .= '</section>';

		return $content;
	}

	/**
	 * Build product- and weakness-aware editorial analysis for one source record.
	 *
	 * @param array<string,mixed> $item Source item.
	 * @param string              $category_label Category label.
	 * @param int                 $position Item position in the briefing.
	 * @return array{why:string,verify:string}
	 */
	private static function item_insight( array $item, string $category_label, int $position ): array {
		$subject = trim(
			implode(
				' ',
				array_filter(
					array(
						(string) ( $item['vendor'] ?? '' ),
						(string) ( $item['product'] ?? '' ),
					)
				)
			)
		);
		if ( '' === $subject ) {
			$subject = (string) ( $item['cve'] ?? '' );
		}
		if ( '' === $subject ) {
			$subject = wp_trim_words( (string) ( $item['title'] ?? 'This update' ), 10, '' );
		}

		$text = strtolower(
			implode(
				' ',
				array(
					(string) ( $item['title'] ?? '' ),
					(string) ( $item['description'] ?? '' ),
					(string) ( $item['vendor'] ?? '' ),
					(string) ( $item['product'] ?? '' ),
				)
			)
		);

		$profiles = array(
			array(
				'pattern' => '/\b(wordpress|woocommerce|elementor|plugin|theme)\b/i',
				'why'     => '%s may sit directly on a public website, so a vulnerable core, plugin, or theme can turn a routine content system into an initial-access path.',
				'verify'  => 'Record the exact WordPress core and extension versions, confirm whether the affected feature is enabled, review administrator accounts, and inspect web requests before and after the update.',
			),
			array(
				'pattern' => '/\b(fortinet|fortios|fortigate|sonicwall|palo alto|firewall appliance)\b/i',
				'why'     => '%s commonly protects an internet edge or management boundary. Exposure there can affect remote access, traffic inspection, credentials, and the trust placed in downstream systems.',
				'verify'  => 'Check the running firmware and model, restrict management access, compare configuration changes and new accounts, preserve independent logs, and rotate credentials if compromise cannot be excluded.',
			),
			array(
				'pattern' => '/\b(sharepoint|active directory|ad fs|exchange|windows|vmswitch|microsoft 365|entra)\b/i',
				'why'     => '%s is likely connected to identity, collaboration, or privileged Windows workloads, where one exposed role can widen impact beyond a single endpoint.',
				'verify'  => 'Map supported builds and server roles, prioritize public and identity systems, confirm the installed update plus restart state, and review authentication and EDR telemetry for abnormal activity.',
			),
			array(
				'pattern' => '/\b(linux|kernel|ubuntu|debian|red hat|rhel|gnu|snap-confine|systemd|openssh|sudo)\b/i',
				'why'     => '%s may be embedded across servers, containers, appliances, and administration hosts. Package installation alone does not prove that the corrected code is running.',
				'verify'  => 'Compare distribution package versions, identify the loaded kernel or library, plan required service restarts or reboots, and validate workload health after the change.',
			),
			array(
				'pattern' => '/\b(langflow|llm|prompt injection|model|agentic|ai agent|copilot|openai|hugging face|mcp server)\b/i',
				'why'     => '%s can combine untrusted text with connectors, stored credentials, and tool permissions. The meaningful risk is what the surrounding agent is allowed to read, change, or send.',
				'verify'  => 'Test with hostile input in an isolated environment, inspect connector scopes and retained context, require approval for sensitive actions, and confirm that tool calls are logged and attributable.',
			),
			array(
				'pattern' => '/\b(github|gitlab|pipeline|runner|docker|container|kubernetes|jenkins|package registry|dependency|supply chain)\b/i',
				'why'     => '%s participates in the path from source code to production. A weakness can inherit runner permissions, build secrets, trusted artifacts, or deployment access.',
				'verify'  => 'Trace untrusted input through pull requests and jobs, review token scope, isolate runners, pin trusted dependencies, and rebuild affected artifacts after remediation.',
			),
			array(
				'pattern' => '/\b(aws|azure|google cloud|gcp|cloud service|iam|service account|tenant)\b/i',
				'why'     => '%s can involve both provider-managed software and tenant-owned identity or exposure settings. Those responsibilities must be separated before the finding can be closed.',
				'verify'  => 'Check affected accounts and regions, public endpoints, identity paths, workload images, provider status, and centralized audit logs that prove the repaired control is active.',
			),
			array(
				'pattern' => '/\b(router|vpn|dns|network|switch|gateway|edge device|tcp|udp|wireless|firmware)\b/i',
				'why'     => '%s may control a traffic or administration path that other systems implicitly trust, making reachability and management-plane exposure more important than the headline score alone.',
				'verify'  => 'Inventory affected models and firmware, close public administration paths, compare routes and configuration, inspect flow and DNS logs, and validate connectivity after the upgrade.',
			),
			array(
				'pattern' => '/\b(sql injection|command injection|code injection|remote code execution|rce)\b/i',
				'why'     => '%s may let attacker-controlled input cross into an interpreter or executable path, which can turn a reachable application feature into data access or code execution.',
				'verify'  => 'Confirm the vulnerable route and authentication state, deploy the fixed release, review suspicious parameters and child processes, and test authorization boundaries after patching.',
			),
			array(
				'pattern' => '/\b(authentication bypass|improper authentication|authorization bypass|idor|access control)\b/i',
				'why'     => '%s concerns a trust decision rather than a cosmetic defect. If the affected path is reachable, an attacker may cross a role, tenant, or login boundary.',
				'verify'  => 'Reproduce the expected access checks safely, identify exposed roles and tenants, invalidate risky sessions or tokens, patch the decision point, and retest denied cases.',
			),
			array(
				'pattern' => '/\b(privilege escalation|elevation of privilege|escalate to root|local privilege)\b/i',
				'why'     => '%s can convert an existing low-privilege foothold into administrative control, increasing the importance of shared hosts, jump systems, and multi-user endpoints.',
				'verify'  => 'Identify who can reach the vulnerable component locally, patch privileged systems first, review recent elevation and process events, and test that the fixed boundary still blocks unprivileged users.',
			),
			array(
				'pattern' => '/\b(buffer overflow|out-of-bounds|use-after-free|memory corruption|integer overflow)\b/i',
				'why'     => '%s is a memory-safety issue whose practical impact depends on the reachable parser, process privileges, platform protections, and reliability of attacker-controlled input.',
				'verify'  => 'Confirm the exact affected build and component exposure, update from the vendor channel, review crash and restart telemetry, and keep network containment in place until the fixed process is running.',
			),
			array(
				'pattern' => '/\b(information disclosure|exposure of sensitive|data leak|credential exposure|secret exposure)\b/i',
				'why'     => '%s may reveal information that enables follow-on access even when it does not directly execute code. Tokens, configuration, keys, and internal topology deserve separate review.',
				'verify'  => 'Determine what data the affected path could return, inspect access logs, revoke exposed credentials, reduce response detail where possible, and confirm the patch closes the same request path.',
			),
			array(
				'pattern' => '/\b(path traversal|directory traversal|arbitrary file|file read|file write)\b/i',
				'why'     => '%s may let a crafted path escape its intended directory, exposing configuration, credentials, application data, or a writable execution location.',
				'verify'  => 'Identify the service account and filesystem boundary, review unusual path sequences in logs, patch the canonicalization check, rotate secrets from readable files, and retest encoded traversal variants.',
			),
			array(
				'pattern' => '/\b(cross-site scripting|xss|csrf|ssrf|browser|http request)\b/i',
				'why'     => '%s affects a browser or server-side request boundary, where authentication state, user roles, and reachable internal services determine the real impact.',
				'verify'  => 'Confirm the vulnerable parameter and required role, update the affected component, inspect relevant requests, and retest output encoding, origin checks, and outbound request restrictions.',
			),
		);

		foreach ( $profiles as $profile ) {
			if ( 1 === preg_match( (string) $profile['pattern'], $text ) ) {
				return array(
					'why'   => sprintf( (string) $profile['why'], $subject ),
					'verify' => (string) $profile['verify'],
				);
			}
		}

		$fallbacks = array(
			'Critical CVE'      => 'belongs in an exploit-led queue only after the affected product is matched to a reachable asset and accountable owner.',
			'Cybersecurity'     => 'changes a threat, product, or control assumption that should be translated into one explicit decision for the responsible team.',
			'Linux Security'    => 'requires version evidence from the running Linux workload, not only a package-manager success message.',
			'DevOps Security'   => 'should be traced across source, runner, dependency, artifact, secret, and deployment boundaries before release.',
			'AI Security'       => 'should be evaluated as part of the complete model, agent, data, connector, and tool-permission system.',
			'Security Tutorial' => 'is useful here as a worked example for turning an advisory into a documented owner, deadline, and verification record.',
			'Cloud Security'    => 'needs an account, region, identity path, exposure state, and provider-versus-tenant ownership decision.',
			'Windows Security'  => 'should be mapped to supported builds, deployed roles, restart requirements, and endpoint monitoring coverage.',
			'Network Security'  => 'must be evaluated at the edge, management plane, firmware level, and independent logging path.',
			'Web Security'      => 'should be tied to a reachable route, enabled component, authentication state, and permanent application fix.',
		);
		$why = $subject . ' ' . ( $fallbacks[ $category_label ] ?? 'needs an asset owner, exposure decision, remediation deadline, and proof that the control works.' );
		$variants = array(
			'Confirm the affected version and reachable component, preserve useful telemetry, apply the publisher guidance, and record the evidence used to close the item.',
			'Start with asset ownership and exposure, compare the fixed release with the deployed build, and validate both security behavior and service health afterward.',
			'Separate confirmed applicability from broad advisory language, assign the remediation decision, and keep any exception visible with an expiry date.',
		);

		return array(
			'why'   => $why,
			'verify' => $variants[ $position % count( $variants ) ],
		);
	}

	/**
	 * Reader-facing section headings for each desk.
	 *
	 * @return array{overview:string,developments:string,response:string,signals:string,closing:string}
	 */
	private static function article_headings( string $label ): array {
		$sets = array(
			'Critical CVE'      => array( 'Exploit-led priority', 'Vulnerabilities requiring action', 'Triage and remediation plan', 'Evidence to confirm', 'Patch queue decision' ),
			'Cybersecurity'     => array( 'Threat picture', 'Developments shaping the day', 'Defensive priorities', 'Escalation signals', 'Operational takeaway' ),
			'Linux Security'    => array( 'Linux exposure snapshot', 'Kernel, package, and service updates', 'Administrator runbook', 'Post-change checks', 'Linux team takeaway' ),
			'DevOps Security'   => array( 'Delivery-chain risk', 'Pipeline and dependency developments', 'DevSecOps response plan', 'Build and release signals', 'Release decision' ),
			'AI Security'       => array( 'Agent and model risk', 'AI security developments', 'Containment and governance actions', 'Tool-boundary signals', 'AI security takeaway' ),
			'Security Tutorial' => array( 'Goal for this exercise', 'Advisories used in the walkthrough', 'Step-by-step workflow', 'Evidence to capture', 'Finish the review' ),
			'Cloud Security'    => array( 'Cloud control-plane view', 'Service and workload developments', 'Cloud response plan', 'Tenant checks', 'Cloud team takeaway' ),
			'Windows Security'  => array( 'Microsoft estate view', 'Windows and identity developments', 'Deployment plan', 'Endpoint and server checks', 'Windows team takeaway' ),
			'Network Security'  => array( 'Edge exposure view', 'Network and appliance developments', 'Containment and firmware plan', 'Traffic and management signals', 'Network team takeaway' ),
			'Web Security'      => array( 'Application attack surface', 'Web and API developments', 'Application response plan', 'Requests and control signals', 'Web security takeaway' ),
		);
		$values = $sets[ $label ] ?? array( 'Current risk picture', 'Developments to review', 'Response plan', 'Signals to verify', 'Bottom line' );

		return array_combine( array( 'overview', 'developments', 'response', 'signals', 'closing' ), $values );
	}

	/**
	 * Category-specific transition into the action list.
	 */
	private static function response_intro( string $label ): string {
		$notes = array(
			'Critical CVE'      => 'Move from exploit evidence to asset matching, containment, patching, and proof of remediation. A scanner finding is the start of the workflow, not the completion record.',
			'Cybersecurity'     => 'Convert the developments above into a short queue of affected systems, accountable owners, deadlines, and detection work. Keep confirmed exposure separate from broad industry reporting.',
			'Linux Security'    => 'Work from the package or kernel version that is actually running. Plan service restarts or reboots, protect high-value workloads during the change, and verify the fixed code is loaded afterward.',
			'DevOps Security'   => 'Protect the path from source code to production. Review what untrusted input can reach, which identities a runner can use, and whether secrets survive in logs, caches, or artifacts.',
			'AI Security'       => 'Treat the model, agent runtime, connectors, stored credentials, and reachable tools as one system. Reduce permissions before testing and keep high-impact actions behind explicit approval.',
			'Security Tutorial' => 'Use one row per advisory and complete the workflow in order. The result should be a decision with an owner and evidence, not another unread list of links.',
			'Cloud Security'    => 'Separate provider-side remediation from tenant-owned configuration. Check identities, public endpoints, workload images, service accounts, regions, and audit coverage before closing the issue.',
			'Windows Security'  => 'Connect each advisory to supported builds and deployed server roles. Identity and internet-facing systems should move before routine endpoint waves, with restart and EDR health verified afterward.',
			'Network Security'  => 'Start at the internet edge and management plane. Preserve configurations, restrict administration paths, patch supported firmware, and rotate credentials where compromise cannot be ruled out.',
			'Web Security'      => 'Confirm that the affected route or component is actually enabled, then patch the permanent cause. Use temporary filtering only as a bridge and review requests for evidence of attempted abuse.',
		);

		return $notes[ $label ] ?? 'Translate each relevant development into an owner, deadline, remediation step, and verification record.';
	}

	/**
	 * Category-specific transition into verification signals.
	 */
	private static function signal_intro( string $label ): string {
		$notes = array(
			'Critical CVE'      => 'Use these checks to decide whether an advisory is urgent in your environment and whether remediation is complete.',
			'Cybersecurity'     => 'Escalate when exposure, privilege, sensitive data, identity control, or recovery impact increases the likely business consequence.',
			'Linux Security'    => 'Package installation is not enough; confirm the running kernel, loaded libraries, service state, and monitoring coverage.',
			'DevOps Security'   => 'Review trust boundaries at pull requests, runners, package resolution, artifact storage, credentials, and deployment approval.',
			'AI Security'       => 'Observe what the agent can read, write, execute, send, and approve when it processes untrusted context.',
			'Security Tutorial' => 'Capture enough evidence that another reviewer can understand the decision without repeating the entire investigation.',
			'Cloud Security'    => 'Verify the affected account and region, the identity path, public reachability, provider responsibility, and audit evidence.',
			'Windows Security'  => 'Check build numbers, installed updates, restart state, privileged authentication, EDR coverage, and server-role health.',
			'Network Security'  => 'Check firmware, exposed management paths, configuration changes, new accounts, tunnels, routes, and independent logs.',
			'Web Security'      => 'Check route reachability, authentication state, roles, request patterns, component versions, and recovery readiness.',
		);

		return $notes[ $label ] ?? 'Confirm exposure, remediation, and evidence before the item is closed.';
	}

	/**
	 * Finish each desk with a distinct editorial takeaway.
	 */
	private static function closing_note( string $label ): string {
		$notes = array(
			'Critical CVE'      => 'The best patch order is the one that starts with exploited, reachable, and privileged systems, then records why every remaining item was deferred or found not applicable.',
			'Cybersecurity'     => 'A useful daily brief changes decisions. Keep the queue small, tie it to real systems, and publish what changed, who owns the response, and what remains uncertain.',
			'Linux Security'    => 'Linux remediation is complete only when the fixed kernel, package, or service is running and the workload has passed its operational checks.',
			'DevOps Security'   => 'The secure release path minimizes inherited trust: isolated runners, short-lived identities, reviewed dependencies, reproducible artifacts, and explicit production approval.',
			'AI Security'       => 'AI risk becomes manageable when tool access is narrow, untrusted context is expected, sensitive actions require approval, and every invocation leaves an auditable trail.',
			'Security Tutorial' => 'End with a short action register: affected asset, decision, owner, deadline, proof required, and the next review time.',
			'Cloud Security'    => 'Close cloud findings only after both the provider status and tenant configuration are understood, with centralized logs showing the repaired control is working.',
			'Windows Security'  => 'A successful Windows update cycle protects identity and public server roles first, proves the new build is active, and keeps every exception visible.',
			'Network Security'  => 'Edge risk falls when management access is private, firmware is supported, credentials are rotated after doubt, and network changes are visible outside the device.',
			'Web Security'      => 'Permanent web risk reduction comes from fixing the vulnerable component or authorization path, then validating the result with request evidence and recovery checks.',
		);

		return $notes[ $label ] ?? 'Keep the response tied to real exposure, accountable ownership, and evidence that the control now works.';
	}

	/**
	 * Remove internal generator notes from older auto-published briefings.
	 */
	public static function clean_legacy_article( string $content ): string {
		$content = (string) preg_replace(
			'#<p\b[^>]*>\s*<strong\b[^>]*>\s*(?:Live verification|Internal verification|Generator note|Automation note):?\s*</strong>.*?</p>#is',
			'',
			$content
		);
		$content = (string) preg_replace(
			'#<p\b[^>]*>[^<]*(?:This briefing was assembled from public CISA|Existing posts are preserved and repeated source IDs are deduplicated).*?</p>#is',
			'',
			$content
		);
		$content = (string) preg_replace(
			'#<h2\b[^>]*>\s*Executive summary\s*</h2>\s*<p\b[^>]*>\s*The current source set produced.*?</p>#is',
			'<h2>Briefing overview</h2>',
			$content
		);
		$content = (string) preg_replace(
			'#<h2\b[^>]*>\s*(?:Prioritization method|Validation checklist|Accuracy and source notes|Frequently asked questions|Source verification|Editorial verification|Generator notes|Automation notes|How this briefing was assembled)\s*</h2>.*?(?=<h2\b|$)#is',
			'',
			$content
		);
		$content = (string) preg_replace(
			'#<p>(?:CISA lists this issue in the Known Exploited Vulnerabilities catalog|This record is a current vulnerability or package advisory|This is an official publisher update rather than a standalone proof of customer exposure).*?</p>#is',
			'',
			$content
		);
		$content = (string) preg_replace(
			'#<p>(?:CISA exploitation evidence moves|For .*?the useful decision points are|This publisher update matters to the).*?</p>#is',
			'',
			$content
		);
		$content = str_replace( '<strong>Source summary:</strong> ', '', $content );
		$content = (string) preg_replace( '#<strong>[^<]+ review:</strong>#i', '<strong>Operational focus:</strong>', $content );
		$content = (string) preg_replace(
			'#<h2\b[^>]*>\s*Sources\s*</h2>\s*(<ul\b[^>]*>.*?</ul>)#is',
			'<details class="isnx-references"><summary>References used in this briefing</summary>$1</details>',
			$content
		);
		$content = (string) preg_replace( '#<!--\s*/?wp:[^>]+-->\s*(?=<!--\s*/?wp:|$)#i', '', $content );

		return trim( $content );
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
