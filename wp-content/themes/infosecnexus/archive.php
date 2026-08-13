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

$queried     = get_queried_object();
$archive_key = $queried instanceof WP_Term ? \InfoSecNexus\Theme\Anime_Design\term_asset_key( $queried->slug ) : 'hero';
$description = get_the_archive_description();
?>
<main id="primary" class="site-main">
	<header class="archive-masthead layout-wide-shell">
		<div class="archive-masthead__copy">
			<?php \InfoSecNexus\Theme\Breadcrumbs\render(); ?>
			<p class="editorial-eyebrow"><?php esc_html_e( 'Security intelligence', 'infosecnexus' ); ?></p>
			<h1><?php echo esc_html( \InfoSecNexus\Theme\Breadcrumbs\archive_label() ); ?></h1>
			<?php if ( $description ) : ?>
				<div class="archive-description"><?php echo wp_kses_post( $description ); ?></div>
			<?php else : ?>
				<p><?php esc_html_e( 'Current analysis, verified advisories, and practical defensive guidance from the InfoSecNexus newsroom.', 'infosecnexus' ); ?></p>
			<?php endif; ?>
		</div>
		<div class="archive-masthead__media" aria-hidden="true">
			<?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The image helper returns escaped theme-owned markup.
			echo \InfoSecNexus\Theme\Anime_Design\asset_image(
				$archive_key,
				array(
					'alt'           => '',
					'loading'       => 'eager',
					'fetchpriority' => 'high',
					'sizes'         => '(max-width: 860px) calc(100vw - 32px), 480px',
				)
			);
			?>
		</div>
	</header>
	<div class="layout-shell">
		<section class="content-area">
			<?php if ( have_posts() ) : ?>
				<header class="listing-header"><h2><?php esc_html_e( 'Latest briefings', 'infosecnexus' ); ?></h2></header>
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
