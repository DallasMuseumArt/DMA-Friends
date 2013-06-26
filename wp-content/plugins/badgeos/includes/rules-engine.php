<?php
/**
 * BadgeStack Rules Engine
 *
 * @package BadgeStack
 *
 *
 */

/**
 * Check if user should earn an achievement, and award it if so.
 *
 * Not in use as of yet, but may come in handy later.
 *
 */
function badgestack_check_and_maybe_award_achievement_to_user( $achievement_id, $user_id = 0 ) {
	if ( $user_id == 0 )
		$user_id = wp_get_current_user()->ID;

	if ( badgestack_check_achievement_completion_for_user( $achievement_id, $user_id ) )
		badgestack_award_achievement_to_user( $achievement_id, $user_id );
}

/**
 * Check if user should earn an achievement.
 *
 *
 *
 */
function badgestack_check_achievement_completion_for_user( $achievement_id, $user_id = 0 ) {
	if ( $user_id == 0 )
		$user_id = wp_get_current_user()->ID;

	$return = true;

	// if the user already has the achievement, return true
	if ( badgestack_check_if_user_has_achievement( $achievement_id, $user_id ) )
		$return = true;

	// get all required achievements linked to achievement
	$required_achievements = badgestack_get_required_achievements_for_achievement( $achievement_id );

	// check if user has completed all required achievements for this achievement
	if ( ! empty( $required_achievements ) ) {
		foreach ( $required_achievements as $achievement ) {
			if ( ! badgestack_check_if_user_has_achievement( $achievement->ID, $user_id ) &&
				! badgestack_check_achievement_completion_for_user( $achievement->ID, $user_id ) )
				$return = false;
				// return false; // if one requirement fails the test, bail, fail.
		}
	}

	// made it, run through filter for custom achievement requirements
	return apply_filters( 'user_deserves_achievement', $return, $user_id, $achievement_id );
}

/**
 * Award an achievement to a user
 *
 *
 *
 */
function badgestack_award_achievement_to_user( $achievement_id, $user_id = 0 ) {
	if ( $user_id == 0 )
		$user_id = wp_get_current_user()->ID;

	// if the user already has the achievement, bail.
	// commented out to allow for earning  multiple challenge badges in YMCA
	// if ( badgestack_check_if_user_has_achievement( $achievement_id, $user_id ) )
		// return;

	$achievements = badgestack_get_user_achievements( $user_id );

	$post_object = get_post( $achievement_id );

	if ( is_null( $post_object ) )
		return false;

	$achievement_object = new stdClass;
	$achievement_object->ID = $achievement_id;
	$achievement_object->post_type = $post_object->post_type;
	$achievement_object->date_earned = time();
	// Add our achievement to the array, and make it filterable so we can add more data elsewhere
	$achievements[] = apply_filters( 'achievement_object', $achievement_object, $user_id );

	// save achievements
	update_user_meta( $user_id, '_badgestack_achievements', $achievements );

	// log entry
	// TODO maybe nix the usage of this for the MVP platform
	badgestack_post_log_entry( $achievement_id, $user_id );

	//hook for adding any actions when awarded
	do_action( 'badgestack_award_achievement', $user_id, $achievement_id );

	// Get achievements that can be earned from completing this achievement
	$related_achievements = badgestack_get_related_achievements( $achievement_id );

	foreach ( $related_achievements as $achievement )
		badgestack_check_and_maybe_award_achievement_to_user( $achievement->ID, $user_id );

	// hook for unlocking any achievement of this achievement type
	$post_type = get_post_type( $achievement_id );
	do_action( 'badgestack_unlock_'.$post_type );

	// hook for unlocking all achievements of this achievement type
	// get all post_ids for this type
	$all_achievements_of_type = badgestack_get_achievements( array( 'post_type' => $post_type ) );
	if ( $all_achievements_of_type ) {
		$all_per_type = true;
		//loop type ids
		foreach ( $all_achievements_of_type as $achievement ) {
			$found_achievement = false;
			foreach ( $achievements as $single_achievement ) {
				if ( $single_achievement->ID == $achievement->ID ) {
					$found_achievement = true;
					break;
				}
			}
			if ( ! $found_achievement ) {
				$all_per_type = false;
				break;
			}
		}
		//if completed all posts for this type then trigger hook
		if ( $all_per_type ) {
			do_action( 'badgestack_unlock_'.$post_type.'_all' );
		}
	}
}

