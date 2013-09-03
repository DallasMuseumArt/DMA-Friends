<?php

//
// ACPlugin - base class for all AutoChimp plugin classes.  You will not derive
// directly from this class, but from one of the subclasses below.  However, you
// will implement some of the functions in this class.
//
class ACPlugin
{
	// 
	// Usually, detecting if a plugin exists is as easy as detecting the presence
	// of a class or function belonging to the plugin.
	//
	public static function GetInstalled()
	{
		return FALSE;
	}
	
	//
	// Returns true if the user wants to use this plugin integration.  In other words,
	// if the user has checked the option to integrate this plugin with AutoChimp,
	// this function will return TRUE.
	//
	public static function GetUsePlugin()
	{
		return FALSE;
	}

	//
	// Function for registering hooks (like actions or filters).  If you don't need 
	// any, then just don't implement your own version.
	//
	public function RegisterHooks()
	{}
	
	//
	// Functions for registering and enqueuing JS scripts.  If you use one, use 
	// both.  
	//
	public function RegisterScripts( $pluginFolder )
	{}
	
	public function EnqueueScripts()
	{}
	
	//
	// Function for displaying the UI for your integration.  This UI will appear
	// on the "Plugins" tab.
	//
	public function ShowPluginSettings()
	{}
	
	//
	// Function called when plugin saving settings.  You can access $_POST variables
	// based on variables that you created in the ShowPluginSettings() method and write 
	// them to the database.
	//
	public function SavePluginSettings()
	{}
}

//
// ACSyncPlugin - All Sync plugins must derive from this class.  This is the most
// popular type of plugin to support.  It syncs signup data with MailChimp.
//
class ACSyncPlugin extends ACPlugin
{
	//
	// Returns the name of the HTML sync control.  Make sure it's unique.  You'll
	// read this variable and write the value to the DB when a user saves his or
	// her settings.
	//
	public static function GetSyncVarName()
	{}
	
	//
	// Returns the name of the option in the options table that holds whether the
	// user wants to sync this plugin.  Must be unique.
	//
	public static function GetSyncDBVarName()
	{}
	
	//
	// By implementing GetSyncVarName() and GetSyncDBVarName() AND you have standard
	// simple settings, you get saving for free.  Only implement if you have special
	// settings, but strive hard not to.
	//
	public function SavePluginSettings()
	{
		AC_SetBooleanOption( $this::GetSyncVarName(), $this::GetSyncDBVarName() );
	}
	
	//
	// This method displays a table of plugin field names to select boxes of
	// MailChimp fields. 
	//	
	public function GenerateMappingsUI( $tableWidth, $mergeVars )
	{}
	
	//
	// This method saves the user's choices of mappings to the database.  It
	// uses the global $_POST variable to read the mappings.
	//
	public function SaveMappings()
	{}
	
	//
	// This is the most challenging method.  It looks up data for the user ID
	// passed in, collects the data that the plugin has set, and formats an array
	// to be sent to sync MailChimp.
	//
	public function FetchMappedData( $userID )
	{}
}

//
// ACPublishPlugin - All Publish plugins must derive from this class.
//
class ACPublishPlugin extends ACPlugin
{
	//
	// Returns the name of the HTML publish control.  Make sure it's unique.  You'll
	// read this variable and write the value to the DB when a user saves his or
	// her settings.
	//
	public static function GetPublishVarName()
	{}
	
	//
	// This is a prefix string used to name your controls so that you can easily
	// identify them when the user wants to save settings.  Make sure this is unique
	// of course.
	//
	public static function GetPostTypeVarPrefix()
	{}

	//
	// Returns the same thing as get_the_terms().  The calling function will check to
	// see if any posts have been created with any of these terms.
	//
	public static function GetTerms( $postID )
	{}

	//
	// Given an array of mailing lists, interest groups, and templates, as well as
	// some Javascript to aid in select box handling, this method generates row-
	// by-row UI of term (category) to list to group to template mappings.  This
	// method should generate all of the UI inline with print statements.
	//	
	public function GenerateMappingsUI( $lists, $groups, $templates, $javaScript )
	{}
	
	//
	// This method saves the user's choices of mappings to the database.  It
	// uses the global $_POST variable to read the mappings.
	//
	public function SaveMappings()
	{}
}

//
// ACContentPlugin - All Content plugins must derive from this class.  Content
// plugins are fairly simple.  They just detect shortcode for the WordPress plugin
// that they represent then, when a post is created, runs the shortcode through
// the plugin to generate the final text that will go to the campaign.
//
class ACContentPlugin extends ACPlugin
{
	//
	// This straightforward method just takes content and converts any supported
	// shortcode and returns the updated content.
	//
	public function ConvertShortcode( $content )
	{
		return $content;
	}
}

//
// Collection Classes
//

//
// This class is used only by AutoChimp.  Third party plugins for AutoChimp do not
// need this class or any of the others below.
//
class ACPlugins
{
	// The constructor is used to load up all plugin files and include them.  See
	// this post (problem number two):  
	//		http://www.wandererllc.com/company/2013/07/problems-with-autochimp-2-10/
	// Loading the classes may not be happening properly for functions that call
	// static members, so this constructor attempts to include each of the plugin
	// files.
	function __construct()
	{
		$plugins = $this->GetPluginClasses( $this->GetType() );
		foreach ( $plugins as $plugin )
		{
			include_once( plugin_dir_path( __FILE__ ) . 'plugins/' . $plugin . '.php' );
		}
	}
	
