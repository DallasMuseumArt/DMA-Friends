<?php

// If this class already exists, just stop here
if ( ! class_exists( 'DMA_Reward' ) ) {

/**
 * Build our Badge object
 *
 * Available Methods:
 *   ->output()
 *   ->modal()
 *
 * @since  1.0
 * @param  int $reward_id The ID of the reward in question
 * @param  int $user_id  	 The ID of the user in question
 */
class DMA_Reward {

	public $user_id;
	public $user_points;
	public $user_bookmarks;
	public $reward;
	public $can_afford;
	public $context = CHILD_THEME_NAME;

	/**
	 * Setup up some details not related to a reward
	 *
	 * @since  1.0
	 * @param  int $user_id  	 The ID of the user in question
	 */
	public function __construct( $user_id = 0 ) {

		// Setup user details
		$this->user_id = dma_get_user_id( $user_id );
		$this->user_points = badgeos_get_users_points( $this->user_id );
		// Grab our user's bookmarks
		$this->user_bookmarks = maybe_unserialize( get_user_meta( $this->user_id, '_dma_bookmarked_items', true ) );
	}

	/**
	 * Setup our current reward object
	 *
	 * @since  1.0
	 * @param  int $reward_id The ID of the reward in question
	 */
	public function init_reward( $reward ) {
		$this->reward = &$reward;

		// Setup a few reward details
		$this->can_afford = dma_can_user_afford_reward( $this->user_id , $reward->ID );
		// get reward fine print post meta
		$reward->fine_print = get_post_meta( $reward->ID, '_dma_reward_fine_print', true );

		$reward->hide = false;
		if ( isset( $_GET['filter'] ) ) {
			if (
				$_GET['filter'] == 'limited-qty' && !$this->reward->inventory
				|| $_GET['filter'] == 'limited-time' && !$this->reward->end_date
				|| $_GET['filter'] == 'bookmarked' && !$this->is_bookmarked()
			)
				$reward->hide = true;
		}

	}

	/**
	 * Generate the reward output
	 *
	 * @since  1.0
	 * @return string Our concatenated output
	 */
	public function output( $class = '' ) {
		// get passed-in class
		$class = $class ? ' '.$class : '';
		$reward = &$this->reward;

		if ( $reward->hide )
			return;
		// Concatenate our output
		$output = '
		<a href="#reward-'. $reward->ID .'" '. $this->rewards_class( 'pop reward-' . $reward->ID . ' object-' . $reward->ID . $class ) .'>
			<div class="thumb">'. $reward->thumbnail .'</div>
			<div class="details">
				<h4 class="title">'. $reward->title .'</h4>
				<div class="description">'. $reward->excerpt .'</div>';
				$output .= $this->constraints();
				if ( $this->context == 'DMA Portal' )
					$output .= '<b class="points">'. $this->x_points() .'</b>';
				$output .= '
			</div><!-- .details -->';
			$output .= $this->button();
		$output .= '
		</a><!-- .reward-'. $reward->ID .' .reward -->';

		// Return our output
		return $output;

	}

	/**
	 * Generate the reward modal output
	 *
	 * @since  1.0
	 * @return string Our concatenated output
	 */
	public function modal() {

		$reward = &$this->reward;

		if ( $reward->hide )
			return;
		// Concatenate our output
		$output = '
		<div id="reward-'. $reward->ID .'" '. $this->rewards_class( 'hidden popup reward-' . $reward->ID .' object-' . $reward->ID ) .'" data-popheight="auto">
			<div class="modal-left">';
				$output .= $reward->thumbnail ? '<span class="thumb">' . $reward->thumbnail . '</span>' : '';
				if ( $this->context != 'DMA Portal' )
					$output .= '<p class="p2">'. sprintf( __( 'Costs %s points', 'dma' ), '<b>'. number_format( $this->reward->points ) .'</b>' ) .'</p>';
			$output .= '
			</div><!-- .modal-left -->
			<div class="modal-right">
				<h1>'. $reward->title .'</h1>
				<div class="description">'. apply_filters( 'the_content', $reward->content ) .'</div>';
				$output .= $this->constraints( '<br>', 'icon-attention' );
				if ( $this->context == 'DMA Portal' )
					$output .= '<p><b class="points">'. $this->x_points() .'</b></p>';
				$output .= $reward->fine_print ? '<div class="fine-print p2">'. apply_filters( 'the_content', $reward->fine_print ) .'</div>' : '';
				$output .= $this->button( true );
				$output .= dma_create_bookmark_form( $this->user_id, $reward->ID, $this->is_bookmarked() );
				$output .= $this->cancel_button();
				$output .= '
			</div><!-- .modal-right -->';
		$output .= '
		</div><!-- .reward-' . $reward->ID . ' .reward -->';

		// Return our output
		return $output;

	}

	/**
	 * Build our string of css classes
	 *
	 * @since 1.0
	 * @return string Our concatenated output
	 */
	public function rewards_class( $classes = '' ) {

		//concatenate our output
		$output = '';

		$output .= 'class="reward ';
		if ( $this->can_afford )
			$output .= 'eligible ';
		if ( $this->reward->inventory )
			$output .= 'limited-qty ';
		if ( $this->reward->end_date )
			$output .= 'limited-time ';
		// @TODO: function to check if bookmarked
		if ( $this->is_bookmarked() )
			$output .= 'bookmarked';
		if ( $classes )
			$output .= ' '. $classes;
		$output .= '"';

		return $output;

	}

	/**
	 * Builds our reward constraints output
	 *
	 * @since 1.0
	 * @return string Our concatenated output
	 */
	public function constraints( $sep = '/', $class = '' ) {

		$reward = &$this->reward;

		//concatenate our output
		$output = '<p class="constraints">';


		$end_date = $reward->end_date ? date_i18n( __( 'M j, Y' ), strtotime( $reward->end_date ) ) : false;

		$use_by = $end_date ? '<span class="'. $class .' use-by">'. __( 'Use by', 'dma' ) .' <b>'. $end_date .'</b></span>' : false;
		$left = $reward->inventory ? '<span class="'. $class .' inventory">'. sprintf( __( 'Only %s Left', 'dma' ), '<b>'. $reward->inventory .'</b>' ) .'</span>' : false;

		if ( $use_by && $left )
			$output .= $sep ? $use_by . $sep . $left : $use_by . $left;
		elseif ( $use_by && !$left )
			$output .= $use_by;
		elseif ( !$use_by && $left )
			$output .= $left;

		$output .= '</p><!-- .constraints -->';


		return $output;

	}

	/**
	 * Builds our reward button output
	 *
	 * @since 1.0
	 * @return string Our concatenated output
	 */
	public function button( $modal = false ) {
		// Claim buttons only on iPad theme
		if ( $this->context != 'DMA' )
			return '';

		$reward = &$this->reward;

		//concatenate our output
		$output = '
		<div class="button-wrap">
		';
		// button for standard output
		if ( !$modal ) {
			$class = $this->can_afford ? ' primary' : '';
			$output .= $this->can_afford ? '<b>'. __( 'Claim for', 'dma' ) .'</b>' : '';
			$output .= '
			<span class="button'. $class .'" >'. $this->x_points() .'</span>
			';
		}
		// button for modal output
		else {
			// If we can afford it, show link
			if ( $this->can_afford ) {
				// create our link button
				$output .= '<p class="action"><a class="primary button blah" href="'. $this->confirm_link() .'">'. __( 'Claim for', 'dma' ) .' '. $this->x_points() .'</a></p>';
			}
			// otherwise show requirements left for this reward
			else {
				$output .= '<p class="action icon-lock">'. sprintf( __( 'You need %s more points.', 'dma' ), '<b>'. number_format( $reward->points - $this->user_points ) .'</b>' ) .'</p>';
			}
		}
		$output .= '
		</div><!-- .button-wrap -->
		';

		return $output;
	}

	/**
	 * DRY method for building our modals' cancel button
	 *
	 * @since 1.0
	 * @return string Our concatenated output
	 */
	public function cancel_button() {
		$text = $this->context == 'DMA Portal' ? __( 'Close', 'dma' ) : __( 'Cancel', 'dma' );

		return '<a class="button secondary close-popup cancel" href="#">'. $text .'</a>';
	}

	/**
	 * Return reward points with 'points' label appended
	 */
	public function x_points() {
		return number_format( $this->reward->points ) .' '. __( 'Points', 'dma' );
	}

	/**
	 * Determines if our badge is bookmarked by the current user
	 *
	 * @since 1.0
	 * @return bool True if badge is bookmarked, false otherwise
	 */
	public function is_bookmarked() {

		if ( isset( $this->reward->is_bookmarked ) )
			return $this->reward->is_bookmarked;

		// If we actually have bookmarks, and this badge is in our bookmarks, return true
		if ( ! empty( $this->user_bookmarks ) && in_array( $this->reward->ID, $this->user_bookmarks ) )
			$this->reward->is_bookmarked = true;
		else
			// Otherwise, the user has no bookmarks or hasn't bookmarked this badge
			$this->reward->is_bookmarked = false;

		// $this->reward->is_bookmarked = true;

		return $this->reward->is_bookmarked;
	}

	/**
	 * Builds a query var link for confirm and success dialogs
	 */
	public function confirm_link( $hash = 'confirm-redemption', $args = array() ) {

		// if we were provided a hash
		$hash = $hash ? '#'.$hash : '';

		$defaults = array(
			'points' => $this->reward->points,
			'reward' => $this->reward->title,
			'reward_id' => $this->reward->ID
		);
		$args = wp_parse_args( $args, $defaults );
		return add_query_arg( $args, site_url( '/get-rewards/'.$hash ) );
	}

	/**
	* Generates claim Reward confirm dialog and form
	*
	* @param  string $reward_id		ID of the reward
	* @return string                HTML form to redeem this Reward
	*/
	public function confirm_output() {

		$points = isset( $_GET['points'] ) ? urldecode( $_GET['points'] ) : false;
		$reward = isset( $_GET['reward'] ) ? urldecode( $_GET['reward'] ) : false;
		$reward_id = isset( $_GET['reward_id'] ) ? urldecode( $_GET['reward_id'] ) : false;
		$congrats = isset( $_GET['congrats'] ) ? true : false;

		if ( !$points || !$reward || !$reward_id )
			return;
		$newbalance = $this->user_points - $points;
		$output = '
		<div id="confirm-redemption" class="popup hidden" data-popheight="870">
			<div class="details">
			<h1 class="title">'. __( 'Confirm Redemption', 'dma' ) .'</h1>
				<div class="description">
					<p>'. sprintf( __( 'You are about to Claim %s in exchange for the reward %s.  Clicking Confirm will proceed with the reward redemption, and those points will be automatically withdrawn from your account. This step cannot be undone.', 'dma' ), '<strong>'. number_format( $points ) .' '. __( 'points', 'dma' ) .'</strong>', '<strong>'. $reward .'</strong>' ) .'</p>
					<table>
						<tr>
							<td>'. __( 'Current balance', 'dma' ) .':</td>
							<td><b>'. number_format( $this->user_points ) .'</b></td>
						</tr>
						<tr>
							<td>'. __( 'Reward', 'dma' ) .':</td>
							<td><b>-'. number_format( $points ) .'</b></td>
						</tr>
						<tr>
							<td>'. __( 'Your new balance will be', 'dma' ) .':</td>
							<td><b>'. number_format( $newbalance ) .'</b></td>
						</tr>
					</table>
					<div class="clear"></div>
				</div><!-- .description -->
			</div><!-- .details -->
			<form class="reward-confirmation-form" method="post" action="'. $this->confirm_link( 'reward-redeemed', array( 'congrats' => true ) ) .'" />
				<input type="hidden" name="user_id" value="'. absint( $this->user_id ) .'" />
				<input type="hidden" name="reward_id" value="'. $reward_id .'" />
				<input type="hidden" name="redeem_reward_action" value="true" />
				<button type="submit" name="redeem_reward" class="primary wide">'. __( 'Yes, Confirm', 'dma' ) .'</button>';
				$output .= $this->cancel_button();
				$output .= '
				<script type="text/javascript">jQuery(\'.reward-confirmation-form\').submit(function(){ jQuery(\'button[name="redeem_reward"]\').attr("disabled","disabled").html("Loading..."); });</script>
			</form>
		</div>
		';

		if ( !$congrats )
			return $output;

		// Congrats popup
		$output .= '
		<div id="reward-redeemed" class="popup" data-popheight="auto">
			<div class="details">
			<h1 class="title">'. __( 'Congratulations!', 'dma' ) .'</h1>
				<div class="description">
					<p>'. __( 'Here\'s how to use your reward:', 'dma' ) .'</p>
					<ol>
						<li>
							<h4>'. __( 'Pick up your coupon, printing below.', 'dma' ) .'</h4>
							<p></p>
						</li>
						<li>
							<h4>'. __( 'Check the "use by" date.', 'dma' ) .'</h4>
							<p></p>
						</li>
						<li>
							<h4>'. __( 'Claim at the information desk.', 'dma' ) .'</h4>
							<p></p>
						</li>
					</ol>
					<div class="clear"></div>
				</div><!-- .description -->
			</div><!-- .details -->
			<a class="button secondary user-logout" href="'. wp_logout_url( site_url() ) .'">'. __( 'Log Out', 'dma' ) .'</a>
			<a class="button secondary close-popup" href="#">'. __( 'Keep Using DMA Friends', 'dma' ) .'</a>
		</div>
		';

		return $output;

	}

}

} // END class_exists check
