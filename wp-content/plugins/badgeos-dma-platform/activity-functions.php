<?php

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
	if ( $lockout_limit = get_post_meta( $activity_id, '_badgeos_activity_lockout', true ) ) {

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
		$earned_points = ( $points = get_post_meta( $activity_id, '_badgeos_points', true ) ) ? sprintf( __( 'You earned %d points!', 'dma'), $points ) : '';
	}

	// If we earned any achievements...
	if ( $achievements = get_transient( 'dma_earned_achievements' ) ) {

		// Grab any earned points and badges
		foreach ( $achievements as $achievement_id ) {
			if ( 'badge' == get_post_type( $achievement_id ) ) {
				$earned_badges[] = $achievement_id;
				$badge_points_total += get_post_meta( $achievement_id, '_badgeos_points', true );
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
	$response['points'] = get_user_meta( $user_id, '_badgeos_points', true );

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
		$response = sprintf( "You've been awarded %d points.", get_post_meta( $activity_id, '_badgeos_points', true ) );
	elseif ( $completed_checkin_id )
		$response = sprintf( "Thank you for checking in! You've been awarded %d points.", get_post_meta( $activity_id, '_badgeos_points', true ) );

	// Return our response
	return $response;
}
add_filter( 'badgeos_txt_notification', 'dma_txt_integration', 10, 3 );

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

/**
 * Cache buster to delete a user's activity stream cache
 *
 * @since 2.0.0
 * @param integer $checkin_id The completed checkin ID
 * @param integer $user_id    The given user's ID
 */
function dma_activity_bust_cache(  $checkin_id = 0, $user_id = 0 ) {
	delete_transient( 'dma_user_{$user_id}_activity_stream' );
}
add_action( 'dma_create_checkin', 'dma_activity_bust_cache', 10, 2 );
