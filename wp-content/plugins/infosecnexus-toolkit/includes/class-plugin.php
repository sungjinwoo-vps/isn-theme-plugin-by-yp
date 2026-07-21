<?php
/**
 * Plugin orchestrator.
 *
 * @package InfoSecNexusToolkit
 */

declare(strict_types=1);

namespace InfoSecNexus\Toolkit;

/**
 * Main plugin class.
 */
final class Plugin {
	/**
	 * Singleton.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Get instance.
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Default module states.
	 *
	 * @return array<string,bool>
	 */
	public static function default_modules(): array {
		return array(
			'content_blocks'    => true,
			'conditions'        => true,
			'elementor_widgets' => true,
			'live_search'       => true,
			'sidebars'          => true,
			'newsletter'        => true,
			'cookie_consent'    => false,
			'featured_videos'   => true,
			'related_posts'     => true,
			'snippets'          => false,
			'maintenance'       => false,
			'woocommerce'       => true,
		);
	}

	/**
	 * Activation defaults.
	 */
	public static function activate(): void {
		$options = options();
		if ( empty( $options ) ) {
			add_option(
				OPTION_KEY,
				array(
					'modules'             => self::default_modules(),
					'cookie_text'         => __( 'InfoSecNexus uses essential cookies and optional preference storage to improve the reading experience.', 'infosecnexus-toolkit' ),
					'newsletter_heading'  => __( 'Get the daily security briefing', 'infosecnexus-toolkit' ),
					'newsletter_intro'    => __( 'A concise roundup of critical CVEs, infrastructure changes, and defensive operations.', 'infosecnexus-toolkit' ),
					'custom_css'          => '',
					'custom_js'           => '',
					'sidebar_conditions'  => '',
					'maintenance_enabled' => false,
					'updates_enabled'     => true,
					'update_manifest_url' => Updater::default_manifest_url(),
				),
				'',
				false
			);
		}
		Content_Blocks::register_post_type();
		Newsletter::register_post_type();
		flush_rewrite_rules();
	}

	/**
	 * Boot modules.
	 */
	public function boot(): void {
		Updater::boot();
		Settings::boot();
		Assets::boot();
		Admin_Meta::boot();
		Content_Blocks::boot();
		Sidebars::boot();
		Search::boot();
		Newsletter::boot();
		Cookie_Consent::boot();
		Related_Posts::boot();
		Demo_Content::boot();
		Snippets::boot();
		Maintenance::boot();
		WooCommerce::boot();
		Elementor::boot();
	}
}
