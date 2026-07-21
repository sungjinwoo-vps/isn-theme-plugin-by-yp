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
	<section class="not-found not-found--cyber">
		<?php \InfoSecNexus\Theme\Breadcrumbs\render(); ?>
		<div class="not-found__panel">
			<div class="not-found__code" aria-hidden="true">404</div>
			<div class="not-found__content">
				<p class="not-found__eyebrow"><?php esc_html_e( 'Lost signal', 'infosecnexus' ); ?></p>
				<h1><?php esc_html_e( 'This briefing is not available.', 'infosecnexus' ); ?></h1>
				<p><?php esc_html_e( 'The URL may have changed, the content may have moved, or the request may be pointing at a retired page. Search current blog posts or jump back into the main security topics.', 'infosecnexus' ); ?></p>
				<?php get_search_form(); ?>
				<div class="not-found__links">
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Latest Briefings', 'infosecnexus' ); ?></a>
					<a href="<?php echo esc_url( \InfoSecNexus\Theme\Header_Builder\category_url( 'critical-cves' ) ); ?>"><?php esc_html_e( 'Critical CVEs', 'infosecnexus' ); ?></a>
					<a href="<?php echo esc_url( \InfoSecNexus\Theme\Header_Builder\category_url( 'cybersecurity' ) ); ?>"><?php esc_html_e( 'Cyber Security', 'infosecnexus' ); ?></a>
				</div>
			</div>
		</div>
	</section>
</main>
<?php
get_footer();
