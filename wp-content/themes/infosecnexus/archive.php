<?php
/**
 * Archive template.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

get_header();

if ( \InfoSecNexus\Theme\Elementor\render_location( 'archive' ) ) {
	get_footer();
	return;
}
?>
<main id="primary" class="site-main">
	<div class="layout-shell">
		<section class="content-area">
			<header class="archive-header">
				<?php \InfoSecNexus\Theme\Breadcrumbs\render(); ?>
				<h1><?php the_archive_title(); ?></h1>
				<?php the_archive_description( '<div class="archive-description">', '</div>' ); ?>
			</header>
			<?php if ( have_posts() ) : ?>
				<div class="post-grid post-grid--archive">
					<?php
					while ( have_posts() ) :
						the_post();
						\InfoSecNexus\Theme\Template_Tags\post_card();
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
