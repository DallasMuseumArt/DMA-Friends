<?php
/**
 * Filter the user_deserves_achievement check to possibly award steps
 *
 * When checking related achievements, BadgeOS core checks puts related achievements
 * through the grinder. We do step checking on our own via the dma_user_deserves_step
 *
 * @since  1.0
 * @param  integer $earned   The timestamp of the activity
 * @param  integer $user_id  The given user's ID
 * @param  integer $step_id  The post ID for our step
 * @return bool              True if user deserves step, false otherwise
 */
add_filter( 'user_deserves_achievement', 'dma_user_deserves_step_filter_wrapper', 10, 3 );
function dma_user_deserves_step_filter_wrapper( $earned, $user_id, $step_id ) {

	// Grab our Step $post details
	$step = get_post( $step_id );

	// If we're not actually dealing with a step here, bail.
	if ( 'dma-step' != $step->post_type )
		return $earned;

	return dma_user_deserves_step( $user_id, $step_id );
}


/**
 * Validate whether or not a user has completed all requirements for a step.
 *
 * @since  1.0
 * @param  integer $user_id  The given user's ID
 * @param  integer $step_id  The given step's ID
 * @return bool              True if user deserves step, false otherwise
 */
function dma_user_deserves_step( $user_id, $step_id ) {

	global $mvp;

	// Grab our current user if no user ID specified
	$user_id = dma_get_user_id( $user_id);

	// Grab the parent badge for our step
	$parent_badge = badgestack_get_parent_of_achievement( $step_id );

	// sanity check for bad data relations.
	if ( empty( $parent_badge ) )
		return false;

	// Get the required number of checkins for the step.
	$required_number_of_checkins = get_post_meta( $step_id, '_dma_checkin_count', true );

	// Grab the relevent check-ins for this step
	$relevant_checkins = dma_find_relevant_checkins_for_step( $user_id, $step_id );

	// If the user is already working on the step's badge...
	if ( dma_is_user_engaged_in_badge( $user_id, $parent_badge->ID ) ) {

		// Grab the badge's used checkins
		$active_badge = dma_get_users_active_badge_details( $user_id, $parent_badge->ID );

		// If we have any already used checkins, exclude them from our relevant checkin array
		// @TODO: Make sure this part actually works, we'll also need it in DMA_Badge
		if ( isset( $active_badge->used_checkins ) && ! empty( $active_badge->used_checkins ) ) {
			$all_checkins = $relevant_checkins;
			$relevant_checkins = array_udiff( $relevant_checkins, $active_badge->used_checkins, 'dma_compare_checkins' );
			// wp_die( var_dump( $active_badge->used_checkins, $all_checkins, $relevant_checkins ) );
		}
	}

	// If we've met the check-in requirements...
	if ( count( $relevant_checkins ) >= $required_number_of_checkins ) {

		// Log the used cards to our active badge array
		dma_update_badge_used_checkins( $user_id, $parent_badge->ID, $relevant_checkins );

		// Acknowledge that user deserves step
		$return = true;

	// Otherwise, user does NOT deserve step
	} else {
		$return = false;
	}

	// Make sure we're working inside the parent badge's date limits
	if ( dma_is_outside_date_restrictions( $parent_badge->ID ) )
		$return = false;

	// Prevent users from earning more than the maximum allowed times
	if ( dma_user_has_exceeded_max_earnings( $user_id, $parent_badge->ID ) )
		$return = false;

	// Make sure the user has the parent badge's prereqs before granting them any steps
	if ( ! dma_user_has_prereq_badges( $user_id, $parent_badge->ID ) )
		$return = false;

	// Finally, return our status
	return $return;

}

/**
 * Check if a user deserves any badges for unlocking an achievement
 *
 * @since  1.0
 * @param  bool $return      The default return status
 * @param  integer $user_id  The given user's ID
 * @param  integer $badge_id The given achievement's post ID
 * @return bool              True if user desrves badge, false otherwise
 */
