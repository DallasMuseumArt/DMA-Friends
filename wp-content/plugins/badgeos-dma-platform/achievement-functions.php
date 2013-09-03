<?php

/**
 * Cache buster to delete DMA Badge query transient
 *
 * Attached to post edit/delete/restore so badge list is always accurate
 *
 * @since 1.0.1
 * @param integer $post_id The ID of the given post
 * @param object  $post    The post object
 */
function dma_badge_bust_cache( $post_id ) {
	if ( 'badge' == get_post_type( $post_id ) )
		delete_transient( 'dma_badges' );
}
add_action( 'save_post', 'dma_badge_bust_cache' );
add_action( 'trashed_post', 'dma_badge_bust_cache' );
add_action( 'untrash_post', 'dma_badge_bust_cache' );

/**
 * Add extra meta data to earned achievement objects for a specific user.
 * Adds location_earned, reflecting the location_id of the Y, and points_earned.
 *
 * @since  1.0.0
 * @param  object  $achivement_object The achievement object being built
 * @param  integer $user_id           The user's ID
 * @return object                     The updated achievement object
 */
function dma_awarded_achievement_object_details( $achievement_object, $user_id ) {

	// Add our location earned if we're in a specific location, otherwise null
	$achievement_object->location = ( $location_id = dma_get_current_location_id() ) ? $location_id : NULL;

	// Return our updated object
	return $achievement_object;
}
add_filter( 'achievement_object', 'dma_awarded_achievement_object_details', 10, 2 );

/**
 * Attempt to quit expired achievements on login
 *
 * @since 2.0.0
 * @param string $user_login The current user's username
 * @param object $user       The current user's $user object
 */
function dma_user_maybe_quit_active_achievement( $user_login, $user ) {

	// Get the user's active achievements
	$achievements = badgeos_user_get_active_achievements( $user->ID );

	// Loop through each achievement
	foreach ( $achievements as $key => $achievement ) {

		// If the achievement has a maximum time between steps...
		if ( $max_time_between_steps = get_post_meta( $achievement->ID, '_badgeos_time_between_steps_max', true ) ) {
			$minutes_elapsed = ( time() - $achievement->last_activity_date ) / MINUTE_IN_SECONDS;

			// If the time elapsed exceeds the max time, quit the achievement
			if ( $minutes_elapsed >= $max_time_between_steps )
				return dma_user_quit_active_achievement( $user->ID, $achievement->ID );
		}

		// If the achievement has a maximum completion time...
		if ( $max_time = get_post_meta( $achievement->ID, '_badgeos_maximum_time', true ) ) {
			$days_elapsed = ( time() - $achievement->date_started ) / DAY_IN_SECONDS;

			// If the time elapsed exceeds the max time, quit the achievement
			if ( $days_elapsed >= $max_time )
				return dma_user_quit_active_achievement( $user->ID, $achievement->ID );
		}
	}

}
add_action( 'wp_login', 'dma_user_maybe_quit_active_achievement', 10, 2 );

/**
 * Log an achievement as "quit" and update active achievement meta
 *
 * @since  2.0.0
 * @param  integer $user_id        User ID
 * @param  integer $achievement_id Achievement post ID
 */
function dma_user_quit_active_achievement( $user_id = 0, $achievement_id = 0 ) {

	// Update the BadgeOS Log to note we've "quit" the achievement
	badgeos_post_log_entry( $achievement_id, $user_id, 'quit' );

	// Grab the achievement data and bump the date started timestamp
	$achievement = badgeos_user_get_active_achievement( $user_id, $achievement_id );
	$achievement->date_started = time();

	// Return the updated achievement object
	badgeos_user_update_active_achievement( $user_id, $achievement_id, $achievement );

}
