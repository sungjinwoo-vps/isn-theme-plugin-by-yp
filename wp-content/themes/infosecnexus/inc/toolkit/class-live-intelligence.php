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
	private const SOURCE_HEALTH_OPTION = 'infosecnexus_live_source_health';
	private const SOURCE_CACHE_PREFIX = 'infosecnexus_live_source_';
	private const SOURCE_LAST_GOOD_PREFIX = 'infosecnexus_live_source_last_good_';
	private const SOURCE_ALERT_PREFIX = 'infosecnexus_live_source_alert_';
	private const CACHE_TTL = 15 * MINUTE_IN_SECONDS;
	private const CRITICAL_SOURCE_TTL = 15 * MINUTE_IN_SECONDS;
	private const GENERAL_SOURCE_TTL = 30 * MINUTE_IN_SECONDS;
	private const LOOKBACK_DAYS = 7;
	private const MAX_ITEMS = 220;
	private const CONTENT_SCHEMA_VERSION = '9';

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

		$items     = array();
		$statuses  = array();
		$health    = get_option( self::SOURCE_HEALTH_OPTION, array() );
		$health    = is_array( $health ) ? $health : array();
		$all_fresh = true;

		foreach ( self::source_definitions() as $key => $source ) {
			$collected        = self::collect_source( (string) $key, $source, $force, (array) ( $health[ $key ] ?? array() ) );
			$items            = array_merge( $items, $collected['items'] );
			$statuses[ $key ] = $collected['status'];
			$health[ $key ]   = $collected['health'];
			$all_fresh        = $all_fresh && empty( $collected['status']['stale'] );
		}

		update_option( self::SOURCE_HEALTH_OPTION, $health, false );

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
			'stale'          => ! $all_fresh,
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
							'cve'         => (string) ( $item['cve'] ?? '' ),
							'due_date'    => (string) ( $item['due_date'] ?? '' ),
							'action'      => (string) ( $item['action'] ?? '' ),
							'categories'  => (array) ( $item['categories'] ?? array() ),
							'references'  => self::item_references( $item ),
							'active'      => ! empty( $item['active_exploitation'] ),
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
	 * Describe every primary or authoritative newsroom source and its cadence.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	private static function source_definitions(): array {
		$critical = self::CRITICAL_SOURCE_TTL;
		$general  = self::GENERAL_SOURCE_TTL;

		return array(
			'cisa_kev' => array(
				'label' => 'CISA Known Exploited Vulnerabilities', 'ttl' => $critical, 'critical' => true,
				'callback' => array( __CLASS__, 'collect_cisa_kev' ),
			),
			'nvd' => array(
				'label' => 'NIST National Vulnerability Database', 'ttl' => $critical, 'critical' => true,
				'callback' => array( __CLASS__, 'collect_nvd' ),
			),
			'cisa_advisory' => array(
				'label' => 'CISA Cybersecurity Advisories', 'ttl' => $critical, 'critical' => true,
				'callback' => static fn() => self::collect_feed( 'cisa_advisory', 'CISA Cybersecurity Advisories', 'https://www.cisa.gov/cybersecurity-advisories/all.xml?source=infosecnexus', 14 ),
			),
			'sonicwall_psirt' => array(
				'label' => 'SonicWall PSIRT', 'ttl' => $critical, 'critical' => true,
				'callback' => static fn() => self::collect_feed( 'sonicwall_psirt', 'SonicWall PSIRT', 'https://psirtapi.global.sonicwall.com/api/v1/feed/rss.xml', 16 ),
			),
			'paloalto_psirt' => array(
				'label' => 'Palo Alto Networks Security Advisories', 'ttl' => $critical, 'critical' => true,
				'callback' => static fn() => self::collect_feed( 'paloalto_psirt', 'Palo Alto Networks Security Advisories', 'https://security.paloaltonetworks.com/rss.xml', 16 ),
			),
			'cisco_psirt' => array(
				'label' => 'Cisco Security Advisories', 'ttl' => $critical, 'critical' => true,
				'callback' => static fn() => self::collect_feed( 'cisco_psirt', 'Cisco Security Advisories', 'https://sec.cloudapps.cisco.com/security/center/psirtrss20/CiscoSecurityAdvisory.xml', 16 ),
			),
			'wordpress_security' => array(
				'label' => 'WordPress Security Releases', 'ttl' => $critical, 'critical' => true,
				'callback' => static fn() => self::collect_feed( 'wordpress_security', 'WordPress Security Releases', 'https://wordpress.org/news/category/security/feed/', 12 ),
			),
			'github_advisory' => array(
				'label' => 'GitHub Advisory Database', 'ttl' => $general, 'critical' => false,
				'callback' => array( __CLASS__, 'collect_github_advisories' ),
			),
			'ubuntu' => array(
				'label' => 'Ubuntu Security Notices', 'ttl' => $general, 'critical' => false,
				'callback' => static fn() => self::collect_feed( 'ubuntu', 'Ubuntu Security Notices', 'https://ubuntu.com/security/notices/rss.xml', 16 ),
			),
			'github_security' => array(
				'label' => 'GitHub Security Blog', 'ttl' => $general, 'critical' => false,
				'callback' => static fn() => self::collect_feed( 'github_security', 'GitHub Security Blog', 'https://github.blog/security/feed/', 12 ),
			),
			'microsoft_security' => array(
				'label' => 'Microsoft Security Blog', 'ttl' => $general, 'critical' => false,
				'callback' => static fn() => self::collect_feed( 'microsoft_security', 'Microsoft Security Blog', 'https://www.microsoft.com/en-us/security/blog/feed/', 12 ),
			),
			'openai' => array(
				'label' => 'OpenAI News', 'ttl' => $general, 'critical' => false,
				'callback' => static fn() => self::collect_feed( 'openai', 'OpenAI News', 'https://openai.com/news/rss.xml', 10, '/\b(security|cyber|vulnerab|exploit|incident|safety|red[- ]?team|sandbox|containment|risk)\b/i' ),
			),
		);
	}

	/**
	 * Collect one source with an independent cache and last-good fallback.
	 *
	 * @param string              $key Source key.
	 * @param array<string,mixed> $source Source definition.
	 * @param bool                $force Skip source cache.
	 * @param array<string,mixed> $previous Previous health state.
	 * @return array{items:array<int,array<string,mixed>>,status:array<string,mixed>,health:array<string,mixed>}
	 */
	private static function collect_source( string $key, array $source, bool $force, array $previous ): array {
		$cache_key = self::SOURCE_CACHE_PREFIX . md5( $key );
		$cached    = get_transient( $cache_key );
		$label     = (string) ( $source['label'] ?? $key );
		$now       = gmdate( 'Y-m-d H:i:s' );

		if ( ! $force && is_array( $cached ) ) {
			$items = is_array( $cached['items'] ?? null ) ? $cached['items'] : array();
			return array(
				'items'  => $items,
				'status' => array(
					'label' => $label, 'ok' => true, 'count' => count( $items ), 'message' => '',
					'checked_at' => (string) ( $cached['checked_at'] ?? $now ), 'last_success' => (string) ( $previous['last_success'] ?? '' ),
					'duration_ms' => 0, 'cached' => true, 'stale' => false, 'failure_count' => (int) ( $previous['failure_count'] ?? 0 ),
				),
				'health' => $previous,
			);
		}

		$started = microtime( true );
		$result  = call_user_func( $source['callback'] );
		$elapsed = (int) round( ( microtime( true ) - $started ) * 1000 );
		if ( ! is_wp_error( $result ) ) {
			$items  = is_array( $result ) ? $result : array();
			$health = array(
				'label' => $label, 'last_checked' => $now, 'last_success' => $now, 'duration_ms' => $elapsed,
				'count' => count( $items ), 'failure_count' => 0, 'last_error' => '',
			);
			$payload = array( 'checked_at' => $now, 'items' => $items );
			set_transient( $cache_key, $payload, max( MINUTE_IN_SECONDS, (int) ( $source['ttl'] ?? self::GENERAL_SOURCE_TTL ) ) );
			update_option( self::SOURCE_LAST_GOOD_PREFIX . $key, $payload, false );

			return array(
				'items' => $items,
				'status' => array_merge( $health, array( 'ok' => true, 'message' => '', 'checked_at' => $now, 'cached' => false, 'stale' => false ) ),
				'health' => $health,
			);
		}

		$failures  = (int) ( $previous['failure_count'] ?? 0 ) + 1;
		$message   = self::clean_text( $result->get_error_message(), 28 );
		$last_good = get_option( self::SOURCE_LAST_GOOD_PREFIX . $key, array() );
		$fallback  = is_array( $last_good ) && is_array( $last_good['items'] ?? null ) ? $last_good['items'] : array();
		$health    = array_merge(
			$previous,
			array(
				'label' => $label, 'last_checked' => $now, 'duration_ms' => $elapsed, 'count' => count( $fallback ),
				'failure_count' => $failures, 'last_error' => $message,
			)
		);
		if ( ! empty( $source['critical'] ) && $failures >= 3 ) {
			self::maybe_alert_source_failure( $key, $label, $message, $failures );
		}

		return array(
			'items' => $fallback,
			'status' => array_merge( $health, array( 'ok' => false, 'message' => $message, 'checked_at' => $now, 'cached' => false, 'stale' => ! empty( $fallback ) ) ),
			'health' => $health,
		);
	}

	/**
	 * Send a rate-limited alert after repeated critical-source failures.
	 */
	private static function maybe_alert_source_failure( string $key, string $label, string $message, int $failures ): void {
		$alert_key = self::SOURCE_ALERT_PREFIX . md5( $key );
		if ( get_transient( $alert_key ) ) {
			return;
		}
		set_transient( $alert_key, '1', 12 * HOUR_IN_SECONDS );
		wp_mail(
			Mailer::recipient_email(),
			'InfoSecNexus source warning: ' . $label,
			sprintf( "%s has failed %d consecutive checks. The last-good copy remains in use when available.\n\n%s", $label, $failures, $message )
		);
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
	 * Build one canonical rolling brief plus conservative breaking stories.
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

		$selected   = self::select_rolling_items( $items );
		$item_ids   = array_values( array_filter( array_map( static fn( array $item ): string => (string) ( $item['id'] ?? '' ), $selected ) ) );
		$categories = array( 'cybersecurity' );
		foreach ( $selected as $item ) {
			$categories = array_merge( $categories, (array) ( $item['categories'] ?? array() ) );
		}
		$categories = array_values( array_unique( array_map( 'sanitize_key', $categories ) ) );

		$lead_titles = array_values( array_filter( array_map( static fn( array $item ): string => (string) ( $item['title'] ?? '' ), array_slice( $selected, 0, 2 ) ) ) );
		$excerpt = wp_trim_words(
			sprintf(
				'Current cybersecurity intelligence for %1$s, led by %2$s. Verified source links, affected products, exploitation signals, and practical response priorities are included.',
				$date,
				implode( ' and ', $lead_titles )
			),
			42,
			'.'
		);
		$fingerprint = hash( 'sha256', self::CONTENT_SCHEMA_VERSION . '|rolling|' . $date . '|' . self::items_fingerprint( $selected ) );
		$posts = array(
			array(
				'kind'        => 'rolling',
				'story_key'   => 'rolling-' . sanitize_title( $date ),
				'title'       => sprintf( 'Live Cybersecurity Brief for %s: Active Threats, CVEs, and Vendor Advisories', $date ),
				'slug'        => 'live-cybersecurity-brief',
				'dated_slug'  => true,
				'categories'  => $categories,
				'excerpt'     => $excerpt,
				'content'     => self::render_rolling_article( $date, $selected, $data ),
				'sources'     => self::source_pairs( $selected ),
				'fingerprint' => $fingerprint,
				'source_ids'  => $item_ids,
				'severity'    => self::brief_severity( $selected ),
				'score'       => self::brief_score( $selected ),
			),
		);

		$breaking_count = 0;
		foreach ( $items as $item ) {
			if ( $breaking_count >= 2 || ! self::is_breaking_item( $item ) ) {
				continue;
			}
			$story_key = self::story_key( $item );
			$title     = (string) ( $item['title'] ?? '' );
			$summary   = self::clean_text( (string) ( $item['description'] ?? '' ), 42 );
			if ( '' === $story_key || '' === $title || '' === $summary ) {
				continue;
			}

			$posts[] = array(
				'kind'        => 'breaking',
				'story_key'   => $story_key,
				'title'       => $title,
				'slug'        => sanitize_title( $story_key . '-' . wp_trim_words( $title, 7, '' ) ),
				'dated_slug'  => false,
				'categories'  => array_values( array_unique( (array) ( $item['categories'] ?? array( 'cybersecurity' ) ) ) ),
				'excerpt'     => $summary,
				'content'     => self::render_breaking_article( $date, $item ),
				'sources'     => self::source_pairs( array( $item ) ),
				'fingerprint' => hash( 'sha256', self::CONTENT_SCHEMA_VERSION . '|breaking|' . $story_key . '|' . self::items_fingerprint( array( $item ) ) ),
				'source_ids'  => array( (string) ( $item['id'] ?? $story_key ) ),
				'severity'    => (string) ( $item['severity'] ?? 'Known Exploited' ),
				'score'       => $item['score'] ?? null,
			);
			++$breaking_count;
		}

		return $posts;
	}

	/**
	 * Keep urgent records first while guaranteeing useful direct-vendor coverage.
	 *
	 * @param array<int,array<string,mixed>> $items Priority-sorted normalized items.
	 * @return array<int,array<string,mixed>>
	 */
	private static function select_rolling_items( array $items ): array {
		$selected = array();
		$add_item = static function ( array $item ) use ( &$selected ): void {
			$key = (string) ( $item['cve'] ?? '' );
			if ( '' === $key ) {
				$key = (string) ( $item['id'] ?? self::canonical_source_url( (string) ( $item['url'] ?? '' ) ) );
			}
			if ( '' !== $key ) {
				$selected[ strtolower( $key ) ] = $item;
			}
		};

		foreach ( array_slice( $items, 0, 10 ) as $item ) {
			$add_item( $item );
		}

		$pinned_sources = array(
			'sonicwall_psirt',
			'paloalto_psirt',
			'cisco_psirt',
			'wordpress_security',
			'ubuntu',
			'microsoft_security',
			'github_security',
			'openai',
		);
		foreach ( $pinned_sources as $source_key ) {
			foreach ( $items as $item ) {
				if ( $source_key === (string) ( $item['source_key'] ?? '' ) ) {
					$add_item( $item );
					break;
				}
			}
			if ( count( $selected ) >= 16 ) {
				break;
			}
		}

		foreach ( $items as $item ) {
			if ( count( $selected ) >= 16 ) {
				break;
			}
			$add_item( $item );
		}

		return array_slice( array_values( $selected ), 0, 16 );
	}

	/**
	 * Render the cross-desk rolling article without internal debug narration.
	 *
	 * @param string                         $date Human-readable date.
	 * @param array<int,array<string,mixed>> $items Current source items.
	 * @param array<string,mixed>            $data Collection metadata.
	 */
	private static function render_rolling_article( string $date, array $items, array $data ): string {
		$counts  = self::summary_counts( $items );
		$sources = self::source_pairs( $items );
		$checked = strtotime( (string) ( $data['checked_at'] ?? '' ) );
		$as_of   = false !== $checked ? wp_date( 'F j, Y g:i a T', $checked ) : $date;

		$content  = '<p class="isnx-live-deck">A continuously updated operational brief built from current government, vulnerability-database, open-source, and vendor security advisories.</p>';
		$content .= '<p>As of <strong>' . esc_html( $as_of ) . '</strong>, this edition tracks ' . esc_html( (string) count( $items ) ) . ' prioritized developments, including ' . esc_html( (string) $counts['kev'] ) . ' known-exploited entries, ' . esc_html( (string) $counts['critical'] ) . ' critical records, and ' . esc_html( (string) $counts['high'] ) . ' high-severity records. Treat the list as a starting point: final urgency depends on deployed versions, exposure, privilege, and available compensating controls.</p>';
		$content .= '<!--more-->';
		$content .= '<h2>Executive security snapshot</h2>';
		$content .= '<p>The highest-value work is to connect each advisory to a real asset and an accountable owner. Known exploitation and direct vendor warnings move ahead of ordinary backlog scoring, while newly disclosed records still require version and reachability checks before a response team declares exposure.</p>';

		$content .= '<h2>Top developments for ' . esc_html( $date ) . '</h2>';
		foreach ( array_slice( $items, 0, 10 ) as $index => $item ) {
			$categories = (array) ( $item['categories'] ?? array() );
			$label      = self::category_label_for_item( $categories );
			$insight    = self::item_insight( $item, $label, (int) $index );
			$content   .= self::render_newsroom_item( $item, $insight );
		}

		$content .= '<h2>Coverage by security desk</h2>';
		$desks = array(
			'critical-cves' => 'Exploited and critical vulnerabilities',
			'linux-administration' => 'Linux and open-source operations',
			'windows-security' => 'Windows and Microsoft security',
			'network-security' => 'Network, VPN, firewall, and edge security',
			'web-security' => 'Web applications, APIs, and WordPress',
			'devops' => 'DevOps and software supply chain',
			'artificial-intelligence' => 'AI and agent security',
			'cloud-security' => 'Cloud and identity controls',
		);
		foreach ( $desks as $slug => $heading ) {
			$desk_items = array_values( array_filter( $items, static fn( array $item ): bool => in_array( $slug, (array) ( $item['categories'] ?? array() ), true ) ) );
			if ( empty( $desk_items ) ) {
				continue;
			}
			$content .= '<h3>' . esc_html( $heading ) . '</h3><ul>';
			foreach ( array_slice( $desk_items, 0, 3 ) as $item ) {
				$content .= '<li><a href="' . esc_url( (string) $item['url'] ) . '" rel="nofollow noopener" target="_blank">' . esc_html( (string) $item['title'] ) . '</a> - ' . esc_html( self::clean_text( (string) ( $item['description'] ?? '' ), 28 ) ) . '</li>';
			}
			$content .= '</ul>';
		}

		$content .= '<h2>Priority actions for today</h2><ol>';
		$content .= '<li><strong>Confirm exposure:</strong> match CVEs and vendor advisories to exact products, versions, internet reachability, and business-critical roles.</li>';
		$content .= '<li><strong>Move exploited items first:</strong> patch, isolate, or disable affected paths for confirmed known-exploited technology before routine CVSS-only work.</li>';
		$content .= '<li><strong>Preserve evidence:</strong> review authentication, process, endpoint, network, and management-plane telemetry before rebooting or replacing an affected system.</li>';
		$content .= '<li><strong>Validate remediation:</strong> prove that the fixed version is running, required restarts are complete, controls still report healthy, and exceptions have owners and deadlines.</li>';
		$content .= '</ol>';

		$content .= self::render_references( $sources );
		return $content;
	}

	/**
	 * Render a source-specific breaking article for confirmed exploitation.
	 *
	 * @param string              $date Human-readable date.
	 * @param array<string,mixed> $item Breaking source item.
	 */
	private static function render_breaking_article( string $date, array $item ): string {
		$subject = trim( implode( ' ', array_filter( array( (string) ( $item['vendor'] ?? '' ), (string) ( $item['product'] ?? '' ) ) ) ) );
		$subject = '' !== $subject ? $subject : (string) ( $item['cve'] ?? 'the affected technology' );
		$label   = self::category_label_for_item( (array) ( $item['categories'] ?? array() ) );
		$insight = self::item_insight( $item, $label, 0 );
		$actions = self::operational_actions( $item );
		$checks  = self::validation_checks( $item );

		$content  = '<p class="isnx-live-deck">An official source reports active exploitation. Teams running ' . esc_html( $subject ) . ' should verify exposure and begin risk-reduction work now.</p>';
		$content .= '<p>' . esc_html( (string) ( $item['description'] ?? '' ) ) . '</p>';
		$content .= '<!--more-->';
		$content .= '<h2>What changed</h2>';
		$content .= '<p>On ' . esc_html( $date ) . ', this issue entered the urgent InfoSecNexus queue because exploitation is identified by an authoritative source. The source record, affected versions, and vendor remediation remain the controlling references; asset inventory and network context determine which systems should move first.</p>';
		$content .= '<h2>Why this matters</h2><p>' . esc_html( $insight['why'] ) . '</p>';
		$content .= '<p>Exploit-first prioritization does not mean patching blindly. Confirm the vulnerable component is installed, identify the reachable attack path, preserve evidence of suspicious activity, and protect critical workloads while the permanent fix is deployed.</p>';
		$content .= '<h2>Immediate response plan</h2><ol>';
		foreach ( $actions as $action ) {
			$content .= '<li>' . esc_html( $action ) . '</li>';
		}
		$content .= '</ol>';
		if ( ! empty( $item['due_date'] ) || ! empty( $item['action'] ) ) {
			$content .= '<h2>Official remediation direction</h2><p>';
			if ( ! empty( $item['due_date'] ) ) {
				$content .= '<strong>CISA due date: ' . esc_html( (string) $item['due_date'] ) . '.</strong> ';
			}
			$content .= esc_html( (string) ( $item['action'] ?? 'Follow the current vendor advisory and CISA guidance.' ) ) . '</p>';
		}
		$content .= '<h2>Detection and validation</h2><p>' . esc_html( $insight['verify'] ) . '</p><ul>';
		foreach ( $checks as $check ) {
			$content .= '<li>' . esc_html( $check ) . '</li>';
		}
		$content .= '</ul>';
		$content .= '<h2>Accuracy note</h2><p>Severity, affected-version ranges, and remediation details can change as the vendor and vulnerability databases add evidence. Recheck the linked primary records before closing the incident or approving a long-lived exception.</p>';
		$content .= self::render_references( self::source_pairs( array( $item ) ) );
		return $content;
	}

	/**
	 * Render one concise source development with corroborating metadata.
	 *
	 * @param array<string,mixed> $item Source item.
	 * @param array{why:string,verify:string} $insight Item-specific analysis.
	 */
	private static function render_newsroom_item( array $item, array $insight ): string {
		$published = strtotime( (string) ( $item['published'] ?? '' ) );
		$date      = false !== $published ? wp_date( 'F j, Y g:i a T', $published ) : 'Publication time not supplied';
		$severity  = (string) ( $item['severity'] ?? '' );
		$score     = isset( $item['score'] ) && is_numeric( $item['score'] ) ? ' | CVSS ' . number_format_i18n( (float) $item['score'], 1 ) : '';
		$content   = '<section class="isnx-live-item"><h3>' . esc_html( (string) $item['title'] ) . '</h3>';
		$content  .= '<p class="isnx-live-item__meta">' . esc_html( (string) ( $item['source'] ?? '' ) . ' | ' . $date . ( '' !== $severity ? ' | ' . $severity : '' ) . $score ) . '</p>';
		$content  .= '<p>' . esc_html( self::clean_text( (string) ( $item['description'] ?? '' ), 75 ) ) . '</p>';
		$content  .= '<p><strong>Why it matters:</strong> ' . esc_html( $insight['why'] ) . '</p>';
		$content  .= '<p><strong>What to verify:</strong> ' . esc_html( $insight['verify'] ) . '</p>';
		$content  .= '<p><a href="' . esc_url( (string) $item['url'] ) . '" rel="nofollow noopener" target="_blank">Open the original source record</a></p></section>';
		return $content;
	}

	/**
	 * Render de-duplicated source references.
	 *
	 * @param array<int,string[]> $sources Source title/URL pairs.
	 */
	private static function render_references( array $sources ): string {
		if ( empty( $sources ) ) {
			return '';
		}
		$content = '<h2>Primary sources and references</h2><ul class="isnx-references">';
		foreach ( $sources as $source ) {
			$content .= '<li><a href="' . esc_url( (string) ( $source[1] ?? '' ) ) . '" rel="nofollow noopener" target="_blank">' . esc_html( (string) ( $source[0] ?? 'Source record' ) ) . '</a></li>';
		}
		return $content . '</ul>';
	}

	/**
	 * Decide whether an item deserves its own stable breaking URL.
	 */
	private static function is_breaking_item( array $item ): bool {
		if ( empty( $item['active_exploitation'] ) ) {
			return false;
		}
		$published = strtotime( (string) ( $item['published'] ?? '' ) );
		if ( false === $published || $published < time() - ( 48 * HOUR_IN_SECONDS ) ) {
			return false;
		}
		$references = self::item_references( $item );
		$source_key = (string) ( $item['source_key'] ?? '' );
		return 'cisa_kev' === $source_key || count( $references ) >= 2 || in_array( $source_key, array( 'sonicwall_psirt', 'paloalto_psirt', 'cisco_psirt', 'wordpress_security' ), true );
	}

	/**
	 * Return a stable identifier for cross-refresh breaking-story deduplication.
	 */
	private static function story_key( array $item ): string {
		if ( ! empty( $item['cve'] ) ) {
			return strtolower( (string) $item['cve'] );
		}
		$url = self::canonical_source_url( (string) ( $item['url'] ?? '' ) );
		return '' !== $url ? 'advisory-' . substr( hash( 'sha256', $url ), 0, 16 ) : '';
	}

	/**
	 * Fingerprint selected source facts, references, and exploitation state.
	 */
	private static function items_fingerprint( array $items ): string {
		$facts = array_map(
			static function ( array $item ): array {
				return array(
					'id' => (string) ( $item['id'] ?? '' ), 'title' => (string) ( $item['title'] ?? '' ),
					'description' => (string) ( $item['description'] ?? '' ), 'published' => (string) ( $item['published'] ?? '' ),
					'severity' => (string) ( $item['severity'] ?? '' ), 'score' => $item['score'] ?? null,
					'action' => (string) ( $item['action'] ?? '' ), 'references' => self::item_references( $item ),
					'active' => ! empty( $item['active_exploitation'] ),
				);
			},
			$items
		);
		return hash( 'sha256', (string) wp_json_encode( $facts ) );
	}

	/**
	 * Build item-specific response actions from the affected technology.
	 *
	 * @return string[]
	 */
	private static function operational_actions( array $item ): array {
		$text = strtolower( implode( ' ', array( (string) ( $item['title'] ?? '' ), (string) ( $item['vendor'] ?? '' ), (string) ( $item['product'] ?? '' ) ) ) );
		$actions = array(
			'Identify exact product versions, owners, exposure paths, and business-critical dependencies.',
			'Apply the latest vendor remediation or isolate the vulnerable path when immediate patching is not possible.',
			'Review available telemetry for exploitation attempts before restarting, rebuilding, or rotating evidence away.',
			'Validate the fixed version and control health, then record any exception with an owner and expiry date.',
		);
		if ( 1 === preg_match( '/\b(sonicwall|cisco|palo alto|firewall|vpn|router|gateway)\b/', $text ) ) {
			$actions[0] = 'Inventory internet-facing and management-plane appliances, including standby nodes and unsupported firmware.';
			$actions[2] = 'Review administrator logins, configuration exports, new accounts, VPN activity, and outbound connections; rotate credentials if compromise cannot be excluded.';
		} elseif ( 1 === preg_match( '/\b(linux|ubuntu|kernel|gnu|sudo)\b/', $text ) ) {
			$actions[0] = 'Map the advisory to distribution package versions and confirm the kernel or library currently loaded by each workload.';
			$actions[3] = 'Verify reboot or service-restart state, loaded modules, application health, and monitoring after remediation.';
		} elseif ( 1 === preg_match( '/\b(wordpress|web|api|http|php|apache|nginx)\b/', $text ) ) {
			$actions[0] = 'Confirm the vulnerable plugin, component, route, role, and authentication state on every public application instance.';
			$actions[2] = 'Review web, WAF, authentication, process, and outbound-request logs for exploit indicators before cleanup.';
		} elseif ( 1 === preg_match( '/\b(windows|microsoft|sharepoint|exchange|entra)\b/', $text ) ) {
			$actions[0] = 'Map affected Windows builds and server roles, prioritizing public, identity, management, and privileged systems.';
			$actions[3] = 'Confirm installed build numbers, required restarts, EDR health, authentication behavior, and service availability.';
		}
		return $actions;
	}

	/**
	 * Build validation checks for one affected technology.
	 *
	 * @return string[]
	 */
	private static function validation_checks( array $item ): array {
		$subject = trim( implode( ' ', array_filter( array( (string) ( $item['vendor'] ?? '' ), (string) ( $item['product'] ?? '' ) ) ) ) );
		$subject = '' !== $subject ? $subject : 'the affected component';
		return array(
			'Confirm the installed and running version of ' . $subject . ' against the current vendor advisory.',
			'Check whether the vulnerable interface is reachable from untrusted networks or lower-privileged identities.',
			'Look for new accounts, privilege changes, crashes, child processes, or unusual outbound traffic associated with the component.',
			'Run a post-remediation service and security-control health check and retain evidence with the change record.',
		);
	}

	/**
	 * Choose the article-analysis desk best matching an item.
	 */
	private static function category_label_for_item( array $categories ): string {
		$labels = array(
			'critical-cves' => 'Critical CVE', 'linux-administration' => 'Linux Security', 'devops' => 'DevOps Security',
			'artificial-intelligence' => 'AI Security', 'cloud-security' => 'Cloud Security', 'windows-security' => 'Windows Security',
			'network-security' => 'Network Security', 'web-security' => 'Web Security', 'cybersecurity' => 'Cybersecurity',
		);
		foreach ( $labels as $slug => $label ) {
			if ( in_array( $slug, $categories, true ) ) {
				return $label;
			}
		}
		return 'Cybersecurity';
	}

	/**
	 * Return the highest briefing severity.
	 */
	private static function brief_severity( array $items ): string {
		$severity = '';
		foreach ( $items as $item ) {
			$severity = self::higher_severity( $severity, (string) ( $item['severity'] ?? '' ) );
		}
		return $severity;
	}

	/**
	 * Return the highest available CVSS score.
	 */
	private static function brief_score( array $items ): ?float {
		$scores = array_values( array_filter( array_map( static fn( array $item ) => isset( $item['score'] ) && is_numeric( $item['score'] ) ? (float) $item['score'] : null, $items ), static fn( $score ): bool => null !== $score ) );
		return empty( $scores ) ? null : max( $scores );
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
					'active_exploitation' => true,
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
		$timeout_filter = static function ( $feed ): void {
			if ( is_object( $feed ) && method_exists( $feed, 'set_timeout' ) ) {
				$feed->set_timeout( 20 );
			}
		};
		add_filter( 'wp_feed_cache_transient_lifetime', $cache_filter );
		add_action( 'wp_feed_options', $timeout_filter );
		$feed = fetch_feed( $url );
		remove_action( 'wp_feed_options', $timeout_filter );
		remove_filter( 'wp_feed_cache_transient_lifetime', $cache_filter );
		if ( is_wp_error( $feed ) && 'cisa_advisory' === $source_key ) {
			$feed = self::fetch_cisa_feed_fallback( $url );
		}

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

			$score = null;
			if ( preg_match( '/\bCVSS(?:\s*v?\d(?:\.\d)?)?(?:\s*(?:base\s*)?score)?\s*[:=-]?\s*(10(?:\.0)?|[0-9](?:\.\d))\b/i', $search_text, $score_match ) ) {
				$score = (float) $score_match[1];
			}
			$severity = self::severity_from_text( $search_text, $score );
			$active   = self::has_confirmed_exploitation_language( $search_text );
			$direct_vendor = in_array( $source_key, array( 'sonicwall_psirt', 'paloalto_psirt', 'cisco_psirt', 'wordpress_security', 'ubuntu' ), true );

			$items[] = self::item(
				array(
					'id'          => $source_key . '-' . md5( $link ),
					'source_key'  => $source_key,
					'source'      => $source,
					'type'        => $direct_vendor ? 'vendor_advisory' : 'official_news',
					'title'       => $title,
					'description' => $description,
					'url'         => $link,
					'published'   => $published > 0 ? gmdate( 'c', $published ) : '',
					'severity'    => $active ? 'Known Exploited' : $severity,
					'score'       => $score,
					'cve'         => $cve,
					'vendor'      => $source,
					'product'     => '',
					'due_date'    => '',
					'action'      => '',
					'priority'    => $active ? 118 : ( $direct_vendor ? 82 : 55 ),
					'active_exploitation' => $active,
				)
			);

			if ( count( $items ) >= $limit ) {
				break;
			}
		}

		return $items;
	}

	/**
	 * Parse CISA's official RSS response when its edge rejects WordPress' HTTP transport.
	 *
	 * @return \SimplePie\SimplePie|\WP_Error
	 */
	private static function fetch_cisa_feed_fallback( string $url ) {
		$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		if ( 'www.cisa.gov' !== $host ) {
			return new \WP_Error( 'invalid_cisa_feed', 'The CISA feed fallback only accepts www.cisa.gov.' );
		}

		$context = stream_context_create(
			array(
				'http' => array(
					'timeout'    => 20,
					'user_agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url( '/' ),
					'header'     => "Accept: application/rss+xml, application/xml;q=0.9, */*;q=0.8\r\n",
				),
				'ssl' => array(
					'verify_peer'      => true,
					'verify_peer_name' => true,
				),
			)
		);

		// The fixed, allowlisted CISA URL is parsed locally and never written to disk.
		$maximum_bytes = 5 * MB_IN_BYTES;
		$body          = @file_get_contents( $url, false, $context, 0, $maximum_bytes + 1 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( false === $body || '' === $body ) {
			return new \WP_Error( 'cisa_feed_unavailable', 'CISA returned no readable RSS content.' );
		}
		if ( strlen( $body ) > $maximum_bytes ) {
			return new \WP_Error( 'cisa_feed_too_large', 'CISA returned an unexpectedly large RSS response.' );
		}

		require_once ABSPATH . WPINC . '/class-simplepie.php';
		$feed = new \SimplePie\SimplePie();
		$feed->set_raw_data( $body );
		$feed->enable_cache( false );
		if ( ! $feed->init() ) {
			return new \WP_Error( 'cisa_feed_parse_error', (string) ( $feed->error() ?: 'CISA RSS parsing failed.' ) );
		}

		return $feed;
	}

	/**
	 * Detect explicit exploitation language while respecting common negations.
	 */
	private static function has_confirmed_exploitation_language( string $text ): bool {
		$negative = '/\b(?:not|isn\'t|is\s+not|are\s+not|was\s+not|were\s+not|has\s+not|have\s+not|no\s+(?:known\s+)?(?:evidence|indication|reports?)\s+of|not\s+aware\s+of)\b.{0,80}\b(?:active(?:ly)?\s+exploit(?:ed|ation)?|exploited\s+in\s+the\s+wild|known\s+exploited|weaponized)\b/i';
		if ( 1 === preg_match( $negative, $text ) ) {
			return false;
		}

		return 1 === preg_match( '/\b(?:active(?:ly)?\s+exploited|active\s+exploitation|exploitation\s+(?:has\s+been\s+)?observed|exploited\s+in\s+the\s+wild|known\s+exploited|under\s+active\s+attack|weaponized)\b/i', $text );
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

			$key = ! empty( $item['cve'] ) ? strtolower( (string) $item['cve'] ) : self::canonical_source_url( (string) $item['url'] );
			if ( '' === $key ) {
				$key = strtolower( (string) ( $item['id'] ?? $item['title'] ) );
			}

			$item['references'] = self::item_references( $item );
			if ( isset( $unique[ $key ] ) ) {
				$item = self::merge_items( $unique[ $key ], $item );
			}
			$item['categories'] = self::item_categories( $item );
			$unique[ $key ]     = $item;
		}

		$items = array_values( $unique );
		usort(
			$items,
			static function ( array $left, array $right ): int {
				$priority = self::effective_priority( $right ) <=> self::effective_priority( $left );
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
		$item['active_exploitation'] = ! empty( $item['active_exploitation'] ) || 'kev' === (string) ( $item['type'] ?? '' );
		$item['references']  = self::item_references( $item );
		return $item;
	}

	/**
	 * Merge matching CVE/advisory records instead of dropping corroboration.
	 *
	 * @param array<string,mixed> $left Existing item.
	 * @param array<string,mixed> $right Additional source item.
	 * @return array<string,mixed>
	 */
	private static function merge_items( array $left, array $right ): array {
		$primary   = self::item_quality( $right ) > self::item_quality( $left ) ? $right : $left;
		$secondary = $primary === $right ? $left : $right;
		$primary['references'] = array_values(
			array_reduce(
				array_merge( self::item_references( $left ), self::item_references( $right ) ),
				static function ( array $carry, array $reference ): array {
					$url = (string) ( $reference['url'] ?? '' );
					if ( '' !== $url ) {
						$carry[ $url ] = $reference;
					}
					return $carry;
				},
				array()
			)
		);

		if ( strlen( (string) ( $secondary['description'] ?? '' ) ) > strlen( (string) ( $primary['description'] ?? '' ) ) ) {
			$primary['description'] = $secondary['description'];
		}
		foreach ( array( 'cve', 'vendor', 'product', 'due_date', 'action' ) as $field ) {
			if ( empty( $primary[ $field ] ) && ! empty( $secondary[ $field ] ) ) {
				$primary[ $field ] = $secondary[ $field ];
			}
		}
		$cve = (string) ( $primary['cve'] ?? $secondary['cve'] ?? '' );
		if (
			'' !== $cve
			&& false === stripos( (string) ( $primary['title'] ?? '' ), $cve )
			&& false !== stripos( (string) ( $secondary['title'] ?? '' ), $cve )
		) {
			$primary['title'] = $secondary['title'];
		}

		$left_score  = isset( $left['score'] ) && is_numeric( $left['score'] ) ? (float) $left['score'] : null;
		$right_score = isset( $right['score'] ) && is_numeric( $right['score'] ) ? (float) $right['score'] : null;
		if ( null !== $left_score || null !== $right_score ) {
			$primary['score'] = max( (float) ( $left_score ?? 0 ), (float) ( $right_score ?? 0 ) );
		}
		$primary['severity'] = self::higher_severity( (string) ( $left['severity'] ?? '' ), (string) ( $right['severity'] ?? '' ) );
		$primary['active_exploitation'] = ! empty( $left['active_exploitation'] ) || ! empty( $right['active_exploitation'] ) || 'kev' === (string) ( $left['type'] ?? '' ) || 'kev' === (string) ( $right['type'] ?? '' );
		$primary['type'] = $primary['active_exploitation'] ? 'kev' : (string) ( $primary['type'] ?? 'advisory' );
		$primary['priority'] = max( (int) ( $left['priority'] ?? 0 ), (int) ( $right['priority'] ?? 0 ) ) + min( 8, count( $primary['references'] ) * 2 );

		$left_time  = strtotime( (string) ( $left['published'] ?? '' ) );
		$right_time = strtotime( (string) ( $right['published'] ?? '' ) );
		if ( false !== $right_time && ( false === $left_time || $right_time > $left_time ) ) {
			$primary['published'] = $right['published'];
		}

		return $primary;
	}

	/**
	 * Build the normalized reference list stored on each item.
	 *
	 * @param array<string,mixed> $item Source item.
	 * @return array<int,array{source:string,title:string,url:string}>
	 */
	private static function item_references( array $item ): array {
		$references = is_array( $item['references'] ?? null ) ? $item['references'] : array();
		$url        = esc_url_raw( (string) ( $item['url'] ?? '' ) );
		if ( '' !== $url ) {
			$references[] = array(
				'source' => sanitize_text_field( (string) ( $item['source'] ?? '' ) ),
				'title'  => self::clean_text( (string) ( $item['title'] ?? '' ), 30 ),
				'url'    => $url,
			);
		}
		return array_values( array_filter( $references, static fn( array $reference ): bool => ! empty( $reference['url'] ) ) );
	}

	/**
	 * Prefer primary-vendor and exploitation records when merging fields.
	 */
	private static function item_quality( array $item ): int {
		$quality = (int) ( $item['priority'] ?? 0 );
		$quality += ! empty( $item['active_exploitation'] ) ? 40 : 0;
		$quality += 'vendor_advisory' === (string) ( $item['type'] ?? '' ) ? 20 : 0;
		$quality += min( 20, (int) floor( strlen( (string) ( $item['description'] ?? '' ) ) / 80 ) );
		return $quality;
	}

	/**
	 * Add freshness and corroboration to the base source priority.
	 */
	private static function effective_priority( array $item ): int {
		$priority = (int) ( $item['priority'] ?? 0 );
		$published = strtotime( (string) ( $item['published'] ?? '' ) );
		if ( false !== $published ) {
			$age_hours = max( 0, ( time() - $published ) / HOUR_IN_SECONDS );
			$priority += max( 0, 36 - (int) floor( $age_hours / 3 ) );
		}
		$priority += min( 12, count( self::item_references( $item ) ) * 3 );
		return $priority;
	}

	/**
	 * Normalize a source URL for duplicate detection.
	 */
	private static function canonical_source_url( string $url ): string {
		$parts = wp_parse_url( $url );
		if ( ! is_array( $parts ) || empty( $parts['host'] ) ) {
			return strtolower( untrailingslashit( $url ) );
		}
		$path = (string) ( $parts['path'] ?? '/' );
		return strtolower( (string) $parts['host'] . untrailingslashit( $path ) );
	}

	/**
	 * Return the more urgent of two severity labels.
	 */
	private static function higher_severity( string $left, string $right ): string {
		$ranks = array( '' => 0, 'LOW' => 1, 'MEDIUM' => 2, 'MODERATE' => 2, 'HIGH' => 3, 'CRITICAL' => 4, 'KNOWN EXPLOITED' => 5 );
		$left_key  = strtoupper( trim( $left ) );
		$right_key = strtoupper( trim( $right ) );
		return ( $ranks[ $right_key ] ?? 0 ) > ( $ranks[ $left_key ] ?? 0 ) ? $right : $left;
	}

	/**
	 * Infer a normalized severity from explicit language or a CVSS score.
	 */
	private static function severity_from_text( string $text, ?float $score ): string {
		if ( 1 === preg_match( '/\bcritical\b/i', $text ) || ( null !== $score && $score >= 9.0 ) ) {
			return 'CRITICAL';
		}
		if ( 1 === preg_match( '/\bhigh(?: severity)?\b/i', $text ) || ( null !== $score && $score >= 7.0 ) ) {
			return 'HIGH';
		}
		if ( 1 === preg_match( '/\bmedium|moderate\b/i', $text ) || ( null !== $score && $score >= 4.0 ) ) {
			return 'MEDIUM';
		}
		return '';
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
			foreach ( self::item_references( $item ) as $reference ) {
				$url   = (string) ( $reference['url'] ?? '' );
				$title = (string) ( $reference['source'] ?? '' );
				$entry = (string) ( $reference['title'] ?? '' );
				if ( '' === $url || '' === $title ) {
					continue;
				}
				$sources[ $url ] = array( $title . ( '' !== $entry ? ': ' . $entry : '' ), $url );
			}
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
