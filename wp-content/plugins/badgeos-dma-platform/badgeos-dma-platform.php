<?php
/**
 * Plugin Name: DMA Platform
 * Plugin URI: http://WebDevStudios.com
 * Description: Extends BadgeOS to include custom functionality for DMA
 * Version: 1.1
 * Author: WebDevStudios
 * Author URI: http://WebDevStudios.com
 */

class DMA {

	function __construct() {

		// Setup our constants
		$this->directory_path = plugin_dir_path( __FILE__ );
		$this->directory_url = plugin_dir_url( __FILE__ );

		// Grab all our necessary files
		$this->includes();

		// Enqueue our necessary scripts for admin
		add_action( 'admin_enqueue_scripts', array( &$this, 'admin_scripts' ) );

		// Enqueue our necessary scripts for the front-end
		add_action( 'wp_enqueue_scripts', array( &$this, 'register_scripts' ) );

		// Nix the default badge relationships made by the BadgeStack plugin.
		add_filter( 'register_default_badge_relationships', '__return_false' );

		// Register our custom P2P relationships
		add_action( 'init', array( &$this, 'register_achievement_relationships' ) );

		// If no Location ID is set, ask user to set one.
		add_action( 'get_header', array( &$this, 'location_setup' ), 20 );

		// If the location has a redirect specified, redirect the user on login
		add_filter( 'badgeos_auth_success_url', array( &$this, 'location_redirect_url' ) );

	}

	function includes() {

		// Include our CPTs and taxonomies
		require_once( $this->directory_path . '/custom-post-types.php' );
		require_once( $this->directory_path . '/custom-taxonomies.php' );

		// Include our classes
		require_once( $this->directory_path . '/classes/dma_base.php' );
		require_once( $this->directory_path . '/classes/dma_activity.php' );
		require_once( $this->directory_path . '/classes/dma_badge.php' );
		require_once( $this->directory_path . '/classes/dma_user.php' );

		// Include our custom functions
		require_once( $this->directory_path . '/misc-functions.php' );
		require_once( $this->directory_path . '/achievement-functions.php' );
		require_once( $this->directory_path . '/activity-functions.php' );
		require_once( $this->directory_path . '/badge-functions.php' );
		require_once( $this->directory_path . '/badgeos-steps-ui.php' );
		require_once( $this->directory_path . '/rules-engine.php' );
		require_once( $this->directory_path . '/user-functions.php' );
		require_once( $this->directory_path . '/user-registration.php' );

	}

