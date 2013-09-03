<?php

/**
 * Register new step triggers for activity- and step-based requirements
 *
 * @since  2.0.0
 * @param  array $triggers The current step triggers
 * @return array           The updated step triggers
 */
function dma_steps_ui_activity_triggers( $triggers ) {

	// Setup custom triggers
	$dma_triggers = array(
		// Activities
		'any-activity'      => __( 'Any Activity', 'dma-platform' ),
		'activity'          => __( 'Specific Activity', 'dma-platform' ),
		'activity-type'     => __( 'Activity Type', 'dma-platform' ),
		'activity-category' => __( 'Activity Category', 'dma-platform' ),

		// Events
		'any-event'         => __( 'Any Event', 'dma-platform' ),
		'event'             => __( 'Specific Event', 'dma-platform' ),
		'event-type'        => __( 'Event Type', 'dma-platform' ),
		'event-category'    => __( 'Event Category', 'dma-platform' ),
	);

	// Merge our custom triggers with the default triggers
	return array_merge( $dma_triggers, $triggers );
}
add_filter( 'badgeos_activity_triggers', 'dma_steps_ui_activity_triggers' );

/**
 * Add select options to Steps UI for activity-based requirements
 *
 * @since 2.0.0
 * @param integer $step_id        The step's post ID
 * @param integer $achievement_id The step's parent post ID
 */
function dma_badgeos_setps_ui_activity_select( $step_id, $achievement_id ) {
	$requirements = badgeos_get_step_requirements( $step_id );
	?>
	<select class="select-activity select-activity-<?php echo $step_id; ?>" <?php if ( $requirements['trigger_type'] != 'activity' ) echo 'style="display: none"'; ?>>
		<option value=""></option>
		<?php
			$activities = get_posts( array(
					'post_type'      => 'activity',
					'posts_per_page' => -1,
					'orderby'        => 'title',
					'order'          => 'ASC',
			) );
			foreach ( $activities as $activity ) {
				echo '<option value="' . $activity->ID . '" ' . selected( $requirements['activity_id'], $activity->ID, false ) . '>' . $activity->post_title . '</option>';
			}
		?>
	</select>
	<select class="select-activity-type select-activity-type-<?php echo $step_id; ?>" <?php if ( $requirements['trigger_type'] != 'activity-type' ) echo 'style="display: none"'; ?>>
		<option value=""></option>
		<?php
			$activity_types = get_terms( 'activity-type', array( 'hide_empty' => false ) );
			foreach ( $activity_types as $activity_type ) {
				echo '<option value="' . $activity_type->term_id . '" ' . selected( $requirements['activity_type'], $activity_type->term_id, false ) . '>' . $activity_type->name . '</option>';
			}
		?>
	</select>
	<select class="select-activity-category select-activity-category-<?php echo $step_id; ?>" <?php if ( $requirements['trigger_type'] != 'activity-category' ) echo 'style="display: none"'; ?>>
		<option value=""></option>
		<?php
			$activity_categories = get_terms( 'activity-category', array( 'hide_empty' => false ) );
			foreach ( $activity_categories as $activity_category ) {
				echo '<option value="' . $activity_category->term_id . '" ' . selected( $requirements['activity_category'], $activity_category->term_id, false ) . '>' . $activity_category->name . '</option>';
			}
		?>
	</select>
	<?php
}
add_action( 'badgeos_steps_ui_html_after_trigger_type', 'dma_badgeos_setps_ui_activity_select', 10, 2 );

/**
 * Add select options to Steps UI for event-based requirements
 *
 * @since 2.0.0
 * @param integer $step_id        The step's post ID
 * @param integer $achievement_id The step's parent post ID
 */
