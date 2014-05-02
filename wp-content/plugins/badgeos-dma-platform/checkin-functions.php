<?php

use BadgeOS\LogEntry;

/**
 * Create a new DMA checkin.
 *
 * Attaches taxonomy data associated with the related activity to the DMA card.
 * Relates the DMA card to the location the member is reporting for.
 * Relates the activity to the DMA Card via P2P.
 *
 * @since  1.0.0
 * @param  integet $user_id      The given user's ID
 * @param  integet $activity_id  The activity's post ID
 * @param  integet $date         The date to use for logging the activity
 * @param  integet $location_id  The location where this activity was logged
 * @param  integet $accession_id The accession ID provided by the user during checkin
 * @param  bool    $is_admin     True if we're submitting from an admin form, false otherwise
 * @return bool                  True on successful check-in, false otherwise
 */
function dma_create_checkin( $user_id = 0, $activity_id = 0, $date = NULL, $location_id = 0, $accession_id = 0, $is_admin = false ) {
	global $wpdb;

	// Assume no check-in gets created
	$checked_in = false;

	// If no activity ID, bail now
	if ( ! $activity_id ) {
		return false;
    } else {
        $activity_id = absint($activity_id);
    }

	// If no user ID, get our current user
	if ( ! $user_id )
		$user_id = get_current_user_id();

    $user_id = absint($user_id);

	// Grab the current date if no date specified
	if ( ! $date )
		$date = current_time( 'mysql' );

	// Grab our current location ID if none specified
	if ( ! $location_id )
		$location_id = dma_get_current_location_id();

	// If the activity has a time lockout, and the user is locked out, prevent checkin
	if ( ! $is_admin && dma_is_activity_locked_for_user( $user_id, $activity_id ) )
		return false;

	// If the activity has any time-based restrictions, make sure we're not outside of those
	if ( ! $is_admin && dma_is_checkin_outside_time_restrictions( $activity_id ) )
		return false;

    // If we're dealing with "liked a work of art",
    // set our artwork_id based on the accession code
    $artwork_id = ( ARTWORK_OBJECT_ID == $activity_id ) ? $accession_id : null;

    if ($artwork_id) {
        // If we are sent an accession id then also log that the user liked a work of art
        badgeos_post_log_entry( $artwork_id, $user_id, 'artwork', "{$user_id} liked the work of art {$artwork_id}" );
    } else {
	    // Log this check-in
        $checked_in = badgeos_post_log_entry( $activity_id, $user_id, 'activity', "{$user_id} just checked-in using code {$accession_id}" );
    }

    // Find all steps associated to the activity and remove the cache
    $steps = $wpdb->get_results(
        "select p2p_to from {$wpdb->prefix}p2p where p2p_from = $activity_id and p2p_type = 'activity-to-step'"
    );
    foreach($steps as $step) {
        delete_transient('dma_checkin_steps_' . $user_id . ':' . $step->p2p_to);
    }

	// Add a hook so we can do other things too
	do_action( 'dma_create_checkin', $checked_in, $user_id, $activity_id, $date, $location_id, $accession_id, $is_admin );
    
	// Return our checked_in status
	return $checked_in;
}

/**
 * Award points to user after check-in
 *
 * @since 2.0.0
 * @param bool    $checked_in  True if checkin was successful, false otherwise
 * @param integer $user_id     The user's ID
 * @param integer $activity_id The activity's post ID
 */
function dma_checkin_award_user_points( $checked_in, $user_id, $activity_id ) {
	// If check-in was successful, award points
	if ( $checked_in )
		badgeos_award_user_points( $user_id, $activity_id );
}
add_action( 'dma_create_checkin', 'dma_checkin_award_user_points', 10, 3 );

/**
 * Maybe award achievements after successful check-in
 *
 * @since 2.0.0
 * @param bool    $checked_in True for successful check-in, false otherwise
 * @param integer $user_id    The given user's ID
 */
function dma_checkin_maybe_award_achievements( $checked_in, $user_id, $activity_id ) {

	// If we didn't successfully check-in, bail here
	if ( ! $checked_in )
		return false;

	// Get steps that require any activity
	$meta_value = ( 'activity' == get_post_type( $activity_id ) ) ? 'any-activity' : 'any-event';
	$any_activity = get_posts( array(
		'post_type'      => 'step',
		'meta_key'       => '_badgeos_trigger_type',
		'meta_value'     => $meta_value,
		'posts_per_page' => -1,
	) );

	// Get steps connected to our activity ID
	$connetion_type = ( 'activity' == get_post_type( $activity_id ) ) ? 'activity-to-step' : 'dma-event-to-step';
	$specific_activity = get_posts( array(
		'post_type'           => 'step',
		'posts_per_page'      => -1,
		'suppress_filters'    => false,
		'connected_direction' => 'from',
		'connected_type'      => $connetion_type,
		'connected_items'     => $activity_id
	) );

	// Get steps connected to our activity's taxonomy terms
	$taxonomy_achievements = dma_get_steps_from_activity_terms( $activity_id );

	// Merge our found achievements together
	$achievements = array_merge( $any_activity, $specific_activity, $taxonomy_achievements );

	// If we've found any achievements, attempt to award them
	if ( ! empty( $achievements ) ) {
		foreach ( $achievements as $achievement ) {
			badgeos_maybe_award_achievement_to_user( $achievement->ID, $user_id );
		}
	}

}
add_action( 'dma_create_checkin', 'dma_checkin_maybe_award_achievements', 10, 3 );

