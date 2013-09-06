<?php

/**
 * Remove default rules engine hooks run by BadgeOS core
 *
 * @since  2.0.0
 */
function dma_rules_engine_remove_defaults() {
	remove_filter( 'user_deserves_achievement', 'badgeos_user_deserves_step' );
}
add_action( 'init', 'dma_rules_engine_remove_defaults' );

/**
 * Checks if a user is allowed to work on a given step
 *
 * @since  1.0.0
 * @param  bool    $return   The default return value
 * @param  integer $user_id  The given user's ID
 * @param  integer $step_id The given badge's post ID
 * @return bool              True if user has access to badge, false otherwise
 */
function dma_user_has_access_to_step( $return = true, $user_id = 0 , $step_id = 0 ) {

	// If we're not working with a setp, bail here
	if ( 'step' !== get_post_type( $step_id ) )
		return $return;

	// Get the parent achievement
	$parent_achievement = badgeos_get_parent_of_achievement( $step_id );

	// If the badge has a minimum time-between-steps requirement,
	if ( $return && $min_time_between_steps = get_post_meta( $parent_achievement->ID, '_badgeos_time_between_steps_min', true ) ) {

		// Loop through each sibling
		if ( $siblings = badgeos_get_required_achievements_for_achievement( $parent_achievement->ID ) ) {
			foreach ( $siblings as $sibling ) {

				// If we're looking at the current step, move along
				if ( $sibling->ID == $step_id )
					continue;

				// If any sibling has been earned within the minimum
				// time threshold, we have no access to the current step
				if ( badgeos_get_user_achievements(
						array(
							'user_id'        => $user_id,
							'achievement_id' => $sibling->ID,
							'since'          => ( time() - ( $min_time_between_steps * MINUTE_IN_SECONDS ) )
						)
					)
				) {
					$return = false;
					break;
				}
			}
		}
	}

	// Send back our eligibility
	return $return;
}
add_filter( 'user_has_access_to_achievement', 'dma_user_has_access_to_step', 11, 3 );

/**
 * Validate whether or not a user has completed all requirements for a step.
 *
 * @since  1.0.0
 * @param  bool $return      True if user deserves achievement, false otherwise
 * @param  integer $user_id  The given user's ID
 * @param  integer $step_id  The post ID for our step
 * @return bool              True if user deserves step, false otherwise
 */
function dma_user_deserves_step( $return = true, $user_id = 0, $step_id = 0 ) {

	// If we're not working with a setp, bail here
	if ( 'step' !== get_post_type( $step_id ) )
		return $return;

	// Get the parent achievement
	$parent_achievement = badgeos_get_parent_of_achievement( $step_id );

	// Get the required number of checkins for the step.
	$required_number_of_checkins = absint( get_post_meta( $step_id, '_badgeos_count', true ) );

	// Grab the relevent check-ins for this step
	$relevant_checkins = dma_find_user_checkins_for_step( $user_id, $step_id );

	// If we have not met the check-in requirements we do not deserve the step
	if ( count( $relevant_checkins ) < $required_number_of_checkins )
		$return = false;

	// If we deserve the step, log our used check-ins
	if ( true == $return ) {
		$used_checkins = array_slice( $relevant_checkins, 0 , $required_number_of_checkins );
		dma_user_update_active_achievement_checkins( $user_id, $parent_achievement->ID, $used_checkins );
	}

	// Finally, return our status
	return $return;

}
add_filter( 'user_deserves_achievement', 'dma_user_deserves_step', 10, 3 );

/**
 * Checks if a user is allowed to work on a given badge
 *
 * @since  1.0.0
 * @param  bool    $return   The default return value
 * @param  integer $user_id  The given user's ID
 * @param  integer $badge_id The given badge's post ID
 * @return bool              True if user has access to badge, false otherwise
 */
function dma_user_has_access_to_badge( $return = true, $user_id = 0, $badge_id = 0 ) {

	// If we're not dealing with a badge, bail here
	if ( 'badge' !== get_post_type( $badge_id ) )
		return $return;

	// If were outside the date limits, no access
	if ( dma_is_outside_date_restrictions( $badge_id ) )
		$return = false;

	// If we have not earned the prerequisite badges, no access
	if ( ! dma_user_has_prereq_badges( $user_id, $badge_id ) )
		$return = false;

	// If badge is admin-awarded only, no access
	if ( 'admin' == get_post_meta( $badge_id, '_badgeos_earned_by', true ) )
		$return = false;

	return $return;
}
add_filter( 'user_has_access_to_achievement', 'dma_user_has_access_to_badge', 10, 3 );

/**
 * Check if user meets special DMA requirements for a given achievement
 *
 * @since  1.0.0
 * @param  bool    $return         The current status of whether or not the user deserves this achievement
 * @param  integer $user_id        The given user's ID
 * @param  integer $achievement_id The given achievement's post ID
 * @return bool                    Our possibly updated earning status
 */
