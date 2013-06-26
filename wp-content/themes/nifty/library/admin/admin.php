<?php
/**
 * Theme administration functions used with other components of the framework admin.
 *
 * @package Nifty
 * @subpackage Admin
 */

/* Add the admin init function to the 'admin_init' hook. */
add_action( 'admin_init', 'nifty_admin_init' );

/**
 * Initializes any admin-related features needed for the framework.
 *
 * @since 12.09
 */
function nifty_admin_init() {

	/* Load the post meta boxes on the new post and edit post screens. */
	add_action( 'load-post.php', 'nifty_admin_load_post_meta_boxes' );
	add_action( 'load-post-new.php', 'nifty_admin_load_post_meta_boxes' );
}


/**
 * Loads the core post meta box files on the 'load-post.php' and 'load-post-new.php' action hook.
 *
 * @since 12.09
 */
function nifty_admin_load_post_meta_boxes() {
	global $nifty;

	/* Load the templates functions. */
	require_once( trailingslashit( $nifty->nifty_admin ) . 'meta-box-post-template.php' );

	/* Load the SEO post meta box. */
	require_if_theme_supports( 'nifty-core-seo', trailingslashit( $nifty->nifty_admin ) . 'meta-box-post-seo.php' );
}
