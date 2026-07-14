<?php
/**
 * Site footer.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

?>
	<?php do_action( 'infosecnexus_before_footer' ); ?>
	<?php
	$infosecnexus_hide_footer = is_singular() && (bool) get_post_meta( get_queried_object_id(), '_infosecnexus_hide_footer', true );
	if ( ! $infosecnexus_hide_footer && ! \InfoSecNexus\Theme\Elementor\render_location( 'footer' ) ) {
		do_action( 'infosecnexus_footer' );
	}
	?>
	<?php do_action( 'infosecnexus_after_footer' ); ?>
	<?php if ( get_theme_mod( 'enable_scroll_top', true ) ) : ?>
		<button class="scroll-top" type="button" data-scroll-top aria-label="<?php esc_attr_e( 'Scroll to top', 'infosecnexus' ); ?>">ÃƒÂ¢Ã¢â‚¬Â Ã¢â‚¬Ëœ</button>
	<?php endif; ?>
</div>
<?php wp_footer(); ?>
</body>
</html>