function dma_badgeos_setps_ui_event_select( $step_id, $achievement_id ) {
	$requirements = badgeos_get_step_requirements( $step_id );
	?>
	<select class="select-event select-event-<?php echo $step_id; ?>" <?php if ( $requirements['trigger_type'] != 'event' ) echo 'style="display: none"'; ?>>
		<option value=""></option>
		<?php
			$events = get_posts(
				array(
					'post_type'      => 'dma-event',
					'posts_per_page' => -1,
					'orderby'        => 'title',
					'order'          => 'ASC',
			));
			foreach ( $events as $event ) {
				echo '<option value="' . $event->ID . '" ' . selected( $requirements['event_id'], $event->ID, false ) . '>' . $event->post_title . '</option>';
			} ?>
	</select>
	<select class="select-event-type select-event-type-<?php echo $step_id; ?>" <?php if ( $requirements['trigger_type'] != 'event-type' ) echo 'style="display: none"'; ?>>
		<option value=""></option>
		<?php
			$event_types = get_terms( 'event-type', array( 'hide_empty' => false ) );
			foreach ( $event_types as $event_type ) {
				echo '<option value="' . $event_type->term_id . '" ' . selected( $requirements['event_type'], $event_type->term_id, false ) . '>' . $event_type->name . '</option>';
			}
		?>
	</select>
	<select class="select-event-category select-event-category-<?php echo $step_id; ?>" <?php if ( $requirements['trigger_type'] != 'event-category' ) echo 'style="display: none"'; ?>>
		<option value=""></option>
		<?php
			$event_categories = get_terms( 'event-category', array( 'hide_empty' => false ) );
			foreach ( $event_categories as $event_category ) {
				echo '<option value="' . $event_category->term_id . '" ' . selected( $requirements['event_category'], $event_category->term_id, false ) . '>' . $event_category->name . '</option>';
			}
		?>
	</select>
	<?php
}
add_action( 'badgeos_steps_ui_html_after_trigger_type', 'dma_badgeos_setps_ui_event_select', 10, 2 );

/**
 * Filter BadgeOS step requirements to include custom DMA requirements
 *
 * @since  2.0.0
 * @param  array   $requirements The step requirements
 * @param  integer $step_id      The step's post ID
 * @return array                 Updated step requirements
 */
function dma_steps_ui_step_requirements( $requirements, $step_id ) {

	// Setup our empty requirements
	$requirements['activity_id']       = null;
	$requirements['activity_category'] = null;
	$requirements['activity_type']     = null;
	$requirements['event_id']          = null;
	$requirements['event_type']        = null;
	$requirements['event_category']    = null;

	switch( $requirements['trigger_type'] ) {
		case 'activity-type' :
		case 'activity-category' :
		case 'event-type' :
		case 'event-category' :
			$terms = wp_get_object_terms( $step_id, $requirements['trigger_type'] );
			if ( ! empty( $terms ) ) {
				$term = array_shift( $terms );
				$requirements[str_replace( '-', '_', $requirements['trigger_type'] )] = $term->term_id;
			}
			break;
		case 'activity' :
			$connected_activities = get_posts(
				array(
					'post_type'           => 'activity',
					'connected_type'      => 'activity-to-step',
					'connected_items'     => $step_id,
					'connected_direction' => 'to',
					'nopaging'            => true,
					'supress_filters'     => false,
				)
			);
			if ( ! empty( $connected_activities ) ) {
				$connected_activity = array_shift( $connected_activities );
				$requirements['activity_id'] = $connected_activity->ID;
			}
			break;
		case 'event' :
			$connected_events = get_posts(
				array(
					'post_type'           => 'dma-event',
					'connected_type'      => 'dma-event-to-step',
					'connected_items'     => $step_id,
					'connected_direction' => 'to',
					'nopaging'            => true,
					'supress_filters'     => false,
				)
			);
			if ( ! empty( $connected_events ) ) {
				$connected_event = array_shift( $connected_events );
				$requirements['event_id'] = $connected_event->ID;
			}
			break;
		default :
			break;
	}

	// Return our requirements
	return $requirements;

}
add_filter( 'badgeos_get_step_requirements', 'dma_steps_ui_step_requirements', 10, 2 );

