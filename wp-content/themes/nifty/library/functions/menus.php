<?php
/**
 * The menus functions deal with registering nav menus within WordPress for the core framework.
 *
 * @package Nifty
 * @subpackage Functions
 */

/* Register nav menus. */
add_action( 'init', 'nifty_register_menu' );

/**
 * Registers the framework default menu.
 *
 * @since 12.09
 * @uses register_nav_menu() Registers a nav menu with WordPress.
 * @link http://codex.wordpress.org/Function_Reference/register_nav_menu
 */
function nifty_register_menu() {
	global $_wp_theme_features;

	if ( !isset( $_wp_theme_features['nifty-core-menus'] ) )
		return;

	$menus = $_wp_theme_features['nifty-core-menus'];

	if ( !is_array( $menus ) )
		return;

	/* Register the 'primary' menu. */
	if ( in_array( 'primary', $menus[0] ) )
		register_nav_menus( array( 'menu-primary' => __( 'Primary Navigation', 'nifty' ) ) );

	/* Register the 'secondary' menu. */
	if ( in_array( 'secondary', $menus[0] ) )
		register_nav_menus( array( 'menu-secondary' => __( 'Secondary Navigation', 'nifty' ) ) );
}

