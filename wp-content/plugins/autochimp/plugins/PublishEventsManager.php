<?php
// Database option names
define( 'EVENTS_MANAGER_PUBLISH', 'wp88_mc_events_manager_publish' );
define( 'EVENTS_MANAGER_POST_TYPE_PREFIX', 'wp88_mc_events_manager_post_type_' );
define( 'EVENTS_MANAGER_MAPPING_PREFIX', 'wp88_mc_emp_' );

class PublishEventsManager extends ACPlugin
{
	public function PublishEventsManager()
	{
	}

	public static function GetInstalled()
	{
		// If this class exists, then Events Manager is running
		return class_exists( 'EM_Scripts_and_Styles' );
	}
	
	public static function GetUsePlugin()
	{
		// Does the user want to use the Events Manager plugin with AutoChimp?
		return get_option( EVENTS_MANAGER_PUBLISH );
	}
	
	public static function GetPublishVarName()
	{
		return 'on_publish_events_manager';
	}
	
	public static function GetPostTypeVarPrefix()
	{
		return 'on_events_manager_post_';
	}
	
	public static function GetTerms( $postID )
	{
		return get_the_terms( $postID, EM_TAXONOMY_CATEGORY );
	}
	
	public function RegisterHooks()
	{
		// Save the option to publish each post type
		foreach ( $this->m_PostTypes as $postType )
		{
			$optionName = AC_EncodeUserOptionName( EVENTS_MANAGER_POST_TYPE_PREFIX, $postType );
			if ( '1' === get_option( $optionName, '0' ) )
			{
				AC_Log( "Registering PublishEventsManager post type '$postType'." );
				add_action( "publish_$postType", 'PublishEventsManager::OnPublishEventsManagerPostType' );
			}
		}
	}

	//
	//	Function for displaying the UI for Events Manager integration.  Asks the user
	//	what type of posts they'd like to create campaigns for.
	//
	public function ShowPluginSettings()
	{
		// Get settings
		$publish = PublishEventsManager::GetUsePlugin();
		$varName = PublishEventsManager::GetPublishVarName();
		
		// UI that shows if the plugin should be supported
		print '<p><strong>You are using <a target="_blank" href="http://wordpress.org/extend/plugins/events-manager/">Events Manager</a></strong>. With AutoChimp, you can automatically publish Events Manager post types to your MailChimp mailing list.</p>';
		print '<fieldset style="margin-left: 20px;">';
		print "<p><input type=CHECKBOX value=\"$varName\" name=\"$varName\" ";
		if ( '1' === $publish )
			print 'checked';
		print '> Create campaigns in MailChimp from Events Manager posts.</p>';

		// UI to display and publish the plugin's custom post types.
		print '<fieldset style="margin-left: 20px;">';
		print '<strong>Post Type Settings</strong> (Which Events Manager post types would you like to create campaigns for)';
		foreach ( $this->m_PostTypes as $postType )
		{
			$varName = AC_EncodeUserOptionName( PublishEventsManager::GetPostTypeVarPrefix(), $postType );
			$optionName = AC_EncodeUserOptionName( EVENTS_MANAGER_POST_TYPE_PREFIX, $postType );
			$publish = get_option( $optionName );
			print "<p><input type=CHECKBOX value=\"$varName\" name=\"$varName\" ";
			if ( '1' === $publish )
				print 'checked';
			print '> ' . ucwords( $postType ) . '</p>';
		}
		print '</fieldset>';

		print '</fieldset>';
	}

	//
	//	This method saves the settings that are displayed in the ShowPluginSettings
	//	method. It relies on the _POST hash.
	//
	public function SavePluginSettings()
	{
		// Save the option to turn on the plugin
		$publish = PublishEventsManager::GetPublishVarName();
		AC_SetBooleanOption( $publish, EVENTS_MANAGER_PUBLISH );
		
		// Save the option to publish each post type
		foreach ( $this->m_PostTypes as $postType )
		{
			$varName = AC_EncodeUserOptionName( PublishEventsManager::GetPostTypeVarPrefix(), $postType );
			$optionName = AC_EncodeUserOptionName( EVENTS_MANAGER_POST_TYPE_PREFIX, $postType );
			AC_SetBooleanOption( $varName, $optionName );
		}
	}

