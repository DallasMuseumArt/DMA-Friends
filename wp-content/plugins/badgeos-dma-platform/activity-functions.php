<?php

/**
 * Retrieve steps (optionally only steps that have the specified related activity).
 *
 * @since  1.0
 * @param  int     $activity_id         [description]
 * @param  array   $fitness_type_terms  [description]
 * @param  array   $activity_type_terms [description]
 * @return [type]                       [description]
 */
function dma_get_steps( $activity_id = 0, $fitness_type_terms = array(), $activity_type_terms = array() ) {
	global $wpdb;

	// Create an empty array we will push posts onto in
	// the following three separate get_post calls
	$steps = array();

	// set up the basic query arguments for the get steps call
	$args = array(
		'post_type'				=> 'dma-step',
		'posts_per_page'		=> - 1,
		'suppress_filters'		=> false,
		'connected_direction'	=> 'from',
		'connected_type'		=> 'activity-to-step',
	);

	// If we're looking for a step that requires a specific activity
	// to be performed
	if ( $activity_id ) {
		// set up the flag for the P2P relationship
		$args['connected_items'] = $activity_id;
		// push any found onto the $steps array
		foreach ( get_posts( $args ) as $post )
			array_push( $steps, $post );
	}

	// Reset flag for the rest of the queries below, as they will
	// require an 'any' relation as far as P2P relationships go.
	unset( $args['connected_direction'] );
	unset( $args['connected_type'] );
	unset( $args['connected_items'] );

	// If we're looking for steps that require a specific fitness type term
	if ( ! empty( $fitness_type_terms ) ) {
		// For each of the fitness type terms required
		foreach ( $fitness_type_terms as $term ) {
			// There should be only one fitness type term required for any
			// step, so we'll set the tax query in the get_posts argument
			// in the 0 element
			$args['tax_query'][0] = array(
				'taxonomy'	=> 'fitness-type',
				'field'		=> 'slug',
				'terms'		=> $term->slug,
			);
			// Push any found posts onto the $steps array
			foreach ( get_posts( $args ) as $post )
				array_push( $steps, $post );
		}
	}

	// If we're looking for steps that require a specific activity type term
	if ( ! empty( $activity_type_terms ) ) {
		foreach ( $activity_type_terms as $term ) {
			// There should be only one activity type term required for any
			// step, so we'll set the tax query in the get_posts argument
			// in the 0 element
			$args['tax_query'][0] = array(
				'taxonomy'	=> 'activity-type',
				'field'		=> 'slug',
				'terms'		=> $term->slug,
			);
			// Push any found posts onto the $steps array
			foreach ( get_posts( $args ) as $post )
				array_push( $steps, $post );
		}
	}

	// Get steps that require any activity
	$args['tax_query'][0] = array(
		'taxonomy' => 'special-step-earning-option',
		'field' => 'slug',
		'terms' => 'any-activity',
	);
	foreach ( get_posts( $args ) as $post )
		array_push( $steps, $post );

	// Get steps that require repeat activity
	$args['tax_query'][0] = array(
		'taxonomy' => 'special-step-earning-option',
		'field' => 'slug',
		'terms' => 'repeat',
	);

	foreach ( get_posts( $args ) as $post )
		array_push( $steps, $post );

	// @TODO maybe move this into the badgestack_get_required_children_of_achievement. would require adding filters there
	return $steps;
}

/**
 * Create a new DMA checkin.
 *
 * Attaches taxonomy data associated with the related activity to the DMA card.
 * Relates the DMA card to the location the member is reporting for.
 * Relates the activity to the DMA Card via P2P.
 *
 * @since  1.0
 * @param  int      $user_id      The given user's ID
 * @param  int      $activity_id  The activity's post ID
 * @param  int      $date         The date to use for logging the activity
 * @param  int      $location_id  The location where this activity was logged
 * @param  int      $accession_id The accession ID provided by the user during checkin
 * @param  bool     $is_admin     True if we're submitting from an admin form, false otherwise
 * @return int|bool               The successfully created checkin's post ID, or false on failure
 */
