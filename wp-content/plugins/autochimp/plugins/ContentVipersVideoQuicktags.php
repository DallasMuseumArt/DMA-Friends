<?php
define( 'WP88_MC_INTEGRATE_VIPER', 'wp88_mc_integrate_viper' );
define( 'WP88_MC_VIDEO_SHOW_TITLE', 'wp88_mc_video_show_title' );
define( 'WP88_MC_VIDEO_SHOW_BORDER', 'wp88_mc_video_show_border' );
define( 'WP88_MC_VIDEO_TRIM_BORDER', 'wp88_mc_video_trim_border' );
define( 'WP88_MC_VIDEO_SHOW_RATINGS', 'wp88_mc_video_show_ratings' );
define( 'WP88_MC_VIDEO_SHOW_NUM_VIEWS', 'wp88_mc_video_show_num_views' );

class ContentVipersVideoQuicktags extends ACPlugin
{
	public static function GetInstalled()
	{
		return class_exists( 'VipersVideoQuicktags' );
	}
	
	public static function GetUsePlugin()
	{
		return get_option( WP88_MC_INTEGRATE_VIPER );
	}

	//
	// Function for registering hooks.  If you don't need any, then just leave this
	// function empty.
	//
	public function RegisterHooks()
	{
	}
	
	//
	//	This function shows the Viper Video Quick Tags UI for AutoChimp.  Prints HTML.
	//
	public function ShowPluginSettings()
	{
		// Get settings
		$integrateViper = ContentVipersVideoQuicktags::GetUsePlugin();
		$showTitle = get_option( WP88_MC_VIDEO_SHOW_TITLE );
		$showBorder = get_option( WP88_MC_VIDEO_SHOW_BORDER );
		$trimBorder = get_option( WP88_MC_VIDEO_TRIM_BORDER );
		$showRatings = get_option( WP88_MC_VIDEO_SHOW_RATINGS );
		$showViews = get_option( WP88_MC_VIDEO_SHOW_NUM_VIEWS );
	
		// Start outputting UI
		print '<p><strong>You are using <a target="_blank" href="http://wordpress.org/extend/plugins/vipers-video-quicktags/">Viper\'s Video Quicktags</a></strong>. With AutoChimp, you can convert your video tags into MailChimp video tags when you create campaigns from posts.</p>' . PHP_EOL;
		print '<fieldset style="margin-left: 20px;">' . PHP_EOL;
	
		// Create a hidden field just to signal that the user can save their preferences
		// even if the sync button isn't checked
		print '<input type="hidden" name="viper_running" />' . PHP_EOL;
		print '<p><input type=CHECKBOX value="on_integrate_viper" name="on_integrate_viper" ';
		if ( '1' === $integrateViper )
			print 'checked';
		print '> Automatically generate MailChimp video tags from Viper\'s Video Quicktags shortcode. Videos will <em>not</em> be shown if you are only generating exceprts for your campaigns.</p>' . PHP_EOL;
	
			print '<fieldset style="margin-left: 20px;">' . PHP_EOL;
	
			print '<strong>Video Settings</strong> (Click <a target="blank" href="http://kb.mailchimp.com/article/can-i-include-music-video-in-my-campaigns">here</a> for examples)' . PHP_EOL;
			print '<p><input type=CHECKBOX value="on_show_title" name="on_show_title" ';
			if ( '1' === $showTitle )
				print 'checked';
			print '> Show video title.</p>' . PHP_EOL;
			print '<p><input type=CHECKBOX value="on_show_border" name="on_show_border" ';
			if ( '1' === $showBorder )
				print 'checked';
			print '> Show video border.</p>' . PHP_EOL;
			print '<p><input type=CHECKBOX value="on_trim_border" name="on_trim_border" ';
			if ( '1' === $trimBorder )
				print 'checked';
			print '> Trim white/black space from the video screenshot border.</p>' . PHP_EOL;
			print '<p><input type=CHECKBOX value="on_show_ratings" name="on_show_ratings" ';
			if ( '1' === $showRatings )
				print 'checked';
			print '> Show video ratings. <em>Likes and stars.</em></p>' . PHP_EOL;
			print '<p><input type=CHECKBOX value="on_show_num_views" name="on_show_num_views" ';
			if ( '1' === $showViews )
				print 'checked';
			print '> Show number of views. <em>Only valid for Youtube.</em></p>' . PHP_EOL;
		
		print '</fieldset></fieldset>' . PHP_EOL;
	}
	
	public function SavePluginSettings()
	{
		AC_SetBooleanOption( 'on_integrate_viper', WP88_MC_INTEGRATE_VIPER );
		AC_SetBooleanOption( 'on_show_title', WP88_MC_VIDEO_SHOW_TITLE );
		AC_SetBooleanOption( 'on_show_border', WP88_MC_VIDEO_SHOW_BORDER );
		AC_SetBooleanOption( 'on_trim_border', WP88_MC_VIDEO_TRIM_BORDER );
		AC_SetBooleanOption( 'on_show_ratings', WP88_MC_VIDEO_SHOW_RATINGS );
		AC_SetBooleanOption( 'on_show_num_views', WP88_MC_VIDEO_SHOW_NUM_VIEWS );
	}
	
