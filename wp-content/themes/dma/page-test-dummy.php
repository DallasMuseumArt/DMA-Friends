<?php

// Remove stock Genesis functionality
remove_action( 'genesis_loop', 'genesis_do_loop' );
remove_all_actions( 'genesis_sidebar' );

// Filter the body class to include custom classes for this page
add_filter( 'body_class', 'dma_activity_page_class' );
function dma_activity_page_class( $classes ) {
	$classes[] = 'activity-logged';
	return $classes;
}

// Enqueue our colorbox
add_action('wp_enqueue_scripts', 'dma_colorbox_etc_add');
function dma_colorbox_etc_add() {

	wp_deregister_style( 'mvp-colorbox' );
	wp_register_style( 'mvp-colorbox', get_stylesheet_directory_uri(). '/lib/css/mvp-colorbox.css', array( 'colorbox5' ) );

	if ( function_exists( 'wds_colorbox' ) )
		wds_colorbox(5);
	wp_enqueue_style( 'mvp-colorbox' );

}

// Output our custom page content
add_action( 'genesis_after_header', 'dma_activity_logged_page' );
function dma_activity_logged_page() {

}

genesis();