function dma_user_deserves_badge(  $return = true, $user_id = 0, $badge_id = 0 ) {

	// If we're not working with a badge, bail here
	if ( 'badge' !== get_post_type( $badge_id ) )
		return $return;

	// Get our badge's "earned by" setting and test accordingly
	$earned_by = get_post_meta( $badge_id, '_badgeos_earned_by', true );
	switch ( $earned_by ) {
		case 'registered' :
			// This will always be true, otherwise the user has no account and this whole checkin is a dream.
			$return = true;
			break;
		case 'provided_email' :
			// Setup our user object
			$user = new DMA_User( $user_id );

			$user_email = $user->email;
			$username = $user->user_login;

			// If the user has a dummy email, bail here
			if ( $user_email == $username . '@example.com' )
				$return = false;
			break;
		case 'connected_social' :
		case 'login' :
			// No support for this just yet
			$return = false;
			break;
		case 'allowed_texting' :
			// If the user has not given us permission to text, no dice.
			$sms_optin = get_user_meta( $user_id, 'sms_optin', true );
			if ( empty( $sms_optin ) )
				$return = false;
			break;
		case 'completed_profile' :
			// Setup our user object
			$user = new DMA_User( $user_id );

			// If any relevant profile information is empty, no dice.
			if (
				empty( $user->first_name )
				|| empty( $user->last_name )
				|| empty( $user->email )
				|| empty( $user->phone )
				|| empty( $user->zip )
				|| empty( $user->avatar )
			)
				$return = false;
			break;
		default :
			break;
	}

	// Finally, return our status
	return $return;
}
add_filter( 'user_deserves_achievement', 'dma_user_deserves_badge', 10, 3 );

/**
 * Check if we're currently outside the post's date limitations
 *
 * @since  1.0.0
 * @param  integer $achievement_id The given achievement's post ID
 * @return bool                    True if we're outside the date restrictions, false if we're not
 */
function dma_is_outside_date_restrictions( $achievement_id ) {
	if ( $beginning_date = get_post_meta( $achievement_id, '_badgeos_time_restriction_date_begin', true ) ) {
		if ( strtotime( date( 'Y-m-d' ) ) < $beginning_date )
			return true;
	}
	if ( $end_date = get_post_meta( $achievement_id, '_badgeos_time_restriction_date_end', true ) ) {
		if ( strtotime( date( 'Y-m-d' ) ) > $end_date )
			return true;
	}

	// If we make it here, there are no date restrictions, or we're inside them
	return false;
}

/**
 * Check if user has the prerequisite badges required for a given achievement
 *
 * @since  1.0.0
 * @param  integer $user_id        The given user's ID
 * @param  integer $achievement_id The given achievement's post ID
 * @return bool                    True if we have the prereqs (or there are none), false if we're missing any
 */
function dma_user_has_prereq_badges( $user_id = 0, $achievement_id = 0 ) {

	// Grab our prerequisite badges
	$prereq_badges = get_posts( array(
		'connected_type'      => 'badge-to-badge',
		'connected_direction' => 'to',
		'connected_items'     => $achievement_id,
		'nopaging'            => true,
		'suppress_filters'    => false,
		'post__not_in'        => array( $achievement_id )
	));

	// If we actually have some prereqs...
	if ( ! empty( $prereq_badges ) ) {

		// Loop through each badge, and if they haven't
		// earned one of them, fail here
		foreach( $prereq_badges as $badge ) {
			if ( ! badgeos_get_user_achievements( array( 'user_id' => $user_id, 'achievement_id' => $badge->ID ) ) )
				return false;
		}

	}

	// If we make it here thare are no prereqs,
	// or we have earned them all
	return true;
}

/**
 * Credit a user for mentioning DMA via social media.
 *
 * Note: Support currently only exists for Twitter.
 *
 * @since  1.1.0
 * @param  integer $post_id    The given imported post ID
 * @param  string  $meta_key   The meta key for the postmeta we're viewing
 * @param  mixed   $meta_value The value of the given meta key
 * @return void
 */
function dma_credit_social_comment_post( $post_id = 0, $meta_key = '', $meta_value = '' ) {

	// Make sure we're working with a post from twitter
	if ( 'social_media_provider' == $meta_key && 'twitter' == strtolower( $meta_value ) ) {

		// Grab our user based on the provided twitter username
		$twitter_handle = strtolower( '@' . get_post_meta( $post_id, 'social_author', true ) );
		$user = dma_get_user_by_meta_data( 'twitter', $twitter_handle );

		// If we have a user, create a checkin for each socially-triggered activity
		if ( $user ) {

			// Grab all our social activities
			$social_activities = get_posts( array(
				'post_type'             => 'activity',
				'activity-trigger-type' => 'social-media',
				'posts_per_page'        => -1
			) );

			// Loop through each activity and create a checkin
			foreach ( $social_activities as $activity ) {
				dma_create_checkin( $user->ID, $activity->ID );
			}
		}
	}

}
add_action( 'pmxi_update_post_meta', 'dma_credit_social_comment_post', 10, 3 );

/**
 * Check to see if a user deserves any profile-related badges
 *
 * @since  1.0.0
 * @param  integer $user_id The given user's ID
 */
function dma_maybe_award_custom_badge_triggers( $user_id ) {
	global $wpdb;

	// Pull back all non-step-baded badges
	$badges = $wpdb->get_results(
		"
		SELECT post_id as ID
		FROM   $wpdb->postmeta
		WHERE  meta_key = '_badgeos_earned_by'
		       AND meta_value IN ('registered','provided_email','connected_social','allowed_texting','completed_profile')
		"
	);

	// Loop through each found badge and attemp to award
	if ( is_array( $badges ) && ! empty( $badges) ) {
		foreach ( $badges as $badge ) {
			badgeos_maybe_award_achievement_to_user( $badge->ID, $user_id );
		}
	}
}
add_action( 'personal_options_update', 'dma_maybe_award_custom_badge_triggers' );
add_action( 'edit_user_profile_update', 'dma_maybe_award_custom_badge_triggers' );
add_action( 'dma_user_registration', 'dma_maybe_award_custom_badge_triggers' );
