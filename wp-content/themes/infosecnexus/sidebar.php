<?php
/**
 * Sidebar template.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

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
