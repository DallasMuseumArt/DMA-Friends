<?php
/**
 * Shortcodes bundled for use with themes. These shortcodes can be used in any post content area.
 *
 * @package Nifty
 * @subpackage Functions
 */

/**
 * Register shortcodes.
 * 
 * @since 12.09
 */
add_action( 'init', 'nifty_add_shortcodes' );

/**
 * Creates new shortcodes for use in any shortcode-ready area.
 *
 * @since 12.09
 * @uses add_shortcode() to create new shortcodes.
 * @link http://codex.wordpress.org/Shortcode_API
 */
function nifty_add_shortcodes() {

	/* Add theme-specific shortcodes. */
	add_shortcode( 'the-year', 'nifty_the_year_shortcode' );
	add_shortcode( 'site-link', 'nifty_site_link_shortcode' );
	add_shortcode( 'site-description', 'nifty_site_description_shortcode' );
	add_shortcode( 'wordpress', 'nifty_wordpress_shortcode' );
	add_shortcode( 'theme-link', 'nifty_theme_link_shortcode' );
	add_shortcode( 'child-link', 'nifty_child_link_shortcode' );
	add_shortcode( 'loginout-link', 'nifty_loginout_link_shortcode' );
	add_shortcode( 'query-counter', 'nifty_query_counter_shortcode' );
	add_shortcode( 'bloginfo', 'nifty_bloginfo_shortcode' );
	add_shortcode( 'nav-menu', 'nifty_nav_menu_shortcode' );

	/* Add entry-specific shortcodes. */
	add_shortcode( 'entry-title', 'nifty_entry_title_shortcode' );
	add_shortcode( 'entry-author', 'nifty_entry_author_shortcode' );
	add_shortcode( 'entry-terms', 'nifty_entry_terms_shortcode' );
	add_shortcode( 'entry-comments-link', 'nifty_entry_comments_link_shortcode' );
	add_shortcode( 'entry-published', 'nifty_entry_published_shortcode' );
	add_shortcode( 'entry-edit-link', 'nifty_entry_edit_link_shortcode' );
	add_shortcode( 'entry-permalink', 'nifty_entry_permalink_shortcode' );
	add_shortcode( 'entry-post-format-link', 'nifty_post_format_link_shortcode' );
	add_shortcode( 'entry-shortlink', 'nifty_entry_shortlink_shortcode' );

	/* Add comment-specific shortcodes. */
	add_shortcode( 'comment-published', 'nifty_comment_published_shortcode' );
	add_shortcode( 'comment-author', 'nifty_comment_author_shortcode' );
	add_shortcode( 'comment-edit-link', 'nifty_comment_edit_link_shortcode' );
	add_shortcode( 'comment-reply-link', 'nifty_comment_reply_link_shortcode' );
	add_shortcode( 'comment-permalink', 'nifty_comment_permalink_shortcode' );
}

/**
 * Shortcode to display the current year.
 *
 * @since 12.09
 * @uses date() Gets the current year.
 */
function nifty_the_year_shortcode() {
	return date( 'Y' );
}

/**
 * Shortcode to display a link back to the site.
 *
 * @since 12.09
 * @uses get_bloginfo() Gets information about the install.
 */
function nifty_site_link_shortcode() {
	return '<a class="site-link" href="' . home_url( '/' ) . '" title="' . get_bloginfo( 'name' ) . '"><span>' . get_bloginfo( 'name' ) . '</span></a>';
}

/**
 * Shortcode to display site description.
 *
 * @since 12.09
 * @uses get_bloginfo() Gets information about the install.
 */
function nifty_site_description_shortcode() {
	return get_bloginfo( 'description' );
}

/**
 * Displays the blog info.
 * 
 * @since 12.09
 * @param array $attr
 */
function nifty_bloginfo_shortcode( $atts ) {
	extract( shortcode_atts( array( 'key' => '' ), $atts ) );
	return get_bloginfo( $key );
}

/**
 * Shortcode to display a link to WordPress.org.
 * @since 12.09
 */
function nifty_wordpress_shortcode() {
	return '<a class="wordpress" rel="external" href="http://wordpress.org" title="' . __( 'Powered by WordPress, state-of-the-art semantic personal publishing platform', 'nifty' ) . '"><span>' . __( 'WordPress', 'nifty' ) . '</span></a>';
}

/**
 * Shortcode to display a link to the nifty theme page.
 *
 * @since 12.09
 */