	function admin_scripts( $hook_suffix ) {

		// Load up our custom stuff
		wp_enqueue_style( 'dma-platform-admin', $this->directory_url . 'css/admin.css' );
		wp_enqueue_script( 'dma-platform-admin', $this->directory_url . 'js/admin.js', array( 'jquery-ui-sortable', 'jquery-ui-datepicker' ) );
		wp_localize_script( 'dma-platform-admin', 'myAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ))); // localize for AJAX calls

		// Load up Genesis Admin JS so we can have "check all/none" on our custom taxonomies
		// Note: we're forcing it in like this because by default it's only loaded if post supports Genesis SEO
		if ( 'post-new.php' == $hook_suffix || 'post.php' == $hook_suffix ) {
			genesis_load_admin_js();
		}

	}

	function register_scripts() {

		if ( ! is_user_logged_in() ) {
			wp_enqueue_script( 'dma-registration', $this->directory_url . 'js/dma-user-registration.js', array( 'jquery' ), '1.0' );
			wp_localize_script( 'dma-registration', 'myAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ))); // localize for AJAX calls
		} else {
			wp_enqueue_script( 'dma-platform', $this->directory_url . 'js/dma-platform.js', array( 'jquery' ), '1.0' );
			wp_localize_script( 'dma-platform', 'myAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ))); // localize for AJAX calls
		}
	}

	function get_link( $page = '' ) {

		if ( 'view-progress' == $page )
			return site_url( '/view-progress/' );
		elseif ( 'challenges' == $page )
			return site_url( '/view-progress/#challenges_panel' );
		elseif ( 'badges' == $page )
			return site_url( '/view-progress/#badges_panel' );
		elseif ( 'report-activity' == $page )
			return site_url( '/report-activity/' );
		elseif ( 'activity-logged' == $page )
			return site_url( '/activity-logged/');
		else
			return site_url();

	}

	/**
	 * Register custom Post 2 Post relationships for steps to badges.
	 *
	 * @since  1.0
	 */
	function register_achievement_relationships() {

		p2p_register_connection_type(
			array(
				'name'   => 'step-to-badge',
				'from'   => 'dma-step',
				'to'     => 'badge',
				'title'  => 'Required Steps',
				'fields' => array(
					'order'       => array(
						'title'   => 'Order',
						'type'    => 'text',
						'default' => 0,
						),
					'required'    => array(
						'title'   => 'Required',
						'type'    => 'select',
						'values'  => array( 'Required', 'Optional' ),
						'default' => 'Required',
					),
				),
				'admin_box' => false
			)
		);

		p2p_register_connection_type(
			array(
				'name'       => 'badge-to-badge',
				'from'       => 'badge',
				'to'         => 'badge',
				'reciprocal' => false,
				'title'      => array( 'from' => 'Required by', 'to' => 'Required Badges' )
			)
		);

		p2p_register_connection_type(
			array(
				'name'  => 'activity-to-step',
				'from'  => 'activity',
				'to'    => 'dma-step',
				'title' => 'Required Activity',
			)
		);

		p2p_register_connection_type(
			array(
				'name'  => 'activity-to-checkin',
				'from'  => 'activity',
				'to'    => 'checkin',
				'title' => 'Reported Activity',
			)
		);

		p2p_register_connection_type(
			array(
				'name'  => 'dma-event-to-step',
				'from'  => 'dma-event',
				'to'    => 'dma-step',
				'title' => 'Required Event',
			)
		);

		p2p_register_connection_type(
			array(
				'name'  => 'dma-event-to-checkin',
				'from'  => 'dma-event',
				'to'    => 'checkin',
				'title' => 'Reported Event',
			)
		);

		p2p_register_connection_type(
			array(
				'name'  => 'dma-location-to-checkin',
				'from'  => 'dma-location',
				'to'    => 'checkin',
				'title' => 'Check-in Location',
			)
		);

		p2p_register_connection_type(
			array(
				'name'      => 'dma-location-to-activity',
				'from'      => 'dma-location',
				'to'        => 'activity',
				'title'     => 'Associated Location(s)',
				'admin_box' => 'any'
			)
		);

		p2p_register_connection_type(
			array(
				'name'  => 'dma-location-to-badge',
				'from'  => 'dma-location',
				'to'    => 'badge',
				'title' => 'Associated Location(s)',
			)
		);

		p2p_register_connection_type(
			array(
				'name'  => 'dma-location-to-dma-event',
				'from'  => 'dma-location',
				'to'    => 'dma-event',
				'title' => 'Associated Location(s)',
			)
		);
	}

	/**
	 * If no Location ID is set, remove all content and output our error
	 *
	 * @since  1.0
	 * @return void
	 */
	function location_setup() {

		// If we do NOT have a location set...
		if ( empty( $_SESSION['location_id'] ) ) {
			$do_redirect = apply_filters( 'dma_do_location_redirect', true ) ? true : false;
			if ( !is_page( 'location' ) && $do_redirect ) {
				// Redirect to the set kiosk location page
				wp_redirect( site_url( '/location/' ) );
				exit;
			}
		} else {
			// Redirect non-logged in users to homepage
			if ( ! is_user_logged_in() && ! is_front_page() ) { wp_redirect( site_url() ); exit; }
		}
	}

	/**
	 * Redirect users on login to the location's specified redirect url (or homepage if none set)
	 *
	 * @since  1.0
	 * @return string The intended URL
	 */
	function location_redirect_url( $login_success_url ) {
		return ( $redirect_url = get_post_meta( $_SESSION['location_id'], '_dma_location_redirect', true ) ) ? $redirect_url : $login_success_url;
	}

}
$GLOBALS['dma'] = new DMA();

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
 * Remove the BadgeOS Badge Options metabox from our badges
 */
add_action( 'admin_init', 'remove_badge_metabox', 20 );
function remove_badge_metabox() {
	remove_meta_box( 'badgestack_badge_type_meta_box', 'badge', 'side' );
}

/**
 * Helper function for getting the current location ID
 *
 * @since  1.0
 * @return bool|int The ID of the current location, or 0 if not set
 */

function dma_get_current_location_id() {

	if ( empty( $_SESSION ) )
		session_start();

	return isset( $_SESSION['location_id'] ) ? $_SESSION['location_id'] : 0;
}

/**
 * When creating a new badgestack log entry,
 * relate the entry to the current location.
 */
add_action( 'badgestack_create_log_entry', 'dma_relate_log_entry_to_location', 10, 1 );
function dma_relate_log_entry_to_location( $entry_id ) {
	wp_set_object_terms( $entry_id, dma_get_current_location_id(), 'location', true );
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
add_action( 'init', 'dma_delete_user_meta', 0 );
function dma_delete_user_meta() {

	if ( isset( $_GET['delete_user_achievements'] ) ) {

		// Grab our DMA User object
		$user_id = $_POST['user_id'];

		// Loop through and delete all user's checkins
		if ( $checkins = dma_get_all_user_checkins( $user_id ) ) {
			foreach ( $checkins as $checkin ) {
				wp_delete_post( $checkin->ID, true );
			}
		}

		// Loop through and delete all user's log entries
		if ( $log_entries = get_posts( array( 'posts_per_page' => -1, 'author' => $user_id, 'post_type' => 'badgestack-log-entry' ) ) ) {
			foreach ( $log_entries as $log_entry ) {
				wp_delete_post( $log_entry->ID, true );
			}
		}

		// Delete user's achievements meta
		delete_user_meta( $user_id, '_badgestack_achievements' );
		delete_user_meta( $user_id, '_dma_active_badges' );
		delete_user_meta( $user_id, '_dma_points' );

	}
}

// @DEV: Debug data showing a user's active achievement progress
add_action( 'genesis_after', 'output_dev_data', 99999 );
function output_dev_data() {

	// Bail early if we're not an admin
	if ( ! current_user_can('manage_options') )
		return;

	// Setup our date format
	$date_format = 'M d @ h:i:sa';

	// Grab our current user
	$user_id = dma_get_user_id();

	// If the user has any achievements...
	if ( $badgestack_achievements = get_user_meta( $user_id, '_badgestack_achievements', true ) ) {

		echo '<div style="margin:2em; padding:2em; background:rgba( 255, 255, 255, .7 ); color:#111; border-radius:10px;">';

		// Provide a delete button for their data
		dma_delete_user_meta_button( $user_id );
		$active_badges = dma_get_users_active_badges( $user_id );

		echo '<br/>';
		echo '<h1>You are currently enrolled in ' . count( $active_badges ) . ' badges: ' . "</h1>\n";
		echo '<hr style="border-bottom:1px solid #333;" />';

		// Loop through each badge...
		foreach( $active_badges as $badge ) {
			echo '<strong style="font-size:1.2em">' . $badge->post_title . "</strong><br/>\n";
			echo '<span style="color:#a00;">Date Started: </span>' . date( $date_format, $badge->date_started ) . '<br/>';
			echo '<span style="color:#a00;">Last step earned:</span> ' . date( $date_format, $badge->date_last_step_earned ) . '<br/>';

			// Grab our required steps
			$required_steps = get_posts(
				array(
					'post_type'				=> 'dma-step',
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
				$earned = dma_check_if_user_has_achievement( $user_id, $step->ID, false, false, $badge->date_started );
				echo '<em>Step ' . $count . ':</em> ' . $step->post_title;
				if ( ! empty( $earned ) )
					echo ' <strong>(Earned, ' . date( $date_format, $earned[0]->date_earned ) . ")</strong><br/>\n";
				else
					echo ' <strong>(Not Earned)</strong><br/>';
				$count++;
			}

			echo '<strong>Used Check-ins:</strong><br/>';
			echo '<ul style="list-style:none; margin:0 0 0 1em; padding:0;">';
			foreach ( $badge->used_checkins as $checkin ) {
				echo '<li>ID #' . $checkin->ID . ' - ' . date( $date_format, strtotime( $checkin->post_date ) ) . '</li>';
			}
			echo '</ul>';

			echo '<hr style="border-bottom:1px solid #333;" />';
		}

		echo '<br/><h1>You currently have ' . count( $badgestack_achievements ) . ' achievements:</h1>';
		echo '<hr style="border-bottom:1px solid #333;" />';
		foreach( $badgestack_achievements as $achievement ) {
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
