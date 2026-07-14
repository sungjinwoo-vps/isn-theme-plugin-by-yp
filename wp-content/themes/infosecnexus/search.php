<?php
/**
 * Search template.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

get_header();
?>
<main id="primary" class="site-main">
	<div class="layout-shell">
		<section class="content-area">
			<header class="archive-header">
				<?php \InfoSecNexus\Theme\Breadcrumbs\render(); ?>
				<h1>
				<?php
				printf(
					/* translators: %s: search query. */
					esc_html__( 'Search results for: %s', 'infosecnexus' ),
					'<span>' . esc_html( get_search_query() ) . '</span>'
				);
				?>
				</h1>
				<?php get_search_form(); ?>
			</header>
			<?php if ( have_posts() ) : ?>
				<div class="post-grid post-grid--archive">
					<?php
					while ( have_posts() ) :
						the_post();
						\InfoSecNexus\Theme\Template_Tags\post_card( 'list' );
					endwhile;
					?>
				</div>
				<?php \InfoSecNexus\Theme\Template_Tags\pagination(); ?>
			<?php else : ?>
				<?php get_template_part( 'template-parts/content', 'none' ); ?>
			<?php endif; ?>
		</section>
		<?php get_sidebar(); ?>
	</div>
</main>
<?php
get_footer();
