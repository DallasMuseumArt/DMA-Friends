<?php

/**
 * Remove default BadgeOS log entry hooks
 *
 * @since 2.0.0
 */
function dma_remove_badgeos_log_entry_hooks() {
	remove_filter( 'badgeos_post_log_entry', 'badgeos_log_entry' );
	remove_action( 'badgeos_create_log_entry', 'badgeos_log_achievement_id' );
	remove_action( 'badgeos_update_users_points', 'badgeos_log_users_points' );
}
add_action( 'init', 'dma_remove_badgeos_log_entry_hooks' );

/**
 * Create a custom log entry
 *
 * @since  2.0.0
 * @param  integer $log_entry_id The created log entry ID
 * @param  array   $args         Additional arguments to use for creating the entry
 * @return integer               The created log entry ID
 */
function dma_post_log_entry( $log_entry_id, $args ) {

	// If we weren't explicitly given a title, let's build one
	if ( empty( $args['title'] ) ) {
		$user              = get_userdata( $args['user_id'] );
		$achievement       = get_post( $args['object_id'] );
		$achievement_types = badgeos_get_achievement_types();
		$achievement_type  = ( $achievement && isset( $achievement_types[$achievement->post_type]['single_name'] ) ) ? $achievement_types[$achievement->post_type]['single_name'] : '';
		$args['title']     = ! empty( $title ) ? $title : "{$user->user_login} {$args['action']} the \"{$achievement->post_title}\" {$achievement_type}";
	}

	// Setup our custom table params
	$table_params = array(
		'user_id'    => $args['user_id'],
		'object_id'  => $args['object_id'],
		'action'     => $args['action'],
		'title'      => $args['title'],
	);

	// Insert our custom log entry
	$log_entry_id = dma_insert_log_entry( $table_params );

	// Insert a custom activity stream entry
	if ( in_array( $args['action'], array( 'activity', 'checked-in', 'claimed-reward', 'event', 'unlocked' ) ) ) {
		unset( $table_params['title'] );
		dma_insert_activity_stream_entry( $table_params );
	}

	return $log_entry_id;
}
add_filter( 'badgeos_post_log_entry', 'dma_post_log_entry', 10, 2 );

/**
 * Log a user's earned points
 *
 * @since 2.0.0
 * @param integer $user_id        The user's ID
 * @param integer $new_points     The new points awarded
 * @param integer $total_points   The user's total points
 * @param integer $admin_id       The admin ID (if admin-awarded)
 * @param integer $achievement_id A connected achievement ID
 */
function dma_log_users_points( $user_id, $new_points, $total_points, $admin_id, $achievement_id ) {

	// Setup our user objects
	$user  = get_userdata( $user_id );
	$admin = get_userdata( $admin_id );

	// Alter our log message if this was an admin action
	if ( $admin_id )
		$title = sprintf( __( '%1$s awarded %2$s %3$s points for a new total of %4$s points', 'badgeos' ), $admin->user_login, $user->user_login, number_format( $new_points ), number_format( $total_points ) );
	else
		$title = sprintf( __( '%1$s earned %2$s points for a new total of %3$s points', 'badgeos' ), $user->user_login, number_format( $new_points ), number_format( $total_points ) );

	// Insert our custom log entry
	dma_insert_log_entry( array(
		'user_id'        => $user_id,
		'object_id'      => $achievement_id,
		'action'         => 'points',
		'title'          => $title,
		'awarded_points' => $new_points,
		'total_points'   => $total_points,
		'admin_id'       => $admin_id,
	) );

}
add_action( 'badgeos_update_users_points', 'dma_log_users_points', 10, 5 );

/**
 * Insert an activity stream entry to the database
 *
 * @since  2.0.0
 * @param  array  $args An associative array of our table columns and their data
 * @return mixed        ID of the inserted table row on success, false on failure
 */
function dma_insert_activity_stream_entry( $args = array() ) {
	global $wpdb;

	// Setup our default args
	$defaults = array(
		'user_id'    => get_current_user_id(),
		'object_id'  => 0,
		'action'     => null,
		'artwork_id' => null,
		'timestamp'  => current_time( 'mysql' )
	);
	$args = wp_parse_args( $args, $defaults );

	// Insert our stream item
	return $wpdb->insert(
		$wpdb->prefix . 'dma_activity_stream',
		array(
			'user_id'    => $args['user_id'],
			'object_id'  => $args['object_id'],
			'action'     => $args['action'],
			'artwork_id' => $args['artwork_id'],
			'timestamp'  => $args['timestamp'],
		),
		array(
			'%d', // user_id
			'%d', // object_id
			'%s', // action
			'%s', // artwork_id
			'%s', // timestamp
		)
	);
}

/**
 * Insert a log entry to the database
 *
 * @since  2.0.0
 * @param  array  $args An associative array of our table columns and their data
 * @return mixed        ID of the inserted table row on success, false on failure
 */
function dma_insert_log_entry( $args = array() ) {
	global $wpdb;

	// Setup our default args
	$defaults = array(
		'user_id'        => get_current_user_id(),
		'object_id'      => null,
		'action'         => null,
		'title'          => null,
		'artwork_id'     => null,
		'awarded_points' => null,
		'total_points'   => null,
		'admin_id'       => null,
		'timestamp'      => current_time( 'mysql' )
	);
	$args = wp_parse_args( $args, $defaults );

	// Insert our stream item
	return $wpdb->insert(
		$wpdb->prefix . 'dma_log_entries',
		array(
			'user_id'        => $args['user_id'],
			'object_id'      => $args['object_id'],
			'action'         => $args['action'],
			'title'          => $args['title'],
			'artwork_id'     => $args['artwork_id'],
			'awarded_points' => $args['awarded_points'],
			'total_points'   => $args['total_points'],
			'admin_id'       => $args['admin_id'],
			'timestamp'      => $args['timestamp'],
		),
		array(
			'%d', // user_id
			'%d', // object_id
			'%s', // action
			'%s', // title
			'%s', // artwork_id
			'%d', // awarded_points
			'%d', // total_points
			'%d', // admin_id
			'%s', // timestamp
		)
	);
}

