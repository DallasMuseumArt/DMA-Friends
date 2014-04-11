<?php

use BadgeOS\LogEntry;

// If this class already exists, just stop here
if ( ! class_exists( 'DMA_User' ) ) {

/**
 * Build our User object
 *
 * Available Methods:
 *   ->profile()
 *   ->edit_profile()
 *   ->save_profile()
 *   ->badge_count()
 *   ->latest_badge()
 *   ->earned_badges()
 *
 * @since  1.0
 * @param  int $this->user_id The ID of the user in question
 */
class DMA_User extends DMA_Base {

	public $context = CHILD_THEME_NAME;
	/**
	 * Setup our current user object
	 *
	 * @since  1.0
	 * @param  int user_id The ID of the user in question
	 */
	public function __construct( $user_id = 0 ) {

		// If we weren't explicitly given a user, try to grab the current user
		$this->ID = $this->user_id = dma_get_user_id( $user_id );

		// Setup all our base user details
		$this->user_info	= get_userdata( $this->user_id );
		$this->user_login	= $this->user_info->user_login;
		$this->first_name	= $this->user_info->first_name;
		$this->last_name	= $this->user_info->last_name;
		$this->full_name	= $this->user_info->first_name . ' ' . $this->user_info->last_name;
		$this->email		= $this->user_info->user_email;
		$this->phone        = get_user_meta( $this->ID, 'phone', true );
		$this->twitter      = get_user_meta( $this->ID, 'twitter', true );
		$this->current_member_number      = get_user_meta( $this->ID, 'current_member_number', true );
		$this->zip          = get_user_meta( $this->ID, 'zip', true );
		$this->sms_optin    = get_user_meta( $this->ID, 'sms_optin', true ) ? true : false;
		$this->email_optin  = get_user_meta( $this->ID, 'email_optin', true ) ? true : false;
		$this->credly_optin = $GLOBALS['badgeos_credly']->user_enabled;
		$this->avatar		= dma_get_user_avatar( apply_filters( 'dma_user_avatar_args', array( 'user_id' => $this->ID ) ) );
		$this->avatar_id    = get_user_meta( $this->ID, 'avatar', true );
		$this->points       = badgeos_get_users_points( $this->ID );
		$this->cache_delete = isset( $_REQUEST['delete-cache'] ) && $_REQUEST['delete-cache'] === 'true' ? true : false;

	}

	/**
	 * Generate markup for zip user profile field
	 *
	 * @since  1.0
	 * @return string Concatenated output for the user zip edit section
	 */
	function zip( $label = 'Zip Code (Optional)' ) {
		return '<fieldset><label for="zip">'. $label .'</label><input type="text" id="zip" name="zip" value="' . $this->zip . '" placeholder="12345" /></fieldset>';
	}
	/**
	 * Get a user's profile
	 *
	 * @since 1.0
	 * @return string|bool The concatenated user profile information if a user is found, FALSE if not
	 */
	function profile() {

		// Only continue if we have a valid user
		if ( $this->user_id ) {

			$profile_bits = apply_filters( 'dma_profile_components', array(
				'avatar'      => $this->avatar,
				'user_name'   => '<h4 class="user-name">Hello, ' . $this->first_name . '</h4>',
				'user_points' => '<div class="user-points"><span class="points">' . $this->points . '</span> <span class="label">Points</span></div>',
				'logout'      => '<a class="user-logout icon-cancel-2" href="' . wp_logout_url( site_url() ) . '">Log Out</a>',
			) );
			// Concatenate our output
			$output = '';
			$output .= '<div class="user-profile">';
				foreach ( $profile_bits as $bit )
					$output .= $bit;
			$output .= '</div><!-- .user-profile -->';

			// Include our edit profile popup content
			// $output .= $this->edit_profile();

			// Return our output
			return $output;
		}

		// We don't have any valid user data, so return false
		return false;

	}

	/**
	 * Pop-up content for editing the user profile
	 *
	 * @since  1.0
	 * @return string Concatenated output for the user profile edit form
	 */
	function edit_profile() {

		// Setup our security
		$nonce = wp_nonce_field( 'save_profile_data', 'profile_data', true, false );

		// Concatenate our output
		$output = '';
		$output .= '<form class="edit-user-profile" method="" action="">';

			if ( $this->context != 'DMA' )
				$output .= '<div class="left">';

			// For validation
			$output .= '<input type="hidden" name="user_id" id="user_id" value="' . $this->ID . '" />';

			// Avatar
			$output .= '<fieldset class="avatar-wrap"><a href="#pop-avatar" class="pop avatar">' . $this->avatar . '</a>';
			$output .= '<input type="hidden" id="avatar" name="avatar" value="' . $this->avatar_id . '" /></fieldset>';

			// First Name
			$output .= '<fieldset><label for="first_name">First Name</label>';
			$output .= '<input type="text" id="first-name" name="first_name" value="' . $this->first_name . '" placeholder="First Name" /></fieldset>';

			// Last Name
			$output .= '<fieldset><label for="last_name">Last Name</label>';
			$output .= '<input type="text" id="last-name" name="last_name" value="' . $this->last_name . '" placeholder="Last Name" /></fieldset>';

			// PIN
			$output .= '<fieldset><label for="pin">Pin Code</label>';
			$output .= '<input type="hidden" name="pin" value="" />';
			$output .= '<a href="#pop-pin" class="pop pin input">&bull;&bull;&bull;&bull;&bull;&bull;</a></fieldset>';

			// Email Address
			$output .= '<fieldset><label for="user_email">Email Address</label>';
			$output .= '<p class="email-error error"></p>';
			$output .= '<input type="text" id="user_email" name="user_email" value="' . $this->email . '" placeholder="Email Address" /></fieldset>';

			if ( $this->context == 'DMA' )
				// ZIP Code
				$output .= $this->zip();

			// Mobile Phone
			$output .= '<fieldset><label for="phone">Mobile Phone (Optional)</label>';
			$output .= '<p class="phone-error error"></p>';
			$output .= '<input type="text" id="phone" name="phone" value="' . $this->phone . '" placeholder="2225551234" /></fieldset>';

			// Twitter
			$output .= '<fieldset><label for="twitter">Twitter Username (Optional)</label>';
			$output .= '<input type="text" id="twitter" name="twitter" value="' . $this->twitter . '" placeholder="@username" /></fieldset>';

			// DMA Partner ID
			$output .= '<fieldset><label for="current_member_number">DMA Partner ID</label>';
			$output .= '<input type="text" id="current_member_number" name="current_member_number" value="' . $this->current_member_number . '" placeholder="" /></fieldset>';

			// Receive Messages from Us
			$output .= '<fieldset class="no-icon"><legend>Receive Messages from Us</legend>';
			$output .= '<div class="checkbox-wrapper"><input class="toggle" type="checkbox" id="email_optin" name="email_optin" ' . checked( $this->email_optin, true, false ) . '> <label for="email_optin" class="standard">By Email</label></div></fieldset>';

			// Filter for additional standard fields
			$output .= apply_filters( 'dma_profile_standard_fields', '', $this->ID, $this );

			// Share My Badges on Credly
			$output .= '<fieldset class="no-icon"><legend>Share my Badges on Credly</legend>';
			$output .= '<input class="toggle" type="checkbox" id="credly_user_enable" name="credly_user_enable" ' . checked( $this->credly_optin, 'true', false ) . '>';
			$output .= '<label for="credly_optin" class="standard"><a class="help small pop credly" href="#what-is-credly" data-popheight="auto"><div class="q icon-help-circled"></div><span>What is Credly?</span></a></label></fieldset>';
			$output .= '<div id="what-is-credly" class="popup close">';
			$output .= dashboard_popup_content( 'What is Credly?' );
			$output .= '<a class="button secondary close-popup" href="#">Close</a>';
			$output .= '</div>';

			// Include our extra fields
			if ( $this->context != 'DMA' ) {
				$output .= '</div>';
				$output .= '<div class="right">';
					// Street Address
					$output .= '<fieldset><label for="street_address">'. __( 'Street Address', 'dma' ) .'</label>';
					$output .= '<input type="text" id="street_address" name="street_address" value="' . get_user_meta( $this->ID, 'street_address', true ) . '" placeholder="1234 Highland Park Ln" /></fieldset>';

					// Apt, suite, etc
					$output .= '<fieldset><label for="apt_suite">'. __( 'Apartment, Suite, Etc.', 'dma' ) .'</label>';
					$output .= '<input type="text" id="apt_suite" name="apt_suite" value="' . get_user_meta( $this->ID, 'apt_suite', true ) . '" placeholder="#2" /></fieldset>';

					// City
					$output .= '<fieldset><label for="city">'. __( 'City', 'dma' ) .'</label>';
					$output .= '<input type="text" id="city" name="city" value="' . get_user_meta( $this->ID, 'city', true ) . '" placeholder="Dallas" /></fieldset>';

					// State
					$output .= '<fieldset><label for="state">'. __( 'State', 'dma' ) .'</label>';
					$output .= '<input type="text" id="state" name="state" value="' . get_user_meta( $this->ID, 'state', true ) . '" placeholder="TX" /></fieldset>';

					// ZIP Code
					$output .= $this->zip( __( 'Zip/Postal Code', 'dma' ) );

					// Home Phone
					$output .= '<fieldset><label for="home_phone">Home Phone</label>';
					$output .= '<p class="home-phone-error error"></p>';
					$output .= '<input type="text" id="home_phone" name="home_phone" value="' . get_user_meta( $this->ID, 'home_phone', true ) . '" placeholder="2225551234" /></fieldset>';

					// Filter for additional extended fields
					$output .= apply_filters( 'dma_profile_extended_fields', '', $this->ID, $this );

				$output .= '</div>';
			}

			// Don't forget security!
			$output .= $nonce;

			$output .= '<div class="clear"></div>
			<button type="submit" id="profile-save" name="Save" class="button submit primary">Save</button>';
			$output .= dma_spinner_notification( 'Profile Saved');
		$output .= '</form>';

		// Avatar Popup
		$output .= '<div id="pop-avatar" class="hidden popup" data-popheight="auto">';
			$output .= '<form class="submit-avatar" method="" action="">';
				$output .= '<h1>'. __( 'Select an Avatar', 'dma' ) .'</h1>';
				// @TODO: when dmafriends.org is live
				// $output .= '<p>'. sprintf( __( 'You can also upload your own image by logging into your DMA Friends account online at %s', 'dma' ), '<b>www.dmafriends.org</b>' ) . '</p>';
				$output .= dma_avatar_layout( $this->avatar_id, $this->context == 'DMA' ? 192 : 96 );
				$output .= '<div class="clear"></div>';
				$output .= '<button type="submit" id="avatar-save" name="Save" class="button submit  primary">Save Changes</button>';
				$output .= dma_spinner_notification( 'Avatar Saved');
				$output .= '<a class="button secondary close-popup cancel" href="#">'. __( 'Close', 'dma' ) .'</a>';
				$output .= $nonce;
			$output .= '</form>';
		$output .= '</div>';

		// PIN Popup
		$output .= '<div id="pop-pin" class="hidden popup" data-popheight="auto">';
			$output .= '<form class="submit-pin" method="" action="">';
				$output .= '<h1>PIN Code</h1>';
				$output .= '<h1>PIN Code Again</h1>';
				$output .= '<p class="pin-error error"></p>';
				$output .= '<div class="left">';
					$output .= '<input type="password" pattern="[0-9]*" id="pin1" name="pin" value="" placeholder="" />';
					$output .= '<div class="checkholder"></div>';
				$output .= '</div>';
				$output .= '<div class="right">';
					$output .= '<input type="password" pattern="[0-9]*" id="pin2" name="pin2" value="" placeholder="" />';
					$output .= '<div class="checkholder"></div>';
				$output .= '</div>';
				$output .= '<div class="clear"></div>';
				$output .= '<button type="submit" id="pin-save" name="Save" class="button submit close-popup primary">Save Changes</button>';
				$output .= dma_spinner_notification( 'PIN Saved');
				$output .= '<a class="button secondary close-popup cancel" href="#">'. __( 'Cancel', 'dma' ) .'</a>';
				$output .= $nonce;
			$output .= '</form>';
		$output .= '</div>';

		// Return our form
		return $output;

	}

	/**
	 * Helper function for saving user profile data via AJAX
	 *
	 * @since  1.0
	 */
	function save_profile() {

		// Parse our seriazed set of formdata
		$_REQUEST = wp_parse_args( $_REQUEST['formdata'], array() );

		// Bail if the user didn't actually save their profile form
		if ( ! ( isset( $_REQUEST['profile_data'] ) && wp_verify_nonce( $_REQUEST['profile_data'], 'save_profile_data' ) ) )
			return;

		// Burn our optin settings (necessary because a "no" value is an unset checkmark, which passes nothing below)
		dma_update_user_data( $this->ID, 'email_optin', false );
		dma_update_user_data( $this->ID, 'credly_user_enable', 'false' );

		// Loop through all our submitted data
		foreach ( $_REQUEST as $field_name => $value ) {

			// If we're looking at PIN and it's empty, skip it
			if ( 'pin' == $field_name && '' == $value )
				continue;

			// Update only our allowed fields
			switch ( $field_name ) {
				case 'email_optin' :
				case 'credly_user_enable' :
					$value = ( ! empty( $value ) ? 'true' : 'false' );
				default:
					dma_update_user_data( $this->ID, $field_name, $value );
					break;
			}
		}

		// Action for handling other events when a profile is saved
		do_action( 'dma_save_profile', $this->ID, $_REQUEST );

		// Finally, setup new user object to reflect updated data
		$GLOBALS['dma_user'] = new DMA_User( $this->ID );

		// Send back our data and bail
		wp_send_json_success( $_REQUEST );
	}

	/**
	 * Get a count of a user's earned badges
	 *
	 * @since  1.0
	 * @param  int $limit_in_days Optionally limit results to the most recent specified number of days
	 * @return int the final count of earned badges
	 */
	function badge_count( $limit_in_days = 0 ) {

		// If the user has earned any badges, count them, otherwise they've earned 0
		$badge_count = ( $badges = badgeos_get_user_achievements( array( 'user_id' => $this->user_id, 'achievement_type' => 'badge', 'since' => strtotime( "-{$limit_in_days} days" ) ) ) ) ? count($badges) : 0;

		// Return our final count
		return $badge_count;
	}

	/**
	 * Get the current badge output for a provided user
	 *
	 * @since  1.0
	 * @return string|bool The output for the user's current badge, or false if no badges earned
	 */
	function latest_badge() {

		// get our transient key
		$transkey = 'dma_user-' . $this->ID . '-latest_badge';
		if ( $this->context == 'DMA' )
			$transkey . '-portal';
		// Assume the user has no badges
		$badge_output = false;

		// @CACHING: Grab our latest badge and return it if found
		$latest_badge = !$this->cache_delete ? get_transient( $transkey ) : false;

		// If we don't have a cached badge, grab the user's achievements
		if ( ! $latest_badge && $earned_badges = badgeos_get_user_achievements( array( 'user_id' => $this->user_id, 'achievement_type' => 'badge' ) ) ) {

			// Use only our newest badge
			$latest_badge = end( $earned_badges );

			// @CACHING: Store our latest badge for 1 week
			set_transient( $transkey, $badge_output, ( 60 * 60 * 24 * 7 ) );

		}

		if ( ! $latest_badge )
			return;

		// Create a new badge object and include our badge output and modal contnent
		$badge = new DMA_Badge( get_post( $latest_badge->ID ), $this->user_id );
		$badge_output = $badge->earned_output();
		$badge_output .= $badge->earned_modal();

		// Return our badge output, or false if no badge
		return $badge_output;
	}

	/**
	 * Generate the output for a user's earned badges
	 *
	 * @since  1.0
	 * @return string|bool  Concatenated output for all of our earned badges, or false if none
	 */
	function earned_badges() {

		// @CACHING: Grab our latest badge and return them if found
		$earned_badges = !$this->cache_delete ? get_transient( 'dma_user-' . $this->ID . '-earned_badges' ) : false;

		// If we don't have a cache of our earned badges...
		if ( ! $earned_badges ) {

			// Assume the user has earned no badges
			$badges = false;
			$date_earned = $badge_id = array();

			// Grab all our earned fun badges
			$earned_badges = badgeos_get_user_achievements( array( 'user_id' => $this->user_id, 'achievement_type' => 'badge' ) );

			// If we actually have any earned badges, lets drop any badges earned more than once
			// Note: this whole process is because array_unique won't work with a multi-dimensional array
			if ( !empty( $earned_badges ) ) {

				// Loop through each badge and add it's ID to an array
				foreach ( $earned_badges as $key => $earned_badge ) {
					$badge_id[$key] = $earned_badge->ID;
				}

				// Run array_unique to drop any duplicate earnings
				$unique = array_unique( $badge_id );

				// Use our $unique array to keep only the non-duplicate badges from our original $earned_badges array
				$earned_badges = array_intersect_key( $earned_badges, $unique );
			}

			// If we STILL have any earned badges (we should)...
			if ( !empty( $earned_badges ) ) {

				// Loop through each earned badge and populate our date earned array
				foreach ( $earned_badges as $id => $earned_badge ) {
					$date_earned[$id] = $earned_badge->date_earned;
				}

				// Sort our earned badges by date earned
				array_multisort( $earned_badges, SORT_DESC, $date_earned );

			}

			// @CACHING: Store our earned badges for 1 week
			set_transient( 'dma_user-' . $this->ID . '-earned_badges', $badges, ( 60 * 60 * 24 * 7 ) );

		}

		// Set our badges output to an empty string
		$badges = '';

		if ( !$earned_badges )
			return;

		// Loop through each earned badge
		foreach ( $earned_badges as $earned_badge ) {

			// Create a new DMA_Badge object and include our badge output and modal content
			$badge = new DMA_Badge( get_post( $earned_badge->ID ), $this->user_id );
			$badges .= $badge->earned_output();
			$badges .= $badge->earned_modal();
		}

		// Return our output, or false if no earned badges
		return $badges;

	}

	/**
	 * Generates a user's activity stream
	 *
	 * @since  1.0
	 *
	 * @return mixed  String of concatenated output or null if user has no logged activities
	 */
	function activity_stream() {

		// Assume we have nothing to output
		$output = '';

        $activities = LogEntry::user($this->ID)
            ->orderBy('timestamp', 'desc')
            ->take(20)
            ->get();

		// If we have activities, generate our output
		if ( ! empty( $activities ) ) {

			$output .= '<div class="activity-stream">';

			foreach ( $activities as $activity ) {

				switch ( $activity->action ) {
					case 'unlocked' :
						if ( 'badge' == get_post_type( $activity->object_id ) ) {
							$earned_badge = new DMA_Badge( get_post( $activity->object_id ) );
							$output .= $this->build_stream_item( array(
								'label' => __( 'You earned a badge' , 'dma' ),
								'title' => sprintf(
										'<a href="#badge-%1$d-pop" class="pop badge-%1$d object-%1$d earned">%2$s</a> %3$s',
										$activity->object_id,
										get_the_title( $activity->object_id ),
										$earned_badge->earned_modal()
									),
								'type'  => 'badge',
								'icon'  => 'cd',
								'time'  => $activity->timestamp,
							) );
						}
						break;
					case 'claimed-reward' :
						$output .= $this->build_stream_item( array(
							'label' => __( 'You claimed a reward' , 'dma' ),
							'title' => get_the_title( $activity->object_id ),
							'type'  => 'reward',
							'icon'  => 'trophy',
							'time'  => $activity->timestamp,
						) );
						break;
					case 'checked-in' :
						$output .= $this->build_stream_item( array(
							'label' => __( 'You checked in at' , 'dma' ),
							'title' => get_the_title( $activity->object_id ),
							'type'  => 'checkin',
							'icon'  => 'location',
							'time'  => $activity->timestamp,
						) );
						break;
					case 'activity' :
						// Handle "Liked a work of art"
						if ( 2344 == $activity->object_id ) {
							$output .= $this->build_stream_item( array(
								'label' => __( 'You liked a work of art' , 'dma' ),
								'title' => sprintf( __( 'Art Accession #%s' , 'dma' ), $activity->artwork_id ),
								'type'  => 'like',
								'icon'  => 'heart',
								'time'  => $activity->timestamp,
							) );

						// Otherwise, this is a normal activity
						} else {
							$output .= $this->build_stream_item( array(
								'title' => get_the_title( $activity->object_id ),
								'time'  => $activity->timestamp,
							) );
						}
						break;
					case 'event' :
						$output .= $this->build_stream_item( array(
							'label' => __( 'You checked in at' , 'dma' ),
							'title' => get_the_title( $activity->object_id ),
							'type'  => 'event',
							'icon'  => 'location',
							'time'  => $activity->timestamp,
						) );
						break;
					default :
						break;
				} // End switch $activity->action

			} // End foreach $activities

			// Manually add our user registration activity at the end
			$output .= $this->build_stream_item( array(
				'label' => __( 'You signed up for' , 'dma' ),
				'title' => __( 'DMA Friends' , 'dma' ),
				'time'  => $this->user_info->user_registered
			) );

			$output .= '</div>';

		}

		// Send back our final output
		return $output;

	}

	function build_stream_item( $args ) {

		$args = wp_parse_args( $args, array(
			'label' => __( 'You completed the activity' , 'dma' ),
			'title' => '',
			'type'  => 'activity',
			'icon'  => 'check',
			'time'  => '',
		) );
		extract( $args );

		$output = '<div class="stream stream-'. $type .'">';
			$output .= '<div class="desc icon-'. $icon .'">'. $label .': <span>'. $title .'</span></div>';
			$output .= '<div class="timestamp">' . human_time_diff( strtotime( $time ), current_time( 'timestamp' ) ) . ' ago</div>';
		$output .= '</div>';

		return $output;
	}
} // END DMA_User class

} // END class_exists check

