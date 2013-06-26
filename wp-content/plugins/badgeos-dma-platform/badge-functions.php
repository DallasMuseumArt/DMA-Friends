<?php

add_action( 'wp_login', 'dma_check_active_badges_for_expiration', 10, 2 );
/**
 * Check in on our user's active fun badges to see if they've expired
 *
 * Quits any expired fun badges.
 *
 * @param  [type] $user_login [description]
 * @param  object $user       The current user's $user object
 */
function dma_check_active_badges_for_expiration( $user_login, $user ) {

	// Grab our user's active fun badges
	$badges = dma_get_users_active_badges( $user->ID );

	// Loop through each badge
	foreach ( $badges as $key => $badge ) {

		// If the badge has a maximum completion time between steps, and we've exceeded that time...
		if ( $max_time = get_post_meta( $badge->ID, '_dma_badge_maximum_time', true )
			&& time() - $badge->date_last_step_earned > $max_time
		) {
			// Quit the badge
			dma_quit_active_badge_for_user( $user->ID, $badge->ID );
		}
	}

}

/**
 * Quit's a user's active badge
 *
 * @param  int $user_id  The given user's ID
 * @param  int $badge_id The given badge's ID
 */
function dma_quit_active_badge_for_user( $user_id, $badge_id ) {

	// Grab our user's active fun badges
	$badges = dma_get_users_active_badges( $user_id );

	// Loop through each badge
	foreach ( $badges as $key => $badge ) {
		// If our provided badge ID is in the array, unset it
		if ( $badge_id == $badge->ID )
			unset( $badges[$key] );
	}

	// Update the Badgestack Log to note we've quit the challenge
	$log_entry_id = badgestack_post_log_entry( $badge_id, $user_id, 'quit' );
	update_post_meta( $log_entry_id, '_dma_badge_status', 'quit' );

	// Update the user's active fun badge array
	$update_status = update_user_meta( $user_id, '_dma_active_badges', $badges );

}

/**
 * Save user's badge data with each earned step
 *
 * @since  1.0
 * @param  integer $user_id         The given user's ID
 * @param  integer $achievement_id  The given achievement's ID
 * @return object                   The final badge object
 */
add_action( 'badgestack_award_achievement', 'dma_save_badge_data', 10, 2 );
function dma_save_badge_data( $user_id, $achievement_id ) {

	// Grab our achievement post based on the provided ID
	$achievement = get_post( $achievement_id );

	// If achievement is not a step, bail here
	if ( 'dma-step' != $achievement->post_type )
		return;

	// Grab our parent achievement, if it's not a badge, bail here
	$parent_badge = badgestack_get_parent_of_achievement( $achievement_id );
	if ( 'badge' != $parent_badge->post_type )
		return;

	// If the user was already working on the given fun badge...
	if ( dma_is_user_engaged_in_badge( $user_id, $parent_badge->ID ) ) {
		// Grab object data about this badge from the user's meta
		$badge = dma_get_users_active_badge_details( $user_id, $parent_badge->ID );

		// Set the last step earned date to right now
		$badge->date_last_step_earned = time();

	// Otherwise, they're just beginning the fun badge...
	} else {
		// Create a new object based on the fun badge
		$badge = $parent_badge;

		// Set our started date and step last earned date to right now
		$badge->date_started = time();
		$badge->date_last_step_earned = time();

	}

	// Update the user's meta with new badge activity
	dma_update_users_active_badges( $user_id, $badge );

	// Return the badge object
	return $badge;

}

add_action( 'badgestack_award_achievement', 'dma_maybe_delete_active_badge', 10, 2 );
/**
 * See if a badge should be dropped from the active fun badge array
 *
 * Check to see if an awarded achievement is a badge and
 * delete it from the active array if so.
 *
 * @param  int $user_id        The given user's ID
 * @param  int $achievement_id The given achievement's ID
 * @return bool                False if we don't remove, true if we do
 */
function dma_maybe_delete_active_badge( $user_id, $achievement_id ) {

	// Grab the achievement post object
	$achievement = get_post( $achievement_id );

	// If the achievement is not a badge, bail here
	if ( 'badge' != $achievement->post_type )
		return false;

	// Otherwise, it IS a badge, so let's drop it from the user's active badges
	dma_delete_active_badge( $user_id, $achievement_id );

	return true;
}

/**
 * Drop the given fun badge from the user's active fun badge array
 *
 * @param  int $user_id      The given user's ID
 * @param  int $badge_id The given fun badge's ID
 */
function dma_delete_active_badge( $user_id, $badge_id ) {

	// Grab our user's list of active fun badges
	$badges = dma_get_users_active_badges( $user_id );

	// Loop through each badge
	foreach ( $badges as $key => $badge )
		// If we find a match on our provided badge ID, drop it
		if ( $badge->ID == $badge_id )
			unset( $badges[$key] );

	// Update the active fun badge meta
	update_user_meta( $user_id, '_dma_active_badges', $badges );
}

