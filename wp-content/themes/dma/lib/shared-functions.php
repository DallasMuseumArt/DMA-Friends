<?php
/**
 * Combined functions for ipad and portal themes
 */

/**
 * Unregister site layouts
 */
genesis_unregister_layout( 'content-sidebar' );
genesis_unregister_layout( 'sidebar-content' );
genesis_unregister_layout( 'content-sidebar-sidebar' );
genesis_unregister_layout( 'sidebar-sidebar-content' );
genesis_unregister_layout( 'sidebar-content-sidebar' );
/**
 * Remove unneeded Genesis functions
 */
remove_action( 'genesis_header', 'genesis_do_header' );
remove_action( 'genesis_after_header', 'genesis_do_nav' );
remove_action( 'genesis_footer', 'genesis_do_footer' );
remove_action( 'genesis_footer', 'genesis_footer_markup_open', 5 );
remove_action( 'genesis_footer', 'genesis_footer_markup_close', 15 );
// Remove Footer
remove_all_actions( 'genesis_footer' );

add_filter( 'genesis_pre_get_option_site_layout', '__genesis_return_full_width_content' );

/**
 * Remove secondary sidebar
 */
unregister_sidebar( 'header-right' );
unregister_sidebar( 'sidebar-alt' );

add_filter( 'wp_editor_set_quality', 'dma_jpeg_quality' ); // >= WP 3.5
add_filter( 'jpeg_quality', 'dma_jpeg_quality' );
/**
 * Avoid WP's quality filtering for better high-dpi images
 */
function dma_jpeg_quality($arg) {
	return (int)100;
}

add_action( 'genesis_meta', 'add_viewport_meta_tag' );
/**
 * Add Meta to the doc head
 */
function add_viewport_meta_tag() {
	?>
	<!-- include apple icons and splash screens -->
	<meta content="yes" name="apple-mobile-web-app-capable"/>

	<!-- iPad -->
	<link href="<?php echo DMA_MAIN_URI; ?>/images/apple-touch-icon-72x72.png"
	      sizes="72x72"
	      rel="apple-touch-icon"/>
	<link href="<?php echo DMA_MAIN_URI; ?>/images/apple-touch-startup-image-768x1004.png"
	      media="(device-width: 768px) and (device-height: 1024px)
	         and (orientation: portrait)
	         and (-webkit-device-pixel-ratio: 1)"
	      rel="apple-touch-startup-image"/>
	<link href="<?php echo DMA_MAIN_URI; ?>/images/apple-touch-startup-image-748x1024.png"
	      media="(device-width: 768px) and (device-height: 1024px)
	         and (orientation: landscape)
	         and (-webkit-device-pixel-ratio: 1)"
	      rel="apple-touch-startup-image"/>

	<!-- iPad (Retina) -->
	<link href="<?php echo DMA_MAIN_URI; ?>/images/apple-touch-icon-144x144.png"
	      sizes="144x144"
	      rel="apple-touch-icon"/>
	<link href="<?php echo DMA_MAIN_URI; ?>/images/apple-touch-startup-image-1536x2008.png"
	      media="(device-width: 768px) and (device-height: 1024px)
	         and (orientation: portrait)
	         and (-webkit-device-pixel-ratio: 2)"
	      rel="apple-touch-startup-image"/>
	<link href="<?php echo DMA_MAIN_URI; ?>/images/apple-touch-startup-image-1496x2048.png"
	      media="(device-width: 768px) and (device-height: 1024px)
	         and (orientation: landscape)
	         and (-webkit-device-pixel-ratio: 2)"
	      rel="apple-touch-startup-image"/>
	<?php
}

// for bumping version numbers
remove_action( 'genesis_meta', 'genesis_load_stylesheet' );
/**
 * Enqueue site's js file
 */
add_action('wp_enqueue_scripts', 'dma_scripts_and_styles');
function dma_scripts_and_styles() {
	if ( is_admin() )
		return;

	// for bumping version numbers
	wp_enqueue_style( 'main-stylesheet', get_stylesheet_uri(), null, '1.0.5' );

	wp_enqueue_script( 'tiny-sort', get_stylesheet_directory_uri() .'/lib/js/tinysort/src/jquery.tinysort.min.js', array( 'jquery' ), '1.5' );
	wp_enqueue_script( 'dma-site-scripts', get_stylesheet_directory_uri() .'/lib/js/site-scripts.js', array( 'jquery' ), '1.5' );

}

add_action( 'genesis_before_content_sidebar_wrap', 'dma_left_right_nav', 5 );
/**
 * Add a filtereable Left/Right Nav to the pages
 */
