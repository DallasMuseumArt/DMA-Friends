<?php

//
//	This function will generate table code that third party plugins can use to 
//  generate mappings between their user fields and MailChimp's fields.
//
//	$pluginName - pass in the name of the third-party plugin that you want to support.
//	This function will add a table header with this string in the heading.
//	$rowCode - pass in row code (everything within each "<tr></tr>") and this function
//  will return the full table code used to generate the mapping UI.
//
function AC_GenerateFieldMappingCode( $pluginName, $rowCode )
{
	// Generate the table now
	$tableText = '<div id=\'filelist\'>' . PHP_EOL;
	$tableText .= '<table class="widefat" style="width:<?php echo $tableWidth; ?>px">
			<thead>
			<tr>
				<th scope="col">'.$pluginName.' User Field:</th>
				<th scope="col">Assign to MailChimp Field:</th>
			</tr>
			</thead>' . PHP_EOL;
	$tableText .= $rowCode;
	$tableText .= '</table>' . PHP_EOL . '</div>' . PHP_EOL;
	return $tableText;
}

//
//	This helpier function generates HTML select box code that can be used for selecting
//	a MailChimp mapping.  You typically call this function for each field that you want
//	to map a value in MailChimp.  This function will also auto-select a value by
// 	searching for the mapping string (stored in the options table) within the hash
//	of options passed in.
//
//	$selectBox - the name of the select box (so it can be identified later)
//	$specialOption - A special option value.  Typically, "All" or "None"
//	$options - A hash of options which maps option name to value.  Can be NULL.
//	$javaScript - Optional JavaScript (leave this argument out if you don't need it)
//	that's attached to the select box.  Most common usage, of course, is the onClick()
//	function.
//
function AC_GenerateSelectBox( $selectName, $specialOption, $options, $selectedVal = NULL, $javaScript = '')
{
	// See which field should be selected (if any)
	if ( NULL == $selectedVal )
		$selectedVal = get_option( $selectName );
	
	// Create a select box from MailChimp merge values
	$selectBox = '<select name="' . $selectName . '"' . $javaScript . '>' . PHP_EOL;

	// Create the special option
	$selectBox .= '<option>' . $specialOption . '</option>' . PHP_EOL;
	
	if ( NULL != $options )
	{
		// Loop through each merge value; use the name as the select
		// text and the tag as the value that gets selected.  The tag
		// is what's used to lookup and set values in MailChimp.
		foreach( $options as $field => $tag )
		{
			// Not selected by default
			$sel = '<option value="' . $tag . '"';
	
			// Should it be $tag?  Is it the same as the tag that the user selected?
			// Remember, the tag isn't visible in the combo box, but it's saved when
			// the user makes a selection.
			if ( 0 === strcmp( $tag, $selectedVal ) )
				$sel .= ' selected>';
			else
				$sel .= '>';
	
			// print an option for each merge value
			$selectBox .= $sel . $field . '</option>' . PHP_EOL;
		}
	}
	$selectBox .= '</select>' . PHP_EOL;
	// Return the HTML code
	return $selectBox;
}

