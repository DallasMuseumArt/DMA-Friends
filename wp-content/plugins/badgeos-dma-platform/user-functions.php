<?php

/**
 * Helper function to get the current user's ID if no ID is provided
 *
 * @since  1.0
 * @param  int $user_id A given user ID
 * @return int          The current user ID if no ID provided
 */
function dma_get_user_id( $user_id = 0 ) {
	return $user_id ? $user_id : get_current_user_id();
}

/**
 * Helper function to output a user's profile
 *
 * @since  1.0
 */
function dma_user_profile() {
	if ( is_user_logged_in() )
		echo $GLOBALS['dma_user']->profile();
}

/**
 * Helper function for building an array of user avatars
 *
 * @since  1.0
 * @return array An array of available avatars
 */
function dma_user_avatars( $ordered = true ) {

	// init our $avatars info array
	$avatars = array();

	// loop through all the files in the plugin's avatars directory and parse the file names
	foreach ( glob( plugin_dir_path(__FILE__) . 'images/avatars/*.jpg' ) as $file ) {
		// remove path info
		$filename   = explode( '/', $file);
		$filename   = end( $filename );

		// separate text
		$fileparts  = explode( '-', $filename );
		$fileparts2 = explode( '.', array_pop( $fileparts ) );

		// the number value is the images place in the order
		$order      = $fileparts2[0];
		$file_ext   = $fileparts2[1];

		// build our array out of the parts
		$avatar_array = array(
			'id'       => str_replace( '.jpg', '', $filename ),
			'order'    => $order,
			'filename' => $filename,
			'desc'     => join( ' ', $fileparts ),
			'file_ext' => $fileparts2[1],
			'url'      => plugins_url( '/images/avatars/'.$filename, __FILE__ ),
		);

		if ( $ordered )
			$avatars[$order] = $avatar_array;
		else
			$avatars[$avatar_array['id']] = $avatar_array;
	}

	// Return our filtered avatar array
	return (array) apply_filters( 'dma_user_avatars', $avatars, $ordered );
}

/**
 * Helper function to return the avatars selection markup
 *
 * @since  1.0
 * @return string Markup containing the listing of available avatars
 */
function dma_avatar_layout( $checked = false, $size = 192 ) {
	$layout = '
	<div class="avatar-listing">
	';
	// get our $avatars info array
	$avatars = dma_user_avatars();
	// iterate our 3 rows
	$count = 1;
	for ( $row = 1; $row < 4; $row++ ) {
		$layout .= '<div class="row-'. $row .'">';
		// iterate our 4 quadrants
		for ( $div = 1; $div < 5; $div++ ) {
			// make sure the $image is set
			if ( ! isset( $avatars[$count] ) ) {
				$count++;
				continue;
			}
			// add an input to our list
			$layout .= '
			<input type="radio" id="avatar-'. $count .'" name="avatar" value="'. $avatars[$count]['id'] .'"';
			$layout .= $checked === true ? checked( $avatars[$count], 1, false ) : checked( $checked, $avatars[$count]['id'], false );
			$layout .= '/>
			<label class="one-fourth quad-'. $div .' count-'. $count .' standard" for="avatar-'. $count .'">
				<figure>
					<img width="'. absint( $size ) .'" height="'. absint( $size ) .'" src="'. $avatars[$count]['url'] .'" />
					<figcaption>'. /*$avatars[$count]['desc'] .*/'</figcaption>
				</figure>
			</label><!-- .one-fourth quad-'. $div .' count-'. $count .' -->
			';
			$count++;
		}
		$layout .= '</div><!-- .row-'. $row .' -->';
	}

	$layout .= '
	</div><!-- .avatar-listing -->
	';
	return $layout;
}

/**
 * Helper function to return URL of avatar based on avatar ID
 *
 * @since  1.0
 * @param  array  $args  An array of the various args we need
 * @return string        An <img> tag containing the user's avatar
 */