function nifty_theme_link_shortcode() {
	$data = wp_get_theme( 'nifty' );
	return '<a class="theme-link" href="' . $data->display( 'ThemeURI', true, false ) . '" title="' . esc_attr( $data->Name ) . '"><span>' . esc_attr( $data->Name ) . '</span></a>';
}

/**
 * Shortcode to display a link to the child theme's page.
 *
 * @since 12.09
 */
function nifty_child_link_shortcode() {
	$data = wp_get_theme();
	return '<a class="child-link" href="' . $data->display( 'ThemeURI', true, false ) . '" title="' . esc_attr( $data->Name ) . '"><span>' . esc_attr( $data->Name ) . '</span></a>';
}

/**
 * Displays a link, which allows users to navigate to the Log In page to log in
 * or log out depending on whether they are currently logged in.
 *
 * @since 12.09
 * @uses is_user_logged_in() Checks if the current user is logged into the site.
 * @uses wp_login_url() Creates a login URL.
 * @uses wp_logout_url() Creates a logout URL.
 */
function nifty_loginout_link_shortcode() {
	if ( !is_user_logged_in() )
		$out = '<a class="login-link" href="' . wp_login_url( $_SERVER['HTTP_REFERER'] ) . '" title="' . __( 'Log into this account', 'nifty' ) . '" rel="nofollow">' . __( 'Log in', 'nifty' ) . '</a>';
	else
		$out = '<a class="logout-link" href="' . wp_login_url( $_SERVER['HTTP_REFERER'] ) . '" title="' . __( 'Log out of this account', 'nifty' ) . '" rel="nofollow">' . __( 'Log out', 'nifty' ) . '</a>';

	return $out;
}

/**
 * Displays query count and load time if the current user can edit themes.
 *
 * @since 12.09
 * @uses current_user_can() Checks if the current user can edit themes.
 */
function nifty_query_counter_shortcode() {
	if ( current_user_can( 'edit_themes' ) )
		$out = sprintf( __( 'This page loaded in %1$s seconds with %2$s database queries.', 'nifty' ), timer_stop( 0, 3 ), get_num_queries() );

	return $out;
}

/**
 * Displays a nav menu that was been created from the Menu screen in the admin.
 * 
 * @since 12.09
 * @param array $attr
 */
function nifty_nav_menu_shortcode( $attr ) {

	$attr = shortcode_atts(
		array(
			'menu' => '',
			'container_class' => 'menu',
			'menu_class' => '',
			'menu_id' => '',
			'link_before' => '<span>',
			'link_after' => '</span>',
			'fallback_cb' => ''
		),
		$attr
	);
	$attr['echo'] = false;

	return wp_nav_menu( $attr );
}

/**
 * Displays a post's title with a link to the post.
 * 
 * @since 12.09
 */
function nifty_entry_title_shortcode() {
	global $wp_query;

	if ( is_front_page() && !is_home() )
		$title = the_title( '<h1 class="' . $wp_query->post->post_type . '-title entry-title"><a href="' . get_permalink() . '" title="' . the_title_attribute( 'echo=0' ) . '" rel="bookmark">', '</a></h1>', false );

	elseif ( is_singular() )
		$title = the_title( '<h1 class="' . $wp_query->post->post_type . '-title entry-title">', '</h1>', false );

	else
		$title = the_title( '<h1 class="entry-title"><a href="' . get_permalink() . '" title="' . the_title_attribute( 'echo=0' ) . '" rel="bookmark">', '</a></h1>', false );

	return $title;
}

/**
 * Displays an individual post's author with a link to his or her archive.
 *
 * @since 12.09
 * @param array $attr
 */
function nifty_entry_author_shortcode( $attr ) {
	$attr = shortcode_atts( array( 'before' => '', 'after' => '' ), $attr );
	$author = '<span class="author vcard"><a class="url fn n" href="' . get_author_posts_url( get_the_author_meta( 'ID' ) ) . '" title="' . get_the_author_meta( 'display_name' ) . '">' . get_the_author_meta( 'display_name' ) . '</a></span>';
	return $attr['before'] . $author . $attr['after'];
}

/**
 * Displays a list of terms for a specific taxonomy.
 *
 * @since 12.09
 * @param array $attr
 */
function nifty_entry_terms_shortcode( $attr ) {
	global $wp_query;

	$attr = shortcode_atts( array( 'id' => $wp_query->post->ID, 'taxonomy' => 'post_tag', 'separator' => ', ', 'before' => '', 'after' => '' ), $attr );

	$attr['before'] = ( empty( $attr['before'] ) ? '<span class="' . $attr['taxonomy'] . '">' : '<span class="' . $attr['taxonomy'] . '"><span class="before">' . $attr['before'] . '</span>' );
	$attr['after'] = ( empty( $attr['after'] ) ? '</span>' : '<span class="after">' . $attr['after'] . '</span></span>' );

	return get_the_term_list( $attr['id'], $attr['taxonomy'], $attr['before'], $attr['separator'], $attr['after'] );
}

