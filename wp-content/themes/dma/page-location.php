<?php

// Remove all the standard template features
add_filter( 'dma_left_right_nav', '__return_null' );
remove_all_actions( 'genesis_header' );

// Redirect to home if a location ID is already set
add_action( 'get_header', 'dma_redirect_to_home' );
function dma_redirect_to_home() {
	if ( dma_get_current_location_id() ) {
		wp_redirect( site_url() );
		exit;
	}

}

// Add our 'location-setup' page body class
add_filter( 'body_class', 'dma_add_login_class' );
function dma_add_login_class( $classes ) {
	$classes[] = 'location-setup';
	return $classes;
}

// Replace the genesis loop with our location selector
remove_action( 'genesis_loop', 'genesis_do_loop' );
add_action( 'genesis_loop', 'dma_user_dashboard' );
function dma_user_dashboard() { ?>
	<div class="select-location-wrap info-box" >
		<div class="dma-logo"></div>
		<form method="GET" name="select-location" class="select-location info" action="">
			<p><?php _e( 'Please select the location of this kiosk:', 'dma' ); ?></p>
			<select name="location_id" id="location_id">
				<?php
					$locations = get_posts( array(
						'posts_per_page' => -1,
						'post_status'    => 'publish',
						'no_found_rows'  => true,
						'post_type'      => 'dma-location',
						'order'          => 'ASC',
						'orderby'        => 'title'
					) );

					if ( ! empty( $locations ) ) {
						foreach ( $locations as $location ) {
							echo '<option value="' . $location->ID,'">' . $location->post_title . '</option>';
						}
					} else {
						echo '<option value="">No locations found.</option>';
					}
				?>
			</select>
			<button type="submit" id="Submit" name="Submit" class="alternate"><?php _e( 'Set Location', 'dma' ); ?></button>
		</form>
	</div><!-- .select-location-wrap -->
<?php
}

genesis();
