<?php
/**
 * Plugin Name: DMA wp_mail() Filter
 * Plugin URI: http://WebDevStudios.com
 * Description: Filters wp_mail() to use "DMA Friends <friends@dma.org>" as "From" for outbound email.
 * Version: 0.1
 * Author: WebDevStudios
 * Author URI: http://WebDevStudios.com
 */

/**
 * Filter the "From" email address
 */
function dma_wp_mail_from() {
	return 'friends@dma.org';
}
add_filter( 'wp_mail_from', 'dma_wp_mail_from' );

/**
 * Filter the "From" name
 */
function dma_wp_mail_from_name() {
	return 'DMA Friends';
}
add_filter( 'wp_mail_from_name', 'dma_wp_mail_from_name' );

/**
 * Send a test email, triggered via querystring
 *
 * Append ?test_wpmail=CUSTOM&to=EMAIL@DOMAIN.COM to send
 * an email to email@domain.com with CUSTOM appended to
 * the subject line.
 */
function br_test_mail() {
	if ( isset( $_GET['test_wpmail'] ) ) {
		$to = isset( $_GET['to'] ) ? $_GET['to'] : 'brianrichards@webdevstudios.com';
		wp_mail( $to, 'wp_mail() test from DMA: ' . $_GET['test_wpmail'], 'just testing DMAs server' );
		trigger_error('testing wpmail, subject: ' . $_GET['test_wpmail'] . ' to: ' . $to );
	}
}
add_action( 'template_redirect', 'br_test_mail' );
