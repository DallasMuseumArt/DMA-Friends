<?php
/*
Plugin Name: DMA SMS Functions
Plugin URI: http://dma.org
Description: Plugin for SMS functions
Author: Ted Forbes
Version: 1.5
Author URI: http://dma.org
*/

// Include the main Twilio engine
require('services/Twilio.php');

/**
 * Handle SMS Checkins
 *
 * @since  1.5
 * @param  string $visitor_phone   The provided phone number (e.g. +12223334444)
 * @param  string $visitor_message The message sent by the user
 * @return string                  The response from BadgeOS
 */
function dma_sms_checkin( $visitor_phone = '', $visitor_message = '' ) {

	// Setup our Twilio credentials
	$sid              = "ACeb858c1b38c8bbfa306051ffe4b0fd02";
	$token            = "c4db8496a5c099e23d5321d10d19caa4";

	// Setup our message variables
	$dma_phone        = '2143909693';
	$visitor_phone    = $_REQUEST['From']; // Overriding the passed value
	$visitor_message  = $_REQUEST['Body']; // Overriding the passed value
	$default_response = "We received your message.";

	// BadgeOS is expecting our phone number to be 10 digits
	$visitor_clean    = str_replace( '+1', '', $visitor_phone );

	// Trigger the BadgeOS check-in
	$badgeos_response = apply_filters( 'badgeos_txt_notification', $default_response, $visitor_clean, $visitor_message );

	// Send a message back to our visitor
	$client = new Services_Twilio($sid, $token);
	$message = $client->account->sms_messages->create(
	  $dma_phone,
	  $visitor_phone,
	  $badgeos_response
	);

	// Return our BadgeOS response, for good measure
	return $badgeos_response;

}

// If we've got a Twilio request, run the checkin
add_action( 'perform_checkin', 'dma_sms_checkin', 10, 2 );
