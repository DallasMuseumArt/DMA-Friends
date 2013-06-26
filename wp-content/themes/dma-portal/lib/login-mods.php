<?php
/**
 * All code necessary to re-tool wp-login.php for our purposes
 */

add_filter( 'style_loader_tag', 'dma_remove_wp_admin_css' );
/**
 * Remove WP admin styles
 */
function dma_remove_wp_admin_css( $style ) {

	if ( strpos( $style, 'wp-admin-css' ) ) {

		$suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
		// replace admin styles with our theme's stylesheet
		return str_ireplace( admin_url( "/css/wp-admin$suffix.css" ), get_stylesheet_uri(), $style );
	}
	// and don't load other admin styles
	if ( strpos( $style, 'buttons-css' ) || strpos( $style, 'colors-fresh-css' ) )
		return false;

	return $style;
}

add_filter( 'login_body_class', 'dma_login_body_class' );
/**
 * Add appropriate body classes
 */
function dma_login_body_class( $classes ) {
	// classes to match our dashboard
	$classes[] = 'login-please dashboard';
	// check if custom background is set
	if ( get_theme_mod( 'background_color' ) || get_background_image() )
		$classes[] = 'custom-background';
	if ( isset( $_GET['action'] ) && $_GET['action'] == 'lostpassword' )
		$classes[] = 'new-pin';

	return $classes;
}

add_action( 'login_enqueue_scripts', 'dma_login_head' );
function dma_login_head() {
	// enqueue our main stylesheet
	wp_enqueue_style( 'main-stylesheet', get_stylesheet_uri(), null, '1.0.4' );
	// remove unneeded password-strength-meter scripts (a pin's strength is always bad)
	wp_dequeue_script( 'user-profile' );
	wp_dequeue_script( 'password-strength-meter' );
	// add custom background styles
	_custom_background_cb();
	// dma_reset_pin_test();
}

add_filter( 'gettext', 'dma_rename_pw_text' );
/**
 * Language to change for our purposes
 */
function dma_rename_pw_text( $text ) {
	switch ( $text ) {
		case 'Password':
			return 'PIN Code';
		case 'Username':
			return 'E-mail or Mobile Phone Number';
		case 'Get New Password':
			return 'Submit';
		case '&larr; Back to %s':
			return 'Cancel';
		case 'Username or E-mail:':
			return 'E-mail Address';
		case '<strong>ERROR</strong>: Enter a username or e-mail address.':
			return '<strong>ERROR</strong>: Enter an e-mail address.';
		case '<strong>ERROR</strong>: Invalid username or e-mail.':
			return '<strong>ERROR</strong>: Invalid e-mail.';
		case '<strong>ERROR</strong>: Invalid username or e-mail.':
			return '<strong>ERROR</strong>: Invalid e-mail.';
		case 'Hint: The password should be at least seven characters long. To make it stronger, use upper and lower case letters, numbers and symbols like ! " ? $ % ^ &amp; ).':
			return 'Your PIN code is a four (4) digit Personal Identification Number that is used for security and verification purposes. Your PIN code is private, so please keep it safe!';
		case 'Strength indicator':
			return '';
	}
	// replace all instances of password with PIN
	return str_ireplace( 'password', 'PIN', $text );
}

add_filter( 'login_message', 'dma_tweak_login_messages' );
/**
 * More langues changes
 */
function dma_tweak_login_messages( $text ) {

	$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'login';
	$title = __( 'Request New PIN', 'dma' );

	switch ( $action ) {
		case 'lostpassword' :
		case 'retrievepassword' :
		$text = '<p class="login-description">'. __( 'Enter your email address to have your PIN emailed to you', 'dma' ) .'</p>';
		break;
		case 'login' :
		$title = __( 'DMA Friends Login', 'dma' );
		$text = '<p class="login-description">'. __( 'Log in with your e-mail or phone number and PIN code below', 'dma' ) .'</p>';
		break;
		case 'rp' :
		// add our numeric validation via JS
		add_action( 'login_footer', 'dma_add_pw_numeric_validation' );
		break;
	}

	$checkemail = isset($_REQUEST['checkemail']) ? $_REQUEST['checkemail'] : '';

	switch ( $checkemail ) {
		case 'confirm':
		case 'newpass':
			$title = __( 'PIN Update Sent', 'dma' );
	}
	// add our own <h1> (and hide other with css)
	$text = '
	<div class="login-title red">
		<h1>'. $title .'</h1>
		'. $text .'
	</div>
	';

	return $text;
}

add_filter( 'retrieve_password_title', 'dma_retrieve_password_title' );
/**
 * Replace default "reset password" email title
 */
function dma_retrieve_password_title( $title ) {
	return 'PIN Reset for DMA Friends';
}

add_action( 'retrieve_password', 'dma_store_user_login' );
/**
 * Stores $user_login for use in 'dma_retrieve_password_message'
 */
function dma_store_user_login( $user_login ) {
	$GLOBALS['dma_dashboard_user_login'] = $user_login;
}

add_filter( 'retrieve_password_message', 'dma_retrieve_password_message', 10, 2 );
/**
 * Replace default "reset password" email body text
 */
function dma_retrieve_password_message( $message, $key ) {
	// get user login from 'dma_store_user_login'
	$user_login = $GLOBALS['dma_dashboard_user_login'];
	// build our message
	$message = __( 'A request was made to re-set the PIN for your DMA Friends account.' ) ."\r\n\r\n";
	$message .= __( 'To reset your PIN, visit the following address:' ) ."\r\n";
	$message .= '<' . network_site_url( "wp-login.php?action=rp&key=$key&login=" . rawurlencode( $GLOBALS['dma_dashboard_user_login'] ), 'login' ) .">\r\n\r\n";
	$message .= __( 'If this was request was made in error or was not made by you, you may ignore this email and your PIN will remain unchanged.' ) ."\r\n\r\n";

	return $message;
}

// dummy for for styling
function dma_reset_pin_test() {
	?>
	<div id="login">
		<h1><a href="http://wordpress.org/" title="Powered by WordPress">DMA</a></h1>

		<div class="login-title red">
		<h1>Request New PIN</h1>

		<p class="message reset-pass"><?php _e('Enter your new password below.'); ?></p>
		</div>

		<form name="resetpassform" id="resetpassform" method="post">
			<p>
				<label for="pass1"><?php _e('New password') ?><br />
				<input type="password" name="pass1" id="pass1" class="input" size="20" value="" autocomplete="off" /></label>
			</p>
			<p>
				<label for="pass2"><?php _e('Confirm new password') ?><br />
				<input type="password" name="pass2" id="pass2" class="input" size="20" value="" autocomplete="off" /></label>
			</p>

			<div id="pass-strength-result" class="hide-if-no-js"><?php _e('Strength indicator'); ?></div>
			<p class="description indicator-hint"><?php _e('Hint: The password should be at least seven characters long. To make it stronger, use upper and lower case letters, numbers and symbols like ! " ? $ % ^ &amp; ).'); ?></p>

			<br class="clear" />
			<p class="submit"><input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="<?php esc_attr_e('Reset Password'); ?>" /></p>
		</form>

		<p id="nav"><a href="http://dma.wdslab.com/wp-login.php">Log in</a></p>

		<p id="backtoblog"><a href="http://dma.wdslab.com/" title=
		"Are you lost?">Cancel</a></p>
	</div>
	<?php
}

function dma_add_pw_numeric_validation() {
	?>
	<script type="text/javascript">
		document.getElementById('pass1').setAttribute('pattern', '[0-9]*');
		document.getElementById('pass2').setAttribute('pattern', '[0-9]*');
	</script>
	<?php
}