function dma_create_checkin( $user_id = 0, $activity_id = 0, $date = NULL, $location_id = 0, $accession_id = 0, $is_admin = false ) {
	global $wpdb;

	// If no user ID, get our current user
	if ( ! $user_id )
		$user_id = wp_get_current_user()->ID;

	// Grab our user data
	$user_data = get_userdata( $user_id );

	// Grab the current date if no date specified
	if ( ! $date )
		$date = current_time( 'mysql' );

	// Grab our current location ID if none specified
	if ( ! $location_id )
		$location_id = dma_get_current_location_id();

	// Assume no checkin gets created
	$checkin_id = false;

	// Nicely formatted date for post title
	$str_time = strtotime( $date );
	$nice_date = ' at ' . mysql2date( 'g:ia', $str_time );
	$nice_date .= ' on ' . mysql2date( 'n/j/Y', $str_time );

	// Grab the activity post
	$activity = get_post( $activity_id );

	// If the activity has a time lockout, and the user is locked out, prevent checkin
	if ( ! $is_admin && dma_is_activity_locked_for_user( $user_id, $activity_id ) )
		return false;

	// If the activity has any time-based restrictions, make sure we're not outside of those
	if ( ! $is_admin && dma_is_checkin_outside_time_restrictions( $activity_id ) )
		return false;

	// Setup our post data to create the new DMA card
	$new_post = array(
		'post_type'		=> 'checkin',
		'post_status'	=> 'publish',
		'post_author'	=> $user_id,
		'post_title'	=> "{$user_data->user_nicename} just completed 1 check-in of {$activity->post_title} {$nice_date}.",
		'post_date'		=> $date,
	);

	// Create a new post for our check-in
	$checkin_id = wp_insert_post( $new_post );

	// If we're logging an activity...
	// NOTE: We're using $wpdb->insert() because p2p_type() isn't available
	// to us during user authentication (e.g. for dma_award_activities_on_login())
	if ( 'activity' == get_post_type( $activity_id ) ) {

		// Create P2P connection from the activity to the checkin
		// p2p_type( 'activity-to-checkin' )->connect( $activity_id, $checkin_id );
		$wpdb->insert( $wpdb->p2p, array( 'p2p_type' => 'activity-to-checkin', 'p2p_from' => $activity_id, 'p2p_to' => $checkin_id ), array( '%s', '%d', '%d' ) );

	// Or, if we're logging an event...
	} elseif ( 'dma-event' == get_post_type( $activity_id ) ) {

		// Create P2P connection from the activity to the checkin
		// p2p_type( 'dma-event-to-checkin' )->connect( $activity_id, $checkin_id );
		$wpdb->insert( $wpdb->p2p, array( 'p2p_type' => 'dma-event-to-checkin', 'p2p_from' => $activity_id, 'p2p_to' => $checkin_id ), array( '%s', '%d', '%d' ) );
	}

	// Set the current location term for the checkin
	wp_set_object_terms( $checkin_id, $location_id, 'location', true );

	// Check if user deserves any steps based on this checkin
	find_relevant_steps_and_maybe_award( $user_id, $activity_id, $activity_type_terms, $activity_category_terms );

	// Add a hook so we can do other things too
	do_action( 'dma_create_checkin', $checkin_id, $user_id, $activity_id, $date, $location_id, $accession_id, $is_admin );
	do_action( 'dma_award_user_points', $user_id, $activity_id );

	// Return our final DMA card ID
	return $checkin_id;
}

/**
 * Get an activity's post ID from a given Accession ID
 *
 * @param  integer $accession_id The provided accession ID
 * @return int|bool              The matching post ID if found, false otherwise
 */
function dma_get_activity_id_from_accession_id( $accession_id = 0 ) {


	// If we're liking a piece of art (http://regexr.com?33f9o)
	if ( preg_match( '/^[a-zA-Z0-9]{2}[a-zA-Z0-9]?[a-zA-Z0-9]?\..+$/', $accession_id ) )
		return 2344; // The post ID for "Liked a work of art"

	// Otherwise, get our activity_id from our provided accession ID (meta_value)
	$posts = get_posts( array( 'post_type' => array( 'dma-event', 'activity' ), 'meta_key' => '_dma_accession_id', 'meta_value' => $accession_id ) );

	// If we don't have exactly 1 matching post, bail here
	if ( 1 != count( $posts ) )
		return 0;

	// Otherwise, our single matching post is the activity we're logging
	$activity_id = $posts[0]->ID;

	// Finally, return our activity ID
	return $activity_id;

}

/**
 * Log an activity
 *
 * @since  1.0
 * @param  int      $user_id      A given user's ID
 * @param  int      $activity_id  A given activity's ID
 * @param  int      $accession_id The accession ID provided by the user during checkin
 * @param  bool     $is_admin     True if we're submitting from an admin form, false otherwise
 * @return int|bool               ID of newly created Checkin post if successful, false if not
 */
