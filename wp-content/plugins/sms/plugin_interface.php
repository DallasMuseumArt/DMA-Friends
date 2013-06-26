<?php
include('sms_widgets.php');
add_action('admin_menu', 'sms_create_menu');
add_action("admin_print_styles", 'sms_adminCsslibs');
add_action('wp_enqueue_scripts', 'sms_client_js');
add_action("wp_enqueue_style", 'sms_client_css');
add_action('widgets_init', create_function( '', 'register_widget( "SMS_Widget" );' ) );
add_shortcode('SMS',  'sms_shortcode');

function sms_client_js() {
	wp_register_style( 'sms_client_css', plugins_url('/css/sms.css', __FILE__));
	wp_enqueue_style( 'sms_client_css' );	
	wp_enqueue_script('jquery');	
	wp_register_script('sms_cookie_js',plugins_url('/js/jquery.cookie.js', __FILE__));		
	wp_enqueue_script('sms_cookie_js');	
	wp_register_script('sms_validate_js',plugins_url('/js/jquery.validate.min.js', __FILE__));		
	wp_enqueue_script('sms_validate_js');		
}

function sms_adminCsslibs(){
	wp_register_style('sms-admin-style', plugins_url('/css/sms_admin.css', __FILE__));
    wp_enqueue_style('sms-admin-style');
}
		
function sms_create_menu() {
	add_options_page('SMS', 'SMS', 'administrator', __FILE__, 'sms_settings_page');	
}

function sms_settings_page() {
	include('includes/sms_header.php');
	include('includes/sms_footer_text.php');
	include('includes/sms_stat.php');
	include('includes/sms_footer.php');
}

function sms_shortcode(){
	$sms_section_from	= 'shortcode';
	include('includes/sms_client_widget.php');	
}

function smsCreateRandomCode() {
     $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890";
     srand((double)microtime()*1000000);
     $i = 0;
     $pass = '' ;
     while ($i <= 16) {
        $num = rand() % 39;
        $tmp = substr($chars, $num, 1);
        $pass = $pass . $tmp;
        $i++;
     }
	return $pass.date('ymdh');
}
?>