/**
 * Revoke an achievement from a user
 *
 *
 *
 */
function badgestack_revoke_achievement_from_user( $achievement_id, $user_id = 0 ) {
	if ( $user_id == 0 )
		$user_id = wp_get_current_user()->ID;

	// if the user doesn't have the achievement, bail.
	if ( ! badgestack_check_if_user_has_achievement( $achievement_id, $user_id ) )
		return;

	$achievements = badgestack_get_user_achievements( $user_id );

	foreach ( $achievements as $key => $achievement ) {
		if ( $achievement->ID == $achievement_id )
			unset( $achievements[$key] );
	}

	//resort array
	$achievements = array_values( $achievements );

	// save achievements
	update_user_meta( $user_id, '_badgestack_achievements', $achievements );

	//hook for revoking achievement
	do_action( 'badgestack_after_revoke_achievement_action', $user_id, $achievement_id );

}

/**
 * Check if user has a specific achievement.
 *
 *
 *
 */
function badgestack_check_if_user_has_achievement( $achievement_id, $user_id = 0, $since = 0 ) {
	if ( $user_id == 0 )
		$user_id = wp_get_current_user()->ID;

	$achievements = badgestack_get_user_achievements( $user_id );

	foreach ( $achievements as $achievement ) {
		if ( $since > $achievement->date_earned )
			continue;
		if ( $achievement->ID == $achievement_id )
			return true;
	}
	return false;
}

/**
 * Triggers Array and add_action setup
 *
 *
 *
 */
add_action( 'init', 'badgestack_load_activity_triggers' );

function badgestack_load_activity_triggers() {
	global $badgestack;
	//get WordPress Hooks
	$trigger_hooks = array(
		array(
			'trigger_hook' => 'wp_login',
			'trigger_description' => 'User logs into site' ),
		array(
			'trigger_hook' => 'comment_post',
			'trigger_description' => 'User comments on a post' ),
	);

	// get badgestack achievement type hooks
	foreach ( badgestack_get_achievement_types_slugs() as $achievement_type_slug ) {
		$obj = get_post_type_object( $achievement_type_slug );

		// make sure we have an object
		if ( ! is_object( $obj ) )
			continue;

		//hooks for unlocking any post of a type
		$achievement_type_hooks[] = array(
			'trigger_hook' => 'badgestack_unlock_'.$achievement_type_slug,
			'trigger_description' => __( 'User', 'badgestack' ).' '.__( 'unlocks', 'badgestack' ).' a '.$obj->labels->singular_name
		);

		//hooks for unlocking all posts of a type
		$achievement_type_hooks[] = array(
			'trigger_hook' => 'badgestack_unlock_'.$achievement_type_slug.'_all',
			'trigger_description' => __( 'User', 'badgestack' ).' '.__( 'unlocks', 'badgestack' ).' all '.$obj->labels->name
		);
	}

	$badgestack->activity_triggers = array_merge( $trigger_hooks, $achievement_type_hooks );

	foreach ( $badgestack->activity_triggers as $trigger )
		add_action( $trigger['trigger_hook'], 'badgestack_trigger_event', 10, 10 );
}

/**
 * Trigger an event
 *
 */
