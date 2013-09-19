=== Network Summary ===
Contributors: jokr
Tags: description, multisite, overview
Requires at least: 3.5.2
Tested up to: 3.6.0
Stable tag: trunk
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugins enables sites of a network to display information and posts from other sites within the same network.

== Description ==

With this plugin each site of a network can decide whether its content can be displayed by other sites within the network.
For this purpose the plugin offers different shortcodes as well as some widgets and menu elements in the future.

In order to have consistent information about each site, it also adds a new field *Description* to each site which should
provide a summary of the site's content.

Currently there are three shortcodes available:

* `[netview]` displays all the available sites with a short description and the most recent posts.
* `[netview-single]` displays one site with a custom image and in a more prominent way.
* `[netview-all]` displays all visible sites in form of an index in alphabetical order without any additional information.

== Installation ==

1. Upload the `network-summary` directory to the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in the network administration area.

== Screenshots ==

1. The settings section for each individual site if the site admins are allowed to decide for themselves whether they want to share their content or not.
2. The settings page in the network admin area.

== Changelog ==

= 1.1.2 =
* Table view is now default layout for the `[netview]` shortcode.
* `[netview]` shortcode does no longer display recent posts header.
* Dates and times in shortcode output now are formatted according to the original site.

= 1.1.1 =
* Renamed the plugin from multisite overview to network summary.
* Settings page in the network admin area now displays additional info about each blog.
* HTML output now gets cached to avoid performance issues with large networks.
* The HTML output of the description within the shortcode now also parses other existing shortcodes.

= 1.1.0 =
* Added a numposts parameter to the multisite and multisite-all shortcode to define the number of recent posts displayed.
* Added a sort parameter to the multisite shortcode to define either alphabetical sorting or sorting by most recent post.
* Added a layout parameter to the multisite shortcode to switch between a two column grid layout and a table layout.
* Added the ability to set the visibility of each site globally in the network admin area.

= 1.0.1 =
* Fixed compatibility with php <= 5.3 by removing function array dereferencing.

= 1.0.0 =
* Stable version.