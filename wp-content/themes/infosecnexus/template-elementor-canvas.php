<?php
/**
 * Template Name: Elementor Canvas
 * Template Post Type: page, post
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'infosecnexus-canvas' ); ?>>
<?php wp_body_open(); ?>
<main id="primary" class="site-main site-main--canvas">
	<?php
	while ( have_posts() ) {
		the_post();
		the_content();
	}
	?>
</main>
<?php wp_footer(); ?>
</body>
</html>