/**
 * Get steps that share an activity's taxonomy terms
 *
 * @since  2.0.0
 * @param  integer $activity_id The activity's post ID
 * @return array                An array of found posts, or empty array
 */
function dma_get_steps_from_activity_terms( $activity_id = 0 ) {

	// Assume we have no connected steps
	$taxonomy_achievements = $tax_query = array();

	// Setup the taxonomies we're testing
	$taxonomies = array(
		'activity-type',
		'activity-category',
		'event-type',
		'event-category'
	);

	// Loop through each taxonomy and look for connected terms
	foreach ( $taxonomies as $taxonomy ) {
		$terms = get_the_terms( $activity_id, $taxonomy );
		// If our activity has a connected term..
		if ( is_array( $terms ) ) {
			$tax_query[] = array(
				'taxonomy' => $taxonomy,
				'field' => 'slug',
				'terms' => wp_list_pluck( $terms, 'slug' )
			);
		}
	}

	// If we have any taxonomies, get the related steps
	if ( ! empty( $tax_query) ) {
		$tax_query['relation'] = 'OR';
		$taxonomy_achievements = get_posts( array(
			'post_type'      => 'step',
			'tax_query'      => $tax_query,
			'posts_per_page' => -1,
		) );
	}

	// Send back any found posts
	return $taxonomy_achievements;
}

/**
 * Find the relevant events or activities for a given step
 *
 * @since  2.0.0
 * @param  integer $step_id The step's post ID
 * @return array            An array of found posts, or an empty array
 */
function dma_find_relevant_activity_for_step( $step_id = 0 ) {

	// Get post IDs for all events/activities that are relevent to this step
	$activity_ids = array();
	$requirements = badgeos_get_step_requirements( $step_id );
	switch ( $requirements['trigger_type'] ) {
		case 'any-activity' :
		case 'any-event' :
			$activity_ids = get_posts( array(
				'post_type'      => substr( $requirements['trigger_type'], 4 ),
				'posts_per_page' => -1,
				'fields'         => 'ids'
			) );
			break;
		case 'activity' :
		case 'event' :
			$connetion_type = ( 'activity' == $requirements['trigger_type'] ) ? 'activity-to-step' : 'dma-event-to-step';
			$activity_ids = get_posts( array(
				'post_type'           => $requirements['trigger_type'],
				'posts_per_page'      => -1,
				'suppress_filters'    => false,
				'connected_direction' => 'to',
				'connected_type'      => $connetion_type,
				'connected_items'     => $step_id,
				'fields'              => 'ids'
			) );
			break;
		case 'activity-type' :
		case 'activity-category' :
		case 'event-type' :
		case 'event-category' :
			// Get the terms associated with our step
			$terms = get_the_terms( $step_id, $requirements['trigger_type'] );
			// If we have terms, use them to find events/activities with the same terms
			if ( is_array( $terms ) ) {
				$activity_ids = get_posts( array(
					'post_type'      => array( 'activity', 'dma-event' ),
					'fields'         => 'ids',
					'posts_per_page' => -1,
					'tax_query'      => array(
						array(
							'taxonomy' => $requirements['trigger_type'],
							'field'    => 'slug',
							'terms'    => wp_list_pluck( $terms, 'slug' )
						)
					),
				) );
			}
			break;
		default :
			break;
	}
	return $activity_ids;
}

/**
 * Find user check-ins relevant for a given step
 *
 * @since  2.0.0
 * @param  integer $user_id The given user's ID
 * @param  integer $step_id The given step's post ID
 * @return array            Found check-ins, or empty array
 */
