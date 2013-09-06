<?php
/**
 * Plugin Name: DMA Custom Login Authentication for BadgeOS
 * Plugin URI: http://WebDevStudios.com
 * Description: Handles authenticating user login via URL queries.
 * Version: 1.0
 * Author: WebDevStudios
 * Author URI: http://WebDevStudios.com
 */

class BadgeOS_Custom_Authentication {


	/**
	 * Initialize our login class
	 *
	 * @access public
	 * @return void
	 */

	static $defaults; // our default login arguments

	public function __construct() {

		self::get_defaults();

		// Add our listener function to init so we can capture login requests
		add_action( 'init', array( &$this, 'login' ), 999 );
		add_action( 'init', array( &$this, 'authenticate' ) );
		add_filter( 'authenticate', array( &$this, 'authentication_filter' ), 999, 3 );
		add_filter( 'badgeos_logout_url', array( &$this, 'logout_with_location_id' ) );

		// Load up our login/logout pieces
		add_action( 'wp_footer', array( &$this, 'authentication_error' ) );

		// Redirect to homepage on logout
		add_action( 'wp_logout', array( &$this, 'logout' ) );
	}

	/**
	 * Determine if current user's IP is whitelisted
	 *
	 * @since  1.0
	 * @return boolean True if user's IP address is inside our specified range, false otherwise
	 */
	function is_ip_whitelisted() {

		// Setup our IP range
		$range_start = ip2long( apply_filters( 'dma_iprange_start',  '66.195.106.0' ) );
		$range_end   = ip2long( apply_filters( 'dma_iprange_end',    '66.195.106.255' ) );
		$ip          = ip2long( apply_filters( 'dma_current_user_ip', $_SERVER['REMOTE_ADDR'] ) );

		// If we're inside the range
		if ($ip >= $range_start && $ip <= $range_end)
			return true;
		else
			return false;
	}

	/**
	 * Listens for a login attempt via querystring
	 *
	 * @since 1.0
	 * @return void
	 */
	public function authenticate() {

		// If someone is trying to authenticate, and the've supplied a username...
		if ( isset($_GET['authenticate']) ) {

			// If a session hasn't been started, start one now
			if ( ! isset( $_SESSION ) )
				session_start();

			// Push the username and dummy through for aunthentication
			$username = isset($_GET['username']) ? dma_get_username_from_id_scan( $_GET['username'] ) : '';
			$user = wp_authenticate( $username, 'password doesnt matter' );

			// If we succesfully authenticated...
			if ( ! is_wp_error( $user ) && is_object( $user ) ) {

				// Setup authentication hook for running other actions
				do_action( 'user_authenticated', $user->data->ID );

				// Set the proper auth cookie and redirect to the provided URL or homepage
				wp_set_auth_cookie( $user->data->ID );
				$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : site_url();
				wp_redirect( $redirect );
				exit;

			// Otherwise there was a problem, so redirect to our failed URL
			} else {
				wp_redirect( esc_url( apply_filters( 'badgeos_auth_error_url', site_url('?authentication_error') ) ) );
				exit;
			}
		}

	}

	/**
	 * Listens for a login attempt via custom form
	 *
	 * @since  1.0
	 */
	public function login() {

		// If someone is trying to authenticate, and the've supplied a username...
		if ( isset( $_POST['login'], $_POST['pin'] ) ) {

			// If a session hasn't been started, start one now
			if ( ! isset( $_SESSION ) )
				session_start();

			// Push the username and stock password (an md5 hash of their username) through for aunthentication
			$login = $_POST['login'];
			$password = $_POST['pin'];

			// If the absint of our login is zero, we're using an email. Otherwise, we're using a phone number
			if ( 0 == absint( $login ) )
				$user_data = get_user_by( 'email', $login);
			else {
				// Query for users based on the phone meta data
				$user_query = new WP_User_Query(
					array(
						'meta_key'    => 'phone',
						'meta_value'  => $login
					)
				);

				// Get the results from the query
				$users = $user_query->get_results();

				// Set our user_data to just the first user result
				$user_data = $users[0];
			}

			// Attempt to authenticate our user...
			$user = isset( $user_data->user_login ) ?wp_authenticate( $user_data->user_login, $password ) : false;

			// If we succesfully authenticated...
			if ( $user && ! is_wp_error( $user ) ) {

				// Set the proper auth cookie and redirect to the provided URL or homepage
				wp_set_auth_cookie( $user->data->ID );
				$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : site_url();
				wp_redirect( esc_url( apply_filters( 'badgeos_auth_success_url', $redirect ) ) );
				exit;

			// Otherwise, there was a problem, so redirect to our failed URL
			} else {
				wp_redirect( esc_url( apply_filters( 'badgeos_auth_error_url', site_url('?authentication_error') ) ) );
				exit;
			}
		}

	}

	/**
	 * Filters wp_authenticate so we can bypass the user password
	 *
	 * @since  1.0
	 * @param  null   $user     The $user object (null by default)
	 * @param  string $username The provided username
	 * @param  string $password The provided password
	 * @return object           The updated $user object
	 */
	public function authentication_filter( $user, $username, $password ) {

		if ( $this->is_ip_whitelisted() && isset( $_GET['authenticate'] ) && true == $_GET['authenticate'] ) {
			$userdata = get_user_by('login', $username );
			if ( $userdata )
				$user =  new WP_User($userdata->ID);
		}

		// Return our user object
		return $user;

	}

