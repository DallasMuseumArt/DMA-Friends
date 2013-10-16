<?php

/**
 * Massive function for building our custom user registration form
 *
 * @since  1.0
 * @return string The full concatenated html output for the registration form
 */
function dma_user_registration_form() {

	$output = '';
	$output .= '
	<div id="do-registration" class="popup ltd" data-popheight="1120">
	<form id="registration" action="' . site_url( '/?register=true' ) . '" method="post">
	';

	// Start
		$output .= '
		<div id="registration-step"></div>
		<div class="registration-step step-1">
			<h1>Sign Up for DMA Friends</h1>
			<p>'. __( 'You are signing up to become a DMA Friend. Once you complete the registration process, you will be ready to check out new badges and activities that are waiting for you. As you participate, you’ll earn credit you can use to pick from a number of fun rewards.', 'dma' ) .'</p>
			<label for="first_name">First Name</label>
			<input type="text" name="first_name" id="first_name" placeholder="First" />
			<label for="last_name">Last Name</label>
			<input type="text" name="last_name" id="last_name" placeholder="Last" />
		</div><!-- .registration-step .step-1 -->
		';

	// Email

		$output .= '
		<div class="registration-step step-2">
			<h2>Enter E-mail Address</h2>
			<p>'. __( 'DMA will use your e-mail address consistent with DMA’s Privacy Policy to send you messages concerning your Friends membership.', 'dma' ) .'</p>

			<label for="email">E-mail Address<p class="email-error error" style="visibility: hidden;">This email is already registered.</p></label>
			<input type="email" name="email" id="email" placeholder="you@example.com" />
			<input type="checkbox" name="email_optin" id="email_optin" checked="checked">
			<label for="email_optin" class="standard">Please send me e-mail concerning other DMA news, events, and offerings.  I understand I may unsubscribe later if I choose to do so.</label>
		</div><!-- .registration-step .step-2 -->
		';

	// PIN

		$output .= '
		<div class="registration-step step-3">
			<h2>Create Pin Code</h2>
			<p> Your PIN code is a four (4) digit Personal Identification Number that is used for security and verification purposes. Your PIN code is private, so please keep it safe!</p>
			<label for="pin1">Pin Code</label>
			<input type="password" pattern="[0-9]*" name="pin1" id="pin1" placeholder="" />
			<label for="pin2">Pin Code Again</label>
			<input type="password" pattern="[0-9]*" name="pin2" id="pin2" placeholder="" />
		</div><!-- .registration-step .step-3 -->
		';

	// Mobile Phone (optional)

		$output .= '
		<div class="registration-step step-4">
			<h2>Enter Mobile Phone Number</h2>
			<p>'. __( 'Sharing your mobile number with DMA Friends is optional but enables you to use Friends in new ways. By entering your phone number here, you agree to allow us to text message you occasionally about your friends membership and your activities.', 'dma' ) .'</p>
			<label for="phone">Mobile Phone Number</label>
			<input type="tel" name="phone" id="phone" placeholder="" />
			<p class="phone-error error" style="visibility: hidden;"></p>
		</div><!-- .registration-step .step-4 -->
		';

	// Zip Code

		$output .= '
		<div class="registration-step step-5">
			<h2>Enter Zip Code</h2>
			<p>'. __( 'Your zip code is optional and will never be shared with outside parties.', 'dma' ) .'</p>
			<label for="zip">'. __( 'Zip Code (Optional)', 'dma' ) .'</label>
			<input type="tel" name="zip" id="zip" placeholder="" />
			<h2>Current DMA Partners</h2>
			<p>'. __( 'Please let us know if you are a current partner of the DMA.', 'dma' ) .'</p>
			<label for="current_member" class="standard">I am a current member.</label>
			<input type="checkbox" name="current_member" id="current_member">
			<div class="hidden">
				<label for="current_member_number">'. __( 'DMA Friend ID as listed on DMA Partner card (optional)', 'dma' ) .'</label>
				<input type="tel" name="current_member_number" id="current_member_number" placeholder="" />
			</div>
		</div><!-- .registration-step .step-5 -->
		';

	// Avatar

		$output .= '
		<div class="registration-step step-6">
			<h2>'. __( 'Select an Avatar', 'dma' ) .'</h2>
			';
			$output .= dma_avatar_layout( 1 );
		$output .= '
		</div><!-- .registration-step .step-6 -->
		';


	// Terms of Service

		$title = 'Accept Terms of Service';
		// Grab our TOS page
		$page = get_page_by_title( $title );

		// Create our page if it doesn't exist
		if ( !$page ) {
			$page = get_post( dma_add_page( array(
				'post_title' => $title,
			) ) );
		}

		$output .= '
		<div class="registration-step step-7">
			<h2>'. $title .'</h2>
			<div class="terms scroll-vertical">'. wpautop( $page->post_content ) .'</div>
		</div><!-- .registration-step .step-7 -->
		';

	// Submission Buttons

		$output .= '
		<button class="registration-previous primary icon-left-open-mini" type="button">back</button>
		<button class="registration-next wide primary" type="button">Continue</button>
		<button class="registration-submit wide primary" type="submit">Accept Terms</button>
		<button class="registration-cancel cancel secondary close-popup" type="reset">Cancel</button>
		';

	// Don't forget security!

		$output .= wp_nonce_field( 'register_new_user', 'profile_data', true, false );

	$output .= '
	</form></div>
	';

	return $output;

}

