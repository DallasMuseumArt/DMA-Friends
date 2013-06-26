<?php

// Remove stock Genesis functionality
remove_action( 'genesis_loop', 'genesis_do_loop' );
remove_all_actions( 'genesis_sidebar' );

// If there is no transient set for this logged activity, return the user to the reporting screen
$GLOBALS['dma_checkin_data'] = get_transient( 'checkin_data' );
if ( !$GLOBALS['dma_checkin_data'] ) {
	wp_redirect( site_url( '/report-activity/' ) );
	exit;
}

// Filter the body class to include custom classes for this page
add_filter( 'body_class', 'dma_activity_page_class' );
function dma_activity_page_class( $classes ) {
	$classes[] = 'activity-logged';
	return $classes;
}

// Enqueue our colorbox
add_action('wp_enqueue_scripts', 'dma_colorbox_etc_add');
function dma_colorbox_etc_add() {

	wp_deregister_style( 'mvp-colorbox' );
	wp_register_style( 'mvp-colorbox', get_stylesheet_directory_uri(). '/lib/css/mvp-colorbox.css', array( 'colorbox5' ) );

	if ( function_exists( 'wds_colorbox' ) )
		wds_colorbox(5);
	wp_enqueue_style( 'mvp-colorbox' );

}

// Output our custom page content
add_action( 'genesis_after_header', 'dma_activity_logged_page' );
function dma_activity_logged_page() {

	$data = $GLOBALS['dma_checkin_data'];
	$dma_user = $GLOBALS['dma_user'];

	// If we actually have activity data, and BuddyPress is active...
	if ( !empty( $data ) && function_exists( 'bp_activity_add' ) ) {

		// If the user hasn't earned any achievements, set it to an empty array
		if ( ! isset( $data['earned_achievements'] ) )
			$data['earned_achievements'] = array();

		echo '
		<p class="note">New activity Logged</p>
		<div class="logged-box">
			<div class="activity">
				<div class="box med brdr-' , $data['color'] , ' alignleft">' , str_replace( '\\', '', $data['icon'] ) , '</div>
				<div class="activity-details">
					<span class="type">' , $data['fitness_type'] , '</span>
					<h3 class="name">' , $data['activity_name'] , '</h3>
					<span class="duration">' , $data['duration'] , ' minutes</span>
				</div><!-- .activity-details -->
			</div><!-- .activity -->
			<table class="points-earned">
				<tr>
					<td class="number">',
						number_format( dma_activity_points_earned( $data['earned_achievements'] ) ),
					'</td>
					<td class="label">' , __( 'New Points Earned', 'mvp' ) , '</td>
				</tr>
				<tr>
					<td class="number">' , number_format( $dma_user->dma_points() ) , '</td>
					<td class="label">' , __( 'All Time Points', 'mvp' ) , '</td>
				</tr>
			</table><!-- .points-earned -->
		</div>
		<div class="hr full"></div>
		';

		delete_transient( 'dma_card_data' );
		unset( $GLOBALS['dma_checkin_data'] );
	}

	// Include badge details

	$no_badge_message = '
	<div class="badge-object no-badges-earned">
		<div class="empty-badge">
		</div><!-- .empty-badge -->
		<div class="badge-description shadow rounded">
			<div class="details">
				<h2 class="title">Want to earn more badges?</h2>
				<p>Try another activity.</p>
			</div><!-- .details -->
		</div><!-- .badge-description -->
	</div><!-- .badge-object -->
	';

	// If we've earned any badges...
	if ( ! empty( $data['earned_achievements'] ) ) {

		// Assume we've earned no badges
		$badges = array();

		// Grab the earned achievements array
		$achievements = $data['earned_achievements'];

		// Loop through each achievement
		foreach ( $achievements as $post_id ) {

			// If it's a fun badge, add it to our badges array
			if ( 'badge' == get_post_type( $post_id ) )
				$badges[] = $post_id;

		}

		// Total up our fun badges and tell the user how many they've won (or display alternate message if no badges were won)
		$count = count($badges);
		$how_many = $count == 1 ? 'badge' : 'badges';
		// check if any badges were earned and add a specific class
		$class = $count == 0 ? ' no-badges-earned' : '';
		// check if any badges were earned and add a specific message
		$message = $count != 0 ? sprintf( __( 'You earned %s new %s', 'mvp' ), $count, $how_many ) : __( 'No badges earned for this activity', 'mvp' );

		$wrap_class = 'no-carousel';

		if ( $count > 1 ) {
			$wrap_class = 'badge-carousel';
		}

		get_dma_badge_wrap_open( $wrap_class );

			echo '<p class="note' . $class . '">' . $message . '</p>';
			// check if any badges were earned and add specific content
			if ( $class != '' ) {
				echo $no_badge_message;
			}

			$badge_icons = $badge_details = array();
			// Loop through each achievement and grab their ID
			foreach( $badges as $badge_id ) {

				// Create a new badge
				$badge = new DMA_Badge( get_post( $badge_id ) );

				// Add this earned badge to an array
				$badge_icons[] = '<li id="badge-' . $badge->ID .'">' . $badge->thumbnail . '</li>';

				// Concatenate our output for the earned badge details
				$badge_detail = '<li class="badge-object" id="badge-object-' . $badge->ID .'">
					<div class="badge-description shadow rounded">
						<div class="points">
							<span class="count">';
							$badge_detail .= $badge->points ? $badge->points : '0';
							$badge_detail .= '</span> <span class="label">' . __( 'points', 'mvp' ) . '</span>
						</div><!-- .points -->
						<div class="details">
							<h2 class="title">' . $badge->post_title . '</h2>';
							$badge_detail .= wpautop( $badge->post_content );
						$badge_detail .= '</div><!-- .details -->
					</div><!-- .badge-description -->
					<a href="#badge-' . $badge->ID . '-pop" class="pop btn-arrow med" data-popwidth="680"><span>' . __( 'Badge Details', 'mvp' ) . '</span></a>';
					$badge_detail .= $badge->earned_modal();
				$badge_detail .= '</li><!-- .badge-object -->';
				// And add this badge detial to an array
				$badge_details[] = $badge_detail;
			}

			if ( !empty( $badge_icons ) && !empty( $badge_details ) ) {
				// create our badge icon list
				echo '<ul id="badges-list">';
				foreach ( $badge_icons as $badge_icon ) {
					echo $badge_icon;
				}
				echo '</ul>';

				// create our badge details list
				echo '<ul id="badge-details">';
				foreach ( $badge_details as $badge_detail ) {
					echo $badge_detail;
				}
				echo '</ul>';
			}

		// Otherwise, No badge was earned...
		} else {

			echo get_dma_badge_wrap_open();
			echo '<p class="note no-badges-earned">' . __( 'No badges earned for this activity', 'mvp' ) . '</p>';
			echo $no_badge_message;
		}

	echo '</div> <!-- .badge-wrap -->';

}

function get_dma_badge_wrap_open( $class = 'no-carousel' ) {
	echo '<div class="badge-wrap activity-logged-bottom '. $class .'">';
}

genesis();
