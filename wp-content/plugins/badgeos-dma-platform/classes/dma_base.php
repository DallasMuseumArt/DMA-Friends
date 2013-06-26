<?php

// Make sure our class doesn't already exist first
if ( ! class_exists('DMA_Base') ) {

/**
 * Build our base object for use in other objects
 *
 * Available Methods:
 *   ->badgestack_achievements()
 *
 * @since  1.0
 */
class DMA_Base {


	/**
	 * Fire things up (this will be overwritten by all other classes)
	 *
	 * @since  1.0
	 * @param  integer $user_id A specific User's ID
	 */
	public function __construct( $user_id = 0 ) {

		// Get the current user, if none specified
		$this->user_id 		= dma_get_user_id( $user_id );

	}

	/**
	 * Get a user's badgestack achievements
	 *
	 * @since  1.0
	 * @param  integer     $user_id         The ID of a specific user
	 * @param  integer     $single_post_id  The ID of a single post
	 * @param  string      $post_type       A specific post type
	 * @param  integer     $limit_in_days   A specific number of days to look back from the current time
	 * @param  integer     $since           A specific timestamp to use in place of $limit_in_days
	 * @return array|bool                   An array of all the achievement objects that matched our parameters, or false if none
	 */
	public function badgestack_achievements( $user_id = 0, $single_post_id = false, $post_type = false, $limit_in_days = false, $since = 0 ) {

		// If no user ID was specified, use this user ID
		$user_id = $user_id ? $user_id : $this->user_id;

		// Assume the user has earned nothing
		$earned_items = array();

		// Get the user's achievements array
		if ( $achievements = get_user_meta( $user_id, '_badgestack_achievements', true ) ) {

			// Loop through all the achievements ($achievement is a post object, and we're using $key to keep everything straight)
			foreach ( $achievements as $key => $achievement ) {

				// If we're looking only within a specific date range...
				if ( $limit_in_days || $since ) {

					// If since is set, use that
					if ( $since > 0 )
						$date = $since;
					// Otherwise, grab the timestamp for how far back we're looking
					else
						$date = strtotime( $limit_in_days . ' days ago' );

					// And include the item if it was earned on or after our specific date...
					if ( $achievement->date_earned >= $date )
						$earned_items[$key] = $achievement;

				} else {

					// Otherwise, include everything
					$earned_items[$key] = $achievement;

				}

				// If we're looking for a specific post id and it doesn't match our achievement ID...
				if ( $single_post_id && $single_post_id != $achievement->ID ) {

						// Drop it from our earned items array
						unset( $earned_items[$key] );

				}

				// If we're looking for a specific post type and it doesn't match our achievement post_type...
				if ( $post_type && $post_type != $achievement->post_type ) {

						// Drop it from our earned items array
						unset( $earned_items[$key] );

				}

			}

		}

		// If we have earned items, return the array_values (so our array keys start back at 0). Otherwise, return false
		return ( ! empty( $earned_items ) ) ? array_values( $earned_items ) : false;

	}

}

} // END class_exists check
