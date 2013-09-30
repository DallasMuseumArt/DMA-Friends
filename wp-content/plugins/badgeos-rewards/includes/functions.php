<?php
/**
* Retrieve all Rewards available for a specific user
*
* @param  string $user_id  ID of the user.  Defaults to logged in user if not passed
* @return array            All Reward posts found
*/
function dma_get_available_rewards_for_user( $user_id = null ) {

	$user_id = dma_get_user_id( $user_id );

	// Attempt to pull our rewards from cache
	$rewards_query = maybe_unserialize( get_transient( 'dma_available_rewards' ) );

	// If we don't have a cached query, run a fresh one
	if ( empty( $rewards_query ) ) {

		// Show rewards up to 4,000 more points than the user has already
		// NOTE: As of 1/17 we show ALL rewards, regardless of point cost
		// $user_points = badgeos_get_users_points( $user_id );
		// $user_points = absint( $user_points ) + 4000;

		// Query all published, non-hidden rewards
		// NOTE: As of 1/17 we no longer restrict based on user's points
		$args = array(
			'post_type'      =>	'badgeos-rewards',
			'post_status'    =>	'publish',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'no_found_rows'  => true,
			'meta_query'     =>	array(
				array(
					'key'     => '_dma_reward_hidden',
					'value'   => 'on',
					'compare' => 'NOT EXISTS'
				),
				// array(
				// 	'key'     => '_dma_reward_points',
				// 	'value'   => array( 0, $user_points ),
				// 	'type'    =>	'numeric',
				// 	'compare' => 'BETWEEN'
				// )
			)
		);
		$rewards_query = new WP_Query( $args );

		// Store our rewards query for a week
		set_transient( 'dma_available_rewards', $rewards_query, WEEK_IN_SECONDS );

	}

	// Double-check we're working with an array...
	// maybe_unserialize() wasn't working on dev
	if ( ! is_object( $rewards_query ) )
		$rewards_query = unserialize( $rewards_query );

	// Init our $rewards variable
	$rewards = array();
	while ( $rewards_query->have_posts() ) : $rewards_query->the_post();

		global $post;
		//verify user has prereqisite badges if any are attached to this reward
		if ( dma_rewards_user_has_prereqs( $user_id, get_the_ID() ) ) {

			$start_date = get_post_meta( get_the_ID(), '_dma_reward_start_date', true );
			$end_date = get_post_meta( get_the_ID(), '_dma_reward_end_date', true );
			$inventory = dma_get_reward_inventory( get_the_ID() );

			if (  ( strtotime( date( "Y-m-d" ) ) > strtotime( $start_date )
					&& strtotime( date( "Y-m-d" ) ) < strtotime( $end_date ) )
					&& ! empty( $start_date )
					&& ( $inventory == '' || ( isset( $inventory ) && $inventory > 0 ) ) ) {

				//reward has a date range set and today's date is within that range
				//reward also has no inventory set or the inventory level is greater than zero
				$reward->ID = get_the_ID();
				$reward->title = get_the_title();
				$reward->content = get_the_content();
				// $reward->excerpt = get_the_excerpt();
				$reward->excerpt = apply_filters( 'the_content', ( $post->post_excerpt ? $post->post_excerpt : wp_trim_words( $reward->content, 18 ) ) );
				$reward->thumbnail = get_the_post_thumbnail( get_the_ID(), 'reward' );
				$reward->points = get_post_meta( get_the_ID(), '_dma_reward_points', true );
				$reward->start_date = $start_date;
				$reward->end_date = $end_date;
				$reward->inventory = $inventory;

			}elseif ( empty( $start_date )
					&& ( $inventory == '' || ( isset( $inventory ) && $inventory > 0 ) ) ) {

				//reward has no date range set
				//reward also has no inventory set or the inventory level is greater than zero
				$reward->ID = get_the_ID();
				$reward->title = get_the_title();
				$reward->content = get_the_content();
				// $reward->excerpt = get_the_excerpt();
				$reward->excerpt = apply_filters( 'the_content', ( $post->post_excerpt ? $post->post_excerpt : wp_trim_words( $reward->content, 18 ) ) );
				$reward->thumbnail = get_the_post_thumbnail( get_the_ID(), 'reward' );
				$reward->points = get_post_meta( get_the_ID(), '_dma_reward_points', true );
				$reward->start_date = $start_date;
				$reward->end_date = $end_date;
				$reward->inventory = $inventory;

			}

			//verify $reward data exists and store in the array
			if ( isset( $reward->ID ) && $reward->ID ) {
				$rewards[] = $reward;
				$reward = null;
			}

		}

	endwhile;

	//TODO: Add transient to cache Reward data

	//sort the rewards based on points from lowest to highest
	usort( $rewards, 'dma_sort_reward_points' );

	//return array of reward IDs that user has access to
	return $rewards;

}

