<?php
/**
 * Author Box Elementor widget.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

namespace InfoSecNexus\Theme\Toolkit\Widgets;

use Elementor\Widget_Base;

/**
 * Author Box widget.
 */
final class Author_Box extends Widget_Base {
	/**
	 * Get widget name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'infosecnexus_author_box';
	}

	/**
	 * Get widget title.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return __( 'Author Box', 'infosecnexus' );
	}

	/**
	 * Get widget icon.
	 *
	 * @return string
	 */
	public function get_icon(): string {
		return 'eicon-person';
	}

	/**
	 * Get widget categories.
	 *
	 * @return string[]
	 */
	public function get_categories(): array {
		return array( 'infosecnexus' );
	}

	/**
	 * Register controls.
	 */
	protected function register_controls(): void {}

	/**
	 * Render widget.
	 */
	protected function render(): void {
		$author_id = get_post_field( 'post_author', get_the_ID() );
		if ( ! $author_id ) {
			return;
		}
		echo '<section class="isnx-author-box">' . get_avatar( $author_id, 72 );
		echo '<div><h3>' . esc_html( get_the_author_meta( 'display_name', $author_id ) ) . '</h3><p>' . esc_html( get_the_author_meta( 'description', $author_id ) ) . '</p></div></section>';
	}
}
