=== Custom Post Type Auto Menu ===
Contributors: badfun
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=UYCUSEFX8Q89C
Tags: custom post type, menus, auto menu
Requires at least: 3.0.1
Tested up to: 3.9
Stable tag: 1.1.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html


Automatically create menu items for your custom post types in your chosen menu and parent menu item.

== Description ==

Custom Post Type Auto Menu allows the user to choose a custom post type, a menu, and a menu parent item to which new custom post type posts
will be added automatically. This simplifies the menu process for users who may not be comfortable adding items to menus, or
for sites that have a large number of custom post type additions, such as products.

The plugin now supports multiple custom post types

NOTE: If you were using the older version of the plugin you will need to re-do your settings!


== Installation ==

1. Upload the plugin to your site and activate.
2. CPT Auto Menus page is now in main admin menu under Settings.
3. Use checkbox to select which Custom Post Types you want an automated menu for, then Save Changes.
4. You will be redirected to Menu Settings tab. Select which menu and which parent menu item where you wish the CPT to display.


== Frequently Asked Questions ==

= How many custom post types can I use? =
The plugin now supports multiple custom post types

= Can I create sub-sub menu items? =
Yes. But it requires a mod to the code and can lead to strange issues. I'll figure it out for future versions.

= What about categories and pages? =
There are other available solutions for that, but if there is demand I could incorporate it in future releases.



== Screenshots ==

1. Custom Post Type Settings page. Choose at least one.
2. Menu Settings page. Choose which menu and which menu item the automated cpt post should appear in.

== Changelog ==

= 1.1.3 =
* Fixed bug that sometimes prevented page redirects and caused 'headers already sent' error
* Improved error message for empty custom post type list
* Fixed intermittent ajax bug
* Added proper sanitization to callbacks

= 1.1.2 =
* Fixed translation strings to WordPress I18n standards
* Formatted code to WordPress standards
* Added a few missing empty variable checks
* Added missing exits after redirect functions

= 1.1.1 =
* Added German translation. Thanks Dad!
* Updated Spanish translation. Thanks Andrew!

= 1.1.0 =
* Now able to select multiple custom post types
* Moved CPT Auto Menu to its own menu page
* Added Spanish, French and Italian translations

= 1.0.1 =
* Fixed bug where saving draft created menu item

= 1.0.0 =
* First upload of working plugin.





== Upgrade Notice ==

=1.1.0 = If you were using an earlier version you will need to re-do your settings for the custom post type. This is because
the settings had to be changed completely to support multiple custom post types.

== Known Issues ==

* After a custom post type post has been published, if it is downgraded to 'draft' the item stays in the menu.
* Bulk trashing custom post types will leave the menu items behind.
* Menu items can not have the same name, even if attached to different menus.

== Acknowledgements ==

* Thanks to Andrew Kurtis of [WebHostingHub](http://www.webhostinghub.com/) for the Spanish translation
* There are various urls in the code from developers whose solutions I used to solve problems. Thanks everyone!

