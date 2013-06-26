<?php
/**
 * BadgeStack Core Initialization
 *
 * @package BadgeStack
 */

add_action( 'admin_enqueue_scripts', 'badgestack_admin_init_scripts' );

function badgestack_admin_init_scripts() {

	// TODO: we should only include these if we're on a BadgeStack admin page
	wp_enqueue_script( 'media-upload' );
	wp_enqueue_script( 'farbtastic' );
	wp_register_script( 'badgestack-admin-js', plugins_url( 'js/admin.js', dirname( __FILE__ ) ), array( 'jquery', 'media-upload', 'thickbox', 'farbtastic' ) );
	wp_register_style( 'badgestack-admin-styles', plugins_url( 'css/admin.css', dirname( __FILE__ ) ) );
	wp_enqueue_style( 'badgestack-admin-styles' );

	wp_print_scripts( array( 'sack' ) );
	$sack = 'var isack = new sack("' .site_url() .'/wp-admin/admin-ajax.php");';
	?>
	<script type="text/javascript">
	//<![CDATA[
		  //open close
		  function wds_open_close_specific_badges(selected,show_id){
			if (show_id == null) {
			    show_id = 'specific_badges';
			}
		  	if(document.getElementById(show_id).style.display == "none"){
				document.getElementById(show_id).style.display='';
			}else{
				document.getElementById(show_id).style.display='none';
			}
		  }
	//]]>
	</script>
	  <?php

}

add_action( 'admin_print_styles', 'badgestack_admin_init_styles' );

function badgestack_admin_init_styles() {
	wp_enqueue_style( 'farbtastic' );
}

// create custom plugin settings menu
add_action( 'admin_menu', 'badgestack_plugin_menu' );

function badgestack_plugin_menu() {

	//get minimum role setting for menus
	$badgestack_settings = get_option( 'badgestack_settings' );
	$minimum_role = ( !empty( $badgestack_settings['minimum_role'] ) ) ? $badgestack_settings['minimum_role'] : 'administrator';

	//create main menu
	add_menu_page( 'BadgeOS', 'BadgeOS', $minimum_role, 'badgestack_badgestack', 'badgestack_settings', plugins_url( 'images/badgestack_icon.png', dirname( __FILE__ ) ) );

	//create submenu items
	add_submenu_page( 'badgestack_badgestack', 'BadgeOS Settings', 'Settings', $minimum_role, 'badgestack_settings', 'badgestack_settings_page' );
	add_submenu_page( 'badgestack_badgestack', 'Add-Ons', 'Add-Ons', $minimum_role, 'badgestack_sub_add_ons', 'badgestack_add_ons_page' );
	add_submenu_page( 'badgestack_badgestack', 'Help / Support', 'Help / Support', $minimum_role, 'badgestack_sub_help_support', 'badgestack_help_support_page' );

}

add_action( 'wp_enqueue_scripts', 'badgestack_enqueue_styles' );

function badgestack_enqueue_styles() {
	wp_enqueue_style( 'badgestack', badgestack_get_directory() . 'css/main.css' );
}
