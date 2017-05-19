=== Plugin Name ===
Contributors: aurelien, aurovrata
Tags: order, reorder, re order, order by category,order custom post type, order by categories, order category, order categories, order by taxonomy, order by taxonomies
Requires at least: 3.4
Tested up to: 4.7
Stable tag: 1.2.2
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Sort Post and Custom Post Type through drag & drop interface of selected category (or custom selected taxonomies).

== Description ==


ReOrder Post Within Categories is used to sort posts (and custom post type) in any custom order by drag & drop interface.
It works with a selected category, each category can have different order of same post.


== Installation ==

1. Upload the 'reorder-posts-within-categories' folder to the '/wp-content/plugins/' directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to the settings page to activate sorting for each categories you choose.


== Screenshots ==

1. Plugin page settings
2. Re-order your post through a drag & drop interface

== FAQ ==

= My Custom Post type isn't listed in the settings page =
By default the plugin allows you to order posts that are shown in the dashboard menu (else you can't oder them), and are non-hierarchical. eg pages.  This is because by default pages don't have categories.  However, some uses have custom posts types that are hierarchical as well as having custom taxonomy.  Therefore they expect their post to appear and it doesn't.  To overcome this, a new filter has been added in v1.2.2 which allows to filter posts that are hierarchical as well,

`add_filter('reorder_post_within_category_query_custom_post', 'show_my_posts');
function show_my_posts($query_args){
  $query_args['hierarchical'] = true;
  return $query_args;
}
`
Keep in mind that you will now see `Pages` as a post type to re-order, selecting such post types which do not have any categories associated with it.
== Changelog ==
= 1.2.2 =
* improved custom post selection in settings

= 1.2.1 =
* added filter 'reorder_post_within_category_query_args'

= 1.2 =
* cleaned up, included better messages to ensure settings are saved after activation, else order menu is not shown
* fixed a small bug

= 1.1.7 =
* Bug fix to allow plugin to work with WP multisite network installation.
* enable editor role access to re-order menu
* fixed some languages translation issues

= 1.1.6 =
* Important bug fix (See http://wordpress.org/support/topic/updating-a-post-removes-it-from-the-custom-order). Thanks to Faison for this fix

= 1.1.5 =
* Add DE_de language

= 1.1.4 =
* Correct minor bug

= 1.1.3 =
* Add spanish translations. Special thanks to David Bravo for this !

= 1.1.2 =
* Add a plugin URI

= 1.1.1 =
* Bug Correction when a post is saving

= 1.1 =
* Change version number

= 1.0 =
* Minor Correction

= 0.1 =
* First commit; Initial version
