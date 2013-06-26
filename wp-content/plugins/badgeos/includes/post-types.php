<?php
/**
 * BadgeStack Init
 *
 * @package BadgeStack
 */

add_action( 'init', 'badgestack_register_post_types' );

function badgestack_register_post_types() {
	global $badgestack;
	//register Badge Type CPT
	$labels = array(
		'name' => __( 'Achievement Types', 'badgestack' ),
		'singular_name' => __( 'Achievement Type', 'badgestack' ),
		'add_new' => __( 'Add New', 'badgestack' ),
		'add_new_item' => __( 'Add New Achievement Type', 'badgestack' ),
		'edit_item' => __( 'Edit Achievement Type', 'badgestack' ),
		'new_item' => __( 'New Achievement Type', 'badgestack' ),
		'all_items' => __( 'Achievement Types', 'badgestack' ),
		'view_item' => __( 'View Achievement Type', 'badgestack' ),
		'search_items' => __( 'Search Achievement Types', 'badgestack' ),
		'not_found' => __( 'No achievement types found', 'badgestack' ),
		'not_found_in_trash' => __( 'No achievement types found in Trash', 'badgestack' ), 
		'parent_item_colon' => '',
		'menu_name' => __( 'Achievement Types', 'badgestack' )
	);
	
	$args = array(
		'labels' => $labels,
		'public' => false,
		'publicly_queryable' => false,
		'show_ui' => true, 
		'show_in_menu' => true, 
		'query_var' => false,
		'rewrite' => false,
		'capability_type' => 'post',
		'has_archive' => false, 
		'hierarchical' => false,
		'menu_position' => null,
		'supports' => array( 'title', 'editor', 'author', 'thumbnail' )
	); 

	register_post_type( 'achievement-type', $args );
	
	//register additional CPTs
	$args = array(
		'post_type'			=>	'achievement-type',
		'posts_per_page'	=>	'-1',
	);
	
	$achievement_types = get_posts( $args );

	// The Loop
	foreach ( $achievement_types as $achievement_type ) {
		// TODO: Need to change this, what if we use "User Statuses"? It will be "User Statuse" for the singular name
		badgestack_register_achievement_type( rtrim( $achievement_type->post_title, 's' ), $achievement_type->post_title );
		$achievement_name_plural = strtolower( $achievement_type->post_title );
		$achievement_name_singular = rtrim( $achievement_type->post_title, 's' ); // TODO: Need to change this, what if we use "User Statuses"? It will be "User Statuse" for the singular name
		$labels = array(
			'name' => __( $achievement_type->post_title, 'badgestack' ),
			'singular_name' => __( $achievement_name_singular, 'badgestack' ),
			'add_new' => __( 'Add New', 'badgestack' ),
			'add_new_item' => __( 'Add New ' . $achievement_name_singular, 'badgestack' ),
			'edit_item' => __( 'Edit ' . $achievement_name_singular, 'badgestack' ),
			'new_item' => __( 'New ' . $achievement_name_singular, 'badgestack' ),
			'all_items' => __( $achievement_type->post_title, 'badgestack' ),
			'view_item' => __( 'View ' . $achievement_name_singular, 'badgestack' ),
			'search_items' => __( 'Search ' . $achievement_type->post_title, 'badgestack' ),
			'not_found' => __( 'No ' . strtolower( $achievement_type->post_title ) . ' found', 'badgestack' ),
			'not_found_in_trash' => __( 'No ' . strtolower( $achievement_type->post_title ) . ' found in Trash', 'badgestack' ), 
			'parent_item_colon' => '',
			'menu_name' => __( $achievement_type->post_title, 'badgestack' )
		);

		$args = array(
			'labels' => $labels,
			'public' => true,
			'publicly_queryable' => true,
			'show_ui' => true, 
			'show_in_menu' => 'badgestack_badgestack', 
			'query_var' => true,
			'rewrite' => array( 'slug' => $achievement_name_plural ),
			'capability_type' => 'post',
			'has_archive' => true, 
			'hierarchical' => true,
			'menu_position' => null,
			'supports' => array( 'title', 'editor', 'author', 'thumbnail', 'page-attributes' )
		);
	
		register_post_type( $achievement_name_singular, $args );
	}

	// Reset Post Data
	wp_reset_postdata();
	
	//register Submissions CPT
	$labels = array(
		'name' => __( 'Submissions', 'badgestack' ),
		'singular_name' => __( 'Submission', 'badgestack' ),
		'add_new' => __( 'Add New', 'badgestack' ),
		'add_new_item' => __( 'Add New Submission', 'badgestack' ),
		'edit_item' => __( 'Edit Submission', 'badgestack' ),
		'new_item' => __( 'New Submission', 'badgestack' ),
		'all_items' => __( 'Submissions', 'badgestack' ),
		'view_item' => __( 'View Submission', 'badgestack' ),
		'search_items' => __( 'Search Submissions', 'badgestack' ),
		'not_found' => __( 'No submissions found', 'badgestack' ),
		'not_found_in_trash' => __( 'No submissions found in Trash', 'badgestack' ), 
		'parent_item_colon' => '',
		'menu_name' => __( 'Submissions', 'badgestack' )
	);
  
	$args = array(
		'labels' => $labels,
		'public' => true,
		'publicly_queryable' => true,
		'show_ui' => true, 
		'show_in_menu' => 'badgestack_badgestack', 
		'show_in_nav_menus' => false, 
		'query_var' => true,
		'rewrite' => true,
		'capability_type' => 'post',
		'has_archive' => true, 
		'hierarchical' => false,
		'menu_position' => null,
		'supports' => array( 'title', 'editor', 'author', 'comments' )
	); 

	register_post_type( 'submission', $args );
	
	
	//register Nominations CPT
	$labels = array(
		'name' => __( 'Nominations', 'badgestack' ),
		'singular_name' => __( 'Nomination', 'badgestack' ),
		'add_new' => __( 'Add New', 'badgestack' ),
		'add_new_item' => __( 'Add New Nomination', 'badgestack' ),
		'edit_item' => __( 'Edit Nomination', 'badgestack' ),
		'new_item' => __( 'New Nomination', 'badgestack' ),
		'all_items' => __( 'Nominations', 'badgestack' ),
		'view_item' => __( 'View Nomination', 'badgestack' ),
		'search_items' => __( 'Search Nominations', 'badgestack' ),
		'not_found' => __( 'No nominations found', 'badgestack' ),
		'not_found_in_trash' => __( 'No nominations found in Trash', 'badgestack' ), 
		'parent_item_colon' => '',
		'menu_name' => __( 'Nominations', 'badgestack' )
	);
  
	$args = array(
		'labels' => $labels,
		'public' => true,
		'publicly_queryable' => true,
		'show_ui' => true, 
		'show_in_menu' => 'badgestack_badgestack', 
		'show_in_nav_menus' => false, 
		'query_var' => true,
		'rewrite' => true,
		'capability_type' => 'post',
		'has_archive' => true, 
		'hierarchical' => false,
		'menu_position' => null,
		'supports' => array( 'title', 'editor', 'author', 'comments' )
	); 

	register_post_type( 'nomination', $args );
	
	//register Log Entries CPT
	$labels = array(
		'name' => __( 'Log Entries', 'badgestack' ),
		'singular_name' => __( 'Log Entry', 'badgestack' ),
		'add_new' => __( 'Add New', 'badgestack' ),
		'add_new_item' => __( 'Add New Log Entry', 'badgestack' ),
		'edit_item' => __( 'Edit Log Entry', 'badgestack' ),
		'new_item' => __( 'New Log Entry', 'badgestack' ),
		'all_items' => __( 'Log Entries', 'badgestack' ),
		'view_item' => __( 'View Log Entries', 'badgestack' ),
		'search_items' => __( 'Search Log Entries', 'badgestack' ),
		'not_found' => __( 'No Log Entries found', 'badgestack' ),
		'not_found_in_trash' => __( 'No Log Entries found in Trash', 'badgestack' ), 
		'parent_item_colon' => '',
		'menu_name' => __( 'Log Entries', 'badgestack' )
	);
  
	$args = array(
		'labels' => $labels,
		'public' => true,
		'publicly_queryable' => true,
		'show_ui' => true, 
		'show_in_menu' => 'badgestack_badgestack', 
		'show_in_nav_menus' => false, 
		'query_var' => true,
		'rewrite' => array( 'slug' => 'log' ),
		'capability_type' => 'post',
		'has_archive' => true, 
		'hierarchical' => false,
		'menu_position' => null,
		'supports' => array( 'title', 'editor', 'author', 'comments' )
	); 

	register_post_type( 'badgestack-log-entry', $args );
}

function badgestack_register_achievement_type( $achievement_name_singular, $achievement_name_plural ) {
	global $badgestack;
	$badgestack->achievement_types[sanitize_title( $achievement_name_singular )] = array( 
		'single_name' => strtolower( $achievement_name_singular ),
		'plural_name' => strtolower( $achievement_name_plural ), 
	);
}