add_filter( 'user_deserves_achievement', 'dma_user_deserves_badge', 10, 3 );
function dma_user_deserves_badge( $return, $user_id, $badge_id ) {

	// Grab the post object for our achievement
	$achievement = get_post( $badge_id );

	// If it's not a badge, bail here
	if ( $achievement->post_type != 'badge' )
		return $return;

	// Sanity check: if the badge isn't published it's not earnable
	if ( 'publish' != $achievement->post_status )
		return false;

	// grab the last date this badge was earned (or quit)
	$since = dma_badge_last_user_activity( $badge_id, $user_id );

	// Setup our badge trigger type, defaults to "steps" if nothing set
	$trigger_type = dma_badge_trigger_type( $badge_id );

	// If this badge is triggered by steps...
	if ( 'steps' == $trigger_type ) {

		// get all required steps for this badge
		$required_steps = badgestack_get_required_achievements_for_achievement( $badge_id );

		// If this badge has required steps, loop through each step...
		if ( ! empty( $required_steps ) ) { foreach ( $required_steps as $step ) {

			// If they have NOT already earned the step,
			// AND do NOT now deserve the step...
			if ( ! badgestack_check_if_user_has_achievement( $step->ID, $user_id, $since )
				&& ! badgestack_check_achievement_completion_for_user( $step->ID, $user_id ) )
				// then they cannot deserve the badge
				$return = false;

		} }

	} elseif ( 'login' == $trigger_type ) {
		// No support for this just yet
		$return = false;

	} elseif ( 'registered' == $trigger_type ) {
		// This will always be true, otherwise the user has no account and this whole checkin is a dream.
		$return = true;

	} elseif ( 'provided_email' == $trigger_type ) {

		// Setup our user object
		$user = new DMA_User( $user_id );

		$user_email = $user->email;
		$username = $user->user_login;

		// If the user has a dummy email, bail here
		if ( $user_email == $username . '@example.com' )
			$return = false;

	} elseif ( 'connected_social' == $trigger_type ) {
		// No support for this just yet
		$return = false;

	} elseif ( 'allowed_texting' == $trigger_type ) {
		// If the user has not given us permission to text, no dice.
		$sms_optin = get_user_meta( $user_id, 'sms_optin', true );
		if ( empty( $sms_optin ) )
			$return = false;

	} elseif ( 'completed_profile' == $trigger_type ) {

		// Setup our user object
		$user = new DMA_User( $user_id );

		// If any relevant profile information is empty, no dice.
		if (
			empty( $user->first_name ) ||
			empty( $user->last_name ) ||
			empty( $user->email ) ||
			empty( $user->phone ) ||
			empty( $user->zip ) ||
			empty( $user->avatar )
		)
			$return = false;

	}

	// Make sure we're working inside the badge date limits
	if ( dma_is_outside_date_restrictions( $badge_id ) )
		$return = false;

	// Prevent users from earning more than the maximum allowed times
	if ( dma_user_has_exceeded_max_earnings( $user_id, $badge_id ) )
		$return = false;

	if ( ! dma_user_has_prereq_badges( $user_id, $badge_id ) )
		$return = false;

	// If user deserves badge, lets grab the points for the new badge
	if ( true == $return )
		do_action( 'dma_award_user_points', $user_id, $badge_id );

	// Finally, return our status
	return $return;
}


/**
 * Finds relevant steps for a given activity ID, or related terms, and awards the steps if the user is eligble.
 *
 * @since  1.0
 * @param  integer $user_id                 The given user's ID
 * @param  integer $activity_id             The given activity's ID
 * @param  array   $activity_type_terms     The given terms for the activity
 * @param  array   $activity_category_terms The given category terms for the activity
 * @return void
 */
function find_relevant_steps_and_maybe_award( $user_id, $activity_id = 0, $activity_type_terms = array(), $activity_category_terms = array() ) {

	// Grab our user ID if not provided
	if ( ! $user_id )
		$user_id = wp_get_current_user()->ID;

	// Get the steps that are relevant to the recorded activity
	$steps = dma_get_steps( $activity_id, $activity_type_terms, $activity_category_terms );

	// If we have any steps...
	if ( ! empty( $steps ) ) {

		// Loop through each step
		foreach ( $steps as $step ) {

			// If user can work on the step...
			if ( badgestack_user_has_access_to_achievement( $user_id, $step->ID ) ) {

				// If we're working with a repeater step...
				if ( dma_is_a_repeater_step( $step->ID ) ) {

					// Grab the closest previous step that isn't a repeater
					$prior_achievement = dma_get_prior_achievement_not_repeater( $step->ID );

					// If we did, in fact, complete a previous step...
					if ( ! empty( $prior_achievement) && is_object( $prior_achievement) ) {

						// Grab the checkin that triggered that previous step completion
						$prior_checkin_meta = dma_activity_meta_that_completed_achievement( $user_id, $prior_achievement->ID, dma_get_date_user_began_challenge() );
						$current_checkin_meta = dma_newest_recorded_activity_meta( $user_id );

						// If the MVP Cards match, and the user deserves the step...
						if ( $prior_checkin_meta == $current_checkin_meta
							 && dma_user_deserves_step( $user_id, $prior_achievement->ID ) ) {
								// Award the step.
								badgestack_award_achievement_to_user( $step->ID, $user_id );
						}
					}

					// Otherwise, contine through the loop...
					continue;
				}

				// For all other steps, simply check to see if user deserves the step and award it if so
				if ( dma_user_deserves_step( $user_id, $step->ID ) ) {
					badgestack_award_achievement_to_user( $step->ID, $user_id );
				}
			}
		}
	}
}

