<?php
/*
Plugin Name: AutoChimp
Plugin URI: http://www.wandererllc.com/company/plugins/autochimp/
Description: Keep MailChimp mailing lists in sync with your WordPress site.  AutoChimp supports many WordPress plugin profile extenders like WP-Members, Wishlist, BuddyPress, and Cimy User Extra fields. MailChimp also gives users the ability to create MailChimp mail campaigns from blog posts with the flexibility of sending different categories to different lists and interest groups.  You can use your user-defined templates as well.  NOTE:  <a href="http://www.wandererllc.com/company/2013/07/problems-with-autochimp-2-10/" target="_blank">AutoChimp 2.10 and up requires PHP 5.3.x or higher</a>. Please update (here is <a href="http://support.hostgator.com/articles/hosting-guide/hardware-software/php-5-3">an example</a> for HostGator users) if you are unable to activate the plugin.
Author: Wanderer LLC Dev Team
Version: 2.15
*/

//
//	When making changes to AutoChimp, keep in mind that MailChimp best practices for 
//	developers and integrators:  http://apidocs.mailchimp.com/api/faq/#faq5
//

if ( !class_exists( 'MCAPI_13' ) )
{
	require_once 'inc/MCAPI.class.php';
}
require_once 'autochimp-helpers.php';	// General helper functions
require_once 'autochimp-plugins.php';	// Plugin class framework

// Set the time zone to UTC
date_default_timezone_set('UTC');

define( "WP88_MC_APIKEY", "wp88_mc_apikey" );

// Mailing List
define( "WP88_MC_LISTS", "wp88_mc_selectedlists" );
define( "WP88_MC_ADD", "wp88_mc_add" );
define( "WP88_MC_DELETE", "wp88_mc_delete" );
define( "WP88_MC_UPDATE", "wp88_mc_update" );
define( 'WP88_MC_BYPASS_OPT_IN', 'wp88_mc_bypass_opt_in' );
define( 'WP88_MC_PERMANENTLY_DELETE_MEMBERS', 'wp88_mc_permanently_delete_member' );
define( 'WP88_MC_SEND_GOODBYE', 'wp88_mc_send_goodbye' );
define( 'WP88_MC_SEND_ADMIN_NOTIFICATION', 'wp88_mc_send_admin_notification' );
define( "WP88_MC_LAST_MAIL_LIST_ERROR", "wp88_mc_last_ml_error" );
define( "WP88_MC_MANUAL_SYNC_PROGRESS", "wp88_mc_ms_progress" );
define( "WP88_MC_MANUAL_SYNC_STATUS", "wp88_mc_ms_status" );

// Campaigns
define( "WP88_MC_CAMPAIGN_FROM_POST", "wp88_mc_campaign_from_post" );	// Unused as of 2.0
define( "WP88_MC_CAMPAIGN_CATEGORY", "wp88_mc_campaign_category" );		// Unused as of 2.0
define( "WP88_MC_CAMPAIGN_EXCERPT_ONLY", "wp88_mc_campaign_excerpt_only" );
define( "WP88_MC_CREATE_CAMPAIGN_ONCE", "wp88_mc_create_campaign_once" );
define( "WP88_MC_SEND_NOW", "wp88_mc_send_now" );
define( "WP88_MC_LAST_CAMPAIGN_ERROR", "wp88_mc_last_error" );
define( "WP88_MC_CAMPAIGN_CREATED", "wp88_mc_campaign" ); // Flags a post that it's had a campaign created from it.

// Plugin integration
define( 'WP88_MC_FIX_REGPLUS', 'wp88_mc_fix_regplus' );
define( 'WP88_MC_FIX_REGPLUSREDUX', 'wp88_mc_fix_regplusredux' );

// NOTE: The following two static defines shouldn't have anything to do with
// BuddyPress, but they do: they were introduced when the BuddyPress sync feature
// was written.  But, remember, these are always used even on additional
// plugins that are introduced.  It's since been moved away from BuddyPress and
// made part of the standard WordPress mappings.
define( 'WP88_MC_STATIC_TEXT', 'wp88_mc_bp_static_text' );
define( 'WP88_MC_STATIC_FIELD', 'wp88_mc_bp_static_field' );

define( 'MMU_ADD', 1 );
define( 'MMU_DELETE', 2 );
define( 'MMU_UPDATE', 3 );

define( 'WP88_SEARCHABLE_PREFIX', 'wp88_mc' );
define( 'WP88_WORDPRESS_FIELD_MAPPING', 'wp88_mc_wp_f_' );
define( 'WP88_CATEGORY_LIST_MAPPING', 'wp88_mc_category_list_' );		// Unused as of 2.02
define( 'WP88_CATEGORY_MAPPING_PREFIX', 'wp88_mc_catmap_' );			// Used instead of WP88_CATEGORY_LIST_MAPPING
define( 'WP88_PLUGIN_FIRST_ACTIVATION', 'wp88_mc_first_activation' );
define( 'WP88_IGNORE_2_0_NOTICE', 'ac_20_ignore_notice' );				// Deprecated as of 2.10
define( 'WP88_IGNORE_2_1_NOTICE', 'ac_21_ignore_notice' );
define( 'WP88_CATEGORY_SUFFIX', '_category' );
define( 'WP88_LIST_SUFFIX', '_list' );
define( 'WP88_GROUP_SUFFIX', '_group' );
define( 'WP88_TEMPLATE_SUFFIX', '_template' );
define( 'WP88_DELETE_MAPPING_SUFFIX', '_delete_mapping' );
// Control names for the mapping rows
define( 'WP88_CATEGORY_CONTROL_PREFIX', 'wp88_categories_select_' );
define( 'WP88_LIST_CONTROL_PREFIX', 'wp88_lists_select_' );
define( 'WP88_GROUP_CONTROL_PREFIX', 'wp88_groups_select_' );
define( 'WP88_TEMPLATE_CONTROL_PREFIX', 'wp88_templates_select_' );

define( 'WP88_IGNORE_FIELD_TEXT', 'Ignore this field' );
define( 'WP88_NONE', 'None' );
define( 'WP88_ANY', 'Any' );
define( 'WP88_GROUPINGS_TEXT', 'GROUPINGS' ); // This value is required by MailChimp
define( 'WP88_FIELD_DELIMITER', '+++' );