function dma_get_user_avatar( $args = array() ) {

	// Setup our defaults
	$defaults = array(
		'user_id'  => dma_get_user_id(),
		'width'    => 192,
		'height'   => 192,
		'url_only' => false
	);
	$args = wp_parse_args( $args, $defaults );

	extract( $args );

	// Grab our user's avatar ID
	$avatar_id = sanitize_text_field( get_user_meta( $user_id, 'avatar', true ) );
	$avatar_id = $avatar_id ? $avatar_id : 0;

	// Grab our array of possible avatars
	$avatars = dma_user_avatars();

	// get the avatar
	if ( $avatar_id !== 0 ) {
		$all_ids = wp_list_pluck( $avatars, 'id' );
		$avatar_key = array_search( $avatar_id, $all_ids );
		$avatar = $avatars[$avatar_key];
	} else {
		// or the default
		$avatar = $avatars[1];
	}

	// build image html
	$avatar_src = '<img class="user-avatar" src="' . $avatar['url'] . '" width="' . absint( $width ) . '" height="' . absint( $height ) . '" />';

	// If we want all the data, return that and quit here
	if ( $url_only == 'array' )
		return array( 'id' => $avatar['id'], 'url' => $avatar['url'], 'src' => $avatar_src );
	// If we only want a URL, return that and quit here
	if ( $url_only && $url_only != 'array' )
		return $avatar['url'];

	// Otherwise, return img html
	return $avatar_src;
}

/**
 * Add extra user meta fields to the Edit Profile screen
 *
 * @since  1.0
 * @param  array  $user  An array of user data
 * @return nothing
 */
function dma_add_user_profile_fields( $user ) {
	if ( !current_user_can( 'manage_options' ) )
		return;

	?>
	<h2><?php _e('DMA Profile Information', 'your_textdomain'); ?></h2>
	<table class="form-table">
		<tr class="dma_curr_member">
			<th><label for="current_member">Existing DMA Member?</label></th>
			<td>
				<input type="checkbox" name="current_member" id="current_member" <?php checked( get_user_meta( $user->ID, 'current_member', true ) ); ?>>
				<div class="hidden">
					<div><label for="current_member_number">Member Number (Optional)</label></div>
					<input type="tel" name="current_member_number" id="current_member_number" class="regular-text" value="<?php echo esc_attr( get_user_meta( $user->ID, 'current_member_number', true ) ); ?>"/>
				</div>
			</td>
		</tr>
		<tr>
			<th><label for="twitter">Twitter</label></th>
			<td>
				<input type="text" name="twitter" id="twitter" value="<?php echo esc_attr( get_user_meta( $user->ID, 'twitter', true ) ); ?>" class="regular-text" /><br />
				<span class="description">Users full twitter handle, including "@" (e.g. <code>@user</code>).</span>
			</td>
		</tr>
		<tr>
			<th><label for="phone">Mobile Phone</label></th>
			<td>
				<input type="text" name="phone" id="phone" value="<?php echo esc_attr( get_user_meta( $user->ID, 'phone', true ) ); ?>" class="regular-text" /><br />
				<span class="description">Digits only (e.g. <code>2345678910</code>)</span>
			</td>
		</tr>
		<tr>
			<th><label for="home_phone">Home Phone</label></th>
			<td>
				<input type="text" name="home_phone" id="home_phone" value="<?php echo esc_attr( get_user_meta( $user->ID, 'home_phone', true ) ); ?>" class="regular-text" /><br />
				<span class="description">Digits only (e.g. <code>2345678910</code>)</span>
			</td>
		</tr>
		<tr>
			<th><label for="street_address">Street Address</label></th>
			<td>
				<input type="text" name="street_address" id="street_address" value="<?php echo sanitize_text_field( get_user_meta( $user->ID, 'street_address', true ) ); ?>" class="regular-text" /><br />
			</td>
		</tr>
		<tr>
			<th><label for="apt_suite">Apt, suite, etc.</label></th>
			<td>
				<input type="text" name="apt_suite" id="apt_suite" value="<?php echo sanitize_text_field( get_user_meta( $user->ID, 'apt_suite', true ) ); ?>" class="regular-text" /><br />
			</td>
		</tr>
		<tr>
			<th><label for="city">City</label></th>
			<td>
				<input type="text" name="city" id="city" value="<?php echo sanitize_text_field( get_user_meta( $user->ID, 'city', true ) ); ?>" class="regular-text" /><br />
			</td>
		</tr>
		<tr>
			<th><label for="state">State</label></th>
			<td>
				<input type="text" name="state" id="state" value="<?php echo sanitize_text_field( get_user_meta( $user->ID, 'state', true ) ); ?>" class="regular-text" /><br />
			</td>
		</tr>
		<tr>
			<th><label for="zip">Zip Code</label></th>
			<td>
				<input type="text" name="zip" id="zip" value="<?php echo sanitize_text_field( get_user_meta( $user->ID, 'zip', true ) ); ?>" class="regular-text" /><br />
				<span class="description">First 5 digits only (e.g. <code>12345</code>)</span>
			</td>
		</tr>
		<tr>
			<th><label for="user_points">User Points</label></th>
			<td>
				<input type="text" name="user_points" id="user_points" value="<?php echo absint( get_user_meta( $user->ID, '_badgeos_points', true ) ); ?>" class="regular-text" /><br />
				<span class="description">The user's points total. Entering a new total will automatically log the change and difference between totals.</span>
			</td>
		</tr>
		<tr>
			<th>Email Opt-in</th>
			<td>
				<label for="email_optin"><input type="checkbox" name="email_optin" id="email_optin" <?php checked( get_user_meta( $user->ID, 'email_optin', true ) ); ?> /> Allow Email from DMA</label>
				<span class="description"></span>
			</td>
		</tr>
		<?php echo $GLOBALS['badgeos_credly']->credly_profile_setting( $user ); ?>
		<tr>
			<th><label for="user_avatar">Avatar</label></th>
			<td>
				<?php
				$curr_avatar = dma_get_user_avatar( array( 'user_id' => $user->ID, 'width' => 100, 'height' => 100, 'url_only' => 'array' ) );
				echo dma_avatar_layout( $curr_avatar['id'], 68 );
				?>
			</td>
		</tr>
	</table>
	<h2>Award an Activity/Event Code</h2>
	<input type="text" name="accession_id" placeholder="Enter Activity Code" />
	<a class="button activity-submit" href="">Award</a>
	<div style="width:30px;"><?php echo dma_spinner_notification(''); ?></div>
	<div class="dma-code-notices"></div>
<?php }
add_action( 'show_user_profile', 'dma_add_user_profile_fields' );
add_action( 'edit_user_profile', 'dma_add_user_profile_fields' );
//remove_action( 'personal_options', array( $GLOBALS['badgeos_credly'], 'credly_profile_setting' ), 999 ); //this isn't working

