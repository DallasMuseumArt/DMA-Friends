<?php
/**
 * Functions for handling how comments are displayed and used on the site.
 *
 * @package Nifty
 * @subpackage Functions
 */

/**
 * Arguments for the wp_list_comments_function() used in comments.php.
 *
 * @since 12.09
 * @return array $args Arguments for listing comments.
 */
function nifty_list_comments_args() {
	$args = array( 'style' => 'ol', 'avatar_size' => '80', 'type' => 'all', 'callback' => 'nifty_comments_callback' );
	return apply_filters( 'nifty_list_comments_args', $args );
}

/**
 * @since 12.09
 * @deprecated 12.09.2
 */
function nifty_list_trackbacks_args() {
	_deprecated_function( __FUNCTION__, '12.09.2' );
	$args = array( 'style' => 'ol', 'type' => 'pings', 'callback' => 'nifty_trackbacks_callback' );
	return apply_filters( 'nifty_list_trackbacks_args', $args );
}

/**
 * Custom callback to list comments.
 * 
 * @since 12.09
 */
function nifty_comments_callback( $comment, $args, $depth ) {
	$GLOBALS['comment'] = $comment;
	$GLOBALS['comment_depth'] = $depth;

	/* Get the comment type of the current comment. */
	$comment_type = get_comment_type( $comment->comment_ID ); ?>

	<?php if ( 'pingback' == $comment_type || 'trackback' == $comment_type ) : ?>

		<li class="<?php echo nifty_comment_class(); ?>">
			<div id="comment-<?php comment_ID() ?>" class="trackback">
				<?php $published = do_shortcode( '[comment-published] [comment-edit-link before="| "]' ); ?>
				<?php echo do_shortcode( "[comment-author before='Pingback: ' after=' - $published']" ); ?>
			</div> <!-- comment-<?php comment_ID(); ?> -->

	<?php else: ?>

		<li class="<?php echo nifty_comment_class(); ?>">

			<div id="comment-<?php comment_ID() ?>" class="comment">
				<?php do_action( 'nifty_before_comment' ); ?>

				<div class="comment-content">
					<?php if ( $comment->comment_approved == '0' ) : ?>
						<p class="alert moderation"><?php _e( 'Your comment is awaiting moderation.', 'nifty' ); ?></p>
					<?php endif; ?>
					<?php comment_text() ?>
				</div>

				<?php do_action( 'nifty_after_comment' ); ?>
			</div> <!-- comment-<?php comment_ID(); ?> -->
	<?php endif;
}

/**
 * Displays the avatar for the comment author.
 *
 * @since 12.09
 * @global $comment The current comment's DB object.
 */
function nifty_comment_avatar() {
	global $comment;

	/* Make sure avatars are allowed before proceeding. */
	if ( !get_option( 'show_avatars' ) )
		return false;

	/* Get/set some comment variables. */
	$author = esc_html( get_comment_author( $comment->comment_ID ) );

	/* Set up the avatar size. */
	$comment_list_args = nifty_list_comments_args();
	$size = ( ( $comment_list_args['avatar_size'] ) ? $comment_list_args['avatar_size'] : 80 );

	/* Get the avatar provided by the get_avatar() function. */
	$avatar = get_avatar( get_comment_author_email( $comment->comment_ID ), absint( $size ), '', $author );

	/* Display the avatar and allow it to be filtered. Note: Use the get_avatar filter hook where possible. */
	echo apply_filters( 'nifty_avatar', $avatar );
}

/**
 * Functions for displaying a comment's metadata.
 * 
 * @since 12.09
 * @param string $metadata
 */
function nifty_comment_meta( $metadata = '' ) {
	global $comment, $post;

	if ( !$metadata )
		$metadata = '[comment-author] [comment-published] [comment-permalink before="| "] [comment-edit-link before="| "]';

	$metadata = '<div class="comment-meta">' . $metadata . '</div>';

	echo do_shortcode( apply_filters( 'nifty_comment_meta', $metadata ) );
}

/**
 * Displays a reply link.
 * 
 * @since 12.09
 */