//
//	Saves dynamically mappings from categories (or terms) to mailing lists, interest
//	groups, and templates.  This function uses indexes from the names of the select
//	boxes to gather data.  However, when saving, it condenses indexes sequentially.
//
//	Arguments:
//		$mappingPrefix - this is the prefix used to save data to the DB (and to
//		create select box names).  Using this, data is safely divided among WordPress
//		categories and terms from other plugins.
//
//	This function uses the global $_POST variable, so only call it at the appropriate 
//	times.	Consider refactoring this function to make it not dependent on $_POST.
//
function AC_SaveCampaignCategoryMappings( $mappingPrefix )
{
	// Holds indexes of existing data saved.
	$indexArray = array();
	$indexCounter = 0;
	$condensedIndex = 0;
	
	// Global DB object
	global $wpdb;

	// Build up an array of indexes.  A bit inefficient, but keeps the code much cleaner.
	$options_table_name = $wpdb->prefix . 'options';
	$sql = "SELECT option_name FROM $options_table_name WHERE option_name like '" . $mappingPrefix . "%' ORDER BY option_name";
	$fields = $wpdb->get_results( $sql );
	// There are FOUR elements for each index, so skip these.
	$everyFourth = 0;
	foreach ( $fields as $field )
	{
		$mod = $everyFourth % 4;
		if ( 0 === $mod )
		{
			$fieldInfo = explode( '_', $field->option_name );
			// For this class, the index is at position 3
			$indexArray[] = $fieldInfo[3];			
		}
		$everyFourth++;
	}
	AC_Log( "Mapping prefix is '$mappingPrefix'." );
	if ( !empty( $indexArray ) )
		AC_Log( $indexArray );
	
	// Set the count now.
	$count = isset( $indexArray[$indexCounter] ) ? $indexArray[$indexCounter] : 0;
	AC_Log("Starting with index count $count.");

	// Loop through the Events Manager category post variables until one is 
	// not found.
	while ( isset( $_POST[ $mappingPrefix . $count . WP88_CATEGORY_SUFFIX ]) )
	{
		// Encode the general name of the fields for this set
		$selectName = AC_EncodeUserOptionName( $mappingPrefix, $count );
		$dbName = AC_EncodeUserOptionName( $mappingPrefix, $condensedIndex );
		AC_Log( "The select name is $selectName.  The dbName is $dbName." );

		// Save the category selection - note if one of these POST variables is here,
		// then they are all expected to be here.  Also note that the $dbName 
		// variable will hold the condensed index whereas the select name could
		// have indexes spread out.
		$categorySelection = $_POST[ $selectName . WP88_CATEGORY_SUFFIX ];
		update_option( $dbName . WP88_CATEGORY_SUFFIX, $categorySelection );

		// Save off the mailing list.  Exact same principle.						
		$listSelection = $_POST[ $selectName . WP88_LIST_SUFFIX ];
		update_option( $dbName . WP88_LIST_SUFFIX, $listSelection );
		
		// Save off interest group selection now. 
		$groupSelection = $_POST[ $selectName . WP88_GROUP_SUFFIX ];
		update_option( $dbName . WP88_GROUP_SUFFIX, $groupSelection );
		
		// Same thing for templates
		$templateSelection = $_POST[ $selectName . WP88_TEMPLATE_SUFFIX ];
		update_option( $dbName . WP88_TEMPLATE_SUFFIX, $templateSelection );

		$condensedIndex++;
		$indexCounter++;
		// Increment the counter either to the value of the next index or
		// one beyond the last value.			
		$count = isset( $indexArray[$indexCounter] ) ? $indexArray[$indexCounter] : $count + 1;
	}
}
	
//
//	Returns HTML row code for a new category/term assignment.
//
function AC_GenerateCategoryMappingRow( $index, $selectPrefix,
										$categories, $selectedCat,
										$lists, $selectedList, $javaScript,
										$groups, $selectedGroup, 
										$templates, $selectedTemplate )
{
	$out = '<tr><td>' . PHP_EOL;
	
	$selectBox = AC_GenerateSelectBox( $selectPrefix . $index . WP88_CATEGORY_SUFFIX, WP88_ANY, $categories, $selectedCat );
	$out .= $selectBox . '</td>' . PHP_EOL . '<td>campaigns go to</td><td>';

	// Assemble the final Javascript
	$groupSelectName = $selectPrefix . $index . WP88_GROUP_SUFFIX;
	$javaScript .= "switchInterestGroups('$groupSelectName',this.value,groupsHash);\"";
	$selectBox = AC_GenerateSelectBox( $selectPrefix . $index . WP88_LIST_SUFFIX, WP88_NONE, $lists, $selectedList, $javaScript );
	$out .= $selectBox . '</td>' . PHP_EOL . '<td>and group</td><td>';
	
	// Start assembling the group select box
	$selectBox = AC_GenerateSelectBox( $groupSelectName, WP88_ANY, $groups[$selectedList], $selectedGroup );
	$out .= $selectBox . '</td>' . PHP_EOL . '<td>using</td><td>';
	
	// Assemble the final select box - templates
	$selectBox = AC_GenerateSelectBox( $selectPrefix . $index . WP88_TEMPLATE_SUFFIX, WP88_NONE, $templates, $selectedTemplate );
	$out .= $selectBox . '</td>' . PHP_EOL;
	
	// Create the delete button
	$out .= '<td><button type="submit" name="' . $selectPrefix . $index . WP88_DELETE_MAPPING_SUFFIX . '" value="' . $selectPrefix . $index . '" onClick="return confirm(\'Are you sure?\');">X</button></td></tr>';
	
	return $out;
}

