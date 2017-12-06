=== Custom Post Type Auto Menu ===
Contributors: badfun
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=UYCUSEFX8Q89C
Tags: custom post type, menus, auto menu
Requires at least: 3.0.1
Tested up to: 4.9
Stable tag: 1.2.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html


Automatically create menu items for your custom post types in your chosen menu and parent menu item.

== Description ==

Custom Post Type Auto Menu allows the user to choose a custom post type, a menu, and a menu parent item to which new custom post type posts
will be added automatically. This simplifies the menu process for users who may not be comfortable adding items to menus, or
for sites that have a large number of custom post type additions, such as products.

The plugin supports multiple custom post types.


== Installation ==

1. Upload the plugin to your site and activate.
2. CPT Auto Menus page is now in main admin menu under Settings.
3. Use checkbox to select which Custom Post Types you want an automated menu for, then Save Changes.
4. You will be redirected to Menu Settings tab. Select which menu and which parent menu item where you wish the CPT to display.


== Frequently Asked Questions ==

= How many custom post types can I use? =
The plugin now supports multiple custom post types. Use as many as you like.

= Can I create sub-sub menu items? =
Yes. Well no. It requires a mod to the code and can lead to strange issues. I'll figure it out for future versions.

= What about categories and pages? =
There are other available solutions for that, but if there is demand I could incorporate it in future releases.

= Can I add existing custom post type posts to the menu? =
Some users have many existing cpt's and want to change them to a new menu. This can be done by selecting all your custom post types
and setting them to 'draft' status, then back to 'publish'. The plugin will detect them as new, and they will be added.

= Why do post types have to be public? =
Custom post types must be set to public for the plugin to see them. This is because one of the assumptions of not setting a post type
to public is that 'show_in_nav_menus' will be false. Obviously this is not the desired outcome of a plugin that automatically adds items
to a menu.

== Screenshots ==

1. Custom Post Type Settings page. Choose at least one.
2. Menu Settings page. Choose which menu and which menu item the automated cpt post should appear in.

== Changelog ==

= 1.2.1 =
* tested with WP 4.9 on multisite

= 1.2.0 =
* test for public post types and give warning if not set. Removed deprecated screen icon function.

= 1.1.9 =
* very minor version changes and a new deploy test

= 1.1.8 =
* A few small bug tweaks

= 1.1.7 =
* SVN did not upload the new classes directory. Going to re-do the whole commit

= 1.1.6 =
* some kind of SVN error. Attempted fix.

= 1.1.5 =
* Fixed bulk trash error
* Fixed publish to draft error
* Broke up main function into new class

= 1.1.4 =
* Fixed current screen error that gave a php warning
* Fixed editing error where titles with an ampersand would create multiple menu items

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

* Ampersands and other HTML entites in titles can sometimes have curious results, such as multiple menu items.
* After a custom post type post has been published, if it is downgraded to 'draft' the item stays in the menu.
* Bulk trashing custom post types will leave the menu items behind. Trash custom post types one at a time and it works fine.
* Menu items can not have the same name, even if attached to different menus.
* Known to conflict with Anything Order plugin: https://wordpress.org/plugins/anything-order/

== Acknowledgements ==

* Thanks to all who have made suggestions for features to this plugin. I will try to put aside some time to work on it.
* Many thanks to [codbox](https://github.com/codbox) for the bug fixes and the new class. Much appreciated!
* Thanks to Andrew Kurtis of [WebHostingHub](http://www.webhostinghub.com/) for the Spanish translation
* There are various urls in the code from developers whose solutions I used to solve problems. Thanks everyone!

