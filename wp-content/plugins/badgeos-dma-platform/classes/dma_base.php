<?php

// Make sure our class doesn't already exist first
if ( ! class_exists('DMA_Base') ) {

/**
 * Build our base object for use in other objects
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

}

} // END class_exists check
