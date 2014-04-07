<?php

/**
 * Function to create a page (or other content) on the fly and optionally set it's featured image
 */
function dma_add_page( $args = array() ) {

	// our new page default args
	$defaults = array(
		'post_title' => 'New Page',
		'post_type' => 'page',
		'post_status' => 'publish',
		'post_author' => 1,
	);
	// merge defaults and passed in arguments
	$args = wp_parse_args( $args, $defaults );

	// check if a featured image url was provided
	$feat_image = isset( $args['featured_image'] ) ? esc_url( $args['featured_image'] ) : false;

	// don't pass the featured image argument to wp_insert_post
	unset( $args['featured_image'] );

	// create our new page and get it's ID
	$new_post_id = wp_insert_post( $args );

	// if we have an ID and a featured image url, upload and set the image as a featured image
	if ( $new_post_id && $feat_image )
		dma_set_featured_img_from_url( $feat_image, $new_post_id, $args['post_title'] );

	return $new_post_id;
}

/**
 * Pass in an image url and post id and this function will upload the image and set it as a featured image
 */
function dma_set_featured_img_from_url( $imgurl, $post_id, $title = '' ) {

	// require the wp admin files that make these functions work
	require_once( ABSPATH . '/wp-admin/includes/file.php' );
	require_once( ABSPATH . '/wp-admin/includes/media.php' );
	require_once( ABSPATH . '/wp-admin/includes/image.php' );

	if ( !empty( $imgurl ) ) {
		$tmp = download_url( $imgurl );

		preg_match('/[^\?]+\.(jpg|JPG|jpe|JPE|jpeg|JPEG|gif|GIF|png|PNG)/', $imgurl, $matches);
		$file_array['name'] = basename( $matches[0] );
		$file_array['tmp_name'] = $tmp;

		if ( is_wp_error( $tmp ) ) {
			@unlink( $file_array['tmp_name'] );
			$file_array['tmp_name'] = '';
		}

		// sideload the file
		$img_id = media_handle_sideload( $file_array, $post_id, $title );

		if ( is_wp_error( $img_id ) ) {
			@unlink( $file_array['tmp_name'] );
			return $img_id;
		}
		// set the newly uploaded file as our featured image
		set_post_thumbnail( $post_id, $img_id );
	}

}

/**
 * Gets and returns the 'what is dma friends' page
 */
function dashboard_popup_content( $title, $echo = false, $help = true, $args = array() ) {

	if ( empty( $args ) )
		$args = array( 'post_title' => $title );

	// Grab our page
	if ( isset( $args['usepath'] ) && $args['usepath'] )
		$page = get_page_by_path( $args['usepath'] );
	else
		$page = get_page_by_title( $title );

	// Create our page if it doesn't exist
	if ( !$page )
		$page = get_post( dma_add_page( $args ) );

	$thumb = get_the_post_thumbnail( $page->ID, 'popup-img' );
	$thumb = $thumb ? '<span class="thumbnail">'. $thumb .'</span>' : '';
	$output = '<div class="details">';
	if ( $help )
		$output .= '<h1 class="title help"><div class="q icon-help-circled"></div><span>'. get_the_title( $page->ID ) .'</span></h1>';
	else
		$output .= '<h1 class="title">'. get_the_title( $page->ID ) .'</h1>';

		$output .= $thumb;
		$output .= '<div class="description">' . wpautop( $page->post_content ) . '</div><!-- .description -->
	</div><!-- .details -->
	';

	if ( !$echo )
		return $output;

	echo $output;
}

/**
 * Outputs markup for ajax waiting spinner and success message
 *
 * @since  1.0
 * @param  string  $message Message to be output in the "success" notification dialog
 * @return string  Concatenated output for spinner markup
 */
function dma_spinner_notification( $message = 'Saved' ) {
	return '
	<div class="spinner">
		<div class="bar1"></div>
		<div class="bar2"></div>
		<div class="bar3"></div>
		<div class="bar4"></div>
		<div class="bar5"></div>
		<div class="bar6"></div>
		<div class="bar7"></div>
		<div class="bar8"></div>
		<div class="bar9"></div>
		<div class="bar10"></div>
		<div class="bar11"></div>
		<div class="bar12"></div>
	</div>
	<p class="notification">'. $message .'</p>
	';
}

/**
 * Helper function for returning canonical URLs for our site
 *
 * @since  1.0
 * @param  string $page The desired page
 * @return string       The full URL of the desired page
 */
function dma_get_link( $page = '' ) {
	return $GLOBALS['dma']->get_link( $page );
}

/**
 * @DEV: If we're logged in, provide a button to delete a user's entire earnings
 */
function dma_delete_user_meta_button( $user_id ) {

	$user_info = get_userdata( $user_id );

	$output = '';
	$output .= '<form action="' . site_url('?delete_user_achievements=true') . '" method="post">';
	$output .= '<input type="hidden" name="user_id" value="' . $user_id . '" />';
	$output .= '<input type="hidden" name="delete_user_achievements" value="true" />';
	$output .= '<input class="button secondary" type="submit" value="Permanently Delete ALL DMA Achievement data for ' . $user_info->first_name . '" />';
	$output .= '</form>';

	echo $output;
}

