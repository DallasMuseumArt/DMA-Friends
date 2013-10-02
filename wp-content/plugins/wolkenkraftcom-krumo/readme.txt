=== Plugin Name ===
Contributors: (this should be a list of wordpress.org userid's)
Donate link: http://www.wolkenkraft.com
Tags: debug, krumo, developer tool, php
Requires at least: 3.0.0
Tested up to: 3.6.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin integrates the great Krumo class in WordPress which allows debugging of objects and variables in a
well formated and structured form.

== Description ==

This plugin integrates the popular Krumo class in you WordPress installation. Krumo helps developers to debug objects
and variables. It presents debug output in a well formated and structured form.

After Installation simply try to debug some variables like this:
`<?php
krumo($_SERVER);
$date = new DateTime(),
krumo($date);
?>`

For detailed instruction visit my [wolkenkraft.com Website](http://wolkenkraft.com/produkte/wordpress-plugin-wolkenkraft-com-krumo/)
or the [Krumo Website](http://krumo.sourceforge.net/)

== Installation ==

Installation is quite easy. Just follow these steps to install and activate the plugin.

1. Install the plugin either via the WordPress.org plugin directory, or by uploading the files to the /wp-content/plugins direcotry your server
2. Activate the plugin through the 'Plugins' menu in WordPress
3. That?s it - installation is finished!
4. To try it add "krumo($_SERVER);" in any of your PHP files and open it in your browser

== Frequently Asked Questions ==

= Where can I find a documentation? =

You can find a short documentation on my [wolkenkraft.com Website](http://wolkenkraft.com/produkte/wordpress-plugin-wolkenkraft-com-krumo/)
as well as on the [Krumo Website](http://krumo.sourceforge.net/).

== Screenshots ==

1. Example debug output for krumo($_SERVER);
2. Example debug output for a DateTime Object

== Changelog ==

= 1.0 =
* Initial release

== Upgrade Notice ==

= 1.0 =
Initial release