function dma_find_user_checkins_for_step( $user_id, $step_id ) {
	global $wpdb;
    static $count;

    // The following reduces overhead for new users.
    // because the query can be expensive for each badge first
    // do a look up to see if the user has completed any activities
    // if the user has not, then its new so store the variable in a
    // static and look up against that for each additional badge
    if ($count === 0) {
        return array();
    } elseif ($count === NULL) {
        $count = count(LogEntry::user($user_id)->where('action', '=', 'activity')->get());
    }

    // Check for cached data first
    $cache_key = 'dma_checkin_steps_' . $user_id . ':' . $step_id;
    $checkins = get_transient($cache_key);
    if ($checkins !== FALSE) return $checkins;

	// Grab the parent achievement
	$parent_achievement = badgeos_get_parent_of_achievement( $step_id );

	// Get the timestamp for the parent's last recorded user activity (or 0 if none)
	if ( $date = badgeos_achievement_last_user_activity( $parent_achievement->ID, $user_id ) )
		$since = ( $date + ( get_option( 'gmt_offset' ) * 3600 ) );
	else
		$since = 0;

	// Get our relevant activity/event post IDs
	$relevant_activities = dma_find_relevant_activity_for_step( $step_id );

	// Get all relevant activity logged by a user
    $checkins = LogEntry::user($user_id)
        ->whereIn('object_id', $relevant_activities)
        ->where('timestamp', '>=', gmdate( 'Y-m-d H:i:s', $since ))
        ->get();

	// If the user is already working on the step's badge...
	if ( $active_achievement = badgeos_user_get_active_achievement( $user_id, $parent_achievement->ID ) ) {

		// If we have any already used check-ins,
		// exclude them from our relevant check-in array
		if ( isset( $active_achievement->used_checkins ) ) {
			$checkins = array_udiff( (array) $checkins, $active_achievement->used_checkins, 'dma_compare_checkins' );
		}
	}

    // Build cache
    set_transient($cache_key, $checkins, YEAR_IN_SECONDS);

	// Finally, return our relevant check-ins
	return $checkins;
}

/**
 * Helper function for comparing two check-ins
 *
 * Used in array_udiff to determine if IDs match or not
 *
 * @since  1.0.0
 * @param  object  $checkin_a An MVP Card post object
 * @param  object  $checkin_b Another MVP Card post object to compare
 * @return integer            Returns 0 if the ID's are the same, or -1 if they're not
 */
function dma_compare_checkins( $checkin_a, $checkin_b ) {
	return ( absint( $checkin_a->ID ) == absint( $checkin_b->ID ) ) ? 0 : -1;
}

/**
 * Conditional to test if activity is being logged outside the time limitations
 *
 * @since  1.0
 * @param  integer $activity_id The given activity's post ID
 * @return bool                 True if we're before or after the date limits, false if we're okay to proceed
 */
function dma_is_checkin_outside_time_restrictions( $activity_id = 0 ) {

	// Lets see if we have any time restrictions at all...
	if ( $time_restriction = get_post_meta( $activity_id, '_badgeos_time_restriction', true ) ) {

		// Don't forget about our time offset...
		$offset = time() + ( get_option('gmt_offset') * 3600 );

		// If the restrictions are day/time based...
		if ( 'hours' == $time_restriction ) {

			// Setup our time vars
			$today        = date('l', $offset );
			$current_time = strtotime( date('G:i:s', $offset ) );
			$allowed_days = get_post_meta( $activity_id, '_badgeos_time_restriction_days', false );
			$start_time   = strtotime( date( 'G:i:s', strtotime( get_post_meta( $activity_id, '_badgeos_time_restriction_hour_begin', true ) ) ) );
			$end_time     = strtotime( date( 'G:i:s', strtotime( get_post_meta( $activity_id, '_badgeos_time_restriction_hour_end', true ) ) ) );

			// if we're not on one of the allowed days
			if ( is_array( $allowed_days ) && ! in_array( $today, $allowed_days ) )
				return true;

			// or we're before the start hour
			if ( $current_time < $start_time )
				return true;

			// or we're after the end hour
			if ( $current_time > $end_time )
				return true;

		// If our restrictions are based on a specific date range...
		} else if ( 'dates' == $time_restriction ) {

			// If this is an event, and we aren't limiting check-ins to their date range, the rest is irrelevant
			$limit_dates = get_post_meta( $activity_id, '_badgeos_time_restriction_limit_checkin1', true );
			if ( 'dma-event' == get_post_type( $activity_id ) && empty( $limit_dates ) )
				return false;

			$todays_date = strtotime( date( 'Y-m-d', $offset ) );

			if ( $beginning_date = get_post_meta( $activity_id, '_badgeos_time_restriction_date_begin', true ) ) {
				if ( $todays_date < $beginning_date )
					return true;
			}
			if ( $end_date = get_post_meta( $activity_id, '_badgeos_time_restriction_date_end', true ) ) {
				if ( $todays_date > $end_date )
					return true;
			}
		}
	}

	// If we made it this far, we're clear to use the activity
	return false;
}

/**
 * Update a user's active achievement data with new check-ins
 *
 * @since 2.0.0
 * @param integer $user_id        User ID
 * @param integer $achievement_id Achievement post ID
 * @param array   $new_checkins   Check-ins to add to active achievement data
 */
function dma_user_update_active_achievement_checkins( $user_id = 0, $achievement_id = 0, $new_checkins = array() ) {

	// Create a new active achievement if one does not already exist
	if ( ! $achievement = badgeos_user_get_active_achievement( $user_id, $achievement_id ) )
		$achievement = badgeos_build_achievement_object( $achievement_id, 'started' );

	// Update the achievement's used checkins (merging if any already exist)
	if ( isset( $achievement->used_checkins ) )
		$achievement->used_checkins = array_merge( $achievement->used_checkins, $new_checkins );
	else
		$achievement->used_checkins = $new_checkins;

	// Return the updated achievement object
	return badgeos_user_update_active_achievement( $user_id, $achievement_id, $achievement );

}
