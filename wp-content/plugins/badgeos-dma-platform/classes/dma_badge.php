<?php

// If this class already exists, just stop here
if ( ! class_exists( 'DMA_Badge' ) ) {

/**
 * Build our Badge object
 *
 * Available Methods:
 *   ->is_user_active()
 *   ->completed_steps_count()
 *   ->steps_progress_percent()
 *   ->steps()
 *   ->step_output()
 *   ->is_bookmarked()
 *   ->details_output()
 *   ->details_badge_class()
 *   ->details_modal()
 *   ->earned_output()
 *   ->earned_modal()
 *
 * @since  1.0
 * @param  int $badge_id The ID of the badge in question
 * @param  int $user_id  	 The ID of the user in question
 */
class DMA_Badge extends DMA_Base {

	public $context = CHILD_THEME_NAME;
	/**
	 * Setup our current badge object
	 *
	 * @since  1.0
	 * @param  int $badge_id The ID of the badge in question
	 * @param  int $user_id  	 The ID of the user in question
	 */
	public function __construct( $badge = 0, $user_id = 0 ) {

		// If we weren't explicitly given an badge, try to grab the current global $post object
		if ( ! $badge ) {
			global $post;
			$badge = $post;
		}

		// Setup all our details
		$this->ID             = $badge->ID;
		$this->user_id        = dma_get_user_id( $user_id );
		$this->post           = $badge;
		$this->post_title     = $badge->post_title;
		$this->post_content   = apply_filters( 'the_content', $badge->post_content);
		$this->post_excerpt   = apply_filters( 'the_content', ( $badge->post_excerpt ? $badge->post_excerpt : wp_trim_words( $this->post_content, 25 ) ) );
		$this->thumbnail      = get_the_post_thumbnail( $this->ID, apply_filters( 'dma_badge_image_size', 'badge-large' ) );
		$this->icon           = get_post_meta( $this->ID, '_thumbnail_id', true );
		$this->points         = get_post_meta( $this->ID, '_badgeos_points', true );
		$this->earned_items   = ( $items = badgeos_get_user_achievements( array( 'user_id' => $this->user_id, 'achievement_id' => $this->ID ) ) ) ? array_reverse( $items ) : null;
		$this->times_earned   = $this->earned_items ? count( $this->earned_items ) : 0;
		$this->awarded_by     = ( $this->earned_items && isset( $this->earned_items[0]->awarded_by ) ) ? $this->earned_items[0]->awarded_by : null;
		$this->active_details = ( $this->is_user_active() ) ? badgeos_user_get_active_achievement( $this->user_id, $this->ID ) : false;
		$this->date_started   = ( is_object( $this->active_details ) ) ? $this->active_details->date_started : 0;
		$this->date_earned    = $this->earned_items ? $this->earned_items[0]->date_earned : null;
		$this->expires        = get_post_meta( $this->ID, '_badgeos_time_restriction_date_end', true );
		$this->credly_support = false;

		// If we have a custom icon set, use that as the thumbnail
		if ( $this->icon ) {
			$this->icon_attributes	= wp_get_attachment_image_src( $this->icon, 'badge-large' );
			$this->thumbnail		= '<img src="' . $this->icon_attributes[0] . '" width="' . $this->icon_attributes[1] . 'px" height="' . $this->icon_attributes[2] . 'px" />';
		}

	}

	/**
	 * Conditional to check if user is actively participating in this badge
	 *
	 * @since  1.0
	 * @return boolean True if badge is in user's active list, false if not
	 */
	public function is_user_active() {
		return ( $this->completed_steps_count() ) ? true : false;
	}

	/**
	 * Get the count for the number of steps a user has completed
	 *
	 * @since  1.0
	 * @return int The number of completed steps
	 */
	public function completed_steps_count() {

		// Assume we've completed no steps
		$completed_count = 0;

		// Loop through all of our required steps
		foreach ( $this->steps() as $step ) {

			// Find our required number checkins and our completed checkins
			// Set our total to whichever amount is smaller
			$required = absint( get_post_meta( $step->ID, '_badgeos_count', true ) );
			$checkins = dma_find_user_checkins_for_step( $this->user_id, $step->ID );
			$total    = min( count( $checkins ), $required );

			// Add our total to the completed count
			$completed_count += $total;
		}

		// Return our completed count
		return absint( $completed_count );

	}

	/**
	 * Calculate percent complete for the badge
	 *
	 * @since 1.0
	 * @return mixed Returns false if no steps exist for this badge. Returns int of completed percentage.
	 */
	public function steps_progress_percent() {

		// If we don't have any steps or any completed steps, OR the badge is completed, bail here
		if ( 0 == $this->steps_count() || 0 == $this->completed_steps_count() || $this->completed_steps_count() == $this->steps_count() )
			return false;

		// Calculate our percent based on steps and completed steps
		$percent = ( $this->completed_steps_count() / $this->steps_count() ) * 100;

		return absint( $percent );

	}

	/**
	 * Get all of the steps for a given challenge
	 *
	 * @since  1.0
	 * @return array An array of $post objects
	 */
	public function steps() {

		// Assume there are no steps
		$steps = array();

		// Grab the steps
		global $wpdb;
		$steps = $wpdb->get_results( $wpdb->prepare(
			"
			SELECT    *
			FROM      $wpdb->posts, $wpdb->p2p
			WHERE     $wpdb->posts.post_type = %s
			          AND $wpdb->p2p.p2p_from = $wpdb->posts.ID
			          AND $wpdb->p2p.p2p_to = %d
			          AND $wpdb->p2p.p2p_type = %s
			",
			'step',
			$this->ID,
			'step-to-badge'
			)
		);

		// Sort steps by their given order
		foreach ( $steps as $step ) {
			$step->order = get_step_menu_order( $step->ID );
		}
		uasort( $steps, 'badgeos_compare_step_order' );

		// Return a re-keyed steps array (an array of $post objects)
		return array_values( $steps );

	}

	/**
	 * Gets a count based on the number of checkins for each step
	 *
	 * @since  1.0
	 * @return integer Our total steps
	 */
	public function steps_count() {

		$stepscount = 0;

		foreach ( $this->steps() as $step )
			$stepscount += get_post_meta( $step->ID, '_badgeos_count', true );

		return $stepscount;
	}

	/**
	 * Generate the short output for a given activity
	 *
	 * @since  1.0
	 * @return string Our concatenated output
	 */
	public function step_output( $step_id = 0, $array_key = 0, $is_badge_complete = false ) {

		// Setup our step details
		$step_title = get_the_title( $step_id );
		$checkins = get_post_meta( $step_id, '_badgeos_count', true );

		// Setup our step progress variables
		$completed_checkins = dma_find_user_checkins_for_step( $this->user_id, $step_id );
		$total_count        = absint( get_post_meta( $step_id, '_badgeos_count', true ) );
		$completed_count    = min( count( $completed_checkins ), $total_count );
		$incomplete_count   = absint( $total_count - $completed_count );

		// Concatenate our output
		$output = '';
			$output .= '<div class="step step-' . $step_id . '">';
			$output .= '<div class="progress-wrap">';
			for ( $completed_count; $completed_count > 0; $completed_count-- ) {
				$output .= '<span class="icon-progress checked"></span>';
			}
			for ( $incomplete_count; $incomplete_count > 0; $incomplete_count-- ) {
				$output .= '<span class="icon-progress"></span>';
			}
			$output .= '</div>';
			$output .= '<h3 class="name">' . $step_title . '</h3>';
		$output .= '</div><!-- .step .step-' . $step_id . ' -->';

		// Return our output
		return $output;

	}

	/**
	 * Determines if our badge is bookmarked by the current user
	 *
	 * @since 1.0
	 * @return bool True if badge is bookmarked, false otherwise
	 */
	public function is_bookmarked() {

		// Grab our user's bookmarks
		$user_bookmarks = maybe_unserialize( get_user_meta( $this->user_id, '_dma_bookmarked_items', true ) );

		// If we actually have bookmarks, and this badge is in our bookmarks, return true
		if ( ! empty( $user_bookmarks ) && in_array( $this->ID, $user_bookmarks ) )
			return true;

		// Otherwise, the user has no bookmarks or hasn't bookmarked this badge
		return false;
	}

	/**
	 * Gets thumbnail (if it has one) markup for our current badge
	 *
	 * @since 1.0
	 * @return string Markup for thumbail, empty otherwise
	 */
	public function thumbnail() {
		return $this->thumbnail ? '<div class="thumb">' . $this->thumbnail . '</div>' : '';
	}

	/**
	 * Generate the badge output
	 *
	 * @since  1.0
	 * @return string Our concatenated output
	 */
	public function details_output( $show_details_button = true, $modal_id_suffix = null ) {

		// Concatenate our output
		$output = '';

		$output .= '<a href="#pop-' . $this->ID . $modal_id_suffix . '" ' . $this->details_badge_class( 'pop' ) . ' data-percentcomplete="' . $this->steps_progress_percent() . '">';
			// If this badge has an end date, add markup for limited banner
			if ( !empty( $this->expires ) )
				$output .= '<span class="limited">Limited Edition</span>';
			// If this badge is in progress, add progress indicator markup
			if ( $this->steps_progress_percent() )
				$output .= '<div class="progress-wrap"><div class="progress-percent" style="width:' . $this->steps_progress_percent()  . '%;"></div></div>';
			$output .= '<div class="details">';
				$output .= $this->thumbnail();
				$output .= '<h4 class="title">' . $this->post_title . '</h4>';
				$output .= '<div class="points">';
					$output .= '<span>' . ( $this->points ? $this->points : '0' ) . '</span> ' . __( 'Points', 'dma' ) . ' / ';
					$output .= '<span>' . ( $this->steps_count() ? $this->steps_count() : '0' ) . '</span> ' . __( 'Steps', 'dma' );
				$output .= '</div><!-- .points -->';
				$output .= '<div class="badge-description">' . $this->post_excerpt . '</div>';
			$output .= '</div><!-- .details -->';
		$output .= '</a><!-- .badge-' . $this->ID . ' .badge -->';

		// Return our output
		return $output;

	}

	/**
	 * Build our string of css classes
	 *
	 * @since 1.0
	 * @return string Our concatenated output
	 */
	public function details_badge_class( $classes = '' ) {

		//concatenate our output
		$output = '';

		$output .= 'class="';
		$output .= 'badge badge-' . $this->ID . ' object-' . $this->ID . ' ';
		// Add .is-active if the badge is active
		if ( $this->is_user_active() )
			$output .= 'is-active ';
		// Add .has-points if the badge has points
		if ( !empty( $this->points ) )
			$output .= 'has-points ';
		if ( $this->is_bookmarked() )
			$output .= 'bookmarked ';
		if ( !empty( $this->expires ) )
			$output .= 'limited ';
		if ( $classes )
			$output .= $classes;
		$output .= '"';

		return $output;

	}

	/**
	 * Generate our badge details modal
	 *
	 * @since  1.0
	 * @return string Our concatenated output
	 */
	public function details_modal( $modal_id_suffix = null ) {

		// Concatenate our output
		$output = '';

		$output .= '<div id="pop-' . $this->ID . $modal_id_suffix . '" ' . $this->details_badge_class( 'hidden popup' ) . '" data-popheight="auto">';
			// If this badge has an end date, add markup for limited banner
			if ( !empty( $this->expires ) )
				$output .= '<span class="limited">Limited Edition</span>';

			$output .= '<div class="modal-left">';
				$output .= $this->thumbnail();
				$output .= '<p class="p2">' . sprintf( __( 'Earn %s points', 'dma' ), '<b>'. ( $this->points ? $this->points : '0' ) .'</b>' ) . '</p>';
			$output .= '</div><!-- .modal-left -->';

			$output .= '<div class="modal-right">';
				$output .= '<h1>' . $this->post_title . '</h1>';
				$output .= '<div class="description">' . $this->post_content . '</div>';

				// If this badge has an end date, add relevant markup for expires text
				if ( !empty( $this->expires ) ) {
					$output .= '<p class="expires">';
						$output .= '<span class="warning icon-attention"></span>';
						$output .= __( 'Available through', 'dma' );
						$output .= '<span class="date"> ' . date_i18n( 'F j, Y' ,strtotime( $this->expires ) ) . '</span>';
					$output .= '</p>';
				}

				// steps output
				$output .= '<div class="steps">';
					$output .= '<p>' . sprintf( _n( '%d Step', '%d Steps', $this->steps_count(), 'dma' ), $this->steps_count() ) . '</p>';
					foreach ( $this->steps() as $array_key => $step ) {
						$output .= $this->step_output( $step->ID, $array_key );
					}
				$output .= '</div>';

				// Bookmark form & button
				$output .= dma_create_bookmark_form( $this->user_id, $this->ID, $this->is_bookmarked() );

				$output .= '<a class="button secondary close-popup" href="#">'. __( 'Close', 'dma' ) .'</a>';

			$output .= '</div><!-- .modal-right -->';
		$output .= '</div><!-- .badge-' . $this->ID . ' .badge -->';

		// Return our output
		return $output;

	}

	/**
	 * Generate the short output for an earned badge
	 *
	 * @since  1.0
	 * @return string Our concatenated output
	 */
	public function earned_output() {

		// Concatenate our output
		$output = '';

		$output .= '<a href="#badge-' . $this->ID . '-pop" class="pop badge badge-' . $this->ID . ' object-' . $this->ID . ' earned">';
			$output .= '<div class="details">';
				$output .= $this->thumbnail();
				if ( $this->times_earned > 1 )
					$output .= '<span class="times-earned">' . $this->times_earned . '</span>';
				$output .= '<h4 class="title">' . $this->post_title . '</h4>';
				$output .= '<p class="last-earned">'. human_time_diff( $this->date_earned) . ' ago</p>';
			$output .= '</div><!-- .details -->';
		$output .= '</a><!-- .badge-' . $this->ID . ' .badge -->';

		// Return our output
		return $output;

	}

	/**
	 * Generate the details modal for an earned badge
	 *
	 * @since  1.0
	 * @return string Our concatenated output
	 */
	public function earned_modal() {

		// Concatenate our output
		$output = '';
		$output .= '<div id="badge-' . $this->ID . '-pop" class="hidden popup badge badge-' . $this->ID . ' object-' . $this->ID . ' earned">';
			// If this badge has an end date, add markup for limited banner
			if ( !empty( $this->expires ) )
				$output .= '<span class="limited">Limited Edition</span>';
			if ( $this->context == 'DMA' )
				$output .= $this->thumbnail();
			$output .= '<div class="modal-left">';
				if ( $this->context == 'DMA Portal' )
					$output .= $this->thumbnail();
				$output .= '<p>' . __( 'You earned:', 'dma' ) . '<b> ' . ( $this->points ? $this->points : '0' ) . ' </b>' . __( 'points', 'dma' ) . '</p>';
				$output .= '<p>' . __( 'You\'ve earned this badge:', 'dma' ) . ' <span class="times-earned">' . $this->times_earned . '</span></p>';
			$output .= '</div><!-- .modal-left -->';
			$output .= '<div class="modal-right">';
				$output .= '<h1>' . $this->post_title . '</h1>';
				$output .= '<div class="description">' . $this->post_content . '</div>';

				// steps output
				$output .= '<div class="steps">';
					$output .='<p>' . $this->steps_count() . ' ' . __( 'Steps', 'dma' ) . '</p>';
					foreach ( $this->steps() as $array_key => $step ) {
						$output .= $this->step_output( $step->ID, $array_key, true );
					}
				$output .= '</div>';
				// send to credly button
				$output .= $this->send_credly();
				// Close button
				$output .= '<a class="button secondary close-popup" href="#">'. __( 'Close', 'dma' ) .'</a>';
			$output .= '</div><!-- .modal-right -->';
		$output .= '</div><!-- .badge-' . $this->ID . ' .badge -->';

		// Return our output
		return $output;

	}

	/**
	 * Conditionally displays a "send to credly" button
	 *
	 * @since  1.0
	 * @return string Our concatenated output
	 */
	public function send_credly() {
		$button = '';

		return '';

		// Do not show if they're sending automatically
		if ( 'true' === $GLOBALS['badgeos_credly']->user_enabled )
			return $button;

		// Do not show if the badge isn't giveable
		if ( 'true' != get_post_meta( $this->ID, '_badgeos_credly_is_giveable', true ) )
			return $button;

		$button .= '<div class="send-credly">';
			$button .= '<a class="button credly" href="#'. $this->ID .'">'. __( 'Send Badge to Credly', 'dma' ) .'</a>';
			$button .= dma_spinner_notification( 'Sent' );
		$button .= '</div><!-- .send-credly -->';

		return $button;
	}

}

} // END class_exists check

/**
 * hook in our credly ajax function
 */
function dma_credly_badge_send() {

	if ( ! isset( $_REQUEST['badge_id'] ) ) {
		echo json_encode( 'Sorry, nothing found.' );
		die();
	}

	$send_to_credly = $GLOBALS['badgeos_credly']->post_credly_user_badge( $_REQUEST['badge_id'] );

	if ( $send_to_credly ) {

		echo json_encode( 'Success!' );
		die();

	} else {

		echo json_encode( 'Sorry, Send to Credly Failed.' );
		die();

	}
}
add_action( 'wp_ajax_credly_badge_send_handler', 'dma_credly_badge_send' );
add_action( 'wp_ajax_nopriv_credly_badge_send_handler', 'dma_credly_badge_send' );
