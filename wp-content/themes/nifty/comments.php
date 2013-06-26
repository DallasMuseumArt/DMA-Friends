<?php
/**
 * The template for displaying Comments.
 * 
 * @package Nifty
 * @subpackage Template
 */

/**
 * If the current post is protected by a password and
 * the visitor has not yet entered the password we will
 * return early without loading the comments.
 */
if ( post_password_required() )
	return;
?>

<div id="comments" class="comments-area">

<?php if ( have_comments() ) : ?>

	<div id="comment-list" class="comments">
		<h3 class="comments-title"><?php comments_number( sprintf( __( 'No responses to %1$s', 'nifty' ), the_title( '&#8220;', '&#8221;', false ) ), sprintf( __( 'One response to %1$s', 'nifty' ), the_title( '&#8220;', '&#8221;', false ) ), sprintf( __( '%1$s responses to %2$s', 'nifty' ), '%', the_title( '&#8220;', '&#8221;', false ) ) ); ?></h3>
		<ol>
			<?php wp_list_comments( nifty_list_comments_args() ); ?>
		</ol>
	</div> <!-- #comments-list -->

	<?php if ( get_comment_pages_count() > 1 && get_option( 'page_comments' ) ) : ?>

	<div class="navigation-links comment-navigation">
		<?php paginate_comments_links(); ?>
	</div><!-- .comment-navigation -->

	<?php endif; ?>
	
<?php endif; /* have_comments() */ ?>
	
<?php if ( !comments_open() && '0' != get_comments_number() && post_type_supports( get_post_type(), 'comments' ) ) : ?>

	<p class="comments-closed">
		<?php _e( 'Comments are closed.', 'nifty' ); ?>
	</p><!-- .comments-closed --> <?php

endif;

	comment_form(); // Load the comment form. ?>

</div> <!-- id="comments" -->
