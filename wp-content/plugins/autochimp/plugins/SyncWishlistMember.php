<?php
define( 'AUTOCHIMP_WISHLIST_MEMBER_DB_SYNC', 'wp88_mc_sync_wishlistmember' );
define( 'AUTOCHIMP_WISHLIST_MEMBER_DB_FIELD_MAPPING', 'wp88_mc_wishlistmember_' ); // DB field prefix for mappings

class SyncWishlistMember extends ACSyncPlugin
{
	public function SyncWishlistMember()
	{
	}

	public static function GetInstalled()
	{
		// Wishlist is normally encrypted.  I suppose that this class will need to be renamed.
		return class_exists('WishListMemberCore');
	}
	
	public static function GetUsePlugin()
	{
		return get_option( AUTOCHIMP_WISHLIST_MEMBER_DB_SYNC );
	}
	
	public static function GetSyncVarName()
	{
		return 'on_sync_wishlistmember';
	}
	
	public static function GetSyncDBVarName()
	{
		return AUTOCHIMP_WISHLIST_MEMBER_DB_SYNC;
	}

	//
	//	Function for displaying the UI for WPMembers integration.  
	//
	public function ShowPluginSettings()
	{
		// Get settings
		$sync = SyncWishlistMember::GetUsePlugin();
		$varName = SyncWishlistMember::GetSyncVarName();
	
		// Start outputting UI
		print '<p><strong>You are using <a target="_blank" href="http://member.wishlistproducts.com/">Wishlist Member</a></strong>. With AutoChimp, you can automatically synchronize your Wishlist Member user profile fields with your selected MailChimp mailing list as users join your site and update their profile.  Please ensure that only one list is selected.</p>';
		print '<fieldset style="margin-left: 20px;">';
		print "<p><input type=CHECKBOX value=\"$varName\" name=\"$varName\" ";
		if ( '1' === $sync )
			print 'checked';
		print '> Automatically sync Wishlist Member profile fields with MailChimp.</p>';
		print '</fieldset>';
	}
	
	//
	// Generates the UI that allows the user to map form fields to MailChimp fields.
	//
	public function GenerateMappingsUI( $tableWidth, $mergeVars )
	{
		// Need to query data in the BuddyPress extended profile table
		global $wpdb;
	
		$wishlist_table_name = $wpdb->prefix . 'wlm_options';
		$sql = "SELECT option_name,option_value FROM $wishlist_table_name WHERE `option_name` LIKE 'CUSTOMREGFORM-%' ORDER BY `option_name` ASC";
		$fields = $wpdb->get_results( $sql, ARRAY_A );
		// Create a hidden field just to signal that the user can save their preferences
		print '<br />'.PHP_EOL.'<input type="hidden" name="wishlist_running" />'.PHP_EOL;
		if ( $fields )
		{
			foreach ( $fields as $field )
			{
				$data = unserialize( $field['option_value'] );
				$formFields = explode( ',', $data['fields'] );
				$rowCode = '';
				foreach ( $formFields as $formField )
				{
					// Skip firstname and lastname.  Those are handled automatically.
					// Having them here will just confuse people and will certainly 
					// mess things up if the user has multiple forms.
					if ( $this->FilterUnusedFields( $formField ) )
						continue;

					// Generate a select box for this particular field
					$fieldNameTag = AC_EncodeUserOptionName( AUTOCHIMP_WISHLIST_MEMBER_DB_FIELD_MAPPING, $formField );
					$selectBox = AC_GenerateSelectBox( $fieldNameTag, WP88_IGNORE_FIELD_TEXT, $mergeVars );
					$rowCode .= '<tr class="alternate">' . PHP_EOL . '<td width="65%">' . $formField . '</td>' . PHP_EOL . '<td width="35%">' . $selectBox . '</td>' . PHP_EOL . '</tr>' . PHP_EOL;
				}
				$formName = $data['form_name'];
				print( AC_GenerateFieldMappingCode( "Wishlist Form '$formName'", $rowCode ) );
				print '<br />';
			}
		}
		return $finalText;
	}
	
	//
	//	This function uses the global $_POST variable..
	//
	public function SaveMappings()
	{
		// Need to query data in the BuddyPress extended profile table
		global $wpdb;
		$wishlist_table_name = $wpdb->prefix . 'wlm_options';
		$sql = "SELECT option_name,option_value FROM $wishlist_table_name WHERE `option_name` LIKE 'CUSTOMREGFORM-%' ORDER BY `option_name` ASC";
		$fields = $wpdb->get_results( $sql, ARRAY_A );
		if ( !$fields )
			return;

		foreach ( $fields as $field )
		{
			$data = unserialize( $field['option_value'] );
			$formFields = explode( ',', $data['fields'] );
			foreach ( $formFields as $formField )
			{
				if ( $this->FilterUnusedFields( $formField ) )
					continue;

				// Encode the name of the field
				$selectName = AC_EncodeUserOptionName( AUTOCHIMP_WISHLIST_MEMBER_DB_FIELD_MAPPING, $formField );
		
				// Now dereference the selection
				$selection = $_POST[ $selectName ];
						
				// Save the selection
				update_option( $selectName, $selection );
			}
		}
	}
	
	//
	//	Looks up the user's Wishlist Member data and returns an array formatted for 
	//	MailChimp of fields mapped to data for the user.  The Wishlist plugin 
	//	serializes this data into a single field which makes the SQL easy.
	//
	public function FetchMappedData( $userID )
	{
		// User data array
		$dataArray = array();
		// Need to query data in the Wordpress options table
		global $wpdb;
		
		// Generate table names
		$optionTable = $wpdb->prefix . 'options';
		
		// Now, see which custom fields the user wants to sync.
		$sql = "SELECT option_name,option_value FROM $optionTable WHERE option_name LIKE '" .
				AUTOCHIMP_WISHLIST_MEMBER_DB_FIELD_MAPPING .
				"%' AND option_value != '" .
				WP88_IGNORE_FIELD_TEXT . "'";
		$fieldNames = $wpdb->get_results( $sql, ARRAY_A );

		// Get the stored data and unserialize it
		$optionTable = $wpdb->prefix . 'wlm_user_options';
		$sql = "SELECT option_value FROM $optionTable WHERE user_id=$userID AND option_name='wpm_useraddress'";
		$unserialized = $wpdb->get_var( $sql );
		$data = unserialize( $unserialized );
		
		// And finally, get the user data for each field name.
		foreach( $fieldNames as $field )
		{
			if ( $this->FilterUnusedFields( $field['option_name'] ) )
				continue;

			$optionName = AC_DecodeUserOptionName( AUTOCHIMP_WISHLIST_MEMBER_DB_FIELD_MAPPING, $field['option_name'] );
			$value = $data[$optionName];
			$dataArray[] = array( 	"name" => $optionName,
									"tag" => $field['option_value'],
									"value" => $value );
		}
		AC_Log( $dataArray );
		return $dataArray;
	}
	
	//
	// Helper function to filter out duplicate fields in Wishlist.
	//
	protected function FilterUnusedFields( $fieldName )
	{
		if ( 0 === strcmp( $fieldName, 'firstname' ) || 0 ===strcmp( $fieldName, 'lastname' ) )
			return TRUE;
		return FALSE;
	}
}
?>