/**
 * Get the meta for the newest recorded activity
 *
 * @since  1.0
 * @param  integer $user_id The given user's ID
 * @return array            The connected terms and $post details for the newest recorded activity
 */
function dma_newest_recorded_activity_meta( $user_id ) {

	// Don't forget the $wpdb global, otherwise the sky will fall.
	global $wpdb;

	// Grab our newest checkin ID
	$checkin_id = $wpdb->get_var(
		$wpdb->prepare(
			"
			SELECT ID
			FROM   $wpdb->posts
			WHERE  post_type = %s
			       AND post_author = %d
			ORDER BY post_date DESC
			",
			'checkin',
			$user_id
		)
	);

	// Grab our post details for the related activity
	$connected_activity = $wpdb->get_var(
		$wpdb->prepare(
			"
			SELECT p2p_from
			FROM   $wpdb->p2p
			WHERE  p2p_to = %d
			",
			$checkin_id
		)
	);

	// Grab any connected terms
	$terms = wp_get_object_terms( $checkin_id, array( 'activity-type', 'activity-category' ) );

	// Finally, return our connected items
	return array( 'terms' => $terms, 'connected_activity' => $connected_activity );
}

/**
 * Get the meta for the recorded activity that satisfied an achievement
 *
 * @since  1.0
 * @param  integer $user_id        The given user's ID
 * @param  integer $achievement_id The given achievement's ID
 * @param  integer $since          An lower-limit date for our results
 * @return bool|array              False if no completed achievement, or an array of the connected terms and $post details for the check-in that completed the achiveement
 */
function dma_activity_meta_that_completed_achievement( $user_id, $achievement_id, $since = 0 ) {

	// Grab our earned achievements
	$earned_achievements = badgestack_get_achievements_for_user( $user_id );

	// Loop through each achievement
	foreach ( $earned_achievements as $earned_achievement ) {
		// If it was earned before our $since date, skip it
		if ( $earned_achievement->date_earned < $since )
			continue;
		// If the achievement matches our provided ID, grab the date earned
		if ( $earned_achievement->ID == $achievement_id ) {
			$date_earned = $earned_achievement->date_earned;
		}
	}

	// If we don't have a date earned, bail here
	if ( ! isset( $date_earned ) )
		return false;

	// Don't forget the $wpdb global, otherwise the sky will fall.
	global $wpdb;

	// Get the check-in recorded before or on our $date_earned date
	$checkin_id = $wpdb->get_var(
		$wpdb->prepare(
			"
			SELECT   ID
			FROM     $wpdb->posts
			WHERE    post_type = %s
			         AND post_date <= %s
			         AND post_author = %d
			ORDER BY post_date DESC
			",
			'checkin',
			date( 'Y-m-d H:i:s', $date_earned),
			$user_id
		)
	);

	// Grab our post details for the related activity
	$connected_activity = $wpdb->get_var(
		$wpdb->prepare(
			"
			SELECT p2p_from
			FROM   $wpdb->p2p
			WHERE  p2p_to = %d
			",
			$checkin_id
		)
	);

	// Grab any connected terms
	$terms = wp_get_object_terms( $checkin_id, array( 'activity-type', 'activity-category' ) );

	// Finally, return our connected items
	return array( 'terms' => $terms, 'connected_activity' => $connected_activity );
}

/**
 * Get our previous non-repeating achievement
 *
 * @since  1.0
 * @param  integer $step_id  The given step's ID
 * @return bool|object       False if no achievement exists, otherwise the achievement's $post object
 */
