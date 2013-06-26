<?php
/**
 * Sets up the core framework's widgets and unregisters some of the default WordPress widgets if the 
 * theme supports this feature.
 *
 * @package Nifty
 * @subpackage Functions
 */

/* Register Nifty widgets. */
add_action( 'widgets_init', 'nifty_widgets_init' );

/**
 * Registers the core frameworks widgets.
 *
 * @since 12.09
 * @uses register_widget() Registers individual widgets with WordPress
 * @link http://codex.wordpress.org/Function_Reference/register_widget
 */
function nifty_widgets_init() {
	global $_wp_theme_features;

	if ( !isset( $_wp_theme_features['nifty-core-sidebars'] ) )
		return;

	$sidebars = $_wp_theme_features['nifty-core-sidebars'];

	if ( !is_array( $sidebars ) )
		return;

	/* Register the primary sidebar. */
	if ( in_array( 'primary', $sidebars[0] ) )
		register_sidebar( array ( 'name' => __( 'Primary Widget Area' , 'nifty' ), 'id' => 'primary', 'description' => __( 'The primary widget area.' , 'nifty' ), 'before_widget' => '<div id="%1$s" class="widget %2$s"><div class="widget-inside">', 'after_widget' => "</div></div>", 'before_title' => '<h3 class="widget-title">', 'after_title' => '</h3>' ) );

	/* Register the secondary sidebar. */
	if ( in_array( 'secondary', $sidebars[0] ) )
		register_sidebar( array ( 'name' => __( 'Secondary Widget Area' , 'nifty' ), 'id' => 'secondary', 'description' => __( 'The secondary widget area.' , 'nifty' ), 'before_widget' => '<div id="%1$s" class="widget %2$s"><div class="widget-inside">', 'after_widget' => "</div></div>", 'before_title' => '<h3 class="widget-title">', 'after_title' => '</h3>' ) );

	/* Register the subsidiary sidebar. */
	if ( in_array( 'subsidiary', $sidebars[0] ) )
		register_sidebar( array ( 'name' => __( 'Subsidiary Widget Area' , 'nifty' ), 'id' => 'subsidiary', 'description' => __( 'A widget area loaded in the footer of the site.' , 'nifty' ), 'before_widget' => '<div id="%1$s" class="widget %2$s"><div class="widget-inside">', 'after_widget' => "</div></div>", 'before_title' => '<h3 class="widget-title">', 'after_title' => '</h3>' ) );
}
