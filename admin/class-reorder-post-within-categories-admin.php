<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://profiles.wordpress.org/aurovrata/
 * @since      1.0.0
 *
 * @package    Reorder_Post_Within_Categories
 * @subpackage Reorder_Post_Within_Categories/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Reorder_Post_Within_Categories
 * @subpackage Reorder_Post_Within_Categories/admin
 * @author     Aurorata V. <vrata@syllogic.in>
 */
class Reorder_Post_Within_Categories_Admin {

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
	public $adminOptionsName = "deefuse_ReOrderSettingAdminOptions";

	public $old_table_name = "reorder_post_rel";
	public static $settings_option_name = "_rpwc2_settings";

	public $custom_cat = 0;
	public $stop_join = false;
	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->_upgrade_to_v2();//if required.
	}

	/**
	 * Register the stylesheets for the admin area.
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
		$mode = get_option('airplane-mode', 'off');
 		if('on'==$mode){
			wp_enqueue_style('jquery-ui-base', plugin_dir_url( __DIR__ ) . 'assets/jquery-ui.min.css', array(), '1.12.1', 'all');

		}else{
			wp_enqueue_style('jquery-ui-base', '//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.min.css', array(), '1.12.1', 'all');
		}
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/reorder-post-within-categories-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
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
		$mode = get_option('airplane-mode', 'off');
		if('on'==$mode){
			wp_enqueue_script('sortable', plugin_dir_url( __DIR__ ) . 'assets/sortable/Sortable.min.js');
			wp_enqueue_script('sortable-jquery', plugin_dir_url( __DIR__ ) .  'assets/jquery-sortablejs/jquery-sortable.js', array('jquery', 'sortable'));
		}else{
			wp_enqueue_script('sortable', '//cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js');
			wp_enqueue_script('sortable-jquery', '//cdn.jsdelivr.net/npm/jquery-sortablejs@latest/jquery-sortable.js', array('jquery', 'sortable'));
		}
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/reorder-post-within-categories-admin.js', array( 'sortable-jquery', 'jquery-ui-slider'), $this->version, false );
		wp_localize_script($this->plugin_name, 'rpwc2',
		 array(
				 'deefuseNounceCatReOrder' =>  wp_create_nonce('nonce-CatOrderedChange'),
				 'deefuseNounceUserOrdering' =>  wp_create_nonce('nonce-UserOrderingChange'),
         'insertRange'=>__('Chose a rank either below or above your current displayed range where you wish to insert your select items.', 'reorder-post-within-categories')
			 )
		 );

	}
	/**
	* function called by ajax when a category order type is changed.
	* @since 1.0.0
	*/
	public function category_order_change(){
		if (!isset($_POST['deefuseNounceOrder']) || !wp_verify_nonce($_POST['deefuseNounceOrder'], 'nonce-CatOrderedChange')) {
			wp_die('nonce failed, reload your page');
		}

		$settings = get_option(RPWC_OPTIONS, array());
		$settings[$_POST['current_cat']] = $_POST['valueForManualOrder'];
		update_option(RPWC_OPTIONS, $settings);

		wp_die();
	}
	/**
	 * Returns an array of admin options
	 */
	public function get_admin_options(){
		return get_option($this->adminOptionsName, array());;
	}

	/**
	* Update to new process: extract order from old custom table and insert into postmeta table.
	* @since 2.0.0
	*/
   private function _upgrade_to_v2(){
     /** simplified @since 2.1.2 */
		if (function_exists('is_multisite') && is_multisite()) {
			global $wpdb;
			$old_blog = $wpdb->blogid;
			$blogids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
			/** simplified @since 2.1.2 */
			foreach ($blogids as $blog_id) {
				switch_to_blog($blog_id);
        $this->_upgrade();
			}
			switch_to_blog($old_blog);
    }else $this->_upgrade();
  }
	/**
	* Update to new process: extract order from old custom table and insert into postmeta table.
	* @since 2.0.0
	*/
	private function _upgrade(){
		$settings = get_option(self::$settings_option_name, array());
		// debug_msg($settings, 'upgrading...');
		/** @since 2.0.1*/
		$upgrade = false;
		switch(true){
			case empty($settings): //either first install or new upgrade.
				$upgrade = true;
				break;
			case isset($settings['version']) &&  $settings['version']=="2.0.0": //reset order.
				global $wpdb;
				// debug_msg('deleting all ranks');
				$wpdb->delete($wpdb->postmeta, array('meta_key'=>'_rpwc2'), array('%s'));
				$upgrade = true;
				break;
		}
		switch($upgrade){
		 	case false:
				$settings['version']=$this->version;
				if( !isset($settings['upgraded']) ) $settings['upgraded']=false;
	 			break;
			case true: //empty = new instal or old version update.
				//update settings.
				$settings['version']=$this->version;
				$settings['upgraded']=false;
				global $wpdb;
				$table_name = $wpdb->prefix . $this->old_table_name;
				$categories = $wpdb->get_col("SELECT DISTINCT category_id FROM {$table_name}");
        //debug_msg($categories, 'found categories ');
				if(!empty($wpdb->last_error)) debug_msg($wpdb->last_error, 'SQL ERROR: ');
				else{ //update db.
					foreach($categories as $cid){
						$ranking = $wpdb->get_results($wpdb->prepare("select * from {$table_name} where category_id = %d order by id", $cid));
            //debug_msg($wpdb->last_query, 'query old table');
						$values = array();
						foreach($ranking as $idx=>$row){
							$values[] = "($row->post_id, '_rpwc2', $cid)";
						}
						//for each category insert a meta_field for the post in the ranking order.
						$sql = sprintf("insert into $wpdb->postmeta (post_id, meta_key, meta_value) values %s", implode(",", $values));
						$wpdb->query($sql);
            //debug_msg($values, 'stored existing order for cid: '.$cid);
					}
					$settings['upgraded']=true; //upgrade settings.
				}
				break;
		}
		update_option(self::$settings_option_name, $settings);
	}
  /**
  * function called by admn ajax to load more posts.
  *@since 2.0.0
  */
  public function load_posts(){
    if (!isset($_POST['deefuseNounceUserOrdering']) || !wp_verify_nonce($_POST['deefuseNounceUserOrdering'], 'nonce-UserOrderingChange')) {
			wp_die('nonce failed, reload your page');
		}
    $start = 0;
    if(isset($_POST['start'])) $start=$_POST['start'];
    $offset = 20;
    if(isset($_POST['offset'])) $offset=$_POST['offset'];
    if($offset<0) $offset = 20;
    $post_type = '';
    if(isset($_POST['post-type'])) $post_type=$_POST['post-type'];
    $term_id = 0;
    if(isset($_POST['term'])) $term_id=$_POST['term'];
		$reset = false;
		if(isset($_POST['reset'])) $reset=$_POST['reset'];
    $results = array();
    if(!empty($post_type) && $term_id>0){
			/** @since 2.1.0. allow rank reset*/
			if($reset) $this->_unrank_all_posts($term_id, $post_type);
      $results = $this->_get_ranked_posts($post_type, $term_id, $start, $offset);
    }
		wp_send_json_success($results);
    wp_die();
  }
	/**
	* function to get ranked posts details for ajax call.
	*@since 2.0.0
	*/
	private function _get_ranked_posts($post_type, $term_id, $start, $offset){
		$results = array();
		$ranking = $this->_get_order($post_type, $term_id, $start, $offset);
    $posts = get_posts(array(
      'post_type' => $post_type,
      'post__in'=>$ranking,
      'ignore_sticky_posts'=>true,
      'posts_per_page'=>-1
    ));
		$results = array_fill(0, count($ranking), '');
    foreach($posts as $post) {
      $img = get_the_post_thumbnail_url( $post, 'thumbnail' );
			if(!$img) $img = plugin_dir_url(__DIR__).'assets/logo.png';
			$rank = array_search($post->ID, $ranking);
      $results[$rank]=array(
				'id'=>$post->ID,
        'link'=>admin_url('post.php?post='.$post->ID.'&action=edit'),
        'img'=> $img,
        'title'=>apply_filters('reorder_posts_within_category_card_text',get_the_title($post), $post, $term_id)
      );
    }
		return $results;
	}
  /**
  * Ajax called function to save the new order.
  * @since 1.0.0.
  */
	public function save_order(){
		if (!isset($_POST['deefuseNounceUserOrdering']) || !wp_verify_nonce($_POST['deefuseNounceUserOrdering'], 'nonce-UserOrderingChange')) {
				wp_die('nonce failed, reload your page');
		}
		// debug_msg($_POST['order'], 'saving order ');
		$this->_save_order(explode(",", $_POST['order']), $_POST['category'], $_POST['start']);
		wp_die();
	}
	/**
  * Ajax called function to save the new order.
  * @since 1.0.0.
  */
	public function shuffle_order(){
		if (!isset($_POST['deefuseNounceUserOrdering']) || !wp_verify_nonce($_POST['deefuseNounceUserOrdering'], 'nonce-UserOrderingChange')) {
				wp_die('nonce failed, reload your page');
		}
		if(!isset($_POST['items']) || !isset($_POST['start']) || !isset($_POST['end']) || !isset($_POST['category'])){
			wp_die('missing data, try again.');
		}
		$items = $_POST['items'];
    $start = $_POST['start'];
    $end = $_POST['end'];
    $term_id = $_POST['category'];
		$post_type = $_POST['post'];
		$move = $_POST['move'];
		// debug_msg($_POST);
		if(empty($items) || empty($start) ||
			empty($end) || empty($term_id) ||
			empty($post_type) || empty($move)){

			wp_die('missing data, try again.');
		}
		// $items = explode(',',$items);
		$order = $this->_get_order($post_type, $term_id, $start-1, $end-$start+1);
		// debug_msg($order, ($start-1).'->'.($end-$start+1));
		// debug_msg($items, 'items to move ');
		foreach($items as $post_id){
			if(false !== ($idx = array_search($post_id, $order))){
				unset($order[$idx]); //remove from order.
			}
		}
		// debug_msg($order, 'purgesd orers ');

		switch($move){
			case 'up':
				$order = array_merge($items, $order);
				break;
			case 'down':
				$order = array_merge($order, $items);
				break;
		}
		// debug_msg($order, 'new order ');
		$this->_save_order($order, $term_id, $start-1);
		$results = $this->_get_ranked_posts($post_type, $term_id, $_POST['range_start'], $_POST['offset']);
		wp_send_json_success($results);
    wp_die();
	}
	/**
	* function to retrieve the current order of posts.
	* @since 2.0.0.
	* @param string $post_type the post type for which to retrive an order.
	* @param int $term_id the id of the category term for which the order is required.
	* @return array an array of post_id from the postmeta table in ranking order.
	*/
	protected function _get_order($post_type, $term_id, $start=0, $length=null){
		global $wpdb;
		$ranking = $wpdb->get_col($wpdb->prepare("SELECT pm.post_id FROM {$wpdb->postmeta} as pm, {$wpdb->posts} as p
			WHERE pm.meta_key ='_rpwc2'
			AND pm.meta_value=%d
			AND pm.post_id=p.ID
			AND p.post_type=%s
      ORDER BY pm.meta_id", $term_id, $post_type));
			// debug_msg($ranking, $term_id.':'.$start.'->'.$length);
		if(empty($ranking)){ //retrieve the default ranking.
			$table_name = $wpdb->prefix . $this->old_table_name;
			$ranking = $wpdb->get_col($wpdb->prepare("select post_id from {$table_name} where category_id = %d order by id", $term_id));
			if(empty($ranking)){
				$orderby = 'p.post_date';
				if(apply_filters('reorder_posts_within_category_initial_orderby', false, $post_type, $term_id)){
					$orderby = 'p.post_name';
				}
				$order = 'DESC';
				if(apply_filters('reorder_posts_within_category_initial_order', false, $post_type, $term_id)){
					$order = 'ASC';
				}
				$sql = $wpdb->prepare("SELECT p.ID FROM {$wpdb->posts} as p LEFT JOIN {$wpdb->term_relationships} AS tr ON p.ID=tr.object_id
					WHERE  p.post_status='publish'
					AND p.post_type='%s'
					AND tr.term_taxonomy_id=%d
					ORDER BY {$orderby} {$order}", $post_type, $term_id);
				$ranking = $wpdb->get_col($sql);
			}
      // debug_msg($sql);
			$this->_save_order($ranking, $term_id);
		}
		if( empty($length) || $length> sizeof($ranking)) $length=sizeof($ranking);
		return array_splice($ranking, $start, $length);
	}
	/**
	* General function to save a new order,
	* @since 2.0.0
	* @param array $order an array of $post_id in ranked order.
	* @param int $term_id the id of the category term for which the posts need to be ranked.
	*/
	protected function _save_order($order=array(), $term_id=0, $start=0){
		if(empty($order) || 0==$term_id) return false;
		global $wpdb;
		$ranked_rows = $wpdb->get_results($wpdb->prepare("SELECT pm.meta_id, pm.post_id FROM {$wpdb->postmeta} as pm WHERE pm.meta_key ='_rpwc2' AND pm.meta_value=%d", $term_id));

		if (empty($ranked_rows)) {
			foreach ($order as $post_id) {
					$value[] = "($post_id, '_rpwc2', $term_id)";
			}
			$sql = sprintf("INSERT INTO {$wpdb->postmeta} (post_id, meta_key, meta_value) VALUES %s", implode(",", $value));
			$wpdb->query($sql);
		} else {
			// $ranked_id=array();
			/** @since 2.0.0 allow for partial ranking.*/
			$old_start = 0;
			$values = array();
			$end = sizeof($order);
			$last = sizeof($ranked_rows); //the last rows retain the same order.
			// debug_msg($ranked_rows, 'current ranked rows ');
			foreach($ranked_rows as $idx=>$row) {
				if( $idx>=$start && ($idx-$start)<$end){ //replace current order.
					$values[] = "({$row->meta_id}, {$order[$idx-$start]}, '_rpwc2', {$term_id})";
				}
			}
			// debug_msg($values, 'saving rank '.$start.' to '.$end);
			$sql = sprintf("REPLACE INTO {$wpdb->postmeta} VALUES %s", implode(",", $values));
			$wpdb->query($sql);
			if( !empty($wpdb->last_error)){
				debug_msg($wpdb->last_error, "SQL ERROR: ");
				return false;
			}
		}

		return true;
	}
	/**
	* function to remove postmeta for terms not manually ordered.
	* @since 2.0.0
	*/
	private function _unrank_posts_unused_taxonomy($all = false, $post_types=array()){
    $terms_used = array();
		$settings = $this->get_admin_options();
    if($all){
			delete_option($this->adminOptionsName);
    }else{
    	$taxonomy_checked = array();
			foreach($post_types as $post_type){
				if(isset($settings['categories_checked'][$post_type])){
					$taxonomy_checked = array_merge($taxonomy_checked, $settings['categories_checked'][$post_type]);
				}
			}
    	$terms_used = get_terms(array('taxonomy'=>$taxonomy_checked));
    	$terms_used = wp_list_pluck($terms_used, 'term_id');
    }
		global $wpdb;
		$terms_ordered = $wpdb->get_col("SELECT DISTINCT meta_value FROM $wpdb->postmeta WHERE meta_key LIKE '_rpwc2'");
		/** @TODO delete ranking by post type */
		foreach($terms_ordered as $term_id){
			if(empty($terms_used) || !in_array($term_id, $terms_used)){
				$wpdb->delete($wpdb->postmeta, array('meta_key'=>'_rpwc2', 'meta_value'=>$term_id), array('%s','%d'));
			}
		}
	}
	/**
	*Function to delete v1.x custom table.
	*@since 2.0.0
	*/
	private function _delete_custom_table(){
		global $wpdb;
		$table_name = $wpdb->prefix . $this->old_table_name;

		$sqlDropTable = "DROP TABLE IF EXISTS $table_name";
		$wpdb->query($sqlDropTable);
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sqlDropTable);
		$settings = get_option(self::$settings_option_name, array());
		$settings['upgraded']=false; //switchoff table delete button.
		update_option(self::$settings_option_name, $settings);
	}
	/**
	* function to save options.
	* @since 1.0.0.
	*/
	public function save_admin_options(){
		// Si le formulaire a Ã©tÃ© soumis, on rÃ©-enregistre les catÃ©gorie dont on veut trier les Ã©lÃ©ments
		if (!empty($_POST) && isset($_POST['nounceUpdateOptionReorder']) && wp_verify_nonce($_POST['nounceUpdateOptionReorder'], 'updateOptionSettings')) {
			$categories_checked = array();
			if (isset($_POST['selection'])) {
					$categories_checked = $_POST['selection'];
			}
			$settingsOptions['categories_checked'] = $categories_checked;
			update_option($this->adminOptionsName, $settingsOptions);
		}
	}

	/**
	* callback funciton to display the order page.
	* @since 1.0.0
	*/
	public function print_order_page(){
		// On rÃ©cupÃ¨re le VPT sur lequel on travaille
		$page_name = $_GET['page'];
		$cpt_name = substr($page_name, 13, strlen($page_name));
		$post_type = get_post_types(array('name' => $cpt_name), 'objects');
		$post_type_detail  = $post_type[$cpt_name];
		unset($post_type, $page_name, $cpt_name);

		// On charge les prÃ©fÃ©rences
		$settingsOptions = $this->get_admin_options();
		// Si le formulaire a Ã©tÃ© soumis
		if (!empty($_POST) &&
		 check_admin_referer('loadPostInCat', 'nounceLoadPostCat') &&
		 isset($_POST['nounceLoadPostCat']) &&
		 wp_verify_nonce($_POST['nounceLoadPostCat'], 'loadPostInCat')) {

			if (isset($_POST['cat_to_retrive']) && !empty($_POST['cat_to_retrive']) && $_POST['cat_to_retrive'] != null) {
				$cat_to_retrieve_post = $_POST['cat_to_retrive'];
				$taxonomySubmitted = $_POST['taxonomy'];
				$term = get_term($cat_to_retrieve_post);

				// Si il y a une catÃ©gorie
				if (!empty($term)) {
					$ranking = $this->_get_order($post_type_detail->name, $cat_to_retrieve_post, 0, 20);
					$total = $term->count;
					$args = array('post_type' => $post_type_detail->name,
						'post__in'=>$ranking,
						'ignore_sticky_posts'=>true,
						'posts_per_page'=>-1
					);
					$posts_array = get_posts($args);
					foreach($posts_array as $post) $posts[$post->ID]=$post;
				}
			}
		}
		//display partial html.
		include_once plugin_dir_path(__FILE__) . '/partials/reorder-post-within-categories-admin-display.php';
	}

	/**
	 *
	 */
	public function print_settings_page(){
		include_once plugin_dir_path(__FILE__) . '/partials/reorder-post-within-categories-settings-display.php';
	}

	/**
	 * Add an option age link for the administrator only
	 */
	public function add_setting_page(){
		if (function_exists('add_options_page')) {
			add_options_page(__('ReOrder Post within Categories', 'reorder-post-within-categories'), __('ReOrder Post', 'reorder-post-within-categories'), 'manage_options', basename(__FILE__), array(&$this, 'print_settings_page'));
		}
	}
	/**
	 * Show admin pages for sorting posts
	 * (as per settings options of plugin);
	 */
	public function add_order_pages(){
		//On liste toutes les catÃ©gorie dont on veut avoir la main sur le trie
		$settingsOptions = $this->get_admin_options();

		if (!isset($settingsOptions['categories_checked'])) {
				return;
		}
		// Pour chaque post_type, on regarde s'il y a des options de trie associÃ©
		//debug_msg($settingsOptions);

		foreach ($settingsOptions['categories_checked'] as $post_type=>$taxonomies) {
			/**
			*filter to allow other capabilities for managing orders.
			* @since 1.3.0
			**/
			$capability = apply_filters('reorder_post_within_categories_capability', 'manage_categories', $post_type);
			if('manage_categories'!== $capability){ //validate capability.
				$roles = wp_roles();
				$is_valid=false;
				foreach($roles->roles as $role){
						if(in_array($capability, $role['capabilities'])){
								$is_valid=true;
								break;
						}
				}
				if(!$is_valid) $capability = 'manage_categories';
			}
			switch ($post_type) {
				case 'attachment':
					$the_page = add_submenu_page('upload.php', 'Re-order', 'Reorder', $capability, 're-orderPost-'.$post_type, array(&$this,'print_order_page'));
					break;
				case 'post':
					$the_page = add_submenu_page('edit.php', 'Re-order', 'Reorder', $capability, 're-orderPost-'.$post_type, array(&$this,'print_order_page'));
					break;
				default:
					$the_page =  add_submenu_page('edit.php?post_type='.$post_type, 'Re-order', 'Reorder', $capability, 're-orderPost-'.$post_type, array(&$this,'print_order_page'));
					break;
			}
			//enqueue styles on scripts on page specific hook.
			add_action('admin_head-'. $the_page, array($this,'enqueue_styles'));
			add_action('admin_head-'. $the_page, array($this,'enqueue_scripts'));
		}
	}
	/**
	 * Dispplay a link to setting page inside the plugin description
	 */
	public function display_settings_link($links){
		$settings_link = '<a href="options-general.php?page=reorder-posts-within-categories.php">' . __('Settings', 'reorder-post-within-categories') . '</a>';
		array_unshift($links, $settings_link);
			return $links;
	}
	/**
	* display admin notice.
	*@since 1.0
	*/
	public function admin_dashboard_notice(){
		$options = $this->get_admin_options();
		if (empty($options)) {
			include_once plugin_dir_path(__FILE__) . '/partials/reorder-post-within-categories-notice-display.php';
		}
	}
	/**
	 * When a new post is created several actions are required
	 * We need to inspect all associated taxonomies
	 * @param type $post_id
	 */
	public function save_post( $new_status, $old_status, $post){
		$public=array('publish', 'private', 'future');
		// debug_msg($new_status.'->'.$old_status );
		if( in_array($old_status, $public) && !in_array($new_status, $public)){
			if( !in_array($new_status, $public) ) $this->unrank_post($post->ID);
			return; //no actions required.
		}
		$settings = $this->get_admin_options();
		if (empty($settings) || !isset($settings['categories_checked'][$post->post_type])) return;
		$settings = $settings['categories_checked'][$post->post_type];

		//verify post is not a revision
		$post_id = $post->ID;
		// Liste des taxonomies associÃ©e Ã  ce post
		$taxonomies = get_object_taxonomies($post->post_type, 'objects');
		if(empty($taxonomies)) return;
		// for each CPT taxonomy, look at only the hierarchical ones
		$post_ranks = get_post_meta($post_id, '_rpwc2', false);
		foreach ($taxonomies as $taxonomie) {
			if (!in_array($taxonomie->name, $settings)) continue;
			$terms = get_terms($taxonomie->name);
			if(empty($terms) || is_wp_error($terms)) continue;

			$terms_of_the_post = wp_get_post_terms($post_id, $taxonomie->name);
			$term_ids_of_the_post = wp_list_pluck($terms_of_the_post, 'term_id');

			foreach ($terms as $term) {
				if (!in_array($term->term_id, $term_ids_of_the_post)){
					if(in_array($term->term_id, $post_ranks)){
						$this->unrank_post($post_id, $term->term_id);
					}
					continue; //post not in term.
				}
				if(in_array($term->term_id, $post_ranks)) continue; //post already ranked.

				$ranking = $this->_get_order($post->post_type, $term->term_id);
				if(!empty($ranking)){ //post_type is manually ranked.
					//add new rank at the bottom of the order.
					add_post_meta($post_id, '_rpwc2', $term->term_id, false);
					// debug_msg($term->term_id.' ranking '.$post_id);
					/**
					* Filter to rank new post at the top of the manual order.
					* @since 2.0.0.
					* @param boolean $first default false, true will place post first.
					* @param WP_Post $post the current post being published.
					* @param WP_Term $term the current taxonomy term within which the post is to be ranked.
					*/
					if(apply_filters('reorder_post_within_categories_new_post_first', false, $post, $term)){
					 	//add new rank at the top of the order.
						$ranking = unshift_array($ranking, $post_id);
						$this->_save_order($ranking, $term->term_id);
					}
				}
			}
		}
	}
	/**
	 * When a post is deleted we remove all entries from the custom table
	 * @param type $post_id
	 */
	public function unrank_post($post_id, $term_id=null){
		delete_post_meta($post_id, '_rpwc2', $term_id);
	}
	/**
	* Delete all ranks for a given term.
	* @since 2.1.0.
	* @param $term_id term id to unrank posts
	* @param $post_type post type for which to unrank posts.
	* @return boolean false if there was an issue.
	*/
	protected function _unrank_all_posts($term_id, $post_type){
		if(empty($term_id) || empty($post_type)){
			debug_msg('UNABLE to Unrank, not term ID and/or post_type defined');
			return false;
		}
		if(is_array($post_type) ) $post_type = implode("','",$post_type);
		global $wpdb;
		$sql = $wpdb->prepare("DELETE meta FROM {$wpdb->postmeta} as meta JOIN {$wpdb->posts} as post ON post.ID=meta.post_id WHERE meta.meta_key LIKE '_rpwc2' AND meta.meta_value LIKE %s AND post.post_type IN ('{$post_type}')", $term_id);
		$wpdb->query($sql);
		if(!empty($wpdb->last_error)){
			debug_msg($wpdb->last_error, 'SQL ERROR:');
			return false;
		}
		// debug_msg($sql, 'deleted '.$post_type.' term '.$term_id);
		return true;
	}
}
