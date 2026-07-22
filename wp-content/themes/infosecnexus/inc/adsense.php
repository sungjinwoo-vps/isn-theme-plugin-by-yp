<?php
/**
 * AdSense placement helpers.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

namespace InfoSecNexus\Theme\AdSense;

use function InfoSecNexus\Theme\Toolkit\option;

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
	return preg_match( '/^ca-pub-\d{10,}$/', $client ) ? $client : '';
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
 * Output the official AdSense loader once when a configured slot can render.
 */
function render_adsense_script(): void {
	if ( ! enabled() ) {
		return;
	}

	$has_slot = slot_id( 'adsense_left_slot' ) || slot_id( 'adsense_right_slot' ) || slot_id( 'adsense_inarticle_slot' );
	if ( ! $has_slot ) {
		return;
	}

	echo '<script async src="' . esc_url( 'https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=' . rawurlencode( client_id() ) ) . '" crossorigin="anonymous"></script>' . "\n";
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
		echo '<section class="post-gate__ad" aria-label="' . esc_attr__( 'Advertisement', 'infosecnexus' ) . '">';
		render_slot( 'inarticle', $slot, __( 'Advertisement', 'infosecnexus' ) );
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
 * Split post content at a more tag or after the opening paragraphs.
 *
 * @param string $content Raw post content.
 * @return array{teaser:string,rest:string}
 */
function split_post_content( string $content ): array {
	if ( preg_match( '/<!--more(.*?)?-->/', $content ) ) {
		$parts = get_extended( $content );
		return array(
			'teaser' => trim( (string) $parts['main'] ),
			'rest'   => trim( (string) $parts['extended'] ),
		);
	}

	$blocks = parse_blocks( $content );
	if ( empty( $blocks ) ) {
		return array(
			'teaser' => $content,
			'rest'   => '',
		);
	}

	$teaser = array();
	$rest   = array();
	$count  = 0;
	foreach ( $blocks as $block ) {
		$target = $count < 2 ? 'teaser' : 'rest';
		if ( 'core/paragraph' === (string) ( $block['blockName'] ?? '' ) ) {
			++$count;
		}
		if ( 'teaser' === $target ) {
			$teaser[] = serialize_block( $block );
		} else {
			$rest[] = serialize_block( $block );
		}
	}

	return array(
		'teaser' => trim( implode( '', $teaser ) ),
		'rest'   => trim( implode( '', $rest ) ),
	);
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