/**
 * Save extra user meta fields to the Edit Profile screen
 *
 * @since  1.0
 * @param  string  $user_id  User ID being saved
 * @return nothing
 */
function dma_save_user_profile_fields( $user_id ) {

	if ( !current_user_can( 'edit_user', $user_id ) )
		return FALSE;

	// Burn our optin settings (necessary because a "no" value is an unset checkmark, which passes nothing below)
	dma_update_user_data( $user_id, 'email_optin', false );
	dma_update_user_data( $user_id, 'current_member', false );

	// Loop through all our submitted data
	foreach ( $_POST as $field_name => $value ) {

		// If we're looking at PIN and it's empty, skip it
		if ( 'pin' == $field_name && '' == $value )
			continue;

		// Update only our allowed fields
		switch ( $field_name ) {
			case 'avatar' :
			case 'phone' :
			case 'twitter' :
			case 'email_optin' :
			case 'street_address' :
			case 'apt_suite' :
			case 'city' :
			case 'state' :
			case 'zip' :
			case 'home_phone' :
			case 'current_member' :
			case 'current_member_number' :
				dma_update_user_data( $user_id, $field_name, $value );
				break;
			default:
				continue;
		}
	}

	// Update our user's points total, but only if edited
	if ( $_POST['user_points'] != get_user_meta( $user_id, '_badgeos_points', true ) )
		badgeos_update_users_points( $user_id, absint( $_POST['user_points'] ), get_current_user_id() );

}
add_action( 'personal_options_update', 'dma_save_user_profile_fields' );
add_action( 'edit_user_profile_update', 'dma_save_user_profile_fields' );

/**
 * Helper function for updating a given user field with new data
 *
 * @since  1.0
 * @param  integer  $user_id The given user's ID
 * @param  string   $type    The name of the data field we want to update
 * @param  mixed    $data    The data we want to update
 * @return int|bool          The integer of the $user_id or $user_meta ID on success, false otherwise
 */