function badgestack_trigger_event( $arg ) {
	global $user_ID, $blog_id, $wpdb;
	$this_trigger = current_filter();
	// Special case: when logging in (which is an activity trigger event), global $user_ID is not
	// yet available so it must be gotten from the user login name that IS passed to this function
	if ( $this_trigger == 'wp_login' ) {
		$user_data = get_user_by( 'login', $arg );
		$user_id = $user_data->ID;
	} else {
		$user_id = $user_ID;
	}
	//Now log the event in user meta and check to see if any posts were earned as a result of this event
	$user_times = get_user_meta( $user_id, '_badgestack_' . $this_trigger . '_' . $blog_id, true );
	if ( ! $user_times ) {
		$user_times = 1;
	} else {
		$user_times += 1;
	}

	//update hook count for this user
	update_user_meta( $user_id, '_badgestack_' . $this_trigger . '_' . $blog_id, $user_times );

	//now determine if any badges are earned based on this trigger event
	$rs = $wpdb->get_results( "SELECT post_id,meta_value FROM $wpdb->postmeta WHERE meta_key = '_badgestack_trigger_".$this_trigger."'" );
	foreach ( $rs as $r ) {
		$post_id = $r->post_id;
		$post_times = $r->meta_value;
		//if user can unlock it go ahead and unlock it
		if ( $user_times >= $post_times ) {
			badgestack_award_achievement_to_user( $post_id, $user_id );
		}
	}

}

/**
 * Returns achievements that may be earned when the given achievement is earned.
 *
 * TODO this function needs renaming. doesn't exactly describe what it does.
 *
 */
function badgestack_get_related_achievements( $achievement_id ) {
	global $wpdb;

	// Select posts from the relation table that have the achievement_id in the 'from' column
	$where = $wpdb->prepare( 'WHERE p2p_from = %s', $achievement_id );
	$post_ids = $wpdb->get_col( "SELECT p2p_to FROM $wpdb->p2p $where" );

	// If no posts were found, bail.
	if ( empty( $post_ids ) )
		return array();

	// Select posts that have the IDs that we found
	$query = "SELECT * FROM $wpdb->posts WHERE ID IN (" . implode( ', ', $post_ids ) . ')';
	$posts = $wpdb->get_results( $query );

	return $posts;
}

/**
 * Returns achievements that must be earned to earn given achievement.
 *
 *
 *
 */
function badgestack_get_required_achievements_for_achievement( $achievement_id = 0 ) {
	global $wpdb;
	if ( $achievement_id == 0 ) {
		global $post;
		$achievement_id = $post->ID;
	}

	// Select posts from the relation table that have the achievement_id in the 'to' column
	$where = $wpdb->prepare( 'WHERE p2p_to = %s AND p2pmeta.meta_key = %s', $achievement_id, 'order' );
	$query = "SELECT p2p_from FROM $wpdb->p2p AS p2p LEFT JOIN $wpdb->p2pmeta AS p2pmeta ON p2p.p2p_id = p2pmeta.p2p_id $where ORDER BY p2pmeta.meta_value ASC";
	$post_ids = $wpdb->get_col( $query );

	// If no posts were found, bail.
	if ( empty( $post_ids ) )
		return array();

	// Select posts that have the IDs that we found
	$query = $wpdb->prepare(
		"SELECT * FROM $wpdb->posts as posts
		 LEFT JOIN $wpdb->p2p	 AS p2p	 ON p2p.p2p_from = posts.ID AND p2p.p2p_to = $achievement_id
		 LEFT JOIN $wpdb->p2pmeta AS p2pmeta ON p2pmeta.p2p_id = p2p.p2p_id AND p2pmeta.meta_key = 'order'
		 WHERE posts.ID IN (" . implode( ', ', $post_ids ) . ")
		 AND posts.post_status = 'publish'
		 ORDER BY p2pmeta.meta_value",
		$achievement_id
	);

	$posts = $wpdb->get_results( $query );

	return $posts;
}

/**
 * Returns achievements that the user has earned.
 *
 *
 *
 */
function badgestack_get_user_achievements( $user_id = 0 ) {
	if ( $user_id == 0 )
		$user_id = wp_get_current_user()->ID;

	// get existing achievement for user
	$achievements = get_user_meta( absint( $user_id ), '_badgestack_achievements', true );
	if ( ! $achievements )
		$achievements = array();
	return $achievements;
}
