<?php

// Adds our Steps metabox to the Badge post editor
add_action( 'add_meta_boxes', 'badgeos_add_create_steps_meta_box' );
function badgeos_add_create_steps_meta_box() {
	add_meta_box(
		'badgeos_create_steps',
		__( 'Required Steps', 'mvp' ),
		'badgeos_create_steps_meta_box',
		'badge'
	);
}

// Renders the HTML for meta box, refreshes whenever a new step is added
function badgeos_create_steps_meta_box( $post ) {

	// Grab our $wpdb global
	global $wpdb;

	// Grab our Badge's required steps
	$required_steps = get_posts(
		array(
			'post_type'				=> 'dma-step',
			'posts_per_page'		=> -1,
			'suppress_filters'		=> false,
			'connected_direction'	=> 'to',
			'connected_type'		=> 'step-to-' . $post->post_type,
			'connected_items'		=> $post->ID,
	));

	// Loop through each step and set the sort order
	foreach ( $required_steps as $required_step ) {
		$required_step->order = get_step_menu_order( $required_step->ID );
	}

	// Sort the steps by their order
	uasort( $required_steps, 'badgeos_compare_step_order' );

	// Concatenate our step output
	echo '<ul id="steps_list">';
	foreach ( $required_steps as $step ) {
		output_edit_step_html( $step->ID );
	}
	echo '</ul>';

	// Render our buttons
	echo '<input style="margin-right: 1em" class="button" type="button" onclick="badgeos_add_new_step(' . $post->ID . ');" value="Add new step">';
	echo '<input class="button-primary" type="button" onclick="badgeos_update_steps( );" value="Save all Steps">';
	echo '<img class="save-steps-spinner" src="' . admin_url( '/images/wpspin_light.gif' ) . '" style="margin-left: 10px; display: none;" />';

}

// Add our Steps JS to the Badge post editor
add_action( 'admin_print_scripts-post-new.php', 'portfolio_admin_script', 11 );
add_action( 'admin_print_scripts-post.php', 'portfolio_admin_script', 11 );
function portfolio_admin_script() {
    global $post_type;
    if( 'badge' == $post_type )
    	wp_enqueue_script( 'dma-step-ui', plugin_dir_url( __FILE__ ) . 'js/dma-steps-ui.js' );
}

// AJAX Handler for adding a step
add_action( 'wp_ajax_add_step', 'badgeos_add_step_ajax_handler' );
function badgeos_add_step_ajax_handler() {

	// Create a new Step post and grab it's ID
	$step_id = wp_insert_post(
		array(
			'post_type'   => 'dma-step',
			'post_status' => 'publish'
	) );

	// Output the edit step html to insert into the Steps metabox
	output_edit_step_html( $step_id );

	// Grab the post object for our Badge
	$badge = get_post( $_POST['badge_id'] );

	// Create the P2P connection from the step to the badge
	$p2p_id = p2p_create_connection( 'step-to-' . $badge->post_type, array(
		'from' => $step_id,
		'to' => $_POST['badge_id'],
		'meta' => array(
			'date' => current_time( 'mysql' )
		)
	) );

	// Add relevant meta to our P2P connection
	p2p_add_meta( $p2p_id, 'required', 'Required' );
	p2p_add_meta( $p2p_id, 'order', '0' );

	// Die here, because it's AJAX
	die;
}

// AJAX Handler for deleting a step
add_action( 'wp_ajax_delete_step', 'badgeos_delete_step_ajax_handler' );
function badgeos_delete_step_ajax_handler() {
	wp_delete_post( $_POST['step_id'] );
	die;
}

