<?php
/**
 * Shared Elementor query widget base.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

namespace InfoSecNexus\Theme\Toolkit\Widgets;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;

/**
 * Shared query widget base.
 */
abstract class Query_Widget extends Widget_Base {
	/**
	 * Get widget categories.
	 *
	 * @return string[]
	 */
	public function get_categories(): array {
		return array( 'infosecnexus' );
	}

	/**
	 * Get widget icon.
	 *
	 * @return string
	 */
	public function get_icon(): string {
		return 'eicon-post-list';
	}

	/**
	 * Register common query controls.
	 */
	protected function register_query_controls(): void {
		$this->start_controls_section(
			'query_section',
			array( 'label' => __( 'Query', 'infosecnexus' ) )
		);
		$this->add_control(
			'posts_per_page',
			array(
				'label'   => __( 'Posts per page', 'infosecnexus' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 4,
				'min'     => 1,
				'max'     => 12,
			)
		);
		$this->add_control(
			'category',
			array(
				'label'       => __( 'Category slug', 'infosecnexus' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => 'critical-cves',
			)
		);
		$this->end_controls_section();
	}

	/**
	 * Build safe query args.
	 *
	 * @param array<string,mixed> $settings Settings.
	 * @return array<string,mixed>
	 */
	protected function query_args( array $settings ): array {
		$args = array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => max( 1, min( 12, absint( $settings['posts_per_page'] ?? 4 ) ) ),
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		);

		$category = sanitize_title( (string) ( $settings['category'] ?? '' ) );
		if ( $category ) {
			$args['category_name'] = $category;
		}

		return $args;
	}

	/**
	 * Render post cards.
	 *
	 * @param \WP_Query $query Query.
	 */
	protected function render_cards( \WP_Query $query ): void {
		if ( ! $query->have_posts() ) {
			echo '<p>' . esc_html__( 'No posts found.', 'infosecnexus' ) . '</p>';
			return;
		}

		echo '<div class="isnx-widget-grid">';
		while ( $query->have_posts() ) {
			$query->the_post();
			echo '<article class="isnx-widget-card">';
			if ( has_post_thumbnail() ) {
				echo '<a class="isnx-widget-card__image" href="' . esc_url( get_permalink() ) . '">' . get_the_post_thumbnail( get_the_ID(), 'medium_large', array( 'loading' => 'lazy' ) ) . '</a>';
			}
			echo '<h3><a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a></h3>';
			echo '<time datetime="' . esc_attr( get_the_date( DATE_W3C ) ) . '">' . esc_html( get_the_date() ) . '</time>';
			echo '</article>';
		}
		wp_reset_postdata();
		echo '</div>';
	}
}
