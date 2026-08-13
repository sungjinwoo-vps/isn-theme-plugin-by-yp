<?php
/**
 * Search template.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

get_header();
?>
<main id="primary" class="site-main">
	<header class="archive-masthead archive-masthead--search layout-wide-shell">
		<div class="archive-masthead__copy">
			<?php \InfoSecNexus\Theme\Breadcrumbs\render(); ?>
			<p class="editorial-eyebrow"><?php esc_html_e( 'Search the newsroom', 'infosecnexus' ); ?></p>
			<h1>
				<?php
				printf(
					/* translators: %s: search query. */
					esc_html__( 'Results for "%s"', 'infosecnexus' ),
					esc_html( get_search_query() )
				);
				?>
			</h1>
			<p><?php esc_html_e( 'Only published InfoSecNexus blog posts and security briefings are included.', 'infosecnexus' ); ?></p>
			<?php get_search_form(); ?>
		</div>
		<div class="archive-masthead__media" aria-hidden="true">
			<?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The image helper returns escaped theme-owned markup.
			echo \InfoSecNexus\Theme\Anime_Design\asset_image(
				'ai',
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
				<header class="listing-header"><h2><?php esc_html_e( 'Matching briefings', 'infosecnexus' ); ?></h2></header>
				<div class="post-grid post-grid--archive">
					<?php
					while ( have_posts() ) :
						the_post();
						\InfoSecNexus\Theme\Template_Tags\post_card( 'list' );
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