/**
 * Displays a post's number of comments wrapped in a link to the comments area.
 *
 * @since 12.09
 * @param array $attr
 */
function nifty_entry_comments_link_shortcode( $attr ) {

	if ( !post_type_supports( get_post_type(), 'comments' ) )
		return;

	$comments_link = '';
	$number = get_comments_number();
	$attr = shortcode_atts( array( 'zero' => __( 'Leave a response', 'nifty' ), 'one' => __( '1 Response', 'nifty' ), 'more' => __( '%1$s Responses', 'nifty' ), 'none' => '', 'before' => '', 'after' => '' ), $attr );

	if ( 0 == $number && !comments_open() && !pings_open() ) {
		if ( $attr['none'] )
			$comments_link = '<span class="comments-link">' . $attr['none'] . '</span>';
	}
	elseif ( $number == 0 )
		$comments_link = '<a class="comments-link" href="' . get_permalink() . '#respond" title="' . sprintf( __( 'Comment on %1$s', 'nifty' ), the_title_attribute( 'echo=0' ) ) . '">' . $attr['zero'] . '</a>';
	elseif ( $number == 1 )
		$comments_link = '<a class="comments-link" href="' . get_comments_link() . '" title="' . sprintf( __( 'Comment on %1$s', 'nifty' ), the_title_attribute( 'echo=0' ) ) . '">' . $attr['one'] . '</a>';
	elseif ( $number > 1 )
		$comments_link = '<a class="comments-link" href="' . get_comments_link() . '" title="' . sprintf( __( 'Comment on %1$s', 'nifty' ), the_title_attribute( 'echo=0' ) ) . '">' . sprintf( $attr['more'], $number ) . '</a>';

	if ( $comments_link )
		$comments_link = $attr['before'] . $comments_link . $attr['after'];

	return $comments_link;
}

/**
 * Displays the published date of an individual post.
 *
 * @since 12.09
 * @param array $attr
 */
function nifty_entry_published_shortcode( $attr ) {
	$attr = shortcode_atts( array( 'before' => '', 'after' => '', 'format' => get_option( 'date_format' ) ), $attr );

	$published = '<abbr class="published" title="' . sprintf( get_the_time( esc_attr__( 'l, F jS, Y, g:i a', 'nifty' ) ) ) . '">' . sprintf( get_the_time( $attr['format'] ) ) . '</abbr>';
	return $attr['before'] . $published . $attr['after'];
}

/**
 * Returns a link back to the post permalink page.
 *
 * @since 12.09
 * @param array $attr The arguments.
 * @return string A permalink.
 */
function nifty_entry_permalink_shortcode( $attr ) {

	$attr = shortcode_atts( array( 'before' => '', 'after' => '' ), $attr );

	return $attr['before'] . '<a href="' . esc_url( get_permalink() ) . '" class="permalink">' . __( 'Permalink', 'nifty' ) . '</a>' . $attr['after'];
}

/**
 * Returns the post formats feature.
 *
 * @since 12.09
 * @param array $attr The arguments.
 * @return string A link to the post format archive.
 */
function nifty_post_format_link_shortcode( $attr ) {

	$attr = shortcode_atts( array( 'before' => '', 'after' => '' ), $attr );
	$format = get_post_format();
	$url = ( empty( $format ) ? get_permalink() : get_post_format_link( $format ) );

	return $attr['before'] . '<a href="' . esc_url( $url ) . '" class="post-format-link">' . get_post_format_string( $format ) . '</a>' . $attr['after'];
}

/**
 * Displays the edit link for an individual post.
 *
 * @since 12.09
 * @param array $attr
 */
function nifty_entry_edit_link_shortcode( $attr ) {
	global $wp_query;

	$post_type = get_post_type_object( $wp_query->post->post_type );

	if ( !current_user_can( "edit_{$post_type->capability_type}", $wp_query->post->ID ) )
		return;

	$attr = shortcode_atts( array( 'before' => '', 'after' => '' ), $attr );

	return $attr['before'] . '<span class="edit"><a class="post-edit-link" href="' . get_edit_post_link( $wp_query->post->ID ) . '" title="' . sprintf( __( 'Edit %1$s', 'nifty' ), $wp_query->post->post_type ) . '">' . __( 'Edit', 'nifty' ) . '</a></span>' . $attr['after'];
}

