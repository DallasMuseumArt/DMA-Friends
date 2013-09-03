<?php
/** Start the engine */
require_once( get_template_directory() . '/lib/init.php' );

// Child theme details (do not remove)
define( 'CHILD_THEME_NAME', 'DMA' );
define( 'CHILD_THEME_URL', 'http://webdevstudios.com/wordpress/support-packages' );
// for PHP versions < 5.3
if ( !defined( '__DIR__' ) )
	define( '__DIR__', dirname( __FILE__ ) );
// define main theme directory
if ( !defined( 'DMA_MAIN' ) )
	define( 'DMA_MAIN', __DIR__ );
// define main theme directory uri
if ( !defined( 'DMA_MAIN_URI' ) )
	define( 'DMA_MAIN_URI', get_stylesheet_directory_uri() );

// combined functions for ipad and portal themes
require_once __DIR__ . '/lib/shared-functions.php';

/**
 * Register both themes' image sizes so crops are created for both themes
 */
// iPad sizes
add_image_size( 'full-bg', 1536, 2048, true );
add_image_size( 'popup-img', 960, 500, true );
add_image_size( 'badge-large', 600, 600, true );
add_image_size( 'reward', 360, 500, true );
// Portal sizes
add_image_size( 'featured', 572, 316, true );
add_image_size( 'popup-img2', 814, 424, true );
add_image_size( 'badge-medium', 300, 300, true );
add_image_size( 'reward2', 270, 375, true );

// Hide the admin bar
add_filter( 'show_admin_bar', '__return_false' );

add_action( 'genesis_meta', 'dma_disable_user_scaling' );
/**
 * Add Meta to the doc head
 */
function dma_disable_user_scaling() {
	?>
	<!-- disable user scaling -->
	<meta name="viewport" content="user-scalable=no" />
	<?php
}

add_action( 'template_redirect', 'dma_start_session', 1 );
/**
 * Start session
 */
function dma_start_session() {
	if ( ! isset( $_SESSION ) )
		session_start();

	// Restore our passed location ID if it's passed via querystring
	if ( isset( $_GET['location_id'] ) && is_int( $_GET['location_id'] ) )
		$_SESSION['location_id'] = $_GET['location_id'];
}

add_action( 'wp_head', 'dma_location_info' );
/**
 * Set bg image per location
 */
function dma_location_info() {

	// Set a default location
	$post_id = 80;

	// Get the current location
	// $post_id = &$_SESSION['location_id'];

	$post = get_post( $post_id );
	if ( empty( $post ) )
		return;

	$GLOBALS['dma_location'] = &$post;
	$post->bg = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), 'full-bg' );

	if ( ! is_user_logged_in() )
		echo '
		<style type="text/css">
			body:not(.location-setup) #wrap {
				background: url('. $post->bg[0] .') no-repeat top left;
			}
		</style>
		';
}

add_action( 'genesis_after_footer', 'dma_location_info_footer' );
/**
 * Add location info to page
 */
function dma_location_info_footer() {

	if ( is_user_logged_in() || ! isset( $GLOBALS['dma_location'] ) )
		return;
	// get our location data
	$post = &$GLOBALS['dma_location'];
	// and display it on the page
	echo '
	<div class="location-info footer">
		<h4 class="location-title icon-location">',$post->post_title,'</h4>',
		wpautop( wp_trim_words( $post->post_content, 15, '...' ) ),
	'</div>
	';
}

add_action( 'genesis_header', 'dma_site_header', 5 );
/**
 * Add header with logo/location, user profile info, and main site nav
 */
function dma_site_header() {
	$location = &$GLOBALS['dma_location'];

	if ( !$location || !is_user_logged_in() )
		return;

	// Add our logo and location to the header
	?>
	<header>
		<a class="dma-logo" href="<?php echo site_url(); ?>"></a>
		<div class="location-info">
			<h4 class="location-title icon-location"><?php echo $location->post_title; ?></h4>
		</div>
		<?php do_action( 'dma_header' ); ?>
	</header>
	<?php

	// Output our user's profile or login form to the site header
	dma_user_profile();

	// Add our primary navigation to the site header
	dma_main_nav_menu();
}

add_filter( 'dma_profile_components', 'dma_add_activity_feed_head' );
/**
 * Add the Activity feed icon/link to the header
 */
function dma_add_activity_feed_head( $profile ) {

	$current = is_page( 'activity-stream' ) ? ' current' : '';
	$feed_link = '<a class="activity-feed icon-list'. $current .'" href="'. site_url( '/activity-stream/' ) .'">'. __( 'Activity Stream', 'dma' ) .'</a>';
	$profile = array_slice( $profile, 0, 3, true ) + array( 'feed' => $feed_link ) + array_slice( $profile, 3, count( $profile ) - 1, true );

	return $profile;
}