	/**
	 * Output an error if the user doesn't exist
	 *
	 * @since  1.0
	 */
	public function authentication_error() {

		// Only include our output if the user is logged in
		if ( is_front_page() && isset( $_GET['authentication_error'] ) ) {

			do_action( 'badgeos_auth_error' );
			$args = array(
				'id_wrap' => 'authentication_error',
				'title' => __( 'User Authentication Failed' ),
				'message' => __( 'There was an issue with either the username or password provided.' ),
				'close_link' => '#form_wrap',
				'button_class' => 'button error-confirm pop close-popup',
				'button_text' => __( 'OK' ),
			);

			$args = apply_filters( 'badgeos_auth_error_defaults', wp_parse_args( $args, $this->get_defaults() ) );

			// Concatenate our output
			$alert = '
			<div id="'. $args['id_wrap']. '" class="alert popup '. $args['class_wrap'] .'">
				'. ( $args['title'] ? '<'. $args['title_wrap'] .' class="'. esc_attr( $args['title_class'] ) .'">'. $args['title'] .'</'. $args['title_wrap'] .'>' : '' )
				.'
				<p>'. $args['message'] .'</p>
				<a href="'. esc_url( $args['close_link'] ) .'" class="'. esc_attr( $args['button_class'] ) .'">'. $args['button_text'] .'</a>
			</div>
			';

			// Display our output
			echo $alert;

		}
	}

	public static function get_defaults() {
		return self::$defaults = apply_filters( 'login_form_defaults', array(
			'echo'              => true,
			'action'            => esc_url( site_url( '/?login=true' ) ),
			'method'            => 'post',
			'redirect'          => ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], // Default redirect is back to the current page
			'title'             => 'DMA Login: ',
			'title_wrap'        => 'strong',
			'title_class'        => 'login-title',
			'label_username'    => __( 'Email or Mobile Phone' ),
			'label_password'    => __( 'PIN' ),
			'label_submit'      => __( 'Log In' ),
			'id_wrap'           => 'form_wrap',
			'id_form'           => 'loginform',
			'id_username'       => 'login',
			'id_password'       => 'pin',
			'id_submit'         => 'wp-submit',
			'button_class'		  => 'button button-primary',
			'class_wrap'        => 'loginform',
			'value_username'    => '',
			'value_password'    => '',
			'tabindex_username' => '10',
			'tabindex_password' => '20',
			'tabindex_submit'   => '30',
		) );
	}

	/**
	 * Provides a simple login form for use anywhere within WordPress. By default, it echoes
	 * the HTML immediately. Pass array('echo'=>false) to return the string instead.
	 *
	 * @since 1.0
	 * @param array $args Configuration options to modify the form output
	 * @return Void, or string containing the form
	 */
	static function login_form( $args = array() ) {

		$args = wp_parse_args( $args, self::get_defaults() );

		$form = apply_filters( 'before_login_form', '', $args ) . '
		<div id="' . $args['id_wrap'] . '" class="' . $args['class_wrap'] . '">
			<form name="' . $args['id_form'] . '" id="' . $args['id_form'] . '" action="' . $args['action'] . '" method="' . $args['method'] . '">
				' . apply_filters( 'login_form_top', '', $args ) . '
				'. ( $args['title'] ? '<' . $args['title_wrap'] . ' class="'. $args['title_class'] .'">' . $args['title'] . '</' . $args['title_wrap'] . '>' : '' ) . '
				<label for="' . esc_attr( $args['id_username'] ) . '">' . esc_html( $args['label_username'] ) . '</label>
				<input type="text" name="' . esc_attr( $args['id_username'] ) . '" id="' . esc_attr( $args['id_username'] ) . '" class="input" value="' . esc_attr( $args['value_username'] ) . '" size="20" tabindex="' . esc_attr( $args['tabindex_username'] ) . '" />
				<label for="' . esc_attr( $args['id_password'] ) . '">' . esc_html( $args['label_password'] ) . '</label>
				<input type="password" '. apply_filters( 'login_form_password_attributes', '', $args ) .' name="' . esc_attr( $args['id_password'] ) . '" id="' . esc_attr( $args['id_password'] ) . '" class="input" value="' . esc_attr( $args['value_password'] ) . '" size="20" tabindex="' . esc_attr( $args['tabindex_password'] ) . '" />
				<button type="submit" name="wp-submit" id="' . esc_attr( $args['id_submit'] ) . '" class="' . esc_attr( $args['button_class'] ) . '" tabindex="' . esc_attr( $args['tabindex_submit'] ) . '">' . esc_attr( $args['label_submit'] ) . '</button>
				<input type="hidden" name="authenticate" id="authenticate" value="true" />
				<input type="hidden" name="redirect" value="' . esc_attr( $args['redirect'] ) . '" />
				' . apply_filters( 'login_form_bottom', '', $args ) . '
			</form>
		</div><!-- #' . $args['id_wrap'] . ' -->
		' . apply_filters( 'after_login_form', '', $args );

		if ( $args['echo'] !== false )
			echo apply_filters( 'login_form', $form, $args );

		return apply_filters( 'login_form', $form, $args );
	}

	/**
	 * Adds custom redirection on logout
	 *
	 * @since 1.0
	 */
	public function logout() {
		wp_redirect( esc_url( apply_filters( 'badgeos_logout_url', site_url() ) ) );
		exit;
	}

	/**
	 * Sets logout URL to append the current DMA location ID
	 *
	 * @since  1.0
	 * @return string Full site URL, including "location_id" querystring parameter
	 */
	public function logout_with_location_id() {
		return site_url( '/?location_id=' . dma_get_current_location_id() );
	}

}

// Fire up our login class!
$badgeos_custom_authentication = new BadgeOS_Custom_Authentication;

/**
 * Wrapper function for BadgeOS_Custom_Authentication->login_form()
 * @param  array $args An array of arguments to pass through the function
 * @return [type]       [description]
 */
function dma_login_form( $args = array() ) {
	return BadgeOS_Custom_Authentication::login_form( $args );
}