// AJAX Handler for saving steps
add_action( 'wp_ajax_update_steps', 'badgeos_update_steps_ajax_handler' );
function badgeos_update_steps_ajax_handler() {

	// Grab our $wpdb global
	global $wpdb;

	// Loop through each of the created steps
	foreach ( $_POST['steps'] as $step ) {

		// Grab all of the relevant values of that step
		$step_id = $step['step_id'];
		$requirement_value = (int) $step['requirement_value'];
		$required_duration = ( ! empty( $step['required_duration'] ) ) ? $step['required_duration'] : 1;
		$requirement_type = $step['requirement_type'];
		$measurement = $step['measurement'];
		// $first_time_only = $step['first_time_only'] ? true : false;
		$order = $step['order'];

		// Clear all relation data
		$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->p2p WHERE p2p_to=%d", $step_id ) );
		wp_set_object_terms( $step_id, NULL, 'fitness-type' );
		wp_set_object_terms( $step_id, NULL, 'activity-type' );

		// Flip between our requirement type and make an appropriate connection
		switch ( $requirement_type ) {

			// Connect the step to ANY logged activity
			case 'any-activity' :
				wp_set_object_terms( $step_id, 'any-activity', 'special-step-earning-option' );
				$term = get_term( $requirement_value, 'special-step-earning-option' );
				$title = 'any activity';
			break;

			// Connect the step to a specific activity
			case 'activity' :
				p2p_create_connection( 'activity-to-step', array(
					'from' => $requirement_value,
					'to' => $step_id,
					'meta' => array(
						'date' => current_time('mysql')
					)
				) );
				$activity = get_post( $requirement_value );
				$title = $activity->post_title;
			break;

			// Connect the step to an activity type
			case 'activity-type' :
				wp_set_object_terms( $step_id, $requirement_value, 'activity-type' );
				$term = get_term( $requirement_value, 'activity-type' );
				$title = $term->name;
			break;

			// Connect the step to an activity category
			case 'activity-category' :
				wp_set_object_terms( $step_id, $requirement_value, 'activity-category' );
				$term = get_term( $requirement_value, 'activity-category' );
				$title = $term->name;
			break;

			// Connect the step to ANY logged event
			case 'any-event' :
				wp_set_object_terms( $step_id, 'any-event', 'special-step-earning-option' );
				$term = get_term( $requirement_value, 'special-step-earning-option' );
				$title = 'any event';
			break;

			// Connect the step to a specific event
			case 'event' :
				p2p_create_connection( 'dma-event-to-step', array(
					'from' => $requirement_value,
					'to' => $step_id,
					'meta' => array(
						'date' => current_time('mysql')
					)
				) );
				$event = get_post( $requirement_value );
				$title = $event->post_title;
			break;

			// Connect the step to an event type
			case 'event-type' :
				wp_set_object_terms( $step_id, $requirement_value, 'event-type' );
				$term = get_term( $requirement_value, 'event-type' );
				$title = $term->name;
			break;

			// Connect the step to an event category
			case 'event-category' :
				wp_set_object_terms( $step_id, $requirement_value, 'event-category' );
				$term = get_term( $requirement_value, 'event-category' );
				$title = $term->name;
			break;

			// Setup a repeater step
			case 'repeat' :
				wp_set_object_terms( $step_id, 'repeat', 'special-step-earning-option' );
				$term = get_term( $requirement_value, 'special-step-earning-option' );
				$title = 'repeat prior step';
			break;
		}

		// Setup our measurement terms
		switch ( $measurement ) {
			case 'check-ins' :
				wp_set_object_terms( $step_id, 'check-ins', 'step-measurement' );
			break;
			// case 'minutes' :
			// 	wp_set_object_terms( $step_id, 'minutes', 'step-measurement' );
			// break;
		}

		// Resave the title of the step.
		$title = isset( $step['title'] ) ? $step['title'] : $required_duration . ' ' . $measurement . ' of ' . $title;
		wp_update_post( array( 'ID' => $step_id, 'post_title' => $title ) );

		// Update the step order
		p2p_update_meta( badgeos_get_p2p_id_from_child_id( $step_id ), 'order', $order );

		// Update required duration
		update_post_meta( $step_id, '_dma_checkin_count', $required_duration );

		// Restrict earning to only the first time the activity is logged
		// update_post_meta( $step_id, '_badgeos_first_time_only', $first_time_only );
	}

	// Cave Johnson. We're done here.
	die;
}


/**
 * Helper function for generating the HTML output for configuring a given step
 *
 * @since  1.0
 * @param  integer $step_id The given step's ID
 * @return string           The concatenated HTML input for the step
 */