//
//	This function generates javascript that, when called, will generate a new row
//	that users can use to map categories to lists, etc.  This is very similar to 
//	GenerateCategoryMappingRow() so if you make changes there, then watch for your
//	changes here AND in the javascript file itself.
//
function AC_GenerateNewRowScript($numExistingRows, $objectPrefix, $appendTo,
								 $categories, $specialCategory,
								 $lists, $specialList,
								 $groups, $specialGroup,
								 $templates, $specialTemplate )
{
	// Set up the categories hash first
	$nrScript = 'var categories={';
	// Add the special category
	$nrScript .= "'$specialCategory':null"; 
	foreach ( $categories as $name => $slug ) 
	{
		$nrScript .= ",'" . addslashes($name) . "':'$slug'";
	}
	$nrScript .= '};';

	// Now set up the lists (almost the same thing)
	$nrScript .= 'var lists={';
	$nrScript .= "'$specialList':null"; 
	foreach ( $lists as $list => $id ) 
	{
		$name = $list;
		$nrScript .= ",'" . addslashes($name) . "':'$id'";
	}
	// As part of the lists, set up the change options which will affect the
	// groups select box.  Close off the previous array too!
	$nrScript .= "};listCO={};";
	foreach( $groups as $listID => $lg )
	{
		$groupCSVString = addslashes( implode( ',', array_values( $lg ) ) );
		$nrScript .= "listCO['$listID']='$groupCSVString'.split(',');";
	}

	// Set up groups, which is very different.  It only starts with the special
	// option, and other options are added later as the user selects lists.
	$nrScript .= "var groups={'$specialGroup':null};";
	
	// Finally, set up the templates.  Straightforward.
	$nrScript .= 'var templates={';
	$nrScript .= "'$specialTemplate':null"; 
	foreach ( $templates as $template => $id ) 
	{
		$name = $template;
		$nrScript .= ",'" . addslashes($name) . "':'$id'";
	}
	$nrScript .= '};';
			
	$nrScript .= "AddCategoryTableRow($numExistingRows,$objectPrefix,$appendTo,categories,lists,listCO,groups,templates);";
	return $nrScript;
}


//
//	This helper function generates the name of a field mapping (from WordPress or a 
//	supported plugin) to MailChimp for the database.  It generates this with a prefix
//	that is unique to the plugin and an option name.  It also cleans up any special
//	characters that are DB-sensitive.  It's not perfect, but can be extended in the
//	future to support other strange third party naming schemes.  If this code is
//	changed, just make sure that it doesn't break existing supported plugins.
//
//	$encodePrefix - the DB option name prefix.  Make sure this is unique to the
//	plugin being supported.
//	$optionName - the name of the option in the plugin.  This string is determined
//	by the supported plugin itself.
//
function AC_EncodeUserOptionName( $encodePrefix, $optionName )
{
	// Tack on the prefix to the option name
	$encoded = $encodePrefix . $optionName;

	// Make sure the option name has no spaces; replace them with hash tags.
	// Not using underscores or dashes since those are commonly used in place
	// of spaces.  If an option name has "#" in it, then this scheme breaks down.
	$encoded = str_replace( ' ', '#', $encoded );
	
	// Periods are also problematic, as reported on 8/7/12 by Katherine Boroski.
	$encoded = str_replace( '.', '*', $encoded );
	
	// "&" symbols are problematic, as reported on 8/23/12 by Enrique.
	$encoded = str_replace( '&', '_', $encoded );

	return $encoded;
}

//
//	This function is the inverse of the Encode function.  Given a decode prefix and
//	and the encoded option name, strips out the prefix, decodes special characters,
//	and returns the original option name.
//
//	Note that if you change the Encode function, then you must also change this one
//	and vice versa.
//
function AC_DecodeUserOptionName( $decodePrefix, $optionName )
{
	// Strip out the searchable tag
	$decoded = substr_replace( $optionName, '', 0, strlen( $decodePrefix ) );

	// Replace hash marks with spaces, asterisks with periods, etc.
	$decoded = str_replace( '#', ' ', $decoded );
	$decoded = str_replace( '*', '.', $decoded );
	$decoded = str_replace( '_', '&', $decoded );

	return $decoded;
}

//
//	Sets options for all kinds of AutoChimp variables.  Uses the _POST hash, so
//	this function is typically used when forms are submitted.
//
//	$postVar - the name of the HTML option (stored in the _POST hash)
//	$optionName - the name of the option in the database
//
function AC_SetBooleanOption( $postVar, $optionName )
{
	if ( isset( $_POST[$postVar] ) )
	{
		update_option( $optionName, '1' );
		//AC_Log( "TURNING ON:  The POST variable is $postVar.  The optionName is $optionName" );
	}
	else
	{
		update_option( $optionName, '0' );
		//AC_Log( "TURNING OFF:  The POST variable is $postVar.  The optionName is $optionName" );
	}
}

