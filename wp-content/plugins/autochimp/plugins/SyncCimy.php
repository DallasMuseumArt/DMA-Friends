<?php

define( 'WP88_MC_SYNC_CIMY', 'wp88_mc_sync_cimy' );
define( 'WP88_CIMY_FIELD_MAPPING', 'wp88_mc_cimy_uef_' );

class SyncCimy extends ACSyncPlugin
{
	public function SyncCimy()
	{
	}
	
	public static function GetInstalled()
	{
		return function_exists( 'get_cimyFields' );
	}
	
	public static function GetUsePlugin()
	{
		return get_option( WP88_MC_SYNC_CIMY );
	}
	
	public static function GetSyncVarName()
	{
		return 'on_sync_cimy';
	}
		
	public static function GetSyncDBVarName()
	{
		return WP88_MC_SYNC_CIMY;
	}
	
	
	public function ShowPluginSettings()
	{
		// Get settings
		$syncCimy = SyncCimy::GetUsePlugin();
		$varName = SyncCimy::GetSyncVarName();
		$staticText = get_option( WP88_MC_STATIC_TEXT );
	
		// Start outputting UI
		print '<p><strong>You are using <a target="_blank" href="http://wordpress.org/extend/plugins/cimy-user-extra-fields/">Cimy User Extra Fields</a></strong>. With AutoChimp, you can automatically synchronize your Cimy User Fields with your selected MailChimp mailing list as users join your site and update their profile.  Please ensure that only one list is selected.</p>';
		print '<fieldset style="margin-left: 20px;">';
	
		// Create a hidden field just to signal that the user can save their preferences
		// even if the sync button isn't checked
		print '<input type="hidden" name="cimy_running" />';
		print "<p><input type=CHECKBOX value=\"$varName\" name=\"$varName\" ";
		if ( '1' === $syncCimy )
			print 'checked';
		print '> Automatically sync Cimy User Extra Fields with MailChimp.</p>';
		
		print '</fieldset>';
	}

	//
	//	The key to AutoChimp's efficiency is that the name of the select box and
	//	the option_name field in the database are the same.
	//
	public function GenerateMappingsUI( $tableWidth, $mergeVars )
	{
		// Need to query data in the BuddyPress extended profile table
		global $wpdb;
	
		// Temporary variable for helping generate UI
		$rowCode = $finalText = '';
	
		$cimy_table_name = $wpdb->prefix . 'cimy_uef_fields';
		$sql = "SELECT NAME,TYPE FROM $cimy_table_name";
		$fields = $wpdb->get_results( $sql, ARRAY_A );
		// Create a hidden field just to signal that the user can save their preferences
		print '<br />'.PHP_EOL.'<input type="hidden" name="cimy_running" />'.PHP_EOL;
		if ( $fields )
		{
			foreach ( $fields as $field )
			{
				// Generate a select box for this particular field
				$fieldNameTag = AC_EncodeUserOptionName( WP88_CIMY_FIELD_MAPPING, $field['NAME'] );
				$selectBox = AC_GenerateSelectBox( $fieldNameTag, WP88_IGNORE_FIELD_TEXT, $mergeVars );
				$rowCode .= '<tr class="alternate">' . PHP_EOL . '<td width="65%">' . $field['NAME'] . '</td>' . PHP_EOL . '<td width="35%">' . $selectBox . '</td>' . PHP_EOL . '</tr>' . PHP_EOL;
			}
	
			print( AC_GenerateFieldMappingCode( 'Cimy', $rowCode ) );
		}
	}
	
	
	//
	//	This function uses the global $_POST variable, so only call it at the appropriate times.
	//	Consider refactoring this function to make it not dependent on $_POST.
	//
	public function SaveMappings()
	{
		// Each Cimy field will have a select box selection assigned to it.
		// Save this selection.
		global $wpdb;
		$cimy_table_name = $wpdb->prefix . 'cimy_uef_fields';
		$sql = "SELECT NAME,TYPE FROM $cimy_table_name";
		$fields = $wpdb->get_results( $sql, ARRAY_A );
	
		foreach( $fields as $field )
		{
			// Encode the name of the field
			$selectName = AC_EncodeUserOptionName( WP88_CIMY_FIELD_MAPPING, $field['NAME'] );
	
			// Now dereference the selection
			$selection = $_POST[ $selectName ];
	
			// Save the selection
			update_option( $selectName, $selection );
		}
	}
	