function output_edit_step_html( $step_id ) {

	// Grab our step's requirements and measurement
	$requirements = badgeos_get_step_requirements( $step_id );
	// $measurement = badgeos_get_step_measurement( $step_id );
	// $first_time_only = get_post_meta( $step_id, '_badgeos_first_time_only', true );
?>

	<li class="step-row step-<?php echo $step_id; ?>" data-step-id="<?php echo $step_id; ?>">
		<input type="hidden" name="order" value="<?php echo get_step_menu_order( $step_id ); ?>" />
		Require
		<input class="required-duration" type="text" size="3" maxlength="3" value="<?php echo get_post_meta( $step_id, '_dma_checkin_count', true ); ?>" placeholder="1">
		<!-- <select class="select-measurement">
			<option value="check-ins" <?php selected( $measurement, 'check-ins' ); ?>>check-ins</option>
			<option value="minutes" <?php selected( $measurement, 'minutes' ); ?>>minutes</option>
		</select> -->
		<input type="hidden" name="measurement" class="select-measurement" value="check-ins" />
		&nbsp;check-in(s) of&nbsp;
		<select class="select-requirement-type" data-step-id="<?php echo $step_id; ?>">
			<option value="any-activity" <?php selected( $requirements['type'], 'any-activity') ?>>Any Activity</option>
			<option value="activity" <?php selected( $requirements['type'], 'activity') ?>>Specific Activity</option>
			<option value="activity-type" <?php selected( $requirements['type'], 'activity-type') ?>>Activity Type</option>
			<option value="activity-category" <?php selected( $requirements['type'], 'activity-category') ?>>Activity Category</option>
			<option value="any-event" <?php selected( $requirements['type'], 'any-event') ?>>Any Event</option>
			<option value="event" <?php selected( $requirements['type'], 'event') ?>>Specific Event</option>
			<option value="event-type" <?php selected( $requirements['type'], 'event-type') ?>>Event Type</option>
			<option value="event-category" <?php selected( $requirements['type'], 'event-category') ?>>Event Category</option>
			<option value="repeat" <?php selected( $requirements['type'], 'repeat') ?>>Repeat prior step</option>
		</select>
		<select class="select-activity select-activity-<?php echo $step_id; ?>" <?php if ( $requirements['type'] != 'activity' ) echo 'style="display: none"'; ?>>
			<option value=""></option>
			<?php
				$activities = get_posts(
					array(
						'post_type' => 'activity',
						'posts_per_page' => - 1,
						'orderby' => 'title',
						'order' => 'ASC',
				));
				foreach ( $activities as $activity ) {
					$dom_args = array();
					$dom_args['value'] = $activity->ID;
					$dom_args['inner'] = $activity->post_title;
					if ( $requirements['type'] == 'activity'
						&& $requirements['id'] == $activity->ID )
						$dom_args['selected'] = 'selected';
					badgeos_step_dom_element( 'option', $dom_args );
				} ?>
		</select>
		<select class="select-activity-type select-activity-type-<?php echo $step_id; ?>" <?php if ( $requirements['type'] != 'activity-type' ) echo 'style="display: none"'; ?>>
			<option value=""></option>
			<?php
				$activity_types = get_terms( 'activity-type', array( 'hide_empty' => false ) );
				foreach ( $activity_types as $activity_type ) {
					$dom_args = array();
					$dom_args['value'] = $activity_type->term_id;
					$dom_args['inner'] = $activity_type->name;
					if ( $requirements['type'] == 'activity-type'
						&& $requirements['term']->term_id == $activity_type->term_id )
						$dom_args['selected'] = 'selected';
					badgeos_step_dom_element( 'option', $dom_args );
				}
			?>
		</select>
		<select class="select-activity-category select-activity-category-<?php echo $step_id; ?>" <?php if ( $requirements['type'] != 'activity-category' ) echo 'style="display: none"'; ?>>
			<option value=""></option>
			<?php
				$fitness_types = get_terms( 'activity-category', array( 'hide_empty' => false ) );
				foreach ( $fitness_types as $fitness_type ) {
					$dom_args = array();
					$dom_args['value'] = $fitness_type->term_id;
					$dom_args['inner'] = $fitness_type->name;
					if ( $requirements['type'] == 'activity-category'
						&& $requirements['term']->term_id == $fitness_type->term_id )
						$dom_args['selected'] = 'selected';
					badgeos_step_dom_element( 'option', $dom_args );
				}
			?>
		</select>
		<select class="select-event select-event-<?php echo $step_id; ?>" <?php if ( $requirements['type'] != 'event' ) echo 'style="display: none"'; ?>>
			<option value=""></option>
			<?php
				$activities = get_posts(
					array(
						'post_type' => 'dma-event',
						'posts_per_page' => - 1,
						'orderby' => 'title',
						'order' => 'ASC',
				));
				foreach ( $activities as $event ) {
					$dom_args = array();
					$dom_args['value'] = $event->ID;
					$dom_args['inner'] = $event->post_title;
					if ( $requirements['type'] == 'event'
						&& $requirements['id'] == $event->ID )
						$dom_args['selected'] = 'selected';
					badgeos_step_dom_element( 'option', $dom_args );
				} ?>
		</select>
		<select class="select-event-type select-event-type-<?php echo $step_id; ?>" <?php if ( $requirements['type'] != 'event-type' ) echo 'style="display: none"'; ?>>
			<option value=""></option>
			<?php
				$event_types = get_terms( 'event-type', array( 'hide_empty' => false ) );
				foreach ( $event_types as $event_type ) {
					$dom_args = array();
					$dom_args['value'] = $event_type->term_id;
					$dom_args['inner'] = $event_type->name;
					if ( $requirements['type'] == 'event-type'
						&& $requirements['term']->term_id == $event_type->term_id )
						$dom_args['selected'] = 'selected';
					badgeos_step_dom_element( 'option', $dom_args );
				}
			?>
		</select>
		<select class="select-event-category select-event-category-<?php echo $step_id; ?>" <?php if ( $requirements['type'] != 'event-category' ) echo 'style="display: none"'; ?>>
			<option value=""></option>
			<?php
				$event_categories = get_terms( 'event-category', array( 'hide_empty' => false ) );
				foreach ( $event_categories as $event_category ) {
					$dom_args = array();
					$dom_args['value'] = $event_category->term_id;
					$dom_args['inner'] = $event_category->name;
					if ( $requirements['type'] == 'event-category'
						&& $requirements['term']->term_id == $event_category->term_id )
						$dom_args['selected'] = 'selected';
					badgeos_step_dom_element( 'option', $dom_args );
				}
			?>
		</select>
		<label for="step-<?php echo $step_id; ?>-title">Label:</label> <input type="text" name="step-title" id="step-<?php echo $step_id; ?>-title" class="title" value="<?php echo get_the_title( $step_id ); ?>" />
		<!-- <label for="first_time_only_<?php echo $step_id; ?>"><input type="checkbox" name="first_time_only" id="first_time_only_<?php echo $step_id; ?>" class="first-time-only" data-step-id="<?php echo $step_id; ?>" value="true" <?php checked( $first_time_only ); ?>/> First Time Only <?php echo $first_time_only; ?></label> -->
		<span class="spinner spinner-step-<?php echo $step_id;?>"></span>
		<a href="javascript: badgeos_delete_step( <?php echo $step_id; ?> );">Delete</a>
	</li>
	<?php
}

