<?php
/**
 * Page content.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

$hide_title = (bool) get_post_meta( get_the_ID(), '_infosecnexus_hide_title', true );
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'page-entry' ); ?>>
	<?php if ( ! $hide_title ) : ?>
		<header class="page-header">
			<?php \InfoSecNexus\Theme\Breadcrumbs\render(); ?>
			<h1><?php the_title(); ?></h1>
		</header>
	<?php endif; ?>
	<div class="entry-content">
		<?php the_content(); ?>
	</div>
</article>
