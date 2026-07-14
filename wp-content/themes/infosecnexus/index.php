<?php
/**
 * Main index template.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

get_header();
?>
<main id="primary" class="site-main">
	<div class="layout-shell">
		<section class="content-area">
			<?php if ( have_posts() ) : ?>
				<header class="archive-header">
					<h1><?php single_post_title(); ?></h1>
				</header>
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