//
// Trimps the excerpt of a post.  This is a replacement of wp_trim_excerpt() 'cause
// I'm not a fan of how it works sometimes, specifically when it strips out stuff.
//
function AC_TrimExcerpt( $text )
{
	$text = strip_shortcodes( $text );
	$text = apply_filters('the_content', $text);
	$text = str_replace(']]>', ']]&gt;', $text);
	$excerpt_length = apply_filters('excerpt_length', 55);
	$permalink = get_permalink( $postID );
	$linkTo = "<p>Read the post <a href=\"$permalink\">here</a>.</p>";
	$excerpt_more = apply_filters('excerpt_more', ' ' . $linkTo);
	return AC_TrimWords( $text, $excerpt_length, $excerpt_more );
}
//
// Trimps words up to a certain point.  This is a replacement of wp_trim_words() 'cause
// I'm not a fan of how it works sometimes, specifically when it strips out stuff.
//
function AC_TrimWords( $text, $excerpt_length, $excerpt_more )
{
	if ( null === $more )
		$more = __( '&hellip;' );
	$original_text = $text;
	//
	// THE FALSE IN wp_strip_all_tags() IS THE ONLY DIFFERENCE!!!
	//
	$text = wp_strip_all_tags( $text, FALSE );
	/* translators: If your word count is based on single characters (East Asian characters),
	   enter 'characters'. Otherwise, enter 'words'. Do not translate into your own language. */
	if ( 'characters' == _x( 'words', 'word count: words or characters?' ) && preg_match( '/^utf\-?8$/i', get_option( 'blog_charset' ) ) ) 
	{
		$text = trim( preg_replace( "/[\n\r\t ]+/", ' ', $text ), ' ' );
		preg_match_all( '/./u', $text, $words_array );
		$words_array = array_slice( $words_array[0], 0, $num_words + 1 );
		$sep = '';
	}
	else
	{
        $words_array = preg_split( "/[\n\r\t ]+/", $text, $num_words + 1, PREG_SPLIT_NO_EMPTY );
		$sep = ' ';
	}
	if ( count( $words_array ) > $num_words ) 
	{
        array_pop( $words_array );
        $text = implode( $sep, $words_array );
        $text = $text . $more;
	}
	else
	{
        $text = implode( $sep, $words_array );
	}
	return apply_filters( 'wp_trim_words', $text, $num_words, $more, $original_text );
}

//	(Note: AutoChimp 2.0 only supports the first level of interest groups.
//	Hence, the [0].)
function AC_AssembleGroupsHash( $mcGroupsArray )
{
	$groupHash = array();
	foreach ( $mcGroupsArray[0]['groups'] as $group )
	{
		$groupHash[$group['name']] = $group['name'];
	}
	return $groupHash;
}

//	(Note: AutoChimp 2.0 only supports the first level of interest groups.
//	Hence, the [0].)
function AC_AssembleGroupsArray( $mcGroupsArray )
{
	$groupArray = array();
	foreach ( $mcGroupsArray[0]['groups'] as $group )
	{
		$groupArray[] = $group['name'];
	}
	return $groupArray;
}

//
// This function assembles the results of get_terms into an array useful to AutoChimp
// which is an associative array of names to slugs.
//
function AC_AssembleTermsArray( $terms )
{
	$formatted = array();
	if ( $terms )
	{
		foreach( $terms as $term )
		{
			$formatted[$term->name] = $term->slug;
		}
	}
	return $formatted;	
}

//
//	Logger for AutoChimp.  Enable WP_DEBUG in wp-config.php to get messages.
//  Well, actually, the WP_DEBUG doesn't seem to work for me, but this does:
//
//		tail -f error.log | grep -i 'AutoChimp'
//
//	Run the above command on the Apache error log file.
//
function AC_Log( $message )
{
	if ( TRUE === WP_DEBUG )
	{
        if ( is_array( $message ) || is_object( $message ) )
        {
            error_log( 'AutoChimp: ' . print_r( $message, true ) );
        }
        else
        {
            error_log( "AutoChimp:  $message" );
        }
    }
}

