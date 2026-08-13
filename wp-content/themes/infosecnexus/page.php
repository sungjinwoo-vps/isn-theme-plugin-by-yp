<?php
/**
 * Page template.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

get_header();
?>
<main id="primary" class="site-main">
	<?php while ( have_posts() ) : ?>
		<?php the_post(); ?>
		<?php get_template_part( 'template-parts/content', 'page' ); ?>
	<?php endwhile; ?>
</main>
<?php
get_footer();