function dma_sort_reward_points( $a, $b ) {

    return $a->points - $b->points;

}

/**
* Returns the inventory level for a Reward
*
* @param  string $reward_id  ID of the Reward to get inventory
* @return string             Inventory level for Reward
*/
function dma_get_reward_inventory( $reward_id = 0 ) {

	$inventory = get_post_meta( absint( $reward_id ), '_dma_reward_inventory', true );

	return $inventory;

}

/**
* Returns the points for a Reward
*
* @param  string $reward_id  ID of the Reward to get inventory
* @return string             Points required to redeem the Reward
*/
function dma_get_reward_points( $reward_id = 0 ) {

	$points = get_post_meta( absint( $reward_id ), '_dma_reward_points', true );

	return $points;

}

/**
* Redeem Reward function
*
* @param  string $user_id    ID of the user redeeming the reward
* @param  string $reward_id  ID of the Reward being redeemed
* @return string             Points required to redeem the Reward
*/
function dma_redeem_reward( $user_id = null, $reward_id = null ) {

	//_badgeos_rewards
	$rewards = dma_get_user_rewards( $user_id );

	$post_object = get_post( $reward_id );

	if ( is_null( $post_object ) )
		return false;

	$reward_object = new stdClass;
	$reward_object->ID = $reward_id;
	$reward_object->post_type = $post_object->post_type;
	$reward_object->date_earned = time();

	// Add our reward to the array, and make it filterable so we can add more data elsewhere
	$rewards[] = apply_filters( 'reward_object', $reward_object, $user_id );

	// save rewards
	update_user_meta( $user_id, '_badgeos_rewards', $rewards );

	// log the Reward redemption
	badgeos_post_log_entry( $reward_id, $user_id, 'claimed-reward' );

	// grab the location printer details
	$location_printer_ip = get_post_meta( dma_get_current_location_id(), '_dma_location_printer_reward', true );

	// action hook when a Reward is redeemed
	do_action( 'dma_user_claimed_reward', absint( $user_id ), absint( $reward_id ), $location_printer_ip );

	// deduct user points for reward redemption
	$reward_points = dma_get_reward_points( $reward_id );
	badgeos_update_users_points( $user_id, - absint( $reward_points ) );

	// check and deduct reward inventory if necessary
	dma_deduct_reward_inventory( $reward_id );

}

/**
* Return users Rewards
*
* @param  string $user_id    ID of the user
* @return array              Rewards redeemed by the user
*/
function dma_get_user_rewards( $user_id = 0 ) {

	$user_id = dma_get_user_id( $user_id );

	// get existing rewards for user
	$rewards = get_user_meta( absint( $user_id ), '_badgeos_rewards', true );

	if ( ! $rewards )
		$rewards = array();

	return $rewards;

}


