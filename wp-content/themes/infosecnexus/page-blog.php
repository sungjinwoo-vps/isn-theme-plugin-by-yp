<?php
/**
 * Cybersecurity blog hub.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

get_header();

$paged   = max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );
$rolling = \InfoSecNexus\Theme\Template_Tags\rolling_brief_post();
$topics  = array(
	'cybersecurity'           => __( 'Cyber Security', 'infosecnexus' ),
	'critical-cves'           => __( 'Critical CVEs', 'infosecnexus' ),
	'linux-administration'    => __( 'Linux & Kernel', 'infosecnexus' ),
	'devops'                  => __( 'DevSecOps', 'infosecnexus' ),
	'artificial-intelligence' => __( 'AI Security', 'infosecnexus' ),
	'cloud-security'          => __( 'Cloud Security', 'infosecnexus' ),
	'web-security'            => __( 'Web Security', 'infosecnexus' ),
	'windows-security'        => __( 'Windows Security', 'infosecnexus' ),
	'network-security'        => __( 'Network Security', 'infosecnexus' ),
	'tutorials'               => __( 'Security Tutorials', 'infosecnexus' ),
);

$latest = new WP_Query(
	array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => 12,
		'paged'               => $paged,
		'post__not_in'        => $rolling instanceof WP_Post ? array( $rolling->ID ) : array(),
		'ignore_sticky_posts' => true,
	)
);
?>
<main id="primary" class="site-main blog-hub">
	<header class="archive-masthead layout-wide-shell blog-hub__masthead">
		<div class="archive-masthead__copy">
			<?php \InfoSecNexus\Theme\Breadcrumbs\render(); ?>
			<p class="editorial-eyebrow"><?php esc_html_e( 'InfoSecNexus newsroom', 'infosecnexus' ); ?></p>
			<h1><?php esc_html_e( 'Cybersecurity briefings and vulnerability analysis', 'infosecnexus' ); ?></h1>
			<p><?php esc_html_e( 'Follow current threats, critical CVEs, vendor advisories, and practical guidance for security, infrastructure, and engineering teams.', 'infosecnexus' ); ?></p>
		</div>
		<div class="archive-masthead__media" aria-hidden="true">
			<?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The helper returns escaped theme-owned markup.
			echo \InfoSecNexus\Theme\Anime_Design\asset_image(
				'hero',
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

	<div class="layout-wide-shell blog-hub__body">
		<nav class="blog-topic-nav" aria-labelledby="blog-topic-heading">
			<div class="listing-header">
				<h2 id="blog-topic-heading"><?php esc_html_e( 'Explore security topics', 'infosecnexus' ); ?></h2>
			</div>
			<ul>
				<?php foreach ( $topics as $slug => $label ) : ?>
					<?php $term = get_category_by_slug( $slug ); ?>
					<?php if ( ! $term instanceof WP_Term ) : ?>
						<?php continue; ?>
					<?php endif; ?>
					<li>
						<a href="<?php echo esc_url( get_category_link( $term ) ); ?>">
							<span><?php echo esc_html( $label ); ?></span>
							<span class="blog-topic-nav__count" aria-label="<?php echo esc_attr( sprintf( _n( '%d published briefing', '%d published briefings', (int) $term->count, 'infosecnexus' ), (int) $term->count ) ); ?>"><?php echo esc_html( number_format_i18n( (int) $term->count ) ); ?></span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</nav>

		<?php if ( 1 === $paged && $rolling instanceof WP_Post ) : ?>
			<section class="blog-hub__featured" aria-labelledby="live-brief-heading">
				<header class="listing-header">
					<h2 id="live-brief-heading"><?php esc_html_e( 'Continuously updated live brief', 'infosecnexus' ); ?></h2>
				</header>
				<?php \InfoSecNexus\Theme\Template_Tags\post_card( 'list', 'h3', $rolling->ID ); ?>
			</section>
		<?php endif; ?>

		<section class="blog-hub__latest" aria-labelledby="latest-briefings-heading">
			<header class="listing-header">
				<h2 id="latest-briefings-heading"><?php esc_html_e( 'Latest cybersecurity briefings', 'infosecnexus' ); ?></h2>
			</header>
			<?php if ( $latest->have_posts() ) : ?>
				<div class="post-grid post-grid--archive">
					<?php while ( $latest->have_posts() ) : ?>
						<?php $latest->the_post(); ?>
						<?php \InfoSecNexus\Theme\Template_Tags\post_card(); ?>
					<?php endwhile; ?>
				</div>
				<?php
				$pagination = paginate_links(
					array(
						'total'     => (int) $latest->max_num_pages,
						'current'   => $paged,
						'mid_size'  => 2,
						'prev_text' => __( 'Previous', 'infosecnexus' ),
						'next_text' => __( 'Next', 'infosecnexus' ),
						'type'      => 'list',
					)
				);
				if ( is_string( $pagination ) && '' !== $pagination ) {
					echo '<nav class="pagination" aria-label="' . esc_attr__( 'Blog posts navigation', 'infosecnexus' ) . '">' . wp_kses_post( $pagination ) . '</nav>';
				}
				?>
			<?php else : ?>
				<?php get_template_part( 'template-parts/content', 'none' ); ?>
			<?php endif; ?>
		</section>
	</div>
</main>
<?php
wp_reset_postdata();
get_footer();
