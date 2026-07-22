<?php
/**
 * AdSense placement helpers.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

namespace InfoSecNexus\Theme\AdSense;

use function InfoSecNexus\Theme\Toolkit\option;

const DEFAULT_CLIENT_ID = 'ca-pub-6550916382964760';

/**
 * Register AdSense hooks.
 */
function bootstrap(): void {
	add_action( 'wp_head', __NAMESPACE__ . '\\render_adsense_script' );
	add_action( 'wp_footer', __NAMESPACE__ . '\\render_side_rails' );
}

/**
 * Whether AdSense rendering is enabled.
 */
function enabled(): bool {
	return (bool) option( 'adsense_enabled', false ) && '' !== client_id();
}

/**
 * Return the configured AdSense client ID.
 */
function client_id(): string {
	$client = (string) option( 'adsense_client', '' );
	return preg_match( '/^ca-pub-\d{10,}$/', $client ) ? $client : DEFAULT_CLIENT_ID;
}

/**
 * Return one configured slot ID.
 *
 * @param string $key Option key.
 */
function slot_id( string $key ): string {
	$slot = (string) option( $key, '' );
	return preg_match( '/^\d{5,}$/', $slot ) ? $slot : '';
}

/**
 * Whether the current template should show side ads.
 */
function should_show_side_rails(): bool {
	if ( is_admin() || ! enabled() || ! (bool) option( 'adsense_side_rails', true ) ) {
		return false;
	}

	return is_front_page() || is_home() || is_archive() || is_search() || is_singular( 'post' );
}

/**
 * Output the official AdSense loader for Auto ads and manual slots.
 */
function render_adsense_script(): void {
	$client = client_id();
	if ( '' === $client ) {
		return;
	}

	echo '<script async src="' . esc_url( 'https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=' . rawurlencode( $client ) ) . '" crossorigin="anonymous"></script>' . "\n";
}

/**
 * Render sticky desktop ad rails.
 */
function render_side_rails(): void {
	if ( ! should_show_side_rails() ) {
		return;
	}

	$left_slot  = slot_id( 'adsense_left_slot' );
	$right_slot = slot_id( 'adsense_right_slot' );
	if ( '' === $left_slot && '' === $right_slot ) {
		return;
	}

	echo '<aside class="isnx-side-ads" aria-label="' . esc_attr__( 'Advertisements', 'infosecnexus' ) . '">';
	if ( '' !== $left_slot ) {
		render_slot( 'side-left', $left_slot, __( 'Advertisement', 'infosecnexus' ) );
	}
	if ( '' !== $right_slot ) {
		render_slot( 'side-right', $right_slot, __( 'Advertisement', 'infosecnexus' ) );
	}
	echo '</aside>';
}

/**
 * Render a post content teaser, in-article ad, and unlock button.
 */
function render_gated_post_content(): void {
	if ( ! is_singular( 'post' ) ) {
		echo '<div class="entry-content" data-enhance-headings>';
		the_content();
		echo '</div>';
		return;
	}

	$post = get_post();
	if ( ! $post ) {
		the_content();
		return;
	}

	$parts = split_post_content( (string) $post->post_content );
	if ( '' === $parts['rest'] ) {
		echo '<div class="entry-content" data-enhance-headings>';
		the_content();
		echo '</div>';
		return;
	}

	echo '<div class="entry-content entry-content--gated" data-enhance-headings data-post-unlock>';
	echo '<div class="post-gate__teaser">' . apply_filters( 'the_content', $parts['teaser'] ) . '</div>';

	$slot       = slot_id( 'adsense_inarticle_slot' );
	$has_ad_gap = enabled() && '' !== $slot && (bool) option( 'adsense_post_gate', true );
	if ( $has_ad_gap ) {
		echo '<section class="post-gate__ad" data-post-gate-ad data-ad-client="' . esc_attr( client_id() ) . '" data-ad-slot="' . esc_attr( $slot ) . '" aria-label="' . esc_attr__( 'Advertisement', 'infosecnexus' ) . '" hidden>';
		echo '<div class="isnx-ad-slot isnx-ad-slot--inarticle">';
		echo '<span class="isnx-ad-slot__label">' . esc_html__( 'Advertisement', 'infosecnexus' ) . '</span>';
		echo '<div data-post-gate-ad-inner></div>';
		echo '</div>';
		echo '</section>';
	}

	echo '<div class="post-gate__unlock">';
	echo '<p>' . esc_html( $has_ad_gap ? __( 'Continue reading the full briefing after this sponsor space.', 'infosecnexus' ) : __( 'Continue reading the full briefing.', 'infosecnexus' ) ) . '</p>';
	echo '<button class="button post-gate__button" type="button" data-post-unlock-button>' . esc_html__( 'Read More', 'infosecnexus' ) . ' <span aria-hidden="true">-></span></button>';
	echo '</div>';
	echo '<div class="post-gate__rest" data-post-unlock-content hidden>' . apply_filters( 'the_content', $parts['rest'] ) . '</div>';
	echo '</div>';
}

/**
 * Split post content around the first substantial third of the article.
 *
 * @param string $content Raw post content.
 * @return array{teaser:string,rest:string}
 */
function split_post_content( string $content ): array {
	$content = preg_replace( '/<!--more(.*?)?-->/', '', $content );
	$content = is_string( $content ) ? trim( $content ) : '';
	$blocks = parse_blocks( $content );
	$chunks = has_structured_blocks( $blocks ) ? chunks_from_blocks( $blocks ) : chunks_from_classic_html( $content );

	return split_chunks_near_gate( $chunks, $content );
}