//
//	2.02 migration of data for category mappings for campaigns.  Using a more
//	system now which requires the current data to follow the updated naming
//	convention.
//
function AC_UpdateCampaignCategoryMappings()
{
	// Need to query data in the BuddyPress extended profile table
	global $wpdb;

	// Get this site's categories	
	$categories = get_categories( 'hide_empty=0&orderby=name' );
	
	// Data counter
	$counter = 1;
	$newIndex = 0;

	// Pull all of the mappings from the DB and update the option name.  There
	// will be only one row for each category, so an index of 0 is safe.
	$options_table_name = $wpdb->prefix . 'options';
	$sql = "SELECT option_name,option_value FROM $options_table_name WHERE option_name LIKE '" . WP88_CATEGORY_LIST_MAPPING . "%' ORDER BY option_name";
	$fields = $wpdb->get_results( $sql );
	if ( $fields )
	{
		foreach ( $fields as $field )
		{
			$data = AC_DecodeUserOptionName( WP88_CATEGORY_LIST_MAPPING , $field->option_name );
			$catInfo = explode( '&', $data );

			// Set a suffix.  Will be either "list", "group", or "template".  The
			// original mapping didn't include "list".
			$suffix = '_list';
			if ( isset( $catInfo[1] ) )
				$suffix = "_$catInfo[1]";
			
			// Inefficient, but done once.  This is necessary because AutoChimp
			// foolishly used to store the category name instead of slug in the
			// option_name.  So, this code looks up the category by name and 
			// finds the slug and writes that.
			foreach ( $categories as $category )
			{
				// Look for a match.
				if ( 0 === strcmp( $catInfo[0], $category->name ) )
				{
					
					// Generate the new name and save it.
					$newName = AC_EncodeUserOptionName( WP88_CATEGORY_MAPPING_PREFIX, $newIndex . $suffix );
					update_option( $newName, $field->option_value );
					AC_Log( "Migrated $field->option_value from $field->option_name to $newName." );
					// Note that in 2.02 and earlier, there were three rows per
					// mapping.  This needs to be translated into the new four
					// rows per mapping.  So, when "_list" is encountered, just
					// take the time to write out "_category" too.
					if ( 0 === strcmp( $suffix, '_list' ) )
					{
						$newName = AC_EncodeUserOptionName( WP88_CATEGORY_MAPPING_PREFIX, $newIndex . '_category' );
						update_option( $newName, $category->slug );
						AC_Log( "Migrated $category->slug from $field->option_name to $newName." );
					}					
				}
			}
			// Update this every three passes.
			if ( 0 == $counter % 3 )
				$newIndex++;
			$counter++;
		}
	}
	
	// Now delete the old rows.
	$sql = "DELETE FROM $options_table_name WHERE option_name LIKE '" . WP88_CATEGORY_LIST_MAPPING . "%'";
	AC_Log( "About to delete rows with this statement:  $sql" );
	$numRows = $wpdb->query( $sql );
	if ( 0 < $numRows )
		AC_Log( "Deleted $numRows from the $options_table_name table." );
	else
		AC_Log( "No rows were found.  Nothing deleted." );
}

//
// This function is not needed by third-party developers of plugins that add support
// for other third party WordPress plugins (like BuddyPress, Wishlist, etc.).  This
// function just shows some support info and affiliate ads to help support the plugin.
//
function AC_ShowSupportInfo( $uiWidth )
{
	$pluginFolder = get_bloginfo('wpurl') . '/wp-content/plugins/autochimp/';
?>
	<div id="info_box" class="postbox" style="width:<?php echo $uiWidth; ?>px">
	<h3 class='hndle'><span>Support and Help</span></h3>
	<div class="inside">
	<table border="0">
		<tr>
			<td>
				<img src="<?php echo $pluginFolder;?>help.png"><a style="text-decoration:none;" href="http://www.wandererllc.com/company/plugins/autochimp" target="_blank"> Support and Help</a>,
				<br />
				<a style="text-decoration:none;" href="http://www.wandererllc.com/company/contact/" target="_blank">Custom plugins</a>,
				<br />
				Leave a <a style="text-decoration:none;" href="http://wordpress.org/extend/plugins/autochimp/" target="_blank">good rating</a>.
			</td>
			<td><a href="http://member.wishlistproducts.com/wlp.php?af=1080050" target="_blank"><img src="http://www.wishlistproducts.com/affiliatetools/images/WLM_120X60.gif" border="0"></a></td>
			<td><a href="http://themeforest.net?ref=Wanderer" target="_blank"><img src="http://envato.s3.amazonaws.com/referrer_adverts/tf_125x125_v5.gif" border=0 alt="ThemeForest - Premium WordPress Themes" width=125 height=125></a></td>
		</tr>
	</table>
	</div>
	</div>
<?php	
}

?>