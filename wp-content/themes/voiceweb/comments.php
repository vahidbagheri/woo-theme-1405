<?php
/**
 * Comments area — custom form + threaded list.
 *
 * @package voiceweb
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Do not load for password-protected posts without the password.
if ( post_password_required() ) {
	return;
}
?>
<section class="comments d-flex d-md-block flex-column w-100 m-0 p-0 bg-transparent" id="comments" data-cn-comments>

	<?php
	$ph_comment = esc_attr( cn_translate( 'Your comment' ) );
	$ph_author  = esc_attr( cn_translate( 'Full name' ) );


	$comment_fields = array(
		'author' => '<p class="comment-form-author"><input class="w-100" id="author" name="author" type="text" required placeholder="' . $ph_author . '"></p>',
		'email'  => '<p class="comment-form-email"><input class="w-100" id="email" name="email" type="email" required placeholder="' . esc_attr( cn_translate( 'Email' ) ) . '"></p>',
		'cookies' => '',
	);
	$form_args = array(
		'class_form'           => 'comment-form d-flex flex-wrap',
		'title_reply'          => esc_html( cn_translate( 'Post Comment' ) ),
		'title_reply_to'       => esc_html( cn_translate( 'Reply to %s' ) ),
		// Use <h2> (not the default <h3>) so heading levels don't skip h1 → h3.
		// comment-reply.js targets #reply-title by id, so this stays functional.
		'title_reply_before'   => '<h2 id="reply-title" class="comment-reply-title">',
		'title_reply_after'    => '</h2>',
		'cancel_reply_link'    => esc_html( cn_translate( 'Cancel' ) ),
		'label_submit'         => esc_html( cn_translate( 'Submit' ) ),
		'class_submit'         => 'comment-submit fw-medium',
		'comment_notes_before' => '',
		'comment_notes_after'  => '',
		'comment_field'        => '<p class="comment-form-comment"><textarea class="w-100" id="comment" name="comment" rows="4" required placeholder="' . $ph_comment . '"></textarea></p>',
		'submit_field'         => '<p class="form-submit d-flex justify-content-end m-0">%1$s %2$s</p>',
		'fields'               => $comment_fields,
	);
	comment_form( $form_args );
	$has_comments = have_comments();
	?>

	<?php if ( $has_comments ) { ?>
		<h2 class="comments-title visually-hidden">
			<?php
			$cnt = (int) get_comments_number();
			printf(
				/* translators: %s: comment count */
				esc_html( cn_translate_plural( '%s comment', '%s comments', $cnt ) ),
				esc_html( number_format_i18n( $cnt ) )
			);
			?>
		</h2>

		<ol class="comment-list d-flex flex-column">
			<?php
			wp_list_comments( array(
				'style'       => 'ol',
				'callback'    => 'cn_comment',
				'avatar_size' => 44,
			) );
			?>
		</ol>

		<?php the_comments_pagination(); ?>
	<?php } ?>

</section>