function dma_get_prior_achievement_not_repeater( $step_id ) {

	// Grab the badge, all its steps, and the previous step sort order
	$badge         = badgestack_get_parent_of_achievement( $step_id );
	$badge_steps   = badgestack_get_all_children_of_achievement( $badge->ID );
	$previous_step = dma_get_p2p_menu_order_of_step( $step_id ) - 1;

	// Loop through each of our steps
	foreach ( $badge_steps as $step ) {

		// Get that step's sort order
		$step_menu_order = dma_get_p2p_menu_order_of_step( $step->ID );

		// If the sort order matches our previous step
		if ( $previous_step == $step_menu_order ) {
			// And it's not a repeater... we can return it
			if ( ! dma_is_a_repeater_step( $step->ID ) )
				return $step;
			// Otherwise, it IS a repeater step and we need to jump back through the function again...
			else
				return dma_get_prior_achievement_not_repeater( $step->ID );
		}
	}

	// If we make it this far, there are no previous non-repeating steps
	return false;
}

/**
 * Conditional to determine if a given step is a repeater step or not
 *
 * @since  1.0
 * @param  integer $step_id The given step's ID
 * @return bool             True if it's a repeater step, false otherwise
 */
function dma_is_a_repeater_step( $step_id ) {

	// Grab our post details for the step
	$step = get_post( $step_id );

	// If it has the term 'repeat' it's a repeater
	if ( has_term( 'repeat', 'special-step-earning-option', $step_id ) )
		return true;
	else
		return false;
}

/**
 * Get the sort order for a given step
 *
 * @since  1.0
 * @param  integer $step_id The given step's ID
 * @return integer          The menu order for the given step
 */
function dma_get_p2p_menu_order_of_step( $step_id ) {
	global $wpdb;
	$menu_order = $wpdb->get_var(
		$wpdb->prepare(
			"
			SELECT    p2pmeta.meta_value
			FROM      $wpdb->p2pmeta AS p2pmeta
			LEFT JOIN $wpdb->p2p AS p2p
			          ON p2pmeta.p2p_id = p2p.p2p_id
			WHERE     p2p.p2p_from = %d
			",
			$step_id
		)
	);
	return $menu_order;
}


/**
 * Checks if a user is allowed to work on a given step
 *
 * @since  1.0
 * @param  bool    $return   The default return value
 * @param  integer $user_id  The given user's ID
 * @param  integer $step_id  The given step's post ID
 * @return bool              True if user has access to step, false otherwise
 */
add_filter( 'user_has_access_to_achievement', 'dma_user_can_work_on_step', 10, 3 );
function dma_user_can_work_on_step( $return, $user_id, $step_id ) {

	// Grab our current user if no user ID specified
	$user_id = dma_get_user_id( $user_id);

	// Grab the parent badge of the step
	$parent_badge = badgestack_get_parent_of_achievement( $step_id );

	// If step doesn't have a parent, bail
	if ( empty( $parent_badge ) )
		return false;

	// If the badge can only be earned X times, make sure they haven't earned it more than X times
	if ( $max_times_allowed_to_earn = get_post_meta( $parent_badge->ID, '_dma_fun_badge_repeat_earning', true ) ) {
		$user_has_badge = dma_check_if_user_has_achievement( $user_id, $parent_badge->ID );
		if ( ! empty( $user_has_badge ) ) {
			$times_user_earned_badge = count( $user_has_badge );
			if ( $times_user_earned_badge >= $max_times_allowed_to_earn ) {
				return false;
			}
		}
	}

	// Min Time between Steps
	// If the parent badge has a minimum time between steps, find the latest step completed
	// under the parent badge, and check if the minimum time has elapsed since earning it
	if ( $min_time_between_steps = get_post_meta( $parent_badge->ID, '_dma_badge_minimum_time', true ) ) {
		$siblings = badgestack_get_required_children_of_achievement( $parent_badge->ID );
		foreach ( $siblings as $sibling ) {
			if ( $sibling->ID == $step_id )
				continue;
			if ( dma_check_if_user_has_achievement( $user_id, $sibling->ID, false, false, ( time() - ( $min_time_between_steps * 60 ) ) ) )
				return false;
		}
	}

	// Check if user has already earned step while working on badge.
	if ( dma_check_if_user_has_achievement( $user_id, $step_id, false, false, dma_badge_last_user_activity( $parent_badge->ID, $user_id ) ) ) {
		return false;
	}

	// If we passed everything else, the user has access to this step
	return true;
}

