<?php
/*
Template Name: User Dashboard
*/

remove_action( 'genesis_loop', 'genesis_do_loop' );

add_filter( 'body_class', 'dma_add_dashboard_class' );
function dma_add_dashboard_class( $classes ) {
	$classes[] = 'dashboard';
	return $classes;
}

if ( !is_user_logged_in() ) {
	add_filter( 'dma_left_right_nav', '__return_null' );
	add_action('wp_enqueue_scripts', 'dma_login_script');
}

/**
 * Generate our Welcome modal
 *
 * @since 1.0
 * @return string Our concatenated output
 */
function dma_welcome_modal() {

	add_action( 'wp_footer', 'dma_welcome_footer_script' );

	// Concatenate our output
	$output ='';

	$output .= '<div id="pop-welcome" class="hidden popup" data-popheight="auto">';
	$output .= dashboard_popup_content( 'Welcome to DMA Friends!', false, false );
	$output .= '<a class="button alternate close-popup" href="#">'. __( 'Get Started', 'dma' ) .'</a>';
	$output .= '</div>';

	delete_transient( 'welcome-' . $GLOBALS['dma_user']->user_login );

	return $output;
}

/**
 * Script to pop welcome popup if transient exists
 */
function dma_welcome_footer_script() {
	?>
	<script type="text/javascript">
	jQuery(document).ready(function($) {
		setTimeout(function() {
			$.popOpen( $('#pop-welcome') );
		}, 800);
	});
	</script>
	<?php
}

/**
 * enqueue our custom script for the login process
 */
function dma_login_script() {
	wp_enqueue_script( 'login', get_stylesheet_directory_uri(). '/lib/js/login.js', array( 'dma-site-scripts' ), '1.0' );
}

add_action( 'genesis_loop', 'dma_user_dashboard' );
function dma_user_dashboard() {

	// If we're not logged in, we should display the registration/login screen
	if ( ! is_user_logged_in() ) {	?>

		<?php
		dma_login_form();
		echo dma_user_registration_form();
		?>

		<div class="info-box login-box" >
			<div class="dma-logo">
				<a class="help small pop" href="#what-is-dma"><div class="q icon-help-circled"></div><span>What is this?</span></a>
			</div>
			<div class="info">
				<a class="button alternate login pop" href="#card-scan"><?php _e( 'Log In', 'dma' ); ?></a>
				<a class="button alternate alignright sign-up pop" href="#do-registration"><?php _e( 'Sign Up', 'dma' ); ?></a>
			</div>
		</div><!-- .select-location-wrap -->

		<div id="what-is-dma" class="popup close" data-popheight="auto">
			<?php dashboard_popup_content( 'What is DMA Friends?', true ); ?>
			<div class="clear"></div>
			<a class="button secondary close-popup" href="#">Close</a>
		</div>

		<?php

	// Otherwise, we're logged in and should display the user dashboard
	} else { ?>
		<div class="dashboard-wrap">
			<?php echo dma_spinner_notification(''); ?>
			<div class="dma-dashboard-left">
				<?php echo dma_code_input_form(); ?>
				<div class="dma-tips">
					<h1>Helpful Tips</h1>
					<?php dma_user_notices(); ?>
					<!-- <p>As you enter Activity codes you’ll earn points, and you can use points to claim rewards. Head to the <a href="<?php echo site_url( '/get-rewards/' ); ?>">Rewards tab</a> to explore what’s available.</p> -->
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
					<li>
						<a class="help pop" href="#what-are-these" data-popheight="auto"><div class="q icon-help-circled"></div><span>What are these?</span></a>
					</li>

				</ul>
				<?php
					// Attempt to retrieve cached badge query
					$dma_badges = maybe_unserialize( get_transient( 'dma_badges' ) );

					// If we don't have a cached query, run a new one
					if ( empty( $dma_badges ) ) {
						$dma_badges = get_posts( array(
							'post_type'      => 'badge',
							'posts_per_page' => -1
						) );

						// Store our badge query for a week
						set_transient( 'dma_badges', $dma_badges, WEEK_IN_SECONDS );
					}

					// Double-check we're working with an array...
					// maybe_unserialize() wasn't working on dev
					if ( ! is_array( $dma_badges ) )
						$dma_badges = unserialize( $dma_badges );

					$badge_output = '';
					$badge_output .= '<div class="badge-list">';
					if ( ! empty( $dma_badges ) ) {
						foreach ( $dma_badges as $dma_badge ) {
							// Bail here if we cannot display this badge to the current user
							if (
								'triggers' !== get_post_meta( $dma_badge->ID, '_badgeos_earned_by', true ) // If it's not a step-based badge
								|| 'hidden' == get_post_meta( $dma_badge->ID, '_badgeos_hidden', true )    // Or it's a hidden badge
								|| dma_is_outside_date_restrictions( $dma_badge->ID )                      // Or we're outside the date restrictions
								|| badgeos_achievement_user_exceeded_max_earnings( dma_get_user_id(), $dma_badge->ID ) // Or the user has earned it the max times
								|| ! dma_user_has_prereq_badges( dma_get_user_id(), $dma_badge->ID )       // Or we do NOT have the prereq badges
							)
								continue;

							// Create a new badge object and concatenate our output
							$badge = new DMA_Badge( $dma_badge );
							$badge_output .= $badge->details_output();
							$badge_output .= $badge->details_modal();

							// Output only the post title
							// $badge_output .= '<p>' . get_the_title( $dma_badge->ID ) . '</p>';
						} // End foreach
					}

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

		// If the welcome transient is set, output the related markup for the popup
		if ( 'welcome' == get_transient( 'welcome-' . $GLOBALS['dma_user']->user_login ) )
			echo dma_welcome_modal();

	} //end if/else
}

