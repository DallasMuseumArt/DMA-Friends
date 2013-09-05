<?php
/**
 * Plugin Name: DMA Test WPMail
 * Plugin URI: http://WebDevStudios.com
 * Description: Extends BadgeOS to include custom functionality for DMA
 * Version: 0.1
 * Author: WebDevStudios
 * Author URI: http://WebDevStudios.com
 */

add_action( 'template_redirect', 'br_test_mail' );
function br_test_mail() {
	if ( isset( $_GET['test_wpmail'] ) ) {
		trigger_error('testing wpmail: ' . $_GET['test_wpmail']);
		wp_mail( 'brianrichards@webdevstudios.com', 'wpmail test from DMA: ' . $_GET['test_wpmail'], 'just testing DMAs server' );
	}
}