/**
 * Given a step, find the relevant check-ins that count towards earning the step.
 */
function dma_find_relevant_checkins_for_step( $user_id, $step_id ) {
	global $mvp;

	// If we don't have a user ID, use the current user's ID
	if ( ! $user_id )
		$user_id = wp_get_current_user()->ID;

	// Grab the badge that owns this step
	$parent_badge = badgestack_get_parent_of_achievement( $step_id );

	// If this step doesn't have a parent badge, fail here
	if ( empty( $parent_badge ) )
		return false;

	// Assume we're using all MVP cards ever logged
	$since = 0;

	// If we have activity for this badge, only use check-ins logged since the most recent activity date
	if ( $date = dma_badge_last_user_activity( $parent_badge->ID, $user_id ) )
		$since = gmdate( 'Y-m-d H:i:s', ( $date + ( get_option( 'gmt_offset' ) * 3600 ) ) );

	// Setup our $post object for the step
	$step = get_post( $step_id );

	// Initialize tax_query
	$tax_query = array();

	// Add any required activity type terms to the tax query
	$activity_type_terms = wp_get_object_terms( $step_id, 'activity-type' );
	if ( ! empty( $activity_type_terms ) ) {
		foreach ( $activity_type_terms as $term ) {
			$tax_query[] = array(
				'taxonomy' => 'activity-type',
				'field'    => 'id',
				'terms'    => $term->term_id
			);
		}
	}

	// Add any required activity category terms to the tax query
	$activity_category_terms = wp_get_object_terms( $step_id, 'activity-category' );
	if ( ! empty( $activity_category_terms ) ) {
		foreach ( $activity_category_terms as $term ) {
			$tax_query[] = array(
				'taxonomy' => 'activity-category',
				'field'    => 'id',
				'terms'    => $term->term_id
			);
		}
	}

	// Find any required activities for the step
	$connected_activities = get_posts(
		array(
			'post_type'       => 'activity',
			'connected_type'  => 'activity-to-step',
			'connected_items' => $step_id,
			'nopaging'        => true,
			'supress_filters' => false
		)
	);

	// Build the WP Query argument
	$checkins_args = array(
		'post_type'      => 'checkin',
		'author'         => $user_id,
		'tax_query'      => $tax_query,
		'posts_per_page' => - 1
	);

	// Add extra arguments if a required activity was found
	if ( ! empty( $connected_activities ) ) {
		$checkins_args['connected_type']      = 'activity-to-checkin';
		$checkins_args['connected_items']     = $connected_activities[0]->ID;
		$checkins_args['connected_direction'] = 'from';
		$checkins_args['nopaging']            = true;
		$checkins_args['suppress_filters']    = false;
	}

	// If we're querying for posts within a date range, add a date range filter here.
	if ( $since ) {
		$checkins_args['_start_date'] = $since;
		$mvp->since = $since;
		add_filter( 'posts_where', 'dma_step_filter_where_for_date', 10, 2 );
	}


	// Run our cards query
	// Note: get_posts() doesn't respect our date range filter, so we're firing up a full WP_Query instead
	$checkins_query = new WP_Query( $checkins_args );

	// Remove the date range filter
	if ( $since )
		remove_filter( 'posts_where', 'dma_step_filter_where_for_date' );

	// Loop through our $checkins_query array and add the resulting posts to an $checkins array
	// Note: $mwp_cards_query->get_posts() geves the same issue as just get_posts()
	$checkins = array();
	if ( $checkins_query->have_posts() ) : while ( $checkins_query->have_posts() ) : $checkins_query->the_post();
		global $post;
		$checkins[] = $post;
	endwhile; endif;
	wp_reset_postdata();

	// Finally, and do I mean FINALLY, return our found cards
	return $checkins;
}

/**
 * Helper function for limiting our query to a specific date
 */
function dma_step_filter_where_for_date( $where, $wp_query ) {
	global $wpdb;

	if ( $wp_query->get( '_start_date' ) )
		$where .= $wpdb->prepare( ' AND post_date >= %s', $wp_query->get( '_start_date' ) );

	return $where;
}