/**
 * Helper function for comparing our step sort order (used in uasort() in badgeos_create_steps_meta_box())
 * @param  integer $a The order number of our given step
 * @param  integer $b The order number of the step we're comparing against
 * @return integer    0 if the order matches, -1 if it's lower, 1 if it's higher
 */
function badgeos_compare_step_order( $step1, $step2 ) {
	if ( $step1->order == $step2->order ) return 0;
	return ( $step1->order < $step2->order ) ? -1 : 1;
}

/**
 * Get all the requirements of a given step
 *
 * @since  1.0
 * @param  integer $step_id The given step's post ID
 * @return array|bool       An array of all the step requirements if it has any, false if not
 */
function badgeos_get_step_requirements( $step_id ) {

	// If the step requires a specific activity type...
	$activity_type_terms = wp_get_object_terms( $step_id, 'activity-type' );
	if ( ! empty( $activity_type_terms ) ) {
		foreach ( $activity_type_terms as $term ) {
			return array( 'type' => 'activity-type', 'term' => $term );
		}
	}

	// If the step requires a specific activity category...
	$event_category_terms = wp_get_object_terms( $step_id, 'activity-category' );
	if ( ! empty( $event_category_terms ) ) {
		foreach ( $event_category_terms as $term ) {
			return array( 'type' => 'activity-category', 'term' => $term );
		}
	}

	// If the step requires a specific activity
	$connected_activities = get_posts(
		array(
			'post_type' => 'activity',
			'connected_type' => 'activity-to-step',
			'connected_items' => $step_id,
			'connected_direction' => 'to',
			'nopaging' => true,
			'supress_filters' => false,
		)
	);
	if ( ! empty( $connected_activities ) )
		return array( 'type' => 'activity', 'id' => $connected_activities[0]->ID );

	// If the step requires a specific event type...
	$event_type_terms = wp_get_object_terms( $step_id, 'event-type' );
	if ( ! empty( $event_type_terms ) ) {
		foreach ( $event_type_terms as $term ) {
			return array( 'type' => 'event-type', 'term' => $term );
		}
	}

	// If the step requires a specific event category...
	$event_category_terms = wp_get_object_terms( $step_id, 'event-category' );
	if ( ! empty( $event_category_terms ) ) {
		foreach ( $event_category_terms as $term ) {
			return array( 'type' => 'event-category', 'term' => $term );
		}
	}

	// If the step requires a specific event
	$connected_events = get_posts(
		array(
			'post_type' => 'dma-event',
			'connected_type' => 'dma-event-to-step',
			'connected_items' => $step_id,
			'connected_direction' => 'to',
			'nopaging' => true,
			'supress_filters' => false,
		)
	);
	if ( ! empty( $connected_events ) )
		return array( 'type' => 'event', 'id' => $connected_events[0]->ID );


	// If the step has a special earning option
	$special_step_earning_option_terms = wp_get_object_terms( $step_id, 'special-step-earning-option' );
	if ( ! empty( $special_step_earning_option_terms ) ) {
		foreach ( $special_step_earning_option_terms as $term ) {
			return array( 'type' => $term->slug, 'term' => $term );
		}
	}

	return false;
}

