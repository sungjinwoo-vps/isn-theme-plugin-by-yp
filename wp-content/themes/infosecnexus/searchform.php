<?php
/**
 * Search form.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

?>
<form role="search" method="get" class="search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label>
		<span class="screen-reader-text"><?php esc_html_e( 'Search for:', 'infosecnexus' ); ?></span>
		<input type="search" class="search-field" placeholder="<?php esc_attr_e( 'Search blog posts, CVEs, Linux, DevOps...', 'infosecnexus' ); ?>" value="<?php echo esc_attr( get_search_query() ); ?>" name="s">
	</label>
	<input type="hidden" name="post_type" value="post">
	<button type="submit"><?php esc_html_e( 'Search', 'infosecnexus' ); ?></button>
</form>
