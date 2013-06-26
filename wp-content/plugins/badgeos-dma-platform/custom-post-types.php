<?php

add_action( 'init', 'dma_register_custom_post_types' );

//register custom post types
function dma_register_custom_post_types() {

	register_post_type( 'activity', array(
		'labels'             => array(
			'name'               => _x( 'Activities', 'post type general name' ),
			'singular_name'      => _x( 'Activity', 'post type singular name' ),
			'add_new'            => _x( 'Add New', 'Activity' ),
			'add_new_item'       => __( 'Add New Activity' ),
			'edit_item'          => __( 'Edit Activity' ),
			'new_item'           => __( 'New Activity' ),
			'all_items'          => __( 'Activities' ),
			'view_item'          => __( 'View Activity' ),
			'search_items'       => __( 'Search Activities' ),
			'not_found'          =>	__( 'No Activities found' ),
			'not_found_in_trash' => __( 'No  Activities found in Trash' ),
			'parent_item_colon'  => '',
			'menu_name'          => __( 'Activities' ),
		),
		'public'             => true,
		'publicly_queryable' => true,
		'show_ui'            => true,
		'show_in_menu'       => 'badgestack_badgestack',
		'query_var'          => true,
		'rewrite'            => true,
		'capability_type'    => 'post',
		'has_archive'        => true,
		'hierarchical'       => false,
		'menu_position'      => null,
		'supports'           => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments' )
	) );

	register_post_type( 'badge', array(
		'labels'             => array(
			'name'               => _x( 'Badges', 'post type general name' ),
			'singular_name'      => _x( 'Badge', 'post type singular name' ),
			'add_new'            => _x( 'Add New', 'Badge' ),
			'add_new_item'       => __( 'Add New Badge' ),
			'edit_item'          => __( 'Edit badge' ),
			'new_item'           => __( 'New badge' ),
			'all_items'          => __( 'Badges' ),
			'view_item'          => __( 'View badge' ),
			'search_items'       => __( 'Search badges' ),
			'not_found'          =>	__( 'No badges found' ),
			'not_found_in_trash' => __( 'No badges found in Trash' ),
			'parent_item_colon'  => '',
			'menu_name'          => __( 'Badges' ),
		),
		'public'             => true,
		'publicly_queryable' => true,
		'show_ui'            => true,
		'show_in_menu'       => 'badgestack_badgestack',
		'query_var'          => true,
		'rewrite'            => array( 'slug' => 'fun-badge' ),
		'capability_type'    => 'post',
		'has_archive'        => true,
		'hierarchical'       => true,
		'menu_position'      => null,
		'supports'           => array( 'title', 'editor', 'excerpt', 'author', 'thumbnail', 'page-attributes' )
	) );
	badgestack_register_achievement_type( 'Badge', 'Badges' );

	register_post_type( 'dma-step', array(
		'labels'             => array(
			'name'               => _x( 'Steps', 'post type general name' ),
			'singular_name'      => _x( 'Step', 'post type singular name' ),
			'add_new'            => _x( 'Add New', 'Step' ),
			'add_new_item'       => __( 'Add Step' ),
			'edit_item'          => __( 'Edit Step' ),
			'new_item'           => __( 'New Step' ),
			'all_items'          => __( 'Steps' ),
			'view_item'          => __( 'View Step' ),
			'search_items'       => __( 'Search Steps' ),
			'not_found'          =>	__( 'No Steps found' ),
			'not_found_in_trash' => __( 'No Steps found in Trash' ),
			'parent_item_colon'  => '',
			'menu_name'          => __( 'Steps' ),
		),
		'public'             => true,
		'publicly_queryable' => true,
		'show_ui'            => false,
		'show_in_menu'       => false,
		'query_var'          => true,
		'rewrite'            => true,
		'capability_type'    => 'post',
		'has_archive'        => true,
		'hierarchical'       => false,
		'menu_position'      => null,
		'supports'           => array( 'title', 'editor' )
	) );
	badgestack_register_achievement_type( 'Step', 'Steps' );

	register_post_type( 'checkin', array(
		'labels'             => array(
			'name'               => _x( 'Check-ins', 'post type general name' ),
			'singular_name'      => _x( 'Check-in', 'post type singular name' ),
			'add_new'            => _x( 'Add New', 'Check-in' ),
			'add_new_item'       => __( 'Add New Check-in' ),
			'edit_item'          => __( 'Edit Check-in' ),
			'new_item'           => __( 'New Check-in' ),
			'all_items'          => __( 'Check-ins' ),
			'view_item'          => __( 'View Check-in' ),
			'search_items'       => __( 'Search Check-ins' ),
			'not_found'          =>	__( 'No Check-ins found' ),
			'not_found_in_trash' => __( 'No Check-ins found in Trash' ),
			'parent_item_colon'  => '',
			'menu_name'          => __( 'Check-ins' ),
		),
		'public'             => true,
		'publicly_queryable' => true,
		'show_ui'            => true,
		'show_in_menu'       => 'badgestack_badgestack',
		'query_var'          => true,
		'rewrite'            => true,
		'capability_type'    => 'post',
		'has_archive'        => true,
		'hierarchical'       => false,
		'menu_position'      => null,
		'supports'           => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments' )
	) );

	register_post_type( 'dma-location', array(
		'labels'             => array(
			'name'               => _x( 'Locations', 'post type general name' ),
			'singular_name'      => _x( 'Location', 'post type singular name' ),
			'add_new'            => _x( 'Add New', 'Location' ),
			'add_new_item'       => __( 'Add New Location' ),
			'edit_item'          => __( 'Edit Location' ),
			'new_item'           => __( 'New Location' ),
			'all_items'          => __( 'Locations' ),
			'view_item'          => __( 'View Location' ),
			'search_items'       => __( 'Search Locations' ),
			'not_found'          =>	__( 'No Locations found' ),
			'not_found_in_trash' => __( 'No Locations found in Trash' ),
			'parent_item_colon'  => '',
			'menu_name'          => __( 'Locations' ),
		),
		'public'             => true,
		'publicly_queryable' => true,
		'show_ui'            => true,
		'show_in_menu'       => 'badgestack_badgestack',
		'query_var'          => true,
		'rewrite'            => true,
		'capability_type'    => 'post',
		'has_archive'        => true,
		'hierarchical'       => false,
		'menu_position'      => null,
		'supports'           => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt' )
	) );

	register_post_type( 'dma-event', array(
		'labels'             => array(
			'name'               => _x( 'Events', 'post type general name' ),
			'singular_name'      => _x( 'Event', 'post type singular name' ),
			'add_new'            => _x( 'Add New', 'Event' ),
			'add_new_item'       => __( 'Add New Event' ),
			'edit_item'          => __( 'Edit Event' ),
			'new_item'           => __( 'New Event' ),
			'all_items'          => __( 'Events' ),
			'view_item'          => __( 'View Event' ),
			'search_items'       => __( 'Search Events' ),
			'not_found'          =>	__( 'No Events found' ),
			'not_found_in_trash' => __( 'No Events found in Trash' ),
			'parent_item_colon'  => '',
			'menu_name'          => __( 'Events' ),
		),
		'public'             => true,
		'publicly_queryable' => true,
		'show_ui'            => true,
		'show_in_menu'       => 'badgestack_badgestack',
		'query_var'          => true,
		'rewrite'            => true,
		'capability_type'    => 'post',
		'has_archive'        => true,
		'hierarchical'       => false,
		'menu_position'      => null,
		'supports'           => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments' )
	) );

	register_post_type( 'social-comments', array(
		'labels'             => array(
			'name'               => _x( 'Social Comments', 'post type general name' ),
			'singular_name'      => _x( 'Social Comment', 'post type singular name' ),
			'add_new'            => _x( 'Add New', 'Social Comment' ),
			'add_new_item'       => __( 'Add New Social Comment' ),
			'edit_item'          => __( 'Edit Social Comment' ),
			'new_item'           => __( 'New Social Comment' ),
			'all_items'          => __( 'Social Comments' ),
			'view_item'          => __( 'View Social Comment' ),
			'search_items'       => __( 'Search Social Comments' ),
			'not_found'          =>	__( 'No Social Comments found' ),
			'not_found_in_trash' => __( 'No Social Comments found in Trash' ),
			'parent_item_colon'  => '',
			'menu_name'          => __( 'Social Comments' ),
		),
		'public'             => true,
		'publicly_queryable' => true,
		'show_ui'            => true,
		'show_in_menu'       => 'badgestack_badgestack',
		'query_var'          => true,
		'rewrite'            => true,
		'capability_type'    => 'post',
		'has_archive'        => true,
		'hierarchical'       => false,
		'menu_position'      => null,
		'supports'           => array( 'title', 'editor', 'author', 'thumbnail', 'custom-fields' )
	) );

	register_post_type( 'notices', array(
		'labels'             => array(
			'name'               => _x( 'User Notices', 'post type general name' ),
			'singular_name'      => _x( 'User Notice', 'post type singular name' ),
			'add_new'            => _x( 'Add New', 'User Notice' ),
			'add_new_item'       => __( 'Add New User Notice' ),
			'edit_item'          => __( 'Edit User Notice' ),
			'new_item'           => __( 'New User Notice' ),
			'all_items'          => __( 'User Notices' ),
			'view_item'          => __( 'View User Notice' ),
			'search_items'       => __( 'Search User Notices' ),
			'not_found'          =>	__( 'No User Notices found' ),
			'not_found_in_trash' => __( 'No User Notices found in Trash' ),
			'parent_item_colon'  => '',
			'menu_name'          => __( 'User Notices' ),
		),
		'public'             => true,
		'publicly_queryable' => true,
		'show_ui'            => true,
		'show_in_menu'       => 'badgestack_badgestack',
		'query_var'          => true,
		'rewrite'            => true,
		'capability_type'    => 'post',
		'has_archive'        => true,
		'hierarchical'       => false,
		'menu_position'      => null,
		'supports'           => array( 'editor', 'author' )
	) );
}

