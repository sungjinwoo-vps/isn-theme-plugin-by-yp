<?php
/**
 * Empty result template.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

?>
<section class="no-results not-found">
	<h2><?php esc_html_e( 'No briefings found', 'infosecnexus' ); ?></h2>
	<p><?php esc_html_e( 'Try a different query or browse the latest security coverage.', 'infosecnexus' ); ?></p>
	<?php get_search_form(); ?>
</section>