function nifty_comment_reply_link( $replylink = '' ) {
	if ( !$replylink )
		$replylink = '[comment-reply-link]';

	$replylink = '<div class="comment-reply">' . $replylink . '</div>';

	echo do_shortcode( apply_filters( 'nifty_comment_reply_link', $replylink ) );
}

/**
 * @since 12.09
 * @deprecated 12.09.2
 */
function nifty_trackbacks_callback( $comment, $args, $depth ) {
	_deprecated_function( __FUNCTION__, '12.09.2' );
	$GLOBALS['comment'] = $comment; ?>

	<li id="comment-<?php comment_ID() ?>" class="trackback">

		<?php $published = do_shortcode( '[comment-published] [comment-edit-link before="| "]' ); ?>
		<?php echo do_shortcode( "[comment-author after=' - $published']" );
}

/**
 * @since 12.09
 * @deprecated 12.09.2
 */
function nifty_comments_number( $count ) {
	_deprecated_function( __FUNCTION__, '12.09.2' );
	if ( is_admin() )
		return $count;

	global $id;

	$comments = &separate_comments( get_comments( array( 'status' => 'approve', 'post_id' => $id ) ) );
	return count( $comments['comment'] );
}

/**
 * Filters the WordPress comment_form() function that was added in WordPress 3.0.
 *
 * @since 12.09
 * @param array $args The default comment form arguments.
 * @return array $args The filtered comment form arguments.
 */
function nifty_comment_form_defaults( $args ) {
	global $user_identity;

	$commenter = wp_get_current_commenter();
	$req = ( ( get_option( 'require_name_email' ) ) ? ' <span class="required">' . __( '*', 'nifty' ) . '</span> ' : '' );

	$fields = array(
		'author' => '<p class="comment-form-author"><label for="author">' . __( 'Name', 'nifty' ) . $req . '</label> <input type="text" class="text-input required" name="author" title="' . __( 'Name', 'nifty' ) . '" id="author" value="' . esc_attr( $commenter['comment_author'] ) . '" size="40" tabindex="1" /></p>',
		'email' => '<p class="comment-form-email"><label for="email">' . __( 'Email', 'nifty' ) . $req . '</label> <input type="text" class="text-input required" name="email" title="' . __( 'Email', 'nifty' ) . '" id="email" value="' . esc_attr( $commenter['comment_author_email'] ) . '" size="40" tabindex="2" /></p>',
		'url' => '<p class="comment-form-url"><label for="url">' . __( 'Website', 'nifty' ) . '</label><input type="text" class="text-input" name="url" title="' . __( 'Website', 'nifty' ) . '" id="url" value="' . esc_attr( $commenter['comment_author_url'] ) . '" size="40" tabindex="3" /></p>'
	);

	$args = array(
		'fields' => apply_filters( 'comment_form_default_fields', $fields ),
		'comment_field' => '<p class="comment-form-textarea"><label for="comment">' . __( 'Comment', 'nifty' ) . '</label><textarea name="comment" title="' . __( 'Comment', 'nifty' ) . '" id="comment" class="required" cols="60" rows="10" tabindex="4"></textarea></p>',
		'must_log_in' => '<p class="alert">' . sprintf( __( 'You must be <a href="%1$s" title="Log in">logged in</a> to post a comment.', 'nifty' ), wp_login_url( get_permalink() ) ) . '</p><!-- .alert -->',
		'logged_in_as' => '<p class="log-in-out">' . sprintf( __( 'Logged in as <a href="%1$s" title="%2$s">%2$s</a>.', 'nifty' ), admin_url( 'profile.php' ), $user_identity ) . ' <a href="' . wp_logout_url( get_permalink() ) . '" title="' . __( 'Log out of this account', 'nifty' ) . '">' . __( 'Log out &raquo;', 'nifty' ) . '</a></p><!-- .log-in-out -->',
		'comment_notes_before' => '',
		'comment_notes_after' => '',
		'id_form' => 'commentform',
		'id_submit' => 'submit',
		'title_reply' => __( 'Leave a Reply', 'nifty' ),
		'title_reply_to' => __( 'Leave a Reply to %s', 'nifty' ),
		'cancel_reply_link' => __( 'Click here to cancel reply.', 'nifty' ),
		'label_submit' => __( 'Post Comment', 'nifty' ),
	);

	return $args;
}

