<?php
/**
 * Functions file for loading media, scripts and stylesheets.
 *
 * @package Nifty
 * @subpackage Functions
 */

/* Remove plugin Stylesheets. */
add_action( 'wp_print_styles', 'nifty_disable_styles' );

/* WP print scripts and styles. */
add_action( 'wp_enqueue_scripts', 'nifty_enqueue_style', 1 );
add_action( 'wp_enqueue_scripts', 'nifty_enqueue_scripts' );

/**
 * Function to load Stylesheets at appropriate time.
 *
 * @since 12.09
 * @link http://codex.wordpress.org/Function_Reference/wp_enqueue_style
 */
function nifty_enqueue_style() {

	if ( is_admin() )
		return null;

	$stylesheet = get_bloginfo( 'stylesheet_url' );
	$theme      = wp_get_theme();

	wp_enqueue_style( 'nifty', $stylesheet, false, $theme->Version, 'screen' );
}

/**
 * Function to load JavaScript at appropriate time. Loads comment reply script only if users choose to 
 * use nested comments.
 *
 * @since 12.09
 * @link http://codex.wordpress.org/Function_Reference/wp_enqueue_script
 * @link http://users.tpg.com.au/j_birch/plugins/superfish
 */
function nifty_enqueue_scripts() {

	/* Don't load any scripts in the admin. */
	if ( is_admin() )
		return;

	global $nifty;

	/* Comment reply. */
	if ( is_singular() && get_option( 'thread_comments' ) && comments_open() )
		wp_enqueue_script( 'comment-reply' );

	/* Superfish drop-down menus. */
	wp_enqueue_script( 'nifty-drop-downs', esc_url( apply_filters( 'nifty-drop-downs', trailingslashit( $nifty->nifty_uri ) . 'js/drop-downs.js' ) ), array( 'jquery' ), '1.0', true );
}

/**
 * Disables stylesheets for particular plugins to allow the theme
 * to easily write its own styles for the plugins features.
 * 
 * @since 12.09
 */
function nifty_disable_styles() {

	/* Deregister the WP PageNavi plugin style. */
	wp_deregister_style( 'wp-pagenavi' );
}
