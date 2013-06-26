<?php
/*
Template Name: User Dashboard
*/

add_filter( 'body_class', 'dma_add_dashboard_class' );
function dma_add_dashboard_class( $classes ) {
	$classes[] = 'dashboard';
	if ( isset( $_GET['auth_error'] ) )
		$classes[] = 'auth-error';

	if ( isset( $_GET['forgot-pin'] ) )
		$classes[] = 'new-pin';

	return $classes;
}

if ( !is_user_logged_in() ) {
	add_filter( 'dma_left_right_nav', '__return_null' );
	add_action('wp_enqueue_scripts', 'dma_login_script');
}

/**
 * enqueue our custom script for the login process
 */
function dma_login_script() {
	wp_dequeue_script( 'dma-site-scripts' );
	wp_dequeue_script( 'dma-registration' );
}

remove_action( 'genesis_loop', 'genesis_do_loop' );
add_action( 'genesis_loop', 'dma_user_dashboard' );
function dma_user_dashboard() {

	// If we're not logged in, we should display the registration/login screen
	if ( !is_user_logged_in() ) {
		if ( isset( $_GET['forgot-pin'] ) )
			dma_forgot_pin_request_form();
		else
			dma_login_form();

	// Otherwise, we're logged in and should display the user dashboard
	} else { ?>
		<div class="dashboard-wrap">
			<div class="dma-dashboard-left">
				<?php dma_user_notices(); ?>
				<div class="dma-featured">
					<?php dma_loop_featured(); ?>
				</div>
			</div>
			<div class="dma-dashboard-right latest-badge">
				<h1>My Latest Badge</h1>
				<?php echo $GLOBALS['dma_user']->latest_badge(); ?>
			</div>
			<div class="clear"></div>
			<div class="things-to-do">
				<h1>Things to Do</h1>
				<?php
					$filter = isset( $_GET['filter'] ) ? $_GET['filter'] : 'all';
					$style = ( 'all' != $filter ) ? ' style="visibility: hidden;"' : '';
				?>
				<ul class="filter-buttons-wrap button-group buttons">
					<?php
					dma_filter_menu_item( 'All' );
					dma_filter_menu_item( 'Steps Remaining', 'is-active' );
					dma_filter_menu_item( 'Points', 'has-points' );
					dma_filter_menu_item( 'Bookmarked' );
					?>
					<li class="last">
						<a class="help pop small" href="#what-are-these" data-popheight="auto"><div class="q icon-help-circled"></div><span>What are these?</span></a>
					</li>

				</ul>
				<?php
					$dma_badges = get_posts( array(
						'post_type'      => 'badge',
						'posts_per_page' => -1
					));

					$badge_output = '';
					$badge_output .= '<div class="badge-list">';
					foreach ( $dma_badges as $dma_badge ) {

						// Make sure we can display this badge...
						if (
							'steps' != dma_badge_trigger_type( $dma_badge->ID )                        // If it's not a step-based badge
							|| 'true' == get_post_meta( $dma_badge->ID, '_dma_hidden', true )          // Or it's a hidden badge
							|| dma_is_outside_date_restrictions( $dma_badge->ID )                      // Or we're outside the date restrictions
							|| dma_user_has_exceeded_max_earnings( dma_get_user_id(), $dma_badge->ID ) // Or the user has earned it the max times
							|| ! dma_user_has_prereq_badges( dma_get_user_id(), $dma_badge->ID )       // Or we do NOT have the prereq badges
						)
							continue;

						// Otherwise, create a new badge object and concatenate our output
						$badge = new DMA_Badge( $dma_badge );
						$badge_output .= $badge->details_output();
						$badge_output .= $badge->details_modal();

					} // End foreach
					$badge_output .= '</div><!-- .badge-list -->';
					echo $badge_output;
				?>
			</div>
			<div id="what-are-these" class="popup close">
				<?php dashboard_popup_content( 'What Are Badges', true ); ?>
				<a class="button secondary close-popup" href="#">Close</a>
			</div>
			<div class="clear"></div>
		</div><!-- .dashboard-wrap -->
		<?php
	} //end if/else
}

add_filter( 'wdscaroufredsel_cpt_image_name', 'dma_caroufredsel_loop_image' );
/**
 * Set carouFredSel cpt loops image size
 */
function dma_caroufredsel_loop_image( $size ) {
	return 'featured';
}


/**
 * Enqueu carouFredSel and loop through our featured posts
 */