//
// Autoload for plugin scripts.  This function is part of the simple platform that
// allows third party developers to add support for other plugins without having to
// wait on an AutoChimp release.
//
// The class name should also match the name for the file that houses the class. 
// Names are case sensitive!
//
function __autoload( $class )
{
	// All plugin scripts are required to be placed in the 'plugins' subfolder and 
	// follow the proper naming convention.  See Help for more.
	if ( 0 === strpos( $class, 'Sync') ||  0 === strpos( $class, 'Publish') ||  0 === strpos( $class, 'Content') )
	{
		// Check that there's not a '.' already in the file (like for a .js file).
		// Don't require those, obviously.
		if ( FALSE === strpos( $class, '.') )
			include_once( plugin_dir_path( __FILE__ ) . 'plugins/' . $class . '.php' );
			//require_once( 'plugins/' . $class . '.php' );
	}
}

// Global variables - If you change this, be sure to see AC_FetchMappedWordPressData()
// which has static comparisons to the values in this array.  FIX LATER.
$wpUserDataArray = array( 'Username', 'Nickname', 'Website', 'Bio' , /*'AIM', 'Yahoo IM', 'Jabber-Google Chat'*/ );

//
//	Actions to hook to allow AutoChimp to do it's work
//
//	See:  http://codex.wordpress.org/Plugin_API/Action_Reference
//
//	The THIRD argument is for the priority.  The default is "10" so choosing "101" is
//	to try to ensure that AutoChimp is called LAST.  For example, other plugins will
//	save their data during "profile_update", so AutoChimp wants them to do it first,
//	then run so that all the data is picked up.
//
add_action('admin_menu', 'AC_OnPluginMenu');				// Sets up the menu and admin page
add_action('user_register','AC_OnRegisterUser', 501);		// Called when a user registers on the site
add_action('delete_user','AC_OnDeleteUser', 501);			//   "      "  "  "   unregisters "  "  "
add_action('profile_update','AC_OnUpdateUser',501,2 );		// Updates the user using a second arg - $old_user_data.
add_action('publish_post','AC_OnPublishPost' );				// Called when an author publishes a post.
add_action('xmlrpc_publish_post', 'AC_OnPublishPost' );		// Same as above, but for XMLRPC
add_action('publish_phone', 'AC_OnPublishPost' );			// Same as above, but for email.  No idea why it's called "phone".
add_action('wp_ajax_query_sync_users', 'AC_OnQuerySyncUsers');
add_action('wp_ajax_run_sync_users', 'AC_OnRunSyncUsers');
add_action('admin_notices', 'AC_OnAdminNotice' );
add_action('admin_init', 'AC_OnAdminInit' );
add_action('plugins_loaded', 'AC_OnMUPluginsLoaded');
register_activation_hook( WP_PLUGIN_DIR . '/autochimp/autochimp.php', 'AC_OnActivateAutoChimp' );

//
//	Ajax
//

//
//	Ajax call to sync all current users against the selected mailing list(s).
//
function AC_OnRunSyncUsers()
{
	$numSuccess = 0;
	$numFailed = 0;
	$summary = '<strong>Report: </strong>';

	// Get a list of users on this site.  For more, see:
	// http://codex.wordpress.org/Function_Reference/get_users
	$users = get_users('');
	$numUsers = count( $users );

	// Iterate over the array and retrieve that users' basic information.  The 
	// info is written to the DB so that the client can periodically make ajax
	// calls to learn the progress.
	foreach ( $users as $user )
	{
		$result = AC_OnUpdateUser( $user->ID, $user, FALSE );
		if ( 0 === $result )
		{
			$numSuccess++;
    		$message = "<br>Successfully synchronized: $user->user_email";
			update_option( WP88_MC_MANUAL_SYNC_STATUS, $message );
		}
		else
		{
			$numFailed++;
    		$message = "<br>Failed to sync email: $user->user_email, Error: $result";
			update_option( WP88_MC_MANUAL_SYNC_STATUS, $message );
			$summary .= $message;
		}
		$percent = intval( ( ($numFailed + $numSuccess) / $numUsers ) * 100 );
		update_option( WP88_MC_MANUAL_SYNC_PROGRESS, $percent );
	}
	if ( 0 == $numFailed )
		$summary .= '<br/>All ';
	else
		$summary .= '</br>';
	$summary .= $numSuccess.' profiles were <strong>successfully</strong> synced.</div>';
	echo $summary;
	// Clean out the records
	delete_option( WP88_MC_MANUAL_SYNC_STATUS );
	delete_option( WP88_MC_MANUAL_SYNC_PROGRESS );
	exit; // This is required by WordPress to return the proper result
}

//
//	Companion Ajax function for AC_OnRunSyncUsers() which checks the current status
//	and reports back.
//
function AC_OnQuerySyncUsers()
{
	$percent = get_option( WP88_MC_MANUAL_SYNC_PROGRESS, 0 );
	$status = get_option( WP88_MC_MANUAL_SYNC_STATUS, 'Running sync...' );
	echo $percent . '#' . $status;
	exit; // This is required by WordPress to return the proper result
}

//
//	End Ajax
//

//
//	START Register Plus AND Register Plus Redux Workaround
//
//	Register Plus overrides this:
//	http://codex.wordpress.org/Function_Reference/wp_new_user_notification
//
//	Look at register-plus.php somewhere around line 1715.  Same thing in
//	register-plus-redux.php around line 2324. More on Pluggable functions
//	can be found here:  http://codex.wordpress.org/Pluggable_Functions
//
//	Register Plus's overridden wp_new_user_notification() naturally includes the
//	original WordPress code for wp_new_user_notification().  This function calls
//	wp_set_password() after it sets user meta data.  This, as far as I can tell,
//	is the only place we can hook WordPress to update the user's MailChimp mailing
//	list with the user's first and last names.  NOTE:  This is a strange and non-
//	standard place for Register Plus to write the user's meta information.  Other
//	plugins like Wishlist Membership work with AutoChimp right out of the box.
//	This hack is strictly to make AutoChimp work with the misbehaving Register Plus.
//
//	The danger with this sort of code is that if the function that is overridden
//	is updated by WordPress, we'll likely miss out!  The best solution is to
//	have Register Plus perform it's work in a more standard way.
//
//	See the readme for more information on this issue.  The good news is the folks
//	at Register Plus explained this problem and are working on fixing it.
//
function AC_OverrideWarning()
{
	if( current_user_can(10) &&  $_GET['page'] == 'autochimp' )
		echo '<div id="message" class="updated fade"><p><strong>You have another plugin installed that is conflicting with AutoChimp and Register Plus.  This other plugin is overriding the user notification emails or password setting.  Please see <a href="http://www.wandererllc.com/plugins/autochimp/">AutoChimp FAQ</a> for more information.</strong></p></div>';
}

