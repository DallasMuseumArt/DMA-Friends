<?php
/**
 * Additional helper functions that the framework or themes may use.
 *
 * @package Nifty
 * @subpackage Functions
 */

/* Add post type support. */
add_action( 'init', 'nifty_add_post_type_support' );

/* Enable/Disable support for Windows Live Writer. */
add_action( 'init', 'nifty_wlwmanifest_link' );

/**
 * Excerpts and Post Layouts are added the 'post' and 'page' post type.
 *
 * @since 12.09
 */
function nifty_add_post_type_support() {
	add_post_type_support( 'post', array( 'post-layouts' ) );
	add_post_type_support( 'page', array( 'excerpt', 'post-layouts' ) );
	add_post_type_support( 'attachment', array( 'comments', 'trackbacks' ) );
}

function nifty_wlwmanifest_link() {
	if ( !nifty_get_setting( 'windows_live_writer' ) ) remove_action( 'wp_head', 'wlwmanifest_link' );
}

/**
 * Loads the loop-meta.php template file for use on archives. Users can overwrite
 * this individual template within their custom child themes.
 *
 * @since 12.09
 */
function nifty_loop_description() {
	locate_template( array( 'templates/loop-meta.php', 'loop-meta.php' ), true );
}

/**
 * Loads the navigation-links.php template file for use on archives, single posts,
 * and attachments. Users can overwrite this individual template within
 * their custom child themes.
 *
 * @since 12.09
 */
function nifty_navigation_links() {
	locate_template( array( 'templates/navigation-links.php', 'navigation-links.php' ), true );
}

/**
 * Displays an author profile block after singular post.
 *
 * Will first attempt to locate the author-profile.php file in either the child or
 * the parent, then load it. If it doesn't exist, then the default author profile
 * will be displayed. Users can overwrite this individual template within
 * their custom child themes.
 * 
 * @since 12.09
 */
function nifty_author_profile() {
	if ( is_singular( 'post' ) ) locate_template( array( 'templates/author-profile.php', 'author-profile.php' ), true );
}

