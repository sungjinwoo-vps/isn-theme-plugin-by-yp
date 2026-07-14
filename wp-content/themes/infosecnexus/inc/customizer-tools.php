<?php
/**
 * Customizer import, export, and safe reset tools.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

namespace InfoSecNexus\Theme\Customizer_Tools;

use function InfoSecNexus\Theme\Customizer\allowed_setting_keys;
use function InfoSecNexus\Theme\Customizer\default_value;

/**
 * Register hooks.
 */
function bootstrap(): void {
	add_action( 'admin_menu', __NAMESPACE__ . '\\admin_menu' );
	add_action( 'admin_post_infosecnexus_export_customizer', __NAMESPACE__ . '\\export' );
	add_action( 'admin_post_infosecnexus_import_customizer', __NAMESPACE__ . '\\import' );
	add_action( 'admin_post_infosecnexus_reset_customizer', __NAMESPACE__ . '\\reset' );
}

/**
 * Add admin page.
 */
function admin_menu(): void {
	add_theme_page(
		__( 'InfoSecNexus Tools', 'infosecnexus' ),
		__( 'InfoSecNexus Tools', 'infosecnexus' ),
		'edit_theme_options',
		'infosecnexus-tools',
		__NAMESPACE__ . '\\render_page'
	);
}

/**
 * Render admin page.
 */
function render_page(): void {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}

	$export = array();
	foreach ( allowed_setting_keys() as $key ) {
		$export[ $key ] = get_theme_mod( $key, default_value( $key ) );
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'InfoSecNexus Customizer Tools', 'infosecnexus' ); ?></h1>
		<p><?php esc_html_e( 'Export, import, or safely reset only InfoSecNexus theme settings. Site content is not deleted.', 'infosecnexus' ); ?></p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'infosecnexus_export_customizer' ); ?>
			<input type="hidden" name="action" value="infosecnexus_export_customizer">
			<?php submit_button( __( 'Download Customizer Export', 'infosecnexus' ) ); ?>
		</form>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'infosecnexus_import_customizer' ); ?>
			<input type="hidden" name="action" value="infosecnexus_import_customizer">
			<textarea name="infosecnexus_import_json" rows="12" class="large-text code" aria-label="<?php esc_attr_e( 'Customizer JSON', 'infosecnexus' ); ?>"><?php echo esc_textarea( wp_json_encode( $export, JSON_PRETTY_PRINT ) ); ?></textarea>
			<?php submit_button( __( 'Import JSON', 'infosecnexus' ), 'secondary' ); ?>
		</form>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'infosecnexus_reset_customizer' ); ?>
			<input type="hidden" name="action" value="infosecnexus_reset_customizer">
			<?php submit_button( __( 'Reset InfoSecNexus Theme Settings', 'infosecnexus' ), 'delete' ); ?>
		</form>
	</div>
	<?php
}

/**
 * Export settings.
 */
function export(): void {
	if ( ! current_user_can( 'edit_theme_options' ) || ! check_admin_referer( 'infosecnexus_export_customizer' ) ) {
		wp_die( esc_html__( 'Permission denied.', 'infosecnexus' ) );
	}

	$data = array();
	foreach ( allowed_setting_keys() as $key ) {
		$data[ $key ] = get_theme_mod( $key, default_value( $key ) );
	}

	nocache_headers();
	header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
	header( 'Content-Disposition: attachment; filename=infosecnexus-customizer.json' );
	echo wp_json_encode( $data, JSON_PRETTY_PRINT );
	exit;
}

/**
 * Import settings.
 */
function import(): void {
	if ( ! current_user_can( 'edit_theme_options' ) || ! check_admin_referer( 'infosecnexus_import_customizer' ) ) {
		wp_die( esc_html__( 'Permission denied.', 'infosecnexus' ) );
	}

	$raw  = isset( $_POST['infosecnexus_import_json'] ) ? sanitize_textarea_field( wp_unslash( $_POST['infosecnexus_import_json'] ) ) : '';
	$data = json_decode( (string) $raw, true );
	if ( ! is_array( $data ) ) {
		wp_safe_redirect( admin_url( 'themes.php?page=infosecnexus-tools&import=invalid' ) );
		exit;
	}

	foreach ( allowed_setting_keys() as $key ) {
		if ( array_key_exists( $key, $data ) ) {
			set_theme_mod( $key, sanitize_text_field( (string) $data[ $key ] ) );
		}
	}

	wp_safe_redirect( admin_url( 'themes.php?page=infosecnexus-tools&import=ok' ) );
	exit;
}

/**
 * Reset theme settings.
 */
function reset(): void {
	if ( ! current_user_can( 'edit_theme_options' ) || ! check_admin_referer( 'infosecnexus_reset_customizer' ) ) {
		wp_die( esc_html__( 'Permission denied.', 'infosecnexus' ) );
	}

	foreach ( allowed_setting_keys() as $key ) {
		remove_theme_mod( $key );
	}

	wp_safe_redirect( admin_url( 'themes.php?page=infosecnexus-tools&reset=ok' ) );
	exit;
}
