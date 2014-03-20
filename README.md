#Custom Post Type Auto Menu
=============

A WordPress plugin that automatically adds new custom post type posts to the chosen menu and parent item as a sub-menu item.

Custom Post Type Auto Menu allows the user to choose a custom post type, a menu, and a menu parent item to which new custom post type posts
will be added automatically. This is useful for simplifying things for clients who may not be comfortable adding items to menus. Or
for sites that have a large number of custom post type additions, such as products.

The plugin now supports multiple custom post types.


## Installation

1. Upload the plugin to your site and activate.
2. CPT Auto Menus page is now in main admin menu under Settings.
3. Use checkbox to select which Custom Post Types you want an automated menu for, then Save Changes.
4. You will be redirected to Menu Settings tab. Select which menu and which parent menu item where you wish the CPT to display.


## Frequently Asked Questions

1. How many custom post types can I use?
The plugin now supports multiple custom post types

2. Can I create sub-sub menu items?
Yes. But it requires a mod to the code and can lead to strange issues. I'll figure it out for future versions.

3. What about categories and pages?
There are other available solutions for that, but if there is demand I could incorporate it in future releases.


## Known Issues

* After a custom post type post has been published, if it is downgraded to 'draft' the item stays in the menu.
* Bulk trashing custom post types will leave the menu items behind.
* Menu items can not have the same name, even if attached to different menus.


## Changelog

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


## Acknowledgements

* Thanks to Andrew Kurtis of [WebHostingHub](http://www.webhostinghub.com/) for the Spanish translation
* There are various urls in the code from developers whose solutions I used to solve problems. Thanks everyone!


