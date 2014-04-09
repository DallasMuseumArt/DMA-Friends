<?php
/**
 * Plugin Name: DMA Platform
 * Plugin URI: http://WebDevStudios.com
 * Description: Extends BadgeOS to include custom functionality for DMA
 * Version: 2.0.0
 * Author: WebDevStudios
 * Author URI: http://WebDevStudios.com
 */

require('vendor/autoload.php');

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
			require_once( $this->directory_path . '/location-functions.php' );
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

	/**
	 * Initialize some defaults that we need when installing the plugin
	 */
	public function install() {
		self::create_tables();
		self::create_default_required_pages();
	}

	public function create_tables() {
		global $wpdb;
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		// Create the new Log Entries table
		$log_table_name = $wpdb->prefix . 'dma_log_entries';
		$log_table_sql =
		"
		CREATE TABLE $log_table_name (
			ID bigint(20) unsigned NOT NULL auto_increment,
			user_id bigint(20) unsigned NOT NULL default '0',
			object_id bigint(20) unsigned NOT NULL default '0',
			action VARCHAR( 100 ) NOT NULL,
			title text NOT NULL,
			artwork_id VARCHAR( 50 ) NOT NULL,
			awarded_points bigint(20) NOT NULL default '0',
			total_points bigint(20) NOT NULL default '0',
			admin_id bigint(20) unsigned NOT NULL default '0',
			timestamp datetime NOT NULL default '0000-00-00 00:00:00',
			PRIMARY KEY  (ID),
			INDEX (  ID , user_id , object_id, action )
		);
		";
		dbDelta( $log_table_sql );

		// Create the new Activity Stream table
		$activity_table_name = $wpdb->prefix . 'dma_activity_stream';
		$activity_table_sql =
		"
		CREATE TABLE $activity_table_name (
			ID bigint(20) unsigned NOT NULL auto_increment,
			user_id bigint(20) unsigned NOT NULL default '0',
			object_id bigint(20) unsigned NOT NULL default '0',
			action VARCHAR( 100 ) NOT NULL,
			artwork_id VARCHAR( 50 ) NOT NULL,
			timestamp datetime NOT NULL default '0000-00-00 00:00:00',
			PRIMARY KEY  (ID),
			INDEX (  ID , user_id , object_id, action )
		);
		";
		dbDelta( $activity_table_sql );
	}

	/**
	 * Helper function to auto-create theme's required pages
	 */
	function create_default_required_pages() {

		require_once( plugin_dir_path( __FILE__ ) . '/misc-functions.php' );

		// Check if pages have already been created (uncomment when all pages have been created)
		// if ( get_option( 'dma_default_pages_created' ) ) return;

		// $image = DMA_MAIN_URI . '/images/DMA-tour.jpg';
		$what_is = '<p>Friends is a FREE program that allows you to discover new and fun activities at the museum. The Museum has created bundles of activities, called badges, that are awarded to Friends who really plug in and make the DMA a vibrant place to be. Badges can give you new ideas about ways to use the Museum that you’ve never thought of before. Earning badges unlocks special rewards and recognition like free tickets, behind-the-scenes tours, discounts on shopping and dining, and access to exclusive experiences at the Museum. We love our Friends and want to make sure they feel appreciated, so get the most from your visit by joining the FREE Friends program today. If you’re already a Partner, simply use your Partner card to log in and we will link your accounts together.</p>';

		$tos = '
	Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nunc ut tortor nunc. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Integer rhoncus pretium erat ut pulvinar. Donec sed libero quis ipsum interdum tempor. In est nunc, ultricies id ullamcorper vel, tincidunt quis purus. Praesent vulputate congue lacus, quis fringilla dui condimentum in. Donec dui purus, hendrerit eu fermentum sit amet, hendrerit placerat elit. Sed arcu justo, placerat vehicula dictum eu, venenatis eget arcu. Aenean vitae neque vel orci pretium venenatis. Morbi pretium sagittis odio non mattis. Nam blandit luctus orci non ultrices. Fusce congue eleifend sem, vel bibendum felis viverra in.

	Suspendisse iaculis lacus quis arcu tincidunt lacinia. Curabitur consectetur, enim id congue fermentum, dui magna commodo magna, id varius leo enim sit amet dolor. Nullam semper, nunc vel aliquam scelerisque, dui sapien condimentum urna, sit amet mattis nisl lorem sed eros. In ultrices, nisl ac ullamcorper rutrum, nunc elit tincidunt arcu, ut euismod lorem elit lobortis eros. Sed urna sem, pharetra fermentum pellentesque vel, auctor ac eros. Phasellus a nisi arcu, vitae accumsan lorem. Quisque aliquet neque at mi volutpat condimentum. Aenean at eros consequat arcu viverra gravida sed vel est. Donec turpis nisl, tempor a fringilla ut, pretium nec nulla. Sed dapibus mattis nibh quis mollis. Nullam et pharetra massa. Quisque velit eros, egestas quis semper eget, dictum in mauris. Donec commodo, nibh at mollis tincidunt, velit velit ultricies metus, et hendrerit ante purus eu velit. Donec mollis neque at quam fermentum nec interdum quam venenatis. Phasellus ac nisl sit amet ante consectetur posuere. Donec eget enim sem, eget malesuada massa.

	Cras felis dui, lacinia in lacinia vitae, ullamcorper sit amet eros. Nam vel dictum sem. Sed ut dolor nisl, in commodo arcu. Sed quis elit urna, id facilisis diam. Suspendisse malesuada nulla id lectus imperdiet eu sollicitudin enim condimentum. Nulla bibendum ante lacus, non rhoncus nisl. Nulla congue, tellus a iaculis porttitor, libero eros pulvinar enim, et luctus massa turpis vel metus.

	Nullam vitae massa odio, at dapibus erat. Quisque quis ante nec nisl hendrerit laoreet ut quis orci. In convallis arcu a nunc ornare suscipit. Mauris eget augue vitae arcu mollis fringilla. Mauris euismod auctor volutpat. Maecenas porta tortor at lorem elementum convallis. Sed dui mi, ultrices non convallis id, faucibus a mauris. Fusce sed sem et justo tristique tincidunt eu posuere est.

	Sed porttitor nulla at velit dapibus blandit. Ut lorem nisi, sodales eu porta vel, egestas quis ligula. Suspendisse porttitor hendrerit dictum. Aenean ac lacus tortor, quis blandit tellus. Curabitur dignissim metus at nunc iaculis sit amet tempus eros consequat. Donec eros augue, dignissim non scelerisque et, adipiscing eget mi. Cras pulvinar, nisi non consectetur sagittis, lectus eros rutrum quam, vel ullamcorper tortor est et sem.';

		// Set up our required pages
		$required_pages = array(
			'Location' => array(),
			'Register' => array(),
			'Members' => array(),
			'Dashboard' => array(),
			'Activity Logged' => array(),
			'My Activity Stream' => array( 'post_name' => 'activity-stream' ),
			'Activate' => array(),
			'My Badges' => array( 'post_name' => 'my-badges' ),
			'Get Rewards' => array( 'post_name' => 'get-rewards' ),
			'Profile' => array(),
			'What is Credly?' => array(),
			'What is Friends?' => array( 'post_content' => $what_is ),
			'Accept Terms of Service' => array( 'post_content' => $tos ),
		);
		$page_ids = array();
		// loop through our required pages, adding them to WordPress
		foreach ( $required_pages as $page_title => $page_params ) {
			// Check if our page already exists
			$page_exists = get_page_by_title( $page_title );
			// If not, create our page
			if ( ! $page_exists ) {
				$args['post_title'] = $page_title;
				$page_params = array_merge( $page_params, $args );
				$slug = sanitize_title( $page_params['post_title'] );
				$page_id = dma_add_page( $page_params );
			}
		}

		update_option( 'show_on_front', 'page' );
		// Set our front page
		$dashboard = get_page_by_title( 'dashboard' );
		if ( $dashboard && isset( $dashboard->ID ) )
			update_option( 'page_on_front', $dashboard->ID );
		// Set our BuddyPress pages
		if ( function_exists( 'bp_update_option' ) ) {
			$bp_slugs = array( 'activity', 'members', 'register', 'activate' );
			$bp_ids = array();
			foreach ( $bp_slugs as $key => $title ) {
				$page = get_page_by_title( $title );
				if ( $page && isset( $page->ID ) )
					$bp_ids[$title] = $page->ID;
			}
			if ( !empty( $bp_ids ) )
				bp_update_option( 'bp-pages', $bp_ids );
		}
		// Set this option will keep this function from running again
		update_option( 'dma_default_pages_created', true );
	}

}
$GLOBALS['dma'] = new DMA();

register_activation_hook( __FILE__, array( 'DMA', 'install' ) );

/**
 * Override default factory classes
 */
function dma_badgeos_settings() {
    $badgeos_settings = get_option( 'badgeos_settings' );
    if ( $badgeos_settings['log_factory'] != 'DMA\DmaLog') {
        $badgeos_settings['log_factory'] = 'DMA\DmaLog';
        update_option( 'badgeos_settings', $badgeos_settings );
    }   
}
add_action( 'init', 'dma_badgeos_settings' );
