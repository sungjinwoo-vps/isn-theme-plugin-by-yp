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

$infosecnexus_required       = (bool) get_option( 'require_name_email' );
$infosecnexus_required_label = $infosecnexus_required ? ' <span class="required">*</span>' : '';

$infosecnexus_comment_fields = array(
	'author' => '<p class="comment-form-author"><label for="author">' . esc_html__( 'Name', 'infosecnexus' ) . wp_kses_post( $infosecnexus_required_label ) . '</label><input id="author" name="author" type="text" value="" autocomplete="name" placeholder="' . esc_attr__( 'Your name', 'infosecnexus' ) . '"' . ( $infosecnexus_required ? ' required' : '' ) . '></p>',
	'email'  => '<p class="comment-form-email"><label for="email">' . esc_html__( 'Email', 'infosecnexus' ) . wp_kses_post( $infosecnexus_required_label ) . '</label><input id="email" name="email" type="email" value="" autocomplete="email" placeholder="' . esc_attr__( 'you@example.com', 'infosecnexus' ) . '"' . ( $infosecnexus_required ? ' required' : '' ) . '></p>',
);
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
	<?php
	comment_form(
		array(
			'class_form'           => 'comment-form isnx-comment-form',
			'class_submit'         => 'submit button comment-submit',
			'title_reply'          => __( 'Comment', 'infosecnexus' ),
			'title_reply_before'   => '<h2 id="reply-title" class="comment-reply-title">',
			'title_reply_after'    => '</h2>',
			'comment_notes_before' => '',
			'logged_in_as'         => '',
			'comment_field'        => '<p class="comment-form-comment"><label for="comment">' . esc_html__( 'Comment', 'infosecnexus' ) . ' <span class="required">*</span></label><textarea id="comment" name="comment" cols="45" rows="8" required placeholder="' . esc_attr__( 'Write your comment...', 'infosecnexus' ) . '"></textarea></p>',
			'fields'               => $infosecnexus_comment_fields,
			'label_submit'         => __( 'Publish Comment', 'infosecnexus' ),
			'submit_button'        => '<button name="%1$s" type="submit" id="%2$s" class="%3$s">%4$s</button>',
		)
	);
	?>
</section>
