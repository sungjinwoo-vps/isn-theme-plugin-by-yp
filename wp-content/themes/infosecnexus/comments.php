<?php
/**
 * Comments template.
 *
 * @package InfoSecNexus
 */

declare(strict_types=1);

if ( post_password_required() ) {
	return;
}
?>
<section id="comments" class="comments-area">
	<?php if ( have_comments() ) : ?>
		<h2 class="comments-title">
			<?php
			printf(
				/* translators: %s: post title. */
				esc_html__( 'Discussion on %s', 'infosecnexus' ),
				'<span>' . esc_html( get_the_title() ) . '</span>'
			);
			?>
		</h2>
		<ol class="comment-list">
			<?php
			wp_list_comments(
				array(
					'style'      => 'ol',
					'short_ping' => true,
				)
			);
			?>
		</ol>
		<?php the_comments_navigation(); ?>
	<?php endif; ?>
	<?php comment_form(); ?>
</section>