	//
	//	This fairly complex UI generator dynamically creates mappings which allow 
	//	the user to fine tune where and how campaigns are created.
	//
	public function GenerateMappingsUI( $lists, $groups, $templates, $javaScript )
	{
		print '<p><strong>Since you are using Events Manager</strong>, you can create campaigns based on Event categories as well.  <em>If you use a \'user template\', be sure that the template\'s content section is called \'main\' so that your post\'s content can be substituted in the template.</em></p><p>' . PHP_EOL;
		print '<table id="event_manager_table">' . PHP_EOL;
		print '<tr><th>Category</th><th></th><th>Mailing List</th><th></th><th>Interest Group</th><th></th><th>User Template</th><th></th></tr>';

		// Will need a list of the EM categories (terms) that the user has created.
		$categories = get_terms( EM_TAXONOMY_CATEGORY, 'orderby=count&hide_empty=0' );
		$categories = AC_AssembleTermsArray( $categories );
		
		// Building array that contains the mappings.  Each entry is a row in the UI.
		$mappings = array();

		// Global DB object
		global $wpdb;

		// Pull all of the mappings from the DB.  Each row will have three items.  The
		// category is encoded in the option_name of each of the three along with an
		// index.	
		$options_table_name = $wpdb->prefix . 'options';
		$sql = "SELECT option_name,option_value FROM $options_table_name WHERE option_name like '" . EVENTS_MANAGER_MAPPING_PREFIX . "%' ORDER BY option_name";
		$fields = $wpdb->get_results( $sql, ARRAY_A );
		if ( $fields )
		{
			foreach ( $fields as $field )
			{
				// Split the results into an array which contains info about this mapping
				$info = explode( '_', $field['option_name'] );
				
				// Create a new array for each new index (at the 3rd element of the split
				// string.  
				if ( !isset( $mappings[$info[3]] ) )
					$mappings[$info[3]] = array();
				
				// Push this item into the array.
				array_push( $mappings[$info[3]], $field['option_value'] );
			}
		}
		
		// Now loop through the constructed array and generate a new row for each
		// mapping found.
		$highestMapping = -1;
		foreach( $mappings as $index => $mapping )
		{
			// The category is contained in the 1st element of the returned array.
			// The index is in the 0th.
			$newRow = AC_GenerateCategoryMappingRow($index, EVENTS_MANAGER_MAPPING_PREFIX,
													$categories, $mapping[0],			// In alphabetical order!!  "category" is first
													$lists, $mapping[2], $javaScript,	// "list" is third
													$groups, $mapping[1],				// "group" is second
													$templates, $mapping[3]  );			// "template" is fourth
			if ( $index > $highestMapping )
				$highestMapping = $index;
			print $newRow;
		}
		// Close out the table.	
		print '</table>' . PHP_EOL;
		
		// Generate the javascript that lets users create new mapping rows.
		$nrScript = AC_GenerateNewRowScript($highestMapping + 1, "'" . EVENTS_MANAGER_MAPPING_PREFIX . "'", "'#event_manager_table'",
											$categories, WP88_ANY, 
											$lists, WP88_NONE, 
											$groups, WP88_ANY, 
											$templates, WP88_NONE );
		
		// Add in the "new row" script.  Clicking on this executes the javascript to
		// create a new row to map categories, lists, groups, and templates.
		print '<p><a href="#" id="addNewEMRow" onclick="' . $nrScript . '">Add new Events Manager post category mapping</a></p>' . PHP_EOL;
		print '</p>';
	}

	//
	//	Loops through the expected _POST variables and reads the data in each
	//	and saves it off.
	//	
	//	This function is tied closely to GenerateMappingsUI() and 
	//	GenerateNewRowScript().  So, if you mess with one, you'll likely have to
	//	mess with both.
	//
	public function SaveMappings()
	{
		AC_Log( 'Attempting to save mappings for Events Manager campaigns...' );
		AC_SaveCampaignCategoryMappings( EVENTS_MANAGER_MAPPING_PREFIX );
	}
	
	//
	//	Action hook for the supported posts types.  This is easy; just forward all
	// 	the work to an AutoChimp function().
	//
	public static function OnPublishEventsManagerPostType( $postID )
	{
		AC_Log( "A custom post type from Events Manager was published with ID $postID. Forwarding to AC_OnPublishPost()." );
		AC_OnPublishPost( $postID );
	}
	
	//
	//	Protected 
	//

	// Array of custom post types that Events Manager supports.  These IDs come from
	// the Event Manager plugin itself.
	protected $m_PostTypes = array( EM_POST_TYPE_EVENT, EM_POST_TYPE_LOCATION, 'event-recurring' );
}
?>