function dma_update_user_data( $user_id = 0, $type, $data ) {

	// Grab our user ID if none specified
	$user_id = dma_get_user_id( $user_id );

	// Figurue out what type of data we're working with, and do something with it
	switch ( $type ) {

		// Special handling for core fields
		case 'first_name' :
		case 'last_name' :
		case 'user_email' :
			return wp_update_user( array( 'ID' => $user_id, $type => sanitize_text_field( $data ) ) );
			break;

		// Password validation
		case 'pin' :
			$data = preg_replace( '/[^\d]+/', '', $data ); // the regex strips any non-digit characters
			return wp_update_user( array( 'ID' => $user_id, 'user_pass' => sanitize_text_field( $data ) ) );
			break;

		// Digit-only sanitization
		case 'phone' :
		case 'zip' :
			$data = preg_replace( '/[^\d]+/', '', $data ); // the regex strips any non-digit characters
			return update_user_meta( $user_id, $type, $data );
			break;

		// Boolean sanitization (for checkboxes)
		case 'sms_optin':
		case 'email_optin':
		case 'current_member':
			$data = isset( $data ) && !empty( $data ) ? true : false;
			return update_user_meta( $user_id, $type, $data );
			break;

		// Special twitter sanitization
		case 'twitter' :
			// Make sure our input begins with an "@"
			if ( '@' != substr( $data, 0, 1 ) )
				$data = '@' . $data;
			// Make sure our username uses only valid characters, and doesn't exceed 15 characters total.
			preg_match( '/^@([A-Za-z0-9_]{1,15})/', $data, $sanitized_twitter );
			$handle = isset( $sanitized_twitter[0] ) ? strtolower( $sanitized_twitter[0] ) : '';
			return update_user_meta( $user_id, $type, $handle );
			break;

		// Standard text sanitization
		default :
			return update_user_meta( $user_id, $type, sanitize_text_field( $data ) );
			break;
	}

	// If we make it this far, something went wrong.
	return false;

}

/**
 * Helper function to retrieve a user based on some meta information. Props Tom McFarlin (http://tommcfarlin.com/get-user-by-meta-data/)
 *
 * @since  1.0
 * @param  string      $meta_key   The given meta key we want to use to find our user
 * @param  mixed       $meta_value The given value of our meta
 * @return object|bool             The user object on success, false on failure
 */
function dma_get_user_by_meta_data( $meta_key, $meta_value ) {

	// Query for users based on the meta data
	$user_query = new WP_User_Query(
		array(
			'meta_key'    => $meta_key,
			'meta_value'  => $meta_value
		)
	);

	// Get the results from the query, returning the first user
	$users = $user_query->get_results();

	if ( is_array( $users ) && ! empty( $users ) )
		return $users[0];
	else
		return false;

}

/**
 * Helper function to retrieve a user's username based on the card they scanned.
 *
 * This is necessary because the user might be scanning their Friend ID
 * (which IS their username), or they may be scanning an existing Partner
 * ID (which is stored in user meta). No matter which they scan, we're
 * going to return their actual username to use for login.
 *
 * @since  1.0
 * @param  string $username The scanned ID (possibly a username)
 * @return string           The verified username
 */
function dma_get_username_from_id_scan( $username ) {
	// First, see if $username IS already a user name...
	if ( username_exists( $username ) ) {
		return $username;

	// If not, let's see if it is a Partner ID (meta)...
	} elseif ( $user = dma_get_user_by_meta_data( 'current_member_number', $username ) ) {
		return $user->user_login;
	}
}

/**
 * Render a "Bookmark this" button and supporting form for a given object
 *
 * @since  1.0
 * @param  integer $user_id       The given user's ID
 * @param  integer $object_id     The given object's post ID
 * @param  boolean $is_bookmarked True if the item is already bookmarked, false otherwise
 * @return string                 The concatenated output
 */
function dma_create_bookmark_form( $user_id = 0, $object_id = 0, $is_bookmarked = false ) {

	// Setup and return our output
	$output = '';
	$output .= '<form class="bookmark-this" action="" method="">';
		$output .= '<input type="hidden" name="user_id" value="' . $user_id . '">';
		$output .= '<input type="hidden" name="object_id" value="' . $object_id . '">';
		$output .= '<input type="hidden" name="action" value="' . ( $is_bookmarked ? 'delete' : 'add' ) . '">';
		$output .= '<button type="submit" name="bookmarkit" class="submit icon-bookmark wide">' . ( $is_bookmarked ? __( 'Bookmarked', 'dma' ) : __( 'Bookmark This', 'dma' ) ) . '</button>';
	$output .= '</form>';

	return $output;
}

/**
 * Update our user's bookmarked badges
 *
 * @since 1.0
 * @param int $user_id    The id of our user who's bookmarked badge list we're updating
 * @param int $object_id   The id of our badge we're adding or deleting
 * @param string $action  Whether we're supposed to be adding or deleting the badge
 * @return bool           True if we updated our meta key. False otherwise
 */
