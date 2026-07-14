<?php
/**
 * Run Theme Check from WP-CLI.
 *
 * @package InfoSecNexus
 */

require_once WP_PLUGIN_DIR . '/theme-check/checkbase.php';

$theme = wp_get_theme( 'infosecnexus' );
$ok    = run_themechecks_against_theme( $theme, 'infosecnexus' );

echo $ok ? "PASS\n" : "FAIL\n";

global $themechecks;
foreach ( (array) $themechecks as $check ) {
	if ( is_object( $check ) && method_exists( $check, 'getError' ) ) {
		$errors = array_filter( (array) $check->getError() );
		foreach ( $errors as $error ) {
			echo wp_strip_all_tags( (string) $error ) . "\n";
		}
	}
}

if ( ! $ok ) {
	exit( 1 );
}
