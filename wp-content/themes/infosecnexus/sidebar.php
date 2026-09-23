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
			$infosecnexus_rolling = \InfoSecNexus\Theme\Template_Tags\rolling_brief_post();
			$infosecnexus_latest = new WP_Query(
				array(
					'posts_per_page'      => $infosecnexus_rolling ? 3 : 4,
					'post__not_in'        => $infosecnexus_rolling ? array( $infosecnexus_rolling->ID ) : array(),
					'ignore_sticky_posts' => true,
				)
			);
			$infosecnexus_items = $infosecnexus_rolling ? array( $infosecnexus_rolling ) : array();
			if ( ! empty( $infosecnexus_latest->posts ) ) {
				$infosecnexus_items = array_merge( $infosecnexus_items, $infosecnexus_latest->posts );
			}
			if ( $infosecnexus_items ) :
				?>
				<ul class="sidebar-post-list">
					<?php foreach ( $infosecnexus_items as $infosecnexus_item ) : ?>
						<?php
						$infosecnexus_post_id = (int) $infosecnexus_item->ID;
						$infosecnexus_date    = \InfoSecNexus\Theme\Template_Tags\post_date_data( $infosecnexus_post_id );
						?>
						<li>
							<a href="<?php echo esc_url( get_permalink( $infosecnexus_post_id ) ); ?>"><?php echo esc_html( get_the_title( $infosecnexus_post_id ) ); ?></a>
							<span><time datetime="<?php echo esc_attr( $infosecnexus_date['datetime'] ); ?>"><?php echo esc_html( $infosecnexus_date['label'] ); ?></time></span>
						</li>
					<?php endforeach; ?>
				</ul>
				<?php
			endif;
			?>
		</section>
		<section class="widget widget--topics">
			<h2 class="widget-title"><?php esc_html_e( 'Explore Blogs', 'infosecnexus' ); ?></h2>
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
