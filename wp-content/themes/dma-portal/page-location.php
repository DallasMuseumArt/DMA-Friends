<?php

add_action( 'get_header', 'dma_redirect_to_home' );
function dma_redirect_to_home() {

	// If the location ID is set, return to homepage
	if ( dma_get_current_location_id() ) {
		wp_redirect( site_url() );
		exit;
	}

	// If we have POST data, and our nonce checks out
	if (
		isset( $_POST['location-select'] )
		&& wp_verify_nonce( $_POST['select_location'], plugin_basename( __FILE__ ) )
	) {
		// Set our location ID and redirect to homepage
		dma_set_current_location_id( $_POST['location-select'] );
		wp_redirect( site_url() );
		exit;
	}

}

add_filter( 'dma_left_right_nav', '__return_null' );
// remove_action( 'genesis_header', 'dma_user_profile', 8 );
// remove_action( 'genesis_header', 'genesis_header_markup_open', 5 );
// remove_action( 'genesis_header', 'dma_header_links' );
// remove_action( 'genesis_header', 'genesis_header_markup_close', 15 );
remove_all_actions( 'genesis_header' );

remove_action( 'genesis_loop', 'genesis_do_loop' );
add_filter( 'body_class', 'dma_add_login_class' );
function dma_add_login_class( $classes ) {
	// Add our page body class
	$classes[] = 'location-setup';

	return $classes;
}


add_action( 'genesis_loop', 'dma_user_dashboard' );
function dma_user_dashboard() {

	echo '
	<div class="select-location-wrap info-box" >
		<div class="dma-logo"></div>
		';

	// See if we're logged in
	// if ( ! is_user_logged_in() ) {
	// 	echo '<p class="select-location">'. __( 'No Locations Found!', 'dma' ). '</p>';
	// }

	$args = array(
		'posts_per_page' => 9999,
		'post_status' => 'publish',
		'no_found_rows' => true,
		'post_type' => 'dma-location',
		'order' => 'ASC',
		'orderby' => 'title'
	);
	$locations = new WP_Query( $args );
	if ( $locations->have_posts() ) {
		?>
		<form method="post" name="select-location" class="select-location info" action="<?php echo site_url( '/location' ); ?>">
			<?php wp_nonce_field( plugin_basename( __FILE__ ), 'select_location' ); ?>
			<p><?php _e( 'Please select the location of this kiosk:', 'dma' ); ?></p>
			<select name="location-select" id="location-select">
				<?php
				while ( $locations->have_posts() ) : $locations->the_post();
					global $post;
					echo '<option value="',$post->ID,'">', the_title(), '</option>';

				endwhile;
				// Reset Post Data
				wp_reset_postdata();
				?>
			</select>
			<button type="submit" id="Submit" name="Submit" class="alternate"><?php _e( 'Set Location', 'dma' ); ?></button>
		</form>
		<?php


	} else {
		?>
		<p class="select-location">Please find a staff person to initilize this kiosk.</p>
		<?php
	}
	echo '</div><!-- .select-location-wrap -->';

}

genesis();
