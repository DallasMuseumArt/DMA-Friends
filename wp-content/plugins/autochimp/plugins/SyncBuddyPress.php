<?php
define( 'WP88_MC_SYNC_BUDDYPRESS', 'wp88_mc_sync_buddypress' );
define( 'WP88_BP_XPROFILE_FIELD_MAPPING', 'wp88_mc_bp_xpf_' );

class SyncBuddyPress extends ACSyncPlugin
{
	public function SyncBuddyPress()
	{
		// Used to sync users with MailChimp
		add_action('xprofile_updated_profile', array( $this, 'OnBuddyPressUserUpdate' ), 101 ); 
		add_action('bp_core_signup_user', array( $this, 'OnBuddyPressUserUpdate' ), 101 ); 
	}
	
	public static function GetInstalled()
	{
		return class_exists( 'BuddyPress' );
	}
	
	public static function GetUsePlugin()
	{
		return get_option( WP88_MC_SYNC_BUDDYPRESS );
	}
	
	public static function GetSyncVarName()
	{
		return 'on_sync_buddypress';
	}
	
	public static function GetSyncDBVarName()
	{
		return WP88_MC_SYNC_BUDDYPRESS;
	}

	//
	//	Function for displaying the UI for BuddyPress integration.  If this function
	//	exists (because this file has been included), then it means that BuddyPress
	//	is installed.  See "bp_init" action. 
	//
	public function ShowPluginSettings()
	{
		// Get settings
		$sync = SyncBuddyPress::GetUsePlugin();
		$varName = SyncBuddyPress::GetSyncVarName();
	
		// Start outputting UI
		print '<p><strong>You are using <a target="_blank" href="http://wordpress.org/extend/plugins/buddypress/">BuddyPress</a></strong>. With AutoChimp, you can automatically synchronize your BuddyPress user profile fields with your selected MailChimp mailing list as users join your site and update their profile.  Please ensure that only one list is selected.</p>';
		print '<fieldset style="margin-left: 20px;">';
		print "<p><input type=CHECKBOX value=\"$varName\" name=\"$varName\" ";
		if ( '1' === $sync )
			print 'checked';
		print '> Automatically sync BuddyPress profile fields with MailChimp.</p>';
		print '</fieldset>';
	}
	
	public function GenerateMappingsUI( $tableWidth, $mergeVars )
	{
		// Need to query data in the BuddyPress extended profile table
		global $wpdb;
	
		// Temporary variable for helping generate UI
		$rowCode = $finalText = '';
	
		$xprofile_table_name = $wpdb->prefix . 'bp_xprofile_fields';
		$fields = $wpdb->get_results( "SELECT name,type FROM $xprofile_table_name WHERE type != 'option'", ARRAY_A );
		// Create a hidden field just to signal that the user can save their preferences
		print '<br />'.PHP_EOL.'<input type="hidden" name="buddypress_running" />'.PHP_EOL;
	
		if ( $fields )
		{
			foreach ( $fields as $field )
			{
				// Generate a select box for this particular field
				$fieldNameTag = AC_EncodeUserOptionName( WP88_BP_XPROFILE_FIELD_MAPPING, $field['name'] );
				$selectBox = AC_GenerateSelectBox( $fieldNameTag, WP88_IGNORE_FIELD_TEXT, $mergeVars );
				$rowCode .= '<tr class="alternate">' . PHP_EOL . '<td width="65%">' . $field['name'] . '</td>' . PHP_EOL . '<td width="35%">' . $selectBox . '</td>' . PHP_EOL . '</tr>' . PHP_EOL;
			}
	
			print( AC_GenerateFieldMappingCode( 'BuddyPress', $rowCode ) );
		}
	}
	
	//
	//	This function uses the global $_POST variable, so only call it at the appropriate times.
	//	Consider refactoring this function to make it not dependent on $_POST.
	//
	public function SaveMappings()
	{
		// Each XProfile field will have a select box selection assigned to it.
		// Save this selection.
		global $wpdb;
		$xprofile_table_name = $wpdb->prefix . 'bp_xprofile_fields';
		$fields = $wpdb->get_results( "SELECT name,type FROM $xprofile_table_name WHERE type != 'option'", ARRAY_A );
	
		foreach( $fields as $field )
		{
			// Encode the name of the field
			$selectName = AC_EncodeUserOptionName( WP88_BP_XPROFILE_FIELD_MAPPING, $field['name'] );
	
			// Now dereference the selection
			$selection = $_POST[ $selectName ];
	
			// Save the selection
			update_option( $selectName, $selection );
		}
	}
	
	//
	//	Looks up the user's BP XProfile data and returns a meaningful array of
	//	associations to the user based on what the AutoChimp needs to sync.
	//
	public function FetchMappedData( $userID )
	{
		// User data array
		$dataArray = array();
	
		// Need to query data in the BuddyPress extended profile table
		global $wpdb;
		
		// Generate table names
		$option_table = $wpdb->prefix . 'options';
		$xprofile_data_table = $wpdb->prefix . 'bp_xprofile_data';
		$xprofile_fields_table = $wpdb->prefix . 'bp_xprofile_fields';
		
		// Now, see which XProfile fields the user wants to sync.
		$sql = "SELECT option_name,option_value FROM $option_table WHERE option_name LIKE '" .
				WP88_BP_XPROFILE_FIELD_MAPPING .
				"%' AND option_value != '" .
				WP88_IGNORE_FIELD_TEXT . "'";
		$fieldNames = $wpdb->get_results( $sql, ARRAY_A );
	
		// Loop through each field that the user wants to sync and hunt down the user's
		// values for those fields and stick them into an array.
		foreach ( $fieldNames as $field )
		{
			$optionName = AC_DecodeUserOptionName( WP88_BP_XPROFILE_FIELD_MAPPING, $field['option_name'] );
	
			// Big JOIN to get the user's value for the field in question
			// Best to offload this on SQL than PHP.
			$sql = "SELECT name,value,type FROM $xprofile_data_table JOIN $xprofile_fields_table ON $xprofile_fields_table.id = $xprofile_data_table.field_id WHERE user_id = $userID AND name = '$optionName' LIMIT 1";
			$results = $wpdb->get_results( $sql, ARRAY_A );
	
			// Populate the data array
			if ( !empty( $results[0] ) )
			{
				$value = $results[0]['value'];
	
				// Now convert a checkbox type to a string
				if ( 0 === strcmp( $results[0]['type'],"checkbox" ) )
				{
					// Here's the magic function to serialize/unserialize
					$checkboxData = unserialize( $value );
					$value = "";
					foreach( $checkboxData as $item )
					{
						$value .= $item . ',';
					}
					$value = rtrim( $value, ',' );
				}
	
				$dataArray[] = array( 	"name" => $optionName,
										"tag" => $field['option_value'],
										"value" => $value );
			}
		}
		return $dataArray;
	}

	public function OnBuddyPressUserUpdate( $user_id = 0 )
	{
		if ( 0 == $user_id )
		{
			// Get the current user
			$user = wp_get_current_user();
		}
		else
		{
			$user = get_userdata( $user_id );
		}
		// Pass their ID to the function that does the work.
		AC_OnUpdateUser( $user->ID, $user, TRUE );
	}
}

?>