/**
 * Conditional to determine whether or not a user is working on a given fun badge
 *
 * @param  int $user_id      The given user's ID
 * @param  int $badge_id The given fun badge's ID
 * @return bool              True if user is working on the badge, false otherwise
 */
function dma_is_user_engaged_in_badge( $user_id, $badge_id ) {

	// Grab our user's list of active fun badges
	$badges = dma_get_users_active_badges( $user_id );

	// Loop through each badge
	foreach ( $badges as $badge )
		// If we find a match on our provided badge ID, return true
		if ( $badge->ID == $badge_id )
			return true;

	// Otherwise, return false
	return false;
}

/**
 * Get our user's active fun badges from user meta
 *
 * @param  int $user_id The given user's ID
 * @return array        An array of the user's active fun badges
 */
function dma_get_users_active_badges( $user_id ) {

	// Grab our user's active badges from meta
	$badges = get_user_meta( $user_id, '_dma_active_badges', true );

	// If we don't have any badges, return an empty array
	if ( empty( $badges ) || is_null( $badges ) )
		return array();

	// Otherwise, we DO have badges and should return them as an array
	return (array) $badges;
}

/**
 * Get details about a specific badge in the user's active array
 *
 * @param  int $user_id      The given user's ID
 * @param  int $badge_id The given fun badge's ID
 * @return obj               An object with relevant data about the badge
 */
function dma_get_users_active_badge_details( $user_id, $badge_id ) {

	// Grab our user's active badges from meta
	$badges = dma_get_users_active_badges( $user_id );

	// Loop through our badges until we find the one we're looking for
	foreach ( $badges as $badge )
		if ( $badge->ID == $badge_id )
			// Return our badge object
			return $badge;
}

/**
 * Update a fun badge's data in our user's active badge array
 *
 * @param  int $user_id   The given user's ID
 * @param  int $badge An object with relevant data about the badge
 */
function dma_update_users_active_badges( $user_id, $badge ) {

	// Grab our user's active badges from meta
	$badges = dma_get_users_active_badges( $user_id );

	// Loop through our badges...
	foreach ( $badges as $key => $badge ) {
		// If this badge is already in our array...
		if ( $badge->ID == $badge->ID ) {
			// Update the badge, our user meta, and bail
			$badges[$key] = $badge;
			update_user_meta( $user_id, '_dma_active_badges', $badges );
			return;
		}
	}

	// If we made it this far, the badge was NOT in the active meta, so let's add it
	$badges[] = $badge;

	// Update the user's meta
	update_user_meta( $user_id, '_dma_active_badges', $badges );

}

/**
 * Update a user's active fun badge's used DMA cards
 *
 * @param  int   $user_id       The given user's ID
 * @param  int   $badge_id  The given badge ID
 * @param  array $new_checkins An array of DMA cards to add to our array
 */
function dma_update_badge_used_checkins( $user_id, $badge_id, $new_checkins ) {

	// Grab our user's active badges from meta
	$badges = dma_get_users_active_badges( $user_id );

	// If the user is enrolled in any badges
	if ( ! empty( $badges ) ) {

		// Loop through our badges...
		foreach ( $badges as $badge ) {

			// If this badge is already in our array...
			if ( $badge->ID == $badge_id ) {

				// If we don't have ANY used_checkins, create a new array and add our checkins to it
				if ( ! isset( $badge->used_checkins ) || empty( $badge->used_checkins ) )
					$badge->used_checkins = $new_checkins;

				// Otherwise, add these new cards to our existing array
				else
					$badge->used_checkins = array_merge( $badge->used_checkins, $new_checkins );

				// Update our active badge with he newly used time
				return dma_update_users_active_badges( $user_id, $badge );

			}
		}
	}

	// If we made it here, the user isn't engaged in this badge, yet...

		// Grab the post for our given badge ID
		$badge = get_post( $badge_id );

		// Set our started date and step last earned date to right now
		$badge->date_started = time();
		$badge->date_last_step_earned = time();

		// Don't forget to add our used check-ins
		$badge->used_checkins = $new_checkins;


	// Finally, update the user's meta with new badge activity
	return dma_update_users_active_badges( $user_id, $badge );

}

/**
 * Helper function for getting the last earned UNIX timestamp for a badge.
 *
 * @since  1.0
 * @param  integer $badge_id The given badge's post ID
 * @param  integer $user_id  The given user's ID
 * @return integer           The UNIX timestamp for the last reported badge activity
 */
function dma_badge_last_user_activity( $badge_id, $user_id ) {

	// Assume the user has no history with this badge
	$date = 0;

	// See if the user has ever earned or failed the badge
	$user_badge_history = get_posts( array(
		'author'        => $user_id,
		'post_type'     => 'badgestack-log-entry',
		'meta_key'      => '_badgestack_log_achievement_id',
		'meta_value'    => $badge_id
	) );

	// If the user DOES have some history with this badge, grab the last interaction time
	if ( ! empty( $user_badge_history ) )
		$date = strtotime( $user_badge_history[0]->post_date_gmt );

	// Finally, return our time
	return $date + 5;

}
