<?php
/**
 * BadgeStack Bootstrap
 *
 * @package BadgeStack
 */

/**
 * Plugin Name: BadgeOS
 * Plugin URI: http://www.badgestack.com/
 * Description: Plugin for creating and awarding badges
 * Author: LearningTimes
 * Version: 0.1
 * Author URI: http://www.badgestack.com/
 * License: GPLv2
 */
  
class BadgeStack {

	function __construct() {
		// Define plugin constants
		$this->version = '0.1'; 
		$this->basename = plugin_basename( __FILE__ );
		$this->directory_path = plugin_dir_path( __FILE__ );
		$this->directory_url = plugins_url( 'badge-plugin/' );

		load_plugin_textdomain( 'badgestack', false, 'badge-plugin/languages' ); // load translated strings
		register_activation_hook( __FILE__, array( $this, 'activate' ) ); // plugin activation actions
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

		add_action( 'plugins_loaded', array( $this, 'includes' ) );

		//include Posts to Posts core
		require( $this->directory_path . 'includes/p2p/load.php' );
	}

	/**
	 * Load the rest of BadgeStack.
	 *
	 */
	function includes() {
		//and fire!
		require_once( $this->directory_path . 'includes/core.php' );
		require_once( $this->directory_path . 'includes/post-types.php' );
		require_once( $this->directory_path . 'includes/admin-settings.php' );
		require_once( $this->directory_path . 'includes/functions.php' );
		require_once( $this->directory_path . 'includes/meta-boxes.php' );
		require_once( $this->directory_path . 'includes/shortcodes.php' );
		require_once( $this->directory_path . 'includes/content-filters.php' );
		require_once( $this->directory_path . 'includes/template-loader.php' );
		require_once( $this->directory_path . 'includes/submission-actions.php' );
		require_once( $this->directory_path . 'includes/rules-engine.php' );
		require_once( $this->directory_path . 'includes/open-badges.php' );
		require_once( $this->directory_path . 'includes/user.php' );
		require_once( $this->directory_path . 'includes/dummy-data.php' );

	}
	
	/**
	 * Activation hook for the plugin.
	 * 
	 * Creates placeholder pages for Badges and Steps.
	 *
	 */
	function activate() {
		$this->includes();

		//verify user is running WP 3.0 or newer
	    if ( version_compare( get_bloginfo( 'version' ), '3.0', '<' ) ) {
	        deactivate_plugins( plugin_basename( __FILE__ ) ); // Deactivate our plugin
	        wp_die( 'This plugin requires WordPress version 3.0 or higher.' );
	    }
		
		//create Steps badge type
		if ( ! get_page_by_title( 'Steps', 'OBJECT', 'achievement-type' ) ) {
			// Create post object
			$new_badge = array(
				'post_title' => 'Steps',
				'post_content' => 'Steps badge type',
				'post_status' => 'publish',
				'post_author' => 1,
				'post_type' => 'achievement-type',
			);

			wp_insert_post( $new_badge );
		}
		
		//create Badges badge type
		if ( !get_page_by_title( 'Badges', 'OBJECT', 'achievement-type' ) ) {
			// Create post object
			$new_badge = array(
				'post_title' => 'Badges',
				'post_content' => 'Badges badge type',
				'post_status' => 'publish',
				'post_author' => 1,
				'post_type' => 'achievement-type',
			);

			wp_insert_post( $new_badge );
		}

		// TODO: Also add dummy content for badges and steps

		// Save plugin defaults
		$options = get_option( 'badgestack_settings' );
		if ( ! is_array( $options ) )
			$options = array();
		
		// Save dashboard slug default option
		if ( ! isset( $options['dashboard_slug'] ) || ! $options['dashboard_slug'] )
			$options['dashboard_slug'] = 'badgestack/dashboard/';

		// Save user profile slug default option
		if ( ! isset( $options['user_profile_slug'] ) || ! $options['user_profile_slug'] )
			$options['user_profile_slug'] = 'badgestack/users';
		update_option( 'badgestack_settings', $options );

		badgestack_register_post_types();
		badgestack_add_rewrite_rules();
		flush_rewrite_rules();
	}

	/**
	 * Deactivation hook for the plugin.
	 * 
	 * 
	 *
	 */
	function deactivate() {
		global $wp_rewrite;
		flush_rewrite_rules();
	}

}

$GLOBALS['badgestack'] = new BadgeStack();
