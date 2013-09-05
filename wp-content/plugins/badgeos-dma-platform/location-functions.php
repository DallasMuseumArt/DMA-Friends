<?php

/**
 * Helper function for setting the current location ID
 *
 * @since  2.0.0
 * @param  integer $location_id The location ID to use
 * @return integer              The set location ID
 */
function dma_set_current_location_id( $location_id = 0 ) {

	// Use the WP_Session class if available
	if ( class_exists( 'WP_Session' ) ) {

		// Store the location ID within our session data
		$wp_session = WP_Session::get_instance();
		$wp_session['location_id'] = absint( $location_id );

		// Return the stored session ID
		return $wp_session['location_id'];

	} else {
		// Start a new session if one isn't set
		if ( empty( $_SESSION ) )
			session_start();

		// Set the passed ID to our current location ID
		$_SESSION['location_id'] = absint( $location_id );

		// Return the stored session ID
		return $_SESSION['location_id'];
	}

}

/**
 * Helper function for getting the current location ID
 *
 * @since  1.0.0
 * @return integer The ID of the current location, or 0 if not set
 */
function dma_get_current_location_id() {

	// Use the WP_Session class if available
	if ( class_exists( 'WP_Session' ) ) {
		// Get the WP_Session data
		$wp_session = WP_Session::get_instance();

		// Return our location ID if set, or 0 if not
		return isset( $wp_session['location_id'] ) ? $wp_session['location_id'] : 0;
	} else {
		// Start a new session if one isn't set
		if ( empty( $_SESSION ) )
			session_start();

		// Return our location ID if set, or 0 if not
		return isset( $_SESSION['location_id'] ) ? $_SESSION['location_id'] : 0;
	}
}

/**
 * Update the current location if passed via $_GET
 *
 * @since 2.0.0
 */
function dma_update_current_location_id() {
	// Restore our passed location ID if it's passed via querystring
	if ( isset( $_GET['location_id'] ) )
		dma_set_current_location_id( $_GET['location_id'] );
}
add_action( 'get_header', 'dma_update_current_location_id', 1 );

/**
 * Ask user to select current location if not found in $_SESSION
 *
 * @since  1.0.0
 */
function dma_redirect_to_location_page() {

	// If location redirect has been disabled, stop here
	if ( true !== apply_filters( 'dma_do_location_redirect', true ) )
		return;

	// If we do NOT have a location set and we're not on the location setting page
	if ( ! dma_get_current_location_id() ) {
		if ( ! is_page( 'location' ) ) {
			// Redirect to the set kiosk location page
			wp_redirect( site_url( '/location/' ) );
			exit;
		}
	// Or the user is not logged in and not on the homepage
	} else {
		if ( ! is_user_logged_in() && ! is_front_page() ) {
			// Redirect to the homepage
			wp_redirect( site_url() );
			exit;
		}
	}
}
add_action( 'get_header', 'dma_redirect_to_location_page', 20 );

/**
 * Redirect to a location's specified redirect url (or homepage if none set)
 *
 * @since  1.0.0
 * @return string The intended redirect URL
 */
function dma_location_redirect_to_url( $login_success_url ) {
	return ( $redirect_url = get_post_meta( $_SESSION['location_id'], '_dma_location_redirect', true ) ) ? $redirect_url : $login_success_url;
}
add_filter( 'badgeos_auth_success_url', 'dma_location_redirect_to_url' );
