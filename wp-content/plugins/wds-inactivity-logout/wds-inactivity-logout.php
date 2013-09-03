<?php
/**
 * Plugin Name: WDS Inactivity Logout
 * Plugin URI: http://WebDevStudios.com
 * Description: Automatically logs a user out after a set amount of inactivity.
 * Version: 1.0
 * Author: WebDevStudios
 * Author URI: http://WebDevStudios.com
 */

class WDS_Inactivity_Logout {

	/**
	 * Initialize our login class
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {

		add_action( 'wp_footer', array( &$this, 'logout_timer' ) );
		add_action( 'wp_enqueue_scripts', array( &$this, 'enqueue_scripts' ) );
		add_action( 'badgeos_settings', array( &$this, 'timer_settings' ) );

	}

	/**
	 * Regesters and enqueues the scripts for our plugin
	 *
	 * @since 1.0
	 * @return void
	 */
	public function enqueue_scripts() {
		// Register our scripts
		wp_register_script('jquery-idle-timer', plugins_url( '/js/jquery.idle-timer.js', __FILE__ ), array( 'jquery' ), '1.0', true );
		wp_register_script('logout-timer', plugins_url( '/js/logout-timer.js', __FILE__ ), array( 'jquery-idle-timer' ), '1.0', true );

		// Setup our configurable data
		$timer_settings = get_option( 'badgeos_settings' );
		$timer_data = array(
			'logout_alert_container'     => '#idletimeout',
			'coutndown_container'        => '#idletimeout-countdown',
			'logged_in_idle_timer'       => isset( $timer_settings['logged_in_idle_timer'] ) ? $timer_settings['logged_in_idle_timer'] : 20,
			'logged_out_idle_timer'      => isset( $timer_settings['logged_out_idle_timer'] ) ? $timer_settings['logged_out_idle_timer'] : 300,
			'logged_in_countdown'        => isset( $timer_settings['logged_in_countdown'] ) ? $timer_settings['logged_in_countdown'] : 30,
			'extra_seconds'              => isset( $timer_settings['extra_seconds'] ) ? $timer_settings['extra_seconds'] : 10,
			'logged_out_countdown'       => 1,
			'logout_url'                 => wp_logout_url(),
			'keepalive_url'              => '',
			'expired_message'            => '<h1>You have been logged out.</h1>',
			'is_user_logged_in'          => is_user_logged_in(),
			'inactivity_logout_enabled'  => apply_filters( 'inactivity_logout_enabled', true )
		);

		// Enqueue our script and load in our data
		wp_enqueue_script( 'logout-timer' );
		wp_localize_script( 'logout-timer', 'logout_data', $timer_data );

	}

	/**
	 * Output a logout warning to idle users
	 *
	 * @since  1.0
	 * @return void
	 */
	public function logout_timer() {

		// Only include our output if the user is logged in
		if ( is_user_logged_in() ) {

			// Concatenate our output
			$alert = '';
			$alert .= '<div id="idletimeout" class="alert logout popup ltd">';
				// $alert .= '<h1>Are you still there?</h1>';
				// $alert .= '<p>You will automatically be logged out for inactivity in <strong id="idletimeout-countdown">0</strong> seconds.</p>';
				// $alert .= '<a href="#" class="button stay-logged-in submit primary">I\'m Still Here</a>';
				// $alert .= '<a href="' . wp_logout_url() . '" class="button secondary logout default">Log Out</a>';

				$alert .= '<form class="pin-entry" method="post" action="">';
					$alert .= '<h1>Are you still there?</h1>';
					$alert .= '<p>For security, please enter your PIN to remain logged in.<br/>You will automatically be logged out for inactivity in <strong id="idletimeout-countdown">0</strong> seconds.</p>';
					$alert .= '<input type="password" pattern="[0-9]*" name="inactivity_pin" id="inactivity_pin" value="" placeholder="Enter your PIN" />';
					$alert .= '<input type="hidden" name="user_id" id="user_id" value="' . get_current_user_id() . '" />';
					$alert .= '<p class="validation-error error"></p>';
					$alert .= '<button type="submit" class="button stay-logged-in submit primary">Stay Logged In</button>';
					$alert .= '<a href="' . wp_logout_url() . '" class="button secondary logout default">Log Out</a>';
				$alert .= '</form>';

			$alert .= '</div>';

			// Display our output
			echo $alert;

		}
	}

	/**
	 * Adds an admin option section in the BadgeOS options page for setting the logout timer
	 *
	 * @since 1.0
	 * @return void
	 */
	public function timer_settings( $timer_settings ) {
		?>
		<tr><td colspan="2"><hr/><h2>Inactivity Timer Settings</h2></td></tr>
		<tr valign="top">
			<th scope="row"><label for="logged_out_idle_timer"><?php _e( 'Logged-out Idle Timer', 'wds-inactivity' ); ?></label></th>
			<td>
				<input id="logged_out_idle_timer" type="text" name="badgeos_settings[logged_out_idle_timer]" value="<?php echo isset( $timer_settings['logged_out_idle_timer'] ) ? $timer_settings['logged_out_idle_timer'] : 300; ?>" /><BR>
				<p class="description">The amount of time (in seconds) before refreshing the logged-out homepage.</p>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="logged_in_idle_timer"><?php _e( 'Logged-in Idle Timer', 'wds-inactivity' ); ?></label></th>
			<td>
				<input id="logged_in_idle_timer" type="text" name="badgeos_settings[logged_in_idle_timer]" value="<?php echo isset( $timer_settings['logged_in_idle_timer'] ) ? $timer_settings['logged_in_idle_timer'] : 20; ?>" /><BR>
				<p class="description">The amount of time (in seconds) before an inactivity modal is displayed to logged-in users.</p>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="logged_in_countdown"><?php _e( 'Logged-in Countdown', 'wds-inactivity' ); ?></label></th>
			<td>
				<input id="logged_in_countdown" type="text" name="badgeos_settings[logged_in_countdown]" value="<?php echo isset( $timer_settings['logged_in_countdown'] ) ? $timer_settings['logged_in_countdown'] : 30; ?>" /><BR>
				<p class="description">The amount of time (in seconds) a user has to respond to the inactivity modal.</p>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="extra_seconds"><?php _e( 'Extra Time for Failure', 'wds-inactivity' ); ?></label></th>
			<td>
				<input id="extra_seconds" type="text" name="badgeos_settings[extra_seconds]" value="<?php echo isset( $timer_settings['extra_seconds'] ) ? $timer_settings['extra_seconds'] : 30; ?>" /><BR>
				<p class="description">Additional seconds a user gains for an incorrect PIN.</p>
			</td>
		</tr>
		<?php
	}

}

// Fire up our inactivity class!
$wds_inactivity_logout = new WDS_Inactivity_Logout;
