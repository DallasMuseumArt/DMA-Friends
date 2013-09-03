<?php

//
// Special thanks to Aron Silverton for his help with this functionality.
//

define( 'AUTOCHIMP_SYNC_WPMEMBERS', 'wp88_mc_sync_wpmembers' );
define( 'WP_MEMBERS_FIELDS', 'wpmembers_fields'); // This name is from the WP-Members plugin.  Don't change it!
define( 'WP_MEMBERS_FIELD_DB_MAPPING', 'wp88_mc_wpmembers_' );

class SyncWPMembers extends ACSyncPlugin
{
	public function SyncWPMembers()
	{
	}

	public static function GetInstalled()
	{
		return function_exists( 'wpmem' );
	}
	
	public static function GetUsePlugin()
	{
		return get_option( AUTOCHIMP_SYNC_WPMEMBERS );
	}
	
	public static function GetSyncVarName()
	{
		return 'on_sync_wpmembers';
	}
	
	public static function GetSyncDBVarName()
	{
		return AUTOCHIMP_SYNC_WPMEMBERS;
	}

	//
	//	Function for displaying the UI for WPMembers integration.  
	//
	public function ShowPluginSettings()
	{
		// Get settings
		$sync = SyncWPMembers::GetUsePlugin();
		$varName = SyncWPMembers::GetSyncVarName();
	
		// Start outputting UI
		print '<p><strong>You are using <a target="_blank" href="http://wordpress.org/extend/plugins/wp-members/">WP-Members</a></strong>. With AutoChimp, you can automatically synchronize your WP-Members user profile fields with your selected MailChimp mailing list as users join your site and update their profile.  Please ensure that only one list is selected.</p>';
		print '<fieldset style="margin-left: 20px;">';
		print "<p><input type=CHECKBOX value=\"$varName\" name=\"$varName\" ";
		if ( '1' === $sync )
			print 'checked';
		print '> Automatically sync WP-Members profile fields with MailChimp.</p>';
		print '</fieldset>';
	}
	
	public function GenerateMappingsUI( $tableWidth, $mergeVars )
	{
		// This data is serializd but get_option will unserialize it, it seems.
		$fields = get_option( WP_MEMBERS_FIELDS );
		if ( FALSE !== $fields )
		{
			// The data is an array of arrays.  Each array represents a field.  The elements:
			//
			// 0 - Order #
			// 1 - Caption text
			// 2 - Name of the option (we care about this)
			// 3 - Option type (we care about this)
			//
			// There are other items as well which are described below as needed.
			print '<br />'.PHP_EOL.'<input type="hidden" name="wpmembers_running" />'.PHP_EOL;
			foreach ( $fields as $field )
			{
				// Check that there's a string.  Sometimes WP-Members will have 
				// empty arrays.
				if ( 0 == strlen( $field[2] ) )
					continue;
				// Generate a select box for this particular field
				$fieldNameTag = AC_EncodeUserOptionName( WP_MEMBERS_FIELD_DB_MAPPING, $field[2] );
				$selectBox = AC_GenerateSelectBox( $fieldNameTag, WP88_IGNORE_FIELD_TEXT, $mergeVars );
				$rowCode .= '<tr class="alternate">' . PHP_EOL . '<td width="65%">' . $field[2] . '</td>' . PHP_EOL . '<td width="35%">' . $selectBox . '</td>' . PHP_EOL . '</tr>' . PHP_EOL;
			}
			print( AC_GenerateFieldMappingCode( 'WP-Members', $rowCode ) );
		}
	}
	
	//
	//	This function uses the global $_POST variable..
	//
	public function SaveMappings()
	{
		// Select the fields from the options table (unserialized by WordPress)
		$fields = get_option( WP_MEMBERS_FIELDS );
		foreach( $fields as $field )
		{
			// Check that there's a string.  Sometimes WP-Members will have 
			// obnoxious empty arrays.
			if ( 0 == strlen( $field[2] ) )
				continue;

			// Encode the name of the field
			$selectName = AC_EncodeUserOptionName( WP_MEMBERS_FIELD_DB_MAPPING, $field[2] );
	
			// Now dereference the selection
			$selection = $_POST[ $selectName ];
	
			// Save the selection
			update_option( $selectName, $selection );
		}
	}
	
	//
	//	Looks up the user's WP-Members data and returns an array formatted for MailChimp
	//	of fields mapped to data for the user.  The WP-Members plugin saves user data
	//	in the wp_usermeta table, which makes things easy.
	//
	public function FetchMappedData( $userID )
	{
		// User data array
		$dataArray = array();
		// Need to query data in the WordPress options table
		global $wpdb;
		
		// Generate table names
		$optionTable = $wpdb->prefix . 'options';
		
		// Now, see which WP-Members fields the user wants to sync.
		$sql = "SELECT option_name,option_value FROM $optionTable WHERE option_name LIKE '" .
				WP_MEMBERS_FIELD_DB_MAPPING .
				"%' AND option_value != '" .
				WP88_IGNORE_FIELD_TEXT . "'";
		$fieldNames = $wpdb->get_results( $sql, ARRAY_A );

		// Get the stored data
		$metadata = get_metadata( 'user', $userID );
		
		// And finally, get the user data for each field name.
		foreach( $fieldNames as $field )
		{
			$optionName = AC_DecodeUserOptionName( WP_MEMBERS_FIELD_DB_MAPPING, $field['option_name'] );
			// The data is in the 0th element of an array belonging to the hash.
			$value = $metadata[$optionName][0];
			$dataArray[] = array( 	"name" => $optionName,
									"tag" => $field['option_value'],
									"value" => $value );
		}

		return $dataArray;
	}
}
?>