function dma_update_user_bookmarks( $user_id = 0, $object_id = 0, $action = 'add' ) {

	// See if our params were passed in via AJAX
	$user_id   = ( isset($_REQUEST['user_id']) ) ? $_REQUEST['user_id'] : $user_id;
	$object_id = ( isset($_REQUEST['object_id']) ) ? $_REQUEST['object_id'] : $object_id;
	$action    = ( isset($_REQUEST['add_or_delete']) ) ? $_REQUEST['add_or_delete'] : $action;

	// Grab our user's existing bookmarks (or empty array if none)
	$user_bookmarks = maybe_unserialize( get_user_meta( $user_id, '_dma_bookmarked_items', true ) );
	if ( empty( $user_bookmarks) ) $user_bookmarks = array();

	if ( 'add' == $action ) {

		$user_bookmarks[] = $object_id;

		// Make sure we didn't accidentally insert a duplicate
		$user_bookmarks = array_unique( $user_bookmarks );

	} elseif ( 'delete' == $action && ! empty( $user_bookmarks ) ) { // We can't delete what's not there.

		$user_bookmarks = array_diff( $user_bookmarks, array( $object_id ) );

	}

	// Update our user meta
	$updated = update_user_meta( $user_id, '_dma_bookmarked_items', $user_bookmarks );

	// If this is an ajax request, echo the response and die here
	if ( isset($_REQUEST) ) {
		echo json_encode( $action . 'ed object #' . $object_id . ' for user ' . $user_id );
		die();
	}

	// For all others, return our updated value
	return $updated;

}
/**
 * AJAX Helper for determining if a user's email has already been registered
 *
 * @since  1.0
 */
add_action( 'wp_ajax_dma_update_user_bookmarks', 'dma_update_user_bookmarks' );
add_action( 'wp_ajax_nopriv_dma_update_user_bookmarks', 'dma_update_user_bookmarks' );

/**
 * Update our user's bookmarked badges
 *
 * @since  1.0
 * @param  integer  $user_id The given user's ID
 * @param  integer  $pin     The user's entered PIN
 * @return bool              True if the pin is valid, false otherwise
 */
function dma_is_user_pin_valid( $user_id = 0, $pin = '' ) {

	// See if our params were passed in via AJAX
	$user_id = ( isset($_REQUEST['user_id']) ) ? $_REQUEST['user_id'] : $user_id;
	$pin = ( isset($_REQUEST['pin']) ) ? $_REQUEST['pin'] : $pin;

	// Grab our user data
	$user = get_userdata( $user_id );

	// See if the provided pin matches the user's password
	if ( wp_check_password( $pin, $user->user_pass, $user_id ) )
		$response = true;
	else
		$response = false;

	// If this is an ajax request, echo the response and die here
	if ( isset($_REQUEST) ) {
		echo json_encode( $response );
		die();
	}

	return $response;

}
/**
 * AJAX Helper for determining if a user's email has already been registered
 *
 * @since  1.0
 */
add_action( 'wp_ajax_dma_is_user_pin_valid', 'dma_is_user_pin_valid' );
add_action( 'wp_ajax_nopriv_dma_is_user_pin_valid', 'dma_is_user_pin_valid' );

/**
 * Output our User Notices posts
 */
function dma_user_notices() {

	// Grab all notice posts
	$notices = get_posts( array( 'post_type' => 'notices', 'posts_per_page' => -1 ) );

	// Loop through each notice and generate our output
	$output = '';
	foreach ( $notices as $notice ) {
		$class = !$output ? ' first' : '';
		$output .= '<div class="user-notice' . $class . '">' . apply_filters( 'the_content', $notice->post_content ) . '</div>' . "\n";
	}

	// Available filter: dma_user_notices
	$output = apply_filters( 'dma_user_notices', $output, $notices );

	// Finally, output our notices
	echo $output;
}

/**
 * Log our user's checkin
 *
 * @since 1.1
 * @param  integer $user_id The given user's ID
 */
function dma_log_user_login( $user_id ) {

	// Grab our current location ID
	$location = get_post( dma_get_current_location_id() );
	$userdata = get_userdata( $user_id );

	// Log the user's checkin if they're at a kiosk location
	if ( $location )
		badgeos_post_log_entry( $location->ID, $user_id, 'checkin', "$userdata->user_login checked-in at $location->post_title" );

}
add_action( 'user_authenticated', 'dma_log_user_login' );
