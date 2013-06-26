<?php

// Remove stock genesis functionality
remove_action( 'genesis_loop', 'genesis_do_loop' );
remove_all_actions( 'genesis_sidebar' );

// Include our custom colorbox
add_action('wp_enqueue_scripts', 'dma_colorbox_add');
function dma_colorbox_add() {

	wp_deregister_style( 'mvp-colorbox' );
	wp_register_style( 'mvp-colorbox', get_stylesheet_directory_uri(). '/lib/css/mvp-colorbox.css', array( 'colorbox5' ) );

	if ( function_exists( 'wds_colorbox' ) )
		wds_colorbox(5);
	wp_enqueue_style( 'mvp-colorbox' );

}

// Filter the body class to include custom classes for this page
add_filter( 'body_class', 'dma_activity_page_class' );
function dma_activity_page_class( $classes ) {
	$classes[] = 'activity';
	return $classes;
}

/**
 * Create Sort By menu and display a list of eligible activities for a user to check-in to.
 *
 * @since  1.0
 */
add_action( 'genesis_after_header', 'dma_activities_page' );
function dma_activities_page() {

	$filter = isset( $_GET['filter'] ) ? $_GET['filter'] : 'all';
	$style = ( 'all' != $filter ) ? ' style="visibility: hidden;"' : '';

	// echo '<p class="note above-filter"'. $style .'>report your latest activity &mdash; or browse for a new one</p>';
	// echo '<div class="center-buttons">';
	// 	echo '<ul class="filter-buttons-wrap button-group buttons">';
	// 		dma_filter_menu_item( 'All', 'btn-left' );
	// 		dma_filter_menu_item( 'Group Exercise', 'btn-mid' );
	// 		dma_filter_menu_item( 'Fitness Training', 'btn-mid' );
	// 		dma_filter_menu_item( 'Sports', 'btn-right' );
	// 	echo '</ul>';
	// echo '</div>';

	// Grab our fitness types transient
	$activity_types = dma_activities_list();

	// If our transient isn't empty, loop through it for output
	if ( ! empty( $activity_types ) ) {
		// @TODO: change this to an AJAX call so we instantly filter with no loading delay
		echo dma_display_activities( $activity_types, $filter );
	}

}

/**
 * Helper function to generate Sort By menu
 *
 * @since  1.0
 */
function dma_filter_menu_item( $item, $classes = '' ) {

	// @TODO: Change this to use #anchor links instead of URL queries so we can use AJAX
	$current = isset( $_GET['filter'] ) ? $_GET['filter'] : 'all';

	$slug = strtolower( str_replace( ' ', '-', $item ) );
	$classes = empty( $classes ) ? array() : array( $classes );
	$classes[] = ( $current == $slug ) ? 'current' : '';
	$classes[] = 'filter-'. $slug .' button large';

	$url = $slug == 'all' ? remove_query_arg( 'filter' ) : '';
	dma_li_a( 'filter', $item, $slug, $classes, '', $url );
}

genesis();