/**
 * Helper function for handling custom registration process
 *
 * @since  1.0
 * @return int|bool  Returns the newly created user's ID on success, false on failure
 */
add_action( 'template_redirect', 'dma_process_user_registration' );
function dma_process_user_registration() {

	// Bail here if the user didn't actually attempt to register
	if ( ! ( isset( $_POST['profile_data'] ) && wp_verify_nonce( $_POST['profile_data'], 'register_new_user' ) ) )
		return;

	// If we have no email address, bail here
	if ( empty( $_POST['email'] ) )
		return;

	// Generate a unique username, 15 characters long prefixed with DMFDX
	$username = substr( uniqid( 'FR' ), 0, 10 );

	// Sanitize the provided email and pin
	$email = sanitize_email( $_POST['email'] );
	$pin   = isset( $_POST['pin1'] ) ? preg_replace( '/[^\d]+/', '', $_POST['pin1'] ) : '';

	// Create our user
	$user_id = wp_create_user( $username, $pin, $email );

	// If user creation is successful...
	if ( ! empty( $user_id ) && ! is_wp_error( $user_id ) ) {

		// Update our user data (sanitization happens inside dma_update_user_data)
		dma_update_user_data( $user_id, 'avatar', $_POST['avatar'] );
		dma_update_user_data( $user_id, 'first_name', $_POST['first_name'] );
		dma_update_user_data( $user_id, 'last_name', $_POST['last_name'] );
		dma_update_user_data( $user_id, 'phone', $_POST['phone'] );
		dma_update_user_data( $user_id, 'zip', $_POST['zip'] );
		dma_update_user_data( $user_id, 'email_optin', $_POST['email_optin'] );
		dma_update_user_data( $user_id, 'current_member', $_POST['current_member'] );
		dma_update_user_data( $user_id, 'current_member_number', $_POST['current_member_number'] );

		// Grab our location printer details
		$location_printer_ip = get_post_meta( dma_get_current_location_id(), '_dma_location_printer_ip', true );

		// Add a hook so other processes can tap into the custom registration
		do_action( 'dma_user_registration', $user_id, $username, sanitize_text_field( $_POST['first_name'] ), sanitize_text_field( $_POST['last_name'] ), $location_printer_ip );

		// Set a transient for our redirect to load the welcome modal markup
		set_transient( 'welcome-' . $username, 'welcome', 60 );

		// Finally, lets authenticate and log in our new user
		wp_redirect( site_url( '?authenticate=true&username=' . $username ) );
		exit;
	}

	// If we hit this point, there was a failure in creating the user
	return false;

}

/**
 * Helper function to see if user email exists
 *
 * @since  1.0
 * @param  string $email The given user's email
 * @return bool          True if user with given email exists, false otherwise
 */
function dma_user_email_exists( $email = '', $user_id = 0 ) {

	// See if our email was passed in via AJAX
	$email = ( isset($_REQUEST['email']) ) ? $_REQUEST['email'] : $email;
	$user_id = ( isset($_REQUEST['user_id']) ) ? $_REQUEST['user_id'] : $user_id;

	// Attempt to grab a user from the email we were given
	$user = get_user_by( 'email', $email );

	// Setup our response accordingly
	if ( $user && $user->ID != $user_id )
		$response = true;
	else
		$response = false;

	// If this is an ajax request, echo the response and die here
	if ( isset($_REQUEST['email']) ) {
		echo json_encode( $response );
		die();
	}

	// Return our response
	return $response;
}

/**
 * Helper function to see if user phone exists
 *
 * @since  1.0
 * @param  string $email The given user's phone number
 * @return bool          True if user with given phone exists, false otherwise
 */
function dma_user_phone_exists( $email = '', $user_id = 0 ) {

	// See if our email was passed in via AJAX
	$phone = ( isset($_REQUEST['phone']) ) ? $_REQUEST['phone'] : $phone;
	$user_id = ( isset($_REQUEST['user_id']) ) ? $_REQUEST['user_id'] : $user_id;

	// Attempt to grab a user from the phone we were given
	$user = dma_get_user_by_meta_data( 'phone', $phone );

	// Setup our response accordingly
	if ( $user && $user->ID != $user_id )
		$response = true;
	else
		$response = false;

	// If this is an ajax request, echo the response and die here
	if ( isset($_REQUEST['phone']) ) {
		echo json_encode( $response );
		die();
	}

	// Return our response
	return $response;
}

/**
 * AJAX Helper for determining if a user's email has already been registered
 *
 * @since  1.0
 */
add_action( 'wp_ajax_dma_user_email_exists', 'dma_user_email_exists' );
add_action( 'wp_ajax_nopriv_dma_user_email_exists', 'dma_user_email_exists' );
add_action( 'wp_ajax_dma_user_phone_exists', 'dma_user_phone_exists' );
add_action( 'wp_ajax_nopriv_dma_user_phone_exists', 'dma_user_phone_exists' );