/**
 * @DEV: Delete a user's entire earnings
 */
function dma_delete_user_meta() {

	if ( isset( $_GET['delete_user_achievements'] ) ) {
		global $wpdb;

		// Grab our DMA User object
		$user_id = absint( $_POST['user_id'] );

		$wpdb->delete( $wpdb->prefix . 'dma_activity_stream', array( 'user_id' => $user_id ), array( '%d' ) );

		// Delete log entries
		$wpdb->delete( $wpdb->prefix . 'dma_log_entries', array( 'user_id' => $user_id ), array( '%d' ) );

		// Delete various achievement meta
		$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->usermeta WHERE meta_key LIKE '_badgeos%%' AND user_id = %d", $user_id ) );

	}
}
add_action( 'init', 'dma_delete_user_meta', 0 );

// @DEV: Debug data showing a user's active achievement progress
function output_dev_data() {

	// Bail early if we're not an admin
	if ( ! current_user_can('manage_options') )
		return;

	// Setup our date format
	$date_format = 'M d @ h:i:sa';

	// Grab our current user
	$user_id = dma_get_user_id();

	// If the user has any achievements...
	if ( $earned_achievements = badgeos_get_user_achievements( array( 'user_id' => $user_id ) ) ) {

		echo '<div style="margin:2em; padding:2em; background:rgba( 255, 255, 255, .7 ); color:#111; border-radius:10px;">';

		// Provide a delete button for their data
		dma_delete_user_meta_button( $user_id );
		$active_badges = badgeos_user_get_active_achievements( $user_id );

		echo '<br/>';
		echo '<h1>You are currently enrolled in ' . count( $active_badges ) . ' badges: ' . "</h1>\n";
		echo '<hr style="border-bottom:1px solid #333;" />';

		// Loop through each badge...
		foreach( $active_badges as $badge ) {
			echo '<strong style="font-size:1.2em">' . get_the_title( $badge->ID ) . "</strong><br/>\n";
			echo '<span style="color:#a00;">Date Started: </span>' . date( $date_format, $badge->date_started ) . '<br/>';
			echo '<span style="color:#a00;">Last step earned:</span> ' . date( $date_format, $badge->last_activity_date ) . '<br/>';

			// Grab our required steps
			$required_steps = get_posts(
				array(
					'post_type'				=> 'step',
					'posts_per_page'		=> -1,
					'suppress_filters'		=> false,
					'connected_direction'	=> 'to',
					'connected_type'		=> 'step-to-' . $badge->post_type,
					'connected_items'		=> $badge->ID,
			));

			// Loop through each step and set the sort order
			foreach ( $required_steps as $required_step ) {
				$required_step->order = get_step_menu_order( $required_step->ID );
			}
			uasort( $required_steps, 'badgeos_compare_step_order' );

			// Loop through each step and output earning status
			$count = 1;
			foreach ( $required_steps as $step ) {
				$earned = badgeos_get_user_achievements( array( 'user_id' => $user_id, 'achievement_id' => $step->ID, 'since' => $badge->date_started ) );
				echo '<em>Step ' . $count . ':</em> ' . $step->post_title;
				if ( ! empty( $earned ) )
					echo ' <strong>(Earned, ' . date( $date_format, $earned[0]->date_earned ) . ")</strong><br/>\n";
				else
					echo ' <strong>(Not Earned)</strong><br/>';
				$count++;
			}

			if ( isset( $badge->used_checkins ) ) {
				echo '<strong>Used Check-ins:</strong><br/>';
				echo '<ul style="list-style:none; margin:0 0 0 1em; padding:0;">';
				foreach ( $badge->used_checkins as $checkin ) {
					echo '<li>ID #' . $checkin->ID . ' - ' . date( $date_format, strtotime( $checkin->post_date ) ) . '</li>';
				}
				echo '</ul>';
			}
			echo '<hr style="border-bottom:1px solid #333;" />';
		}

		echo '<br/><h1>You currently have ' . count( $earned_achievements ) . ' achievements:</h1>';
		echo '<hr style="border-bottom:1px solid #333;" />';
		foreach( $earned_achievements as $achievement ) {
			echo '<strong>' . $achievement->post_type . ' #' . $achievement->ID . ':</strong> ' . get_the_title( $achievement->ID ) . ' <em>(' . date( $date_format ) . ')</em><br/>';
		}

		// Rewards stuff
		$rewards = dma_get_user_rewards( $user_id );
		echo '<br/><h1>You currently have ' . count( $rewards ) . ' rewards:</h1>';
		if ( $rewards ) {
			echo '<ul>';
			foreach ( $rewards as $reward ) {
				echo '<li style="list-style-type: none;"><strong>'. get_the_title( $reward->ID ) .'</strong></li>';

			}
			echo '</ul>';
		}
		echo '<hr style="border-bottom:1px solid #333;" />';

		echo '</div>';
	}
}
// add_action( 'genesis_after', 'output_dev_data', 99999 );
