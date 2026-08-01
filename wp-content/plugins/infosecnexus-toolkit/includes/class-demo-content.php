<?php
/**
 * Demo content and site setup.
 *
 * @package InfoSecNexusToolkit
 */

declare(strict_types=1);

namespace InfoSecNexus\Toolkit;

/**
 * Demo content importer.
 */
final class Demo_Content {
	private const SEEDED_OPTION = 'infosecnexus_demo_seeded_version';

	/**
	 * Register hooks.
	 */
	public static function boot(): void {
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'handle_import' ) );
		add_action( 'admin_init', array( __CLASS__, 'maybe_auto_seed' ) );
	}

	/**
	 * Add setup page.
	 */
	public static function admin_menu(): void {
		add_theme_page(
			__( 'InfoSecNexus Setup', 'infosecnexus-toolkit' ),
			__( 'InfoSecNexus Setup', 'infosecnexus-toolkit' ),
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
		$reset_url  = wp_nonce_url( admin_url( 'themes.php?page=infosecnexus-setup&infosecnexus_reset_demo_settings=1' ), 'infosecnexus_reset_demo_settings' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'InfoSecNexus Setup', 'infosecnexus-toolkit' ); ?></h1>
			<?php if ( ! empty( $_GET['infosecnexus_imported'] ) ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Demo content repaired. Your existing theme and toolkit settings were not reset.', 'infosecnexus-toolkit' ); ?></p></div>
			<?php endif; ?>
			<?php if ( ! empty( $_GET['infosecnexus_settings_reset'] ) ) : ?>
				<div class="notice notice-warning is-dismissible"><p><?php esc_html_e( 'Recommended demo settings were reset.', 'infosecnexus-toolkit' ); ?></p></div>
			<?php endif; ?>
			<p><?php esc_html_e( 'Create or repair the missing categories, demo posts, pages, and menus for the newsroom demo.', 'infosecnexus-toolkit' ); ?></p>
			<p><a class="button button-primary" href="<?php echo esc_url( $import_url ); ?>"><?php esc_html_e( 'Import / Repair Demo Content', 'infosecnexus-toolkit' ); ?></a></p>
			<p><?php esc_html_e( 'This content repair is non-destructive for your Customizer and Toolkit settings.', 'infosecnexus-toolkit' ); ?></p>
			<hr>
			<h2><?php esc_html_e( 'Reset Settings', 'infosecnexus-toolkit' ); ?></h2>
			<p><?php esc_html_e( 'Use this only when you intentionally want to restore the recommended InfoSecNexus theme and toolkit settings.', 'infosecnexus-toolkit' ); ?></p>
			<p><a class="button" href="<?php echo esc_url( $reset_url ); ?>"><?php esc_html_e( 'Reset Recommended Settings', 'infosecnexus-toolkit' ); ?></a></p>
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
	 * Run importer.
	 *
	 * @param bool $reset_settings Whether to reset recommended theme/toolkit settings.
	 */
	public static function run( bool $reset_settings = false ): void {
		self::cleanup_starter_content();
		$categories = self::create_categories();
		self::create_posts( $categories );
		self::create_pages();
		self::create_menus( $categories );
		if ( $reset_settings ) {
			self::set_options();
		}
		update_option( self::SEEDED_OPTION, INFOSECNEXUS_TOOLKIT_VERSION, false );
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
				'title'      => 'Januscape: The KVM Vulnerability That Requires Immediate Patching',
				'slug'       => 'januscape-kvm-vulnerability-immediate-patching',
				'categories' => array( 'critical-cves', 'linux-administration' ),
				'excerpt'    => 'A critical flaw in Januscape KVM could allow attackers to escape the virtual environment.',
				'content'    => self::article_content( 'januscape' ),
			),
			array(
				'title'      => 'CVE-2026-57156: FreeRDP Integer Overflow',
				'slug'       => 'cve-2026-57156-freerdp-integer-overflow',
				'categories' => array( 'critical-cves' ),
				'excerpt'    => 'A heap-based buffer overflow in FreeRDP channel handling requires immediate review.',
				'content'    => self::article_content( 'freerdp' ),
			),
			array(
				'title'      => 'GhostLock Kernel Fixes Released',
				'slug'       => 'ghostlock-kernel-fixes-released',
				'categories' => array( 'linux-administration', 'critical-cves' ),
				'excerpt'    => 'Security patches are now available for supported Linux kernels.',
				'content'    => self::article_content( 'ghostlock' ),
			),
			array(
				'title'      => 'Microsoft Products Reach End of Support',
				'slug'       => 'microsoft-products-reach-end-of-support',
				'categories' => array( 'cybersecurity', 'windows-security' ),
				'excerpt'    => 'Several Microsoft products have reached end of support. Review your exposure and mitigation options.',
				'content'    => self::article_content( 'microsoft-eol' ),
			),
			array(
				'title'      => 'Secure Your Cloud: 5 Misconfigurations to Fix',
				'slug'       => 'secure-your-cloud-five-misconfigurations',
				'categories' => array( 'cloud-security', 'cybersecurity' ),
				'excerpt'    => 'Common cloud configuration mistakes continue to be a leading cause of breaches.',
				'content'    => self::article_content( 'cloud-misconfigurations' ),
			),
			array(
				'title'      => 'AI Model Supply Chain Risks on the Rise',
				'slug'       => 'ai-model-supply-chain-risks-on-the-rise',
				'categories' => array( 'artificial-intelligence', 'cybersecurity' ),
				'excerpt'    => 'New research reveals vulnerabilities in model dependencies and third-party AI components.',
				'content'    => self::article_content( 'ai-supply-chain' ),
			),
			array(
				'title'      => 'KVM Security Hardening Best Practices for 2026',
				'slug'       => 'kvm-security-hardening-best-practices-2026',
				'categories' => array( 'linux-administration', 'tutorials' ),
				'excerpt'    => 'A practical checklist for virtualization administrators.',
				'content'    => self::article_content( 'kvm-hardening' ),
			),
			array(
				'title'      => 'Build a Patch Review Runbook',
				'slug'       => 'build-a-patch-review-runbook',
				'categories' => array( 'tutorials', 'devops' ),
				'excerpt'    => 'A lightweight process for tracking risk, owners, testing, rollback, and deployment.',
				'content'    => self::article_content( 'patch-runbook' ),
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
					'post_status'   => 'publish',
					'post_category' => $term_ids,
				)
			);
		}
	}

	/**
	 * Rich article body for demo posts.
	 *
	 * @param string $key Article key.
	 * @return string
	 */
	private static function article_content( string $key ): string {
		$articles = array(
			'januscape'               => array(
				'<h2>Executive Summary</h2>',
				'<p>Januscape KVM environments should be reviewed immediately because the flaw tracked as CVE-2026-53359 affects the trust boundary between guest workloads and the virtualization host. In a normal deployment, that boundary is the control point that prevents a compromised guest from influencing the host, neighboring guests, storage paths, or management services.</p>',
				'<p>The highest priority systems are internet-facing virtualization gateways, shared hosting clusters, lab environments that run untrusted images, and production hosts where administrative access is delegated across teams. Even when exploitation requires local guest access, the business impact can be high because the attacker may already have a foothold inside a tenant or workload.</p>',
				'<h2>What Happened</h2>',
				'<p>The issue is rooted in insufficient isolation inside the virtualization path. A guest process can attempt to trigger unexpected host-side behavior through a crafted sequence of operations. Successful exploitation could allow privilege escalation, host process compromise, or access to information that should remain outside the guest boundary.</p>',
				'<p>Security teams should treat this as a containment and patch-management event, not only a package update. The right response includes confirming which hosts run the vulnerable component, which guests are exposed to untrusted users, and which monitoring points can show abnormal guest-to-host activity.</p>',
				'<h2>Systems Most at Risk</h2>',
				'<ul><li>Virtualization hosts running public, customer-managed, or third-party guest images.</li><li>Clusters where management interfaces are reachable from broad internal networks.</li><li>Hosts that have delayed kernel, hypervisor, or platform updates because of uptime requirements.</li><li>Environments that lack detailed audit logs for guest lifecycle events, console access, and host management actions.</li></ul>',
				'<h2>Recommended Action</h2>',
				'<p>Patch affected hosts during the next emergency maintenance window and validate the exact vendor guidance for your distribution or appliance. If patching cannot happen immediately, isolate high-risk guests, restrict console and management access, and increase monitoring on host processes tied to virtualization.</p>',
				'<p>After deployment, verify host versions, reboot status, live-patching state, and guest availability. Record any exceptions with an owner and a deadline so temporary mitigations do not become permanent risk.</p>',
				'<h2>Detection Ideas</h2>',
				'<ul><li>Review host logs for unexpected virtualization process crashes or restarts.</li><li>Look for guest actions followed by host-level privilege changes or unusual management API calls.</li><li>Alert on new administrative sessions to virtualization hosts during non-maintenance windows.</li><li>Compare guest inventory against known high-risk workloads and untrusted images.</li></ul>',
			),
			'freerdp'                 => array(
				'<h2>Overview</h2>',
				'<p>CVE-2026-57156 describes an integer overflow in FreeRDP channel handling that can lead to memory corruption when crafted input is processed. Remote desktop tooling often sits close to administrative workflows, which makes even client-side exposure important for enterprise defenders.</p>',
				'<p>The risk is highest where users connect to untrusted RDP endpoints, where jump boxes accept connections from unmanaged networks, or where automation scripts invoke FreeRDP with stored credentials. A single compromised remote service can become a path to steal session data, execute code, or pivot into administrative tooling.</p>',
				'<h2>Why This Matters</h2>',
				'<p>Remote access clients are trusted by operators and are often permitted through endpoint controls. If the client is exploitable, attackers can reverse the expected trust relationship by turning a malicious server into the delivery mechanism.</p>',
				'<h2>Immediate Response</h2>',
				'<ul><li>Update FreeRDP packages on workstations, jump hosts, and automation runners.</li><li>Restrict outbound RDP connections to approved destinations while patching is in progress.</li><li>Rotate credentials used from any system that connected to suspicious or untrusted RDP services.</li><li>Review EDR telemetry for crashes, child processes, or unusual network activity from remote desktop clients.</li></ul>',
				'<h2>Hardening Notes</h2>',
				'<p>Prefer brokered access, conditional access, and short-lived credentials for administrative remote desktop workflows. Teams should also document which tools are approved for RDP access so unmanaged clients do not become hidden exposure.</p>',
			),
			'ghostlock'               => array(
				'<h2>Patch Window</h2>',
				'<p>GhostLock kernel fixes are available for supported Linux releases. Kernel updates deserve careful coordination because the patch is only complete when the running kernel is replaced, affected modules are refreshed, and reboot or live-patch status is verified.</p>',
				'<p>For production systems, combine patching with a short validation plan: confirm service health, check kernel version, verify monitoring agents, and make sure rollback images are available before starting the maintenance window.</p>',
				'<h2>Operational Checklist</h2>',
				'<ul><li>Identify exposed systems and prioritize internet-facing hosts.</li><li>Confirm whether live patching applies or a reboot is required.</li><li>Validate kernel module compatibility for security, storage, networking, and virtualization drivers.</li><li>Document hosts that remain temporarily unpatched and isolate them where possible.</li></ul>',
				'<h2>Monitoring After Patching</h2>',
				'<p>Watch for failed boots, unexpected kernel taints, module load failures, and process behavior that changed after the update. The first twenty-four hours after a kernel maintenance window are the right time to review dashboards and compare error rates against the previous baseline.</p>',
			),
			'microsoft-eol'           => array(
				'<h2>Operational Risk</h2>',
				'<p>End-of-support software creates a predictable target for attackers. Once security updates stop, every newly disclosed flaw becomes more useful because defenders cannot rely on normal patch flow. The issue is not only the unsupported product itself; it is the identity, network access, and business workflow attached to it.</p>',
				'<p>Teams should treat end-of-support inventory as a risk register item with owners, dates, compensating controls, and a migration path. Unsupported assets that cannot be upgraded quickly should be isolated and monitored until replacement is complete.</p>',
				'<h2>Review Plan</h2>',
				'<ul><li>Inventory installed versions across endpoints, servers, and shared workstations.</li><li>Map each affected system to the business process it supports.</li><li>Remove internet exposure and unnecessary lateral access.</li><li>Prioritize upgrades for systems with privileged users, sensitive data, or remote access.</li></ul>',
				'<h2>Mitigation While Migrating</h2>',
				'<p>Use application control, network segmentation, least-privilege access, and increased logging to reduce exposure. Where possible, move the workload behind a supported gateway or replace the vulnerable component with a maintained alternative.</p>',
			),
			'cloud-misconfigurations' => array(
				'<h2>Focus Areas</h2>',
				'<p>Cloud breaches often begin with simple configuration mistakes that remain visible for too long. The most common problems are public storage, broad identity permissions, exposed management ports, missing logging, and secrets stored in places that were never designed for credentials.</p>',
				'<h2>Five Fixes to Prioritize</h2>',
				'<ul><li>Block public object storage by default and require exception approval.</li><li>Replace broad administrator roles with task-based access and time-bound elevation.</li><li>Move secrets into a managed vault and rotate keys that have been exposed in code or tickets.</li><li>Limit management interfaces to trusted networks and identity-aware access paths.</li><li>Enable audit logs, cloud trail events, and alerting before an incident begins.</li></ul>',
				'<h2>How to Measure Progress</h2>',
				'<p>Track the number of critical misconfigurations by account, owner, and age. A useful dashboard shows which issues are new, which are repeated, and which teams need guardrails in CI/CD rather than more manual reminders.</p>',
			),
			'ai-supply-chain'         => array(
				'<h2>Why It Matters</h2>',
				'<p>AI systems increasingly depend on outside model registries, datasets, notebooks, plugins, and open-source libraries. That supply chain can introduce security, privacy, licensing, and reliability risk before the model ever reaches production.</p>',
				'<p>Security review should cover where the model came from, what data shaped it, which dependencies are loaded at runtime, and who can change prompts, weights, connectors, or retrieval sources.</p>',
				'<h2>Risk Areas</h2>',
				'<ul><li>Untrusted model artifacts downloaded without provenance checks.</li><li>Training or fine-tuning data that includes sensitive information.</li><li>Plugins and tools with access to internal systems.</li><li>Prompt and retrieval changes deployed without review.</li><li>Third-party APIs that become business-critical without resilience planning.</li></ul>',
				'<h2>Practical Controls</h2>',
				'<p>Maintain a model inventory, pin dependencies, scan artifacts, approve external connectors, and log tool calls. The goal is not to slow teams down; it is to make AI deployments understandable enough to operate safely.</p>',
			),
			'kvm-hardening'           => array(
				'<h2>Hardening Goal</h2>',
				'<p>KVM hosts are high-value systems because one host can support many workloads. A secure configuration reduces the chance that a compromised guest can reach the host, neighboring guests, storage fabric, or management plane.</p>',
				'<h2>Baseline Controls</h2>',
				'<ul><li>Keep host kernels, QEMU, libvirt, firmware, and management tools patched.</li><li>Separate tenant, storage, backup, and management networks.</li><li>Use least privilege for operators and automation accounts.</li><li>Enable secure boot, measured boot, or integrity controls where the platform supports them.</li><li>Restrict device passthrough to workloads with clear business justification.</li></ul>',
				'<h2>Operational Practices</h2>',
				'<p>Review guest images before onboarding, keep snapshots under retention control, and test restore paths regularly. Logging should cover guest creation, deletion, migration, console access, and changes to host networking.</p>',
				'<h2>Validation</h2>',
				'<p>Run a quarterly review against your host baseline and capture exceptions. The best hardening program is boring, repeatable, and easy for operations teams to prove during an incident.</p>',
			),
			'patch-runbook'           => array(
				'<h2>Runbook Structure</h2>',
				'<p>A useful patch runbook turns vulnerability noise into a repeatable workflow. It should identify severity, affected assets, business owner, testing requirements, deployment window, rollback plan, and validation evidence.</p>',
				'<h2>Recommended Fields</h2>',
				'<ul><li>Vulnerability or advisory ID, product, and affected version range.</li><li>Asset owner, service tier, and exposure level.</li><li>Patch package, test result, deployment date, and rollback decision.</li><li>Compensating controls for systems that cannot be patched immediately.</li><li>Final validation notes and monitoring checks.</li></ul>',
				'<h2>Operating Rhythm</h2>',
				'<p>Review critical issues daily during active exploitation and weekly for normal patch cycles. Keep exception dates short and visible so risk does not disappear into a spreadsheet after the first meeting.</p>',
				'<h2>Outcome</h2>',
				'<p>The runbook should make it obvious what has been fixed, what remains exposed, who owns the decision, and when the next action happens.</p>',
			),
		);

		return implode( '', $articles[ $key ] ?? array() );
	}

	/**
	 * Create demo pages.
	 */
	private static function create_pages(): void {
		$image = esc_url( get_stylesheet_directory_uri() . '/assets/images/hero-shield.png' );
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
		$pages = array(
			'about'      => array(
				'<section class="isnx-page-hero isnx-page-hero--about"><div><p class="isnx-page-kicker">About InfoSecNexus</p><h2>Cybersecurity intelligence for people who build, run, and defend technology.</h2><p>InfoSecNexus turns fast-moving security news into clear operational guidance for engineers, admins, security teams, and technology leaders.</p><p>We focus on the details that help teams act: what changed, who is affected, why it matters, and what to do next.</p></div><img src="' . $image . '" alt=""></section>',
				'<section class="isnx-stat-strip"><div><strong>Daily</strong><span>signal from security, Linux, cloud, and AI coverage</span></div><div><strong>Action-first</strong><span>patch notes, hardening steps, and review checklists</span></div><div><strong>Independent</strong><span>clear explanations without vendor noise</span></div></section>',
				'<section class="isnx-section"><div class="isnx-section__intro"><p class="isnx-page-kicker">What we cover</p><h2>Built for practical security teams</h2><p>Our coverage is designed for people who need to understand risk quickly and move work through real environments.</p></div><div class="isnx-card-grid"><div class="isnx-card"><h3>Cyber Security</h3><p>Threat intelligence, attack analysis, defensive engineering, incident lessons, and security operations guidance.</p></div><div class="isnx-card"><h3>Linux & DevOps</h3><p>Linux administration, kernel security, containers, automation, CI/CD, and cloud-native operational risk.</p></div><div class="isnx-card"><h3>AI Security</h3><p>Model supply-chain risk, data privacy, AI tooling governance, prompt abuse, and secure adoption patterns.</p></div><div class="isnx-card"><h3>Critical CVEs</h3><p>High-priority vulnerabilities with exploitation context, affected systems, detection ideas, and mitigation plans.</p></div></div></section>',
				'<section class="isnx-split"><div><p class="isnx-page-kicker">Our mission</p><h2>Make complex security developments practical, accurate, and actionable.</h2><p>Security information can move faster than the teams responsible for responding to it. InfoSecNexus exists to reduce that gap. We translate alerts, advisories, and research into language that supports confident decisions.</p></div><div class="isnx-check-list"><h3>How we work</h3><ul><li>Verify important details before turning them into guidance.</li><li>Separate confirmed facts from reasonable operational assumptions.</li><li>Prioritize clear next steps over dramatic language.</li><li>Keep engineers and administrators in mind from the first paragraph.</li></ul></div></section>',
			),
			'contact'    => array(
				'<section class="isnx-page-hero isnx-page-hero--contact"><div><p class="isnx-page-kicker">Contact InfoSecNexus</p><h2>Send corrections, tips, questions, and collaboration requests.</h2><p>We read every serious message and route it to the right editorial or technical review path. For sensitive reports, keep secrets, passwords, private keys, and exploit code out of the first message.</p></div><img src="' . $image . '" alt=""></section>',
				'<section class="isnx-contact-grid"><div class="isnx-contact-card"><h3>Editorial & Corrections</h3><p>Report factual errors, request corrections, or suggest improvements to published content.</p><p><a href="mailto:yashpatel@infosecnexus.com">yashpatel@infosecnexus.com</a></p></div><div class="isnx-contact-card"><h3>Security Tips</h3><p>Share vulnerability leads, exploitation activity, suspicious campaigns, or defensive lessons from the field.</p><p><a href="mailto:yashpatel@infosecnexus.com">yashpatel@infosecnexus.com</a></p></div><div class="isnx-contact-card"><h3>General Enquiries</h3><p>Send partnership requests, media questions, guest ideas, and general notes for the InfoSecNexus team.</p><p><a href="mailto:yashpatel@infosecnexus.com">yashpatel@infosecnexus.com</a></p></div></section>',
				'<section class="isnx-contact-panel"><div><h2>Write to us</h2><p>Tell us what needs review, what changed, and how we can reach you. Clear context helps us route corrections, security tips, and collaboration requests faster.</p><div class="isnx-alert-note"><strong>Before you send:</strong> do not include passwords, private keys, tokens, or sensitive credentials.</div></div><form class="isnx-contact-form" action="mailto:yashpatel@infosecnexus.com" method="post" enctype="text/plain"><label>Your Name<input type="text" name="name" placeholder="Your name"></label><label>Work Email<input type="email" name="email" placeholder="you@example.com"></label><label>Subject<input type="text" name="subject" placeholder="How can we help?"></label><label>Message<textarea name="message" rows="6" placeholder="Share the context we should know."></textarea></label><button type="submit">Send Message</button></form></section>',
				'<section class="isnx-response-band"><div><strong>Typical response time</strong><span>We usually reply within two business days. Urgent corrections and security tips are reviewed first.</span></div></section>',
			),
			'privacy'    => array(
				'<section class="isnx-page-hero isnx-page-hero--legal"><div><p class="isnx-page-kicker">Privacy Policy</p><h2>How InfoSecNexus collects, uses, and protects information.</h2><p>Last updated: July 2026. This policy explains the practical privacy expectations for visitors, subscribers, and people who contact InfoSecNexus.</p></div></section>',
				'<section class="isnx-legal-grid"><div class="isnx-legal-card"><h3>Privacy at a glance</h3><p>We aim to collect only the information needed to operate, improve, secure, and communicate about the website.</p></div><div class="isnx-legal-card"><h3>Minimal data</h3><p>We do not want unnecessary sensitive information. Contact forms and email should not include credentials or private keys.</p></div><div class="isnx-legal-card"><h3>Your choices</h3><p>You may request updates, deletion, or clarification about information you have provided to us.</p></div></section>',
				'<section class="isnx-legal-body"><h2>Information We Collect</h2><p>We may collect information you provide directly, such as your name, email address, message subject, and message content when you contact us or subscribe to updates. We may also receive basic technical information such as browser type, device information, pages visited, and approximate location derived from standard web logs or analytics tools.</p><h2>How We Use Information</h2><p>We use information to operate the website, respond to messages, publish and improve content, measure site performance, prevent abuse, and maintain security. We do not sell personal information.</p><h2>Cookies and Analytics</h2><p>The website may use essential cookies for normal operation and optional analytics or preference storage. Analytics should be configured to collect the least amount of data needed to understand site reliability and content performance.</p><h2>Third-Party Services</h2><p>InfoSecNexus may rely on hosting, email, analytics, security, and content delivery providers. Those providers may process limited information according to their own terms and privacy practices.</p><h2>Data Retention</h2><p>We keep information only as long as needed for the purposes described here, unless a longer period is required for legal, security, or operational reasons.</p><h2>Contact</h2><p>Privacy questions can be sent to <a href="mailto:yashpatel@infosecnexus.com">yashpatel@infosecnexus.com</a>.</p></section>',
			),
			'terms'      => array(
				'<section class="isnx-page-hero isnx-page-hero--legal"><div><p class="isnx-page-kicker">Terms and Conditions</p><h2>The terms governing access to and use of InfoSecNexus.</h2><p>Last updated: July 2026. By accessing this website, you agree to use it responsibly and in accordance with these terms.</p></div></section>',
				'<section class="isnx-legal-body"><h2>Acceptance of Terms</h2><p>By using InfoSecNexus, you agree to these Terms and Conditions. If you do not agree, you should stop using the website.</p><h2>Use of the Website</h2><p>You may use the website for lawful informational and educational purposes. You may not use the website to disrupt services, attempt unauthorized access, distribute malicious content, or interfere with other users.</p><h2>Content and Intellectual Property</h2><p>Articles, graphics, logos, page designs, and other materials on InfoSecNexus are owned by InfoSecNexus or used with permission unless otherwise stated. You may reference our content with proper attribution, but you may not reproduce substantial portions without permission.</p><h2>No Guarantee of Availability</h2><p>We work to keep the website available and accurate, but access may be interrupted for maintenance, security, hosting, or other operational reasons.</p><h2>Third-Party Links</h2><p>InfoSecNexus may link to third-party websites, advisories, tools, or documentation. We are not responsible for the content, security, privacy practices, or availability of third-party sites.</p><h2>Changes to These Terms</h2><p>We may update these terms from time to time. Continued use of the website after updates means you accept the revised terms.</p><h2>Contact</h2><p>Questions about these terms can be sent to <a href="mailto:yashpatel@infosecnexus.com">yashpatel@infosecnexus.com</a>.</p></section>',
			),
			'disclaimer' => array(
				'<section class="isnx-page-hero isnx-page-hero--legal"><div><p class="isnx-page-kicker">Disclaimer</p><h2>Important information about security content published on InfoSecNexus.</h2><p>Last updated: July 2026. This disclaimer explains how to interpret the educational and operational guidance on this website.</p></div></section>',
				'<section class="isnx-legal-body"><h2>General Information</h2><p>InfoSecNexus publishes cybersecurity, infrastructure, open-source, and technology content for general informational and educational purposes. Content may summarize public advisories, research, vendor guidance, and operational best practices.</p><h2>No Professional Advice</h2><p>The content does not constitute legal, financial, compliance, technical, or professional advice for your specific environment. Always consult qualified professionals and trusted vendors before making high-impact decisions.</p><h2>Accuracy of Information</h2><p>We work to provide accurate and timely information, but security developments change quickly. Vulnerability status, affected versions, exploitation details, and mitigations may change after publication.</p><h2>Security Research</h2><p>Security research, testing, scanning, and exploit validation should be performed only in systems and environments where you have clear authorization. Do not use information from this website for unauthorized activity.</p><h2>External Links</h2><p>External links are provided for convenience and reference. InfoSecNexus is not responsible for third-party content, changes, privacy practices, or availability.</p><h2>Limitation of Liability</h2><p>Use of information from this website is at your own risk. InfoSecNexus and its authors are not liable for damages arising from use of, or inability to use, the information published here.</p><h2>Contact</h2><p>Questions about this disclaimer can be sent to <a href="mailto:yashpatel@infosecnexus.com">yashpatel@infosecnexus.com</a>.</p></section>',
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
		self::remove_menu_items_by_title( $footer, array( 'Home', 'About', 'About InfoSecNexus', 'Contact', 'Contact InfoSecNexus', 'Privacy Policy', 'Terms and Conditions', 'Disclaimer', 'Back to top', 'RSS' ) );
		self::remove_menu_items_by_title( $legal, array( 'Privacy Policy', 'Terms and Conditions', 'Disclaimer', 'Back to top', 'RSS' ) );

		self::add_custom_menu_item_once( $primary, 'Home', home_url( '/' ) );
		foreach ( array( 'cybersecurity', 'critical-cves', 'linux-administration', 'artificial-intelligence', 'tutorials' ) as $slug ) {
			if ( isset( $categories[ $slug ] ) ) {
				self::add_term_menu_item_once( $primary, $categories[ $slug ] );
			}
		}

		foreach ( array( 'about', 'contact' ) as $slug ) {
			$page = get_page_by_path( $slug );
			if ( $page ) {
				self::add_post_menu_item_once( $primary, (int) $page->ID );
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
	private static function add_custom_menu_item_once( int $menu_id, string $title, string $url ): void {
		if ( self::menu_has_title( $menu_id, $title ) ) {
			return;
		}
		wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-title'  => $title,
				'menu-item-url'    => $url,
				'menu-item-status' => 'publish',
				'menu-item-type'   => 'custom',
			)
		);
	}

	/**
	 * Add term menu item if missing.
	 */
	private static function add_term_menu_item_once( int $menu_id, int $term_id ): void {
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
		$items = wp_get_nav_menu_items( $menu_id );
		if ( empty( $items ) ) {
			return false;
		}
		foreach ( $items as $item ) {
			if ( $item->title === $title ) {
				return true;
			}
		}
		return false;
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
		$options['newsletter_heading'] = __( 'Get the Daily Cyber Brief', 'infosecnexus-toolkit' );
		$options['newsletter_intro'] = __( 'Top stories, critical alerts, and expert analysis delivered to your inbox every morning.', 'infosecnexus-toolkit' );
		update_option( OPTION_KEY, $options, false );

		set_theme_mod( 'default_color_mode', 'light' );
		set_theme_mod( 'alert_enabled', false );
		set_theme_mod( 'home_hero_badge', 'Critical Brief' );
		set_theme_mod( 'header_button_label', 'Daily Cyber Brief' );
		set_theme_mod( 'header_button_url', home_url( '/#daily-cyber-brief' ) );
		set_theme_mod( 'alert_link_url', home_url( '/category/critical-cves/' ) );
		set_theme_mod( 'home_hero_button_url', home_url( '/category/critical-cves/' ) );
		set_theme_mod( 'footer_top_elements', 'logo' );
		set_theme_mod( 'footer_main_elements', '' );
		set_theme_mod( 'footer_bottom_elements', 'copyright,spacer,legal_navigation' );
		set_theme_mod( 'copyright', 'Copyright {year} InfoSecNexus. All rights reserved.' );
	}
}
