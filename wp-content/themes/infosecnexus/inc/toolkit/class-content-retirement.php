<?php
/**
 * Reversible retirement workflow for legacy generated posts.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

namespace InfoSecNexus\Theme\Toolkit;

/**
 * Keep old URLs crawlable for deindexing before a deliberate 410 finalization.
 */
final class Content_Retirement {
	private const STATE_META             = '_infosecnexus_retirement_state';
	private const RETIRED_AT_META        = '_infosecnexus_retired_at';
	private const INVENTORY_OPTION       = 'infosecnexus_content_retirement_inventory';
	private const GONE_PATHS_OPTION      = 'infosecnexus_content_retirement_gone_paths';
	private const NEWS_SITEMAP_QUERY_VAR = 'infosecnexus_news_sitemap';

	/**
	 * Register retirement, sitemap, admin, and CLI integrations.
	 */
	public static function boot(): void {
		add_filter( 'wp_robots', array( __CLASS__, 'robots' ) );
		add_action( 'send_headers', array( __CLASS__, 'send_x_robots_header' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'exclude_retired_from_public_queries' ) );
		add_filter( 'wp_sitemaps_posts_query_args', array( __CLASS__, 'filter_sitemap_query' ), 10, 2 );
		add_filter( 'robots_txt', array( __CLASS__, 'robots_txt' ), 10, 2 );
		add_action( 'init', array( __CLASS__, 'register_news_sitemap' ) );
		add_filter( 'query_vars', array( __CLASS__, 'query_vars' ) );
		add_action( 'template_redirect', array( __CLASS__, 'maybe_render_special_response' ), 1 );
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'handle_admin_actions' ) );

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'infosecnexus content inventory', array( __CLASS__, 'cli_inventory' ) );
			\WP_CLI::add_command( 'infosecnexus content stage', array( __CLASS__, 'cli_stage' ) );
			\WP_CLI::add_command( 'infosecnexus content finalize', array( __CLASS__, 'cli_finalize' ) );
		}
	}

	/**
	 * Send an explicit crawler directive even when an SEO plugin replaces meta.
	 */
	public static function send_x_robots_header(): void {
		if ( ! is_singular( 'post' ) || ! self::is_staged( (int) get_queried_object_id() ) || headers_sent() ) {
			return;
		}
		header( 'X-Robots-Tag: noindex, follow, noarchive', true );
	}

	/**
	 * Add noindex directives while a legacy URL remains available to crawlers.
	 *
	 * @param array<string,mixed> $robots Existing robots directives.
	 * @return array<string,mixed>
	 */
	public static function robots( array $robots ): array {
		if ( is_singular( 'post' ) && self::is_staged( (int) get_queried_object_id() ) ) {
			$robots['noindex']   = true;
			$robots['follow']    = true;
			$robots['noarchive'] = true;
			unset( $robots['index'], $robots['nofollow'] );
		}

		return $robots;
	}

	/**
	 * Retired posts stay reachable by exact URL but disappear from reader lists.
	 *
	 * @param \WP_Query $query Public query.
	 */
	public static function exclude_retired_from_public_queries( \WP_Query $query ): void {
		if ( ( defined( 'WP_CLI' ) && WP_CLI ) || is_admin() || $query->is_singular() || 'post' !== self::query_post_type( $query ) ) {
			return;
		}

		self::append_active_content_meta_query( $query );
	}

	/**
	 * Exclude staged URLs from WordPress core sitemaps.
	 *
	 * @param array<string,mixed> $args Sitemap query arguments.
	 * @param string              $post_type Sitemap post type.
	 * @return array<string,mixed>
	 */
	public static function filter_sitemap_query( array $args, string $post_type ): array {
		if ( 'post' !== $post_type ) {
			return $args;
		}

		$meta_query         = is_array( $args['meta_query'] ?? null ) ? $args['meta_query'] : array();
		$meta_query[]       = self::active_content_clause();
		$args['meta_query'] = $meta_query;

		return $args;
	}

	/**
	 * Advertise the two-day Google News-compatible sitemap.
	 *
	 * @param string $output    Existing robots.txt output.
	 * @param bool   $is_public Whether search engines may index the site.
	 * @return string
	 */
	public static function robots_txt( string $output, bool $is_public ): string {
		if ( ! $is_public ) {
			return $output;
		}

		$line = 'Sitemap: ' . home_url( '/news-sitemap.xml' );
		if ( false === strpos( $output, $line ) ) {
			$output = rtrim( $output ) . "\n" . $line . "\n";
		}

		return $output;
	}

	/**
	 * Register the custom newsroom sitemap route.
	 */
	public static function register_news_sitemap(): void {
		add_rewrite_rule( '^news-sitemap\.xml$', 'index.php?' . self::NEWS_SITEMAP_QUERY_VAR . '=1', 'top' );
	}

	/**
	 * Register custom public query variables.
	 *
	 * @param string[] $vars Existing query vars.
	 * @return string[]
	 */
	public static function query_vars( array $vars ): array {
		$vars[] = self::NEWS_SITEMAP_QUERY_VAR;
		return $vars;
	}

	/**
	 * Serve the news sitemap or a finalized 410 response.
	 */
	public static function maybe_render_special_response(): void {
		if ( '1' === (string) get_query_var( self::NEWS_SITEMAP_QUERY_VAR ) ) {
			self::render_news_sitemap();
		}

		if ( ! is_404() || ! self::is_finalized_path( self::request_path() ) ) {
			return;
		}

		status_header( 410 );
		nocache_headers();
		$template = get_query_template( '404' );
		if ( '' !== $template ) {
			include $template;
		} else {
			wp_die( esc_html__( 'This briefing has been permanently retired.', 'infosecnexus' ), '', array( 'response' => 410 ) );
		}
		exit;
	}

	/**
	 * Add the retirement screen under Tools.
	 */
	public static function admin_menu(): void {
		add_management_page(
			__( 'Content Retirement', 'infosecnexus' ),
			__( 'Content Retirement', 'infosecnexus' ),
			'manage_options',
			'infosecnexus-content-retirement',
			array( __CLASS__, 'render_admin_page' )
		);
	}

	/**
	 * Handle staged-retirement and CSV export requests.
	 */
	public static function handle_admin_actions(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! empty( $_GET['infosecnexus_stage_generated_posts'] ) ) {
			check_admin_referer( 'infosecnexus_stage_generated_posts' );
			$result = self::stage_generated_posts();
			wp_safe_redirect(
				wp_nonce_url(
					add_query_arg(
						array(
							'page'   => 'infosecnexus-content-retirement',
							'staged' => (int) $result['staged'],
						),
						admin_url( 'tools.php' )
					),
					'infosecnexus_retirement_notice'
				)
			);
			exit;
		}

		if ( ! empty( $_GET['infosecnexus_export_retirement_csv'] ) ) {
			check_admin_referer( 'infosecnexus_export_retirement_csv' );
			self::export_csv();
		}
	}

	/**
	 * Render the reversible retirement workflow.
	 */
	public static function render_admin_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$inventory    = self::inventory();
		$staged       = array_filter( $inventory, static fn( array $row ): bool => 'staged' === (string) $row['retirement_state'] );
		$staged_count = null;
		if ( isset( $_GET['staged'], $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( (string) $_GET['_wpnonce'] ) ), 'infosecnexus_retirement_notice' ) ) {
			$staged_count = absint( wp_unslash( (string) $_GET['staged'] ) );
		}
		$stage_url  = wp_nonce_url(
			admin_url( 'tools.php?page=infosecnexus-content-retirement&infosecnexus_stage_generated_posts=1' ),
			'infosecnexus_stage_generated_posts'
		);
		$export_url = wp_nonce_url(
			admin_url( 'tools.php?page=infosecnexus-content-retirement&infosecnexus_export_retirement_csv=1' ),
			'infosecnexus_export_retirement_csv'
		);
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Content Retirement', 'infosecnexus' ); ?></h1>
			<?php if ( null !== $staged_count ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php /* translators: %d: Number of staged legacy URLs. */ echo esc_html( sprintf( __( '%d generated URLs were staged for deindexing.', 'infosecnexus' ), $staged_count ) ); ?></p></div>
			<?php endif; ?>
			<p><?php esc_html_e( 'Staging keeps each exact URL available with noindex, follow and noarchive, removes it from archives and sitemaps, and preserves it until Google has processed the change.', 'infosecnexus' ); ?></p>
			<p><strong><?php esc_html_e( 'Do not permanently delete these posts until Search Console confirms that the URLs are no longer indexed.', 'infosecnexus' ); ?></strong></p>
			<p>
				<a class="button button-primary" href="<?php echo esc_url( $stage_url ); ?>"><?php esc_html_e( 'Stage Legacy Generated Posts', 'infosecnexus' ); ?></a>
				<a class="button" href="<?php echo esc_url( $export_url ); ?>"><?php esc_html_e( 'Download URL Inventory', 'infosecnexus' ); ?></a>
			</p>
			<table class="widefat striped" style="max-width: 1100px">
				<thead><tr><th><?php esc_html_e( 'Total generated', 'infosecnexus' ); ?></th><th><?php esc_html_e( 'Staged', 'infosecnexus' ); ?></th><th><?php esc_html_e( 'Permanent deletion', 'infosecnexus' ); ?></th></tr></thead>
				<tbody><tr><td><?php echo esc_html( (string) count( $inventory ) ); ?></td><td><?php echo esc_html( (string) count( $staged ) ); ?></td><td><?php esc_html_e( 'Locked to an explicit WP-CLI confirmation after deindex verification.', 'infosecnexus' ); ?></td></tr></tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Return every legacy generated post that is safe to manage as a batch.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public static function inventory(): array {
		$query = new \WP_Query(
			array(
				'post_type'      => 'post',
				'post_status'    => array( 'publish', 'draft', 'private', 'pending', 'future' ),
				'posts_per_page' => -1,
				'orderby'        => 'ID',
				'order'          => 'ASC',
				'no_found_rows'  => true,
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'   => '_infosecnexus_demo_content',
						'value' => '1',
					),
					array(
						'key'     => '_infosecnexus_newsroom_post',
						'compare' => 'NOT EXISTS',
					),
				),
			)
		);

		$rows = array();
		foreach ( $query->posts as $post ) {
			if ( ! $post instanceof \WP_Post ) {
				continue;
			}
			$rows[] = array(
				'id'               => (int) $post->ID,
				'title'            => (string) $post->post_title,
				'url'              => (string) get_permalink( $post ),
				'slug'             => (string) $post->post_name,
				'published'        => (string) $post->post_date_gmt,
				'modified'         => (string) $post->post_modified_gmt,
				'status'           => (string) $post->post_status,
				'retirement_state' => (string) get_post_meta( $post->ID, self::STATE_META, true ),
			);
		}

		return $rows;
	}

	/**
	 * Stage all legacy generated posts without deleting any URL.
	 *
	 * @return array{staged:int,already_staged:int,total:int}
	 */
	public static function stage_generated_posts(): array {
		$rows           = self::inventory();
		$staged         = 0;
		$already_staged = 0;
		$timestamp      = current_time( 'mysql', true );

		foreach ( $rows as $row ) {
			$post_id = (int) $row['id'];
			if ( 'staged' === (string) $row['retirement_state'] ) {
				++$already_staged;
				continue;
			}
			update_post_meta( $post_id, self::STATE_META, 'staged' );
			update_post_meta( $post_id, self::RETIRED_AT_META, $timestamp );
			++$staged;
		}

		$staged_rows = self::inventory();
		update_option(
			self::INVENTORY_OPTION,
			array(
				'created_at' => $timestamp,
				'rows'       => $staged_rows,
			),
			false
		);

		if ( method_exists( Demo_Content::class, 'purge_public_cache' ) ) {
			Demo_Content::purge_public_cache( array_map( static fn( array $row ): int => (int) $row['id'], $rows ) );
		}

		return array(
			'staged'         => $staged,
			'already_staged' => $already_staged,
			'total'          => count( $rows ),
		);
	}

	/**
	 * Permanently remove staged posts after an external deindex confirmation.
	 *
	 * @return array{deleted:int,skipped:int}
	 */
	public static function finalize_staged_posts(): array {
		$deleted = 0;
		$skipped = 0;
		$paths   = get_option( self::GONE_PATHS_OPTION, array() );
		$paths   = is_array( $paths ) ? array_map( 'strval', $paths ) : array();

		foreach ( self::inventory() as $row ) {
			if ( 'staged' !== (string) $row['retirement_state'] ) {
				++$skipped;
				continue;
			}

			$path = self::url_path( (string) $row['url'] );
			if ( '' !== $path ) {
				$paths[] = $path;
			}
			if ( wp_delete_post( (int) $row['id'], true ) ) {
				++$deleted;
			} else {
				++$skipped;
			}
		}

		update_option( self::GONE_PATHS_OPTION, array_values( array_unique( $paths ) ), false );
		if ( method_exists( Demo_Content::class, 'purge_public_cache' ) ) {
			Demo_Content::purge_public_cache( array() );
		}

		return array(
			'deleted' => $deleted,
			'skipped' => $skipped,
		);
	}

	/**
	 * WP-CLI inventory command.
	 *
	 * @param string[]            $args Positional arguments.
	 * @param array<string,mixed> $assoc_args Named arguments.
	 */
	public static function cli_inventory( array $args, array $assoc_args ): void {
		unset( $args, $assoc_args );
		\WP_CLI\Utils\format_items( 'table', self::inventory(), array( 'id', 'title', 'url', 'status', 'retirement_state' ) );
	}

	/**
	 * WP-CLI staging command.
	 *
	 * @param string[]            $args Positional arguments.
	 * @param array<string,mixed> $assoc_args Named arguments.
	 */
	public static function cli_stage( array $args, array $assoc_args ): void {
		unset( $args, $assoc_args );
		$result = self::stage_generated_posts();
		\WP_CLI::success( sprintf( 'Staged %d of %d legacy generated URLs; %d were already staged.', $result['staged'], $result['total'], $result['already_staged'] ) );
	}

	/**
	 * WP-CLI finalization command with a deliberately explicit confirmation.
	 *
	 * @param string[]            $args Positional arguments.
	 * @param array<string,mixed> $assoc_args Named arguments.
	 */
	public static function cli_finalize( array $args, array $assoc_args ): void {
		unset( $args );
		if ( 'DELETE' !== (string) ( $assoc_args['confirm'] ?? '' ) ) {
			\WP_CLI::error( 'Deindex confirmation is required. Re-run with --confirm=DELETE only after Search Console confirms removal.' );
		}
		$result = self::finalize_staged_posts();
		\WP_CLI::success( sprintf( 'Permanently deleted %d staged posts; skipped %d.', $result['deleted'], $result['skipped'] ) );
	}

	/**
	 * Output the current retirement inventory as CSV.
	 */
	private static function export_csv(): void {
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=infosecnexus-retirement-inventory-' . gmdate( 'Y-m-d' ) . '.csv' );
		$stream = fopen( 'php://output', 'wb' );
		if ( false === $stream ) {
			wp_die( esc_html__( 'Unable to create the CSV export.', 'infosecnexus' ) );
		}
		fputcsv( $stream, array( 'ID', 'Title', 'URL', 'Slug', 'Published GMT', 'Modified GMT', 'Status', 'Retirement State' ) );
		foreach ( self::inventory() as $row ) {
			fputcsv( $stream, array_values( $row ) );
		}
		fclose( $stream );
		exit;
	}

	/**
	 * Render a compact two-day news sitemap for newsroom posts.
	 */
	private static function render_news_sitemap(): void {
		$query = new \WP_Query(
			array(
				'post_type'           => 'post',
				'post_status'         => 'publish',
				'posts_per_page'      => 1000,
				'ignore_sticky_posts' => true,
				'date_query'          => array(
					array( 'after' => '2 days ago' ),
				),
				'meta_query'          => array(
					array(
						'key'   => '_infosecnexus_newsroom_post',
						'value' => '1',
					),
					self::active_content_clause(),
				),
			)
		);

		status_header( 200 );
		header( 'Content-Type: application/xml; charset=UTF-8' );
		echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">' . "\n";
		foreach ( $query->posts as $post ) {
			if ( ! $post instanceof \WP_Post ) {
				continue;
			}
			echo "  <url>\n";
			echo '    <loc>' . esc_xml( (string) get_permalink( $post ) ) . "</loc>\n";
			echo "    <news:news>\n";
			echo "      <news:publication>\n";
			echo '        <news:name>' . esc_xml( (string) get_bloginfo( 'name' ) ) . "</news:name>\n";
			echo "        <news:language>en</news:language>\n";
			echo "      </news:publication>\n";
			echo '      <news:publication_date>' . esc_xml( get_post_time( 'c', true, $post ) ) . "</news:publication_date>\n";
			echo '      <news:title>' . esc_xml( (string) get_the_title( $post ) ) . "</news:title>\n";
			echo "    </news:news>\n";
			echo "  </url>\n";
		}
		echo "</urlset>\n";
		exit;
	}

	/**
	 * Append the active-content clause to a WP_Query.
	 *
	 * @param \WP_Query $query Query to constrain.
	 */
	private static function append_active_content_meta_query( \WP_Query $query ): void {
		$meta_query   = $query->get( 'meta_query' );
		$meta_query   = is_array( $meta_query ) ? $meta_query : array();
		$meta_query[] = self::active_content_clause();
		$query->set( 'meta_query', $meta_query );
	}

	/**
	 * Return the post type represented by a public query.
	 *
	 * @param \WP_Query $query Public query.
	 */
	private static function query_post_type( \WP_Query $query ): string {
		$post_type = $query->get( 'post_type' );
		if ( is_array( $post_type ) ) {
			return in_array( 'post', $post_type, true ) ? 'post' : '';
		}
		return '' === (string) $post_type ? 'post' : (string) $post_type;
	}

	/**
	 * Reusable query clause for anything not staged.
	 *
	 * @return array<int|string,array<string,string>|string>
	 */
	private static function active_content_clause(): array {
		return array(
			'relation' => 'OR',
			array(
				'key'     => self::STATE_META,
				'compare' => 'NOT EXISTS',
			),
			array(
				'key'     => self::STATE_META,
				'value'   => 'staged',
				'compare' => '!=',
			),
		);
	}

	/**
	 * Whether one post is currently staged.
	 *
	 * @param int $post_id Post ID.
	 */
	private static function is_staged( int $post_id ): bool {
		return $post_id > 0 && 'staged' === (string) get_post_meta( $post_id, self::STATE_META, true );
	}

	/**
	 * Whether a deleted path should permanently return 410.
	 *
	 * @param string $path Normalized request path.
	 */
	private static function is_finalized_path( string $path ): bool {
		$paths = get_option( self::GONE_PATHS_OPTION, array() );
		return is_array( $paths ) && in_array( $path, array_map( 'strval', $paths ), true );
	}

	/**
	 * Return the normalized current request path.
	 */
	private static function request_path(): string {
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( (string) $_SERVER['REQUEST_URI'] ) ) : '';
		$path        = (string) wp_parse_url( $request_uri, PHP_URL_PATH );
		return '/' . trim( $path, '/' ) . '/';
	}

	/**
	 * Normalize a URL to the path stored for future 410 handling.
	 *
	 * @param string $url Absolute URL.
	 */
	private static function url_path( string $url ): string {
		$path = (string) wp_parse_url( $url, PHP_URL_PATH );
		return '' === $path ? '' : '/' . trim( $path, '/' ) . '/';
	}
}
