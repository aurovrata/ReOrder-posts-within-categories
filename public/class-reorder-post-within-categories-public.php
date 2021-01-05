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
	 * The store the manually ranked terms.
	 *
	 * @since    2.9.0
	 * @access   private
	 * @var      Array    $ranked_terms   term IDs => post_types.
	 * @var      Array    $tax_options  plugin's stored options.
	 */
	static private $ranked_terms;
	static private $tax_options;
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
	* filter post_join query.
	* hooked on 'posts_join'.
	* @since 1.0.0.
	*/
  public function filter_posts_join($args, $wp_query){
		if( $this->is_manual_sort_query($wp_query) ){
			global $wpdb;
			/** @since 2.2.1 chnage from INNER JOIN to JOIN to see if fixes front-end queries*/
      $args .= " LEFT JOIN {$wpdb->postmeta} AS rankpm ON {$wpdb->posts}.ID = rankpm.post_id ";
    }
    return $args;
  }
	/**
	* filter posts_hwere query.
	* @since 1.0.0
	*/
  public function filter_posts_where($args, $wp_query){
		if( ($term_id = $this->is_manual_sort_query( $wp_query)) ){
			/** @since 2.3.0 check if term id is ranked for this post type. */
			$args .= " AND rankpm.meta_value={$term_id} AND rankpm.meta_key='_rpwc2' ";
			debug_msg("RPWC2 SORT VALIDATION, sorting posts in term: {$term_id}");
		}
    return $args;
  }

	/**
	* filter posts_where query.
	* @since 1.0.0.
	*/
  public function filter_posts_orderby($args, $wp_query){
		if( $this->is_manual_sort_query($wp_query, true) ){
        $args = "rankpm.meta_id ASC";
        add_filter('posts_clauses', function($pieces) use ($args){
					if( false !== strstr($pieces['where'],'rankpm') ){
						$pieces['orderby']=$args;
					}
					return $pieces;
        });
    }
    return $args;
  }
	/**
	* function to validate manual sorting queries.
	*
	*@since 2.5.9
	*@param WP_Query $wp_query query
	*@return boolean true is manual sorting required.
	*/
	private function is_manual_sort_query($wp_query, $print_dbg=false){
		$queriedObj = $wp_query->get_queried_object();

    if (isset($queriedObj->taxonomy) && isset($queriedObj->term_id)) {
			/** @since 2.9.1 fix multi-term queries bug */
			$validate = true;
			switch(true){
				case $wp_query->is_category and ($wp_query->is_tag || $wp_query->is_tax):
				case $wp_query->is_tag and ($wp_query->is_category || $wp_query->is_tax):
				case $wp_query->is_tax and ($wp_query->is_category || $wp_query->is_tag):
					$validate = false; //multiple taxonomy queried.
					break;
				case isset($wp_query->tax_query):
					foreach($wp_query->tax_query->queries as $t){
						if(count($t['terms'])>1) $validate = false; //multiple terms queried.
					}
					break;
			}
			if(!$validate) return 0;
      $term_id = $queriedObj->term_id;
			$taxonomy = $queriedObj->taxonomy;
			 // debug_msg($wp_query->tax_query->queries, "$taxonomy is ranked $term_id ");
    } else {
      return 0;
    }
		/** @since 2.3.0 check if the post type */
		$type = $wp_query->query_vars['post_type'];
		/** @since 2.4.3 fix for woocommerce */
		if(empty($type) && isset($wp_query->query_vars['wc_query']) && 'product_query'==$wp_query->query_vars['wc_query']){
			$type = 'product';
		}
		return (self::is_ranked($taxonomy, $term_id, $type, $wp_query, $print_dbg)) ? $term_id : 0;
	}
	/**
	* check if term id is a being ranked for this post type.
	*
	*@since 2.3.0
  *@param string $taxonomy taxonomy being queried.
	*@param string $term_id term id being queried.
	*@param string $type post type being queried.
	*@return boolean true or false if being manually ranked.
	*/
	static private function is_ranked($taxonomy, $term_id, &$type, $wp_query=null, $print_dbg=false){
    if(empty($wp_query) && empty($type)) return false;

		/** @since 2.9.0 speedup ranking check */
		if(!isset(self::$tax_options)){
			self::$tax_options = get_option(RPWC_OPTIONS_2, array());
			self::$ranked_terms = array();
			foreach(self::$tax_options as $p=>$terms){
				if($p){
					foreach($terms as $tid=>$r){
						if( isset(self::$ranked_terms[$tid]) ) array_push(self::$ranked_terms[$tid], $p);
						else self::$ranked_terms[$tid] = array($p);
					}
				}
			}
		}
		if(isset(self::$ranked_terms[$term_id])) $type = self::$ranked_terms[$term_id];
		else return false; //term id is not manually ranked.
		switch(true){
      case 'any' == $type:
        return false; //multi type search cannot be done.
			case !empty($type) && is_array($type) && count($type)<2 :
        /** @since 2.7.3 select single posts */
        $type=$type[0];
				break;
			case !empty($type) && !is_array($type): //type is set and single value.
        break;
			case $wp_query->is_attachment(): //type is empty.
        $type = 'attachment';
        break;
			case $wp_query->is_page():
        $type = 'page';
				break;
      case empty($type) && ($wp_query->is_tax() || $wp_query->is_category()): /** @since 2.5.1 fix */
      case is_array($type):
        if(empty($type)){
        	// Do a fully inclusive search for currently registered post types of queried taxonomies.
        	$post_type  = array();
        	foreach ( get_post_types( array( 'exclude_from_search' => false ) ) as $pt ) {
          	$object_taxonomies = get_object_taxonomies( $pt );
  	        if ( in_array( $taxonomy, $object_taxonomies ) ) {
  	          $post_type[] = $pt;
  	        }
        	}
        }else $post_type = $type;
				// debug_msg($post_type, $taxonomy.' term '.$term_id);
				switch(count($post_type)){
					case 1:
						$type = $post_type[0];
						break;
					default: //multiple post types or none.
					  /** @since 2.5.9 assume types with posts to make it easier for non-devs */
						$types_with_posts = array();
						if($print_dbg) debug_msg($post_type, "RPWC2 SORT VALIDATION, found multiple post types: ");
						/** filter multiple post types.
						* @since 2.5.0.
						* @param String $type post type to filter.
						* @param String $post_types post types associated with taxonomy.
						* @param String $taxonomy being queried.
						* @param WP_Query $wp_query query object.
						*/
						$type = apply_filters('rpwc2_filter_multiple_post_type','', $post_type, $taxonomy, $wp_query);
						/* deprecated in 2.6.0 */
						$type = apply_filters('reorderpwc_filter_multiple_post_type','', $post_type, $taxonomy, $wp_query);
						if(empty($type)){
							//find if any post types has multiple posts in this term.
	            foreach($post_type as $pt){
								// if(self::count_posts($pt, $term_id, $taxonomy) > 1){ already counted in admin.
								$types_with_posts[]=$pt;
                switch(true){
                  case ('attachment' == $pt): //unlikely being displayed.
                    break;
                  default:
                    if(empty($type)){
											if($print_dbg) debug_msg("RPWC2 SORT VALIDATION, using type '{$pt}'.");
											$type = $pt;
										}else{
											if($print_dbg) debug_msg("RPWC2 SORT VALIDATION, ignoring type '{$pt}', if this is the post type you are trying to sort, use the 'rpwc2_filter_multiple_post_type' hook as detailed in FAQ #10.");
										}
                    break;
                }
							}
						}
            if(empty($type) || !is_string($type)){
							if($print_dbg) debug_msg($post_type, "RPWC2 SORT VALIDATION ABORTED, found multiple post types, non suitable: ");
              return false;
            }
						break;
				}
				break;
			default: //assume it is a post.
				$type = 'post';
				break;
		}
		if($print_dbg) debug_msg("RPWC2 SORT VALIDATION, found post_type '{$type}' / taxonomy '{$taxonomy}'({$term_id})");

		$is_ranked=false;
		if( isset(self::$tax_options[$type]) && isset(self::$tax_options[$type][$term_id]) ){
			$is_ranked = self::$tax_options[$type][$term_id]['order'];
			/** @since 2.5.9 allow custom ranking 'orderby' override. */
			if( $is_ranked && isset($wp_query) && !empty($wp_query->query_vars['orderby']) ){
				$override = self::$tax_options[$type][$term_id]['override'];
				$is_ranked = apply_filters('rpwc2_allow_custom_sort_orderby_override', $override, $wp_query, $taxonomy, $term_id, $type);
				if($print_dbg){
					if( !$is_ranked )  debug_msg("RPWC2 SORT VALIDATION ABORTED, for orderby: {$wp_query->query_vars['orderby']}");
        	else debug_msg("RPWC2 SORT VALIDATION, overriding orderby: '{$wp_query->query_vars['orderby']}'");
				}
			}
			if( $is_ranked && isset($wp_query) ){ /** @since 2.7.0 allow general override filter */
				$is_ranked = apply_filters('rpwc2_manual_sort_override', $is_ranked, $wp_query, $taxonomy, $term_id, $type);
			}
		}
		return $is_ranked;
	}
	/**
	* Function to retrieve post count for a given term/post_type.
	*
	*@since 2.5.9
	*@param string $param text_description
	*@return string text_description
	*/
	static protected function count_posts($post_type, $term_id, $taxonomy){
    //return $count;
    $args = array(
      'post_type'     => $post_type,
      'post_status'   => array('publish','future','private'),
      'posts_per_page' => -1,
      'tax_query' => array(
        array(
          'taxonomy' => $taxonomy,
          'field' => 'term_id',
          'terms' =>  $term_id,
					'include_children' => 0,
        )
      )
    );
    $posts = get_posts( $args); //suppress filters.
    return (int) count($posts);
	}
  /**
  * Get adjacent post in ranked posts.
  * dismiss join sql statement. hooked on 'get_previous_post_join' / 'get_next_post_join'
  * @since 2.4.4
  * @param string  $join           The JOIN clause in the SQL.
  * @param bool    $in_same_term   Whether post should be in a same taxonomy term.
  * @param array   $excluded_terms Array of excluded term IDs.
  * @param string  $taxonomy       Taxonomy. Used to identify the term used when `$in_same_term` is true.
  * @param WP_Post $post           WP_Post object.
  */
  public function filter_adjacent_post_join($join, $in_same_term, $excluded_terms, $taxonomy, $post){
		if(!$in_same_term) return $join;
		//else check the term.
		if ( ! is_object_in_taxonomy( $post->post_type, $taxonomy ) ) return $join;

		$term =	$this->check_for_ranked_term($excluded_terms, $taxonomy, $post);

		if(!empty($term)) $join ='';
		return $join;
  }
  /**
  * Get adjacent post in ranked posts.
  * modify where sql statement. Hooked on 'get_previous_post_where' / 'get_next_post_where'
  * @since 2.4.4
  * @param string  $join           The JOIN clause in the SQL.
  * @param bool    $in_same_term   Whether post should be in a same taxonomy term.
  * @param array   $excluded_terms Array of excluded term IDs.
  * @param string  $taxonomy       Taxonomy. Used to identify the term used when `$in_same_term` is true.
  * @param WP_Post $post           WP_Post object.
  */
  public function filter_next_post_where($where, $in_same_term, $excluded_terms, $taxonomy, $post){
    return $this->get_adjacent_post_where($where, $in_same_term, $excluded_terms, $taxonomy, $post, 'next');
  }
  public function filter_prev_post_where($where, $in_same_term, $excluded_terms, $taxonomy, $post){
    return $this->get_adjacent_post_where($where, $in_same_term, $excluded_terms, $taxonomy, $post, 'prev');
  }
  protected function get_adjacent_post_where($where, $in_same_term, $excluded_terms, $taxonomy, $post, $pos){
		if(!$in_same_term) return $where;
		//else check the term.
		if ( ! is_object_in_taxonomy( $post->post_type, $taxonomy ) ) return $where;

		$term =	$this->check_for_ranked_term($excluded_terms, $taxonomy, $post);
		// debug_msg($where, 'where ');
		if(!empty($term)){
			$compare = '>';
			$order = "ASC";
			if('prev'==$pos){
				$compare='<';
				$order = "DESC";
			}
			global $wpdb;
      // debug_msg($wpdb->db_version(), 'version ');
			$adj_id = $wpdb->get_var("SELECT (
				SELECT rankpm.post_id FROM {$wpdb->postmeta} as rankpm LEFT JOIN {$wpdb->posts} AS rankp ON rankp.ID=rankpm.post_id
				  WHERE rankpm.meta_key like '_rpwc2' AND rankpm.meta_value={$term} AND rankp.post_type LIKE '{$post->post_type}' AND rankpm.meta_id{$compare}selectp.meta_id ORDER BY rankpm.meta_id {$order} LIMIT 1 OFFSET 0
    		) AS next_post FROM {$wpdb->postmeta} AS selectp
				  WHERE selectp.meta_id = (SELECT meta_id FROM {$wpdb->postmeta} WHERE post_id={$post->ID} AND meta_key LIKE '_rpwc2' AND meta_value={$term})");

				// debug_msg($wpdb->last_query, $pos.' SQL QUERY:');
			if(!empty($adj_id)){
				$where = " WHERE p.ID= {$adj_id} ";
			}else $where = " WHERE p.ID=0 ";
		}

		return $where;
  }
	/**
	* Check for a term with manual ranking.
	*
	*@since 2.5.0
	* @param array   $excluded_terms Array of excluded term IDs.
  * @param string  $taxonomy       Taxonomy. Used to identify the term used when `$in_same_term` is true.
  * @param WP_Post $post           WP_Post object.
	* @return int term id or null.
	*/
	protected function check_for_ranked_term($excluded_terms, $taxonomy, $post){
		//check the terms of the current post.
    $term_array = wp_get_object_terms( $post->ID, $taxonomy, array( 'fields' => 'ids' ) );
    // Remove any exclusions from the term array to include.
    $term_array = array_diff( $term_array, (array) $excluded_terms );
    $term_array = array_map( 'intval', $term_array );

    if ( ! $term_array || is_wp_error( $term_array ) ) return $where;

		$term = apply_filters('rpwc2_filter_terms_get_adjacent_post', $term_array, $post, $taxonomy);
 		if(is_array($term)) $term = $term[0];

		if(!self::is_ranked($taxonomy, $term, $post->post_type, null)) $term = null;

		return $term;
	}
  /**
  * Function to return the adjacent post.
  *
  *@since 2.8.0
  *@param string $param text_description
  *@return string text_description
  */
  static public function get_adjacent_post($post_id, $term_id, $post_type, $taxonomy){
		if(!self::is_ranked($taxonomy, $term_id, $post_type, null)) return null;
    global $wpdb;
    $sql = $wpdb->prepare("SELECT (
			SELECT rankpm.post_id FROM $wpdb->postmeta as rankpm
			LEFT JOIN $wpdb->posts AS rankp ON rankp.ID=rankpm.post_id
			WHERE rankpm.meta_key like '_rpwc2' AND rankpm.meta_value=%d AND rankp.post_type LIKE %s AND rankpm.meta_id < (SELECT meta_id FROM $wpdb->postmeta WHERE post_id=%d AND meta_key LIKE '_rpwc2' AND meta_value=%d) ORDER BY rankpm.meta_id DESC LIMIT 0,1
		) as prev_post,
    (
			SELECT rankpm.post_id FROM $wpdb->postmeta as rankpm
			LEFT JOIN $wpdb->posts AS rankp ON rankp.ID=rankpm.post_id
			WHERE rankpm.meta_key like '_rpwc2' AND rankpm.meta_value=%d AND rankp.post_type LIKE %s AND rankpm.meta_id > (SELECT meta_id FROM $wpdb->postmeta WHERE post_id=%d AND meta_key LIKE '_rpwc2' AND meta_value=%d) ORDER BY rankpm.meta_id ASC LIMIT 0,1
		) as next_post", $term_id, $post_type, $post_id, $term_id, $term_id, $post_type, $post_id, $term_id);
    return $wpdb->get_row($sql);
  }
}
function get_adjacent_rpwc2_posts($post_id, $term_id, $post_type, $taxonomy){
	return Reorder_Post_Within_Categories_Public::get_adjacent_post($post_id, $term_id, $post_type, $taxonomy);
}
