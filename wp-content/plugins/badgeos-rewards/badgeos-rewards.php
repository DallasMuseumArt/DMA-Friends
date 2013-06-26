<?php
/**
 * Plugin Name: BadgeOS Rewards
 * Plugin URI: http://WebDevStudios.com
 * Description: Extends BadgeOS to include purchasable rewards
 * Version: 1.0
 * Author: WebDevStudios
 * Author URI: http://WebDevStudios.com
 */


class BadgeOS_Rewards_Plugin {

	function __construct() {

		// Define plugin constants
		$this->version = '1.0';
		$this->basename = plugin_basename( __FILE__ );
		$this->directory_path = plugin_dir_path( __FILE__ );
		$this->directory_url = plugins_url( 'badgeos-rewards/' );

		// Register our custom post types
		add_action( 'init', array( $this, 'badgeos_rewards_register_custom_post_types' ) );
		add_action( 'plugins_loaded', array( $this, 'includes' ) );

	}

	function badgeos_rewards_register_custom_post_types() {

		register_post_type( 'badgeos-rewards', array(
			'labels'             => array(
				'name'               => 'Rewards',
				'singular_name'      => 'Reward',
				'add_new'            => 'Add New',
				'add_new_item'       => 'Add New Reward',
				'edit_item'          => 'Edit Reward',
				'new_item'           => 'New Reward',
				'all_items'          => 'Rewards',
				'view_item'          => 'View Reward',
				'search_items'       => 'Search Rewards',
				'not_found'          =>	'No Rewards found',
				'not_found_in_trash' => 'No  Rewards found in Trash',
				'parent_item_colon'  => '',
				'menu_name'          => 'Rewards',
			),
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_menu'       => 'badgestack_badgestack',
			'query_var'          => true,
			'rewrite'            => false,
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt' )
		) );

		p2p_register_connection_type(
			array(
				'name'  => 'badge-to-badgeos-rewards',
				'from'  => 'badge',
				'to'    => 'badgeos-rewards',
				'title' => 'Required Badges',
			)
		);

	}

	function includes() {

		require_once( $this->directory_path . 'includes/functions.php' );
		require_once( $this->directory_path . 'includes/dma_reward.php' );
		require_once( $this->directory_path . 'includes/email.php' );

	}

}

new BadgeOS_Rewards_Plugin();
