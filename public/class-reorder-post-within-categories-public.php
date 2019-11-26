<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://profiles.wordpress.org/aurovrata/
 * @since      1.0.0
 *
 * @package    Reorder_Post_Within_Categories
 * @subpackage Reorder_Post_Within_Categories/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Reorder_Post_Within_Categories
 * @subpackage Reorder_Post_Within_Categories/public
 * @author     Aurorata V. <vrata@syllogic.in>
 */
class Reorder_Post_Within_Categories_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Reorder_Post_Within_Categories_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Reorder_Post_Within_Categories_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/reorder-post-within-categories-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Reorder_Post_Within_Categories_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Reorder_Post_Within_Categories_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/reorder-post-within-categories-public.js', array( 'jquery' ), $this->version, false );

	}
	/**
	* filter post_join query.
	* hooked on 'posts_join'.
	* @since 1.0.0.
	*/
  public function filter_posts_join($args, $wp_query){
    $queriedObj = $wp_query->get_queried_object();

    if (isset($queriedObj->taxonomy) && isset($queriedObj->term_id)) {
      $term_id = $queriedObj->term_id;
    } else {
      return $args;
    }
		/** @since 2.3.0 check if the post type */
		$type = $wp_query->query_vars['post_type'];
		if( $this->is_ranked($term_id, $type, $wp_query) ){
			global $wpdb;
			/** @since 2.2.1 chnage from INNER JOIN to JOIN to see if fixes front-end queries*/
      $args .= "LEFT JOIN {$wpdb->postmeta} AS rankpm ON {$wpdb->posts}.ID = rankpm.post_id ";
    }

    return $args;
  }
	/**
	* filter posts_hwere query.
	* @since 1.0.0
	*/
  public function filter_posts_where($args, $wp_query){
      $queriedObj = $wp_query->get_queried_object();
      if (isset($queriedObj->taxonomy) && isset($queriedObj->term_id)) {
          $term_id = $queriedObj->term_id;
      } else {
          return $args;
      }
			/** @since 2.3.0 check if the post type */
			$type = $wp_query->query_vars['post_type'];
			if( $this->is_ranked($term_id, $type, $wp_query) ){
				/** @since 2.3.0 check if term id is ranked for this post type. */
				// if(!empty($type) && is_string($type)) $args .= " AND rankp.post_type ='{$type}'";
				$args .= " AND rankpm.meta_value={$term_id} AND rankpm.meta_key='_rpwc2' ";
			}
      return $args;
  }
	/**
	* filter posts_where query.
	* @since 1.0.0.
	*/
  public function filter_posts_orderby($args, $wp_query){
    $queriedObj = $wp_query->get_queried_object();
    if (isset($queriedObj->taxonomy) && isset($queriedObj->term_id)) {
        $term_id = $queriedObj->term_id;
    } else {
        return $args;
    }
		/** @since 2.3.0 check if the post type */
		$type = $wp_query->query_vars['post_type'];

		if( $this->is_ranked($term_id, $type, $wp_query) ){
        $args = "rankpm.meta_id ASC";
    }
    return $args;
  }
	/**
	* check if term id is a being ranked for this post type.
	*
	*@since 2.3.0
	*@param string $term_id term id being queried.
	*@param string $type post type being queried.
	*@return boolean true or false if being manually ranked.
	*/
	private function is_ranked($term_id, &$type, $wp_query){
		$tax_options = get_option(RPWC_OPTIONS, array());
		// debug_msg($tax_options, 'term '.$term_id);
		switch(true){
			case 'any' == $type: //multi type search cannot be done.
			case !empty($type) && is_array($type):
				//let's leave it to the user to decide what to do.
				$type=''; //reset.
				$type_filter = apply_filters('reorderpwc_filter_multiple_post_type', $type, $wp_query);
				if(!empty($type_filter) && is_string($type_filter)){
					$type = $type_filter;
				}
				break;
			case !empty($type): //type is set and single value.
			case $wp_query->is_attachment(): //type is empty.
			case $wp_query->is_page():
				break;
			default: //assume it is a post.
				$type = 'post';
				break;
		}
		$is_ranked=false;
		if(isset($tax_options[$type]) && isset($tax_options[$type][$term_id])){
			/** @since 2.3.0 check if term id is ranked for this post type. */
			if(isset($tax_options[$type][$term_id]) && $tax_options[$type][$term_id] == 'true'){
				$is_ranked=true;
			}
		}else if (!empty($tax_options[$term_id]) && $tax_options[$term_id] == "true" ) {
			//for backward compatibility, let's still check if the term id is ranked.
			$is_ranked=true;
		}
		return $is_ranked;
	}
}