function dma_loop_featured() {
	$featured = function_exists('wds_fcs_get_featured') ? wds_fcs_get_featured() : '';
	if ( !$featured )
		return;

	wds_caroufredsel('.dma-featured', array(
		'width' => 572,
		'items' => 1,
		'scroll' => 1,
		'scroll' => array(
			'fx' => 'crossfade'
		),
		'auto' => array(
			'easing' => 'linear',
			'duration' => 1000,
			'timeoutDuration' => 9000,
			'pauseOnHover' => true
		),
		'pagination' => array(
			'container' => '.featured-nav',
			'anchorBuilder' => 'function( nr ) {
				return \'<a data-icon="\e019" href="#">Test</a>\';
			}'
		),

	) );
	?>
	<div class="dma-featured-wrap">
		<ul class="dma-featured">
		<?php
		foreach ( $featured as $id => $feature ) {

			// Is the "open in new window" box checked?
			$feature['ext_link'] = get_post_meta( $id, '_dma_is_external_link', true ) ? ' target="_blank"' : '';

			// feature's image
			$html = dma_maybe_link( $feature, 'image' );
			// feature's title
			$html .= dma_maybe_link( $feature, 'title', 'h2' );
			// feature's content
			$html .= dma_maybe_link( $feature, 'content' );

			echo '<li>'. $html .'</li>';
		}
		?>
		</ul><!-- .dma-featured -->
		<div class="featured-nav"></div>
		<div class="clear"></div>
	</div><!-- .dma-featured-wrap -->

	<?php
}

/**
 * Wrap feature in url if it has one
 */
function dma_maybe_link( $feature, $index, $wrap = false ) {

	if ( !$index )
		return '';

	if ( !$feature['url'] )
		return $feature[$index];

	$html = '<a class="'. $index .'" href="'. $feature['url'] .'"'. $feature['ext_link'] .'>'. $feature[$index] .'</a>';
	if ( $wrap )
		$html = '<'. $wrap .'>'. $html .'</'. $wrap .'>';

	return $html;
}

/**
 * Add 'forgot pin' link
 */
add_filter( 'login_form_bottom', 'dma_forgot_pin' );
function dma_forgot_pin( $html ) {

	$html = '';
	if ( isset( $_GET['auth_error'] ) )
		$html .= '<a class="button secondary close-popup cancel" href="'. site_url() .'">Cancel</a>';

	$html .= '
	<a class="help small forgot-pin" href="'. site_url( '/?forgot-pin=true' ) .'"><div class="q icon-help-circled"></div><span>'. __( 'I forgot my PIN.', 'dma' ) .'</span></a>
	';

	return $html;
}

/**
 * Add numeric keyboard attribute
 */
add_filter( 'login_form_password_attributes', 'dma_pw_numeric_attr' );
function dma_pw_numeric_attr( $attrs ) {

	$attrs .= ' pattern="[0-9]*" ';

	return $attrs;
}

/**
 * Update the default arguments for the badgeos login form
 */
add_filter( 'login_form_defaults', 'dma_login_defaults' );
function dma_login_defaults( $args ) {

	$title = isset( $_GET['auth_error'] ) ? __( 'Please Try Again', 'dma' ) : __( 'DMA Friends Login', 'dma' );
	$args['title'] = '
	<h1>'. $title .'</h1>
	<p class="login-description login-error red icon-attention">'. __( 'The e-mail or PIN code you entered is incorrect.', 'dma' ) .'</p><p class="login-description">'. __( 'Log in with your e-mail or phone number and PIN code below', 'dma' ) .'</p>
	';

	$args['class_wrap'] = $args['class_wrap'].' popup ltd" data-popheight="990';
	$args['title_wrap'] = 'div';
	$args['title_class'] = $args['title_class']. ' red';
	$args['label_username'] = __( 'E-mail or Mobile Phone Number', 'dma' );
	$args['label_password'] = __( 'Pin Code', 'dma' );
	$args['button_class'] = $args['button_class']. ' primary wide';
	return $args;
}

function dma_forgot_pin_request_form() {
	?>
	<div id="form_wrap" class="forgot-pin-form popup">
		<form name="forgot-pin-form" id="forgot-pin-form" action="<?php echo esc_url( site_url( '/wp-login.php?action=lostpassword', 'login_post' ) ); ?>" method="post">

			<div class="login-title red">
				<h1><?php _e( 'Request New PIN', 'dma' ); ?></h1>
				<p class="login-description"><?php _e( 'Enter your email address to have your PIN emailed to you', 'dma' ); ?></p>
				<?php
				if ( empty( $_POST['user_login'] ) ) {
					$errors = new WP_Error();
					$errors->add( 'empty_username', __( '<strong>ERROR</strong>: Enter an e-mail address.' ) );
				}
				?>
			</div>
			<label for="email">E-mail Address</label>
			<input type="text" name="user_login" id="email" class="input" value="" tabindex="10">
			<input type="hidden" name="redirect_to" value="<?php echo esc_attr( site_url() ); ?>" />
			<button type="submit" name="wp-submit" id="wp-submit" class="button button-primary primary wide" tabindex="30"><?php _e( 'Submit', 'dma' ); ?></button>
			<a class="button secondary close-popup cancel" href="<?php echo site_url(); ?>"><?php _e( 'Cancel', 'dma' ); ?></a>
		</form>
	</div>
	<?php
}

genesis();
