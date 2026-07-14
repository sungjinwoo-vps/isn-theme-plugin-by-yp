<?php
/**
 * Featured Story Elementor widget.
 *
 * @package InfoSecNexusToolkit
 */

declare(strict_types=1);

namespace InfoSecNexus\Toolkit\Widgets;

/**
 * Featured Story widget.
 */
final class Featured_Story extends Query_Widget {
	/**
	 * Get widget name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'infosecnexus_featured_story';
	}

	/**
	 * Get widget title.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return __( 'Featured Story', 'infosecnexus-toolkit' );
	}

	/**
	 * Register controls.
	 */
	protected function register_controls(): void {
		$this->register_query_controls();
	}

	/**
	 * Render widget.
	 */
	protected function render(): void {
		$settings                   = $this->get_settings_for_display();
		$settings['posts_per_page'] = 1;
		$query                      = new \WP_Query( $this->query_args( $settings ) );
		if ( ! $query->have_posts() ) {
			echo '<p>' . esc_html__( 'No featured story found.', 'infosecnexus-toolkit' ) . '</p>';
			return;
		}
		while ( $query->have_posts() ) {
			$query->the_post();
			echo '<article class="isnx-featured-widget">';
			echo '<div><h2><a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a></h2><p>' . esc_html( wp_trim_words( get_the_excerpt(), 32 ) ) . '</p></div>';
			if ( has_post_thumbnail() ) {
				echo get_the_post_thumbnail( get_the_ID(), 'large', array( 'loading' => 'lazy' ) );
			}
			echo '</article>';
		}
		wp_reset_postdata();
	}
}
