=== Plugin Name ===
Contributors: aurovrata, aurelien
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=36PNU5KYMP738
Tags: order, reorder, re-order, order by category,order custom post type, order by categories, order category, order categories, order by taxonomy, order by taxonomies, manual order, order posts
Requires at least: 3.4
Tested up to: 5.2.0
Requires PHP: 5.6
Stable tag: trunk
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Sort Post and Custom Post Type through drag & drop interface of selected category (or custom selected taxonomies).

== Description ==

**UPGRADE NOTE** if you are upgrading from v1.x, your old ranking data remains unaffected in the custom table used by the v1.x plugin.  However, in v2.x all the ranking is now stored as post meta.  While upgrading, some users have complained of missing posts/ lost rankings.  If this is the case, you can reset your order for given term using the reset checkbox/button provided in the admin page (see screenshot #4).  It will reload the ranking from the v1.x custom table.

If your term was not sorted in the v1.x table or you are upgrading from v2.0.x or v2.1.x, then the reset button will reload the post order as per the default WP post table listing, which can be changed using the filtrs provided (see FAQ #7).


ReOrder Post Within Categories is used to sort posts (and custom post type) in any custom order by drag & drop interface.
It works with a selected category, each category can have different order of same post.

New enhanced **version 2.0** with grid-layout and multi-drag interface to ease sorting of large list of posts.  Makes use of [SortableJS](https://sortablejs.github.io/Sortable/) plugin.  If you are using this plugin for a commercial website, please consider making a donation to the authors of the SortableJS plugin to continue its development.

= Thanks to =
[Nikita Spivak](https://wordpress.org/support/users/nikitasp/) for the Russian translation.
[Tor-Bjorn Fjellner](https://profiles.wordpress.org/tobifjellner/) for the swedish translation and i18n clean-up.
[alekseo](https://wordpress.org/support/users/alekseo/) for support for the plugin.
[Andrei Negrea](https://github.com/andreicnegrea) for post delete bug fix.

== Installation ==

1. Upload the 'reorder-posts-within-categories' folder to the '/wp-content/plugins/' directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to the settings page to activate sorting for each categories you choose.


== Screenshots ==

1. Plugin page settings, if you uninstall this plugin for good, delete all data using this settings page first before deactivating the plugin.
2. Re-order your post through a drag & drop grid-layout interface with multi-select capabilities.  For large sets of posts, a range slider will appear allowing you to view your posts in sub-sets by moving the slider range accordingly and sorting posts in smaller more manageable groups.  You can also multi-select the posts and enter a rank value to which you want to send those selected posts too.  For example, if you are sorting posts between the ranks fo 100 and 150 and you want to send 3 posts to the beginning of the order, simply select them and enter 1 in the rank input field and press enter. A rest button is introduced in v2.1 so an order can be reset.  Using the filters described in faq #7 it is possible to reset the default ranking to various initial ordered lists.
3. v2.1 introduced a reset button on the amdin reorder page. The checkbox enables the button which you can use to reset your posts ranking order for this term.  This in conjunction with the intial order filters (see FAQ 7) allows you to set a chronological or an alphabetical ranking for the initial order.
4. the reset checkbox will enable the reset button.  If you upgraded from v1.x and you have not deteleted the custom table used in the previous versions, the reset button will reload your previously stored ranking for ther term if it exists in the table.  Otherwise the default post table ranking will be loaded which can be modified using the filters provided (see FAQ #7 for more info).

== FAQ ==
= 1.Modify the reorder category query =

A filter allows you to hook into the query of the posts before your reorder them in the dashboard.  This is useful is you want to order parent terms posts and not children.  WP post category query by default include post from children terms, which will show up in the order list.  So by excluding them you are able to order only the posts of parent terms,
`
add_filter('reorder_post_within_category_query_args', 'exclude_children');
function exclude_children($args) {
    $args['tax_query'][0]['include_children']=false;
    return $args;
}`

= 2.I want to order posts in non-hierarchical taxonomies (tags) =
By default the plugin allows you to order posts only within hierarchical taxonomies (categories).  This is done as a means to ensure one doesn't have spurious orders as allowing both tags and category ordering could lead to users trying to order a post in both and this would create issues which have not been tested by this author.  Hence tread with caution if you enable this in your functions.php file,

`add_filter('reorder_post_within_categories_and_tags', '__return__true');`

Keep in mind that you will now see `Pages` as a post type to re-order, selecting such post types which do not have any categories associated with it.

= 3.I want limit/enable roles that can re-order posts =

Since v1.3.0 a new filter has been added that allows you to do that.  Make sure you return a [valid capability](https://codex.wordpress.org/Roles_and_Capabilities#Capabilities),

`add_filter('reorder_post_within_categories_capability', 'enable_editors', 10,2);
function enable_editors($capability, $post_type){
    //you can filter based on the post type
    if('my-users-posts' == $post_type){
        $capability = 'publish_posts'; //Author role.
    }
    return $capability;
}`
if an unknown capability is returned, the plugin will default back to 'manage_categories' which is an administrator's capability.

= 4.I am uninstalling this plugin, how do I removed the custom table data ? =
You can now flag the custom sql table to be deleted when you disable the plugin from your dashboard with the following filter,
` add_filter('reorder_post_within_categories_delete_custom_table', '__return__true')`
note that this filter is fired when you disable the plugin in the dashboard.  So make sure it is activated when you set this filter.

= 5.Can newly published posts be ranked first rather than last? =
Yes, as of v2.0 newly published posts can be ranked first instead of last by default using the following filter,

`add-filter('reorder_post_within_categories_new_post_first', 'rank_new_posts', 10, 3);
function rank_new_posts($is_first, $post, $term){
    $is_first = true;
    //you can filter by taxonomy term, or other post parameters.
    //WP_Post $post;
    //WP_Term $term.
    return $is_first;
}
`
NOTE: the post-type must already have a manual ranking for that category term for this hook to fire.  TO ensure this, go to the post ReOrder admin page, select the category term and manually order a couple of post, this is enough to ensure this hook fires.  Even if you have the manual ranking radio-toggle to 'No', this hook will still fire.

= 6. Is it possible to customise the text on the sortable cards? =
Yes. On v2+ of this plugin, the sortable cards are now displaying the thumbnail of each posts along with the title.  The title text can be changed or added to in case you require additional meta fields to be displayed to help you manually rank your posts.  To achieve this, hook the following filter,
`
add_filter ('reorder_posts_within_category_card_text', 'custom_card_text', 10,3 );
function custom_card_text($text, $post,$term_id){
  //the $text is set to the title fo the post by default.
  //$post is the WP_Post object.
  //$term_id is the taxonomy term being sorted.
  $text = '<div>'.$text.'</div><div>'.get_post_meta($post->ID, 'custom-field', true).'</div>';
  return $text;
}
`
= 7. The initial order of post is chronological, can it be changed? =
Yes, by default the first time you manually sort your posts, they will be presented in the same order as your post table, namely by post data.  There are 3 possible alternative default order you can set,
1.reverse chronological by hooking this filter,

`
add_filter('reorder_posts_within_category_initial_order', 'reverse_order', 10, 3);
function reverse_order($reverse, $post_type, $term_id){
  //$reverse is a boolean flag.
  //$post_type for the current posts being ranked.
  //$term_id of the taxonomy term for which the posts are being ranked.
  return true;
}
`
2. by alphabetical title order, using the following hook,

`
add_filter('reorder_posts_within_category_initial_orderby', 'chronological_or_alphabetical_order', 10, 3);
function chronological_or_alphabetical_order($is_alpha, $post_type, $term_id){
  //$is_alpha is a boolean flag set to false by default.
  //$post_type for the current posts being ranked.
  //$term_id of the taxonomy term for which the posts are being ranked.
  return true;
}
`
3. or by reverse alphabetical title order, using both of the above hooks,

`
add_filter('reorder_posts_within_category_initial_order', 'reverse_order', 10, 3);
function reverse_order($reverse, $post_type, $term_id){
  //$reverse is a boolean flag.
  //$post_type for the current posts being ranked.
  //$term_id of the taxonomy term for which the posts are being ranked.
  return true;
}
add_filter('reorder_posts_within_category_initial_orderby', 'chronological_or_alphabetical_order', 10, 3);
function chronological_or_alphabetical_order($is_alpha, $post_type, $term_id){
  //$is_alpha is a boolean flag set to false by default.
  //$post_type for the current posts being ranked.
  //$term_id of the taxonomy term for which the posts are being ranked.
  return true;
}
`
**NOTE**: in all 3 cases, you may use the reset button (see screenshot #3) on the reorder admin page to get the filters to change the order.

== Changelog ==
= 2.2.0 =
* removed delete_before_post.
* reset post rank from v1.x table in admin page.

= 2.1.4 =
* fix sql order by bug in admin order page.

= 2.1.3 =
* fix bug on save_post.

= 2.1.2 =
* fix multisite bug.

= 2.1.1 =
* fix slider post loading.
* max posts loaded to form square grid.

= 2.1.0 =
* deprecated filter 'reorder_post_within_category_taxonomy_term_query_args'.
* improved helper text on reorder pages.
* added a reset button on order page.

= 2.0.1 =
* js bug fix preventing proper saving of orders.

= 2.0.0 =
* complete re-write of the plugin file structure.
* removal of custom DB table, post rank is now saved as a postmeta key.
* addition of a new filter 'reorder_post_within_categories_new_post_first' to allow new posts to be ranked first instead of last by default.
* proper handling of post_type for order ranking.
* ability to reset/delete data from settings page.
* added filter 'reorder_posts_within_category_card_text'.
* added filter 'reorder_posts_within_category_initial_orderby'.
* added filter 'reorder_posts_within_category_initial_order'.
* using sortableJS plugin for Grid layout using multi-grid sorting for large lists.
* addition of thumbnails on sortable cards for better visual representation of posts.

= 1.8.1 =
* english corrections.
= 1.8.0 =
* I18N: Changed language of translatable strings in the code to en_US. Inline code comments are still in French.
= 1.7.0 =
* introduce admin post link in order list.
= 1.6.0 =
* added term query filter 'reorder_post_within_category_taxonomy_term_query_args'
= 1.5.0 =
* fixed capability bug.
* added filter to delete custom sql table when uninstalling.

= 1.4.1 =
* changed text-domain to include plugin in translate.wordpress.org.
= 1.4.0 =
* added russian locale.
=1.3.0=
* added filter to change capability of reorder post submenu access.
=1.2.3=
* bug fix
= 1.2.2 =
* improved custom post selection in settings
* added filter 'reorder_post_within_categories_and_tags'

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