function dma_activity_submit( $user_id = 0, $activity_id = 0, $accession_id = 0, $is_admin = false ) {

	// Assume we can't log the activity for some reason
	$completed_checkin_id = false;

	// Setup our variables
	if ( ! $user_id )
		$user_id = $_POST['user_id'];

	if ( ! $activity_id )
		$activity_id = $_POST['activity_id'];

	// Log our activity
	$completed_checkin_id = dma_create_checkin( $user_id, $activity_id, NULL, false, $accession_id, $is_admin );

	// Return our successfully logged activity, or false if failed
	return $completed_checkin_id;
}

/**
 * Conditional to check if a user is locked out of an activity based on its lockout limit meta
 *
 * @since  1.0
 * @param  integer $user_id     The given user's ID
 * @param  integer $activity_id The given activity's post ID
 * @return boolean              True if the user is locked out, false otherwise
 */
function dma_is_activity_locked_for_user( $user_id, $activity_id = 0 ) {

	// If the activity has a lockout, see if a user should be prevented from checkin
	if ( $lockout_limit = get_post_meta( $activity_id, '_dma_activity_lockout', true ) ) {

		// Set a timestamp for the current time minus lockout (in minutes)
		$since = date( 'Y-m-d H:i:s', time() - ( absint( $lockout_limit ) * 60 ) );

		global $wpdb;

		// Get posts connected to this activity id, created by this user, since our lockout limit
		$activities_logged = $wpdb->get_results( $wpdb->prepare(
			"
			SELECT    *
			FROM      $wpdb->posts as posts,
			          $wpdb->p2p as p2p
			WHERE     posts.post_author    = %d
			          AND posts.post_type  = 'checkin'
			          AND p2p.p2p_type     = 'activity-to-checkin'
			          AND p2p.p2p_from     = %d
			          AND p2p.p2p_to       = posts.ID
			          AND posts.post_date  > %s
			",
			$user_id,
			$activity_id,
			$since
		));

		// If we have any posts, the user is locked out
		if ( $activities_logged )
			return true;

	}

	// Otherwise, the user is NOT locked out
	return false;

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
	if ( $time_restriction = get_post_meta( $activity_id, '_dma_time_restriction', true ) ) {

		// Don't forget about our time offset...
		$offset = time() + ( get_option('gmt_offset') * 3600 );

		// If the restrictions are day/time based...
		if ( 'hours' == $time_restriction ) {

			// Setup our time vars
			$today        = date('l', $offset );
			$current_time = strtotime( date('G:i:s', $offset ) );
			$allowed_days = get_post_meta( $activity_id, '_dma_time_restriction_days', false );
			$start_time   = strtotime( date( 'G:i:s', strtotime( get_post_meta( $activity_id, '_dma_time_restriction_hour_begin', true ) ) ) );
			$end_time     = strtotime( date( 'G:i:s', strtotime( get_post_meta( $activity_id, '_dma_time_restriction_hour_end', true ) ) ) );

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
			$limit_dates = get_post_meta( $activity_id, '_dma_time_restriction_limit_checkin1', true );
			if ( 'dma-event' == get_post_type( $activity_id ) && empty( $limit_dates ) )
				return false;

			$todays_date = strtotime( date( 'Y-m-d', $offset ) );

			if ( $beginning_date = get_post_meta( $activity_id, '_dma_time_restriction_date_begin', true ) ) {
				if ( $todays_date < $beginning_date )
					return true;
			}
			if ( $end_date = get_post_meta( $activity_id, '_dma_time_restriction_date_end', true ) ) {
				if ( $todays_date > $end_date )
					return true;
			}
		}
	}

	// If we made it this far, we're clear to use the activity
	return false;
}

/**
 * Generate our DMA event/activity code input form
 */
