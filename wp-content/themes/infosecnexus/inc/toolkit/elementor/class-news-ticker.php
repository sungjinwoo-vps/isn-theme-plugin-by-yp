<?php
/**
 * News Ticker Elementor widget.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

namespace InfoSecNexus\Theme\Toolkit\Widgets;

/**
 * News Ticker widget.
 */
class News_Ticker extends Query_Widget {
	/**
	 * Get widget name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'infosecnexus_news_ticker';
	}

	/**
	 * Get widget title.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return __( 'News Ticker', 'infosecnexus' );
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
		$query = new \WP_Query( $this->query_args( $this->get_settings_for_display() ) );
		echo '<div class="isnx-news-ticker" role="list">';
		while ( $query->have_posts() ) {
			$query->the_post();
			echo '<a role="listitem" href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a>';
		}
		wp_reset_postdata();
		echo '</div>';
	}
}