/**
 * Helper function to automatically create an DMA User and put their detials in the global space
 */
function dma_create_user_object() {
	if ( is_user_logged_in() ) {
		$GLOBALS['dma_user'] = new DMA_User();

		// Add AJAX handler for profile saving
		add_action( 'wp_ajax_dma_save_user_profile', array( $GLOBALS['dma_user'], 'save_profile' ) );
		add_action( 'wp_ajax_nopriv_dma_save_user_profile', array( $GLOBALS['dma_user'], 'save_profile' ) );
	}
}
add_action( 'init', 'dma_create_user_object' );

// @CACHING: Adds cache-buster for 'user-' . $this->ID . '-latest_badge'
// @CACHING: Adds cache-buster for 'user-' . $this->ID . '-earned_badges'
function dma_user_cache_buster( $checkin_earned_achievements = false, $user_id = 0 ) {

	// If we're dealing with earned achievements
	if ( is_array( $checkin_earned_achievements ) ) {

		// Grab our current DMA user
		$dma_user = $GLOBALS['dma_user'];

		// Loop through each achievement
		foreach ( $checkin_earned_achievements as $post_id ) {

			// If we earned a badge, dump the badge and  point transients
			if ( 'badge' == get_post_type( $post_id ) ) {
				delete_transient( 'dma_user-' . $user_id . '-earned_badges' );
				delete_transient( 'dma_user-' . $user_id . '-latest_badge' );
			}

		}
	}
}
add_action( 'dma_create_checkin', 'dma_user_cache_buster', 10, 2 );
add_action( 'checkin_earned_achievements', 'dma_user_cache_buster', 10, 2 );
