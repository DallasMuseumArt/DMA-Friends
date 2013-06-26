<?php
/**
 * Function to create a page (or other content) on the fly and optionally set it's featured image
 */
function dma_add_page( $args = array() ) {

	// our new page default args
	$defaults = array(
		'post_title' => 'New Page',
		'post_type' => 'page',
		'post_status' => 'publish',
		'post_author' => 1,
	);
	// merge defaults and passed in arguments
	$args = wp_parse_args( $args, $defaults );

	// check if a featured image url was provided
	$feat_image = isset( $args['featured_image'] ) ? esc_url( $args['featured_image'] ) : false;

	// don't pass the featured image argument to wp_insert_post
	unset( $args['featured_image'] );

	// create our new page and get it's ID
	$new_post_id = wp_insert_post( $args );

	// if we have an ID and a featured image url, upload and set the image as a featured image
	if ( $new_post_id && $feat_image )
		dma_set_featured_img_from_url( $feat_image, $new_post_id, $args['post_title'] );

	return $new_post_id;
}

/**
 * Pass in an image url and post id and this function will upload the image and set it as a featured image
 */
function dma_set_featured_img_from_url( $imgurl, $post_id, $title = '' ) {

	// require the wp admin files that make these functions work
	require_once( ABSPATH . '/wp-admin/includes/file.php' );
	require_once( ABSPATH . '/wp-admin/includes/media.php' );
	require_once( ABSPATH . '/wp-admin/includes/image.php' );

	if ( !empty( $imgurl ) ) {
		$tmp = download_url( $imgurl );

		preg_match('/[^\?]+\.(jpg|JPG|jpe|JPE|jpeg|JPEG|gif|GIF|png|PNG)/', $imgurl, $matches);
		$file_array['name'] = basename( $matches[0] );
		$file_array['tmp_name'] = $tmp;

		if ( is_wp_error( $tmp ) ) {
			@unlink( $file_array['tmp_name'] );
			$file_array['tmp_name'] = '';
		}

		// sideload the file
		$img_id = media_handle_sideload( $file_array, $post_id, $title );

		if ( is_wp_error( $img_id ) ) {
			@unlink( $file_array['tmp_name'] );
			return $img_id;
		}
		// set the newly uploaded file as our featured image
		set_post_thumbnail( $post_id, $img_id );
	}

}

/**
 * Gets and returns the 'what is dma friends' page
 */
function dashboard_popup_content( $title, $echo = false, $help = true, $args = array() ) {

	if ( empty( $args ) )
		$args = array( 'post_title' => $title );

	// Grab our page
	if ( isset( $args['usepath'] ) && $args['usepath'] )
		$page = get_page_by_path( $args['usepath'] );
	else
		$page = get_page_by_title( $title );

	// Create our page if it doesn't exist
	if ( !$page )
		$page = get_post( dma_add_page( $args ) );

	$thumb = get_the_post_thumbnail( $page->ID, 'popup-img' );
	$thumb = $thumb ? '<span class="thumbnail">'. $thumb .'</span>' : '';
	$output = '<div class="details">';
	if ( $help )
		$output .= '<h1 class="title help"><div class="q icon-help-circled"></div><span>'. get_the_title( $page->ID ) .'</span></h1>';
	else
		$output .= '<h1 class="title">'. get_the_title( $page->ID ) .'</h1>';

		$output .= $thumb;
		$output .= '<div class="description">' . wpautop( $page->post_content ) . '</div><!-- .description -->
	</div><!-- .details -->
	';

	if ( !$echo )
		return $output;

	echo $output;
}

/**
 * Outputs markup for ajax waiting spinner and success message
 *
 * @since  1.0
 * @param  string  $message Message to be output in the "success" notification dialog
 * @return string  Concatenated output for spinner markup
 */
function dma_spinner_notification( $message = 'Saved' ) {
	return '
	<div class="spinner">
		<div class="bar1"></div>
		<div class="bar2"></div>
		<div class="bar3"></div>
		<div class="bar4"></div>
		<div class="bar5"></div>
		<div class="bar6"></div>
		<div class="bar7"></div>
		<div class="bar8"></div>
		<div class="bar9"></div>
		<div class="bar10"></div>
		<div class="bar11"></div>
		<div class="bar12"></div>
	</div>
	<p class="notification">'. $message .'</p>
	';
}
