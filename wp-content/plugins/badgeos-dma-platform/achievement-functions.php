<?php

/**
 * @TODO: [dma_save_awarded_by_data_on_badge_earning description]
 *
 * @since  1.0
 * @param  int    $log_post_id [description]
 * @param  int    $post_id     [description]
 * @param  int    $user_id     [description]
 * @param  string $action      [description]
 * @return [type]              [description]
 */
add_action( 'badgestack_create_log_entry', 'dma_save_awarded_by_data_on_badge_earning', 10, 4 );
function dma_save_awarded_by_data_on_badge_earning( $log_post_id, $post_id, $user_id, $action ) {
	$achievement = get_post( $post_id );
	if ( $action != 'unlocked' || $achievement->post_type != 'badge' )
		return;

	return add_post_meta( $log_post_id, '_dma_badge_awarded_by', dma_get_current_location_id() );
}

/**
 * Add extra meta data to earned achievement objects for a specific user.
 * Adds location_earned, reflecting the location_id of the Y, and points_earned.
 *
 * @since  1.0
 * @param  object $achivement_object [description]
 * @param  int    $user_id           [description]
 * @return object                    [description]
 */
add_filter( 'achievement_object', 'dma_awarded_achievement_object_details', 10, 2 );
function dma_awarded_achievement_object_details( $achievement_object, $user_id ) {

	// Add our points earned
	$achievement_object->points_earned = get_post_meta( $achievement_object->ID, '_dma_points', true );

	// Add our location earned if we're in a specific location, otherwise null
	$achievement_object->location_earned = ( $location_id = dma_get_current_location_id() ) ? $location_id : NULL;

	// Return our updated object
	return $achievement_object;
}

/**
 * Store any earned achievements in a global to pass for redirection later.
 *
 * @since  1.0
 * @param  int $user_id        [description]
 * @param  int $achievement_id [description]
 * @return void
 */
add_action( 'badgestack_award_achievement', 'dma_capture_earned_achievements', 10, 2 );
function dma_capture_earned_achievements( $user_id, $achievement_id ) {

	// If we have an active transient, grab it. If not, setup an empty array
	$achivements = get_transient( 'dma_earned_achievements' );
	if ( ! $achievements ) $achievements = array();

	// Add our achievement to the array
	$achievements[] = $achievement_id;

	// Store a transient for 5 seconds
	set_transient( 'dma_earned_achievements', $achievements, 5 );

}
