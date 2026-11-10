<?php
/**
 * Truthful author discovery and existing reader subscription paths.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

namespace InfoSecNexus\Theme\Reader_Growth;

/** Register public author metadata without duplicating an SEO plugin. */
function bootstrap(): void {
	add_action( 'wp_head', __NAMESPACE__ . '\\author_schema', 6 );
	add_filter( 'the_content', __NAMESPACE__ . '\\contextual_product_link', 20 );
}

/** Link separate appliance families only where that distinction helps the reader. */
function contextual_product_link( string $content ): string {
	if ( is_admin() || ! is_singular( 'post' ) || (int) get_the_ID() !== (int) get_queried_object_id()
		|| post_password_required() || str_contains( $content, 'reader-context-link' ) ) {
		return $content;
	}
	preg_match_all( '/\bCVE-\d{4}-\d{4,}\b/i', (string) get_the_title(), $matches );
	$identifiers = array_unique( array_map( 'strtoupper', $matches[0] ) );
	if ( 1 !== count( $identifiers ) ) {
		return $content;
	}
	$cve = (string) reset( $identifiers );
	$targets = array( 'CVE-2026-19868' => 'CVE-2026-83548', 'CVE-2026-83548' => 'CVE-2026-19868' );
	if ( ! isset( $targets[ $cve ] ) || ! preg_match( '/<h2\b[^>]*>\s*How to fix\s*<\/h2>/i', $content ) ) {
		return $content;
	}
	static $links = array();
	$target_cve = $targets[ $cve ];
	if ( ! array_key_exists( $target_cve, $links ) ) {
		$links[ $target_cve ] = '';
		$candidates = get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 's' => $target_cve, 'posts_per_page' => 10, 'has_password' => false, 'no_found_rows' => true ) );
		foreach ( $candidates as $candidate ) {
			if ( preg_match( '/\b' . preg_quote( $target_cve, '/' ) . '\b/i', $candidate->post_title )
				&& '' === (string) get_post_meta( $candidate->ID, '_infosecnexus_retirement_state', true )
				&& (int) get_post_meta( $candidate->ID, '_infosecnexus_newsroom_superseded_by', true ) <= 0 ) {
				$links[ $target_cve ] = get_permalink( $candidate );
				break;
			}
		}
	}
	if ( '' === $links[ $target_cve ] ) {
		return $content;
	}
	$label = 'CVE-2026-19868' === $cve ? __( 'the separate SMA1000 SSRF briefing', 'infosecnexus' ) : __( 'the separate SonicOS SSLVPN briefing', 'infosecnexus' );
	$link = '<a href="' . esc_url( $links[ $target_cve ] ) . '">' . esc_html( $label ) . '</a>';
	/* translators: %s: link to a separate product-family advisory. */
	$note = sprintf( __( 'If you also operate the other SonicWall appliance family, review %s. SonicOS and SMA1000 are distinct products; the affected versions and remediation in one briefing must not be applied to the other.', 'infosecnexus' ), $link );
	$addition = '<p class="reader-context-link">' . $note . '</p>';
	return (string) preg_replace_callback( '/<h2\b[^>]*>\s*How to fix\s*<\/h2>/i', static fn( array $match ): string => $match[0] . $addition, $content, 1 );
}

/** Describe the same public person visible on the author page. */
function author_schema(): void {
	if ( ! is_author() || \InfoSecNexus\Theme\SEO\seo_plugin_active() ) {
		return;
	}
	$author = get_queried_object();
	if ( ! $author instanceof \WP_User ) {
		return;
	}
	$url = get_author_posts_url( (int) $author->ID );
	$schema = array(
		'@context' => 'https://schema.org',
		'@type' => 'ProfilePage',
		'@id' => $url . '#profile',
		'url' => $url,
		'name' => $author->display_name,
		'mainEntity' => array(
			'@type' => 'Person',
			'@id' => $url . '#person',
			'name' => $author->display_name,
			'url' => $url,
		),
	);
	$bio = trim( wp_strip_all_tags( (string) get_the_author_meta( 'description', (int) $author->ID ) ) );
	if ( '' !== $bio ) {
		$schema['mainEntity']['description'] = $bio;
	}
	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
}

/** Reuse the real double-opt-in form, only on current public articles. */
function article_newsletter(): void {
	$post_id = (int) get_the_ID();
	if ( post_password_required( $post_id )
		|| '' !== (string) get_post_meta( $post_id, '_infosecnexus_retirement_state', true )
		|| (int) get_post_meta( $post_id, '_infosecnexus_newsroom_superseded_by', true ) > 0
		|| ! function_exists( '\\InfoSecNexus\\Theme\\Toolkit\\module_enabled' )
		|| ! \InfoSecNexus\Theme\Toolkit\module_enabled( 'newsletter' )
		|| ! class_exists( '\\InfoSecNexus\\Theme\\Toolkit\\Newsletter' ) ) {
		return;
	}
	?>
	<section class="reader-newsletter" aria-labelledby="reader-newsletter-title">
		<p class="editorial-eyebrow"><?php esc_html_e( 'Keep the context', 'infosecnexus' ); ?></p>
		<h2 id="reader-newsletter-title"><?php esc_html_e( 'Get the next security briefing.', 'infosecnexus' ); ?></h2>
		<p><?php esc_html_e( 'Security news and practical guidance, delivered by email.', 'infosecnexus' ); ?></p>
		<?php \InfoSecNexus\Theme\Toolkit\Newsletter::render_form( false ); ?>
		<p class="reader-newsletter__privacy"><?php esc_html_e( 'Confirm by email. Unsubscribe any time.', 'infosecnexus' ); ?> <a href="<?php echo esc_url( \InfoSecNexus\Theme\Header_Builder\page_url( 'privacy-policy' ) ); ?>"><?php esc_html_e( 'Privacy policy', 'infosecnexus' ); ?></a></p>
	</section>
	<?php
}