	//
	//	Looks up the user's Cimy profile data and returns a meaningful array of
	//	associations to the user based on what the AutoChimp needs to sync.
	//
	//	There's some CIMY checkbox weirdness here to pay attention to.
	//
	public function FetchMappedData( $userID )
	{
		// User data array
		$dataArray = array();
		// Special array for pain-in-the-axe checkboxes.
		$checkboxArray = array();
	
		// Need to query data in the Cimy tables
		global $wpdb;
		
		// Generate table names - options table tells us what mappings to pay attention to.
		$option_table = $wpdb->prefix . 'options';
		$cimy_data_table = $wpdb->prefix . 'cimy_uef_data';
		$cimy_fields_table = $wpdb->prefix . 'cimy_uef_fields';
		
		// Now, see which Cimy fields the user wants to sync.
		$sql = "SELECT option_name,option_value FROM $option_table WHERE option_name LIKE '" .
				WP88_CIMY_FIELD_MAPPING .
				"%' AND option_value != '" .
				WP88_IGNORE_FIELD_TEXT . "'";
	//	print "<br/>SQL to fetch field names: $sql";
		$fieldNames = $wpdb->get_results( $sql, ARRAY_A );
	
		// Loop through each field that the user wants to sync and hunt down the user's
		// values for those fields and stick them into an array.
		foreach ( $fieldNames as $field )
		{
			$optionName = AC_DecodeUserOptionName( WP88_CIMY_FIELD_MAPPING, $field['option_name'] );
	
			// Big JOIN to get the user's value for the field in question
			// Best to offload this on SQL than PHP.
			$sql = "SELECT $cimy_fields_table.NAME,$cimy_data_table.VALUE,TYPE FROM $cimy_data_table JOIN $cimy_fields_table ON $cimy_fields_table.ID = $cimy_data_table.FIELD_ID WHERE USER_ID = $userID AND NAME = '$optionName' LIMIT 1";
	//		print "<br/>SQL to fetch data: $sql";
			$results = $wpdb->get_results( $sql, ARRAY_A );
	
			// Populate the data array
			if ( !empty( $results[0] ) )
			{
				$value = $results[0]['VALUE'];
	
				// Do conversions based on field type
	
				// First, convert a timestamp to a date.  Without this code,
				// the month and day comes through fine, but the year is in
				// the year 4000 or something.  Low priority - look into this.
				if ( 0 === strcmp( $results[0]['TYPE'],"registration-date" ) )
				{
					$value = date( "Y-m-d", $value );
				}
	
				// If it's a checkbox, do some special work.  This code exists because of
				// how CIMY does checkboxes.  Each integration with a 3rd party plugin is
				// a pain and this is CIMY's.  Basically, in order to properly send checkboxes
				// over to Mailchimp, we need to assemble them in to the same merge variable.
				if ( 0 === strcmp( $results[0]['TYPE'],"checkbox" ) )
				{
					// Change the name from upper case to lower case, then capitalize the 
					// first words. This is the only reasonably easy way to match CIMY 
					// field names to values required by MailChimp.  The rule that I am forced
					// to create is that the MailChimp checkbox value must have each word 
					// capitalized.  So, this is reconstructed here.  CIMY doesn't store 
					// spaces (pain #1) and it capitalizes everything (pain #2).  I can see no
					// need for the second one (the one that's a real pain).
					$name = strtolower( $results[0]['NAME'] );
					$name = str_replace( '_', ' ', $name );
					$name = ucwords( $name );
					
					// Do additional work if the checkbox is checked (value will be 'YES')
					if ( 0 == strcmp( $value, 'YES' ) )
					{
						// Have this funky loop here to update elements in the "value" key of
						// the array.  This is how MailChimp expects checkboxes to be set:
						// with comma-separated strings representing the correct values that
						// you set up in MailChimp.
						$found = FALSE;
						// Loop through each element in the checkbox array - note the reference 
						// (&) because we will be updating the value, not just reading it.
						foreach( $checkboxArray as &$cba )
						{
							// If the same tag is found already, it means we need to ADD it
							// to the EXISTING hash.  The value that MailChimp expects is
							// stored in the "NAME" - Remember, that's because of these
							// artificial rules created to work around the limitations of CIMY.
							if ( in_array( $field['option_value'], $cba ) )
							{
								// Mark it as found so that it's not added twice.
								$found = TRUE;
								// Update the value.
								$cba['value'] = $cba['value'] . ",$name";
							}
						}
						// If not found, it's a new set of checkboxes (or, more likely,
						// it's the first element).
						if ( !$found )
						{
							$cba = array( "name" => $optionName,
										  "tag" => $field['option_value'],
										  "value" => $name	);
							$checkboxArray[] = $cba;
						}
					}
				}
				// Otherwise, this is the other case.  Almost everything goes here instead
				// of all that messy code above.
				else 
				{
					$dataArray[] = array( 	"name" => $optionName,
											"tag" => $field['option_value'],
											"value" => $value );
				}
			}
		}
	
		// Finally, in finishing up with the CIMY specific stuff, add the consolidated
		// checkbox data into the data array. 
		if ( !empty( $checkboxArray ) )
		{
			foreach( $checkboxArray as $cba )
			{
				$dataArray[] = $cba;
			}		
		}
		return $dataArray;
	}
}

?>