if ( function_exists( 'wp_set_password' ) )
{
	// Check if the user wants to patch
	$fixRegPlus = get_option( WP88_MC_FIX_REGPLUS );
	$fixRegPlusRedux = get_option( WP88_MC_FIX_REGPLUSREDUX );
	if ( '1' === $fixRegPlus || '1' === $fixRegPlusRedux )
	{
		add_action( 'admin_notices', 'AC_OverrideWarning' );
	}
}

//
// Override wp_set_password() which is called by Register Plus's overridden
// pluggable function - the only place I can see to grab the user's first
// and last name.
//
if ( !function_exists('wp_set_password') && ( '1' === get_option( WP88_MC_FIX_REGPLUS ) ||
											  '1' === get_option( WP88_MC_FIX_REGPLUSREDUX ) ) ) :
function wp_set_password( $password, $user_id )
{
	//
	// START original WordPress code
	//
	global $wpdb;

	$hash = wp_hash_password($password);
	$wpdb->update($wpdb->users, array('user_pass' => $hash, 'user_activation_key' => ''), array('ID' => $user_id) );

	wp_cache_delete($user_id, 'users');
	//
	// END original WordPress code
	//

	//
	// START Detect Register Plus
	//

	// Write some basic info to the DB about the user being added
	$user_info = get_userdata( $user_id );
	update_option( WP88_MC_LAST_CAMPAIGN_ERROR, "Updating user within Register Plus Redux patch.  User name is:  $user_info->first_name $user_info->last_name" );
	// Do the real work
	AC_ManageMailUser( MMU_UPDATE, $user_info, $user_info, TRUE );

	//
	// END Detect
	//
}
endif;	// wp_set_password is not overridden yet

//
// 	END Register Plus Workaround
//

//
//	Filters to hook
//
add_filter( 'plugin_row_meta', 'AC_AddAutoChimpPluginLinks', 10, 2 ); // Expand the links on the plugins page

//
//	Function to create the menu and admin page handler
//
function AC_OnPluginMenu()
{
	$page = add_submenu_page('options-general.php',	'AutoChimp Options', 'AutoChimp', 'add_users', basename(__FILE__), 'AC_AutoChimpOptions' );
	// When the plugin menu is clicked on, call AC_OnLoadAutoChimpScripts()
	add_action( 'admin_print_styles-' . $page, 'AC_OnLoadAutoChimpScripts' );

	// Register custom hooks needed for 3rd party plugin publishing support.  Do 
	// this here as opposed to the global pace so that plugins have a change to
	// acknowledge themselves as 'installed'.
	$plugins = new ACPublishPlugins;
	$plugins->RegisterHooks();
}

function AC_OnMUPluginsLoaded()
{
	// The sync plugins need to be placed early in the page load.  Other plugins, like
	// the publish plugins can be loaded later (see ACPublishPlugins below).
	$plugins = new ACSyncPlugins;
	$plugins->RegisterHooks();
}

