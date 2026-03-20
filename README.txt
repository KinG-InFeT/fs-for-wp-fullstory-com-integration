=== FS for WP - FullStory.com Integration ===
Contributors: Vincenzo Luongo
Tags: full story, fullstory.com, fullstory, fullstory integration, woocommerce fullstory integration
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

FS for WP - FullStory.com Integration is a wordpress plugin makes it simple to add the FullStory code snippet to your website.

== Description ==

FS for WP - FullStory.com Integration is a wordpress plugin makes it simple to add the FullStory code snippet to your website.

Features:

* Easy setup - just paste your FullStory code snippet
* User identity tracking with FS.identify for logged-in users
* WooCommerce integration (total orders, total amount spent)
* Secure output with proper escaping and sanitization

== Installation ==

1. Upload `fs-for-wp-fullstory-com-integration` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Edit the plugin settings by clicking "FullStory Settings" on the settings navbar

== Frequently Asked Questions ==

= This is official plugin? =

No

= Where can I find out more about FullStory.com? =

For more info go to official site: [Full Story](https://www.fullstory.com/ "fullstory.com")

= Is there a premium version available? =

There is currently no premium version available.

= Where can I get the code snippet from? =

Setup an free (or premium) account and get your code snippet from [Full Story](https://www.fullstory.com/ "fullstory.com")


== Screenshots ==

1. FullStory Settings menu
2. Plugin Settings


== Changelog ==

= 2.0.0 =
* Support for WordPress 7.0
* Fixed critical bug: FS.identify was never executed due to incorrect `document.onload` event
* Fixed FS.identify safety check: verify FS object exists before calling identify
* Fixed WooCommerce detection (case-sensitive class name)
* Security: sanitize snippet code with `wp_kses` allowing only script tags
* Security: escape JavaScript output with `esc_js` and `JSON_HEX_*` flags
* Security: use `manage_options` capability instead of `administrator` role
* Security: added `sanitize_callback` for all settings
* Security: use `esc_textarea` for snippet code textarea
* Security: proper escaping on all output with `esc_html`, `esc_attr`, `esc_url`
* Security: added `current_user_can` check on settings page render
* Security: added `rel="noopener noreferrer"` to external links
* Improved: redesigned settings page with modern WordPress admin UI
* Improved: settings organized in card-based layout with descriptions
* Improved: monospace font for code snippet textarea
* Improved: Settings link added as first action in plugins list
* Improved: all strings are translatable with proper text domain
* Improved: sanitize additional data keys with `sanitize_key`
* Updated: FS.identify upgraded to FullStory Browser API v2 `FS('setIdentity')` with v1 fallback
* Updated: removed deprecated type suffixes (`_str`, `_int`, `_real`) from custom property names
* Updated: minimum WordPress version to 6.0
* Updated: minimum PHP version to 7.4
* Removed: dead code and unused variable assignments

= 1.4.3 =
* Support for Wordpress 6.1 added

= 1.4.2 =
* Support for Wordpress 6.x added

= 1.4.1 =
* Minor Bug Fix and added support for Wordpress 5.9

= 1.4.0 =
* Minor Bug Fix and added support for Wordpress 5.6

= 1.3.0 =
* Update donate link
* Minor fix

= 1.2.1 =
* Update donate link

= 1.2.0 =
* Compatible for Wordpress 5.3
* Minor bug fix

= 1.1.0 =
* Add Plugin Links
* Add complete support for Woocommerce 3.6.x
* Add more information in FS additions data from woocommerce (Total order, total amount spent)
* Add role information in FS user data
* Change donation url
* Minor Improvements

= 1.0.0 =
* Public release
