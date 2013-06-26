<?php
/** Start the engine */
require_once( get_template_directory() . '/lib/init.php' );

// Child theme details (do not remove)
define( 'CHILD_THEME_NAME', 'DMA Portal' );
define( 'CHILD_THEME_URL', 'http://webdevstudios.com/wordpress/support-packages' );

// for PHP versions < 5.3
if ( !defined( '__DIR__' ) )
	define( '__DIR__', dirname( __FILE__ ) );
// define main theme directory
if ( !defined( 'DMA_MAIN' ) )
	define( 'DMA_MAIN', dirname( __DIR__ ) .'/dma' );
// define main theme directory uri
if ( !defined( 'DMA_MAIN_URI' ) )
	define( 'DMA_MAIN_URI', get_theme_root_uri( 'dma' ) .'/dma' );

// combined functions for ipad and portal themes
require_once DMA_MAIN . '/lib/shared-functions.php';
// wp-login.php mods
if ( ! is_user_logged_in() && !is_admin() )
	require_once __DIR__ . '/lib/login-mods.php';

add_filter( 'show_admin_bar', 'dma_remove_admin_bar_non_admins' );
/**
 * Hide the admin bar from non-admins
 */
function dma_remove_admin_bar_non_admins( $show ) {
	if ( !current_user_can( 'manage_options' ) )
		return false;
	return $show;
}

add_action( 'admin_init', 'dma_redirect_to_login_non_admins' );
/**
 * Redirect to home for < Editors
 */
function dma_redirect_to_login_non_admins( $do_redirect ) {

	if ( !current_user_can( 'edit_others_posts' ) && ! defined('DOING_AJAX') ) {
		wp_redirect( site_url() ); exit;
	}
}

add_filter( 'wp_mail_from_name', 'dma_core_email_from_name_filter' );
/**
 * dma_core_email_from_name_filter()
 *
 * Sets the "From" name in emails sent to the name of the site and not "WordPress"
 *
 * @uses get_bloginfo() to get the site name
 * @return The site name
 */
function dma_core_email_from_name_filter() {
 	return apply_filters( 'dma_core_email_from_name_filter', wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) );
}

/**
 * Register both themes' image sizes so crops are created for both themes
 */
// iPad sizes
add_image_size( 'full-bg', 1536, 2048, true );
add_image_size( 'popup-img2', 960, 500, true );
add_image_size( 'badge-large2', 600, 600, true );
add_image_size( 'reward2', 360, 500, true );
// Portal sizes
add_image_size( 'featured', 572, 316, true );
add_image_size( 'popup-img', 814, 424, true );
add_image_size( 'badge-medium', 300, 300, true );
add_image_size( 'badge-large', 300, 300, true );
add_image_size( 'reward', 270, 375, true );

add_theme_support( 'custom-background', array(
	// Let WordPress know what our default background image is.
	'default-image' => get_stylesheet_directory_uri().'/images/DMA-BG.png',
) );

add_filter( 'theme_mod_background_image', 'dma_bg_image_home_only' );
add_filter( 'theme_mod_background_color', 'dma_bg_image_home_only' );
/**
 * Only show custom background selection on login page
 */
function dma_bg_image_home_only( $mod ) {
	if ( is_user_logged_in() && !is_admin() )
		return false;
	return $mod;
}

add_filter( 'dma_do_location_redirect', 'dma_redirect_to_login' );
/**
 * Don't do location redirect
 */
function dma_redirect_to_login( $do_redirect ) {
	if ( ! is_user_logged_in() && ! is_front_page() ) {
		wp_redirect( site_url() ); exit;
	}
	return false;
}
// Disable the inactivity logout
add_filter( 'inactivity_logout_enabled', '__return_false' );

// Include featured cpt in plugin
if ( function_exists( 'wds_enable_cfs_cpt' ) )
	wds_enable_cfs_cpt();