/**
 * Filter the handler for saving all steps
 *
 * @since  2.0.0
 * @param  string  $title     The original title for our step
 * @param  integer $step_id   The given step's post ID
 * @param  array   $step_data Our array of all available step data
 * @return string             Our potentially updated step title
 */
function dma_steps_ui_save_step( $title, $step_id, $step_data ) {

	$count = sprintf( _n( '%d time', '%d times', $step_data['required_count'] ), $step_data['required_count'] );

	switch ( $step_data['trigger_type'] ) {
		case 'any-activity' :
			$title = sprintf( __( 'Complete any activity %1$s.', 'dma-platform' ), $count );
			break;
		case 'any-event' :
			$title = sprintf( __( 'Attend any event %1$s.', 'dma-platform' ), $count );
			break;
		case 'activity' :
			p2p_create_connection( 'activity-to-step', array(
				'from' => $step_data['activity_id'],
				'to'   => $step_id,
				'meta' => array(
					'date' => current_time('mysql')
				)
			) );
			$title = sprintf(
				__( 'Complete activity "%1$s" %2$s.', 'dma-platform' ),
				get_the_title( $step_data['activity_id'] ),
				$count
			);
			break;
		case 'event' :
			p2p_create_connection( 'dma-event-to-step', array(
				'from' => $step_data['event_id'],
				'to'   => $step_id,
				'meta' => array(
					'date' => current_time('mysql')
				)
			) );
			$title = sprintf(
				__( 'Attend the "%1$s" event %2$s.', 'dma-platform' ),
				get_the_title( $step_data['event_id'] ),
				$count
			);
			break;
		case 'activity-type' :
		case 'activity-category' :
		case 'event-type' :
		case 'event-category' :

			// Connect our Term
			$taxonomy = $step_data['trigger_type'];
			$term_id  = absint( $step_data[str_replace( '-', '_', $taxonomy )] );
			$term     = get_term( $term_id, $taxonomy );
			wp_set_object_terms( $step_id, $term_id, $taxonomy );

			// Setup our title text
			$title_text = ( in_array( $taxonomy, array( 'activity-type', 'activity-category') ) )
				? __( 'Complete any "%1$s" activity %2$s.', 'dma-platform' )
				: __( 'Attend any "%1$s" event %2$s.', 'dma-platform' );

			// Pass in our data to the title text
			$title = sprintf(
				$title_text,
				$term->name,
				$count
			);
			break;
		default :
			break;
	}

	// Send back our custom title
	return $title;
}
add_filter( 'badgeos_save_step', 'dma_steps_ui_save_step', 10, 3 );

/**
 * Include custom JS for the BadgeOS Steps UI
 *
 * @since 2.0.0
 */
function dma_steps_ui_js() { ?>
	<script type="text/javascript">
	jQuery(document).ready(function($) {

		// Listen for our change to our trigger type selector
		$(document).on( 'change', '.select-trigger-type', function() {

			// Cache our trigger selector
			var $trigger_type = $(this);

			// Show only the select box for trigger type
			$trigger_type.siblings('.select-activity, .select-activity-type, .select-activity-category').hide();
			$trigger_type.siblings('.select-event, .select-event-type, .select-event-category').hide();
			$trigger_type.siblings('.select-' + $trigger_type.val()).show();

		});

		// Trigger a change so we properly show/hide our community menues
		$('.select-trigger-type').change();

		// Inject our custom step details into the update step action
		$(document).on( 'update_step_data', function( event, step_details, step ) {
			step_details.activity_id       = $('.select-activity', step).val();
			step_details.activity_type     = $('.select-activity-type', step).val();
			step_details.activity_category = $('.select-activity-category', step).val();
			step_details.event_id          = $('.select-event', step).val();
			step_details.event_type        = $('.select-event-type', step).val();
			step_details.event_category    = $('.select-event-category', step).val();
			console.log(step_details);
		});

	});
	</script>
<?php }
add_action( 'admin_footer', 'dma_steps_ui_js' );
