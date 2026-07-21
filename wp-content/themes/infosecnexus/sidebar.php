<?php
/**
 * Sidebar template.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

if ( is_home() || is_archive() || is_search() ) {
	?>
	<aside id="secondary" class="widget-area widget-area--curated" aria-label="<?php esc_attr_e( 'Sidebar', 'infosecnexus' ); ?>">
		<section class="widget widget--search">
			<h2 class="widget-title"><?php esc_html_e( 'Search Briefings', 'infosecnexus' ); ?></h2>
			<?php get_search_form(); ?>
		</section>
		<section class="widget widget--latest">
			<h2 class="widget-title"><?php esc_html_e( 'Latest Briefings', 'infosecnexus' ); ?></h2>
			<?php
			$infosecnexus_latest = new WP_Query(
				array(
					'posts_per_page'      => 4,
					'ignore_sticky_posts' => true,
				)
			);
			if ( $infosecnexus_latest->have_posts() ) :
				?>
				<ul class="sidebar-post-list">
					<?php
					while ( $infosecnexus_latest->have_posts() ) :
						$infosecnexus_latest->the_post();
						?>
						<li>
							<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
							<span><?php echo esc_html( get_the_date() ); ?></span>
						</li>
					<?php endwhile; ?>
				</ul>
				<?php
				wp_reset_postdata();
			endif;
			?>
		</section>
		<section class="widget widget--topics">
			<h2 class="widget-title"><?php esc_html_e( 'Explore Topics', 'infosecnexus' ); ?></h2>
			<?php
			$infosecnexus_uncategorized = get_cat_ID( 'Uncategorized' );
			$infosecnexus_categories    = get_categories(
				array(
					'hide_empty' => true,
					'exclude'    => $infosecnexus_uncategorized ? array( $infosecnexus_uncategorized ) : array(),
					'number'     => 7,
					'orderby'    => 'count',
					'order'      => 'DESC',
				)
			);
			if ( $infosecnexus_categories ) :
				?>
				<ul class="sidebar-topic-list">
					<?php foreach ( $infosecnexus_categories as $infosecnexus_category ) : ?>
						<li>
							<a href="<?php echo esc_url( get_category_link( $infosecnexus_category ) ); ?>"><?php echo esc_html( $infosecnexus_category->name ); ?></a>
							<span><?php echo esc_html( (string) $infosecnexus_category->count ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</section>
	</aside>
	<?php
	return;
}

if ( function_exists( 'infosecnexus_toolkit_render_sidebar' ) && infosecnexus_toolkit_render_sidebar() ) {
	return;
}

if ( ! is_active_sidebar( 'sidebar-1' ) ) {
	return;
}
?>
<aside id="secondary" class="widget-area" aria-label="<?php esc_attr_e( 'Sidebar', 'infosecnexus' ); ?>">
	<?php dynamic_sidebar( 'sidebar-1' ); ?>
</aside>
