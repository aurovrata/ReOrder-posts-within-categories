=== Plugin Name ===
Contributors: aurovrata, aurelien, pondermatic, robrecord
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=36PNU5KYMP738
Tags: order, reorder, re-order, order by category,order custom post type, order by categories, order category, order categories, order by taxonomy, order by taxonomies, manual order, order posts
Requires at least: 4.4
Tested up to: 6.3.0
Requires PHP: 5.6
Stable tag: 2.14.5
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Enables manual ranking of post (and custom post) within taxonomy terms using a drag &amp; drop grid interface.

== Description ==
Due to a [bug](https://core.trac.wordpress.org/ticket/50070) in WordPress core, archive taxonomy queries are not being ranked properly on the front end.  If your **posts are not being ranked on your front-end site** please read this [thread](https://wordpress.org/support/topic/help-the-pluign-is-not-working/) for more information.

v2.3 is now multi-post taxonomy enabled.  A taxonomy registered with multiple post types can has its term's posts in each type ranked manually and separately.

**UPGRADE NOTE** if you are upgrading from v1.x, your old ranking data remains unaffected in the custom table used by the v1.x plugin.  However, in v2.x all the ranking is now stored as post meta.  While upgrading, some users have complained of missing posts/lost rankings.  If this is the case, you can reset your order for given term using the reset checkbox/button provided in the admin page (see screenshot #4).  It will reload the ranking from the v1.x custom table.  Please read FAQ #17 for more information on how to migrate your data.

If your term was not sorted in the v1.x table or you are upgrading from v2.0.x or v2.1.x, then the reset button will reload the post order as per the default WP post table listing, which can be changed using the filtrs provided (see FAQ #7).


ReOrder Post Within Categories is used to sort posts (and custom post type) in any custom order by drag & drop interface.
It works with a selected category, each category can have different order of same post.

New enhanced **version 2.0** with grid-layout and multi-drag interface to ease sorting of large list of posts.  Makes use of [SortableJS](https://sortablejs.github.io/Sortable/) plugin.  If you are using this plugin for a commercial website, please consider making a donation to the authors of the SortableJS plugin to continue its development.

== Thanks to ==
[Nikita Spivak](https://wordpress.org/support/users/nikitasp/) for the Russian translation.
[Tor-Bjorn Fjellner](https://profiles.wordpress.org/tobifjellner/) for the swedish translation and i18n clean-up.
[alekseo](https://wordpress.org/support/users/alekseo/) for support for the plugin.
[Andrei Negrea](https://github.com/andreicnegrea) for post delete bug fix.
[maddogprod](https://profiles.wordpress.org/maddogprod/) for helping resolve custom taxonomy front-end ordering.
[menard1965](https://profiles.wordpress.org/menard1965/) for helping resolve `get_adjacent_post` prev/next ranked posts.
[alexjamesbishop](https://profiles.wordpress.org/alexjamesbishop/) for helping fix the 'orderby' bug.
[pondermatic](https://profiles.wordpress.org/pondermatic/) for fixing the min-range bug.
[andreicnegrea](https://profiles.wordpress.org/andreicnegrea/) for fixing the offset warnings.
[isinica](https://profiles.wordpress.org/isinica/) for fixing the disappearing ranked post when editing them.
[sarahjsouris](https://profiles.wordpress.org/sarahjsouris/) from [playimports.com.au](https://www.playimports.com.au) for sponsoring WooCommerce plugin upgrade.
[howdy_mcgee](https://profiles.wordpress.org/howdy_mcgee/) - helping fix array orderby directives for WooCommerce.
[pavelkovar](https://profiles.wordpress.org/pavelkovar/) - helping fix html escaping issues on admin pages.

== Installation ==

1. Upload the 'reorder-posts-within-categories' folder to the '/wp-content/plugins/' directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to the settings page to activate sorting for each categories you choose.


== Screenshots ==

1. (1) Plugin page settings, if you uninstall this plugin for good, delete all data using this settings page first before deactivating the plugin.
2. (2) Re-order your post through a drag & drop grid-layout interface with multi-select capabilities.  For large sets of posts, a range slider will appear allowing you to view your posts in sub-sets by moving the slider range accordingly and sorting posts in smaller more manageable groups.  You can also multi-select the posts and enter a rank value to which you want to send those selected posts too.  For example, if you are sorting posts between the ranks fo 100 and 150 and you want to send 3 posts to the beginning of the order, simply select them and enter 1 in the rank input field and press enter. A rest button is introduced in v2.1 so an order can be reset.  Using the filters described in faq #7 it is possible to reset the default ranking to various initial ordered lists.
3. (3) v2.1 introduced a reset button on the amdin reorder page. The checkbox enables the button which you can use to reset your posts ranking order for this term.  This in conjunction with the intial order filters (see FAQ 7) allows you to set a chronological or an alphabetical ranking for the initial order.
4. (4) the reset checkbox will enable the reset button.  If you upgraded from v1.x and you have not deteleted the custom table used in the previous versions, the reset button will reload your previously stored ranking for ther term if it exists in the table.  Otherwise the default post table ranking will be loaded which can be modified using the filters provided (see FAQ #7 for more info).
5. (5) in v2.6 you can now override `orderby` directives in queries.  Use with **caution** because this will override all `orderby` directives, and some WooCommerce themes allow sorting by price which will not work.  Read FAQ #10 for more details on how to have a finer control of this.

== FAQ ==
= 1.Retrieving ordered posts with custom get_posts query not working! =

this plugin uses filters (posts_join, posts_where, and posts_orderby) to modify the front-end query for ordered posts and ensure the results are ordered as per your custom order.

However, `get_posts` function uses a 'suppress_filters' [parameter](https://developer.wordpress.org/reference/functions/get_posts/#parameters) which is set to true by default.  You need to explicitly set it to false in your custom queries to ensure you retrieve yours posts in the right order.

= 2.I want to order posts in non-hierarchical taxonomies (tags) =
By default the plugin allows you to order posts only within hierarchical taxonomies (categories).  This is done as a means to ensure one doesn't have spurious orders as allowing both tags and category ordering could lead to users trying to order a post in both and this would create issues which have not been tested by this author.  Hence tread with caution if you enable this in your functions.php file,

`add_filter('reorder_post_within_categories_and_tags', '__return__true');`

Keep in mind that you will now see `Pages` as a post type to re-order, selecting such post types which do not have any categories associated with it.

= 3.I want limit/enable roles that can re-order posts =

Since v1.3.0 a new filter has been added that allows you to do that.  Make sure you return a [valid capability](https://wordpress.org/support/article/roles-and-capabilities/#roles),

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
function rank_new_posts($is_first, $post, $term_id){
    $is_first = true;
    //you can filter by taxonomy term, or other post parameters.
    //WP_Post $post object being ranked;
    //$term_id for which the post is rank is being inserted.
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
as of v2.4 it is now possible to programmatically rank the intial post order, see FAQ 11.
= 8. When I drag the slider, both sliders move and the number of loaded posts remain fixed. =
When you have a large number of posts in a category, the controls move when the limit of posts to display is reached.

This to reduce the load on the server. WP limits REST api posts to 100, and this is the base value used. However, the plugin uses a dynamic approach, based on a square grid, hence when your posts grid number of columns equates the number of rows, the slider will automatically adjust the non-dragged slider button to maintain that square.

If you wish to display more posts, reduce your window zoom level (ctrl+mouse scroll on firefox/chrome), this will force the number of columns to expand and therefore the js script will allow more posts to be loaded until the rows match the columns.

= 9. Multi-post taxonomy query not ranked =

When you have a custom query to display a set of posts on the front-end which combines multiple post-types under a single taxonomy term, then the plugin needs to be told which post-type to use to rank the results.  It will fire a filter which you need to hook,
`
apply_filters('reorderpwc_filter_multiple_post_type', 'ranking_post_type',10,2);
function ranking_post_type($type, $wp_query){
  //use WP_Query object to figure is this is your query,
  //then return the post-type the to use to rank the results.
  //if no type is returned the posts will be ranked by date.
  return $type;
}
`

= 10. My posts are not being ranked properly on the front-end =

**There are several reasons why this might happen,**

**1. You are using a custom query get_posts()...**
If you are displaying your posts using a **custom query with the function get_posts()** you should be aware that it sets the attribute 'suppress_filters' to true by default (see the [codex page](https://developer.wordpress.org/reference/functions/get_posts/#parameters)).  The ranked order is applied using filters on the query, hence you need to explictly set this attribute to `false` to get your results ranked properly.

**2. Your theme or custom query explictly set the 'orderby' query attribute. **
If your **query explicitly sets the 'orderby'** [attribute](https://developer.wordpress.org/reference/classes/wp_query/#order-orderby-parameters), and the override checkbox is checked (see [screenshot](https://wordpress.org/plugins/reorder-post-within-categories/#screenshots) #5), then the plugin will override your query and rank the results as per your manual order.  However, if you uncheck the ovverride setting (ie override is set to false), your query will be ordered as per the orderby directive.  However, you can programmatically override the `orderby` directive with the following hook should you need finer control,

`add_filter('rpwc2_allow_custom_sort_orderby_override', 'override_orderby_sorting', 10,5);
function  override_orderby_sorting($override, $wp_query, $taxonomy, $term_id, $type){
    //check this is the correct query
    if($wp_query....){
      $override = true;
    }
    return $override;
}`

** 3. You are displaying a taxonomy archive page. **
If your query is a **taxonomy archive query** for a given term, then WordPress core query does not specify the `post_type` by default see this [bug](https://core.trac.wordpress.org/ticket/50070)).  This forces the plugin to seek which `post_type` is associated with this taxonomy.  **In the event that you are using this taxonomy to classify multiple post types** this will lead to the plugin choosing the first type it encounters with available posts for the queried term, and this may give spurious results.  A hook is provided for you to correctly filter the `post_type` and ensure the right results,

`
add_filter('reorderpwc_filter_multiple_post_type', 'filter_my_ranked_post_type', 10, 4);
function filter_my_ranked_post_type($type, $post_types, $taxonomy, $wp_query){
  /* String $type post type to filter.
  *  String $post_types post types associated with taxonomy.
  *  String $taxonomy being queried.
  *  WP_Query $wp_query query object. */
  if('my-custom-tax' == $taxonomy && in_array('my-custom-post',$post_types)) $type = 'my-custom-post';
  return $type;
}
`

= 11. Programmatically ranking initial post order in admin page. =
If you are migrating from another plugin in which you have painstakingly sorted your posts, or you need have the intial order of posts based on some other criteria (some date or other meta field value), then you can use the following filter to pass the required rank,

`add_filter('rpwc2_filter_default_ranking', 'custom_intial_order', 10, 4);
function custom_intial_order($ranking, $term_id, $taxonomy, $post_type){
  //$ranking an array containing a list of post IDs in their default order.
  //$term_id the current term being reordered.
  //$taxonomy the taxonomy to which the term belongs.
  //$post_type the post type being reordered.
  //check if this is the correct taxonomy/post type you wish to reorder.
  if('my-custom-post' != $post_type || 'my-category'!=$taxonomy ) return $ranking;
  //load you default order programmatically... says as $new_order from your DB
  $filtered_order = array()
  foreach($new_order as $post_id){
    //check the post ID is actually in the ranking.
    if(in_array($post_id, $new_order)) filtered_order[]=$post_id;
  }
  return $filtered_order;
}`

in version 2.6.1, an additional filter is introduced to allow different post status to appear in the initial rank,

`add_filter('rpwc2_initial_rank_posts_status', 'allow_draft_in_initial_order',10,3);
function allow_draft_in_initial_order($status, $post_type, $term_id){
  //allow draft post to be ranked initially.  By default $status=array('private','publish','future').
  if('post'==$post_type){
    $status[]='draft';
  }
  return $status;
}`
this will only affect the posts in the admin dashboard reorder page.

= 12. Can I rank draft posts? =

Yes!  By default all posts moved to draft/pending status are removed from the manual ranking.  However, you can hook the following filter and control which draft or pending posts should appear in the manual ranking in the amdin dashboard,

`add_filter('rpwc2_rank_draft_posts', 'allow_draft_posts_in_ranking', 10, 5);
function allow_draft_posts_in_ranking($allow, $new_status, $old_status, $term_id, $post){
  //$new_status of the post being saved.
  //$old_status of the post being saved.
  //$term_id term for which the post is being ranked.
  //WP_Post object being saved.
  if(new_status == 'pending' && $term_id == 5){ //allow pending posts for term id 5 to be ranked.
    $allow = true;
  }
  return $allow;
}`
NOTE:  this will only affect the admin dashboard queries.  Your draft posts will appear in the admin re-order pages but will not appear in the front-end queries, as only published posts will be retrieved by your queries.
If you need to have draft/pending posts in the intial ranking, see FAQ #11.

= 13. Can I remove private/future posts from the manual rank ? =

Yes, there is a filter that allows you to control those too,
`add_filter('rpwc2_rank_published_posts', 'disable_future_posts_in_ranking', 10, 5);
function allow_draft_posts_in_ranking($allow, $new_status, $old_status, $term_id, $post){
  //$new_status of the post being saved.
  //$old_status of the post being saved.
  //$term_id term for which the post is being ranked.
  //WP_Post object being saved.
  if(new_status == 'future' && $term_id == 5){ //do not allow future posts for term id 5 to be ranked.
    $allow = false;
  }
  return $allow;
}`
NOTE: note that this will effect front-end mixed-queries trying to display both future (and/or private) and 'publish'ed posts.

as of v2.6.1, the intial sorted posts includes future and provate posts which you can remove using,

`add_filter('rpwc2_initial_rank_posts_status', 'disable_future_in_initial_order',10,3);
function allow_draft_in_initial_order($status, $post_type, $term_id){
  //allow draft post to be ranked initially.  By default $status=array('private','publish','future').
  if('post'==$post_type){
    $status=array('publish','private');
  }
  return $status;
}`

**NOTE**: in all 3 cases, you may use the reset button (see screenshot #3) on the reorder admin page to get the filters to change the order.

= 14. Is it possible to control when the manual sorting is applied programmatically ? =
In v2.7 a new filter has been added to do just that, allowing you to override the sorting of anually ranked posts,

`
add_filter('rpwc2_manual_sort_override', 'override_manual_sorting', 10,5);
function  override_manual_sorting($apply_sorting, $wp_query, $taxonomy, $term_id, $type){
    //$apply_sorting a boolean to filter, true by default, which will apply the manual sorting.
    //the current queried $taxonomy with $term_id for post_type $type.
    // $wp_query is the WP_Querry objet
    //check some parameters a
    if(....){
      $apply_sorting = false; //do not sort using the manual ranking.
    }
    return $apply_sorting;
}
`

= 15. How to enable post navigation (prev/next) on a page based on the manual order? =
use the WordPress core functions,

[get_the_posts_navigation()](https://developer.wordpress.org/reference/functions/get_the_posts_navigation/),
or [get_previous_posts_link()](https://developer.wordpress.org/reference/functions/get_previous_posts_link/) and [get_next_posts_link()](https://developer.wordpress.org/reference/functions/get_next_posts_link/).

= 16. How to get adjacent posts IDs? =
 Unlike the the posts navigation links, the function `get_adjacent_post()` can be called outside a paged query, and will not return the correct post IDs in your manual sorted posts.

 as of v2.8, a new function is provided to expose this functionality, however you will need to provide the post ID, the taxonomy term and the post type which identifies this post as being part of a manually sorted list of posts for that term.

 `
$adj = get_adjacent_rpwc2_posts($post_id, $term_id, $post_type, $taxonomy);
//if the post is part of a manually ranked list,
$adj->prev_post; //this is the previous post ID, null is the post is the first in the list.
$adj->next_post; //this is the next post ID, null is the post is the last in the list.

 `
= 17. I have upgraded from v1.X, should I delete the old table ? =

The old plugin (v1.X) used a custom table to stored the manual order of posts.  This legacy data will be imported into the new plugin when you upgrade.  However, it is important that you delete the old table once you have successfully imported your ranking data.

**How so I know if the data was successfully imported?**
Go to your posts reorder admin pages, for each manually sorted term, check that your posts are in the correct order and that none are missing. If the lists of posts are correct (the right number of posts in the right order), **then move the 2nd posts to the first position and back to the 2nd position**.  This will automatically save the ranked list into your database usign the latest format.  Repeat for all manually sorted terms.  Once they are all saved with the new format, you will need to delete the legacy table.

If you have spurious post listings then it is possible the imported data has been corrupted, you can reset it using the provided reset button, however, if the list of posts is still showing erroneous results then you will need to reset the manual ranking for that term one you have deleted the legacy table, and manually re-order your posts.

**How do I delete the legacy data? **
In your dashboard, navigate to the Settings->Reorder Posts page, scroll to the bottom of the page and proceed to the delete the legacy table under the section title: **'Delete old custom table from plugin v1.x'**.

= 18. How can I access the ranking order of a post within a term ? =

v2.11 introduced the `get_rpwc2_order()` function to do this.

`
//the function returns an array of post IDs for a given post type and term ID
$ranking = get_rpwc2_order($post_type, $term_id);
//the ranking will refelct the manual sort order that was saved in the admin reOrder page.
$zero_based_rank = array_search($post_ID, $ranking);
`

= 19. Can I order attahment posts types ? =
As of v2.13 this is now possible thanks to the contribution from @robrecord.  You will need to add the 'inherit' post status to the allowed status to make it work using the following filters in your `functions.php` file,

`
add_filter(
    'rpwc2_initial_rank_posts_allowed_statuses',
    function ($statuses) {
        $statuses[] = 'inherit';
        return $statuses;
    }
);

add_filter(
    'rpwc2_initial_rank_posts_status',
    function ($status, $post_type, $term_id) {
        if ('attachment' === $post_type) {
            $status[] = 'inherit';
        }
        return $status;
    },
    10,
    3
);
`
= 20. Can I debug the manual ranking process ? =
Sure, if you enable `WP_DEBUG` and `WPGURUS_DEBUG` in your `wp-config.php` file,

`
define('WP_DEBUG', true);
define('WPGURUS_DEBUG', true);
`
the plugin will printout debug messages, along with the final SQL query for your manually ranked posts.  This is useful in order to determine if another plugin is also filtering your posts queries and overriding the ranking of the resuls.

== Changelog ==
= 2.14.5 =
* fix term validation issue on admin reorder page.
= 2.14.4 =
* cleanup html escaping logic.
* fix misuse esc_attr_e() bug.
= 2.14.3 =
* added localisation for admin menu labels.
= 2.14.2 =
* fix admin page refresh post selection.
* fix for PHPCS WP Code security std.
= 2.14.1 =
* WPML compatibility fix.
= 2.14.0 =
* enable full SQL print in debug mode.
* cache ranked queries to speed up front-end queries.
= 2.13.0 =
* enable attachment posts (see FAQ #19)
* enable upgrade warnings before major upgrades.
* fix null start/end in admin page.
= 2.12.5 =
* handle array orderby directives.
= 2.12.4 =
* handle multi orderby directive.
= 2.12.3 =
* fix slider range reload bug.
= 2.12.2 =
* fix non-woocommerce override.
= 2.12.1 =
* typo fix
= 2.12.0 =
* WooCommerce products orderby overriden by default.
= 2.11.0 =
* expose functionality `get_rpwc2_order()` to retrieve rank for given post_type in given term.
= 2.10.4 =
* undo change to v2.10.3
= 2.10.3 =
* set post id select query as unique.
= 2.10.2 =
* fix ranked id merger bug.
= 2.10.1 =
* fix disappearing ranked posts when editing them.
* fix illegal offset warnings.
= 2.10.0 =
* improve taxonomy term dropdown list.