function dma_code_input_form() {

	// Concatenate our output
	$output = '
	<div class="dma-code-notices"></div>
	<div class="dma-code-input">
		<form class="activity-submit" method="" action="">
			<input id="activity-codes" type="text" name="accession_id" placeholder="'. __( 'Enter Activity Code', 'dma' ) .'" />
			<input type="hidden" name="activity_id" value="1" />
			<input type="hidden" name="user_id" value="' . dma_get_user_id() . '" />
			<button class="secondary" type="submit">'. __( 'Go', 'dma' ) .'</button>
		</form>
		<a class="help small pop" href="#what-codes" data-popheight="auto"><div class="q icon-help-circled"></div><span>What kinds of codes can I enter?</span></a>
	</div>
	<div id="what-codes" class="popup close" data-popheight="auto">';
		$output .= dashboard_popup_content( 'What kinds of codes can I enter?' );
		$output .= '<a class="button secondary close-popup" href="#">Close</a>
	</div>
	';

	// Return our output
	return $output;
}

/**
 * AJAX Handler for submitting Activity/Event codes
 */
function dma_code_ajax_handler() {

	// Setup our variables
	$user_id            = dma_get_user_id();
	$is_admin           = isset( $_REQUEST['is_admin'] ) ? $_REQUEST['is_admin'] : false;
	$accession_id       = isset( $_REQUEST['accession_id'] ) ? $_REQUEST['accession_id'] : '';
	$activity_id        = dma_get_activity_id_from_accession_id( $accession_id );
	$time_restricted    = $is_admin ? false : dma_is_checkin_outside_time_restrictions( $activity_id );
	$locked_out         = $is_admin ? false : dma_is_activity_locked_for_user( $user_id, $activity_id );
	$response           = $earned_badges = array();
	$badge_points_total = 0;
	$badge_points       = '';

	// If we have a valid ID, and we're not blocked in any way, submit the activity.
	if ( $activity_id && ! $locked_out && ! $time_restricted ) {
		$completed_checkin_id = dma_activity_submit( $user_id, $activity_id, $accession_id, $is_admin );
		$earned_points = ( $points = get_post_meta( $activity_id, '_dma_points', true ) ) ? sprintf( __( 'You earned %d points!', 'dma'), $points ) : '';
	}

	// If we earned any achievements...
	if ( $achievements = get_transient( 'dma_earned_achievements' ) ) {

		// Grab any earned points and badges
		foreach ( $achievements as $achievement_id ) {
			if ( 'badge' == get_post_type( $achievement_id ) ) {
				$earned_badges[] = $achievement_id;
				$badge_points_total += get_post_meta( $achievement_id, '_dma_points', true );
			}
		}

		// If we've got any badge points, setup a nice text string
		$badge_points = ! empty( $badge_points_total ) ? 'And you earned ' . $badge_points_total . ' bonus points!' : '';

	}

	// Setup our responses
	if ( isset( $completed_checkin_id ) )
		$response['message'] = '<div class="check-in success"><span>' . sprintf( __( 'Success! We have checked you in to: "%s". %s', 'dma' ), get_the_title( $activity_id ), $earned_points ) . '</span></div>';

	if ( isset( $completed_checkin_id ) && 2344 == $activity_id )
		$response['message'] = '<div class="check-in success"><span>' . sprintf( __( 'You liked work of art "%s". %s', 'dma' ), $accession_id, $earned_points ) . '</span></div>';

	if ( count( $earned_badges ) == 1 )
		$response['message'] .= '<div class="check-in success"><span>' . sprintf( __( 'Congrats! You have earned the badge "<a href="%s">%s</a>". %s', 'dma' ), site_url('my-badges'), get_the_title( $earned_badges[0] ), $badge_points ) . '</span></div>';

	if ( count( $earned_badges ) > 1 )
		$response['message'] .= '<div class="check-in success"><span>' . sprintf( __( 'Congrats! You have earned <a href="%s">%d new badges</a>. %s', 'dma' ), site_url('my-badges'), count( $earned_badges ), $badge_points ) . '</span></div>';

	if ( ! $activity_id )
		$response['message'] = '<div class="check-in failure"><span>' . sprintf( __( 'We can\'t seem to find the Code "%s". Please double-check the Code and try again.', 'dma' ), $accession_id ) . '</span></div>';

	if ( $time_restricted )
		$response['message'] = '<div class="check-in failure"><span>' . sprintf( __( 'Sorry, "%s" is not available at this time.', 'dma' ), $accession_id ) . '</span></div>';

	if ( $locked_out )
		$response['message'] = '<div class="check-in failure"><span>' . sprintf( __( 'We\'re sorry, you\'ve already checked in using "%s". Try another activity?', 'dma' ), $accession_id ) . '</span></div>';

	// Grab our user's current points total
	$response['points'] = get_user_meta( $user_id, '_dma_points', true );

	// Send back our data and bail
	echo json_encode( $response );
	die();

}
add_action( 'wp_ajax_dma_code_ajax_handler', 'dma_code_ajax_handler' );
add_action( 'wp_ajax_nopriv_dma_code_ajax_handler', 'dma_code_ajax_handler' );

