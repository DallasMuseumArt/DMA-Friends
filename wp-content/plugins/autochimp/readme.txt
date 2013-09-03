=== AutoChimp ===
Plugin Name: AutoChimp
Contributors: WandererLLC
Plugin URI: http://www.wandererllc.com/company/plugins/autochimp/
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=HPCPB3GY5LUQW&lc=US
Tags: AutoChimp, MailChimp, Mail, Chimp, email, campaign, mailing list, interest, group, template, BuddyPress, Register, Plus, Redux, Profile Fields, XProfile, Cimy, Wishlist Member, WP-Member, Events Manager, merge, admin, create, automatically, subscribe, unsubscribe, sync, synchronize
Requires at least: 2.8
Tested up to: 3.6
Stable tag: 2.15

Keep website users and MailChimp mailing lists in sync and create campaigns from posts.

== Description ==

Automatically subscribe, unsubscribe, and update users to your [MailChimp](http://eepurl.com/MnhD "Mail Chimp") mailing list as users subscribe and unsubscribe to your site.  Sync your BuddyPress or Cimy User Extra Fields profile fields with your MaiChimp merge variables and interest groups.  Create MailChimp mail campaigns automatically from blog posts leveraging multiple campaigns, lists, interest groups, and user templates.  AutoChimp uses a tabbed options page to help you manage your settings.  In order to use AutoChimp, you must already have an account with MailChimp and at least one mailing list.

To use, save your MailChimp API Key on the options page then start adding your new registrations to any selected MailChimp mailing list.  You can configure the plugin to update your mailing list when 1) a new user subscribes, 2) a user unsubscribes, or 3) a user updates his information.  You may also choose to create campaigns automatically from post categories of your choosing.  You can send the campaigns immediately or just save them.

== Screenshots ==

1. The Mailing Lists options allow to set up AutoChimp to sync with your MailChimp lists.  You can choose to sync when users join and leave your site as well as when they update their information.  You can also use supported plugins to extend your user profile and use AutoChimp to sync that data with MailChimp.
2. The Campaigns options give you a lot of flexibility in generating campaigns from you posts.  You can choose as many categories as you want and send post data to any select mailing list, interest group, using any user template.  Note:  Gallery templates are not supported, but you can always copy a gallery template as a user template on the MailChimp website.
3. Using the Plugins options, you can instruct AutoChimp to work with other supported plugins like Register Plus, BuddyPress, Cimy User Extra Fields, and Viper's Video Tags.
4. You should only have to vist the API Key options once when you set your API key.  Until you do this, you won't be able to use AutoChimp.  You can find your API key on the MailChimp website at: Account -> API Keys and Authorized Apps.
5. Also on the Mailing Lists tab is the manual sync feature.  You can use this feature to sync all of your existing users with MailChimp.  While the sync is running, you'll be able to see progress as well as a report when it's done.

== Special Notes ==

1. AutoChimp 2.10 and up requires PHP 5.3.x or higher.  If you are getting activation errors, then check your PHP version and ask your hosting company to upgrade you to 5.3.
1. MailChimp, like all other major email campaign managers, sends newly subscribed members a confirmation email. So, you must wait until the new subscriber receives, accepts, and confirms the new subscription before you see them appear in your mailing list.  AutoChimp will trigger the confirmation email right away.  However, this can all be bypassed by checking the "Bypass double opt-in" checkbox.
1. Sometimes, plugin output may not render properly in your campaigns generated from posts.  This is usually because the plugin doesn't have access to information it needs until it is displaying its output on the screen for an end user.  The best suggestion in this case is to learn which of your plugins are problematic (most are fine) and adapt accordingly.  We'll look for ways to improve this in the future too.
1. Your campaign formatting may appear differently than your post formatting.  This is because your post uses formatting files that belong to your WordPress theme that your campaign doesn't have access to.  The best thing you can do is to use the HTML tab in the "Post Edit" page to add specific HTML instructions.  MailChimp campaigns speak HTML very well.
1. If you want more control over the visual settings of your campaigns, consider creating a user template (or copying one from the MailChimp gallery) and then selecting that template in the Campaign options.  One very important key:  Your main content section must be called "main".  This is where AutoChimp will substitute content. 
1. The subject of your campaign is your blog post title.  The "From" email address and name are taken from your MailChimp configuration.  To change that, you'll need to log into your MailChimp account.  The "To" field is the "*|FNAME|*" merge code.
1. AutoChimp creates several rows in the wp_options table of your WordPress database to store your options.

