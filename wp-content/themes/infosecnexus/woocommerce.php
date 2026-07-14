<?php
/**
 * WooCommerce wrapper.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

get_header();
?>
<main id="primary" class="site-main">
	<div class="layout-shell">
		<section class="content-area woocommerce-area">
			<?php woocommerce_content(); ?>
		</section>
		<?php get_sidebar(); ?>
	</div>
</main>
<?php
get_footer();