/**
* Deducts Reward inventory level when redeemed by a user
*
* @param  string $reward_id		ID of the reward
* @return nothing
*/
function dma_deduct_reward_inventory( $reward_id = 0 ) {

	$inventory = dma_get_reward_inventory( $reward_id );

	if ( is_numeric( $inventory ) && $inventory > 0 ) {

		//reduce inventory by 1
		$inventory = $inventory - 1;

		//save reward inventory
		update_post_meta( absint( $reward_id ), '_dma_reward_inventory', absint( $inventory ) );
	}

}

/**
* Generates claim Reward form
*
* @param  string $reward_id		ID of the reward
* @return string                HTML form to redeem this Reward
*/
function dma_claim_reward_form( $reward_id = 0 ) {

	$user_id = dma_get_user_id();

	if ( dma_can_user_afford_reward( $user_id, $reward_id ) ) {

		$form = '<form method="post" />';
		$form .= '<input type="hidden" name="user_id" value="'.absint( $user_id ).'" />';
		$form .= '<input type="hidden" name="reward_id" value="'.absint( $reward_id ).'" />';
		$form .= '<button type="submit" name="redeem_reward"/>'. __( 'Redeem', 'dma' ) .'</button>';
		$form .= '</form>';
	}else{

		$form = 'Not enough points to redeem';

	}

	return $form;

}

add_action( 'init', 'dma_process_reward_redemption' );

/**
* Process a Reward redemption
*/
function dma_process_reward_redemption() {

	if ( isset( $_POST['redeem_reward_action'] ) ) {

		$user_id = dma_get_user_id( absint( $_POST['user_id'] ) );

		$reward_id = $_POST['reward_id'];

		dma_redeem_reward( absint( $user_id ), absint( $reward_id ) );

	}

}

/**
* Checks if user has enough points to redeem a specific
*
* @param  string $user_id		ID of the user
* @param  string $reward_id		ID of the reward
* @return						True is uesr has enough points, false otherwise
*/
function dma_can_user_afford_reward( $user_id, $reward_id ) {

	$user_id = dma_get_user_id( $user_id );
	$reward_points = dma_get_reward_points( absint( $reward_id ) );
	$user_points = badgeos_get_users_points( $user_id );

	if ( is_numeric( $reward_points ) && is_numeric( $user_points ) ) {

		if ( $user_points >= $reward_points )
			return true;

	}

	return false;
}

/**
* Check if user has prerequisite Badges for a Reward
*
* @param  string $user_id		ID of the user
* @param  string $reward_id		ID of the reward
* @return						True is uesr has all prerequisites, false otherwise
*/
function dma_rewards_user_has_prereqs( $user_id = 0, $reward_id = 0 ) {

	$user_id = dma_get_user_id( $user_id );

	$args = array(
		'post_type'			=>	'badge',
		'connected_type'	=>	'badge-to-badgeos-rewards',
		'connected_items'	=>	absint( $reward_id ),
		'nopaging'			=>	true,
		'supress_filters'	=>	false
	);

	$connected = get_posts( $args );

	foreach( $connected as $post ) :
		setup_postdata( $post );

		//check if user has earned this badge
		if ( badgeos_get_user_achievements( array( 'user_id' => $user_id, 'achievement_id' => get_the_ID() ) ) ) {
			//user has earned this badge so keep processing
			continue;
		}else{
			//user does not have a prerequisite badge so return false
			return false;
		}

	endforeach;

	//user has all prerequisite badges so return true
	return true;

}

/**
 * Cache buster to delete DMA Rewards query transient
 *
 * Attached to post edit/delete/restore so rewards are always accurate
 *
 * @since 1.0.1
 * @param integer $post_id The ID of the given post
 * @param object  $post    The post object
 */
function dma_rewards_cache_buster( $post_id ) {
	if ( 'badgeos-rewards' == get_post_type( $post_id ) )
		delete_transient( 'dma_available_rewards' );
}
add_action( 'save_post', 'dma_rewards_cache_buster' );
add_action( 'trashed_post', 'dma_rewards_cache_buster' );
add_action( 'untrash_post', 'dma_rewards_cache_buster' );