== Frequently Asked Questions ==

= Where can I find the complete FAQ? =

You can find the complete FAQ [here](http://www.wandererllc.com/company/plugins/autochimp/).

= How do I make suggestions or report bugs for this plugin? =

Just go to <http://www.wandererllc.com/company/plugins/autochimp/> and follow the instructions.

== Changelog ==

= 2.15 =

* BuddyPress update/sync problem fixed.

= 2.14 =

* Fixed the missing paragraph breaks bug.
* Fixed paragraph breaks in excerpts code.
* Fixed Events Manager new mapping bug.
* Added a timestamp to user update messages.
* Removed deprecated PHP functions.

= 2.13 =

* Fixed BuddyPress sync problems, includeds manual sync as well.
* Fixed Javascript errors on Campaign settings.

= 2.12 =

* Fixed the "Class 'XXX' not found" bug.

= 2.11 =

* Added notifications to let users know to upgrade PHP to 5.3.8 or higher.
* Attempted mitigation of "Class 'XXX' not found" for clues on how to fix (not able to reproduce).

= 2.10 =

* Added support for WP-Members, Wishlist Member, and Events Manager
* Support for creating campaigns from custom post types
* Updated UI and data format for Campaign settings
* Data migration code and reminders on activation
* Added framework to allow other developers to quickly add support for third party WordPress plugins
* Bug fixes

= 2.02 =

* Added a link back to API Key tab if connection can't be established to MailChimp.
* Fixed issue with '&' in Category names

= 2.01 =

* Small UI updates
* Fixed issue with '.' in Category names
* Loading scripts only when AutoChimp admin menus are active
* 2.0 acknowledgements added

= 2.0 =

* Major upgrade of the UI.  Options are now organized by tabs.
* Manual sync will now give you real time updates and progress.  No more click the button and watch the page spin and spin with no progress feedback.
* Create campaigns from multiple categories of posts and send them to various lists and interest groups.
* Choose MailChimp user templates for your campaigns.
* Create excerpts of campaigns.
* Added support for Cimy User Extra Fields.  Sync Cimy fields with MailChimp.
* Better support for checkboxes.
* Campaigns are sent when posts are scheduled.
* Added more options when a user unsubscribes from a site.
* Added plugin activation messages.
* Added support for Viper's Video Tags which will convert your video tags into MailChimp video tags.  Currently only supports Vimeo and Youtube.
* Dropped the priority on add/delete/update actions so that AutoChimp has a chance to read all modified user data because other registration plugins use these same actions.
* Updated to work with the latest version of BuddyPress.

= 1.14 =

* Removed clunky "temporary email address" feature when sync'ing user data.
* Added support for creating campaigns with only excerpts.
* Added a very simple "news" banner system.

= 1.13 =

* Fixed bug - Not assuming that WP database table prefix is "wp_".

= 1.12 =

* Fixed bug - updating a subscriber by email inadverdently created a new subscriber in MailChimp
* Fixed bug - 'Sync Users' reactivates a user in the system instead of failing.

= 1.11 = 

* Improved syncronization with Register Plus Redux.
* Updated FAQ.

= 1.10 =

* Can now synchronize all WordPress user fields.
* Fixed bug associated with Register Plus Redux.
* Moved the static text field from the BuddyPress UI to the main UI.

= 1.02 =

* Fixed issue where new blog users were synchronized with MailChimp but errors were incorrectly reported.
* Added extra links in the UI to give users more info.
* Cleaned up some strings.

= 1.01 =

* Bypass Double Opt-in is now OFF by default.
* AutoChimp now can coexist with the MailChimp widget plugin.
* Renamed functions to reduce conflicts with other plugins.

= 1.00 =

* Added integration with BuddyPress.  You can now sync your BuddyPress profile fields with your MailChimp merge variables and groups.
* Added a fix/patch for Register Plus and Register Plus Redux.  You can now sync first name and last name with MailChimp successfully.
* Improved the UI

= 0.83 =

* Fixed issue of missing break statements.

= 0.82 =

* Fixed issue of pending email posts to campaigns being sent prematurely.
* Added "Read the full story here" with permalink URL to the blog post at the bottom of the campaign.

= 0.81 =

* A tiny update to make small updates to the UI.

= 0.8 =

* Users can now create mail campaigns from posts when publishing a new post.
* Added additional UI to support basic preferences for creating campaigns from posts.

= 0.6 =

* Add, update, and delete users in your mailing list as your site's users change.  This synchronization is one-way:  from site to mailing list.
* Basic UI for keeping a mailing list in sync with your site's users.

== Upgrade Notice ==

= 2.15 =

All users of BuddyPress should update.

= 2.14 =

Bug fixes.  Please upgrade.  Fixed bug:  stripped paragraph breaks in excerpts.  Actually, this is standard WordPress behavior, but it doesn't work so well as an email campaign, so the fix just overrides the standard WordPress behavior and does everything else except strip out the paragraph breaks.

= 2.14 =

Important bug fixes.  Definitely upgrade!

= 2.13 =

Important bug fixes.  Definitely upgrade!

= 2.12 =

'Class XXX not found' bug has been fixed.  Please upgrade.

= 2.11 = 

No need to upgrade unless you're running into activation problems or 'Class XXX not found' errors.

= 2.10 =

Major release.  Please upgrade.  NOTE:  Requires PHP 5.3.8 and higher.

= 2.02 =

Small UI improvements and fixes.  Please upgrade.

= 2.01 =

Bug fixes.  Please upgrade.

= 2.0 =

BIG changes and fixes recommended for everyone.  Please upgrade pronto.

= 1.14 =

Not a critical update.

= 1.13 =

Recommended for all.  Please upgrade.

= 1.12 =

Recommended for all.  Please upgrade.

= 1.11 =

Recommended if you use Register Plus or Register Plus Redux.

= 1.10 = 

Recommended if you use Register Plus or Register Plus Redux.  Also, if you want to synchronize fields other than first name, last name, and email, please upgrade.

= 1.02 =

Small changes and fixes which are recommended for all users.

= 1.01 =

Recommended for all users, especially those who also use the MailChimp widget plugin.

= 1.0 =

Recommended for all users.  This version greatly expands AutoChimp's feature set.

= 0.83 =

All users should upgrade to this version ASAP.

= 0.82 =

Not a critical update.  Small fix for email-to-post users, plus a "Read more" link at the bottom of campaigns.

= 0.81 =

This version simply tightens down the 0.8 UI.

= 0.8 =

This version adds the ability to create campaigns from blog posts.

== Acknowledgments ==

There are many people who have suggested features for AutoChimp.  Special consideration needs to be made to the following people who had an active role in contributing by providing a detailed design, monetary sponsorship, or offering to test and provide useful feedback:

1. Aron Silverton for his help with integrating the WP-Members plugin.
1. Anton Alksnin at [Forex Alert](http://www.forex-alert.net "Forex Alert") for supporting the "blog post to campaign" feature.
1. Peter Michael at [FlowDrops](http://www.flowdrops.com/) for some quality testing.
1. [Latinos a Morir](http://www.latinosamorir.com/) for supporting the BuddyPress Synchronization feature.
1. Bryan Hoffman at [Dwell DFW Apartments](http://apartments.dwelldfw.com/dallas/) for supporting synchronizing all WordPress user fields.
1. Sarah Anderson for quality testing.
1. Morgan at [Satellite Jones](http://satellitejones.com/) for catching the "wp_" bug.
1. Jamie at [WunderDojo](http://www.wunderdojo.com) for a much better solution to the 'temporary email' problem when a user or admin changes the email address for an account.
1. Tristan at [Grasshopper Herder](http://grasshopperherder.com/) for sponsorship of AutoChimp 2.0.
1. The folks at [MailChimp](http://www.mailchimp.com) for sponsorship of AutoChimp 2.0.
1. [Web Tonic](http://www.webtonic.co.nz/) for sponsorship of AutoChimp 2.0.
1. [Allen Hancock](http://www.kayakmississippi.com/), sponsor of the Phatwater Kayak Challenge 42 miles on the Mighty Mississippi.
1. Several anonymous contributions to the AutoChimp 2.0 project.
1. Katherine Boroski, [BKB Design Group](http://www.bkbdesigngroup.com), for reporting the '.' in Category names bug.
1. Travis Clinton for helping debug the mysterious AutoChimp 2.10 plugin files not loading.

== License ==

This file is part of AutoChimp.

AutoChimp is free software:  you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.  

AutoChimp is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.

See the license at <http://www.gnu.org/licenses/>.