add_filter( 'manage_edit-notices_columns', 'dma_notice_admin_columns' );
/**
 * Reorganize notice cpt admin columns
 */
function dma_notice_admin_columns( $columns ) {
	$columns = array(
		'cb' => '<input type="checkbox" />',
		'title' => 'Edit',
		'noticecontent' => 'Notice Text',
		'date' => 'Date',
		'author' => 'Author',
	);
	return $columns;
}

add_filter( 'the_title', 'dma_notices_title_display', 10, 2 );
/**
 * Replace title with edit text
 */
function dma_notices_title_display( $title, $id ) {

	if ( !is_admin() || get_current_screen()->id != 'edit-notices' )
		return $title;

	return __( 'Edit Notice', 'dma' );
}

add_action( 'manage_posts_custom_column', 'dma_notice_admin_columns_display' );
/**
 * Display notice content in column
 */
function dma_notice_admin_columns_display( $column ) {
	global $post;
	if ( $column == 'noticecontent' )
		the_content();
}

add_action( 'admin_footer', 'dma_adjust_notice_column_widths' );
/**
 * Adjust width of notice admin columns
 */
function dma_adjust_notice_column_widths() {
	if ( get_current_screen()->id != 'edit-notices' )
		return;
	?>
	<style type="text/css">
	.widefat .column-noticecontent {
		width: 60%;
	}
	.widefat .column-title {
		width: 120px;
	}
	</style>
	<?php
}
