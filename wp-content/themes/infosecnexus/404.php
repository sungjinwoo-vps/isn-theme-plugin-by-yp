<?php
/**
 * 404 template.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

get_header();
?>
<main id="primary" class="site-main">
	<section class="not-found">
		<?php \InfoSecNexus\Theme\Breadcrumbs\render(); ?>
		<h1><?php esc_html_e( 'Signal not found', 'infosecnexus' ); ?></h1>
		<p><?php esc_html_e( 'The requested page is unavailable. Try a search or return to the latest briefings.', 'infosecnexus' ); ?></p>
		<?php get_search_form(); ?>
	</section>
</main>
<?php
get_footer();
