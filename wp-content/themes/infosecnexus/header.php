<?php
/**
 * Site header.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

$infosecnexus_default_color_mode = (string) \InfoSecNexus\Theme\Customizer\get_value( 'default_color_mode' );
$infosecnexus_initial_color_mode = 'dark' === $infosecnexus_default_color_mode ? 'dark' : 'light';

?><!doctype html>
<html <?php language_attributes(); ?> data-color-mode="<?php echo esc_attr( $infosecnexus_initial_color_mode ); ?>" data-default-color-mode="<?php echo esc_attr( $infosecnexus_default_color_mode ); ?>">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div id="page" class="site">
	<div class="reading-progress" data-reading-progress aria-hidden="true"></div>
	<a class="skip-link screen-reader-text" href="#primary"><?php esc_html_e( 'Skip to content', 'infosecnexus' ); ?></a>
	<?php do_action( 'infosecnexus_before_header' ); ?>
	<?php
	$infosecnexus_hide_header = is_singular() && (bool) get_post_meta( get_queried_object_id(), '_infosecnexus_hide_header', true );
	if ( ! $infosecnexus_hide_header && ! \InfoSecNexus\Theme\Elementor\render_location( 'header' ) ) {
		do_action( 'infosecnexus_header' );
	}
	?>
	<?php do_action( 'infosecnexus_after_header' ); ?>