/**
 * Whether parsed blocks contain real block structure.
 *
 * @param array<int,array<string,mixed>> $blocks Parsed blocks.
 */
function has_structured_blocks( array $blocks ): bool {
	foreach ( $blocks as $block ) {
		if ( null !== ( $block['blockName'] ?? null ) ) {
			return true;
		}
	}

	return count( $blocks ) > 1;
}

/**
 * Convert Gutenberg blocks into split chunks.
 *
 * @param array<int,array<string,mixed>> $blocks Parsed blocks.
 * @return string[]
 */
function chunks_from_blocks( array $blocks ): array {
	$chunks = array();
	foreach ( $blocks as $block ) {
		if ( 'core/more' === (string) ( $block['blockName'] ?? '' ) ) {
			continue;
		}
		$html = trim( serialize_block( $block ) );
		if ( '' !== $html ) {
			$chunks[] = $html;
		}
	}

	return $chunks;
}

/**
 * Convert classic HTML into top-level readable chunks.
 *
 * @param string $content Raw post content.
 * @return string[]
 */
function chunks_from_classic_html( string $content ): array {
	$chunks = array();
	if ( preg_match_all( '/<(h[2-6]|p|ul|ol|figure|blockquote|table|pre)\b[^>]*>.*?<\/\1>/is', $content, $matches, PREG_OFFSET_CAPTURE ) ) {
		$cursor = 0;
		foreach ( $matches[0] as $match ) {
			$html   = $match[0];
			$offset = (int) $match[1];
			if ( $offset > $cursor ) {
				$before = trim( substr( $content, $cursor, $offset - $cursor ) );
				if ( '' !== $before ) {
					$chunks[] = $before;
				}
			}
			$chunks[] = trim( $html );
			$cursor   = $offset + strlen( $html );
		}

		$after = trim( substr( $content, $cursor ) );
		if ( '' !== $after ) {
			$chunks[] = $after;
		}
	}

	if ( empty( $chunks ) ) {
		$chunks = preg_split( '/\n\s*\n/', $content );
	}

	return array_values( array_filter( array_map( 'trim', (array) $chunks ) ) );
}

/**
 * Split chunks at the closest sensible boundary before the article midpoint.
 *
 * @param string[] $chunks  Content chunks.
 * @param string   $content Original content fallback.
 * @return array{teaser:string,rest:string}
 */
function split_chunks_near_gate( array $chunks, string $content ): array {
	if ( count( $chunks ) < 3 ) {
		return array(
			'teaser' => $content,
			'rest'   => '',
		);
	}

	$weights = array_map( __NAMESPACE__ . '\\chunk_word_count', $chunks );
	$total   = array_sum( $weights );
	if ( $total < 260 ) {
		return array(
			'teaser' => $content,
			'rest'   => '',
		);
	}

	$target      = max( 150, (int) round( $total * 0.36 ) );
	$minimum     = (int) round( $total * 0.24 );
	$maximum     = (int) round( $total * 0.48 );
	$running     = 0;
	$split_index = 0;
	$best_score  = PHP_INT_MAX;

	foreach ( $chunks as $index => $chunk ) {
		$running += $weights[ $index ];
		if ( $running < $minimum || $running > $maximum ) {
			continue;
		}

		$candidate = $index + 1;
		if ( $candidate >= count( $chunks ) ) {
			continue;
		}

		while ( $candidate < count( $chunks ) && chunk_is_heading( $chunks[ $candidate - 1 ] ) ) {
			++$candidate;
		}

		$score = abs( $running - $target );
		if ( $score < $best_score ) {
			$best_score  = $score;
			$split_index = $candidate;
		}
	}

	if ( 0 === $split_index ) {
		$split_index = max( 1, (int) floor( count( $chunks ) * 0.38 ) );
	}

	$teaser = trim( implode( '', array_slice( $chunks, 0, $split_index ) ) );
	$rest   = trim( implode( '', array_slice( $chunks, $split_index ) ) );
	if ( chunk_word_count( $teaser ) < 120 || chunk_word_count( $rest ) < 180 ) {
		return array(
			'teaser' => $content,
			'rest'   => '',
		);
	}

	return array(
		'teaser' => $teaser,
		'rest'   => $rest,
	);
}

/**
 * Count readable words in a chunk.
 *
 * @param string $chunk HTML chunk.
 */
function chunk_word_count( string $chunk ): int {
	return str_word_count( wp_strip_all_tags( strip_shortcodes( $chunk ) ) );
}

/**
 * Whether a chunk is only a heading.
 *
 * @param string $chunk HTML chunk.
 */
function chunk_is_heading( string $chunk ): bool {
	return (bool) preg_match( '/^\s*<h[2-6]\b/i', $chunk );
}

/**
 * Render one AdSense slot.
 *
 * @param string $position Slot position.
 * @param string $slot     Ad slot ID.
 * @param string $label    Visible label.
 */
function render_slot( string $position, string $slot, string $label ): void {
	$client = client_id();
	if ( '' === $client || '' === $slot ) {
		return;
	}

	$format = str_starts_with( $position, 'side' ) ? 'vertical' : 'fluid';

	echo '<div class="isnx-ad-slot isnx-ad-slot--' . esc_attr( $position ) . '">';
	echo '<span class="isnx-ad-slot__label">' . esc_html( $label ) . '</span>';
	echo '<ins class="adsbygoogle" style="display:block" data-ad-client="' . esc_attr( $client ) . '" data-ad-slot="' . esc_attr( $slot ) . '" data-ad-format="' . esc_attr( $format ) . '" data-full-width-responsive="true"></ins>';
	echo '<script>(adsbygoogle = window.adsbygoogle || []).push({});</script>';
	echo '</div>';
}