	public function ShowPluginSettings()
	{
		$plugins = $this->GetPluginClasses( $this->GetType() );
		foreach ( $plugins as $plugin )
		{
			if ( $plugin::GetInstalled() )
			{
				$p = new $plugin;
				$p->ShowPluginSettings();
			}
		}
	}

	public function SavePluginSettings()
	{
		//AC_Log( 'Running ACPlugins::SavePluginSettings()' );
		$plugins = $this->GetPluginClasses( $this->GetType() );
		foreach ( $plugins as $plugin )
		{
			if ( $plugin::GetInstalled() )
			{
				$p = new $plugin;
				$p->SavePluginSettings();
			}
		}
	}
	
	public function RegisterHooks()
	{
		$plugins = $this->GetPluginClasses( $this->GetType() );
		foreach ( $plugins as $plugin )
		{
			if ( $plugin::GetInstalled() && $plugin::GetUsePlugin() )
			{
				$p = new $plugin;
				$p->RegisterHooks();
			}
		}
	}
	
	public function RegisterScripts( $pluginFolder )
	{
		$plugins = $this->GetPluginClasses( $this->GetType() );
		foreach ( $plugins as $plugin )
		{
			if ( $plugin::GetInstalled() && $plugin::GetUsePlugin() )
			{
				$p = new $plugin;
				$p->RegisterScripts( $pluginFolder );
			}
		}
	}
	
	public function EnqueueScripts()
	{
		$plugins = $this->GetPluginClasses( $this->GetType() );
		foreach ( $plugins as $plugin )
		{
			if ( $plugin::GetInstalled() && $plugin::GetUsePlugin() )
			{
				$p = new $plugin;
				$p->EnqueueScripts();
			}
		}
	}
	
	public function GetPluginClasses( $classType )
	{
		// array to build the list in
		$classlist = array();
	
		// Attempt to open the folder
		$path = WP_PLUGIN_DIR . '/autochimp/plugins';
		if ( ( $p = opendir( $path ) ) !== FALSE )
		{
			// Read the directory for items inside it.
			while ( ( $item = readdir( $p ) ) !== FALSE )
			{
				// First check if the filter succeeds for the class type
				$filter = TRUE;
				// For a blank classType, get everything.  Otherwise, only get matches.
				if ( 0 !== strlen( $classType ) )
					$filter = ( 0 === strpos( $item, $classType ) );
				
				// Make sure the file is a PHP file as well passes the filter test
				if ( $filter && 0 < strpos( $item, '.php') )
				{
					$class = basename( $item, '.php' );
					array_push( $classlist, $class );
				}
			}
			closeDir($p);
		}
		return $classlist;
	}

	public function GetType()
	{
		// This is the same as asking for all plugins
		return '';
	}
}

//
// ACSyncPlugins
//
class ACSyncPlugins extends ACPlugins
{
	public function GetType()
	{
		return 'Sync';
	}

	public function GenerateMappingsUI( $tableWidth, $mergeVars )
	{
		$syncPlugins = $this->GetPluginClasses( $this->GetType() );
		foreach ( $syncPlugins as $plugin )
		{
			if ( $plugin::GetInstalled() && $plugin::GetUsePlugin() )
			{
				$mapper = new $plugin;
				$mapper->GenerateMappingsUI( $tableWidth, $mergeVars );
			}
		}
	}
	
	public function SaveMappings()
	{
		$publishPlugins = $this->GetPluginClasses( $this->GetType() );
		foreach ( $publishPlugins as $plugin )
		{
			if ( $plugin::GetInstalled() && $plugin::GetUsePlugin() )
			{
				$mapper = new $plugin;
				$mapper->SaveMappings();
			}
		}
	}
	
	public function SyncData( &$merge_vars, $userID )
	{
		$syncPlugins = $this->GetPluginClasses( $this->GetType() );
		foreach ( $syncPlugins as $plugin )
		{
			if ( $plugin::GetInstalled() && $plugin::GetUsePlugin() )
			{
				$sync = new $plugin;
				AC_Log( "About to sync data for the $plugin plugin." );
				$data = $sync->FetchMappedData( $userID );
				AC_AddUserFieldsToMergeArray( $merge_vars, $data );
			}
		}
	}
}

//
// ACPublishPlugins
//
class ACPublishPlugins extends ACPlugins
{
	public function GetType()
	{
		return 'Publish';
	}
	
	public function GenerateMappingsUI( $lists, $groups, $templates, $javaScript )
	{
		$publishPlugins = $this->GetPluginClasses( $this->GetType() );
		foreach ( $publishPlugins as $plugin )
		{
			if ( $plugin::GetInstalled() && $plugin::GetUsePlugin() )
			{
				$mapper = new $plugin;
				$mapper->GenerateMappingsUI( $lists, $groups, $templates, $javaScript );
			}
		}
	}
	
	public function SaveMappings()
	{
		$publishPlugins = $this->GetPluginClasses( $this->GetType() );
		foreach ( $publishPlugins as $plugin )
		{
			if ( $plugin::GetInstalled() && $plugin::GetUsePlugin() )
			{
				$mapper = new $plugin;
				$mapper->SaveMappings();
			}
		}
	}
}

//
// ACContentPlugins
//
class ACContentPlugins extends ACPlugins
{
	public function GetType()
	{
		return 'Content';
	}
	
	public function ConvertShortcode( $content )
	{
		$plugins = $this->GetPluginClasses( $this->GetType() );
		foreach ( $plugins as $plugin )
		{
			if ( $plugin::GetInstalled() && $plugin::GetUsePlugin() )
			{
				$p = new $plugin;
				$content = $p->ConvertShortcode( $content );
			}
		}
		$content = apply_filters( 'the_content', $content );
		return $content;				
	}
}
?>