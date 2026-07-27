<?php
/**
 * Agent-friendly discovery endpoints.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

namespace InfoSecNexus\Theme\Agentic;

/**
 * Register public discovery routes.
 */
function bootstrap(): void {
	add_action( 'template_redirect', __NAMESPACE__ . '\\maybe_render_llms_txt', -100 );
}

/**
 * Render a concise llms.txt document at the site root.
 */
function maybe_render_llms_txt(): void {
	$request_path = (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH );
	$llms_path    = (string) wp_parse_url( home_url( '/llms.txt' ), PHP_URL_PATH );

	if ( untrailingslashit( rawurldecode( $request_path ) ) !== untrailingslashit( $llms_path ) ) {
		return;
	}

	status_header( 200 );
	header( 'Content-Type: text/plain; charset=UTF-8' );
	header( 'Cache-Control: public, max-age=3600, stale-while-revalidate=86400' );
	header( 'X-Content-Type-Options: nosniff' );

	$lines = array(
		'# InfoSecNexus',
		'',
		'> Practical cybersecurity briefings, vulnerability analysis, Linux and DevOps guidance, AI security news, and defensive checklists.',
		'',
		'InfoSecNexus publishes concise, source-aware security coverage for engineers, administrators, defenders, and technology teams.',
		'',
		'## Core pages',
		'',
		'- [Home](' . home_url( '/' ) . '): Latest featured cybersecurity coverage.',
		'- [About](' . home_url( '/about/' ) . '): Editorial purpose and coverage standards.',
		'- [Contact](' . home_url( '/contact/' ) . '): Corrections, security tips, and general enquiries.',
		'- [Privacy Policy](' . home_url( '/privacy-policy/' ) . '): Data and privacy practices.',
		'- [Terms and Conditions](' . home_url( '/terms-and-conditions/' ) . '): Terms governing site use.',
		'- [Disclaimer](' . home_url( '/disclaimer/' ) . '): Limits and context for published security information.',
		'- [XML Sitemap](' . home_url( '/wp-sitemap.xml' ) . '): Machine-readable content index.',
		'',
		'## Coverage',
		'',
	);

	$categories = get_categories(
		array(
			'hide_empty' => true,
			'number'     => 12,
			'orderby'    => 'count',
			'order'      => 'DESC',
		)
	);
	foreach ( $categories as $category ) {
		$lines[] = '- [' . markdown_text( $category->name ) . '](' . get_category_link( $category ) . ')';
	}

	$latest = get_posts(
		array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => 10,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		)
	);
	if ( ! empty( $latest ) ) {
		$lines[] = '';
		$lines[] = '## Latest briefings';
		$lines[] = '';
		foreach ( $latest as $post ) {
			$lines[] = '- [' . markdown_text( get_the_title( $post ) ) . '](' . get_permalink( $post ) . ')';
		}
	}

	$lines[] = '';
	$lines[] = '## Editorial note';
	$lines[] = '';
	$lines[] = 'Security information can change quickly. Confirm affected versions, exploitation status, and remediation guidance with the primary sources linked in each article before making operational decisions.';

	echo implode( "\n", $lines ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit;
}

/**
 * Escape link text for a Markdown document.
 *
 * @param string $text Raw text.
 */
function markdown_text( string $text ): string {
	$text = wp_strip_all_tags( $text, true );
	return str_replace( array( '\\', '[', ']', '(', ')' ), array( '\\\\', '\\[', '\\]', '\\(', '\\)' ), $text );
}
