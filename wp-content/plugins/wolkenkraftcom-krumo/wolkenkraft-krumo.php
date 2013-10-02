<?
/*!
 * Plugin Name: wolkenkraft.com Krumo Debug Plugin
 * Plugin URI: http://www.wolkenkraft.com/
 * Description: wolkenkraft.com Krumo integrates the great Krumo class (http://krumo.sourceforge.net/) in WordPress and is a must-have for all WordPress developers. This plugin replaces the var_dump() and print_r() functions with a function called krumo() which displays the debug output in a very nice and useful form.
 * Version: 1.0
 * Author: Michael Fuerst
 * Author URI: http://www.wolkenkraft.com
 * License: GNU General Public License v2
 *
 * Krumo itself is released under the GNU LGPL License which is part of the Krumo package included in this distribution
 * Visit http://www.sourceforge.net/projects/krumo/ for mor information on Krumo
 */


class WolkenkraftKrumoBootStrap {
	
	/*
	 * Plugin version
	 */
	var $version = '1.0';
	
	
	/*
	 * Constructor on class loading
	 */
	function __construct() {
		
		global $wpdb;
			
		/* load all important libs */
		$this->loadLibraries();
		
	}
	
	/*
	 * Call the constructor the old way
	 */
	function WolkenkraftKrumoBootStrap() {
		$this->__construct();
	}
	
	
	
	/*
	 * Method that loads the 
	 */
	function loadLibraries() {
		
		require_once (dirname (__FILE__) . '/krumo/class.krumo.php');
				
	}
	
	
}

/*
 * Finally run the plugin
 */
function wolkenkraftKrumoStart() {
	global $wolkenkraftKrumoBootStrap;
	$wolkenkraftKrumoBootStrap = new WolkenkraftKrumoBootStrap();
}

add_action('init','wolkenkraftKrumoStart');


