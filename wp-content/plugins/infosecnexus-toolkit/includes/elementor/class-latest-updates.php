<?php
/**
 * Latest Updates Elementor widget.
 *
 * @package InfoSecNexusToolkit
 */

declare(strict_types=1);

namespace InfoSecNexus\Toolkit\Widgets;

/**
 * Latest Updates widget.
 */
final class Latest_Updates extends News_Ticker {
	/**
	 * Get widget name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'infosecnexus_latest_updates';
	}

	/**
	 * Get widget title.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return __( 'Latest Updates', 'infosecnexus-toolkit' );
	}
}
