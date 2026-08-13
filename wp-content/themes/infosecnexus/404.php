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
			<div class="not-found__content">
				<p class="not-found__eyebrow"><?php esc_html_e( 'Lost signal', 'infosecnexus' ); ?></p>
				<div class="not-found__code" aria-hidden="true">404</div>
				<h1><?php esc_html_e( 'The signal dropped before this page arrived.', 'infosecnexus' ); ?></h1>
				<p><?php esc_html_e( 'The URL may have changed, the content may have moved, or the request may be pointing at a retired page. Search current blog posts or jump back into the main security topics.', 'infosecnexus' ); ?></p>
				<?php get_search_form(); ?>
				<div class="not-found__links">
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Latest Briefings', 'infosecnexus' ); ?></a>
					<a href="<?php echo esc_url( \InfoSecNexus\Theme\Header_Builder\category_url( 'critical-cves' ) ); ?>"><?php esc_html_e( 'Critical CVEs', 'infosecnexus' ); ?></a>
					<a href="<?php echo esc_url( \InfoSecNexus\Theme\Header_Builder\category_url( 'cybersecurity' ) ); ?>"><?php esc_html_e( 'Cyber Security', 'infosecnexus' ); ?></a>
				</div>
			</div>
			<div class="not-found__visual" aria-hidden="true">
				<?php
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The image helper returns escaped theme-owned markup.
				echo \InfoSecNexus\Theme\Anime_Design\asset_image(
					'hero',
					array(
						'alt'           => '',
						'loading'       => 'eager',
						'fetchpriority' => 'high',
						'sizes'         => '(max-width: 760px) calc(100vw - 32px), 520px',
					)
				);
				?>
				<span class="not-found__scan"></span>
			</div>
		</div>
	</section>
</main>
<?php
get_footer();