add_action( 'wdscaroufredsel_cpt_metabox', 'dma_add_ext_checkbox' );
function dma_add_ext_checkbox() {
	?>
	<label for="_dma_is_external_link"><input type="checkbox" name="_dma_is_external_link" id="_dma_is_external_link" value="1" <?php checked( get_post_meta( get_the_ID(), '_dma_is_external_link', true ) ); ?>/>&nbsp;&nbsp;Have link open in new window?</label>
	<?php
}

add_action( 'wdscaroufredsel_cpt_metabox_save', 'dma_save_ext_link' );
function dma_save_ext_link( $post_id ) {
	// The data
	$ext = isset( $_POST['_dma_is_external_link'] ) && $_POST['_dma_is_external_link'] ? true : false;
	// update our post
	update_post_meta( $post_id, '_dma_is_external_link', $ext );
}

add_filter( 'dma_badge_image_size', 'dma_update_badge_image_size' );
function dma_update_badge_image_size( $size_id ) {
	if ( is_front_page() )
		return $size_id;
	return 'badge-medium';
}

add_filter( 'dma_user_avatar_args', 'dma_update_user_avatar_args' );
function dma_update_user_avatar_args( $args ) {
	$args['width'] = $args['height'] = 96;
	return $args;
}

add_action( 'genesis_header', 'dma_site_header', 5 );
/**
 * Add header with logo/location, user profile info, and main site nav
 */
function dma_site_header() {
	// Add our logo and location to the header
	?>
	<div id="pre-header">
		<a class="dma-logo" href="<?php echo site_url(); ?>"><span class="icon-dma"></span><span class="icon-friends"></span></a>
		<div class="location-info">
			<h4 class="location-title"><a href="http://dma.org/" title="DMA.ORG">DMA.ORG</a></h4>
		</div>
		<?php do_action( 'dma_header' ); ?>
	</div>
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

add_action( 'genesis_before_content_sidebar_wrap', 'dma_back_to_top', 5 );
/**
 * Add a "Top" button for scrolling to the top
 */
function dma_back_to_top() {
	echo '<a class="back-to-top icon-arrow-up" href="#wrap">Top</a>';
}


add_filter( 'gettext', 'dma_replace_rewards_text' );
function dma_replace_rewards_text( $text ) {
	if ( $text == 'Get Rewards' )
		return 'Rewards';
	return $text;
}


add_filter( 'body_class', 'dma_site_body_class' );
function dma_site_body_class( $classes ) {
	$classes[] = 'dma-portal';
	if ( is_admin_bar_showing() )
		$classes[] = 'admin-bar';
	if ( wp_is_mobile() )
		$classes[] = 'mobile';

	// Determine user's browser and adds appropriate class
	global $is_lynx, $is_gecko, $is_IE, $is_opera, $is_NS4, $is_safari, $is_chrome, $is_iphone;
	if($is_lynx) $classes[] = 'lynx';
	elseif($is_gecko) $classes[] = 'gecko';
	elseif($is_opera) $classes[] = 'opera';
	elseif($is_NS4) $classes[] = 'ns4';
	elseif($is_safari) $classes[] = 'safari';
	elseif($is_chrome) $classes[] = 'chrome';
	elseif($is_IE) $classes[] = 'ie';
	elseif($is_iphone) $classes[] = 'iphone';
	elseif( $is_iphone || $is_safari || $is_chrome ) $classes[] = 'webkit';
	else $classes[] = 'unknown';

	// Include user's IE version for version-specific hacking. Credit: http://wordpress.org/extend/plugins/krusty-msie-body-classes/
	if( preg_match( '/MSIE ([0-9]+)([a-zA-Z0-9.]+)/', $_SERVER['HTTP_USER_AGENT'], $browser_version ) ){
		// add a class with the major version number
		$classes[] = 'ie' . $browser_version[1];
		// add an ie-lt9 class to match MSIE 8 and older
		if ( 9 > $browser_version[1] )
			$classes[] = 'ie-lt9';
		// add an ie-lt8 and ie-old class to match MSIE 7 and older
		if ( 8 > $browser_version[1] ) {
			$classes[] = 'ie-lt8';
			$classes[] = 'ie-old';
		}
	}

	return $classes;
}
