<?php
/**
 * Live AJAX search.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

namespace InfoSecNexus\Theme\Toolkit;

use WP_REST_Request;
use WP_REST_Response;

/**
 * Search module.
 */
final class Search {
	/**
	 * Register hooks.
	 */
	public static function boot(): void {
		if ( ! module_enabled( 'live_search' ) ) {
			return;
		}
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
		add_action( 'wp_footer', array( __CLASS__, 'modal' ) );
	}

	/**
	 * Register REST routes.
	 */
	public static function routes(): void {
		register_rest_route(
			'infosecnexus/v1',
			'/search',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'search' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'q' => array( 'sanitize_callback' => 'sanitize_text_field' ),
				),
			)
		);
	}

	/**
	 * Search callback.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function search( WP_REST_Request $request ): WP_REST_Response {
		if ( ! rate_limit( 'live_search', 40, 60 ) ) {
			return new WP_REST_Response( array( 'message' => __( 'Too many requests.', 'infosecnexus' ) ), 429 );
		}

		$query_text = trim( (string) $request->get_param( 'q' ) );
		if ( strlen( $query_text ) < 2 ) {
			return new WP_REST_Response( array( 'results' => array() ) );
		}

		$wp_query = new \WP_Query(
			array(
				's'                   => $query_text,
				'post_type'           => 'post',
				'post_status'         => 'publish',
				'posts_per_page'      => 8,
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
			)
		);

		$results = array();
		foreach ( $wp_query->posts as $post ) {
			$results[] = array(
				'title'   => get_the_title( $post ),
				'url'     => get_permalink( $post ),
				'type'    => get_post_type_object( $post->post_type )->labels->singular_name ?? $post->post_type,
				'excerpt' => wp_trim_words( wp_strip_all_tags( get_the_excerpt( $post ) ), 22 ),
			);
		}

		return new WP_REST_Response( array( 'results' => $results ) );
	}

	/**
	 * Render modal shell.
	 */
	public static function modal(): void {
		?>
		<div class="isnx-live-search" data-isnx-live-search hidden>
			<div class="isnx-live-search__panel" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Live search', 'infosecnexus' ); ?>">
				<button type="button" class="isnx-live-search__close" data-isnx-live-search-close aria-label="<?php esc_attr_e( 'Close search', 'infosecnexus' ); ?>">&times;</button>
				<form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" data-isnx-live-search-form>
					<label>
						<span class="screen-reader-text"><?php esc_html_e( 'Search', 'infosecnexus' ); ?></span>
						<input type="search" name="s" autocomplete="off" data-isnx-live-search-input placeholder="<?php esc_attr_e( 'Search blog posts...', 'infosecnexus' ); ?>">
					</label>
					<input type="hidden" name="post_type" value="post">
					<button type="submit"><?php esc_html_e( 'Search', 'infosecnexus' ); ?></button>
				</form>
				<div class="isnx-live-search__results" data-isnx-live-search-results role="listbox" aria-live="polite"></div>
			</div>
		</div>
		<?php
	}
}