/**
 * Helper function for comparing two MVP cards based on their ID. Used in array_udiff
 *
 * @param  object $checkin_a An MVP Card post object
 * @param  object $checkin_b Another MVP Card post object to compare
 * @return integer            Returns 0 if the ID's are the same, or another integer if they're not
 */
function dma_compare_checkins( $checkin_a, $checkin_b ) {
	return absint( $checkin_a->ID ) - absint( $checkin_b->ID );
}

/**
 * Check to see if a user deserves any profile-related badges
 *
 * @since  1.0
 * @param  integer $user_id The given user's ID
 */
function dma_maybe_award_custom_badge_triggers( $user_id ) {

	global $wpdb;

	// Loop through all non-step-based badges and see if the user deserves to earn any
	$badges = $wpdb->get_results( "SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = '_dma_badge_trigger_type' AND meta_value != 'steps'" );
	foreach ( $badges as $badge ) {
		badgestack_check_and_maybe_award_achievement_to_user( $badge->post_id, $user_id );
	}
}
add_action( 'personal_options_update', 'dma_maybe_award_custom_badge_triggers' );
add_action( 'edit_user_profile_update', 'dma_maybe_award_custom_badge_triggers' );
add_action( 'dma_user_registration', 'dma_maybe_award_custom_badge_triggers' );


/**
 * Check if we're currently outside the post's date limitations
 *
 * @since  1.0
 * @param  integer $achievement_id The given achievement's post ID
 * @return bool                    True if we're outside the date restrictions, false if we're not
 */
function dma_is_outside_date_restrictions( $achievement_id ) {
	if ( $beginning_date = get_post_meta( $achievement_id, '_dma_time_restriction_date_begin', true ) ) {
		if ( strtotime( date( 'Y-m-d' ) ) < $beginning_date )
			return true;
	}
	if ( $end_date = get_post_meta( $achievement_id, '_dma_time_restriction_date_end', true ) ) {
		if ( strtotime( date( 'Y-m-d' ) ) > $end_date )
			return true;
	}

	// If we make it here, there are no date restrictions or we're inside them
	return false;
}

/**
 * Check if user has already earned an achievement the maximum number of times
 *
 * @since  1.0
 * @param  integer $user_id        The given user's ID
 * @param  integer $achievement_id The given achievement's post ID
 * @return bool                    True if we've exceed the max possible earnings, false if we're still eligable
 */
function dma_user_has_exceeded_max_earnings( $user_id = 0, $achievement_id = 0 ) {

	// Grab our max allowed times to earn, and if set, see how many times we've earned the badge
	if ( $max_times_allowed_to_earn = get_post_meta( $achievement_id, '_dma_fun_badge_repeat_earning', true ) ) {
		$user_has_badge = dma_check_if_user_has_achievement( $user_id, $achievement_id );
		if ( ! empty( $user_has_badge ) ) {
			$times_user_earned_badge = count( $user_has_badge );
			if ( $times_user_earned_badge >= $max_times_allowed_to_earn ) {
				return true;
			}
		}
	}

	// If we make it here, the post has no max earning limit, or we're under it
	return false;
}

/**
 * Check if user has the prerequisite badges required for a given achievement
 *
 * @since  1.0
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

		// Loop through each badge, and if they haven't earned one of them, fail here
		foreach( $prereq_badges as $badge ) {
			if ( ! dma_check_if_user_has_achievement( $user_id, $badge->ID ) )
				return false;
		}

	}

	// If we make it here thare are no prereqs, or we have them all
	return true;
}

/**
 * Return the trigger type for a given badge
 *
 * @param  integer $badge_id The given badge's post ID
 * @return string            The type of trigger used for this badge
 */
function dma_badge_trigger_type( $badge_id = 0 ) {
	return ( $type = get_post_meta( $badge_id, '_dma_badge_trigger_type', true ) ) ? $type : 'steps';
}

/**
 * Credit a user for mentioning DMA via social media.
 *
 * Note: Support currently only exists for Twitter.
 *
 * @since  1.1
 * @param  integer $post_id    The given imported post ID
 * @param  string  $meta_key   The meta key for the postmeta we're viewing
 * @param  mixed   $meta_value The value of the given meta key
 * @return void
 */
add_action( 'pmxi_update_post_meta', 'dma_credit_social_comment_post', 10, 3 );
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