/**
 * Get the the ID of a post connected to a given child post ID
 *
 * @since  1.0
 * @param  integer $child_id The given child's post ID
 * @return integer           The resulting connected post ID
 */
function badgeos_get_p2p_id_from_child_id( $child_id ) {
	global $wpdb;
	$p2p_id = $wpdb->get_var( $wpdb->prepare( "SELECT p2p_id FROM $wpdb->p2p WHERE p2p_from = %d ", $child_id ) );
	return $p2p_id;
}

/**
 * Get the sort order for a given step
 *
 * @since  1.0
 * @param  integer $step_id The given step's post ID
 * @return integer          The step's sort order
 */
function get_step_menu_order( $step_id ) {
	global $wpdb;
	$p2p_id = $wpdb->get_var( $wpdb->prepare( "SELECT p2p_id FROM $wpdb->p2p WHERE p2p_from = %d", $step_id ) );
	$menu_order = $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM $wpdb->p2pmeta WHERE p2p_id=%d AND meta_key='order'", $p2p_id ) );
	if ( ! $menu_order || $menu_order == 'NaN' ) $menu_order = '0';
	return $menu_order;
}

/**
 * Get a given step's measurement type
 *
 * @since  1.0
 * @param  integer $step_id The given step's post ID
 * @return string           The step's measurement type (either 'minutes' or 'checkin')
 */
function badgeos_get_step_measurement( $step_id ) {
	$terms = wp_get_object_terms( $step_id, 'step-measurement' );
	foreach ( $terms as $term )
		return $term->slug;
}

/**
 * Generate HTML markup for a DOM element
 *
 * @since  1.0
 * @param  string $tag    A valid HTML element
 * @param  array  $attrs  Attributes for our HTML element
 * @return void
 */
function badgeos_step_dom_element( $tag, $attrs ) {

	// Concatenate our output
	$output = '<' . $tag . ' ';
	foreach ( $attrs as $attr => $val ) {
		if ( $attr == 'inner' ) continue;
		$output .= $attr . '="' . $val . '" ';
	}
	$output .= '>';
	$output .= $attrs['inner'];
	$output .= '</' . $tag . '>';

	echo $output;
}
