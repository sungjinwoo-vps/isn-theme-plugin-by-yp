<?php
/**
 * Template Name: Elementor Full Width
 * Template Post Type: page, post
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

get_header();
while ( have_posts() ) {
	the_post();
	echo '<main id="primary" class="site-main site-main--full-width"><div class="entry-content">';
	the_content();
	echo '</div></main>';
}
get_footer();