/**
 * Filter to integrate SMS-based check-ins
 *
 * @param  1.0
 * @param  string  $response     The response we'll send back to our SMS handler
 * @param  integer $phone_number The user's phone number
 * @param  integer $accession_id The user's provided accession ID
 * @return string                Our response based on the checkin success
 */
function dma_txt_integration( $response = '', $phone_number = 0, $accession_id = 0 ) {

	// Look up our user ID based on provided phone number
	$user_query = new WP_User_Query(
		array(
			'meta_key'    => 'phone',
			'meta_value'  => absint( $phone_number )
		)
	);

	// Setup our variables
	$users           = $user_query->get_results();
	$user_id         = is_array( $users ) ? $users[0]->ID : 0;
	$activity_id     = dma_get_activity_id_from_accession_id( $accession_id );
	$time_restricted = dma_is_checkin_outside_time_restrictions( $activity_id );
	$locked_out      = dma_is_activity_locked_for_user( $user_id, $activity_id );

	// Submit our activity, but only if we have both a valid user and activity ID
	if ( $user_id && $activity_id && !$time_restricted && !$locked_out )
		$completed_checkin_id = dma_activity_submit( $user_id, $activity_id, $accession_id );

	// Setup our response
	if ( ! $user_id )
		$response = "We're sorry, but we can't seem to find you in the system. Please see visitor services.";
	elseif ( ! $activity_id )
		$response = "Hmmm... we can't seem to find that Code - are you sure you sent the right one?";
	elseif ( $time_restricted )
		$response = "Sorry, the Code you entered is not available at this time.";
	elseif ( $locked_out )
		$response = "We're sorry, it seems you've already checked in using this Code.";
	elseif ( $completed_checkin_id && 2344 == $activity_id )
		$response = sprintf( "You've been awarded %d points.", get_post_meta( $activity_id, '_dma_points', true ) );
	elseif ( $completed_checkin_id )
		$response = sprintf( "Thank you for checking in! You've been awarded %d points.", get_post_meta( $activity_id, '_dma_points', true ) );

	// Return our response
	return $response;
}
add_filter( 'badgeos_txt_notification', 'dma_txt_integration', 10, 3 );


/**
 * Hook into our checkin process and add custom meta for "Liked a piece of art" posts
 * All params are fed by the "dma_create_checkin" hook.
 */
function dma_associate_art_accession_id( $checkin_id, $user_id, $activity_id, $date, $location_id, $accession_id ) {
	if ( 2344 == $activity_id )
		update_post_meta( $checkin_id, '_dma_accession_id', $accession_id );
}
add_action( 'dma_create_checkin', 'dma_associate_art_accession_id', 10, 6 );

/**
 * Get all activities connected to a given location
 *
 * @since  1.1
 * @param  integer $location_id The given location's post ID
 * @return array                An array of activity posts
 */
function dma_get_activites_for_location( $location_id = false ) {

	// Grab our current location ID if not explicitly set
	if ( ! $location_id )
		$location_id = dma_get_current_location_id();

	// Get our activities connected to this location
	global $wpdb;
	$activities = $wpdb->get_results( $wpdb->prepare(
		"
		SELECT *
		FROM   $wpdb->posts as posts,
		       $wpdb->p2p as p2p
		WHERE  posts.ID = p2p.p2p_to
		       AND p2p.p2p_from = %d
		       AND p2p.p2p_type = %s
		",
		$location_id,
		'dma-location-to-activity'
	));

	// Available filter: dma_activities_for_location
	$activities = apply_filters( 'dma_activities_for_location', $activities, $location_id );

	// Return our connected activities
	return $activities;
}

/**
 * Award activities associated with a location during login
 *
 * @since 1.1
 * @param  integer $user_id The given user's ID
 */
function dma_award_activities_on_login( $user_id ) {
	// Loop through all activites connected to the current location
	foreach ( dma_get_activites_for_location() as $activity ) {
		// Maybe award the activity for the user
		dma_activity_submit( $user_id, $activity->ID );
	}
}
add_action( 'user_authenticated', 'dma_award_activities_on_login' );