/**
 * Add the card scanner to the beginning of the login form
 */
add_filter( 'before_login_form', 'dma_add_card_scan' );
function dma_add_card_scan( $html ) {

	$html = '
	<div id="card-scan" class="popup close ltd" data-popheight="990">
		<h1>'. __( 'Scan Membership Card', 'dma' ) .'</h1>
		<p>'. __( 'Log in with your DMA Friends card! Use the guide on the screen to help you line up your membership card to the camera.', 'dma' ) .'</p>
		<a class="help pop manual-login" href="#form_wrap"><div class="q icon-help-circled"></div><span>'. __( 'I don\'t have my card.', 'dma' ) .'</span></a>
		<a class="button secondary close-popup cancel" href="#">Cancel</a>

	</div>
	';

	return $html;
}

/**
 * Add 'forgot pin' link
 */
add_filter( 'login_form_bottom', 'dma_forgot_pin' );
function dma_forgot_pin( $html ) {

	$html = '
	<a class="help small pop forgot-pin" href="#forgot-pin"><div class="q icon-help-circled"></div><span>'. __( 'I forgot my PIN.', 'dma' ) .'</span></a>
	<a class="button secondary close-popup cancel" href="#">Cancel</a>
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
 * Add 'forgot pin' popup
 */
add_filter( 'after_login_form', 'dma_forgot_pin_popup' );
function dma_forgot_pin_popup( $html ) {

	$html = '
	<div id="forgot-pin" class="popup close" data-popheight="auto">
		<p>'. __( 'Please contact a Visitor Services representative at the Visitor Services Desk to assist you.', 'dma' ) .'</p>
		<a class="button secondary close-popup" href="#">Close</a>
	</div>
	';

	return $html;
}

/**
 * Update the default arguments for the badgeos login form
 */
add_filter( 'login_form_defaults', 'dma_login_defaults' );
function dma_login_defaults( $args ) {

	$title = isset( $_GET['auth_error'] ) ? __( 'Please Try Again', 'dma' ) : __( 'Or Type Your Info', 'dma' );
	$args['title'] = '
	<h1>'. $title .'</h1>
	<p class="login-description login-error red icon-attention">'. __( 'The e-mail or PIN code you entered is incorrect.', 'dma' ) .'</p><p class="login-description">'. __( 'Log in with your e-mail or mobile phone number and PIN code below', 'dma' ) .'</p>
	';

	$args['class_wrap'] = $args['class_wrap'].' popup ltd" data-popheight="990';
	$args['title_wrap'] = 'div';
	$args['title_class'] = $args['title_class']. ' red';
	$args['label_username'] = __( 'E-mail or Mobile Phone Number', 'dma' );
	$args['label_password'] = __( 'Pin Code', 'dma' );
	$args['button_class'] = $args['button_class']. ' primary wide';
	return $args;
}

/**
 * Add custom authentication error message for scanned ID cards
 */
add_action( 'genesis_after', 'dma_authenticaion_failure_message' );
function dma_authenticaion_failure_message() {
	if ( is_front_page() && isset( $_GET['auth_error'] ) ) {
		echo '<div id="auth_error" class="popup" data-popheight="auto">';
		echo '<h2>Oops! We can’t seem to find you in the Friends&nbsp;system.<h2>';
		echo '<p>If you are <strong>already a DMA Friend</strong>, please see Visitor Services to activate your card. If you would like <strong>to become a DMA Friend,</strong> please click "Continue".</p>';
		echo '<br/>';
		echo '<a href="#do-registration" class="pop wide primary button">Continue</button>';
		echo '<a class="button secondary left close-popup" href="#">Close</a>';
		echo '</div>';
	}
}

add_filter( 'dma_user_notices', 'dma_carousel_user_notices', 10, 2 );
/**
 * Get all user notices and display in a carousel
 */
function dma_carousel_user_notices( $output, $notices ) {

	if ( !function_exists( 'wds_caroufredsel' ) )
		return 'nope'.$output;

	wds_caroufredsel('.dma-tips-list', array(
		'width' => 600,
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
			'container' => '.tips-list-nav',
			'anchorBuilder' => 'function( nr ) {
				return \'<a href="#">Test</a>\';
			}'
		),

	) );
	$output = '<div class="dma-tips-list-wrap">' . "\n";
	$output = '<ul class="dma-tips-list">' . "\n";
	foreach ( $notices as $notice ) {

		$output .= '<li class="user-notice">'. apply_filters( 'the_content', $notice->post_content ) .'</li>' . "\n";
	}
	$output .= '</ul><!-- .dma-tips-list -->' . "\n";
	$output .= '<div class="tips-list-nav"></div>' . "\n";
	$output .= '</div><!-- .dma-tips-list-wrap -->' . "\n";
	$output .= '<div class="clear"></div>' . "\n";

	return $output;
}

genesis();
