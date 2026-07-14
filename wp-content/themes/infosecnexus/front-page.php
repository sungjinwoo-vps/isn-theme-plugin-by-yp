<?php
/**
 * Front page template.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

get_header();
?>
<main id="primary" class="site-main">
	<?php
	if ( have_posts() ) {
		while ( have_posts() ) {
			the_post();
			if ( trim( get_the_content() ) ) {
				echo '<div class="front-page-content entry-content">';
				the_content();
				echo '</div>';
			}
		}
	}
	get_template_part( 'template-parts/home', 'sections' );
	?>
</main>
<?php
get_footer();
