<?php
/*
Plugin Name: P2P Bundle Example
Author: scribu
*/

require dirname( __FILE__ ) . '/scb/load.php';

scb_init( '_badgestack_p2p_load' );

function _badgestack_p2p_load() {
	add_action( 'plugins_loaded', '_badgestack_load_p2p_core', 20 );
	add_action( 'init', 'badgestack_connection_types', 20 );
}

function _badgestack_load_p2p_core() {
	if ( function_exists( 'p2p_register_connection_type' ) )
		return;

	define( 'P2P_PLUGIN_VERSION', '1.4-beta' );

	define( 'P2P_TEXTDOMAIN', 'badgestack' );

	foreach ( array(
		'storage', 'query', 'query-post', 'query-user', 'url-query',
		'util', 'side', 'type-factory', 'type', 'directed-type', 'indeterminate-type',
		'api', 'list', 'extra', 'item',
	) as $file ) {
		require dirname( __FILE__ ) . "/p2p-core/$file.php";
	}

	if ( is_admin() ) {
		foreach ( array( 'mustache', 'factory',
			'box-factory', 'box', 'fields',
			'column-factory', 'column',
			'tools'
		) as $file ) {
			require dirname( __FILE__ ) . "/p2p-admin/$file.php";
		}
	}

	// TODO: can't use activation hook
	add_action( 'admin_init', array( 'P2P_Storage', 'install' ) );
}

/**
 * Create P2P connection types for all our custom achievement types
 *
 *
 */
function badgestack_connection_types() {

	// Filter for bailing if a customized relationship schema is desired.
	if ( ! apply_filters( 'register_default_badge_relationships', true ) )
		return;

	p2p_register_connection_type( array(
		'name' => 'badge_to_badge',
		'from' => 'badge',
		'to' => 'badge',
		'title' => 'Badges required for this badge',
		'fields' => array(
			'order' => array(
				'title' => 'Order',
				'type' => 'text',
				'default' => 0
				),
			'required' => array(
				'title' => 'Required',
				'type' => 'select',
				'values' => array( 'Required', 'Optional' ),
				'default' => 'Required',
			)
		)
	) );

	p2p_register_connection_type( array(
		'name' => 'step_to_badge',
		'from' => 'step',
		'to' => 'badge',
		'title' => 'Steps required for this badge',
		'fields' => array(
			'order' => array(
				'title' => 'Order',
				'type' => 'text',
				'default' => 0
				),
			'required' => array(
				'title' => 'Required',
				'type' => 'select',
				'values' => array( 'Required', 'Optional' ),
				'default' => 'Required',
			)
		)
	) );
}

add_filter( 'p2p_admin_box_show', 'hide_some_meta_boxes', 10, 3 );

/**
 * Hides half of the meta boxes created by P2P.
 *
 * Since connections are by nature reciprocal,
 * this deals with the issue of showing both quests required by this badge
 * AND quess that require this badge
 */
function hide_some_meta_boxes( $show, $directed, $post ) {
	$direction = $directed->get_direction();
	if ( $direction == 'from' )
		$show = false;
	return $show;
}
