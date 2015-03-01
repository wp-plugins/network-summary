=== Network Summary ===
Contributors: jokr
Tags: description, multisite, overview
Requires at least: 3.5.2
Tested up to: 4.1.1
Stable tag: 2.0.11
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin enables sites of a network to display information and posts from other sites within the same network. This happens through shortcodes and custom rss feeds.

== Description ==

WordPress networks can consist of many different sites which are usually kept separate. With this plugin the network admin and the site admins can change that and display content from other sites in the same network as well.

All sites can be categorized in any number of categories to allow structure and grouping. Also each site now can enter its own description to provide the reader with more detailed information about the site.

Finally the content can be displayed in multiple ways. There are three shortcodes that offer a high flexibility in parameters and filters to display a list of sites with the most recent posts on any other site. Going further the plugin adds a new rss that aggregates posts of all sites or only certain categories. It also adds a custom feed builder which allows each user to build a customized feed with only the sites she or he is interested in.

Currently there are three shortcodes available:

= Network Overview =

_Shortcode:_ `[netview]`

Description: Displays a rather detailed overview of a specified set of sites. Can include images and most recent posts. Offers two layouts and two ways of sorting them.

Options:

* `include` (optional, defaults: all available) expects a comma separated list of site ids. It will only will list these sites.
* `exclude` (optional) expects a comma separated list of site ids. It will list all sites except the listed ones.
* `category` (optional) expects a comma separated list of category ids. Only sites within these categories will be displayed, regardless of the include parameter. The exclude parameter will still apply.
* `numposts` (optional, default: 2) expects a positive number or zero. Limits the number of most recent published posts displayed.
* `sort` (optional, default: 'abc') expects either 'abc' or 'posts'. 'abc' means alphabetical sorting. 'posts' will sort the sites according to their most recent post.
* `layout` (optional, default: 'table') expects either 'grid' or 'table'. Defines the layout of the list. Grid uses two columns. Table uses one row per site.
* `images` (optional, default: 'true') expects either 'true' or 'false'. Defines whether header images of the sites are displayed if available.
* `rss` (optional, default: 'true') expects either 'true' or 'false'. Defines whether a custom rss feed link should be displayed.
* `minposts` (optional, default: 0) expects a positive number or zero. Defines a limit of posts a site must have published in order to be shown.
* `post_types` (optional) additional post_types that are taken into consideration and displayed, type 'post' is always included.

Example:

    [netview order=posts layout=grid numposts=3 images=false]

Lists all visible sites in a grid layout without images and with the three most recent posts. The list is sorted by the site with the most recent post first.

= Network Single View =

Shortcode: `[netview-single]`

Description: Displays one site with a custom image and in a more prominent way.

= Network Index =

Shortcode: `[netview-all]`

Description: Displays all visible sites in form of an index in alphabetical order without any additional information.

== Installation ==

1. Upload the `network-summary` directory to the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in the network administration area.

== Frequently asked questions ==

= Where do I find the new feed? =

The new feed is available on any site of the network under the url `{site-url}\feed\rss2-network`.

= Where do I find the custom feed builder? =

The custom feed builder is available on any site of the network und the url `{site-url}\network-feed`.

== Screenshots ==

1. The settings section for each individual site if the site admins are allowed to decide for themselves whether they want to share their content or not.
1. The settings page in the network admin area. Here every site can either be set to visible or hidden. The network admin can also decide, whether the site admins can make this choice on their own or not.
1. Here the site categories can be managed.
1. Excerpt of the custom feed builder.

== Changelog ==

= 2.0.11 =
* Added option to include new post types to netview shortcode (e.g. podcasts).

= 2.0.10 =
* Fixed a bug with the rss feed.

= 2.0.9 =
* General reformatting to comply with WordPress guidelines.
* Update TinyMCE call to be compatible with TinyMCE 4.0.

= 2.0.8 =
* Tested compatibility with WordPress Core 3.9.1.
* Fixed bug that did not display sharing option on individual blog correctly.

= 2.0.7 =
* Small css changes on the shortcodes.
* Fixed the brackets on the custom feed builder for sites without any category.

= 2.0.6 =
* Fixed some small bugs on the rss builder.
* The rss feed of the shortcode now uses the permalink.
* The feed builder now displays links to the sites.

= 2.0.5 =
* Fixed permalink structure in rss feed.
* Flushing rewrite rules at the correct moment.
* Adding minposts parameter.
* Added a custom rss feed builder.

= 2.0.4 =
* Simplified RSS feed urls.
* Rewrite rules are now flushed with activation.
* Scripts to create databases now check for the correct mysql storage engine.

= 2.0.3 =
* Updated the rss feed. It is no valid rss feed. The language is always en-US and the author is the page of the post.
* RSS feed accepts category arguments, e.g. `/feed/rss2-network?category=1` will display all items of this category.
* The RSS feed now has a default limit of 50 items. The value can be changed in the network settings.
* Refactoring of the settings and options.

= 2.0.2 =
* Fixed a bug with the rss feed.

= 2.0.1 =
* Properly added uninstall.php and data is now preserved over deactivation.
* Fixed bug in network summary admin screen if a site does not have any posts.
* Deleted old classes that still lingered around in root directory.

= 2.0.0 =
* Massive refactoring of existing code.
* Adding the category setting for each blog and the network.

= 1.1.5 =
* Added rss functionality.

= 1.1.4 =
* Added the display of header pictures.
* Extended documentation of options for main short tag.

= 1.1.3 =
* Ensured compatibility with WordPress 3.8.
* Minor layout tweaks in the network admin screen.
* Updated code documentation.
* Fixed a anonymous function call that prevented compatibility with PHP 5.2.

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