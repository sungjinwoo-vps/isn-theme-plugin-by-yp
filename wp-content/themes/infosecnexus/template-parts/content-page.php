<?php
/**
 * Page content.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

$entry_id         = (int) get_the_ID();
$hide_title       = (bool) get_post_meta( $entry_id, '_infosecnexus_hide_title', true );
$page_slug        = (string) get_post_field( 'post_name', $entry_id );
$is_about         = 'about' === $page_slug;
$is_contact       = 'contact' === $page_slug;
$is_legal         = in_array( $page_slug, array( 'privacy-policy', 'terms-and-conditions', 'disclaimer' ), true );
$is_special       = $is_about || $is_contact || $is_legal;
$raw_content      = (string) get_post_field( 'post_content', $entry_id );
$page_intro       = $is_special ? \InfoSecNexus\Theme\Anime_Design\page_intro( $entry_id ) : '';
$page_hero_title  = $is_special ? \InfoSecNexus\Theme\Anime_Design\page_hero_title( $entry_id ) : '';
$display_content  = (string) get_the_content();
$display_content  = $is_special ? \InfoSecNexus\Theme\Anime_Design\without_seeded_page_hero( $display_content ) : $display_content;
$rendered_content = apply_filters( 'the_content', $display_content );
$rendered_content = $is_contact ? \InfoSecNexus\Theme\Anime_Design\ensure_secure_contact_form( $rendered_content ) : $rendered_content;
$rendered_content = \InfoSecNexus\Theme\Anime_Design\add_heading_ids( $rendered_content );
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'page-entry page-entry--' . sanitize_html_class( $page_slug ) . ( $is_legal ? ' page-entry--legal' : '' ) ); ?>>
	<?php if ( ! $hide_title || $is_special ) : ?>
		<header class="page-header page-header--editorial">
			<div class="page-header__copy">
				<?php \InfoSecNexus\Theme\Breadcrumbs\render(); ?>
				<p class="editorial-eyebrow"><?php echo esc_html( $is_special ? get_the_title() : __( 'InfoSecNexus', 'infosecnexus' ) ); ?></p>
				<h1><?php echo esc_html( '' !== $page_hero_title ? $page_hero_title : get_the_title() ); ?></h1>
				<?php if ( '' !== $page_intro ) : ?>
					<p><?php echo esc_html( $page_intro ); ?></p>
				<?php endif; ?>
			</div>
			<?php if ( $is_about || $is_contact ) : ?>
				<div class="page-header__media" aria-hidden="true">
					<?php if ( has_post_thumbnail() ) : ?>
						<?php
						the_post_thumbnail(
							'infosecnexus-hero',
							array(
								'alt'           => '',
								'loading'       => 'eager',
								'fetchpriority' => 'high',
								'decoding'      => 'async',
								'sizes'         => '(max-width: 860px) calc(100vw - 32px), 520px',
							)
						);
						?>
					<?php else : ?>
						<?php
						// The helper returns complete image markup with escaped attributes.
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						echo \InfoSecNexus\Theme\Anime_Design\asset_image(
							'team',
							array(
								'alt'           => '',
								'loading'       => 'eager',
								'fetchpriority' => 'high',
								'sizes'         => '(max-width: 860px) calc(100vw - 32px), 520px',
							)
						);
						?>
					<?php endif; ?>
				</div>
			<?php elseif ( $is_legal ) : ?>
				<div class="page-header__legal-mark" aria-hidden="true"><span></span></div>
			<?php endif; ?>
		</header>
	<?php endif; ?>
	<?php if ( $is_legal ) : ?>
		<div class="legal-layout">
			<aside class="legal-layout__rail" aria-label="<?php esc_attr_e( 'On this page', 'infosecnexus' ); ?>">
				<?php \InfoSecNexus\Theme\Template_Tags\table_of_contents( $raw_content ); ?>
			</aside>
			<div class="entry-content legal-layout__content" data-enhance-headings>
				<?php echo $rendered_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</div>
	<?php else : ?>
		<div class="entry-content" data-enhance-headings>
			<?php echo $rendered_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
	<?php endif; ?>
</article>
