<?php

// If this class already exists, just stop here
if ( ! class_exists( 'DMA_Activity' ) ) {

/**
 * Build our Activity object
 *
 * Available Methods:
 *   ->link()
 *   ->modal()
 *
 * @since  1.0
 * @param  object $activity The $post object of the activity in question
 * @param  int $user_id  	 The ID of the user in question
 */
class DMA_Activity extends DMA_Base {

	/**
	 * Setup our current activity object
	 *
	 * @since  1.0
	 * @param  object $activity The $post object of the activity in question
	 * @param  int $user_id  	 The ID of the user in question
	 */
	public function __construct( $activity = false, $user_id = 0 ) {

		// If we weren't explicitly given an activity, try to grab the current global $post object
		if ( ! $activity ) {
			global $post;
			$activity = $post;
		}

		// Setup all our details
		$this->ID			= $activity->ID;
		$this->user_id		= dma_get_user_id( $user_id );
		$this->post			= $activity;
		$this->post_title	= $activity->post_title;
		$this->post_content	= $activity->post_content;
		$this->slug			= $activity->post_name;
		$this->thumbnail	= !isset( $this->post->feat_img ) ? get_the_post_thumbnail( $this->ID, 'activity-box-icon' ) : $this->post->feat_img;
		$this->points		= get_post_meta( $this->ID, '_dma_activity_points', true );

	}

	/**
	 * Generate a link to our given activity's lightbox
	 *
	 * @since  1.0
	 * @return string Our concatenated output
	 */
	public function link( $classes = array(), $use_image = true ) {

		// Setup our classes
		$classes = empty( $classes ) ? array() : array( $classes );
		$classes[] = 'box-'. $this->slug;
		$classes[] = 'box pop';
		if ( isset( $this->post->activity_type ) ) {
			foreach ( $this->post->activity_type as $activity_type ) {
				$classes[] = 'type-'. $activity_type->slug;
			}
		}
		$classes = implode( ' ', array_unique( array_filter( $classes ) ) );

		// Setup our URL
		$url = '#pop-'. $this->slug;

		// Setup our content
		if ( $use_image && empty( $this->thumbnail ) || ! isset( $this->thumbnail ) )
			$this->thumbnail = '<div class="placeholder">X</div>';
		// Setup our content
		$content = !$use_image ? $this->post_title : $this->thumbnail . $this->post_title;

		// Setup our output
		$output = '<a class="' . $classes . '" href="' . $url . '">' . $content . '</a>';

		// Return our output
		return $output;

	}

	/**
	 * Generate the modal output for a given activity
	 *
	 * @since  1.0
	 * @return string Our concatenated output
	 */
	public function modal( $classes = '', $term ) {

		// Setup our classes
		$classes = empty( $classes ) ? array() : array( $classes );
		// $classes[] = 'popup activity-popup';
		$classes = implode( ' ', array_unique( array_filter( $classes ) ) );

		// Concatenate our output
		$output = '<div id="pop-' . $this->slug . '" class="' . $classes . '">';
			$output .= '<form method="post" action="' . dma_get_link( 'activity-logged' ) . '">';
			$output .= '<h2>' . $this->post_title . '</h2>';
			$output .= apply_filters( 'the_content', $this->post_content );
			$output .= '<input type="hidden" name="activity_id" value="' . $this->ID . '" />';
			$output .= '<input type="hidden" name="duration" value="1" />';
			$output .= '<input type="hidden" name="user_id" value="' . $this->user_id . '" />';
			$output .= '<input type="hidden" name="activity_name" value="' . $this->post_title . '" />';
			$output .= '<input type="hidden" name="fitness_type" value="' . $term->name . '" />';
			$output .= '<input type="hidden" name="icon" value="' . esc_attr( $this->thumbnail ) . '" />';
			$output .= '<input type="hidden" name="color" value="' . $term->color . '" />';
			$output .= '<button type="submit" name="submit" class="btn-arrow"><span>' . __( 'Log Activity', 'dma' ) . '</span></button>';
			$output .= '</form>';
		$output .= '</div>';

		return $output;
	}

}

} // END class_exists check
