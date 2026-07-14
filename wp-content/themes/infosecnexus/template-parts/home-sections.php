<?php
/**
 * Dynamic newsroom home sections.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

$featured = new WP_Query(
	array(
		'posts_per_page'      => 1,
		'ignore_sticky_posts' => false,
	)
);

$sections = array(
	'Top Headlines'   => '',
	'Latest Articles' => '',
	'Linux Admin'     => 'linux-administration',
	'DevOps'          => 'devops',
	'AI News'         => 'artificial-intelligence',
	'Cybersecurity'   => 'cybersecurity',
	'Critical CVEs'   => 'critical-cves',
	'Major Releases'  => 'major-releases',
);
?>
<section class="alert-strip" aria-label="<?php esc_attr_e( 'Security alert strip', 'infosecnexus' ); ?>">
	<strong><?php esc_html_e( 'Security alert', 'infosecnexus' ); ?></strong>
	<span><?php esc_html_e( 'Track critical CVEs, infrastructure changes, and operational security signals.', 'infosecnexus' ); ?></span>
</section>

<?php if ( $featured->have_posts() ) : ?>
	<section class="featured-story">
		<?php
		while ( $featured->have_posts() ) :
			$featured->the_post();
			?>
			<div class="featured-story__copy">
				<?php \InfoSecNexus\Theme\Template_Tags\category_badges(); ?>
				<h1><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h1>
				<?php \InfoSecNexus\Theme\Template_Tags\post_meta(); ?>
				<div class="featured-story__excerpt"><?php the_excerpt(); ?></div>
			</div>
			<?php if ( has_post_thumbnail() ) : ?>
				<a class="featured-story__media" href="<?php the_permalink(); ?>"><?php the_post_thumbnail( 'large' ); ?></a>
			<?php endif; ?>
			<?php
		endwhile;
		wp_reset_postdata();
		?>
	</section>
<?php endif; ?>

<section class="executive-summary">
	<h2><?php esc_html_e( 'Executive Summary', 'infosecnexus' ); ?></h2>
	<p><?php esc_html_e( 'Daily, editor-curated cybersecurity and infrastructure coverage for teams that need crisp operational context.', 'infosecnexus' ); ?></p>
</section>

<?php foreach ( $sections as $section_title => $slug ) : ?>
	<?php
	$args = array(
		'posts_per_page'      => 4,
		'ignore_sticky_posts' => true,
	);
	if ( $slug ) {
		$args['category_name'] = $slug;
	}
	$query = new WP_Query( $args );
	if ( ! $query->have_posts() ) {
		continue;
	}
	?>
	<section class="news-section">
		<header class="section-header">
			<h2><?php echo esc_html( $section_title ); ?></h2>
			<?php if ( $slug ) : ?>
				<?php $section_category = get_category_by_slug( $slug ); ?>
				<?php if ( $section_category ) : ?>
					<a href="<?php echo esc_url( get_category_link( $section_category ) ); ?>"><?php esc_html_e( 'View all', 'infosecnexus' ); ?></a>
				<?php endif; ?>
			<?php endif; ?>
		</header>
		<div class="post-grid post-grid--4">
			<?php
			while ( $query->have_posts() ) :
				$query->the_post();
				\InfoSecNexus\Theme\Template_Tags\post_card( 'compact' );
			endwhile;
			wp_reset_postdata();
			?>
		</div>
	</section>
<?php endforeach; ?>

<section class="category-explorer">
	<h2><?php esc_html_e( 'Category explorer', 'infosecnexus' ); ?></h2>
	<div class="category-explorer__grid">
		<?php
		$categories = get_categories( array( 'number' => 12 ) );
		foreach ( $categories as $category ) {
			echo '<a href="' . esc_url( get_category_link( $category ) ) . '"><span>' . esc_html( $category->name ) . '</span><small>' . esc_html( (string) $category->count ) . '</small></a>';
		}
		?>
	</div>
</section>

<?php if ( function_exists( 'infosecnexus_toolkit_newsletter_form' ) ) : ?>
	<section class="newsletter-section">
		<?php infosecnexus_toolkit_newsletter_form(); ?>
	</section>
<?php endif; ?>