function dma_left_right_nav() {

	echo apply_filters( 'dma_left_right_nav', '<div class="nav-left icon-left-open-big"></div><div class="nav-right icon-right-open-big"></div>' );
}

add_filter( 'body_class', 'dma_add_body_classes' );
/**
 * Add a "not logged in" class and remove header
 */
function dma_add_body_classes( $classes ) {
	// Check if user is not logged in
	if ( !is_user_logged_in() ) {
		// and add appropriate class
		$classes[] = 'login-please';
		// if not logged in, remove header
		remove_all_actions( 'genesis_header' );
	}
	return $classes;
}

/**
 * Site's main navigation markup
 */
function dma_main_nav_menu() {
	?>
	<div class="main-menu">
		<a class="first menu-item menu-item-home <?php dma_is_active_page( 'home' ); ?>" href="<?php echo site_url(); ?>/"><span><?php _e( 'Home', 'dma' ); ?></span></a>
		<a class="menu-item menu-item-badges <?php dma_is_active_page( 'my-badges' ); ?>" href="<?php echo site_url( '/my-badges' ); ?>"><span><?php _e( 'My Badges', 'dma' ); ?></span></a>
		<a class="menu-item menu-item-rewards <?php dma_is_active_page( 'get-rewards' ); ?>" href="<?php echo site_url( '/get-rewards' ); ?>"><span><?php _e( 'Get Rewards', 'dma' ); ?></span></a>
		<a class="last menu-item menu-item-profile <?php dma_is_active_page( 'profile' ); ?>" href="<?php echo site_url( '/profile' ); ?>"><span><?php _e( 'My Profile', 'dma' ); ?></span></a>
	</div>
	<?php
}

/**
 * Helper function for determining if a given page is our active page
 *
 * @param  string $slug The given page we want to test (use "home" for front page)
 * @return void
 */
function dma_is_active_page( $slug ) {

	// Grab our current page slug
	$pagename = get_query_var( 'pagename' );

	// See if it matches
	if ( 'home' == $slug && is_front_page() )
		$is_current = true;
	elseif ( isset( $pagename ) && $pagename == $slug )
		$is_current = true;
	else
		$is_current = false;

	// If we have a match, return the current class
	if ( $is_current )
		echo 'active';
}

/**
 * Helper function to generate Sort By menu
 *
 * @since  1.0
 */
function dma_filter_menu_item( $item, $slug = '', $page = '' ) {

	// @TODO: Change this to use #anchor links instead of URL queries so we can use AJAX
	$current = isset( $_GET['filter'] ) ? $_GET['filter'] : 'all';

	$slug = $slug ? $slug : strtolower( str_replace( ' ', '-', str_replace( '.', '', $item ) ) );
	$classes = array( 'filter-'. $slug, 'button', 'large' );
	$classes[] = ( $current == $slug ) ? 'current' : '';

	$url = site_url( '/'.$page );

	$url = $slug == 'all' ? $url : add_query_arg( 'filter', $slug, $url );

	dma_li_a( 'filter', $item, $slug, $classes, '', $url );
}

/**
 * Helper function for generating a list item link
 *
 * @since  1.0
 * @param  string $arg        The type of list item we're creating
 * @param  string $item       The text to use for the link
 * @param  string $slug       If $arg is page, this should be the page slug we're using
 * @param  array  $classes    An array of classes to apply to the anchor tag
 * @param  array  $li_classes An array of classes to apply to the list menu
 * @param  string $url        The exact URL to use for the anchor tag
 */
function dma_li_a( $arg, $item, $slug, $classes, $li_classes = '', $url = '' ) {
	$classes = implode( ' ', array_unique( array_filter( $classes ) ) );
	$li_classes = empty( $li_classes ) ? '' : ' class="'. $li_classes .'"';
	if ( empty( $url ) ) {
		$url = $arg == 'page' ? site_url( '/'.$slug ) : add_query_arg( $arg, $slug );
	} else {
		$url = esc_url( $url );
	}
	printf( '<li%s><a class="%s" href="%s">%s</a></li>', $li_classes, $classes, $url, $item );
}


/**
 * Redirect on login failure
 */
function dma_redirect_on_failure( $url ) {
	if ( isset( $_GET['authenticate'] ) )
		return site_url( '?auth_error#auth_error' );
	else
		return site_url( '?auth_error#form_wrap' );
}
add_filter( 'badgeos_auth_error_url', 'dma_redirect_on_failure' );
