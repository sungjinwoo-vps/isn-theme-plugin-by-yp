<?php
/**
 * Public author profile and existing article archive.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

get_header();
if ( \InfoSecNexus\Theme\Elementor\render_location( 'archive' ) ) {
	get_footer();
	return;
}
$profile_author = get_queried_object();
$profile_name = $profile_author instanceof WP_User ? $profile_author->display_name : __( 'InfoSecNexus author', 'infosecnexus' );
$profile_bio = $profile_author instanceof WP_User ? trim( (string) get_the_author_meta( 'description', (int) $profile_author->ID ) ) : '';
?>
<main id="primary" class="site-main author-profile">
	<header class="archive-masthead layout-wide-shell author-profile__header">
		<div class="archive-masthead__copy">
			<?php \InfoSecNexus\Theme\Breadcrumbs\render(); ?>
			<p class="editorial-eyebrow"><?php esc_html_e( 'Author', 'infosecnexus' ); ?></p>
			<h1><?php echo esc_html( $profile_name ); ?></h1>
			<?php if ( '' !== $profile_bio ) : ?>
				<div class="archive-description"><?php echo wp_kses_post( wpautop( $profile_bio ) ); ?></div>
			<?php else : ?>
				<p><?php /* translators: %s: public author name. */ echo esc_html( sprintf( __( 'Articles published under %s\'s byline on InfoSecNexus.', 'infosecnexus' ), $profile_name ) ); ?></p>
			<?php endif; ?>
		</div>
	</header>
	<div class="layout-wide-shell author-profile__context">
		<section>
			<h2><?php esc_html_e( 'Sources and context', 'infosecnexus' ); ?></h2>
			<p><?php esc_html_e( 'Briefings link to vendor advisories, vulnerability records and government notices where available. Those links identify the evidence behind affected versions, severity and reported exploitation.', 'infosecnexus' ); ?></p>
			<p><?php esc_html_e( 'An advisory summary does not establish that your installation is affected. Check product, version and configuration details against the original sources.', 'infosecnexus' ); ?></p>
		</section>
		<section>
			<h2><?php esc_html_e( 'Corrections and source updates', 'infosecnexus' ); ?></h2>
			<p><?php esc_html_e( 'If a briefing needs a correction, send its URL, the detail in question and a supporting source. Article update history records substantive revisions when available.', 'infosecnexus' ); ?></p>
			<a class="button button--secondary" href="<?php echo esc_url( \InfoSecNexus\Theme\Header_Builder\page_url( 'contact' ) ); ?>"><?php esc_html_e( 'Send a correction', 'infosecnexus' ); ?></a>
		</section>
	</div>
	<div class="layout-shell">
		<section class="content-area">
			<header class="listing-header"><h2><?php esc_html_e( 'Published briefings', 'infosecnexus' ); ?></h2></header>
			<?php if ( have_posts() ) : ?>
				<div class="post-grid post-grid--archive">
					<?php while ( have_posts() ) : the_post(); ?>
						<?php \InfoSecNexus\Theme\Template_Tags\post_card(); ?>
					<?php endwhile; ?>
				</div>
				<?php \InfoSecNexus\Theme\Template_Tags\pagination(); ?>
			<?php else : ?>
				<?php get_template_part( 'template-parts/content', 'none' ); ?>
			<?php endif; ?>
		</section>
		<?php get_sidebar(); ?>
	</div>
</main>
<?php get_footer(); ?>
