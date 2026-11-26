=== FS for WP - FullStory.com Integration ===
Contributors: vluongo
Tags: full story, fullstory.com, fullstory, fullstory integration, woocommerce fullstory integration
Requires at least: 6.0
Tested up to: 7.2
Requires PHP: 7.4
Stable tag: 3.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add FullStory session recording to your site without editing your theme, and see who your logged-in users and WooCommerce customers are.

== Description ==

FullStory records how visitors use your site, so you can replay their clicks, scrolls and page views. This plugin adds FullStory to every page of your site without editing your theme.

= How it works =

1. Copy the code snippet from your FullStory account.
2. Paste it in Settings > FullStory Settings, turn the integration on and save.
3. Open your site: the visits start showing up in FullStory.

= Features =

* Paste the snippet exactly as FullStory gives it to you: both the current and the older snippet work.
* Identify logged-in users (optional): in FullStory their sessions show name, email and role instead of an anonymous visitor.
* With WooCommerce, identified customers also show their number of orders and total spent.
* AMP pages are left untouched, so they stay valid.
* Only administrators allowed to add code to the site can change the snippet.
* Available in English and Italian.

This plugin is not affiliated with or endorsed by FullStory.

== External services ==

This plugin connects your site to [FullStory](https://www.fullstory.com/), a session recording and analytics service. Nothing is sent until you paste your snippet and enable the integration.

* **Session recording:** the snippet loads FullStory's recording script on every page and sends FullStory how visitors use your site (for example page views, clicks and scrolls), according to the settings of your FullStory account.
* **User identification** (only with "Enable FS.identify"): for logged-in users it also sends their user ID and email as FullStory user ID, display name, email, roles and, with WooCommerce, order count and total spent.

FullStory [terms of service](https://www.fullstory.com/legal/terms-and-conditions/) and [privacy policy](https://www.fullstory.com/legal/privacy-policy/). Depending on your visitors' country, you may need to inform them and ask for their consent.

== Installation ==

1. Install the plugin from Plugins > Add New, or upload the `fs-for-wp-fullstory-com-integration` folder to `/wp-content/plugins/`.
2. Activate it.
3. Go to Settings > FullStory Settings, paste your snippet, enable the integration and save.

== Frequently Asked Questions ==

= Where do I find the code snippet? =

In your [FullStory account](https://app.fullstory.com). Paste it as FullStory provides it, `<script>` tags included.

= Why can't I change the snippet? =

The snippet is JavaScript that runs on every page, so only users allowed to publish unfiltered HTML can change it. On multisite that means super admins only; if `DISALLOW_UNFILTERED_HTML` is set, nobody can.

= I updated from 2.0.0. Do I need to do anything? =

No. Version 2.0.0 altered the snippet and FullStory did not load; 3.0.0 repairs snippets saved with 2.0.0 automatically.

= Does it work with AMP pages (AMP for WP)? =

FullStory cannot record AMP pages: AMP only allows its own components, and FullStory has none. The plugin detects AMP pages (AMP for WP and the official AMP plugin) and leaves them untouched, so they stay valid AMP. Visitors are recorded as soon as they open a regular page.

= Can I send more data about the user? =

Yes, with the `fsi_additional_data` filter:

`add_filter( 'fsi_additional_data', function ( $data ) { $data['plan'] = 'gold'; return $data; } );`

= What happens to my settings when I delete the plugin? =

They are removed together with the plugin. Deactivating it keeps them.

== Screenshots ==

1. The FullStory Settings entry in the Settings menu.
2. Settings page: enable the integration, paste the snippet and turn on user identification.

== Changelog ==

= 3.0.0 =
* Tested up to WordPress 7.1.
* Fix: FullStory did not load with 2.0.0, because the snippet was altered on save and on output. Snippets saved with 2.0.0 are repaired automatically.
* Fix: user identification did not work with the classic FullStory snippet, and emails containing `&` produced a wrong user ID.
* Fix: notices on the settings page were shown twice.
* Security: only users allowed to publish unfiltered HTML (not site admins on multisite) can change the snippet.
* The snippet is added through the WordPress script API; admin styles are a separate file.
* AMP pages (AMP for WP and the official AMP plugin) are left untouched, so they stay valid AMP.
* New: Italian translation. The readme now lists the data sent to FullStory.

= 2.0.0 =
* Tested up to WordPress 7.0; requires WordPress 6.0 and PHP 7.4.
* Redesigned, translatable settings page.
* Fix: the user identification code never ran. Identification now uses the FullStory 2.x API.
* Known issue: the snippet is altered and FullStory does not load. Fixed in 3.0.0.

= 1.4.3 =
* WordPress 6.1 support.

= 1.4.2 =
* WordPress 6.x support.

= 1.4.1 =
* WordPress 5.9 support and minor fixes.

= 1.4.0 =
* WordPress 5.6 support and minor fixes.

= 1.3.0 =
* Donate link update and minor fixes.

= 1.2.1 =
* Donate link update.

= 1.2.0 =
* WordPress 5.3 support and minor fixes.

= 1.1.0 =
* Settings link in the plugin list.
* Roles, WooCommerce order count and total spent added to the user data.

= 1.0.0 =
* First release.
