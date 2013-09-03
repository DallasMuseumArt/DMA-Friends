<?php
/**
* Plugin Name: BadgeOS DMA Print
* Plugin URI: http://WebDevStudios.com
* Description: Custom class for DMA printing
* Version: 1.0
* Author: WebDevStudios
* Author URI: http://WebDevStudios.com
*/

// Hook our ID printer to the custom user registration
// Note: $username is our DMA Friends ID (e.g. FR00000000)
add_action( 'dma_user_registration', 'dma_print_user_id', 10, 5 );
function dma_print_user_id( $user_id, $username, $first_name, $last_name, $location_printer_ip ) {

	require_once( 'poc/dmaprint.class.php' );
	$idcard = new dmaprintid( $first_name . ' ' . $last_name, $username, $location_printer_ip );
	$idcard->doPrint();

}

//Print a Reward voucher when redeeming a reward
add_action( 'dma_user_claimed_reward', 'dma_printer_reward_claim', 10, 3 );
function dma_printer_reward_claim( $user_id, $reward_id, $location_printer_ip ) {

	//load reward metadata
	$reward_fine_print = get_post_meta( absint( $reward_id ), '_dma_reward_fine_print', true );
	$reward_barcode = get_post_meta( absint( $reward_id ), '_dma_reward_barcode', true );
	$expire_days = get_post_meta( absint( $reward_id ), '_dma_reward_days_valid', true );

	//generate expires date based on days valid metadata
	$expires = date( 'm-d-Y', strtotime( "+" .absint( $expire_days )." days" ) );

	require_once( 'poc/dmaprint.class.php' );
	$coupon = new dmaprintcoupon( $reward_fine_print, $expires, $reward_barcode, $location_printer_ip );
	$coupon->doPrint();

}

// create custom plugin settings menu
add_action( 'admin_menu', 'badgeos_dma_print_menu', 99 );
function badgeos_dma_print_menu() {

	add_submenu_page( 'badgeos_badgeos', 'Print', 'Print', 'manage_options', 'badgeos_print_help', 'badgeos_dma_test_print_page' );

}


add_action( 'admin_init', 'badgeos_dma_test_print' );
function badgeos_dma_test_print() {

	if ( isset( $_POST['test_print'] ) ) {

		//nonce for security
		check_admin_referer( 'test-print-membership-card', 'dma-admin-print' );

		dma_print_user_id( 1, $_POST['number'], 'John', 'Doe', $_POST['printer'] );
	}

	if ( isset( $_POST['test_reward'] ) ) {

		//nonce for security
		check_admin_referer( 'test-print-reward-card', 'dma-admin-print' );

		dma_printer_reward_claim( 1, $_POST['reward'], $_POST['printer'] );
	}

	if ( isset( $_POST['reprint_user_card'] ) ) {

		//nonce for security
		check_admin_referer( 'print-membership-card', 'dma-admin-print' );

		$user_id = $_POST['user_id'];
		$user_info = get_userdata( absint( $user_id ) );
		$username = $user_info->user_login;
		$first_name = $user_info->user_firstname;
		$last_name = $user_info->user_lastname;
		$location_printer_ip = $_POST['printer'];

		dma_print_user_id( $user_id, $username, $first_name, $last_name, $location_printer_ip );

	}

}

function badgeos_dma_test_print_page() {
	?>
	<h2>Test ID Card Printing</h2>
	<form method="post">
		<?php wp_nonce_field( 'test-print-membership-card', 'dma-admin-print' ); ?>
		<input type="hidden" name="printtype" value="idcard" />

		<label for="name">User Name</label>
		<input type="text" name="name"></input><br/>

		<label for="number">User ID Number</label>
		<input type="text" name="number"></input><br/>

		<label for="printer">Printer</label>
		<input type="text" name="printer" value="192.168.91.89" /><br/>

		<input type="submit" name="test_print" value="Print Test ID Card" />
	</form>

	<h2>Test ID Reward Printing</h2>
	<form method="post">
		<?php wp_nonce_field( 'test-print-reward-card', 'dma-admin-print' ); ?>
		<input type="hidden" name="printtype" value="idcard" />

		<label for="name">Reward</label>
		<select name="reward">
			<?php
			$args = array(
				'post_type'		=>	'badgeos-rewards',
				'post_status'	=>	'publish',
				'orderby'		=>	'title',
				'order'			=>	'ASC',
				'numberposts'	=>	'-1'
			);

			$the_ads = get_posts( $args );
			foreach ( $the_ads as $ad ) {

				echo '<option value="'.$ad->ID.'">'.$ad->post_title.'</option>';

			}
			?>
		</select><br />
		<label for="printer">Printer</label>
		<input type="text" name="printer" value="192.168.91.89" /><br/>

		<input type="submit" name="test_reward" value="Print Test Reward" />
	</form>

	<h2>Reprint User Membership Card</h2>
	<form method="post">
		<?php wp_nonce_field( 'print-membership-card', 'dma-admin-print' ); ?>
		<input type="hidden" name="printtype" value="idcard" />

		<label for="name">Username</label>

		<select name="user_id">
			<?php
			$users = get_users( 'orderby=nicename' );
			foreach ( $users as $user ) {
				echo '<option value="'.$user->ID.'">' . $user->user_login . '</option>';
			}
			?>
		</select><br />
		<label for="printer">Printer</label>
		<input type="text" name="printer" value="192.168.91.89" /><br/>

		<input type="submit" name="reprint_user_card" value="Reprint Card" />
	</form>

	<?php

}