//
//	Load function for AutoChimp scripts.  Does the following:
//	1) Loads jQuery.
//	2) Loads WordPress Ajax functionality.
//	3) Loads AutoChimp custom scripts.
//
function AC_OnLoadAutoChimpScripts() 
{
	// jQuery UI stuff - files for the progress bar and dependencies PLUS style for them.
	wp_enqueue_script('jquery');
	wp_enqueue_script('jquery-ui-core');
	wp_enqueue_script('jquery-ui-widget');
	wp_enqueue_script('jquery-ui-progressbar');
    wp_register_style('jquery-style', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.1/themes/smoothness/jquery-ui.css', true);
    wp_enqueue_style('jquery-style');

	// Load the javascript file that makes the AJAX request
	wp_enqueue_script( 'autochimp-ajax-request' );
		 
	// declare the URL to the file that handles the AJAX request (wp-admin/admin-ajax.php)
	wp_localize_script( 'autochimp-ajax-request', 'AutoChimpAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );

	// Some AutoChimp plugins may use JS as well.
	$plugins = new ACPlugins;
	$plugins->EnqueueScripts();
}

function AC_AddAutoChimpPluginLinks($links, $file)
{
	if ( $file == plugin_basename(__FILE__) )
	{
		$links[] = '<a href="http://wordpress.org/extend/plugins/autochimp/">' . __('Overview', 'autochimp') . '</a>';
		$links[] = '<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=HPCPB3GY5LUQW&lc=US">' . __('Donate', 'autochimp') . '</a>';
	}
	return $links;
}

function AC_OnAdminInit() 
{
	global $current_user;
	$user_id = $current_user->ID;
	// If user clicks to ignore the notice, add that to their user meta so that
	// the notice doesn't come up anymore.
	if ( isset($_GET['ac_21_nag_ignore']) && '0' == $_GET['ac_21_nag_ignore'] ) 
	{
		add_user_meta( $user_id, WP88_IGNORE_2_1_NOTICE, 'true', true );
	}
	
	// Register the AutoChimp JS scripts - they'll be loaded later when the
	// AutoChimp admin menu is clicked on.  Ensures that these scripts are only
	// loaded when needed (flow is a little goofy - search for
	// "wp_enqueue_script( 'autochimp-ajax-request'" for the next step).
	$pluginFolder = get_bloginfo('wpurl') . '/wp-content/plugins/autochimp/';
	wp_register_script( 'autochimp-ajax-request', $pluginFolder.'js/autochimp.js', array( 'jquery' ) );
	
	// Some AutoChimp plugins may use JS as well.  Load them here.
	//$plugins = new ACPlugins;
	//$plugins->RegisterScripts( $pluginFolder );
}

//
//	This function is responsible for saving the user's' the AutoChimp options.  It
//	also displays the admin UI, which happens at the very bottom of the function, 
//	with the require_once statement.
//
function AC_AutoChimpOptions()
{
	// Stop the user if they don't have permission
	if (!current_user_can('add_users'))
	{
    	wp_die( __('You do not have sufficient permissions to access this page.') );
  	}

	// If the upload_files POST option is set, then files are being uploaded
	if ( isset( $_POST['save_api_key'] ) )
	{
		// Security check
		check_admin_referer( 'mailchimpz-nonce' );

		$newAPIKey = $_POST['api_key'];

		// Update the database (save the key, but also clean out other stuff)
		update_option( WP88_MC_APIKEY, $newAPIKey );
		update_option( WP88_MC_LAST_MAIL_LIST_ERROR, '' );
		update_option( WP88_MC_LAST_CAMPAIGN_ERROR, '' );

		// Tell the user
		print '<div id="message" class="updated fade"><p>Successfully saved your API Key!</p></div>';
	}

	// Save off the mailing list options here
	if ( isset( $_POST['save_mailing_list_options'] ) )
	{
		// Security check
		check_admin_referer( 'mailchimpz-nonce' );

		// Step 1:  Save the mailing lists that the user wants to affect

		// Declare an empty string...add stuff later
		$selectionOption = '';

		// Go through here and generate the option - a list of mailing list IDs separated by commas
		foreach( $_POST as $postVar )
		{
			$pos = strpos( $postVar, WP88_SEARCHABLE_PREFIX );
			if ( false === $pos ){}
			else
			{
				$selectionOption .= $postVar . ',';
			}
		}

		// Update the database
		update_option( WP88_MC_LISTS, $selectionOption );

		// Step 2:  Save when the user wants to update the list

		AC_SetBooleanOption( 'on_add_subscriber', WP88_MC_ADD );
		AC_SetBooleanOption( 'on_bypass_opt_in', WP88_MC_BYPASS_OPT_IN );
		AC_SetBooleanOption( 'on_delete_subscriber', WP88_MC_DELETE );
		AC_SetBooleanOption( 'on_update_subscriber', WP88_MC_UPDATE );
		AC_SetBooleanOption( 'on_delete_member', WP88_MC_PERMANENTLY_DELETE_MEMBERS );
		AC_SetBooleanOption( 'on_send_goodbye', WP88_MC_SEND_GOODBYE );
		AC_SetBooleanOption( 'on_send_notify', WP88_MC_SEND_ADMIN_NOTIFICATION );

		// Step 3:  Save the extra WordPress fields that the user wants to sync.
		global $wpUserDataArray;
		foreach( $wpUserDataArray as $userField )
		{
			// Encode the name of the field
			$fieldName = AC_EncodeUserOptionName( WP88_WORDPRESS_FIELD_MAPPING, $userField );

			// Now dereference the selection
			$fieldData = $_POST[ $fieldName ];

			// Save the selection
			update_option( $fieldName, $fieldData );
		}

		// Now save the special static field and the mapping
		$staticText = $_POST[ 'static_select' ];
		update_option( WP88_MC_STATIC_TEXT, $staticText );
		update_option( WP88_MC_STATIC_FIELD, $_POST[ WP88_MC_STATIC_FIELD ] );

		// Step 4:  Save the plugin mappings.  Uses the _POST hash.
		// This hidden field allows the user to save their mappings even when the
		// sync button isn't checked
		//if ( isset( $_POST['buddypress_running'] ) )
		//if ( isset( $_POST['cimy_running'] ) )
		$syncPlugins = new ACSyncPlugins;
		$syncPlugins->SaveMappings();

		// Tell the user
		print '<div id="message" class="updated fade"><p>Successfully saved your AutoChimp mailing list options.</p></div>';
	}

	if ( isset( $_POST['save_campaign_options'] ) )
	{
		// Save off the mappings of categories to campaigns.
		AC_SaveCampaignCategoryMappings( WP88_CATEGORY_MAPPING_PREFIX );
		
		// Now save off the plugin mappings.
		$publishPlugins = new ACPublishPlugins;
		$publishPlugins->SaveMappings();
		
		// The rest is easy...
		AC_SetBooleanOption( 'on_excerpt_only', WP88_MC_CAMPAIGN_EXCERPT_ONLY );
		AC_SetBooleanOption( 'on_send_now', WP88_MC_SEND_NOW );
		AC_SetBooleanOption( 'on_create_once', WP88_MC_CREATE_CAMPAIGN_ONCE );

		// Tell the user
		print '<div id="message" class="updated fade"><p>Successfully saved your AutoChimp campaign options.</p></div>';
	}
	
	// Deleting campaign rows is a little more sophisticated.  Have to loop through 
	// looking for rows that may have been deleted.  Only one may be deleted at a 
	// time.
	foreach( $_POST as $key => $value )
	{
		// If a match was found, then the row to delete is in $value and the
		// first part of the $key will determine the name of the DB field.
		// Following this naming convention, there's no need to forward the
		// call to a plugin.  
		if ( FALSE !== strpos( $key, WP88_DELETE_MAPPING_SUFFIX ) )
		{
			// The value will hold the beginning of the name of the DB option.
			// Just tack on the suffix and delete.  Done.
			delete_option( $value . WP88_CATEGORY_SUFFIX );
			delete_option( $value . WP88_LIST_SUFFIX );
			delete_option( $value . WP88_GROUP_SUFFIX );
			delete_option( $value . WP88_TEMPLATE_SUFFIX );
			break;
		}
	}

	if ( isset( $_POST['save_plugin_options'] ) )
	{
		// These are hardcoded as part of AutoChimp
		AC_SetBooleanOption( 'on_fix_regplus', WP88_MC_FIX_REGPLUS );
		AC_SetBooleanOption( 'on_fix_regplusredux', WP88_MC_FIX_REGPLUSREDUX );
		
		// Plugins for AutoChimp are handled here.
		$plugins = new ACSyncPlugins;
		$plugins->SavePluginSettings();
		
		$plugins = new ACContentPlugins;
		$plugins->SavePluginSettings();
		
		$plugins = new ACPublishPlugins;
		$plugins->SavePluginSettings();

		// Tell the user
		print '<div id="message" class="updated fade"><p>Successfully saved your AutoChimp plugin options.</p></div>';
	}

	// The file that will handle uploads is this one (see the "if" above)
	$action_url = $_SERVER['REQUEST_URI'];
	require_once 'autochimp-settings.php';
}

//
//	Syncs a single user of this site with the AutoChimp options that the site owner
//	has selected in the admin panel.  For more information on batch updating, which,
//	as of AutoChimp 2.0, is not supported, go here:
//
//	http://apidocs.mailchimp.com/api/how-to/sync-you-to-mailchimp.php
//
//	The third argument, $old_user_data, is for the profile_update action, which calls
//	AC_OnUpdateUser.  If $mode is MMU_UPDATE, then ensure that this data is a copy
//	of user data.  Otherwise, null is fine. 
//
//	List of exceptions and error codes: http://www.mailchimp.com/api/1.3/exceptions.field.php
//
function AC_ManageMailUser( $mode, $user_info, $old_user_data, $writeDBMessages )
{
	$apiKey = get_option( WP88_MC_APIKEY );
	$api = new MCAPI_13( $apiKey );

	$myLists = $api->lists();
	$errorCode = 0;

	if ( null != $myLists )
	{
		$list_id = -1;

		// See if the user has selected some lists
		$selectedLists = get_option( WP88_MC_LISTS );

		// Put all of the selected lists into an array to search later
		$valuesArray = array();
		$valuesArray = preg_split( "/[\s,]+/", $selectedLists );

		foreach ( $myLists['data'] as $list )
		{
			$list_id = $list['id'];

			// See if this mailing list should be selected
			foreach( $valuesArray as $searchableID )
			{
				$pos = strpos( $searchableID, $list_id );
				if ( false === $pos ){}
				else
				{
					// First and last names are always added.  NOTE:  Email is only
					// managed when a user is updating info 'cause email is used as
					// the key when adding a new user.
					$merge_vars = array( 'FNAME'=>$user_info->first_name, 'LNAME'=>$user_info->last_name );

					// Grab the extra WP user info
					$data = AC_FetchMappedWordPressData( $user_info->ID );
					// Add that info into the merge array.
					AC_AddUserFieldsToMergeArray( $merge_vars, $data );

					// Gather the additional data from AutoChimp plugins
					$syncPlugins = new ACSyncPlugins;
					$syncPlugins->SyncData( $merge_vars, $user_info->ID );			

					// This one gets static data...add it as well to the array.
					$data = AC_FetchStaticData();
					// Add that info into the merge array.
					AC_AddUserFieldsToMergeArray( $merge_vars, $data );

					switch( $mode )
					{
						case MMU_ADD:
						{
							// Check to see if the site wishes to bypass the double opt-in feature
							$doubleOptIn = ( 0 === strcmp( '1', get_option( WP88_MC_BYPASS_OPT_IN ) ) ) ? false : true;
							$retval = $api->listSubscribe( $list_id, $user_info->user_email, $merge_vars, 'html', $doubleOptIn );
							// Start all error notifications with a date.
							$errorString = date("(Y-m-d H:i:s) ");
							if ( $api->errorCode )
							{
								$errorCode = $api->errorCode;
								// Set latest activity - displayed in the admin panel
								$errorString = "Problem pushing $user_info->first_name $user_info->last_name ('$user_info->user_email') to list $list_id.  Error Code: $errorCode, Message: $api->errorMessage, Data: ";
								$errorString .= print_r( $merge_vars, TRUE );
							}
							else
							{
								$errorString = "Pushed $user_info->first_name $user_info->last_name ('$user_info->user_email') to list $list_id.";
							}
							AC_Log( $errorString );
							if ( FALSE != $writeDBMessages )
								update_option( WP88_MC_LAST_MAIL_LIST_ERROR, $errorString );
							break;
						}
						case MMU_DELETE:
						{
							$deleteMember = ( '1' === get_option( WP88_MC_PERMANENTLY_DELETE_MEMBERS ) );
							$sendGoodbye = ( '1' === get_option( WP88_MC_SEND_GOODBYE ) );
							$sendNotify = ( '1' === get_option( WP88_MC_SEND_ADMIN_NOTIFICATION ) );
							update_option( WP88_MC_LAST_MAIL_LIST_ERROR, $lastMessage );
							$retval = $api->listUnsubscribe( $list_id, $user_info->user_email, $deleteMember, $sendGoodbye, $sendNotify );
							$rightNow = date("(Y-m-d H:i:s) ");
							if ( $api->errorCode )
							{
								$errorCode = $api->errorCode;

								if ( FALSE != $writeDBMessages )
								{
									// Set latest activity - displayed in the admin panel
									update_option( WP88_MC_LAST_MAIL_LIST_ERROR, "$rightNow Problem removing $user_info->first_name $user_info->last_name ('$user_info->user_email') from list $list_id.  Error Code: $errorCode, Message: $api->errorMessage" );
								}
							}
							else
							{
								if ( FALSE != $writeDBMessages )
									update_option( WP88_MC_LAST_MAIL_LIST_ERROR, "$rightNow Removed $user_info->first_name $user_info->last_name ('$user_info->user_email') from list $list_id." );
							}
							break;
						}
						case MMU_UPDATE:
						{
							$updateEmail = $old_user_data->user_email;
							$rightNow = date("(Y-m-d H:i:s) ");

							// Potential update to the email address (more likely than name!)
							$merge_vars['EMAIL'] = $user_info->user_email;

							// No emails are sent after a successful call to this function.
							$retval = $api->listUpdateMember( $list_id, $updateEmail, $merge_vars );
							if ( $api->errorCode )
							{
								$errorCode = $api->errorCode;
								if ( FALSE != $writeDBMessages )
								{
									// Set latest activity - displayed in the admin panel
									$errorString = "$rightNow Problem updating $user_info->first_name $user_info->last_name ('$user_info->user_email') from list $list_id.  Error Code: $errorCode, Message: $api->errorMessage, Data: ";
									$errorString .= print_r( $merge_vars, TRUE );
									update_option( WP88_MC_LAST_MAIL_LIST_ERROR, $errorString );
								}
							}
							else
							{
								if ( FALSE != $writeDBMessages )
								{
									$errorString = "$rightNow Updated $user_info->first_name $user_info->last_name ('$user_info->user_email') from list $list_id.";
									// Uncomment this to see debug info on success
									//$errorString .= ' Data: ';
									//$errorString .= print_r( $merge_vars, TRUE );
									update_option( WP88_MC_LAST_MAIL_LIST_ERROR, $errorString );
								}
							}
							break;
						}
					}
				}
			}
		}
	}
	return $errorCode;
}

//
//	Arguments:
//		an instance of the MailChimp API class (for performance).
//		the post ID
//		the list ID that the campaign should be created for.
//		the interest group name.
//		the user template ID.
//
//	Returns STRING "-1" if the creation was skipped, "0" on failure, and a legit
//	ID on success.  Except for "-1", each return point will write the latest result
//	of the function to the DB which will be visible to the user in the admin page.
//
function AC_CreateCampaignFromPost( $api, $postID, $listID, $interestGroupName, $categoryTemplateID )
{
	// Does the user only want to create campaigns once?
	if ( '1' == get_option( WP88_MC_CREATE_CAMPAIGN_ONCE ) )
	{
		if ( '1' == get_post_meta( $postID, WP88_MC_CAMPAIGN_CREATED, true ) )
			return '-1';	// Don't create the campaign again!
	}

	// Get the info on this post
	$post = get_post( $postID );

	// If the post is somehow in an unsupported state (sometimes from email
	// posts), then just skip the post.
	if ('pending' == $post->post_status ||
		'draft' == $post->post_status ||
		'private' == $post->post_status )
	{
		return '-1'; // Don't create the campaign yet.
	}
	
	// Get info on the list
	$filters = array();
	$filters['list_id'] = $listID;
	$lists = $api->lists( $filters );
	$list = $lists['data'][0];

	// Time to start creating the campaign...
	// First, create the options array
	$htmlContentTag = 'html';
	$options = array();
	$options['list_id']	= $listID;
	$options['subject']	= $post->post_title;
	$options['from_email'] = $list['default_from_email'];
	$options['from_name'] = $list['default_from_name'];
	$options['to_email'] = '*|FNAME|*';
	$options['tracking'] = array('opens' =>	true, 'html_clicks' => true, 'text_clicks' => false );
	$options['authenticate'] = true;
	// See if a template should be used
	if ( 0 != strcmp( $categoryTemplateID, WP88_NONE ) )
	{
		$options['template_id'] = $categoryTemplateID;
		// 'main' is the name of the section that will be replaced.  This is a
		// hardcoded decision.  Keeps things simple.  To view the sections of
		// a template, use MailChimp's templateInfo() function.  For more
		// information, go here:
		// http://apidocs.mailchimp.com/api/1.3/templateinfo.func.php
		// You need the campaign ID.  That can be retrieved with campaigns().
		$htmlContentTag = 'html_main';
	}

	// Start generating content
	$content = array();
	$postContent = '';
	
	// Get the excerpt option; if on, then show the excerpt
	if ( '1' === get_option( WP88_MC_CAMPAIGN_EXCERPT_ONLY ) )
	{
		if ( 0 == strlen( $post->post_excerpt ) )
		{
			// Handmade function which mimics wp_trim_excerpt() (that function won't operate
			// on a non-empty string)
			$postContent = AC_TrimExcerpt( $post->post_content );
		}
		else
		{
			$postContent = apply_filters( 'the_excerpt', $post->post_excerpt );
			// Add on a "Read the post" link here
			$permalink = get_permalink( $postID );
			$postContent .= "<p>Read the post <a href=\"$permalink\">here</a>.</p>";
			// See http://codex.wordpress.org/Function_Reference/the_content, which
			// suggests adding this code:
			$postContent = str_replace( ']]>', ']]&gt;', $postContent );
		}

		// Set the text content variables
		$content['text'] = strip_tags( $postContent );
	}
	else
	{
		// Run the full text through the content plugins
		$contentPlugins = new ACContentPlugins;
		$postContent = $contentPlugins->ConvertShortcode( $post->post_content );
		
		// Text version isn't run through the content plugins
		$textPostContent = apply_filters( 'the_content', $post->post_content );
		$content['text'] = strip_tags( $textPostContent );
	}

	// Set the content variables
	$content[$htmlContentTag] = $postContent;

	// Segmentation, if any (Interest groups)
	$segment_opts = NULL;
	if ( 0 != strcmp( $interestGroupName, WP88_ANY ) )
	{
		$group = $api->listInterestGroupings( $listID );
		if ( NULL != $group )
		{
			$interestID = $group[0]['id'];
			$conditions = array();
			$conditions[] = array('field'=>"interests-$interestID", 'op'=>'all', 'value'=>$interestGroupName);
			$segment_opts = array('match'=>'all', 'conditions'=>$conditions);
		}
	}

	// More info here:  http://apidocs.mailchimp.com/api/1.3/campaigncreate.func.php
	$result = $api->campaignCreate( 'regular', $options, $content, $segment_opts );
	if ($api->errorCode)
	{
		// Set latest activity - displayed in the admin panel
		update_option( WP88_MC_LAST_CAMPAIGN_ERROR, "Problem with campaign with title '$post->post_title'.  Error Code: $api->errorCode, Message: $api->errorMessage" );
		$result = "0";
	}
	else
	{
		// Set latest activity
		update_option( WP88_MC_LAST_CAMPAIGN_ERROR, "Your latest campaign created is titled '$post->post_title' with ID: $result" );

		// Mark this post as having a campaign created from it.
		add_post_meta( $postID, WP88_MC_CAMPAIGN_CREATED, '1' );
	}

	// Done
	return $result;
}

function AC_OnPublishPost( $postID )
{
	// Get the info on this post
	$post = get_post( $postID );
	$categories = AC_AssembleTermsArray( get_the_category( $postID ) );	// Potentially several categories
	// Need to have the "Any" category for the big foreach below to work.  Add it
	// here.
	$categories['Any'] = 'any';
	
	if ( empty( $categories ) )
	{
		AC_Log( "There is no standard category for post $postID.  Searching for a third-party plugin category." );
		// Now, search for custom categories (actually taxonomy terms) in the
		// various Publish plugins.
		$publishPlugins = new ACPublishPlugins;
		$pluginCollection = $publishPlugins->GetPluginClasses( $publishPlugins->GetType() );
		foreach ( $pluginCollection as $plugin )
		{
			if ( $plugin::GetInstalled() && $plugin::GetUsePlugin() )
			{
				$newTerms = AC_AssembleTermsArray( $plugin::GetTerms( $postID ) );
				foreach ($newTerms as $name => $slum) 
				{
					$categories[$name] = $slum;
				}
			}
			// If a category was found, break out.
			if ( !empty( $categories ) )
			{
				AC_Log( "Found additional categories through the plugin $plugin." );
			}
		}
	}

	AC_Log( "Attempting to create a campaign for post ID $postID." );
	if ( !empty( $categories ) )
		AC_Log( $categories );
	
	// If it matches the user's category choice or is "Any" category, then
	// do the work.  This needs to be a loop because a post can belong to
	// multiple categories.
	global $wpdb;
	foreach( $categories as $categoryName => $categorySlug )
	{
		// Do a SQL lookup of all category rows that match this category slug.  NOTE that
		// the option for the "Any" category is in this SQL string.
		$options_table_name = $wpdb->prefix . 'options';
		$sql = "SELECT option_name,option_value FROM $options_table_name WHERE option_name LIKE 'wp88_mc_%' AND (option_value = '$categorySlug' OR option_value = '" . WP88_ANY . "')";
		$fields = $wpdb->get_results( $sql );
		if ( $fields )
		{
			foreach ( $fields as $field )
			{
				// NOTE:  This approach currently does have the problem that if a category
				// and a plugin's term have the same slug, then campaigns could go to the 
				// wrong place.  This is fairly unlikely, but this leak needs to be plugged
				// with an improved architecture here.
				//
				// This can happen because the above SQL statement does not discriminate
				// between categories or terms.  The prefix part of the string and the index
				// can easily differ while the category slug is the same.
				
				// Split the results into an array which contains info about this mapping
				$info = explode( '_', $field->option_name );
				
				// The last part of $info should contain the word "category".  It's possible
				// that other rows will be picked up (like when the option value is "Any", 
				// the "group" option will be picked up too since it can have an "Any" value)
				// so skip those here.
				if ( 0 !== strcmp( $info[4], 'category') )
					continue;
				
				// Yank off the "category" from the tail of each string and replace it with the 
				// other values, then query them.
				$categoryMailingList = get_option( str_replace( WP88_CATEGORY_SUFFIX, WP88_LIST_SUFFIX, $field->option_name ) );
				$categoryGroupName = get_option( str_replace( WP88_CATEGORY_SUFFIX, WP88_GROUP_SUFFIX, $field->option_name ) );
				$categoryTemplateID = get_option( str_replace( WP88_CATEGORY_SUFFIX, WP88_TEMPLATE_SUFFIX, $field->option_name ) );
				AC_Log( "For the $categorySlug category:  The mailing list is:  $categoryMailingList.  The group is:  $categoryGroupName.  The template ID is:  $categoryTemplateID." );
		
				// If the mailing list is NOT "None" then create a campaign.		
				if ( 0 != strcmp( $categoryMailingList, WP88_NONE ) )
				{
					// Create an instance of the MailChimp API
					$apiKey = get_option( WP88_MC_APIKEY );
					$api = new MCAPI_13( $apiKey );
		
					// Do the work
					$id = AC_CreateCampaignFromPost( $api, $postID, $categoryMailingList, $categoryGroupName, $categoryTemplateID );
					AC_Log( "Created a campaign with ID $id in category $categoryName." );
		
					// Does the user want to send the campaigns right away?
					$sendNow = get_option( WP88_MC_SEND_NOW );
		
					// Send it, if necessary (if user wants it), and the $id is
					// sufficiently long (just picking longer than 3 for fun).
					if ( '1' == $sendNow && ( strlen( $id ) > 3 ) )
					{
						$api->campaignSendNow( $id );
					}
		
					// Not breaking anymore.  Now, if you assign multiple categories to 
					// create campaigns, then each will be created.
				}
			}
		}
	}
}

//
//	Given a mailing list, return an associative array of the names and tags of
//	the merge variables (custom fields) for that mailing list.
//
function AC_FetchMailChimpMergeVars( $api, $list_id )
{
	$mergeVars = array();
	$mv = $api->listMergeVars( $list_id );

	$ig = $api->listInterestGroupings( $list_id );

	// Bail here if nothing is returned
	if ( NULL == $mv && NULL == $ig )
		return $mergeVars;

	// Copy over the merge variables
	if ( !empty( $mv ) )
	{
		foreach( $mv as $i => $var )
		{
			$mergeVars[ $var['name'] ] = $var['tag'];
		}
	}

	// Copy over the interest groups
	if ( !empty( $ig ) )
	{
		foreach( $ig as $i => $var )
		{
			// Create a special encoding - grouping text, plus delimiter, then the name of the grouping
			$mergeVars[ $var['name'] ] = WP88_GROUPINGS_TEXT . WP88_FIELD_DELIMITER . $var['name'];
		}
	}

	return $mergeVars;
}

//
//	Looks up the user's additional WordPress user data and returns a meaningful
//	array of associations to the users based on what the user wants to sync.
//
function AC_FetchMappedWordPressData( $userID )
{
	// User data array
	$dataArray = array();

	// This global array holds the names of the WordPress user fields
	global $wpUserDataArray;

	// Get this user's data
	$user_info = get_userdata( $userID );

	// Loop through each field that the user wants to sync and hunt down the user's
	// values for those fields and stick them into an array.
	foreach ( $wpUserDataArray as $field )
	{
		// Figure out which MailChimp field to map to
		$optionName = AC_EncodeUserOptionName( WP88_WORDPRESS_FIELD_MAPPING, $field );
		$fieldData = get_option( $optionName );

		// If the mapping is not set, then skip everything and go on to the next field
		if ( 0 !== strcmp( $fieldData, WP88_IGNORE_FIELD_TEXT ) )
		{
			// Now, get the user's data.  Since the data is basically static,
			// this is just a collection of "if"s.
			if ( 0 === strcmp( $field, 'Username' ) )
			{
				$value = $user_info->user_login;
				$dataArray[] = array( 	'name' => $optionName,
										'tag' => $fieldData,
										'value' => $value );
			}
			elseif ( 0 === strcmp( $field, 'Nickname' ) )
			{
				$value = $user_info->user_nicename;
				$dataArray[] = array( 	'name' => $optionName,
										'tag' => $fieldData,
										'value' => $value );
			}
			elseif ( 0 === strcmp( $field, 'Website' ) )
			{
				$value = $user_info->user_url;
				$dataArray[] = array( 	'name' => $optionName,
										'tag' => $fieldData,
										'value' => $value );
			}
			elseif ( 0 === strcmp( $field, 'Bio' ) )
			{
				$value = $user_info->user_description;
				$dataArray[] = array( 	'name' => $optionName,
										'tag' => $fieldData,
										'value' => $value );
			}
			elseif ( 0 === strcmp( $field, 'AIM' ) )
			{
				$value = $user_info->user_description;
				$dataArray[] = array( 	'name' => $optionName,
										'tag' => $fieldData,
										'value' => $value );
			}
			elseif ( 0 === strcmp( $field, 'Yahoo IM' ) )
			{
				$value = $user_info->user_description;
				$dataArray[] = array( 	'name' => $optionName,
										'tag' => $fieldData,
										'value' => $value );
			}
			elseif ( 0 === strcmp( $field, 'Jabber-Google Chat' ) )
			{
				$value = $user_info->user_description;
				$dataArray[] = array( 	'name' => $optionName,
										'tag' => $fieldData,
										'value' => $value );
			}
		}
	}
	return $dataArray;
}

function AC_FetchStaticData()
{
	// Will hold a row of static data...assuming user wants this data, of course
	$dataArray = array();

	// Does the user want static data?
	$mapping = get_option( WP88_MC_STATIC_FIELD );

	// If the mapping is set...
	if ( 0 !== strcmp( $mapping, WP88_IGNORE_FIELD_TEXT ) )
	{
		$text = get_option( WP88_MC_STATIC_TEXT );

		if ( !empty( $text ) )
		{
			$dataArray[] = array( 	"name" => WP88_MC_STATIC_FIELD,
									"tag" => $mapping,
									"value" => $text );
		}
	}
	return $dataArray;
}

//
//	Takes a by-reference array argument and adds extra merge variable data
//	specific to the user ID passed in to the array.
//
function AC_AddUserFieldsToMergeArray( &$mergeVariables, $data )
{
	// Create a potentially used groupings array.  Tack this on at the end
	$groupingsArray = array();

	// Add this data to the merge variables
	foreach ( $data as $item )
	{
		// Catch the "GROUPINGS" tag and create a special array for that
		$groupTag = strpos( $item['tag'], WP88_GROUPINGS_TEXT );
		if ( FALSE === $groupTag )
		{
			$mergeVariables[ $item['tag'] ] = $item['value'];
		}
		else
		{
			$fields = explode( WP88_FIELD_DELIMITER, $item['tag'] );
			$groupingsArray[] = array('name' => $fields[1],
									'groups' => $item['value'] );
		}
	}

	// Tack on the group array now if there are groupings to add
	if ( !empty( $groupingsArray ) )
	{
		$mergeVariables[ WP88_GROUPINGS_TEXT ] = $groupingsArray;
	}
}

//
//	WordPress Action handlers
//

function AC_OnRegisterUser( $userID )
{
	$user_info = get_userdata( $userID );
	$onAddSubscriber = get_option( WP88_MC_ADD );
	if ( '1' == $onAddSubscriber )
	{
		$result = AC_ManageMailUser( MMU_ADD, $user_info, NULL, TRUE );
	}
	return $result;
}

function AC_OnDeleteUser( $userID )
{
	$user_info = get_userdata( $userID );
	$onDeleteSubscriber = get_option( WP88_MC_DELETE );
	if ( '1' == $onDeleteSubscriber )
	{
		$result = AC_ManageMailUser( MMU_DELETE, $user_info, NULL, TRUE );
	}
	return $result;
}

function AC_OnUpdateUser( $userID, $old_user_data, $writeDBMessages = TRUE )
{
	$user_info = get_userdata( $userID );
	$onUpdateSubscriber = get_option( WP88_MC_UPDATE );
	if ( '1' === $onUpdateSubscriber )
	{
		$result = AC_ManageMailUser( MMU_UPDATE, $user_info, $old_user_data, $writeDBMessages );

		// 232 is the MailChimp error code for: "user doesn't exist".  This
		// error can occur when a new user signs up but there's a required
		// field in MailChimp which the software doesn't have access to yet.
		// The field will be populated when the user finally activates their
		// account, but their account won't exist.  So, catch that here and
		// try to re-add them.  This is a costly workflow, but that's how
		// it works.
		//
		// This can also happen when synchronizing users with MailChimp who
		// aren't subscribers to the MailChimp mailing list yet.
		//
		// 215 is the "List_NotSubscribed" error message which can happen if 
		// the user is in the system but not subscribed to that list.  So, do
		// an add for that too.
		//
		if ( 232 === $result || 215 === $result )
		{
			$onAddSubscriber = get_option( WP88_MC_ADD );
			if ( '1' === $onAddSubscriber )
			{
				// Don't need the $old_user_data variable anymore; pass NULL.
				$result = AC_ManageMailUser( MMU_ADD, $user_info, NULL, $writeDBMessages );
			}
		}
	}
	return $result;
}

//
// Added for 2.0 to do some slight conversion work when upgrading from 1.x to 2.0.
// There are also upgrades from 2.02 to 2.10.
//
function AC_OnActivateAutoChimp()
{
	$show = get_option( WP88_PLUGIN_FIRST_ACTIVATION, '0' );
	if ( '0' === $show )
	{
		global $wpdb;
		// Delete options that are no longer needed 
		delete_option( WP88_MC_CAMPAIGN_CATEGORY );
		delete_option( WP88_MC_CAMPAIGN_FROM_POST );

		// Delete a bunch of those temp email options
		$tableName = $wpdb->prefix . "options";
		$sql = "delete FROM $tableName WHERE option_name LIKE 'wp88_mc_temp%'";
		$wpdb->query( $sql );

		// Set defaults for new options		
		update_option( WP88_MC_PERMANENTLY_DELETE_MEMBERS, '0' );
		update_option( WP88_MC_SEND_GOODBYE, '1' );
		update_option( WP88_MC_SEND_ADMIN_NOTIFICATION, '1' );
		
		// Done.
		update_option( WP88_PLUGIN_FIRST_ACTIVATION, '1' );
	}
	
	// This is for versions after 2.02 where the campaigns mappings become more
	// dynamic.  Need to migrate existing data to new naming scheme.
	AC_UpdateCampaignCategoryMappings();
}

function AC_OnAdminNotice() 
{
	global $current_user;
	$user_id = $current_user->ID;
	// Check that the user hasn't already clicked to ignore the message
	if ( !get_user_meta( $user_id, WP88_IGNORE_2_1_NOTICE ) ) 
	{
		global $pagenow;
	    if ( 'plugins.php' == $pagenow || 'options-general.php' == $pagenow ) 
	    {
	    	$currentPage = $_SERVER['REQUEST_URI'];
			
			// If there are already arguments, append the ignore message.  Otherwise
			// add it as the only variable.
			if ( FALSE === strpos( $currentPage, '?' ) )
				$currentPage .= '?ac_21_nag_ignore=0';
			else
				$currentPage .= '&ac_21_nag_ignore=0';
			
	    	$apiSetMessage = '';
	    	$apiSet = get_option( WP88_MC_APIKEY, '0' );
			if ( '0' == $apiSet )
			{
				$apiSetMessage = '<p>The first thing to do is set your MailChimp API key.  You can find your key on the MailChimp website under <em>Account</em> - <em>API Keys & Authorized Apps</em>.  Click <a target="_blank" href="options-general.php?page=autochimp.php">here</a> to set your API key now. | <a href="' . $currentPage . '">Dismiss</a></p>';
			}
			echo '<div class="updated"><p>';
			printf(__('Welcome to AutoChimp 2.14.  If you are upgrading, be sure to review your <a target="_blank" href="options-general.php?page=autochimp.php&tab=campaigns">campaign settings</a> which AutoChimp has just migrated.  To learn more about AutoChimp, please visit the <a href="http://www.wandererllc.com/company/plugins/autochimp/"">AutoChimp homepage</a>. | <a href="%1$s">Dismiss</a>'), $currentPage );
			print( $apiSetMessage );
			echo "</p></div>";
		}
	}
}
?>