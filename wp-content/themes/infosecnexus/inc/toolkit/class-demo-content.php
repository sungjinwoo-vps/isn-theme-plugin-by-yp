<?php
/**
 * Demo content and site setup.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

namespace InfoSecNexus\Theme\Toolkit;

/**
 * Demo content importer.
 */
final class Demo_Content {
	private const SEEDED_OPTION = 'infosecnexus_demo_seeded_version';
	private const DAILY_SEEDED_OPTION = 'infosecnexus_daily_content_seeded_dates';
	private const DAILY_CRON_HOOK = 'infosecnexus_publish_daily_content';
	private const PUBLIC_CACHE_RELEASE_OPTION = 'infosecnexus_public_cache_release';
	private const CONTENT_REFRESH_VERSION = '0.1.32';
	private const DAILY_SCHEMA_OPTION = 'infosecnexus_daily_content_schema';
	private const DAILY_SCHEMA_HOOK = 'infosecnexus_upgrade_daily_content_schema';

	/**
	 * Register hooks.
	 */
	public static function boot(): void {
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'handle_import' ) );
		add_action( 'admin_init', array( __CLASS__, 'maybe_auto_seed' ) );
		add_action( 'admin_init', array( __CLASS__, 'maybe_seed_daily_content' ) );
		add_action( 'admin_init', array( __CLASS__, 'maybe_upgrade_daily_content' ), 30 );
		add_action( 'init', array( __CLASS__, 'schedule_daily_content' ) );
		add_action( 'init', array( __CLASS__, 'schedule_daily_content_upgrade' ), 40 );
		add_action( 'init', array( __CLASS__, 'maybe_purge_release_cache' ), 99 );
		add_action( self::DAILY_CRON_HOOK, array( __CLASS__, 'publish_daily_content' ) );
		add_action( self::DAILY_SCHEMA_HOOK, array( __CLASS__, 'maybe_upgrade_daily_content' ) );
		add_action( 'infosecnexus_post_artwork_changed', array( __CLASS__, 'purge_artwork_cache' ) );
	}

	/**
	 * Add setup page.
	 */
	public static function admin_menu(): void {
		add_theme_page(
			__( 'InfoSecNexus Setup', 'infosecnexus' ),
			__( 'InfoSecNexus Setup', 'infosecnexus' ),
			'manage_options',
			'infosecnexus-setup',
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Render setup page.
	 */
	public static function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$import_url = wp_nonce_url( admin_url( 'themes.php?page=infosecnexus-setup&infosecnexus_import_demo=1' ), 'infosecnexus_import_demo' );
		$daily_url  = wp_nonce_url( admin_url( 'themes.php?page=infosecnexus-setup&infosecnexus_add_daily_content=1' ), 'infosecnexus_add_daily_content' );
		$reset_url  = wp_nonce_url( admin_url( 'themes.php?page=infosecnexus-setup&infosecnexus_reset_demo_settings=1' ), 'infosecnexus_reset_demo_settings' );
		$status     = Live_Intelligence::status();
		$checked_at = (string) ( $status['checked_at'] ?? '' );
		$source_rows = is_array( $status['sources'] ?? null ) ? $status['sources'] : array();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'InfoSecNexus Setup', 'infosecnexus' ); ?></h1>
			<?php if ( ! empty( $_GET['infosecnexus_imported'] ) ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Demo content repaired. Your existing theme settings were not reset.', 'infosecnexus' ); ?></p></div>
			<?php endif; ?>
			<?php if ( ! empty( $_GET['infosecnexus_daily_added'] ) ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Live sources were checked and today\'s category briefings were refreshed without deleting old posts.', 'infosecnexus' ); ?></p></div>
			<?php endif; ?>
			<?php if ( ! empty( $_GET['infosecnexus_settings_reset'] ) ) : ?>
				<div class="notice notice-warning is-dismissible"><p><?php esc_html_e( 'Recommended demo settings were reset.', 'infosecnexus' ); ?></p></div>
			<?php endif; ?>
			<p><?php esc_html_e( 'Create or repair the missing categories, demo posts, pages, and menus for the newsroom demo.', 'infosecnexus' ); ?></p>
			<p><a class="button button-primary" href="<?php echo esc_url( $import_url ); ?>"><?php esc_html_e( 'Import / Repair Demo Content', 'infosecnexus' ); ?></a></p>
			<p><?php esc_html_e( 'This content repair is non-destructive for your Customizer and feature settings.', 'infosecnexus' ); ?></p>
			<hr>
			<h2><?php esc_html_e( 'Live Cybersecurity Briefings', 'infosecnexus' ); ?></h2>
			<p><?php esc_html_e( 'Build long, source-backed posts from current CISA KEV, NIST NVD, GitHub, Ubuntu, Microsoft, OpenAI, and official security feeds. The automatic refresh runs near 6:30 AM and 6:30 PM in the WordPress site timezone.', 'infosecnexus' ); ?></p>
			<p><a class="button button-primary" href="<?php echo esc_url( $daily_url ); ?>"><?php esc_html_e( 'Refresh Live Briefings Now', 'infosecnexus' ); ?></a></p>
			<?php if ( '' !== $checked_at ) : ?>
				<p>
					<strong><?php esc_html_e( 'Last source check:', 'infosecnexus' ); ?></strong>
					<?php echo esc_html( get_date_from_gmt( $checked_at, 'F j, Y g:i a T' ) ); ?>
					<?php if ( ! empty( $status['stale'] ) ) : ?>
						<span class="notice-inline"><?php esc_html_e( '(showing the last good source set)', 'infosecnexus' ); ?></span>
					<?php endif; ?>
				</p>
				<p>
					<strong><?php esc_html_e( 'Verified items:', 'infosecnexus' ); ?></strong>
					<?php echo esc_html( (string) (int) ( $status['item_count'] ?? 0 ) ); ?>
				</p>
			<?php endif; ?>
			<?php if ( ! empty( $source_rows ) ) : ?>
				<table class="widefat striped" style="max-width: 820px">
					<thead><tr><th><?php esc_html_e( 'Source', 'infosecnexus' ); ?></th><th><?php esc_html_e( 'Status', 'infosecnexus' ); ?></th><th><?php esc_html_e( 'Items', 'infosecnexus' ); ?></th></tr></thead>
					<tbody>
					<?php foreach ( $source_rows as $source ) : ?>
						<tr>
							<td><?php echo esc_html( (string) ( $source['label'] ?? '' ) ); ?></td>
							<td><?php echo ! empty( $source['ok'] ) ? esc_html__( 'Connected', 'infosecnexus' ) : esc_html__( 'Unavailable', 'infosecnexus' ); ?></td>
							<td><?php echo esc_html( (string) (int) ( $source['count'] ?? 0 ) ); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
			<hr>
			<h2><?php esc_html_e( 'Reset Settings', 'infosecnexus' ); ?></h2>
			<p><?php esc_html_e( 'Use this only when you intentionally want to restore the recommended InfoSecNexus theme and feature settings.', 'infosecnexus' ); ?></p>
			<p><a class="button" href="<?php echo esc_url( $reset_url ); ?>"><?php esc_html_e( 'Reset Recommended Settings', 'infosecnexus' ); ?></a></p>
		</div>
		<?php
	}

	/**
	 * Handle manual import request.
	 */
	public static function handle_import(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! empty( $_GET['infosecnexus_import_demo'] ) ) {
			check_admin_referer( 'infosecnexus_import_demo' );
			self::run();
			wp_safe_redirect( admin_url( 'themes.php?page=infosecnexus-setup&infosecnexus_imported=1' ) );
			exit;
		}

		if ( ! empty( $_GET['infosecnexus_add_daily_content'] ) ) {
			check_admin_referer( 'infosecnexus_add_daily_content' );
			self::publish_daily_content( true );
			wp_safe_redirect( admin_url( 'themes.php?page=infosecnexus-setup&infosecnexus_daily_added=1' ) );
			exit;
		}

		if ( ! empty( $_GET['infosecnexus_reset_demo_settings'] ) ) {
			check_admin_referer( 'infosecnexus_reset_demo_settings' );
			self::set_options();
			wp_safe_redirect( admin_url( 'themes.php?page=infosecnexus-setup&infosecnexus_settings_reset=1' ) );
			exit;
		}
	}

	/**
	 * Seed once on mostly-empty sites so uploaded demos do not look broken.
	 */
	public static function maybe_auto_seed(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$theme = wp_get_theme();
		if ( 'infosecnexus' !== $theme->get_stylesheet() ) {
			return;
		}

		$seeded_version = (string) get_option( self::SEEDED_OPTION, '' );
		if ( '' !== $seeded_version ) {
			if ( version_compare( $seeded_version, self::CONTENT_REFRESH_VERSION, '<' ) && self::has_demo_content() ) {
				self::run();
			}
			return;
		}

		$posts = (int) wp_count_posts( 'post' )->publish;
		$pages = (int) wp_count_posts( 'page' )->publish;
		if ( $posts > 3 || $pages > 3 ) {
			return;
		}

		self::run( true );
	}

	/**
	 * Add today's daily batch when an admin visits and the cron has not run yet.
	 */
	public static function maybe_seed_daily_content(): void {
		if ( ! current_user_can( 'manage_options' ) || ! (bool) option( 'daily_content_enabled', true ) ) {
			return;
		}

		$theme = wp_get_theme();
		if ( 'infosecnexus' !== $theme->get_stylesheet() ) {
			return;
		}

		self::publish_daily_content();
	}

	/**
	 * Schedule a one-time cleanup whenever the reader-facing article schema changes.
	 */
	public static function schedule_daily_content_upgrade(): void {
		$version = Live_Intelligence::content_schema_version();
		if ( $version === (string) get_option( self::DAILY_SCHEMA_OPTION, '' ) ) {
			return;
		}

		if ( ! wp_next_scheduled( self::DAILY_SCHEMA_HOOK ) ) {
			wp_schedule_single_event( time() + 5, self::DAILY_SCHEMA_HOOK );
		}
	}

	/**
	 * Remove old generator notes and refresh today's generated briefings.
	 */
	public static function maybe_upgrade_daily_content(): void {
		$version = Live_Intelligence::content_schema_version();
		if ( $version === (string) get_option( self::DAILY_SCHEMA_OPTION, '' ) ) {
			return;
		}

		if ( ! wp_doing_cron() && ! current_user_can( 'manage_options' ) ) {
			return;
		}

		self::create_posts( self::create_categories() );

		$query = new \WP_Query(
			array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'meta_query'     => array(
					array(
						'key'     => '_infosecnexus_daily_content',
						'compare' => 'EXISTS',
					),
				),
			)
		);
		$changed_ids = array();
		foreach ( array_map( 'intval', $query->posts ) as $post_id ) {
			$existing = (string) get_post_field( 'post_content', $post_id );
			$cleaned  = Live_Intelligence::clean_legacy_article( $existing );
			$cleaned  = self::modernize_short_daily_content( $cleaned );
			$cleaned  = self::individualize_legacy_daily_content( $post_id, $cleaned );
			if ( $cleaned === $existing ) {
				continue;
			}

			$result = wp_update_post(
				wp_slash(
					array(
						'ID'           => $post_id,
						'post_content' => $cleaned,
					)
				),
				true
			);
			if ( ! is_wp_error( $result ) ) {
				$changed_ids[] = $post_id;
			}
		}

		if ( (bool) option( 'daily_content_enabled', true ) ) {
			self::publish_daily_content();
		}

		update_option( self::DAILY_SCHEMA_OPTION, $version, false );
		if ( ! empty( $changed_ids ) ) {
			self::purge_public_cache( $changed_ids );
		}
	}

	/**
	 * Schedule or unschedule the daily content event.
	 */
	public static function schedule_daily_content(): void {
		if ( ! (bool) option( 'daily_content_enabled', true ) ) {
			wp_clear_scheduled_hook( self::DAILY_CRON_HOOK );
			return;
		}

		$event = wp_get_scheduled_event( self::DAILY_CRON_HOOK );
		if ( $event && 'twicedaily' !== (string) $event->schedule ) {
			wp_clear_scheduled_hook( self::DAILY_CRON_HOOK );
			$event = false;
		}

		if ( ! $event ) {
			$now     = current_datetime();
			$morning = $now->setTime( 6, 30, 0 );
			$evening = $now->setTime( 18, 30, 0 );

			if ( $now < $morning ) {
				$next = $morning;
			} elseif ( $now < $evening ) {
				$next = $evening;
			} else {
				$next = $morning->modify( '+1 day' );
			}

			wp_schedule_event( $next->getTimestamp(), 'twicedaily', self::DAILY_CRON_HOOK );
		}
	}

	/**
	 * Publish today's daily blog batch.
	 *
	 * @param bool $force Force a fresh source request and rewrite changed posts.
	 */
	public static function publish_daily_content( bool $force = false ): void {
		if ( ! $force && ! (bool) option( 'daily_content_enabled', true ) ) {
			return;
		}

		$live_data = Live_Intelligence::collect( $force );
		if ( empty( $live_data['items'] ) ) {
			return;
		}

		$categories = self::create_categories();
		self::create_daily_posts( $categories, $force, $live_data );
	}

	/**
	 * Purge stale public HTML once after each installed theme release changes.
	 */
	public static function maybe_purge_release_cache(): void {
		$version = defined( 'INFOSECNEXUS_VERSION' ) ? (string) INFOSECNEXUS_VERSION : '';
		if ( '' === $version || $version === (string) get_option( self::PUBLIC_CACHE_RELEASE_OPTION, '' ) ) {
			return;
		}

		update_option( self::PUBLIC_CACHE_RELEASE_OPTION, $version, false );
		self::purge_public_cache( array() );
	}

	/**
	 * Purge anonymous page caches after generated artwork changes.
	 *
	 * @param array<int,int> $post_ids Posts that received featured artwork.
	 */
	public static function purge_artwork_cache( array $post_ids ): void {
		self::purge_public_cache( $post_ids );
	}

	/**
	 * Run importer.
	 *
	 * @param bool $reset_settings Whether to reset recommended theme/toolkit settings.
	 */
	public static function run( bool $reset_settings = false ): void {
		self::cleanup_starter_content();
		$categories = self::create_categories();
		self::create_posts( $categories );
		self::create_daily_posts( $categories, true );
		self::create_pages();
		self::create_menus( $categories );
		self::refresh_content_theme_mods();
		if ( $reset_settings ) {
			self::set_options();
		}
		update_option( self::SEEDED_OPTION, INFOSECNEXUS_THEME_TOOLKIT_VERSION, false );
		flush_rewrite_rules();
	}

	/**
	 * Remove starter/duplicate demo content that makes the site look unfinished.
	 */
	private static function cleanup_starter_content(): void {
		$starter_posts = array(
			array( 'post', 'hello-world' ),
			array( 'page', 'sample-page' ),
		);

		foreach ( $starter_posts as $item ) {
			$post = get_page_by_path( $item[1], OBJECT, $item[0] );
			if ( $post && ( false !== stripos( (string) $post->post_content, 'Welcome to WordPress' ) || 'Sample Page' === $post->post_title ) ) {
				wp_trash_post( (int) $post->ID );
			}
		}

		$legacy_about = get_page_by_path( 'about-infosecnexus', OBJECT, 'page' );
		if ( $legacy_about && get_post_meta( $legacy_about->ID, '_infosecnexus_demo_content', true ) ) {
			wp_trash_post( (int) $legacy_about->ID );
		}

		// Keep existing demo and daily blog posts. Imports now repair missing content only.
	}

	/**
	 * Whether the site already contains theme-seeded content.
	 */
	private static function has_demo_content(): bool {
		$query = new \WP_Query(
			array(
				'post_type'      => array( 'post', 'page' ),
				'post_status'    => array( 'publish', 'draft', 'private', 'pending', 'future' ),
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => '_infosecnexus_demo_content',
				'meta_value'     => '1',
				'no_found_rows'  => true,
			)
		);

		return $query->have_posts();
	}

	/**
	 * Create categories.
	 *
	 * @return array<string,int>
	 */
	private static function create_categories(): array {
		$items = array(
			'cybersecurity'            => array( 'Cyber Security', 'Threat intelligence, security operations, and defensive engineering.' ),
			'critical-cves'            => array( 'Critical CVEs', 'High-priority vulnerabilities, exploitation notes, and mitigation guidance.' ),
			'linux-administration'     => array( 'Linux & Kernel', 'Linux administration, kernel security, hardening, and operations.' ),
			'devops'                   => array( 'DevOps', 'Containers, automation, CI/CD, and cloud-native operations.' ),
			'artificial-intelligence'  => array( 'AI Security', 'AI risk, model security, data privacy, and responsible use.' ),
			'tutorials'                => array( 'Tutorials', 'Practical guides, checklists, and implementation playbooks.' ),
			'cloud-security'           => array( 'Cloud Security', 'Cloud configuration, identity, network, and platform security.' ),
			'windows-security'         => array( 'Windows Security', 'Windows platform advisories and endpoint hardening.' ),
			'network-security'         => array( 'Network Security', 'Network exposure, segmentation, and detection engineering.' ),
			'web-security'             => array( 'Web Security', 'Web application and API security coverage.' ),
		);

		$ids = array();
		foreach ( $items as $slug => $item ) {
			$term = term_exists( $slug, 'category' );
			if ( ! $term ) {
				$term = wp_insert_term(
					$item[0],
					'category',
					array(
						'slug'        => $slug,
						'description' => $item[1],
					)
				);
			}
			if ( ! is_wp_error( $term ) ) {
				$ids[ $slug ] = (int) ( is_array( $term ) ? $term['term_id'] : $term );
			}
		}

		return $ids;
	}

	/**
	 * Create demo posts.
	 *
	 * @param array<string,int> $categories Category IDs by slug.
	 */
	private static function create_posts( array $categories ): void {
		$posts = array(
			array(
				'title'      => 'CVE Triage Checklist for High-Risk Vulnerabilities',
				'slug'       => 'cve-triage-checklist-high-risk-vulnerabilities',
				'categories' => array( 'critical-cves' ),
				'excerpt'    => 'A short CVE triage workflow for ranking exploited vulnerabilities by exposure, blast radius, and patch urgency.',
				'content'    => self::brief_content(
					'CVE triage works best when severity is combined with business context. Scores matter, but internet exposure, privilege level, public exploit code, and affected asset value decide the real response order.',
					array(
						'Confirm affected versions and whether exploitation is active.',
						'Rank internet-facing, privileged, and customer-impacting systems first.',
						'Track owners, deadlines, compensating controls, and verification evidence.',
					),
					'Use this checklist during daily vulnerability review so critical CVEs become assigned work instead of unread alerts.'
				),
			),
			array(
				'title'      => 'Zero-Day Response: First 24 Hours for Security Teams',
				'slug'       => 'zero-day-response-first-24-hours-security-teams',
				'categories' => array( 'critical-cves' ),
				'excerpt'    => 'A practical first-day response plan for zero-day exploitation, exposure checks, and temporary mitigations.',
				'content'    => self::brief_content(
					'The first day of a zero-day response should reduce uncertainty fast. Teams need to identify exposed assets, apply available mitigations, and create a repeatable update rhythm.',
					array(
						'Create one owner for the advisory and one source of truth for status.',
						'Search external attack surface, endpoint telemetry, and asset tags.',
						'Apply vendor mitigations, block risky paths, and schedule patches when available.',
					),
					'Keep the response short and visible: what is exposed, what is protected, what remains open, and when the next decision happens.'
				),
			),
			array(
				'title'      => 'Exploitability Signals to Watch Before Patch Tuesday',
				'slug'       => 'exploitability-signals-before-patch-tuesday',
				'categories' => array( 'critical-cves' ),
				'excerpt'    => 'How defenders can spot vulnerability signals that deserve attention before the normal patch cycle.',
				'content'    => self::brief_content(
					'Exploitability clues often appear before broad exploitation. Public proof-of-concept code, unauthenticated attack paths, edge-device exposure, and suspicious scanning can all raise priority.',
					array(
						'Watch vendor advisories, KEV-style lists, exploit repositories, and honeypot telemetry.',
						'Separate reachable services from products that exist only in inventory.',
						'Prepare rollback and maintenance notes before the patch window opens.',
					),
					'This approach helps teams move early without treating every CVE as an emergency.'
				),
			),
			array(
				'title'      => 'Security Operations Metrics That Actually Reduce Risk',
				'slug'       => 'security-operations-metrics-that-reduce-risk',
				'categories' => array( 'cybersecurity' ),
				'excerpt'    => 'Useful security operations metrics for vulnerability response, incident handling, and executive reporting.',
				'content'    => self::brief_content(
					'Security metrics should show whether risk is shrinking, not only whether dashboards are busy. Focus on measurable outcomes that change decisions.',
					array(
						'Track age of critical findings by owner and exposure level.',
						'Measure time from alert validation to containment or patch verification.',
						'Review repeated root causes so controls improve instead of tickets multiplying.',
					),
					'A small set of clear metrics beats a large report that no team uses during real security work.'
				),
			),
			array(
				'title'      => 'Phishing Defense Controls for Hybrid Teams',
				'slug'       => 'phishing-defense-controls-hybrid-teams',
				'categories' => array( 'cybersecurity' ),
				'excerpt'    => 'A concise phishing defense baseline covering identity, mail security, browser controls, and response.',
				'content'    => self::brief_content(
					'Hybrid teams need phishing defenses that work beyond the office network. Identity controls, safe reporting, and fast takedown response matter more than awareness alone.',
					array(
						'Require phishing-resistant MFA for privileged and high-risk accounts.',
						'Tune mail authentication, attachment controls, and suspicious link rewriting.',
						'Create a one-click reporting path and measure time to mailbox cleanup.',
					),
					'Treat phishing defense as an operational workflow that connects users, identity, email, and incident response.'
				),
			),
			array(
				'title'      => 'Threat Intelligence Triage Without Alert Fatigue',
				'slug'       => 'threat-intelligence-triage-without-alert-fatigue',
				'categories' => array( 'cybersecurity' ),
				'excerpt'    => 'A lean method for turning threat intelligence into actions security teams can actually complete.',
				'content'    => self::brief_content(
					'Threat intelligence becomes useful when it maps to your environment. Indicators, tactics, and advisories should drive scoped detection, hardening, or response tasks.',
					array(
						'Filter intelligence by industry, exposed technologies, geography, and current campaigns.',
						'Convert relevant items into detection logic, patch tickets, or control reviews.',
						'Retire stale indicators and document why low-value alerts were ignored.',
					),
					'The goal is fewer generic alerts and more decisions that protect real systems.'
				),
			),
			array(
				'title'      => 'Linux Kernel Patch Runbook for Production Servers',
				'slug'       => 'linux-kernel-patch-runbook-production-servers',
				'categories' => array( 'linux-administration' ),
				'excerpt'    => 'A short Linux kernel patch runbook for maintenance windows, reboot planning, and validation.',
				'content'    => self::brief_content(
					'Linux kernel patching is complete only after teams verify the running kernel, module health, reboot state, and service behavior. Package installation alone is not enough.',
					array(
						'Group servers by service tier, reboot tolerance, and maintenance window.',
						'Confirm backup, rollback, kernel module, storage, and monitoring readiness.',
						'Validate kernel version, uptime, logs, and application health after deployment.',
					),
					'Keep exceptions visible with an owner and deadline so temporary exposure does not become permanent risk.'
				),
			),
			array(
				'title'      => 'SSH Hardening Baseline for Admin Teams',
				'slug'       => 'ssh-hardening-baseline-admin-teams',
				'categories' => array( 'linux-administration' ),
				'excerpt'    => 'Essential SSH hardening controls for Linux servers, administrators, and automation accounts.',
				'content'    => self::brief_content(
					'SSH remains a critical administrative path for Linux systems. A good baseline reduces password attacks, credential reuse, and uncontrolled privileged access.',
					array(
						'Disable password login where key-based or federated access is available.',
						'Restrict root login, enforce least privilege, and log administrative sessions.',
						'Review authorized keys, stale accounts, bastion access, and exposed ports.',
					),
					'Document the approved access path so emergency changes do not quietly bypass the baseline.'
				),
			),
			array(
				'title'      => 'Linux Log Review Checklist After Suspicious Activity',
				'slug'       => 'linux-log-review-checklist-suspicious-activity',
				'categories' => array( 'linux-administration' ),
				'excerpt'    => 'A compact Linux log review checklist for suspicious login, privilege, process, and network events.',
				'content'    => self::brief_content(
					'Linux incident review should quickly answer who logged in, what changed, which processes ran, and whether data moved. Start with high-signal logs before deep forensics.',
					array(
						'Review authentication, sudo, systemd, cron, package manager, and web server logs.',
						'Compare new users, SSH keys, scheduled jobs, services, and listening ports.',
						'Preserve logs before rebooting or rotating evidence.',
					),
					'A consistent checklist helps administrators collect useful evidence without delaying containment.'
				),
			),
			array(
				'title'      => 'CI/CD Secrets Hygiene Checklist',
				'slug'       => 'ci-cd-secrets-hygiene-checklist',
				'categories' => array( 'devops' ),
				'excerpt'    => 'Short CI/CD secrets hygiene guidance for pipelines, runners, variables, and deployment keys.',
				'content'    => self::brief_content(
					'CI/CD systems often hold powerful credentials. Secrets hygiene reduces the blast radius when a repository, runner, or build job is compromised.',
					array(
						'Store secrets in managed vaults and scope them to specific environments.',
						'Rotate long-lived tokens and remove credentials from logs, artifacts, and scripts.',
						'Use protected branches, reviewed workflows, and isolated runners for production deploys.',
					),
					'Review secrets whenever pipeline permissions change, not only after a leak is discovered.'
				),
			),
			array(
				'title'      => 'Container Image Scanning Before Production Deploys',
				'slug'       => 'container-image-scanning-before-production-deploys',
				'categories' => array( 'devops' ),
				'excerpt'    => 'A fast container image scanning workflow for vulnerabilities, base images, and risky packages.',
				'content'    => self::brief_content(
					'Container image scanning is most valuable when it runs before production and produces fixable results. Teams need policy, ownership, and repeatable exceptions.',
					array(
						'Pin base images and rebuild regularly to absorb upstream security fixes.',
						'Block critical vulnerabilities with known fixes from production promotion.',
						'Track accepted risk with expiry dates and compensating controls.',
					),
					'Scanning should make releases safer without turning every inherited package into an emergency.'
				),
			),
			array(
				'title'      => 'Infrastructure as Code Security Review Tips',
				'slug'       => 'infrastructure-as-code-security-review-tips',
				'categories' => array( 'devops' ),
				'excerpt'    => 'Practical IaC security review checks for identity, storage, networks, logging, and drift.',
				'content'    => self::brief_content(
					'Infrastructure as Code review catches risky defaults before they become live cloud exposure. The best checks are specific, automated, and easy for engineers to fix.',
					array(
						'Review public access, broad IAM permissions, weak encryption, and disabled logging.',
						'Scan pull requests and enforce guardrails in reusable modules.',
						'Compare deployed resources with code to find drift and manual exceptions.',
					),
					'Pair automated policy with short human review for systems that carry sensitive data or privileged access.'
				),
			),
			array(
				'title'      => 'AI Data Leakage Controls for Internal Tools',
				'slug'       => 'ai-data-leakage-controls-internal-tools',
				'categories' => array( 'artificial-intelligence' ),
				'excerpt'    => 'AI data leakage controls for prompts, files, connectors, logs, and internal knowledge tools.',
				'content'    => self::brief_content(
					'Internal AI tools can expose sensitive data through prompts, file uploads, retrieval systems, plugin calls, or verbose logs. Governance needs to be practical and visible.',
					array(
						'Classify which data types may be used with approved AI services.',
						'Limit connectors, redact logs, and block secrets before prompts leave the browser.',
						'Review access to retrieval indexes and shared conversation history.',
					),
					'Start with high-risk data such as credentials, customer records, legal material, and unreleased product information.'
				),
			),
			array(
				'title'      => 'Model Dependency Risk Checklist for AI Teams',
				'slug'       => 'model-dependency-risk-checklist-ai-teams',
				'categories' => array( 'artificial-intelligence' ),
				'excerpt'    => 'A short model dependency checklist for AI supply chain, libraries, datasets, and external APIs.',
				'content'    => self::brief_content(
					'AI applications depend on models, libraries, datasets, vector stores, and external APIs. Each dependency can introduce security, privacy, reliability, or licensing risk.',
					array(
						'Maintain a model and dependency inventory with owners and versions.',
						'Scan packages, pin versions, and record model provenance before deployment.',
						'Review third-party APIs for data handling, outage impact, and access scope.',
					),
					'Make dependency review part of release readiness so AI systems are supportable after launch.'
				),
			),
			array(
				'title'      => 'Prompt Injection Monitoring for Business Apps',
				'slug'       => 'prompt-injection-monitoring-business-apps',
				'categories' => array( 'artificial-intelligence' ),
				'excerpt'    => 'Monitoring ideas for prompt injection, unsafe tool calls, retrieval abuse, and AI app misuse.',
				'content'    => self::brief_content(
					'Prompt injection becomes serious when an AI system can retrieve private data or call tools. Monitoring should focus on impact, not only suspicious wording.',
					array(
						'Log tool calls, data sources used, user identity, and policy decisions.',
						'Alert on unusual connector access, blocked instructions, and repeated sensitive requests.',
						'Test prompts against approved abuse cases before major releases.',
					),
					'Good monitoring helps teams understand whether guardrails are working in real business workflows.'
				),
			),
			array(
				'title'      => 'Build a Weekly Vulnerability Review Workflow',
				'slug'       => 'weekly-vulnerability-review-workflow',
				'categories' => array( 'tutorials' ),
				'excerpt'    => 'A simple weekly vulnerability review workflow for backlog cleanup, owners, and patch evidence.',
				'content'    => self::brief_content(
					'A weekly vulnerability review keeps patch work moving between emergency cycles. It should be short, owner-driven, and focused on aging risk.',
					array(
						'Sort findings by severity, exposure, asset value, and age.',
						'Assign owners and due dates for the top unresolved items.',
						'Close tickets only after version or configuration evidence is captured.',
					),
					'Keep the meeting practical: fewer slides, more decisions, and visible follow-through.'
				),
			),
			array(
				'title'      => 'How to Document Temporary Security Exceptions',
				'slug'       => 'document-temporary-security-exceptions',
				'categories' => array( 'tutorials' ),
				'excerpt'    => 'A lightweight exception record for security risks that cannot be fixed immediately.',
				'content'    => self::brief_content(
					'Temporary exceptions are sometimes necessary, but undocumented exceptions become silent risk. A good record explains why the issue remains open and when it will be reviewed.',
					array(
						'Capture affected asset, risk, owner, compensating control, and expiry date.',
						'Require approval from the team that owns the business impact.',
						'Review expired exceptions before accepting new ones.',
					),
					'An exception should be a managed decision, not a place where unresolved security work disappears.'
				),
			),
			array(
				'title'      => 'Create a Simple Asset Exposure Register',
				'slug'       => 'simple-asset-exposure-register',
				'categories' => array( 'tutorials' ),
				'excerpt'    => 'A beginner-friendly asset exposure register for internet-facing systems and critical services.',
				'content'    => self::brief_content(
					'Asset exposure registers help teams understand what attackers can reach. Start small with systems that face the internet or carry privileged access.',
					array(
						'Record hostname, owner, business purpose, technology stack, and public exposure.',
						'Add authentication method, logging status, patch cadence, and backup contact.',
						'Review the register before major releases and after incident response changes.',
					),
					'A simple register is better than a perfect inventory that nobody maintains.'
				),
			),
			array(
				'title'      => 'Cloud Storage Exposure Checklist',
				'slug'       => 'cloud-storage-exposure-checklist',
				'categories' => array( 'cloud-security' ),
				'excerpt'    => 'Cloud storage security checks for public access, encryption, logging, retention, and ownership.',
				'content'    => self::brief_content(
					'Cloud storage exposure remains one of the easiest mistakes to make and one of the easiest to prevent. Defaults, ownership, and monitoring matter.',
					array(
						'Block public access by default and require documented exceptions.',
						'Enable encryption, access logging, lifecycle retention, and versioning where needed.',
						'Review stale buckets, broad policies, anonymous access, and shared credentials.',
					),
					'Use automated checks in every account so storage exposure is found before customers or attackers find it.'
				),
			),
			array(
				'title'      => 'IAM Least Privilege Review for Cloud Teams',
				'slug'       => 'iam-least-privilege-review-cloud-teams',
				'categories' => array( 'cloud-security' ),
				'excerpt'    => 'A short cloud IAM review for administrator roles, service accounts, access keys, and stale users.',
				'content'    => self::brief_content(
					'Cloud IAM risk grows quietly as teams add projects, service accounts, and emergency permissions. Regular review keeps access aligned with real work.',
					array(
						'Identify administrator roles, wildcard permissions, long-lived keys, and unused accounts.',
						'Replace broad roles with task-based access and time-bound elevation.',
						'Monitor privileged changes and require stronger approval for production environments.',
					),
					'Least privilege is easier when access is reviewed in small, frequent batches instead of once a year.'
				),
			),
			array(
				'title'      => 'Cloud Logging Baseline for Faster Investigations',
				'slug'       => 'cloud-logging-baseline-faster-investigations',
				'categories' => array( 'cloud-security' ),
				'excerpt'    => 'Cloud logging baseline guidance for audit trails, identity events, network flow, and storage access.',
				'content'    => self::brief_content(
					'Investigations move faster when cloud logs already exist. Missing audit trails turn simple questions into slow reconstruction work.',
					array(
						'Enable identity, control-plane, network, storage, and key-management logs.',
						'Centralize logs outside the account or project they describe.',
						'Create alerting for disabled logging, deleted trails, and unusual privileged actions.',
					),
					'Logging should be part of account creation, not a separate project after the first incident.'
				),
			),
			array(
				'title'      => 'Windows Endpoint Hardening Quick Wins',
				'slug'       => 'windows-endpoint-hardening-quick-wins',
				'categories' => array( 'windows-security' ),
				'excerpt'    => 'Windows endpoint hardening quick wins for identity, macro control, PowerShell, and local admin rights.',
				'content'    => self::brief_content(
					'Windows endpoint hardening should focus on controls that reduce common attacker movement. Identity, scripting, and local admin scope are strong starting points.',
					array(
						'Remove unnecessary local administrators and monitor privilege changes.',
						'Control macros, script execution, credential storage, and remote management paths.',
						'Keep EDR, tamper protection, and operating system updates enforced.',
					),
					'Small baseline improvements can reduce incident impact before larger modernization work is complete.'
				),
			),
			array(
				'title'      => 'Active Directory Review Items Before an Incident',
				'slug'       => 'active-directory-review-items-before-incident',
				'categories' => array( 'windows-security' ),
				'excerpt'    => 'Active Directory security review items for privileged groups, stale accounts, delegation, and logging.',
				'content'    => self::brief_content(
					'Active Directory remains a common control plane for enterprise access. Review high-impact settings before an incident forces a rushed audit.',
					array(
						'Check privileged groups, stale accounts, weak delegation, and service account sprawl.',
						'Enable auditing for authentication, directory changes, and privileged operations.',
						'Protect domain controllers with tight network access and backup validation.',
					),
					'Document normal admin paths so unusual access stands out when investigations begin.'
				),
			),
			array(
				'title'      => 'PowerShell Logging Controls for Security Teams',
				'slug'       => 'powershell-logging-controls-security-teams',
				'categories' => array( 'windows-security' ),
				'excerpt'    => 'PowerShell logging controls that improve detection for suspicious scripts and administrative misuse.',
				'content'    => self::brief_content(
					'PowerShell is useful for administrators and attackers. Logging turns script activity into evidence that defenders can investigate.',
					array(
						'Enable script block logging, module logging, and transcription where appropriate.',
						'Collect logs centrally and alert on encoded commands, download cradles, and unusual child processes.',
						'Pair logging with execution policy, constrained language mode, and application control where feasible.',
					),
					'Validate that logs arrive in your SIEM before relying on them during an incident.'
				),
			),
			array(
				'title'      => 'Network Segmentation Checks That Reduce Blast Radius',
				'slug'       => 'network-segmentation-checks-reduce-blast-radius',
				'categories' => array( 'network-security' ),
				'excerpt'    => 'Network segmentation checks for admin paths, production systems, backups, and exposed services.',
				'content'    => self::brief_content(
					'Network segmentation limits how far an attacker can move after one system is compromised. The most useful checks focus on sensitive zones and admin paths.',
					array(
						'Separate management, production, backup, user, and guest networks.',
						'Review firewall rules for broad any-to-any access and stale exceptions.',
						'Test whether low-trust systems can reach domain controllers, admin interfaces, or backup consoles.',
					),
					'Segmentation should be verified with real connectivity tests, not only diagrams.'
				),
			),
			array(
				'title'      => 'VPN Access Review for Remote Teams',
				'slug'       => 'vpn-access-review-remote-teams',
				'categories' => array( 'network-security' ),
				'excerpt'    => 'VPN access review guidance for remote users, device posture, MFA, split tunneling, and logging.',
				'content'    => self::brief_content(
					'VPN access should be reviewed regularly because remote connectivity often reaches sensitive internal systems. Identity and device posture are key controls.',
					array(
						'Require MFA, device compliance, and clear ownership for remote access groups.',
						'Remove stale users, shared accounts, and broad network routes.',
						'Monitor impossible travel, unusual login times, and access from unmanaged devices.',
					),
					'The review should show who can connect, what they can reach, and which controls prove they should still have access.'
				),
			),
			array(
				'title'      => 'DNS Monitoring Ideas for Early Threat Detection',
				'slug'       => 'dns-monitoring-ideas-early-threat-detection',
				'categories' => array( 'network-security' ),
				'excerpt'    => 'DNS monitoring ideas for suspicious domains, tunneling patterns, malware callbacks, and policy gaps.',
				'content'    => self::brief_content(
					'DNS telemetry can reveal early signs of phishing, malware, data staging, and policy bypass. It is especially useful when endpoint visibility is incomplete.',
					array(
						'Alert on newly registered domains, high-entropy names, unusual TLDs, and known malicious infrastructure.',
						'Watch for DNS tunneling patterns such as long queries, high volume, and repeated failures.',
						'Block unmanaged resolvers and compare DNS logs with proxy and endpoint data.',
					),
					'Keep detections tuned so DNS monitoring remains high signal instead of background noise.'
				),
			),
			array(
				'title'      => 'API Authentication Mistakes to Fix This Week',
				'slug'       => 'api-authentication-mistakes-fix-this-week',
				'categories' => array( 'web-security' ),
				'excerpt'    => 'Web API authentication mistakes involving tokens, sessions, rate limits, and authorization checks.',
				'content'    => self::brief_content(
					'API security failures often come from authentication and authorization gaps that are easy to overlook during fast product changes.',
					array(
						'Use short-lived tokens, strong session storage, and rotation for leaked credentials.',
						'Check object-level authorization on every sensitive API route.',
						'Add rate limits, audit logs, and alerts for suspicious authentication patterns.',
					),
					'Fixing the basics reduces account takeover and data exposure risk before deeper testing begins.'
				),
			),
			array(
				'title'      => 'Web Application Security Headers Explained',
				'slug'       => 'web-application-security-headers-explained',
				'categories' => array( 'web-security' ),
				'excerpt'    => 'A simple guide to security headers for clickjacking, content sniffing, HTTPS, and browser policy.',
				'content'    => self::brief_content(
					'Security headers help browsers enforce safer behavior. They are not a WAF, but they reduce common risks such as clickjacking, mixed content, and unsafe content handling.',
					array(
						'Set X-Frame-Options or CSP frame-ancestors to reduce clickjacking exposure.',
						'Use X-Content-Type-Options nosniff and HSTS on HTTPS sites.',
						'Add a Content-Security-Policy that fits scripts, images, forms, and frames your site actually uses.',
					),
					'Start with a compatible policy, test it, then tighten directives as the site becomes more predictable.'
				),
			),
			array(
				'title'      => 'Login Security Checklist for WordPress Sites',
				'slug'       => 'login-security-checklist-wordpress-sites',
				'categories' => array( 'web-security' ),
				'excerpt'    => 'WordPress login security checklist for MFA, least privilege, updates, backups, and monitoring.',
				'content'    => self::brief_content(
					'WordPress login security depends on more than a hidden URL. Strong identity controls, updates, backups, and monitoring all matter.',
					array(
						'Use MFA for administrators and remove unused privileged accounts.',
						'Keep themes, plugins, and WordPress core patched from trusted sources.',
						'Monitor failed logins, admin changes, plugin installs, and file modifications.',
					),
					'Pair login hardening with reliable backups so recovery remains possible if prevention fails.'
				),
			),
		);

		foreach ( $posts as $post ) {
			$term_ids = array();
			foreach ( $post['categories'] as $slug ) {
				if ( isset( $categories[ $slug ] ) ) {
					$term_ids[] = $categories[ $slug ];
				}
			}

			self::upsert_post(
				'post',
				$post['slug'],
				array(
					'post_title'    => $post['title'],
					'post_excerpt'  => $post['excerpt'],
					'post_content'  => $post['content'],
					'post_status'    => 'publish',
					'comment_status' => 'closed',
					'ping_status'    => 'closed',
					'post_category'  => $term_ids,
				)
			);
		}
	}

	/**
	 * Create one current briefing per category for the active site date.
	 *
	 * @param array<string,int> $categories Category IDs by slug.
	 * @param bool              $force      Whether to request fresh source data.
	 * @param array<string,mixed> $live_data Optional pre-collected source data.
	 */
	private static function create_daily_posts( array $categories, bool $force = false, array $live_data = array() ): void {
		$date = current_time( 'Y-m-d' );
		if ( empty( $live_data ) ) {
			$live_data = Live_Intelligence::collect( $force );
		}
		if ( empty( $live_data['items'] ) ) {
			return;
		}

		$timestamp  = strtotime( $date . ' 12:00:00' );
		$human_date = $timestamp ? wp_date( 'F j, Y', $timestamp ) : $date;
		$post_date  = current_time( 'mysql' );
		$date_slug  = sanitize_title( $date );
		$changed     = 0;
		$changed_ids = array();

		foreach ( Live_Intelligence::daily_posts( $human_date, $live_data ) as $post ) {
			$term_ids = array();
			foreach ( $post['categories'] as $slug ) {
				if ( isset( $categories[ $slug ] ) ) {
					$term_ids[] = $categories[ $slug ];
				}
			}

			$post_slug = $date_slug . '-' . $post['slug'];
			$existing  = get_page_by_path( $post_slug, OBJECT, 'post' );
			if (
				$existing
				&& hash_equals(
					(string) get_post_meta( $existing->ID, '_infosecnexus_live_fingerprint', true ),
					(string) $post['fingerprint']
				)
			) {
				continue;
			}

			$published_date     = $existing ? (string) $existing->post_date : $post_date;
			$published_date_gmt = $existing ? (string) $existing->post_date_gmt : get_gmt_from_date( $post_date );
			$post_id = self::upsert_post(
				'post',
				$post_slug,
				array(
					'post_title'    => $post['title'],
					'post_excerpt'  => $post['excerpt'],
					'post_content'  => $post['content'],
					'post_status'   => 'publish',
					'post_date'     => $published_date,
					'post_date_gmt' => $published_date_gmt,
					'comment_status' => 'closed',
					'ping_status'   => 'closed',
					'post_category' => $term_ids,
					'meta_input'    => array(
						'_infosecnexus_daily_content'     => $date,
						'_infosecnexus_source_urls'       => wp_json_encode( $post['sources'] ),
						'_infosecnexus_live_source_ids'   => wp_json_encode( $post['source_ids'] ),
						'_infosecnexus_live_fingerprint'  => $post['fingerprint'],
						'_infosecnexus_live_checked_at'   => (string) ( $live_data['checked_at'] ?? '' ),
						'_infosecnexus_seo_description'   => $post['excerpt'],
						'_yoast_wpseo_metadesc'           => $post['excerpt'],
						'rank_math_description'           => $post['excerpt'],
						'_seopress_titles_desc'           => $post['excerpt'],
					),
				)
			);
			if ( $post_id > 0 ) {
				Post_Artwork::ensure( $post_id );
				++$changed;
				$changed_ids[] = $post_id;
			}
		}

		if ( $changed > 0 || ! empty( $live_data['items'] ) ) {
			self::mark_daily_date_seeded( $date );
		}

		if ( $changed > 0 || $force ) {
			self::purge_public_cache( $changed_ids );
		}
	}

	/**
	 * Purge public page caches after live briefings change.
	 *
	 * Logged-in administrators commonly bypass host-level caches, so explicitly
	 * purge the public homepage and affected archives for anonymous readers.
	 *
	 * @param array<int,int> $post_ids Changed post IDs.
	 */
	private static function purge_public_cache( array $post_ids ): void {
		$urls          = array( home_url( '/' ), home_url( '/llms.txt' ) );
		$posts_page_id = (int) get_option( 'page_for_posts' );

		if ( $posts_page_id > 0 ) {
			$urls[] = get_permalink( $posts_page_id );
		}

		$blog_page = get_page_by_path( 'blog' );
		if ( $blog_page instanceof \WP_Post ) {
			$urls[] = get_permalink( $blog_page );
		}

		foreach ( array_unique( array_map( 'absint', $post_ids ) ) as $post_id ) {
			if ( $post_id <= 0 ) {
				continue;
			}

			$urls[] = get_permalink( $post_id );
			foreach ( wp_get_post_categories( $post_id ) as $category_id ) {
				$category_url = get_category_link( $category_id );
				if ( ! is_wp_error( $category_url ) ) {
					$urls[] = $category_url;
				}
			}
		}

		/**
		 * Filter public URLs purged after a live briefing refresh.
		 *
		 * @param array<int,string> $urls     URLs queued for purge.
		 * @param array<int,int>    $post_ids Changed post IDs.
		 */
		$urls = (array) apply_filters( 'infosecnexus_daily_cache_purge_urls', $urls, $post_ids );
		$urls = array_slice( array_values( array_unique( array_filter( array_map( 'strval', $urls ) ) ) ), 0, 30 );

		wp_cache_flush();

		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
		}
		if ( function_exists( 'w3tc_flush_all' ) ) {
			w3tc_flush_all();
		}
		if ( function_exists( 'wp_cache_clear_cache' ) ) {
			wp_cache_clear_cache();
		}
		do_action( 'litespeed_purge_all' );
		do_action( 'rt_nginx_helper_purge_all' );

		$home_host = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
		foreach ( $urls as $url ) {
			$url_host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
			if ( '' === $home_host || $home_host !== $url_host ) {
				continue;
			}

			wp_remote_request(
				$url,
				array(
					'method'      => 'PURGE',
					'timeout'     => 3,
					'redirection' => 0,
					'headers'     => array(
						'Cache-Control' => 'no-cache',
						'X-Purge-Reason' => 'infosecnexus-daily-content',
					),
				)
			);
		}

		do_action( 'infosecnexus_daily_cache_purged', $urls, $post_ids );
	}

	/**
	 * Return duplicate-safe daily briefing blueprints.
	 *
	 * @param string $date Human readable date.
	 * @return array<int,array<string,mixed>>
	 */
	private static function daily_post_blueprints( string $date ): array {
		return array(
			array(
				'title'       => 'Daily CVE Watch for ' . $date . ': KEV, NVD, and Patch Priority',
				'slug'        => 'daily-cve-watch-kev-nvd-patch-priority',
				'categories'  => array( 'critical-cves' ),
				'excerpt'     => 'A daily CVE triage note for exploited vulnerabilities, recent NVD entries, exposure checks, and patch ownership.',
				'summary'     => 'Today\'s CVE review should start with active exploitation signals, then move into fresh NVD entries and the systems that are actually reachable in your environment.',
				'source_note' => 'The latest CISA KEV additions on July 21, 2026 included Langflow, WordPress Core, DD-WRT, Fortinet FortiSandbox, and Microsoft SharePoint entries. NVD also published multiple new CVE records on July 21, including Netty and Gitleaks items.',
				'checks'      => array(
					'Compare the CISA KEV catalog with your external asset inventory before ranking normal backlog items.',
					'Review NVD records for technologies your teams actually run, then confirm affected versions from vendor guidance.',
					'Give internet-facing, privileged, and customer-impacting systems the first patch or mitigation window.',
					'Record the owner, target date, temporary control, and validation evidence for every high-risk exception.',
				),
				'sources'     => array(
					array( 'CISA Known Exploited Vulnerabilities Catalog', 'https://www.cisa.gov/known-exploited-vulnerabilities-catalog' ),
					array( 'NVD Vulnerability Search', 'https://nvd.nist.gov/vuln/search' ),
				),
				'next_step'   => 'Create a same-day shortlist of exposed assets and schedule validation before the patch ticket is marked complete.',
			),
			array(
				'title'       => 'Cyber Security Brief for ' . $date . ': Exploitation Signals and Response Focus',
				'slug'        => 'cyber-security-brief-exploitation-signals-response-focus',
				'categories'  => array( 'cybersecurity' ),
				'excerpt'     => 'A practical daily security operations brief for exploit signals, detection review, and response planning.',
				'summary'     => 'Daily security review works best when threat signals are converted into tasks that engineering, IT, and security can finish within the next operating window.',
				'source_note' => 'Use the current CISA KEV and NVD feeds as source inputs, then map only relevant items to your environment instead of treating every advisory as equal.',
				'checks'      => array(
					'Check whether newly listed exploited products overlap with internet-facing services, VPN paths, identity platforms, or admin tooling.',
					'Review detection coverage for authentication changes, new processes, public scanning, and unusual outbound traffic.',
					'Separate confirmed exposure from inventory-only matches so urgent work does not become background noise.',
					'Publish a short internal note with what changed, who owns action, and when the next update will happen.',
				),
				'sources'     => array(
					array( 'CISA Known Exploited Vulnerabilities Catalog', 'https://www.cisa.gov/known-exploited-vulnerabilities-catalog' ),
					array( 'NVD Vulnerability Search', 'https://nvd.nist.gov/vuln/search' ),
				),
				'next_step'   => 'Turn the top three relevant signals into detection, patch, or isolation work with named owners.',
			),
			array(
				'title'       => 'Linux Security Brief for ' . $date . ': Kernel, Package, and Service Checks',
				'slug'        => 'linux-security-brief-kernel-package-service-checks',
				'categories'  => array( 'linux-administration' ),
				'excerpt'     => 'A Linux admin checklist for daily security notices, kernel updates, exposed packages, and post-patch validation.',
				'summary'     => 'Linux patch review should connect distribution notices with the servers, containers, and services that actually depend on affected packages.',
				'source_note' => 'Ubuntu Security Notices and NVD recent records are useful daily inputs for kernel, service, and library review. Treat distribution guidance as the final source for package versions.',
				'checks'      => array(
					'Compare distro security notices with your running kernel and installed package versions.',
					'Check reboot-required status, live patch status, loaded modules, and service restarts after updates.',
					'Prioritize exposed SSH, web, DNS, database, and management hosts before lower-risk internal systems.',
					'Document exceptions for hosts that cannot reboot, including the temporary control and next maintenance window.',
				),
				'sources'     => array(
					array( 'Ubuntu Security Notices', 'https://ubuntu.com/security/notices' ),
					array( 'NVD Vulnerability Search', 'https://nvd.nist.gov/vuln/search' ),
				),
				'next_step'   => 'Run a version and reboot-state check on production Linux groups before closing patch work.',
			),
			array(
				'title'       => 'DevOps Security Brief for ' . $date . ': Pipelines, Secrets, and Build Dependencies',
				'slug'        => 'devops-security-brief-pipelines-secrets-build-dependencies',
				'categories'  => array( 'devops' ),
				'excerpt'     => 'A daily DevOps security review for CI/CD secrets, package advisories, runners, and build isolation.',
				'summary'     => 'DevOps risk often appears through build systems, dependency updates, automation tokens, and release workflows rather than a single server alert.',
				'source_note' => 'Recent NVD and GitHub advisory feeds should be checked for dependency, package, and developer-tool vulnerabilities that affect active pipelines.',
				'checks'      => array(
					'Review dependency advisories for packages used by build jobs, deployment tooling, and internal services.',
					'Rotate exposed or long-lived CI/CD tokens and remove secrets from logs, artifacts, and cached workspaces.',
					'Confirm production deploy workflows require reviewed branches, scoped permissions, and isolated runners.',
					'Block releases only for issues with reachable impact or clear exploitability in your pipeline context.',
				),
				'sources'     => array(
					array( 'GitHub Advisory Database', 'https://github.com/advisories' ),
					array( 'NVD Vulnerability Search', 'https://nvd.nist.gov/vuln/search' ),
				),
				'next_step'   => 'Pick one high-risk pipeline and verify secrets, runner isolation, and dependency scan results today.',
			),
			array(
				'title'       => 'AI Security Brief for ' . $date . ': Prompt Injection, Connectors, and Data Boundaries',
				'slug'        => 'ai-security-brief-prompt-injection-connectors-data-boundaries',
				'categories'  => array( 'artificial-intelligence' ),
				'excerpt'     => 'A daily AI security note for prompt injection, connector access, sensitive data, and model dependency review.',
				'summary'     => 'AI security review should focus on the points where model output can trigger tools, expose private data, or influence business workflows.',
				'source_note' => 'OWASP GenAI guidance and the NIST Generative AI profile provide useful control language for prompt injection, data leakage, and governance discussions.',
				'checks'      => array(
					'List which AI tools can access email, tickets, code, documents, cloud data, or production systems.',
					'Add approval, logging, and scope controls around connector actions that change data or call external services.',
					'Block credentials, private keys, customer records, and unreleased product information from prompts and logs.',
					'Test prompt injection scenarios against retrieval and tool-use workflows before expanding access.',
				),
				'sources'     => array(
					array( 'OWASP Top 10 for LLM Applications', 'https://genai.owasp.org/llm-top-10/' ),
					array( 'NIST Generative AI Profile', 'https://www.nist.gov/itl/ai-risk-management-framework/generative-artificial-intelligence-profile' ),
				),
				'next_step'   => 'Review one AI workflow with connector access and document the data it can read, write, and expose.',
			),
			array(
				'title'       => 'Tutorial: Run a 30-Minute Daily Vulnerability Standup on ' . $date,
				'slug'        => 'tutorial-run-daily-vulnerability-standup',
				'categories'  => array( 'tutorials' ),
				'excerpt'     => 'A simple daily vulnerability standup format for teams that need faster ownership and cleaner patch decisions.',
				'summary'     => 'A daily vulnerability standup does not need to be long. It needs a clear queue, owners, evidence, and decisions that reduce exposure before the next meeting.',
				'source_note' => 'Use authoritative feeds such as CISA KEV, NVD, vendor bulletins, and distribution notices to decide what belongs in the agenda.',
				'checks'      => array(
					'Open with exploited and internet-facing items before normal severity sorting.',
					'Assign one owner and one validation method for each action item.',
					'Separate patch-now, mitigate-now, monitor, and accept-risk decisions.',
					'End with a written list of assets still exposed and the next review time.',
				),
				'sources'     => array(
					array( 'CISA Known Exploited Vulnerabilities Catalog', 'https://www.cisa.gov/known-exploited-vulnerabilities-catalog' ),
					array( 'NVD Vulnerability Search', 'https://nvd.nist.gov/vuln/search' ),
				),
				'next_step'   => 'Use this agenda for the next patch review and keep the notes short enough that teams will actually update them.',
			),
			array(
				'title'       => 'Cloud Security Brief for ' . $date . ': Bulletins, IAM, and Public Exposure',
				'slug'        => 'cloud-security-brief-bulletins-iam-public-exposure',
				'categories'  => array( 'cloud-security' ),
				'excerpt'     => 'A cloud security review for provider bulletins, public assets, IAM risk, and logging coverage.',
				'summary'     => 'Cloud security review should combine provider bulletins with your own exposure map. A bulletin matters most when affected services are public, privileged, or tied to sensitive data.',
				'source_note' => 'AWS and Google Cloud security bulletin pages are useful starting points, but each team must confirm which managed services, images, and nodes are deployed.',
				'checks'      => array(
					'Review provider security bulletins against cloud accounts, projects, regions, and managed services in use.',
					'Check public storage, exposed load balancers, admin ports, and broad security group rules.',
					'Look for over-permissive IAM roles, long-lived keys, stale service accounts, and missing MFA paths.',
					'Confirm audit logs are centralized outside the account or project they describe.',
				),
				'sources'     => array(
					array( 'AWS Security Bulletins', 'https://aws.amazon.com/security/security-bulletins/' ),
					array( 'Google Cloud Security Bulletins', 'https://cloud.google.com/support/bulletins' ),
				),
				'next_step'   => 'Select one production account and verify public exposure, privileged IAM, and log collection before the next deploy.',
			),
			array(
				'title'       => 'Windows Security Brief for ' . $date . ': Update Review and Identity Controls',
				'slug'        => 'windows-security-brief-update-review-identity-controls',
				'categories'  => array( 'windows-security' ),
				'excerpt'     => 'A Windows security review for Microsoft updates, privileged access, endpoint controls, and patch validation.',
				'summary'     => 'Windows patch review should not stop at installing updates. Teams need to confirm affected products, restart state, privilege exposure, and endpoint control health.',
				'source_note' => 'The Microsoft Security Update Guide is the primary source for Windows and Microsoft product security update details. Use it with asset inventory and EDR telemetry.',
				'checks'      => array(
					'Confirm which Microsoft products and versions are present across endpoints and servers.',
					'Review privileged groups, service accounts, remote management paths, and stale local administrators.',
					'Validate patch state after reboot, then verify EDR, tamper protection, and logging still report correctly.',
					'Escalate systems with internet exposure, domain privilege, or sensitive data before normal endpoint queues.',
				),
				'sources'     => array(
					array( 'Microsoft Security Update Guide', 'https://msrc.microsoft.com/update-guide' ),
					array( 'NVD Vulnerability Search', 'https://nvd.nist.gov/vuln/search' ),
				),
				'next_step'   => 'Run a focused report for unpatched privileged Windows systems and give each exception a deadline.',
			),
			array(
				'title'       => 'Network Security Brief for ' . $date . ': Edge Devices, DNS, and Segmentation',
				'slug'        => 'network-security-brief-edge-devices-dns-segmentation',
				'categories'  => array( 'network-security' ),
				'excerpt'     => 'A daily network security review for exposed edge devices, DNS signals, firewall rules, and blast-radius reduction.',
				'summary'     => 'Network teams should treat exploited edge-device advisories and suspicious DNS behavior as signals to verify reachable paths, not only device versions.',
				'source_note' => 'CISA KEV and NVD records regularly include routers, NAS devices, VPN products, and network appliances. Confirm public exposure before assigning urgency.',
				'checks'      => array(
					'Compare edge-device advisories with firewall, VPN, NAS, router, and remote-management inventory.',
					'Check whether management interfaces are reachable from the internet or lower-trust networks.',
					'Review DNS logs for newly registered domains, tunneling patterns, and malware callback indicators.',
					'Test segmentation between user, production, management, and backup networks.',
				),
				'sources'     => array(
					array( 'CISA Known Exploited Vulnerabilities Catalog', 'https://www.cisa.gov/known-exploited-vulnerabilities-catalog' ),
					array( 'NVD Vulnerability Search', 'https://nvd.nist.gov/vuln/search' ),
				),
				'next_step'   => 'Choose the most exposed edge service and verify patch state, management access, logs, and backup isolation.',
			),
			array(
				'title'       => 'Web Security Brief for ' . $date . ': APIs, Headers, and WordPress Attack Surface',
				'slug'        => 'web-security-brief-apis-headers-wordpress-attack-surface',
				'categories'  => array( 'web-security' ),
				'excerpt'     => 'A web security review for API authentication, browser security headers, WordPress updates, and public attack surface.',
				'summary'     => 'Web security review should cover the application behavior users touch, the headers browsers enforce, and the CMS or framework components that can expose the site.',
				'source_note' => 'Use OWASP API guidance with WordPress and NVD advisories to review authentication, authorization, patching, and browser protection basics.',
				'checks'      => array(
					'Confirm API routes enforce object-level authorization and rate limits on sensitive actions.',
					'Check security headers for frame protection, content sniffing prevention, HSTS, and a compatible CSP.',
					'Update WordPress core, themes, and plugins from trusted sources and remove unused extensions.',
					'Review logs for failed logins, admin changes, plugin installs, and unusual request spikes.',
				),
				'sources'     => array(
					array( 'OWASP API Security Project', 'https://owasp.org/API-Security/' ),
					array( 'NVD Vulnerability Search', 'https://nvd.nist.gov/vuln/search' ),
				),
				'next_step'   => 'Run one public-page check and one authenticated API check, then fix the highest-impact gap first.',
			),
		);
	}

	/**
	 * Build a daily, source-aware SEO briefing body.
	 *
	 * @param string               $summary Summary paragraph.
	 * @param string[]             $checks Action checklist.
	 * @param string               $source_note Source context.
	 * @param array<int,string[]>   $sources Source title and URL pairs.
	 * @param string               $next_step Closing paragraph.
	 * @return string
	 */
	private static function daily_brief_content( string $summary, array $checks, string $source_note, array $sources, string $next_step ): string {
		$content  = '<p>' . esc_html( $summary ) . '</p><!--more-->';
		$content .= '<h2>What changed today</h2>';
		$content .= '<p>' . esc_html( $source_note ) . '</p>';
		$content .= '<h2>Why this matters</h2>';
		$content .= '<p>Daily cybersecurity content should help teams move from awareness to action. The best review starts with trusted sources, filters those signals through your own asset inventory, and turns the remaining items into work that has owners and evidence.</p>';
		$content .= '<h2>Action checklist</h2><ul>';

		foreach ( $checks as $check ) {
			$content .= '<li>' . esc_html( $check ) . '</li>';
		}

		$content .= '</ul>';
		$content .= '<h2>Source watch</h2>';
		$content .= '<p>Use these references as live source material, then validate affected versions and mitigations against vendor documentation before making production changes.</p>';
		$content .= self::source_links_html( $sources );
		$content .= '<h2>Next step</h2><p>' . esc_html( $next_step ) . '</p>';

		return $content;
	}

	/**
	 * Render source links for generated briefings.
	 *
	 * @param array<int,string[]> $sources Source title and URL pairs.
	 * @return string
	 */
	private static function source_links_html( array $sources ): string {
		$content = '<ul>';
		foreach ( $sources as $source ) {
			$title = (string) ( $source[0] ?? '' );
			$url   = (string) ( $source[1] ?? '' );
			if ( '' === $title || '' === $url ) {
				continue;
			}
			$content .= '<li><a href="' . esc_url( $url ) . '" rel="nofollow noopener" target="_blank">' . esc_html( $title ) . '</a></li>';
		}
		$content .= '</ul>';

		return $content;
	}

	/**
	 * Check if a daily content date has already been seeded.
	 *
	 * @param string $date Site-local date in Y-m-d format.
	 * @return bool
	 */
	private static function daily_date_seeded( string $date ): bool {
		$dates = get_option( self::DAILY_SEEDED_OPTION, array() );
		return is_array( $dates ) && in_array( $date, $dates, true );
	}

	/**
	 * Mark a daily content date as seeded.
	 *
	 * @param string $date Site-local date in Y-m-d format.
	 */
	private static function mark_daily_date_seeded( string $date ): void {
		$dates = get_option( self::DAILY_SEEDED_OPTION, array() );
		$dates = is_array( $dates ) ? array_map( 'strval', $dates ) : array();
		$dates[] = $date;
		$dates = array_slice( array_values( array_unique( $dates ) ), -90 );

		update_option( self::DAILY_SEEDED_OPTION, $dates, false );
	}

	/**
	 * Build an SEO-friendly demo briefing body.
	 *
	 * @param string   $summary Summary paragraph.
	 * @param string[] $checks Action checklist.
	 * @param string   $next_step Closing paragraph.
	 * @return string
	 */
	private static function brief_content( string $summary, array $checks, string $next_step ): string {
		$profile = self::brief_profile( $summary . ' ' . implode( ' ', $checks ) . ' ' . $next_step );
		$content  = '<p>' . esc_html( $summary ) . '</p><!--more-->';
		$content .= '<h2>' . esc_html( $profile['context_heading'] ) . '</h2>';
		$content .= '<p>' . esc_html( $profile['context'] ) . '</p>';
		$content .= '<p>' . esc_html( $profile['scope'] ) . '</p>';
		$content .= '<h2>' . esc_html( $profile['review_heading'] ) . '</h2>';

		foreach ( $checks as $index => $check ) {
			$note = $profile['check_notes'][ $index % count( $profile['check_notes'] ) ];
			$content .= '<h3>' . esc_html( (string) $check ) . '</h3>';
			$content .= '<p>' . esc_html( (string) $note ) . '</p>';
		}

		$content .= '<h2>' . esc_html( $profile['workflow_heading'] ) . '</h2>';
		$content .= '<p>' . esc_html( $profile['workflow'] ) . '</p>';
		$content .= '<ul>';
		foreach ( $profile['workflow_steps'] as $step ) {
			$content .= '<li>' . esc_html( (string) $step ) . '</li>';
		}
		$content .= '</ul>';
		$expansion = self::brief_expansion( (string) $profile['closing_heading'] );
		$content .= '<h2>' . esc_html( $expansion['priority_heading'] ) . '</h2>';
		$content .= '<p>' . esc_html( $expansion['priority'] ) . '</p>';
		$content .= '<h3>' . esc_html( $expansion['pitfall_heading'] ) . '</h3>';
		$content .= '<p>' . esc_html( $expansion['pitfall'] ) . '</p>';
		$content .= '<h2>' . esc_html( $profile['validation_heading'] ) . '</h2>';
		$content .= '<p>' . esc_html( $profile['validation'] ) . '</p>';
		$content .= '<p>' . esc_html( $profile['reporting'] ) . '</p>';
		$content .= '<h2>' . esc_html( $profile['closing_heading'] ) . '</h2>';
		$content .= '<p>' . esc_html( $next_step ) . '</p>';

		return $content;
	}

	/**
	 * Rebuild only short legacy daily posts with the current desk structure.
	 *
	 * @param string $content Existing article HTML.
	 */
	private static function modernize_short_daily_content( string $content ): string {
		if ( strlen( wp_strip_all_tags( $content ) ) >= 2600 ) {
			return $content;
		}

		$summary = '';
		if ( preg_match( '#<p[^>]*>(.*?)</p>#is', $content, $summary_match ) ) {
			$summary = trim( wp_strip_all_tags( (string) $summary_match[1] ) );
		}

		$checks = array();
		if ( preg_match_all( '#<li[^>]*>(.*?)</li>#is', $content, $check_matches ) ) {
			foreach ( (array) $check_matches[1] as $check_html ) {
				if ( false !== stripos( (string) $check_html, '<a ' ) ) {
					continue;
				}
				$check = trim( wp_strip_all_tags( (string) $check_html ) );
				if ( strlen( $check ) >= 28 ) {
					$checks[] = $check;
				}
				if ( count( $checks ) >= 4 ) {
					break;
				}
			}
		}

		$next_step = '';
		if ( preg_match( '#<h2>Next step</h2>\s*<p[^>]*>(.*?)</p>#is', $content, $next_match ) ) {
			$next_step = trim( wp_strip_all_tags( (string) $next_match[1] ) );
		}

		if ( '' === $summary || count( $checks ) < 2 || '' === $next_step ) {
			return $content;
		}

		$rebuilt = self::brief_content( $summary, $checks, $next_step );
		if ( preg_match( '#<details class="isnx-references">.*?</details>#is', $content, $references ) ) {
			$rebuilt .= (string) $references[0];
		} elseif ( preg_match( '#<h2>Source watch</h2>.*?(<ul>.*?</ul>)#is', $content, $references ) ) {
			$rebuilt .= '<details class="isnx-references"><summary>References used in this briefing</summary>' . (string) $references[1] . '</details>';
		}

		return $rebuilt;
	}

	/**
	 * Give older daily editions a date- and desk-specific editorial focus.
	 *
	 * Early daily posts could reuse the same body when upstream feeds had not
	 * changed overnight. Current source-backed posts already contain live item
	 * sections, so this migration is limited to those older editions.
	 *
	 * @param int    $post_id Daily post ID.
	 * @param string $content Existing article HTML.
	 */
	private static function individualize_legacy_daily_content( int $post_id, string $content ): string {
		if (
			false !== strpos( $content, 'class="isnx-live-item"' )
			|| false !== strpos( $content, 'class="isnx-edition-focus"' )
		) {
			return $content;
		}

		$date = sanitize_text_field( (string) get_post_meta( $post_id, '_infosecnexus_daily_content', true ) );
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			$date = get_the_date( 'Y-m-d', $post_id );
		}

		$timestamp = strtotime( $date . ' 12:00:00' );
		$human_date = false !== $timestamp ? wp_date( 'F j, Y', $timestamp ) : $date;
		$term_slugs = wp_get_post_terms( $post_id, 'category', array( 'fields' => 'slugs' ) );
		$term_slugs = is_wp_error( $term_slugs ) ? array() : array_map( 'strval', $term_slugs );
		$profiles   = array(
			'critical-cves' => array(
				'label'    => 'Exploit-led vulnerability',
				'scope'    => 'actively exploited, internet-facing, privileged, and hard-to-recover systems',
				'evidence' => 'exact product versions, reachable features, exploit telemetry, patch state, and restart evidence',
				'action'   => 'assign a patch, containment, investigation, or documented non-applicability decision',
			),
			'cybersecurity' => array(
				'label'    => 'Security operations',
				'scope'    => 'developments that change a current defensive assumption or expose an owned technology',
				'evidence' => 'asset ownership, exposure, identity impact, detection coverage, and recovery readiness',
				'action'   => 'turn each relevant signal into a small, accountable response rather than a broad news queue',
			),
			'linux-administration' => array(
				'label'    => 'Linux operations',
				'scope'    => 'public services, bastions, orchestration nodes, shared hosts, and privileged workloads',
				'evidence' => 'distribution package versions, the running kernel or process, reboot state, and service health',
				'action'   => 'sequence remediation around workload risk and prove that the corrected code is actually loaded',
			),
			'devops' => array(
				'label'    => 'DevSecOps',
				'scope'    => 'pull requests, runners, dependencies, artifacts, secrets, and production deployment paths',
				'evidence' => 'trigger conditions, token scope, runner isolation, artifact provenance, and approval boundaries',
				'action'   => 'remove inherited trust from the path between untrusted code and a production release',
			),
			'artificial-intelligence' => array(
				'label'    => 'AI security',
				'scope'    => 'agents and assistants that combine untrusted context with private data or consequential tools',
				'evidence' => 'connector scope, retained context, tool permissions, approval gates, and complete invocation logs',
				'action'   => 'reduce authority outside the model and test whether hostile context can influence a sensitive call',
			),
			'tutorials' => array(
				'label'    => 'Security workflow',
				'scope'    => 'a limited set of advisories that can be mapped to real systems and responsible teams',
				'evidence' => 'affected assets, owners, deadlines, verification steps, exceptions, and the next review time',
				'action'   => 'finish with a compact action register that another engineer can verify without repeating the research',
			),
			'cloud-security' => array(
				'label'    => 'Cloud security',
				'scope'    => 'public control planes, privileged identities, storage, production clusters, and sensitive workloads',
				'evidence' => 'accounts, regions, identity paths, public endpoints, workload images, provider status, and audit logs',
				'action'   => 'separate provider remediation from tenant-owned work and verify both sides of the control',
			),
			'windows-security' => array(
				'label'    => 'Windows security',
				'scope'    => 'identity systems, exposed servers, administrator workstations, and endpoints with reusable credentials',
				'evidence' => 'supported builds, installed updates, restart state, server roles, authentication events, and EDR health',
				'action'   => 'move high-impact deployment rings first and keep failed or unreachable systems visible',
			),
			'network-security' => array(
				'label'    => 'Network security',
				'scope'    => 'internet-edge appliances, VPNs, gateways, DNS infrastructure, and management interfaces',
				'evidence' => 'models, firmware, exposed administration paths, configuration changes, routes, tunnels, and independent logs',
				'action'   => 'contain reachable management paths before patching and validate traffic plus high availability afterward',
			),
			'web-security' => array(
				'label'    => 'Application security',
				'scope'    => 'public routes, APIs, sessions, plugins, browser controls, and authorization boundaries',
				'evidence' => 'enabled component versions, request methods, user roles, response behavior, logs, and recovery readiness',
				'action'   => 'fix the permanent application cause and retest both denied and authorized requests through the public path',
			),
		);

		$slug = 'cybersecurity';
		foreach ( array_keys( $profiles ) as $candidate ) {
			if ( in_array( $candidate, $term_slugs, true ) ) {
				$slug = $candidate;
				break;
			}
		}

		$profile = $profiles[ $slug ];
		$variant = abs( (int) crc32( $date . '|' . $slug ) ) % 3;
		$paragraphs = array(
			array(
				'For %1$s, this %2$s edition puts %3$s first. The aim is to convert the day\'s references into an owned decision instead of repeating every headline.',
				'Review %1$s, then %2$s. Production evidence should determine priority; a category label or severity score alone should not close the work.',
			),
			array(
				'The %1$s %2$s review is organized around %3$s. Read each item against the environment that actually runs it, including inherited trust and operational dependencies.',
				'Use %1$s to separate confirmed exposure from broad advisory language, then %2$s. Record the reason whenever an item is deferred or found not applicable.',
			),
			array(
				'This %1$s edition emphasizes closure quality for %2$s. Start with %3$s so the response follows reachable risk rather than the order in which headlines arrived.',
				'Capture %1$s before and after remediation, then %2$s. Keep temporary mitigations attached to an owner and expiry date until the permanent control is verified.',
			),
		);

		$edition  = '<section class="isnx-edition-focus">';
		$edition .= '<h2>' . esc_html( (string) $profile['label'] . ' focus for ' . $human_date ) . '</h2>';
		$edition .= '<p>' . esc_html(
			sprintf(
				$paragraphs[ $variant ][0],
				$human_date,
				(string) $profile['label'],
				(string) $profile['scope']
			)
		) . '</p>';
		$edition .= '<p>' . esc_html(
			sprintf(
				$paragraphs[ $variant ][1],
				(string) $profile['evidence'],
				(string) $profile['action']
			)
		) . '</p>';
		$edition .= '</section>';

		if ( false !== strpos( $content, '<!--more-->' ) ) {
			return (string) preg_replace( '/<!--more-->/', '<!--more-->' . $edition, $content, 1 );
		}

		return $edition . $content;
	}

	/**
	 * Add category-specific prioritization depth without generic filler.
	 *
	 * @return array{priority_heading:string,priority:string,pitfall_heading:string,pitfall:string}
	 */
	private static function brief_expansion( string $closing_heading ): array {
		$sets = array(
			'Application team action' => array(
				'priority_heading' => 'Choose the first route to fix',
				'priority'         => 'Prioritize an application path when it is reachable without strong authentication, exposes another user or tenant, performs a privileged action, handles payment or identity data, or depends on a component with active exploitation. Use production request evidence and role testing to rank work. A broad scanner label should not move ahead of a confirmed authorization failure on a sensitive route. Where several endpoints share the same middleware or plugin, fix the common cause and test representative routes from every role.',
				'pitfall_heading'  => 'Avoid a cosmetic scanner fix',
				'pitfall'          => 'Changing an error message, blocking one payload, or hiding a version string can make a scan quiet while leaving the vulnerable code path available. Confirm the server-side authorization or component update, test alternate methods and encodings, and check that caching or a reverse proxy does not serve an older response. Keep any emergency WAF rule only until the application fix is deployed and independently verified.',
			),
			'Windows team action' => array(
				'priority_heading' => 'Order the Windows rollout',
				'priority'         => 'Move domain controllers, federation and certificate services, exposed Windows servers, administrator workstations, and systems carrying reusable credentials ahead of ordinary endpoint rings. Then consider exploit evidence, affected build, restart need, recovery readiness, and service criticality. Unsupported systems require a separate containment or retirement decision because deployment success cannot be assumed. Keep identity-control changes and operating-system updates coordinated so a rushed rollout does not create an unmonitored authentication gap.',
				'pitfall_heading'  => 'Do not trust deployment status alone',
				'pitfall'          => 'A management console can report success while a device is awaiting restart, a service still uses an old binary, or the endpoint sensor is unhealthy. Verify a representative sample directly and investigate systems that have not checked in. Avoid closing broad remediation from a percentage without identifying the unpatched privileged and public systems hidden inside the remainder.',
			),
			'Linux operations action' => array(
				'priority_heading' => 'Rank hosts by service impact',
				'priority'         => 'Patch public services, bastions, orchestration nodes, authentication infrastructure, and hosts with privileged workloads before low-impact internal systems. Use the distribution advisory for the fixed package, but confirm which process or kernel is active on each host. For clustered services, sequence nodes around capacity and failover tests. For immutable images and containers, rebuild and redeploy from a fixed base rather than changing a short-lived instance that will be replaced by the vulnerable image.',
				'pitfall_heading'  => 'Avoid package-installed false confidence',
				'pitfall'          => 'Installing an update is not the same as loading it. Kernels await reboot, long-running processes retain old libraries, and containers may continue from stale layers. Verify the runtime and service state after the maintenance window. Also make sure rollback packages, snapshots, and golden images do not silently reintroduce the vulnerable version during recovery.',
			),
			'DevSecOps action' => array(
				'priority_heading' => 'Prioritize paths to production',
				'priority'         => 'Start with workflows triggered by pull requests or external input that can reach organization secrets, persistent runners, package publishing, cloud roles, or production deployment. A medium-severity dependency in an isolated test job may be less urgent than a workflow design that hands a write token to untrusted code. Review inherited reusable workflows and organization defaults because the dangerous permission may not be visible in the repository where the finding appears.',
				'pitfall_heading'  => 'Do not secure only the YAML',
				'pitfall'          => 'Pipeline policy can look correct while a shared runner retains another job workspace, an artifact can be replaced after scanning, or a secret appears in debugging output. Validate the runtime boundary, artifact handoff, and target environment. Remove obsolete tokens and caches after the change so old exposure does not survive a corrected workflow.',
			),
			'AI security action' => array(
				'priority_heading' => 'Rank AI workflows by capability',
				'priority'         => 'Prioritize agents and assistants that can read private repositories, customer records, email, tickets, or cloud resources, especially when they can also write, execute, send, approve, or purchase. The model name is less important than the combination of untrusted context and tool authority. Reduce service-account scope and connector reach before relying on prompt controls. A workflow with no consequential tools can tolerate a different review cadence from one that changes production.',
				'pitfall_heading'  => 'Avoid treating a refusal as enforcement',
				'pitfall'          => 'A model refusing one obvious prompt does not prove that encoded, indirect, retrieved, or multi-step instructions are contained. Enforcement should sit outside the model at data and tool boundaries. Test whether a malicious document, web page, code comment, or retrieved record can influence a sensitive call, and preserve the complete trace rather than only the final natural-language answer.',
			),
			'Cloud team action' => array(
				'priority_heading' => 'Rank cloud resources by reach and privilege',
				'priority'         => 'Start with public control planes, exposed storage, privileged identities, production clusters, security tooling, and resources holding regulated or customer data. Check whether a provider-side change is automatic or still requires tenant action. A finding in an unused region may be lower priority than the same issue behind a public load balancer. Use tags and billing ownership to find the responsible team, but validate ownership when old projects or acquisitions have incomplete metadata.',
				'pitfall_heading'  => 'Do not stop at the provider bulletin',
				'pitfall'          => 'A provider may patch infrastructure while customer images, policies, service accounts, or deployed components remain exposed. Conversely, teams can spend time patching a layer the provider already owns. Record the shared-responsibility boundary, verify final tenant state, and reconcile infrastructure code so the next deployment does not restore the weak configuration.',
			),
			'Network operations action' => array(
				'priority_heading' => 'Rank the reachable edge',
				'priority'         => 'Move public VPNs, firewalls, routers, gateways, DNS infrastructure, and shared management platforms ahead of isolated access switches. Consider whether the interface is internet-reachable, whether authentication can be bypassed, what trust zones the device connects, and whether configuration backup and replacement hardware are ready. Devices with unsupported firmware need isolation or replacement, not an indefinite exception based on low scanner confidence.',
				'pitfall_heading'  => 'Avoid configuration-only validation',
				'pitfall'          => 'A rule base or network diagram cannot prove the real path is blocked. NAT, temporary exceptions, alternate interfaces, IPv6, and out-of-band management may create unexpected reachability. Test from representative zones and inspect independent flow or DNS logs. After firmware changes, verify that logging, routing, high availability, and backup synchronization still work.',
			),
			'Put it into practice' => array(
				'priority_heading' => 'Keep the first version small',
				'priority'         => 'Begin with the highest-value systems or a limited set of urgent findings, then improve the workflow after people use it. A compact register with reliable owners and dates is more useful than a large form filled with unknown values. Decide which fields change a security decision and remove decorative reporting. Automate collection only after the team agrees on meanings, otherwise automation simply produces a larger inconsistent backlog.',
				'pitfall_heading'  => 'Avoid process without decisions',
				'pitfall'          => 'Meetings and forms can become a substitute for remediation when every item is discussed but no owner, deadline, or proof is recorded. End each review with explicit decisions and carry unresolved research as assigned work. Periodically remove stale fields and labels so the workflow remains fast enough for teams to maintain during real incidents.',
			),
			'Vulnerability team action' => array(
				'priority_heading' => 'Move beyond the severity score',
				'priority'         => 'Rank active exploitation, public reachability, authentication requirements, privilege gained, asset value, and recovery difficulty before using score as a tie-breaker. Match exact versions and enabled components so unaffected inventory does not crowd the emergency queue. For each confirmed asset, decide patch, mitigation, isolation, investigation, or documented non-applicability. Keep catalog deadlines and business maintenance windows visible together so urgency is not lost between security and operations.',
				'pitfall_heading'  => 'Avoid patching without compromise review',
				'pitfall'          => 'When exploitation is known and the system was reachable, updating software removes future exposure but does not answer whether the asset was already used. Review authentication, process, network, and configuration evidence for the relevant period. Rotate credentials or rebuild when trust cannot be restored confidently. Record this investigation separately from patch deployment so neither task is mistaken for the other.',
			),
			'Security operations action' => array(
				'priority_heading' => 'Choose signals that change action',
				'priority'         => 'Prioritize information that maps to owned technology, a reachable path, sensitive identity or data, or a credible campaign affecting your sector. Validate the signal before creating broad work, then assign the smallest response that changes exposure. This can be a detection query, a configuration review, a patch, an isolation decision, or a communication to a specific owner. Retire stale indicators and low-value alerts so analysts can see changes that matter.',
				'pitfall_heading'  => 'Avoid measuring queue activity as risk reduction',
				'pitfall'          => 'Ticket volume, alert count, and dashboard color can improve without changing attacker opportunity. Track validation time, containment, verified remediation, repeat causes, and aging high-impact exceptions. Make sure metrics do not reward teams for splitting one issue into many tickets or closing findings before the enforcing control has been tested.',
			),
		);

		return $sets[ $closing_heading ] ?? $sets['Security operations action'];
	}

	/**
	 * Return desk-specific editorial structure for seeded articles.
	 *
	 * @param string $text Article text used for desk detection.
	 * @return array<string,mixed>
	 */
	private static function brief_profile( string $text ): array {
		$text = strtolower( $text );

		if ( preg_match( '/wordpress|web application|api |security headers|login security/', $text ) ) {
			return array(
				'context_heading'    => 'Application attack surface',
				'context'            => 'Web risk lives at the point where routes, sessions, plugins, browser controls, and public requests meet. A useful review starts with the exact feature that is reachable, the identity required to use it, and the data or action exposed when authorization fails.',
				'scope'              => 'Map the issue to production URLs, API methods, active components, user roles, and deployment versions. Staging evidence is useful, but it cannot replace a check against the code and configuration serving real traffic.',
				'review_heading'     => 'Controls to inspect',
				'check_notes'        => array(
					'Test this control with an authenticated low-privilege account and an unauthenticated request where appropriate. Record the expected response, the actual response, and any proxy or application log evidence.',
					'Confirm the permanent fix in the application or component rather than relying only on a WAF rule. Temporary filtering should have an owner, an expiry date, and a test that proves the protected route still works.',
					'Review adjacent routes and roles after the first fix. Authorization and session mistakes often repeat across similar endpoints because they share middleware, helpers, or plugin code.',
				),
				'workflow_heading'   => 'From request to remediation',
				'workflow'           => 'Reproduce the safe failure mode, identify the responsible component, deploy the smallest compatible fix, and then repeat the test through the same public path. Preserve enough request detail for another engineer to verify the result.',
				'workflow_steps'     => array( 'Capture the affected route, role, component version, and response code.', 'Patch or reconfigure the application and clear only the caches required for validation.', 'Review web, authentication, and administrative logs for attempted abuse before closure.' ),
				'validation_heading' => 'Proof the web control works',
				'validation'         => 'A completed review includes the fixed component version, a denied unauthorized request, a successful authorized request, and confirmation that browser headers or session controls are present on the final response.',
				'reporting'          => 'Report public reachability, exposed data or action, affected users, and remaining exceptions in plain language. Avoid marking the issue complete merely because a scanner no longer recognizes the original response.',
				'closing_heading'    => 'Application team action',
			);
		}

		if ( preg_match( '/windows|active directory|powershell|microsoft|endpoint/', $text ) ) {
			return array(
				'context_heading'    => 'Windows estate exposure',
				'context'            => 'Windows security work crosses endpoint builds, server roles, identity, remote administration, and endpoint detection. Priority should reflect privilege and business role as well as the update severity shown in a vendor bulletin.',
				'scope'              => 'Build the review from supported operating-system versions, installed product builds, domain roles, exposure paths, and restart requirements. Domain controllers, public servers, and administrator workstations need a tighter window than ordinary user devices.',
				'review_heading'     => 'Windows checks that matter',
				'check_notes'        => array(
					'Verify the setting or update on a representative system from the affected deployment ring. Use build output, policy results, and endpoint telemetry instead of assuming that central deployment status proves activation.',
					'Review the identity path around this control, including local administrators, service accounts, delegated rights, and remote-management groups. Privilege can change the impact of an otherwise routine weakness.',
					'Plan for restart and recovery before broad rollout. A successful installation that leaves the old binary loaded or disables EDR coverage is not a completed security change.',
				),
				'workflow_heading'   => 'Deployment and rollback plan',
				'workflow'           => 'Start with an inventory-backed pilot, validate critical applications, expand through deployment rings, and keep every deferred host attached to an owner and maintenance date.',
				'workflow_steps'     => array( 'Confirm affected builds, server roles, and privileged endpoints.', 'Deploy to a controlled ring and verify restart, application, and EDR health.', 'Escalate failed or unreachable devices before the exception becomes stale.' ),
				'validation_heading' => 'Post-change evidence',
				'validation'         => 'Record the running build, installed update, last restart, policy result, and endpoint protection state. For identity changes, also verify authentication logs and the effective membership of privileged groups.',
				'reporting'          => 'Summaries should separate fully protected systems, systems awaiting restart, unsupported assets, and accepted exceptions. This gives operations a usable queue instead of one misleading completion percentage.',
				'closing_heading'    => 'Windows team action',
			);
		}

		if ( preg_match( '/linux|kernel|ubuntu|ssh|systemd|sudo/', $text ) ) {
			return array(
				'context_heading'    => 'Linux service and package context',
				'context'            => 'Linux security depends on what is actually running: kernel, packages, loaded libraries, services, modules, containers, and administrative access. Distribution guidance should be mapped to the exact release and package stream used by each workload.',
				'scope'              => 'Separate internet-facing hosts, privileged jump systems, orchestration nodes, and business-critical services from lower-impact fleets. Include reboot tolerance and clustered failover in the plan before applying changes.',
				'review_heading'     => 'Administrator review',
				'check_notes'        => array(
					'Run this check on the host or image that serves the workload. Package inventory from a management console may lag behind the running process, loaded library, or kernel that still carries the exposure.',
					'Preserve service logs and current configuration before changing the system. This gives the administrator a rollback reference and protects evidence if suspicious activity appears during review.',
					'Test the operational dependency after remediation, including service status, ports, storage, scheduled work, monitoring, and cluster membership. Security completion includes healthy production behavior.',
				),
				'workflow_heading'   => 'Maintenance-window runbook',
				'workflow'           => 'Group systems by role and redundancy, update one safe target first, validate the running state, and then continue through the fleet. Keep non-rebooted or pinned systems visible as exceptions.',
				'workflow_steps'     => array( 'Capture package, kernel, process, and service versions before change.', 'Apply the distribution-supported update with rollback and capacity prepared.', 'Verify logs, listening services, monitoring, and workload health after restart.' ),
				'validation_heading' => 'Running-state verification',
				'validation'         => 'Use uname, package-manager output, process maps, service state, and application checks to prove the fixed code is active. A downloaded package or pending reboot does not reduce the live exposure.',
				'reporting'          => 'Record host groups completed, hosts deferred, the reason for each exception, and the next maintenance date. Include any temporary network restriction or live-patching control that remains in place.',
				'closing_heading'    => 'Linux operations action',
			);
		}

		if ( preg_match( '/ci\\/cd|pipeline|container|infrastructure as code|runner|build job|deployment/', $text ) ) {
			return array(
				'context_heading'    => 'Software delivery trust boundary',
				'context'            => 'DevOps security follows the path from source changes and dependencies through runners, artifacts, registries, credentials, and production approval. The highest-risk weakness is often the one that lets untrusted input inherit a powerful automation identity.',
				'scope'              => 'Review the workflow file, trigger conditions, runner isolation, token permissions, dependency resolution, artifact integrity, and target environment together. A clean repository scan does not prove the delivery chain is safe.',
				'review_heading'     => 'Pipeline controls to review',
				'check_notes'        => array(
					'Inspect the effective permission at the exact pipeline stage where this control matters. Repository defaults, inherited organization policy, and reusable workflows can grant more access than the visible job suggests.',
					'Use a short-lived test credential and a non-production runner while validating changes. Build logs, caches, and artifacts should be checked for accidental secret or source disclosure afterward.',
					'Trace the artifact that reaches production back to reviewed source and an isolated build. Record hashes or attestations where the platform supports them so replacement and tampering are visible.',
				),
				'workflow_heading'   => 'Secure release workflow',
				'workflow'           => 'Reduce permissions first, isolate untrusted builds, pin or review dependencies, and require explicit approval before an artifact reaches a protected environment.',
				'workflow_steps'     => array( 'Map workflow triggers, identities, secrets, runners, and deployment targets.', 'Test changes in an isolated runner with production credentials unavailable.', 'Verify the promoted artifact and retain the logs needed to reconstruct the release.' ),
				'validation_heading' => 'Release evidence',
				'validation'         => 'A complete change shows the effective token scope, runner boundary, dependency result, artifact identity, and approval record. Also verify that old credentials, caches, and superseded artifacts were removed.',
				'reporting'          => 'Describe which release paths were protected and which repositories or environments remain outside the control. Assign each exception to the team that owns the affected workflow.',
				'closing_heading'    => 'DevSecOps action',
			);
		}

		if ( preg_match( '/\\bai\\b|model|prompt|llm|connector|retrieval/', $text ) ) {
			return array(
				'context_heading'    => 'Model, data, and tool boundaries',
				'context'            => 'AI application risk comes from the whole system around the model: prompts, retrieved data, connectors, tools, logs, human approvals, and external providers. Impact grows when untrusted context can influence an action with broad permissions.',
				'scope'              => 'Document what the workflow can read, what it can change, where its output goes, and which identity performs each tool call. Include model and dependency versions so a later review can reproduce the behavior.',
				'review_heading'     => 'AI controls to test',
				'check_notes'        => array(
					'Exercise this control with realistic untrusted input while sensitive tools use test data and minimum permissions. Log both the model decision and the enforcement result outside the model context.',
					'Review every connector and retrieval source involved in the workflow. Access inherited from a user, service account, or shared index can expose information the prompt alone does not reveal.',
					'Test failure and refusal behavior as well as the happy path. A guardrail must still hold when content is encoded, indirect, retrieved from a document, or combined across several steps.',
				),
				'workflow_heading'   => 'Constrain before expanding',
				'workflow'           => 'Begin with read-only access and narrow data, require approval for consequential actions, and expand only after logs show that policy decisions and tool calls can be audited reliably.',
				'workflow_steps'     => array( 'Inventory models, prompts, data stores, connectors, tools, and service identities.', 'Run abuse cases in an isolated environment with secrets and production writes blocked.', 'Review logs for data exposure, unsafe tool use, policy bypass, and unexplained model behavior.' ),
				'validation_heading' => 'AI assurance evidence',
				'validation'         => 'Keep the test prompt or document, model and policy version, retrieved sources, tool-call record, enforcement result, and reviewer decision. This separates a repeatable control test from a one-time demo.',
				'reporting'          => 'State the permitted capability and remaining limitation plainly. Avoid claiming that prompt filtering alone makes an agent safe when connectors or service identities still provide broad access.',
				'closing_heading'    => 'AI security action',
			);
		}

		if ( preg_match( '/cloud|iam|storage bucket|account|project|security group/', $text ) ) {
			return array(
				'context_heading'    => 'Cloud ownership and exposure',
				'context'            => 'Cloud findings sit across provider-managed services and tenant-managed identity, networking, data, workloads, and logging. The response must establish which side owns the fix and whether the affected resource is public, privileged, or linked to sensitive data.',
				'scope'              => 'Map the issue to accounts, projects, subscriptions, regions, resource IDs, service versions, and workload owners. Check infrastructure code and deployed state because manual drift may be the real source of exposure.',
				'review_heading'     => 'Tenant controls to inspect',
				'check_notes'        => array(
					'Query the cloud control plane for this condition across every production account and region. Samples and console screenshots can miss resources created through automation or old projects.',
					'Review the identity and network path together. A private resource with an overpowered role, or a restricted role attached to a public service, can still create serious exposure.',
					'Confirm that audit records are centralized outside the workload account and retained long enough for investigation. The fix should not disable the telemetry used to prove it.',
				),
				'workflow_heading'   => 'Cloud remediation sequence',
				'workflow'           => 'Contain public or privileged exposure, apply the provider or tenant fix, reconcile infrastructure code, and verify the final resource state through an independent query.',
				'workflow_steps'     => array( 'Identify affected resource IDs, owners, regions, identities, and public paths.', 'Apply the change through reviewed infrastructure code where possible.', 'Re-scan deployed state and inspect control-plane logs for prior abuse or drift.' ),
				'validation_heading' => 'Control-plane proof',
				'validation'         => 'Capture the final policy, resource configuration, software or image version, network reachability, and audit event. Provider status alone is not proof that tenant configuration is protected.',
				'reporting'          => 'Separate resources fixed automatically, resources changed by the tenant, and resources awaiting an owner. Include costs or availability constraints when they explain a temporary exception.',
				'closing_heading'    => 'Cloud team action',
			);
		}

		if ( preg_match( '/network|vpn|dns|segmentation|router|firewall/', $text ) ) {
			return array(
				'context_heading'    => 'Network path and management plane',
				'context'            => 'Network security is determined by reachable paths, device firmware, management access, identity, configuration, and independent telemetry. Internet-edge and administrative interfaces deserve priority because one device can bridge several trust zones.',
				'scope'              => 'Inventory the exact model, firmware, public address, management source networks, authentication method, and zones connected to the device. Include backup and out-of-band access before scheduling disruptive work.',
				'review_heading'     => 'Edge and traffic checks',
				'check_notes'        => array(
					'Validate this condition from both the device configuration and an external connectivity test. Documentation or diagrams may not reflect temporary rules, NAT paths, or shadow administration services.',
					'Preserve configuration and logs before firmware or policy changes. Rotate management credentials when compromise cannot be excluded, especially for public or shared administration paths.',
					'Compare traffic before and after the change for new denies, unexpected routes, DNS anomalies, or broken dependencies. A secure rule that silently disrupts recovery or monitoring needs correction.',
				),
				'workflow_heading'   => 'Contain, update, and observe',
				'workflow'           => 'Restrict management exposure first, back up the configuration, deploy supported firmware or policy, and observe traffic from an independent logging system.',
				'workflow_steps'     => array( 'Record model, firmware, interfaces, routes, rules, zones, and administrators.', 'Apply temporary access restriction before disruptive remediation where risk is active.', 'Verify segmentation, management reachability, traffic, and logs after the change.' ),
				'validation_heading' => 'Network evidence',
				'validation'         => 'Keep the running firmware, configuration diff, authorized management path, connectivity test, and centralized log event. Test from low-trust and administrative zones rather than trusting a single device view.',
				'reporting'          => 'List protected devices and remaining unsupported or unreachable appliances separately. Every exception needs a replacement, isolation, or maintenance decision.',
				'closing_heading'    => 'Network operations action',
			);
		}

		if ( preg_match( '/weekly vulnerability|temporary security exception|asset exposure register|standup|document/', $text ) ) {
			return array(
				'context_heading'    => 'Goal and expected outcome',
				'context'            => 'This workflow is designed to turn scattered security information into a small, repeatable decision record. The useful output is not another dashboard; it is a clear owner, affected scope, action, deadline, and proof requirement.',
				'scope'              => 'Choose one manageable group of assets or findings for the first pass. Define the fields and decisions before collecting data so the process stays usable when the backlog grows.',
				'review_heading'     => 'Walk through the process',
				'check_notes'        => array(
					'Complete this step with a real asset or finding and write the result in the shared record. Avoid placeholder values that hide missing ownership or unresolved scope.',
					'Keep the decision language consistent so another reviewer can compare entries without reopening every source. Link evidence instead of pasting sensitive logs or credentials into the record.',
					'Set a review time while completing the step. Temporary decisions become unmanaged risk when no one knows when to revisit them.',
				),
				'workflow_heading'   => 'Make the workflow repeatable',
				'workflow'           => 'Use the same compact fields, assign one facilitator, time-box research, and move unresolved questions into owned follow-up rather than extending the meeting indefinitely.',
				'workflow_steps'     => array( 'Define scope, source of truth, required fields, and decision labels.', 'Run one real example from intake through validation.', 'Review the result with the asset owner and adjust only fields that improve action.' ),
				'validation_heading' => 'Quality check',
				'validation'         => 'A new team member should be able to read the record and understand what was reviewed, why the decision was made, what evidence supports it, and when the next action happens.',
				'reporting'          => 'Track overdue owners, expired exceptions, missing evidence, and repeated causes. These measures show whether the workflow reduces risk without turning the process into a reporting exercise.',
				'closing_heading'    => 'Put it into practice',
			);
		}

		if ( preg_match( '/cve|zero-day|exploit|vulnerabilit/', $text ) ) {
			return array(
				'context_heading'    => 'Exploit-led vulnerability priority',
				'context'            => 'Vulnerability priority should combine exploitation evidence, reachable attack paths, privilege, business impact, affected versions, and the availability of a reliable fix. A score is useful context, but it cannot describe your exposure on its own.',
				'scope'              => 'Match the advisory to internet-facing and administrative assets first. Confirm product and version with the vendor record, then separate affected systems from scanner matches that are unreachable, disabled, or already fixed.',
				'review_heading'     => 'Triage decisions',
				'check_notes'        => array(
					'Record the evidence used for this decision: asset ID, version, reachability, privilege, exploit status, and owner. This makes urgent work defensible and keeps false positives out of the emergency queue.',
					'Where a patch is not immediately possible, choose a temporary control that blocks the vulnerable path and can be tested. Give that control an expiry date tied to permanent remediation.',
					'Review detection evidence while patching. Exploited systems may need containment, credential rotation, or incident response even after the vulnerable software is updated.',
				),
				'workflow_heading'   => 'Patch queue workflow',
				'workflow'           => 'Start with exploited and reachable systems, contain where necessary, deploy the supported fix, and validate both the running version and business service before closure.',
				'workflow_steps'     => array( 'Match vendor-affected versions to owned and reachable assets.', 'Assign patch, mitigation, or not-affected decisions with deadlines.', 'Verify the fixed version and investigate exposure that existed before remediation.' ),
				'validation_heading' => 'Remediation proof',
				'validation'         => 'Keep version output, deployment or configuration evidence, service health, and the detection review result. A closed scanner finding without a running-state check is not sufficient proof.',
				'reporting'          => 'Report the number of truly affected assets, those remediated, those contained, and those deferred. Include the reason and next decision date for every remaining exception.',
				'closing_heading'    => 'Vulnerability team action',
			);
		}

		return array(
			'context_heading'    => 'Security operations context',
			'context'            => 'Security operations improves when threat information is connected to real assets, identities, owners, and response decisions. Volume alone is not a useful measure; the goal is to identify the few signals that can change exposure or defensive action.',
			'scope'              => 'Define the systems, users, data, and business service covered by the review. Separate confirmed evidence from assumptions so teams can move quickly without presenting speculation as fact.',
			'review_heading'     => 'Operational checks',
			'check_notes'        => array(
				'Assign this check to the team that owns the affected control and agree on the evidence required for closure. Shared awareness without a named action does not reduce risk.',
				'Compare the result with authentication, endpoint, network, and application telemetry where relevant. Independent evidence helps distinguish a real event from an inventory or alerting error.',
				'Document the decision and next review time. This keeps lower-priority work visible without allowing it to compete indefinitely with confirmed, high-impact exposure.',
			),
			'workflow_heading'   => 'Turn signals into owned work',
			'workflow'           => 'Validate the signal, establish affected scope, choose containment or remediation, and confirm the result with evidence from the system that enforces the control.',
			'workflow_steps'     => array( 'Identify the affected asset, identity, data, and business owner.', 'Choose the smallest action that materially reduces the confirmed risk.', 'Validate completion and record what remains uncertain or deferred.' ),
			'validation_heading' => 'Evidence and communication',
			'validation'         => 'Keep the alert or source, investigation notes, control change, technical validation, owner, and deadline. Reports should explain what changed and what decision is required rather than repeating raw alerts.',
			'reporting'          => 'A concise update should state impact, scope, completed action, remaining risk, and the next review time. This supports both engineering follow-through and accurate leadership communication.',
			'closing_heading'    => 'Security operations action',
		);
	}

	/**
	 * Create demo pages.
	 */
	private static function create_pages(): void {
		$image_file = file_exists( get_stylesheet_directory() . '/assets/images/hero-shield.webp' ) ? 'hero-shield.webp' : 'hero-shield.png';
		$image      = esc_url( get_stylesheet_directory_uri() . '/assets/images/' . $image_file );
		$pages = array(
			'about'                => array(
				'title'   => 'About InfoSecNexus',
				'content' => self::page_content( 'about', $image ),
			),
			'contact'              => array(
				'title'   => 'Contact InfoSecNexus',
				'content' => self::page_content( 'contact', $image ),
			),
			'privacy-policy'       => array(
				'title'   => 'Privacy Policy',
				'content' => self::page_content( 'privacy', $image ),
			),
			'terms-and-conditions' => array(
				'title'   => 'Terms and Conditions',
				'content' => self::page_content( 'terms', $image ),
			),
			'disclaimer'           => array(
				'title'   => 'Disclaimer',
				'content' => self::page_content( 'disclaimer', $image ),
			),
		);

		foreach ( $pages as $slug => $page ) {
			self::upsert_post(
				'page',
				$slug,
				array(
					'post_title'   => $page['title'],
					'post_content' => $page['content'],
					'post_status'  => 'publish',
					'meta_input'   => array(
						'_infosecnexus_hide_title' => '1',
					),
				)
			);
		}
	}

	/**
	 * Rich page body for demo pages.
	 *
	 * @param string $key Page key.
	 * @param string $image Hero image URL.
	 * @return string
	 */
	private static function page_content( string $key, string $image ): string {
		$email      = 'yashpatel@infosecnexus.com';
		$email_link = '<a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>';
		$pages = array(
			'about'      => array(
				'<section class="isnx-page-hero isnx-page-hero--about"><div><p class="isnx-page-kicker">About InfoSecNexus</p><h2>Cybersecurity intelligence for people who build, run, and defend technology.</h2><p>InfoSecNexus turns fast-moving security news into clear operational guidance for engineers, admins, security teams, and technology leaders.</p><p>We focus on the details that help teams act: what changed, who is affected, why it matters, and what to do next.</p></div><img src="' . $image . '" alt=""></section>',
				'<section class="isnx-stat-strip"><div><strong>Daily</strong><span>signal from security, Linux, cloud, and AI coverage</span></div><div><strong>Action-first</strong><span>patch notes, hardening steps, and review checklists</span></div><div><strong>Independent</strong><span>clear explanations without vendor noise</span></div></section>',
				'<section class="isnx-section"><div class="isnx-section__intro"><p class="isnx-page-kicker">What we cover</p><h2>Built for practical security teams</h2><p>Our coverage is designed for people who need to understand risk quickly and move work through real environments.</p></div><div class="isnx-card-grid"><div class="isnx-card"><h3>Cyber Security</h3><p>Threat intelligence, attack analysis, defensive engineering, incident lessons, and security operations guidance.</p></div><div class="isnx-card"><h3>Linux & DevOps</h3><p>Linux administration, kernel security, containers, automation, CI/CD, and cloud-native operational risk.</p></div><div class="isnx-card"><h3>AI Security</h3><p>Model supply-chain risk, data privacy, AI tooling governance, prompt abuse, and secure adoption patterns.</p></div><div class="isnx-card"><h3>Critical CVEs</h3><p>High-priority vulnerabilities with exploitation context, affected systems, detection ideas, and mitigation plans.</p></div></div></section>',
				'<section class="isnx-split"><div><p class="isnx-page-kicker">Our mission</p><h2>Make complex security developments practical, accurate, and actionable.</h2><p>Security information can move faster than the teams responsible for responding to it. InfoSecNexus exists to reduce that gap. We translate alerts, advisories, and research into language that supports confident decisions.</p></div><div class="isnx-check-list"><h3>How we work</h3><ul><li>Verify important details before turning them into guidance.</li><li>Separate confirmed facts from reasonable operational assumptions.</li><li>Prioritize clear next steps over dramatic language.</li><li>Keep engineers and administrators in mind from the first paragraph.</li></ul></div></section>',
			),
			'contact'    => array(
				'<section class="isnx-page-hero isnx-page-hero--contact"><div><p class="isnx-page-kicker">Contact InfoSecNexus</p><h2>Send corrections, tips, questions, and collaboration requests.</h2><p>We read every serious message and route it to the right editorial or technical review path. For sensitive reports, keep secrets, passwords, private keys, and exploit code out of the first message.</p></div><img src="' . $image . '" alt=""></section>',
				'<section class="isnx-contact-grid"><div class="isnx-contact-card"><h3>Editorial & Corrections</h3><p>Report factual errors, request corrections, or suggest improvements to published content.</p><p>' . $email_link . '</p></div><div class="isnx-contact-card"><h3>Security Tips</h3><p>Share vulnerability leads, exploitation activity, suspicious campaigns, or defensive lessons from the field.</p><p>' . $email_link . '</p></div><div class="isnx-contact-card"><h3>General Enquiries</h3><p>Send partnership requests, media questions, guest ideas, and general notes for the InfoSecNexus team.</p><p>' . $email_link . '</p></div></section>',
				'<section class="isnx-contact-panel" id="contact-form"><div><h2>Write to us</h2><p>Tell us what needs review, what changed, and how we can reach you. Clear context helps us route corrections, security tips, and collaboration requests faster.</p><div class="isnx-alert-note"><strong>Before you send:</strong> do not include passwords, private keys, tokens, or sensitive credentials.</div></div>' . "\n[infosecnexus_contact_form]\n" . '</section>',
				'<section class="isnx-response-band"><div><strong>Typical response time</strong><span>We usually reply within two business days. Urgent corrections and security tips are reviewed first.</span></div></section>',
			),
			'privacy'    => array(
				'<section class="isnx-page-hero isnx-page-hero--legal"><div><p class="isnx-page-kicker">Privacy Policy</p><h2>How InfoSecNexus collects, uses, and protects information.</h2><p>Last updated: July 2026. This policy explains the practical privacy expectations for visitors, subscribers, and people who contact InfoSecNexus.</p></div></section>',
				'<section class="isnx-legal-grid"><div class="isnx-legal-card"><h3>Privacy at a glance</h3><p>We aim to collect only the information needed to operate, improve, secure, and communicate about the website.</p></div><div class="isnx-legal-card"><h3>Minimal data</h3><p>We do not want unnecessary sensitive information. Contact forms and email should not include credentials or private keys.</p></div><div class="isnx-legal-card"><h3>Your choices</h3><p>You may request updates, deletion, or clarification about information you have provided to us.</p></div></section>',
				'<section class="isnx-legal-body"><h2>Information We Collect</h2><p>We may collect information you provide directly, such as your name, email address, message subject, and message content when you contact us or subscribe to updates. We may also receive basic technical information such as browser type, device information, pages visited, and approximate location derived from standard web logs or analytics tools.</p><h2>How We Use Information</h2><p>We use information to operate the website, respond to messages, publish and improve content, measure site performance, prevent abuse, and maintain security. We do not sell personal information.</p><h2>Cookies and Analytics</h2><p>The website may use essential cookies for normal operation and optional analytics or preference storage. Analytics should be configured to collect the least amount of data needed to understand site reliability and content performance.</p><h2>Third-Party Services</h2><p>InfoSecNexus may rely on hosting, email, analytics, security, and content delivery providers. Those providers may process limited information according to their own terms and privacy practices.</p><h2>Data Retention</h2><p>We keep information only as long as needed for the purposes described here, unless a longer period is required for legal, security, or operational reasons.</p><h2>Contact</h2><p>Privacy questions can be sent to ' . $email_link . '.</p></section>',
			),
			'terms'      => array(
				'<section class="isnx-page-hero isnx-page-hero--legal"><div><p class="isnx-page-kicker">Terms and Conditions</p><h2>The terms governing access to and use of InfoSecNexus.</h2><p>Last updated: July 2026. By accessing this website, you agree to use it responsibly and in accordance with these terms.</p></div></section>',
				'<section class="isnx-legal-body"><h2>Acceptance of Terms</h2><p>By using InfoSecNexus, you agree to these Terms and Conditions. If you do not agree, you should stop using the website.</p><h2>Use of the Website</h2><p>You may use the website for lawful informational and educational purposes. You may not use the website to disrupt services, attempt unauthorized access, distribute malicious content, or interfere with other users.</p><h2>Content and Intellectual Property</h2><p>Articles, graphics, logos, page designs, and other materials on InfoSecNexus are owned by InfoSecNexus or used with permission unless otherwise stated. You may reference our content with proper attribution, but you may not reproduce substantial portions without permission.</p><h2>No Guarantee of Availability</h2><p>We work to keep the website available and accurate, but access may be interrupted for maintenance, security, hosting, or other operational reasons.</p><h2>Third-Party Links</h2><p>InfoSecNexus may link to third-party websites, advisories, tools, or documentation. We are not responsible for the content, security, privacy practices, or availability of third-party sites.</p><h2>Changes to These Terms</h2><p>We may update these terms from time to time. Continued use of the website after updates means you accept the revised terms.</p><h2>Contact</h2><p>Questions about these terms can be sent to ' . $email_link . '.</p></section>',
			),
			'disclaimer' => array(
				'<section class="isnx-page-hero isnx-page-hero--legal"><div><p class="isnx-page-kicker">Disclaimer</p><h2>Important information about security content published on InfoSecNexus.</h2><p>Last updated: July 2026. This disclaimer explains how to interpret the educational and operational guidance on this website.</p></div></section>',
				'<section class="isnx-legal-body"><h2>General Information</h2><p>InfoSecNexus publishes cybersecurity, infrastructure, open-source, and technology content for general informational and educational purposes. Content may summarize public advisories, research, vendor guidance, and operational best practices.</p><h2>No Professional Advice</h2><p>The content does not constitute legal, financial, compliance, technical, or professional advice for your specific environment. Always consult qualified professionals and trusted vendors before making high-impact decisions.</p><h2>Accuracy of Information</h2><p>We work to provide accurate and timely information, but security developments change quickly. Vulnerability status, affected versions, exploitation details, and mitigations may change after publication.</p><h2>Security Research</h2><p>Security research, testing, scanning, and exploit validation should be performed only in systems and environments where you have clear authorization. Do not use information from this website for unauthorized activity.</p><h2>External Links</h2><p>External links are provided for convenience and reference. InfoSecNexus is not responsible for third-party content, changes, privacy practices, or availability.</p><h2>Limitation of Liability</h2><p>Use of information from this website is at your own risk. InfoSecNexus and its authors are not liable for damages arising from use of, or inability to use, the information published here.</p><h2>Contact</h2><p>Questions about this disclaimer can be sent to ' . $email_link . '.</p></section>',
			),
		);

		return implode( '', $pages[ $key ] ?? array() );
	}

	/**
	 * Create a post/page if missing; update only importer-created content.
	 *
	 * @param string              $type Post type.
	 * @param string              $slug Slug.
	 * @param array<string,mixed> $data Post data.
	 * @return int
	 */
	private static function upsert_post( string $type, string $slug, array $data ): int {
		$existing = get_page_by_path( $slug, OBJECT, $type );
		$data['post_name'] = $slug;
		$data['post_type'] = $type;

		if ( $existing ) {
			$can_update = (bool) get_post_meta( $existing->ID, '_infosecnexus_demo_content', true );
			if ( ! $can_update && self::can_overwrite_slug( $type, $slug ) ) {
				$can_update = true;
			}
			if ( $can_update ) {
				$data['ID'] = $existing->ID;
				wp_update_post( wp_slash( $data ) );
			}
			update_post_meta( $existing->ID, '_infosecnexus_demo_content', '1' );
			return (int) $existing->ID;
		}

		$id = wp_insert_post( wp_slash( $data ), true );
		if ( ! is_wp_error( $id ) ) {
			update_post_meta( (int) $id, '_infosecnexus_demo_content', '1' );
			return (int) $id;
		}

		return 0;
	}

	/**
	 * Decide which shipped demo slugs are safe to refresh.
	 *
	 * @param string $type Post type.
	 * @param string $slug Slug.
	 * @return bool
	 */
	private static function can_overwrite_slug( string $type, string $slug ): bool {
		$slugs = array(
			'page' => array(
				'about',
				'about-infosecnexus',
				'contact',
				'privacy-policy',
				'terms-and-conditions',
				'disclaimer',
			),
			'post' => array(
				'januscape-kvm-vulnerability-immediate-patching',
				'cve-2026-57156-freerdp-integer-overflow',
				'ghostlock-kernel-fixes-released',
				'microsoft-products-reach-end-of-support',
				'secure-your-cloud-five-misconfigurations',
				'ai-model-supply-chain-risks-on-the-rise',
				'kvm-security-hardening-best-practices-2026',
				'build-a-patch-review-runbook',
			),
		);

		return in_array( $slug, $slugs[ $type ] ?? array(), true );
	}

	/**
	 * Create menus and menu items.
	 *
	 * @param array<string,int> $categories Category IDs by slug.
	 */
	private static function create_menus( array $categories ): void {
		$primary = self::menu_id( 'Primary' );
		$footer  = self::menu_id( 'Footer' );
		$legal   = self::menu_id( 'Legal' );

		self::assign_location( $primary, 'primary' );
		self::assign_location( $footer, 'footer' );
		self::assign_location( $legal, 'legal' );
		self::remove_menu_items_by_title( $primary, array( 'Home', 'Topics', 'Blogs', 'Cybersecurity', 'Cyber Security', 'Critical CVEs', 'Linux Admin', 'Linux & Kernel', 'Linux & DevOps', 'DevOps', 'AI News', 'AI Security', 'Tutorials', 'Cloud Security', 'Web Security', 'Windows Security', 'Network Security', 'Major Releases', 'Sample Page', 'RSS', 'About', 'About InfoSecNexus', 'Contact', 'Contact InfoSecNexus' ) );
		self::remove_menu_items_by_title( $footer, array( 'Home', 'About', 'About InfoSecNexus', 'Contact', 'Contact InfoSecNexus', 'Privacy Policy', 'Terms and Conditions', 'Disclaimer', 'Back to top', 'RSS' ) );
		self::remove_menu_items_by_title( $legal, array( 'Privacy Policy', 'Terms and Conditions', 'Disclaimer', 'Back to top', 'RSS' ) );

		self::add_custom_menu_item_once( $primary, 'Home', home_url( '/' ) );
		$topics_parent = self::add_custom_menu_item_once( $primary, 'Blogs', \InfoSecNexus\Theme\Header_Builder\category_url( 'cybersecurity' ) );
		foreach ( array( 'cybersecurity', 'critical-cves', 'linux-administration', 'devops', 'artificial-intelligence', 'tutorials', 'cloud-security', 'web-security', 'windows-security', 'network-security' ) as $slug ) {
			if ( isset( $categories[ $slug ] ) ) {
				self::add_term_menu_item_once( $primary, $categories[ $slug ], $topics_parent );
			}
		}

		foreach ( array( 'about' => 'About', 'contact' => 'Contact' ) as $slug => $label ) {
			$page = get_page_by_path( $slug );
			if ( $page ) {
				self::add_custom_menu_item_once( $primary, $label, get_permalink( $page ) );
			}
		}

		foreach ( array( 'privacy-policy', 'terms-and-conditions', 'disclaimer' ) as $slug ) {
			$page = get_page_by_path( $slug );
			if ( $page ) {
				self::add_post_menu_item_once( $legal, (int) $page->ID );
			}
		}
	}

	/**
	 * Get or create menu ID.
	 *
	 * @param string $name Menu name.
	 * @return int
	 */
	private static function menu_id( string $name ): int {
		$menu = wp_get_nav_menu_object( $name );
		if ( $menu ) {
			return (int) $menu->term_id;
		}
		return (int) wp_create_nav_menu( $name );
	}

	/**
	 * Assign menu location.
	 *
	 * @param int    $menu_id Menu ID.
	 * @param string $location Theme location.
	 */
	private static function assign_location( int $menu_id, string $location ): void {
		$locations = get_theme_mod( 'nav_menu_locations', array() );
		$locations[ $location ] = $menu_id;
		set_theme_mod( 'nav_menu_locations', $locations );
	}

	/**
	 * Add custom menu item if missing.
	 */
	private static function add_custom_menu_item_once( int $menu_id, string $title, string $url, int $parent_id = 0 ): int {
		$existing = self::menu_item_id_by_title( $menu_id, $title );
		if ( $existing ) {
			return $existing;
		}
		return (int) wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-title'  => $title,
				'menu-item-url'    => $url,
				'menu-item-status' => 'publish',
				'menu-item-type'   => 'custom',
				'menu-item-parent-id' => $parent_id,
			)
		);
	}

	/**
	 * Add term menu item if missing.
	 */
	private static function add_term_menu_item_once( int $menu_id, int $term_id, int $parent_id = 0 ): void {
		$term = get_term( $term_id, 'category' );
		if ( ! $term || is_wp_error( $term ) || self::menu_has_title( $menu_id, $term->name ) ) {
			return;
		}
		wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-title'     => $term->name,
				'menu-item-object-id' => $term_id,
				'menu-item-object'    => 'category',
				'menu-item-type'      => 'taxonomy',
				'menu-item-status'    => 'publish',
				'menu-item-parent-id' => $parent_id,
			)
		);
	}

	/**
	 * Add post menu item if missing.
	 */
	private static function add_post_menu_item_once( int $menu_id, int $post_id ): void {
		$title = get_the_title( $post_id );
		if ( self::menu_has_title( $menu_id, $title ) ) {
			return;
		}
		wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-title'     => $title,
				'menu-item-object-id' => $post_id,
				'menu-item-object'    => get_post_type( $post_id ),
				'menu-item-type'      => 'post_type',
				'menu-item-status'    => 'publish',
			)
		);
	}

	/**
	 * Check menu item by title.
	 */
	private static function menu_has_title( int $menu_id, string $title ): bool {
		return (bool) self::menu_item_id_by_title( $menu_id, $title );
	}

	/**
	 * Find a menu item by title.
	 */
	private static function menu_item_id_by_title( int $menu_id, string $title ): int {
		$items = wp_get_nav_menu_items( $menu_id );
		if ( empty( $items ) ) {
			return 0;
		}
		foreach ( $items as $item ) {
			if ( $item->title === $title ) {
				return (int) $item->ID;
			}
		}
		return 0;
	}

	/**
	 * Remove known stale menu items so refreshed demo links point to the right pages.
	 *
	 * @param int      $menu_id Menu ID.
	 * @param string[] $titles Item titles.
	 */
	private static function remove_menu_items_by_title( int $menu_id, array $titles ): void {
		$items = wp_get_nav_menu_items( $menu_id );
		if ( empty( $items ) ) {
			return;
		}

		foreach ( $items as $item ) {
			if ( in_array( $item->title, $titles, true ) ) {
				wp_delete_post( (int) $item->ID, true );
			}
		}
	}

	/**
	 * Set recommended options.
	 */
	private static function set_options(): void {
		$options = options();
		$options['modules'] = array_merge( Plugin::default_modules(), is_array( $options['modules'] ?? null ) ? $options['modules'] : array() );
		$options['modules']['cookie_consent'] = false;
		$options['daily_content_enabled'] = true;
		$options['newsletter_heading'] = __( 'Get the Daily Cyber Brief', 'infosecnexus' );
		$options['newsletter_intro'] = __( 'Top stories, critical alerts, and expert analysis delivered to your inbox every morning.', 'infosecnexus' );
		update_option( OPTION_KEY, $options, false );

		set_theme_mod( 'default_color_mode', 'light' );
		set_theme_mod( 'alert_enabled', false );
		set_theme_mod( 'home_hero_badge', 'Security Brief' );
		set_theme_mod( 'home_hero_title', 'Security Operations Brief: Patch Risk, Cloud Exposure & AI Controls' );
		set_theme_mod( 'home_hero_excerpt', 'Short, practical cybersecurity briefings for teams that need clear next steps.' );
		set_theme_mod( 'header_button_label', 'Daily Cyber Brief' );
		set_theme_mod( 'header_button_url', home_url( '/#daily-cyber-brief' ) );
		set_theme_mod( 'alert_link_url', home_url( '/category/critical-cves/' ) );
		set_theme_mod( 'home_hero_button_url', home_url( '/category/critical-cves/' ) );
		set_theme_mod( 'footer_top_elements', 'logo' );
		set_theme_mod( 'footer_main_elements', '' );
		set_theme_mod( 'footer_bottom_elements', 'copyright,spacer,legal_navigation' );
		set_theme_mod( 'copyright', '© {year} InfoSecNexus. All rights reserved.' );
	}

	/**
	 * Refresh stale demo copy without resetting user-selected design settings.
	 */
	private static function refresh_content_theme_mods(): void {
		$legacy_hero_titles = array(
			'',
			'July Patch Shockwave: Enterprise EOL & Active Zero-Days',
		);

		if ( in_array( (string) get_theme_mod( 'home_hero_title', '' ), $legacy_hero_titles, true ) ) {
			set_theme_mod( 'home_hero_title', 'Security Operations Brief: Patch Risk, Cloud Exposure & AI Controls' );
		}

		$legacy_hero_excerpts = array(
			'',
			'Critical updates, end-of-life notices, and zero-day activity shaping risk this month.',
		);

		if ( in_array( (string) get_theme_mod( 'home_hero_excerpt', '' ), $legacy_hero_excerpts, true ) ) {
			set_theme_mod( 'home_hero_excerpt', 'Short, practical cybersecurity briefings for teams that need clear next steps.' );
		}

		$legacy_copyrights = array(
			'',
			'Copyright {year} InfoSecNexus. All rights reserved.',
			'InfoSecNexus {year}. Cybersecurity insights, daily.',
		);

		if ( in_array( (string) get_theme_mod( 'copyright', '' ), $legacy_copyrights, true ) ) {
			set_theme_mod( 'copyright', '© {year} InfoSecNexus. All rights reserved.' );
		}

		set_theme_mod( 'alert_enabled', false );
		set_theme_mod( 'home_hero_badge', 'Security Brief' );
		set_theme_mod( 'home_hero_button_url', home_url( '/category/critical-cves/' ) );
		set_theme_mod( 'footer_top_elements', 'logo' );
		set_theme_mod( 'footer_main_elements', '' );
		set_theme_mod( 'footer_bottom_elements', 'copyright,spacer,legal_navigation' );
	}
}
