<?php
/**
 * Accessible whole-link post card.
 *
 * @package InfoSecNexus
 *
 * @var array<string,mixed> $args Template arguments.
 */

declare(strict_types=1);

$args         = isset( $args ) && is_array( $args ) ? $args : array();
$card_post_id = isset( $args['post_id'] ) ? absint( $args['post_id'] ) : (int) get_the_ID();
$variant      = isset( $args['variant'] ) ? sanitize_html_class( (string) $args['variant'] ) : 'grid';
$heading      = isset( $args['heading'] ) && in_array( (string) $args['heading'], array( 'h2', 'h3' ), true ) ? (string) $args['heading'] : 'h2';
$card_post    = get_post( $card_post_id );

if ( ! $card_post instanceof \WP_Post ) {
	return;
}

$date       = \InfoSecNexus\Theme\Template_Tags\post_date_data( $card_post_id );
$categories = get_the_category( $card_post_id );
$excerpt    = has_excerpt( $card_post_id ) ? get_the_excerpt( $card_post ) : wp_strip_all_tags( (string) $card_post->post_content );
$critical   = \InfoSecNexus\Theme\Anime_Design\is_critical_post( $card_post_id );
$classes    = get_post_class( 'post-card post-card--' . $variant . ( $critical ? ' post-card--critical' : '' ), $card_post_id );
?>
<article id="post-<?php echo esc_attr( (string) $card_post_id ); ?>" class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
	<a class="post-card__link" href="<?php echo esc_url( get_permalink( $card_post_id ) ); ?>">
		<div class="post-card__media">
			<?php
			// The helper returns complete image markup with escaped attributes.
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo \InfoSecNexus\Theme\Anime_Design\post_image(
				$card_post_id,
				array(
					'class' => 'post-card__image isn-editorial-image',
					'sizes' => '(max-width: 760px) calc(100vw - 32px), (max-width: 1180px) 31vw, 390px',
				),
				'medium_large'
			);
			?>
		</div>
		<div class="post-card__body">
			<?php if ( ! empty( $categories ) ) : ?>
				<div class="post-card__topics" aria-label="<?php esc_attr_e( 'Topics', 'infosecnexus' ); ?>">
					<?php foreach ( array_slice( $categories, 0, 2 ) as $category ) : ?>
						<span class="severity-chip<?php echo 'critical-cves' === $category->slug ? ' severity-chip--critical' : ''; ?>"><?php echo esc_html( $category->name ); ?></span>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<<?php echo esc_attr( $heading ); ?> class="post-card__title"><?php echo esc_html( get_the_title( $card_post_id ) ); ?></<?php echo esc_attr( $heading ); ?>>
			<p class="post-card__excerpt"><?php echo esc_html( wp_trim_words( $excerpt, 'compact' === $variant ? 16 : 28, '...' ) ); ?></p>
			<div class="entry-meta post-card__meta">
				<time datetime="<?php echo esc_attr( $date['datetime'] ); ?>"><?php echo esc_html( $date['label'] ); ?></time>
				<span aria-hidden="true">&middot;</span>
				<?php
				printf(
					/* translators: %d: estimated reading time in minutes. */
					esc_html( _n( '%d min read', '%d min read', \InfoSecNexus\Theme\Template_Tags\reading_time( $card_post_id ), 'infosecnexus' ) ),
					(int) \InfoSecNexus\Theme\Template_Tags\reading_time( $card_post_id )
				);
				?>
			</div>
			<span class="post-card__cta"><?php esc_html_e( 'Read briefing', 'infosecnexus' ); ?><span class="post-card__arrow" aria-hidden="true">&rarr;</span></span>
		</div>
	</a>
</article>
