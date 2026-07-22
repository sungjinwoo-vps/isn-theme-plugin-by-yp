<?php
/**
 * Single post template.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

get_header();

if ( \InfoSecNexus\Theme\Elementor\render_location( 'single' ) ) {
	get_footer();
	return;
}
?>
<main id="primary" class="site-main">
	<?php while ( have_posts() ) : ?>
		<?php the_post(); ?>
		<article id="post-<?php the_ID(); ?>" <?php post_class( 'single-entry' ); ?>>
			<header class="single-hero">
				<div class="single-hero__inner">
					<?php \InfoSecNexus\Theme\Breadcrumbs\render(); ?>
					<?php \InfoSecNexus\Theme\Template_Tags\category_badges(); ?>
					<h1><?php the_title(); ?></h1>
					<?php \InfoSecNexus\Theme\Template_Tags\post_meta(); ?>
				</div>
				<div class="single-hero__media">
					<?php if ( has_post_thumbnail() ) : ?>
						<?php the_post_thumbnail( 'large', array( 'loading' => 'eager', 'fetchpriority' => 'high', 'decoding' => 'async', 'sizes' => '(max-width: 1000px) calc(100vw - 32px), 920px' ) ); ?>
					<?php else : ?>
						<?php echo \InfoSecNexus\Theme\Template_Tags\fallback_image( null, array( 'loading' => 'eager', 'fetchpriority' => 'high', 'sizes' => '(max-width: 1000px) calc(100vw - 32px), 920px' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php endif; ?>
				</div>
			</header>
			<div class="layout-shell layout-shell--single">
				<aside class="single-rail">
					<?php \InfoSecNexus\Theme\Template_Tags\table_of_contents(); ?>
					<?php \InfoSecNexus\Theme\Template_Tags\social_share(); ?>
				</aside>
				<div class="entry-content-wrap">
					<?php \InfoSecNexus\Theme\Template_Tags\featured_video(); ?>
					<?php \InfoSecNexus\Theme\AdSense\render_gated_post_content(); ?>
					<?php
					wp_link_pages(
						array(
							'before' => '<nav class="page-links" aria-label="' . esc_attr__( 'Post pages', 'infosecnexus' ) . '">',
							'after'  => '</nav>',
						)
					);
					?>
					<?php if ( function_exists( 'infosecnexus_toolkit_newsletter_form' ) ) : ?>
						<?php infosecnexus_toolkit_newsletter_form(); ?>
					<?php endif; ?>
					<footer class="entry-footer">
						<?php the_tags( '<div class="tag-links">', '', '</div>' ); ?>
					</footer>
					<?php \InfoSecNexus\Theme\Template_Tags\related_posts(); ?>
					<?php the_post_navigation(); ?>
					<?php \InfoSecNexus\Theme\Template_Tags\contact_cta(); ?>
				</div>
			</div>
		</article>
	<?php endwhile; ?>
</main>
<?php
get_footer();