/**
 * Displays the shortlink of an individual entry.
 * 
 * @since 12.09
 * @global $comment The current comment's DB object.
 */
function nifty_entry_shortlink_shortcode( $attr ) {
	global $wp_query;

	$attr = shortcode_atts(
		array(
			'text' => __( 'Shortlink', 'nifty' ),
			'title' => the_title_attribute( array( 'echo' => false ) ),
			'before' => '',
			'after' => ''
		),
		$attr
	);

	$shortlink = wp_get_shortlink( $wp_query->post->ID );

	return "{$attr['before']}<a class='shortlink' href='{$shortlink}' title='{$attr['title']}' rel='shortlink'>{$attr['text']}</a>{$attr['after']}";
}

/**
 * Displays the published date and time of an individual comment.
 * 
 * @since 12.09
 */
function nifty_comment_published_shortcode() {
	$link = '<span class="published">' . sprintf( __( '%1$s at %2$s', 'nifty' ), '<abbr class="comment-date" title="' . get_comment_date( __( 'l, F jS, Y, g:i a', 'nifty' ) ) . '">' . get_comment_date() . '</abbr>', '<abbr class="comment-time" title="' . get_comment_date( __( 'l, F jS, Y, g:i a', 'nifty' ) ) . '">' . get_comment_time() . '</abbr>' ) . '</span>';
	return $link;
}

/**
 * Displays the comment author of an individual comment.
 *
 * @since 12.09
 * @param array $attr
 * @global $comment The current comment's DB object.
 * @return string
 */
function nifty_comment_author_shortcode( $attr ) {
	global $comment;

	$attr = shortcode_atts( array( 'before' => '', 'after' => '' ), $attr );

	$author = esc_html( get_comment_author( $comment->comment_ID ) );
	$url = esc_url( get_comment_author_url( $comment->comment_ID ) );

	if ( $url )
		$output = '<cite class="fn" title="' . $url . '"><a href="' . $url . '" title="' . $author . '" class="url" rel="external nofollow">' . $author . '</a></cite>';
	else
		$output = '<cite class="fn">' . $author . '</cite>';

	$output = '<div class="comment-author vcard">' . $attr['before'] . apply_filters( 'get_comment_author_link', $output ) . $attr['after'] . '</div><!-- .comment-author .vcard -->';

	return $output;
}

/**
 * Displays a comment's edit link to users that have the capability to edit the comment.
 * 
 * @since 12.09
 */
function nifty_comment_edit_link_shortcode( $attr ) {
	global $comment;

	$edit_link = get_edit_comment_link( $comment->comment_ID );

	if ( !$edit_link )
		return '';

	$attr = shortcode_atts( array( 'before' => '', 'after' => '' ), $attr );

	$link = '<a class="comment-edit-link" href="' . $edit_link . '" title="' . sprintf( __( 'Edit %1$s', 'nifty' ), $comment->comment_type ) . '"><span class="edit">' . __( 'Edit', 'nifty' ) . '</span></a>';
	$link = apply_filters( 'edit_comment_link', $link, $comment->comment_ID );

	return $attr['before'] . $link . $attr['after'];
}

/**
 * Displays a reply link for the 'comment' comment_type if threaded comments are enabled.
 * 
 * @since 12.09
 * @param array $attr
 */
function nifty_comment_reply_link_shortcode( $attr ) {

	if ( !get_option( 'thread_comments' ) || 'comment' !== get_comment_type() )
		return '';

	$defaults = array(
		'reply_text' => __( 'Reply', 'nifty' ),
		'login_text' => __( 'Log in to reply.', 'nifty' ),
		'depth' => $GLOBALS['comment_depth'],
		'max_depth' => get_option( 'thread_comments_depth' ),
		'before' => '',
		'after' => ''
	);

	$attr = shortcode_atts( $defaults, $attr );

	return get_comment_reply_link( $attr );
}

/**
 * Displays the permalink to an individual comment.
 * 
 * @since 12.09
 * @param array $attr
 */
function nifty_comment_permalink_shortcode( $attr ) {
	global $comment;
	$attr = shortcode_atts( array( 'before' => '', 'after' => '' ), $attr );
	$link = '<a class="permalink" href="' . get_comment_link( $comment->comment_ID ) . '" title="' . sprintf( __( 'Permalink to comment %1$s', 'nifty' ), $comment->comment_ID ) . '">' . __( 'Permalink', 'nifty' ) . '</a>';
	return $attr['before'] . $link . $attr['after'];
}