	//
	//	Pass the content of the post to this function and it will return updated content
	//	with all of the Viper Quicktags replaced with MailChimp tags.  See the $tagnames
	//	array for which video services are currently supported.
	//
	//	For more info on this function, have a look at wp-includes/shortcodes.php
	//
	public function ConvertShortcode( $content )
	{
		// Supported video types
		$tagnames = array( 'vimeo', 'youtube' );
		$tagregexp = $tagregexp = join( '|', array_map('preg_quote', $tagnames) );
		
		// WARNING! Do not change this regex.  I can barely understand it.
		$pattern = 
			  '\\['                              // Opening bracket
			. '(\\[?)'                           // 1: Optional second opening bracket for escaping shortcodes: [[tag]]
			. "($tagregexp)"                     // 2: Shortcode name
			. '\\b'                              // Word boundary
			. '('                                // 3: Unroll the loop: Inside the opening shortcode tag
			.     '[^\\]\\/]*'                   // Not a closing bracket or forward slash
			.     '(?:'
			.         '\\/(?!\\])'               // A forward slash not followed by a closing bracket
			.         '[^\\]\\/]*'               // Not a closing bracket or forward slash
			.     ')*?'
			. ')'
			. '(?:'
			.     '(\\/)'                        // 4: Self closing tag ...
			.     '\\]'                          // ... and closing bracket
			. '|'
			.     '\\]'                          // Closing bracket
			.     '(?:'
			.         '('                        // 5: Unroll the loop: Optionally, anything between the opening and closing shortcode tags
			.             '[^\\[]*+'             // Not an opening bracket
			.             '(?:'
			.                 '\\[(?!\\/\\2\\])' // An opening bracket not followed by the closing shortcode tag
			.                 '[^\\[]*+'         // Not an opening bracket
			.             ')*+'
			.         ')'
			.         '\\[\\/\\2\\]'             // Closing shortcode tag
			.     ')?'
			. ')'
			. '(\\]?)';                          // 6: Optional second closing brocket for escaping shortcodes: [[tag]]
	
		// Callback function for each video tag instance. 			
		$updatedContent = preg_replace_callback( "/$pattern/s", 'VVQT_SwitchViperVideoTags', $content );
		AC_Log( "ContentVipersVideoQuicktags has altered shortcode to this:  $updatedContent" );
		return $updatedContent;
	}
}

//
//	This function is passed an array of information on the shortcode data.
//	0 => The complete shortcode
//	1 => Usually empty 
//	2 => The video service (the shortcode tag)
//	3 => The arguments (in string format)
//	4 => Usually empty
//	5 => The video link (between the two shortcode tags)
//	6 => Usually empty
//
function VVQT_SwitchViperVideoTags( $m ) 
{
	// Get options
	$showTitle = get_option( WP88_MC_VIDEO_SHOW_TITLE );
	$showBorder = get_option( WP88_MC_VIDEO_SHOW_BORDER );
	$trimBorder = get_option( WP88_MC_VIDEO_TRIM_BORDER );
	$showRatings = get_option( WP88_MC_VIDEO_SHOW_RATINGS );
	$showViews = get_option( WP88_MC_VIDEO_SHOW_NUM_VIEWS );

	// allow [[foo]] syntax for escaping a tag
	if ( $m[1] == '[' && $m[6] == ']' ) {
		return substr($m[0], 1, -1);
	}

	// Generate tags
	$service = strtoupper( $m[2] ); // Always upper case
	$attr = shortcode_parse_atts( $m[3] );
	if ( 0 === strcmp( $service, 'VIMEO') )	// Extracting VIMEO is easy
		$vidID = basename( $m[5] );
	elseif ( 0 === strcmp( $service, 'YOUTUBE' ) ) // YOUTUBE is a little harder
	{
		$output = array();
		// Extract the query string
		$up = parse_url( $m[5], PHP_URL_QUERY );
		// Parse it into components
		parse_str( $up, $output );
		// Get the 'v' argument
		$vidID = $output['v'];  
	}
	$width = (isset( $attr['width'])) ? (', $max_width=' . $attr['width']) : '';
	$title = ('0'===$showTitle) ? (', $title=N') : '';
	$border = ('0'===$showBorder) ? (', $border=N') : '';
	$trim = ('0'===$trimBorder) ? (', $trim_border=N') : '';
	$ratings = ('0'===$showRatings) ? (', $ratings=N') : '';
	$views = ('0'===$showViews) ? (', $views=N') : '';
	
	// Assemble the final MailChimp tag
	$final = '*|'. $service .':[$vid=' . $vidID . $width . $title . $border . $trim . $ratings . $views . ']|*';
	//print "Final string is: $final.";
	//exit;
	return $final;
}
?>