<?php
/**
 * Plugin Name: DMA Platform
 * Plugin URI: http://WebDevStudios.com
 * Description: Extends BadgeOS to include custom functionality for DMA
 * Version: 2.0.0
 * Author: WebDevStudios
 * Author URI: http://WebDevStudios.com
 */

class DMA {

	function __construct() {

		// Setup our constants
		$this->basename       = plugin_basename( __FILE__ );
		$this->directory_path = plugin_dir_path( __FILE__ );
		$this->directory_url  = plugin_dir_url( __FILE__ );

		// Grab all our necessary files
		add_action( 'init', array( $this, 'includes' ), 1 );

		// If BadgeOS is unavailable, deactivate our plugin
		add_action( 'admin_notices', array( $this, 'maybe_disable_plugin' ) );

		// Enqueue our necessary scripts for admin
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );

		// Enqueue our necessary scripts for the front-end
		add_action( 'wp_enqueue_scripts', array( $this, 'register_scripts' ) );

		// Register our custom P2P relationships
		add_action( 'init', array( $this, 'register_achievement_relationships' ) );

		// If no Location ID is set, ask user to set one.
		// add_action( 'get_header', array( $this, 'location_setup' ), 20 );

		// If the location has a redirect specified, redirect the user on login
		add_filter( 'badgeos_auth_success_url', array( $this, 'location_redirect_url' ) );

	}

	function includes() {
		if ( $this->meets_requirements() ) {
			// Include our CPTs and taxonomies
			require_once( $this->directory_path . '/custom-post-types.php' );
			require_once( $this->directory_path . '/custom-taxonomies.php' );
			require_once( $this->directory_path . '/custom-meta-boxes.php' );

			// Include our classes
			require_once( $this->directory_path . '/classes/dma_base.php' );
			require_once( $this->directory_path . '/classes/dma_badge.php' );
			require_once( $this->directory_path . '/classes/dma_user.php' );

			// Include our custom functions
			require_once( $this->directory_path . '/achievement-functions.php' );
			require_once( $this->directory_path . '/activity-functions.php' );
			require_once( $this->directory_path . '/checkin-functions.php' );
			require_once( $this->directory_path . '/logging-functions.php' );
			require_once( $this->directory_path . '/misc-functions.php' );
			require_once( $this->directory_path . '/rules-engine.php' );
			require_once( $this->directory_path . '/steps-ui.php' );
			require_once( $this->directory_path . '/user-functions.php' );
			require_once( $this->directory_path . '/user-registration.php' );
		}
	}

	function admin_scripts( $hook_suffix ) {

		// Load up our custom stuff
		wp_enqueue_style( 'dma-platform-admin', $this->directory_url . 'css/admin.css' );
		wp_enqueue_script( 'dma-platform-admin', $this->directory_url . 'js/admin.js', array( 'jquery-ui-sortable', 'jquery-ui-datepicker' ) );
		wp_localize_script( 'dma-platform-admin', 'myAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ))); // localize for AJAX calls

		// Load up Genesis Admin JS so we can have "check all/none" on our custom taxonomies
		// Note: we're forcing it in like this because by default it's only loaded if post supports Genesis SEO
		if ( 'post-new.php' == $hook_suffix || 'post.php' == $hook_suffix ) {
			genesis_load_admin_js();
		}

	}

	function register_scripts() {

		if ( ! is_user_logged_in() ) {
			wp_enqueue_script( 'dma-registration', $this->directory_url . 'js/dma-user-registration.js', array( 'jquery' ), '1.0' );
			wp_localize_script( 'dma-registration', 'myAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ))); // localize for AJAX calls
		} else {
			wp_enqueue_script( 'dma-platform', $this->directory_url . 'js/dma-platform.js', array( 'jquery' ), '1.0' );
			wp_localize_script( 'dma-platform', 'myAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ))); // localize for AJAX calls
		}
	}

	function get_link( $page = '' ) {

		if ( 'view-progress' == $page )
			return site_url( '/view-progress/' );
		elseif ( 'challenges' == $page )
			return site_url( '/view-progress/#challenges_panel' );
		elseif ( 'badges' == $page )
			return site_url( '/view-progress/#badges_panel' );
		elseif ( 'report-activity' == $page )
			return site_url( '/report-activity/' );
		elseif ( 'activity-logged' == $page )
			return site_url( '/activity-logged/');
		else
			return site_url();

	}

	/**
	 * Register custom Post 2 Post relationships for steps to badges.
	 *
	 * @since  1.0.0
	 */
	function register_achievement_relationships() {
		if ( $this->meets_requirements() ) {
			p2p_register_connection_type(
				array(
					'name'       => 'badge-to-badge',
					'from'       => 'badge',
					'to'         => 'badge',
					'reciprocal' => false,
					'title'      => array( 'from' => 'Required by', 'to' => 'Required Badges' )
				)
			);

			p2p_register_connection_type(
				array(
					'name'  => 'activity-to-step',
					'from'  => 'activity',
					'to'    => 'step',
					'title' => 'Required Activity',
				)
			);

			p2p_register_connection_type(
				array(
					'name'  => 'dma-event-to-step',
					'from'  => 'dma-event',
					'to'    => 'step',
					'title' => 'Required Event',
				)
			);

			p2p_register_connection_type(
				array(
					'name'      => 'dma-location-to-activity',
					'from'      => 'dma-location',
					'to'        => 'activity',
					'title'     => 'Associated Location(s)',
					'admin_box' => 'any'
				)
			);

			p2p_register_connection_type(
				array(
					'name'  => 'dma-location-to-badge',
					'from'  => 'dma-location',
					'to'    => 'badge',
					'title' => 'Associated Location(s)',
				)
			);

			p2p_register_connection_type(
				array(
					'name'  => 'dma-location-to-dma-event',
					'from'  => 'dma-location',
					'to'    => 'dma-event',
					'title' => 'Associated Location(s)',
				)
			);
		}
	}

	/**
	 * If no Location ID is set, remove all content and output our error
	 *
	 * @since  1.0.0
	 * @return void
	 */
	function location_setup() {

		// If we do NOT have a location set...
		if ( empty( $_SESSION['location_id'] ) ) {
			$do_redirect = apply_filters( 'dma_do_location_redirect', true ) ? true : false;
			if ( !is_page( 'location' ) && $do_redirect ) {
				// Redirect to the set kiosk location page
				wp_redirect( site_url( '/location/' ) );
				exit;
			}
		} else {
			// Redirect non-logged in users to homepage
			if ( ! is_user_logged_in() && ! is_front_page() ) { wp_redirect( site_url() ); exit; }
		}
	}

	/**
	 * Redirect users on login to the location's specified redirect url (or homepage if none set)
	 *
	 * @since  1.0.0
	 * @return string The intended URL
	 */
	function location_redirect_url( $login_success_url ) {
		return ( $redirect_url = get_post_meta( $_SESSION['location_id'], '_dma_location_redirect', true ) ) ? $redirect_url : $login_success_url;
	}


	/**
	 * Check if BadgeOS is available
	 *
	 * @since  1.0.0
	 * @return bool True if BadgeOS is available, false otherwise
	 */
	public static function meets_requirements() {

		if ( class_exists('BadgeOS') )
			return true;
		else
			return false;

	}

	/**
	 * Generate a custom error message and deactivates the plugin if we don't meet requirements
	 *
	 * @since 1.0.0
	 */
	public function maybe_disable_plugin() {

		if ( ! $this->meets_requirements() ) {
			// Display our error
			echo '<div id="message" class="error">';
			echo '<p>' . sprintf( __( 'BadgeOS DMA Platform Add-On requires BadgeOS and has been <a href="%s">deactivated</a>. Please install and activate BadgeOS and then reactivate this plugin.', 'badgeos-rewards' ), admin_url( 'plugins.php' ) ) . '</p>';
			echo '</div>';

			// Deactivate our plugin
			deactivate_plugins( $this->basename );
		}
	}

}
$GLOBALS['dma'] = new DMA();
