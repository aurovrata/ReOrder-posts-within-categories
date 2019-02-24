<?php
/*
Plugin Name: ReOrder Post Within Categories
Plugin URI:   https://github.com/aurovrata/ReOrder-posts-within-categories/
Description: Arrange Post and Custom Post Type through drag & drop interface of selected category (or custom taxonomies).
Version: 1.8.1
Author: Aurélien Chappard, Aurovrata Venet
Author URI: https://profiles.wordpress.org/aurovrata/
License: GPLv2
Text Domain: reorder-post-within-categories
Domain Path: /languages
*/

if (!class_exists('ReOrderPostWithinCategory')) {
    class ReOrderPostWithinCategory
    {
        public $adminOptionsName = "deefuse_ReOrderSettingAdminOptions";
        public $orderedCategoriesOptionName = "deefuse_ReOrderOrderedCategoriesOptions";

        public $deefuse_ReOrder_db_version = "1.0";
        public $deefuse_ReOrder_dbOptionVersionName = "deefuse_ReOrder_db_version";
        public $deefuse_ReOrder_tableName = "reorder_post_rel";

        public $custom_cat = 0;
        public $stop_join = false;


        /**
         * Constructor
         */
        public function __construct()
        {
            add_action('init', function(){
                load_plugin_textdomain('reorder-post-within-categories', false,  'reorder-post-within-categories/languages/');
            });


            //hook for new blog on multisite
            add_action('wpmu_new_blog', 'multisite_new_blog', 10, 6);
            // hook for desactivation
            register_deactivation_hook(__FILE__, array(&$this, 'reOrder_uninstall'));
            // hook for activation
            register_activation_hook(__FILE__, array(&$this, 'reOrder_install'));
            // Link to the setting page
            $plugin = plugin_basename(__FILE__);
            add_filter("plugin_action_links_$plugin", array(&$this,'display_settings_link'));

            //hook for notices
            add_action('admin_notices', array(&$this, 'admin_dashboard_notice'));
            //Action qui sauvegardera le paamÃ©trage du plugin
            add_action('init', array(&$this, 'saveOptionPlugin'));
            // Ajout de la page de paramÃ©trage du plugins
            add_action('admin_menu', array(&$this, 'add_setting_page'));

            // Ajout des pages de classement des post pour les post et custom post type concernÃ©s
            add_action('admin_menu', array(&$this, 'add_order_pages'));

            add_action('wp_ajax_cat_ordered_changed', array(&$this, 'cat_orderedChangeTraiment'));
            add_action('wp_ajax_user_ordering', array(&$this, 'user_orderingTraiment'));

            add_action('save_post', array(&$this, 'savePost_callBack'));
            add_action('before_delete_post', array(&$this, 'deletePost_callBack'));
            add_action('trashed_post', array(&$this, 'deletePost_callBack'));

            add_action('deleteUnecessaryEntries', array(&$this, 'deleteUnecessaryEntries_callBack'));

            if ((defined('DOING_AJAX') && DOING_AJAX) || !is_admin()) {
                add_filter('posts_join', array(&$this, 'reOrder_query_join'), 10, 2);
                add_filter('posts_where', array(&$this, 'reOrder_query_where'), 10, 2);
                add_filter('posts_orderby', array(&$this, 'reOrder_query_orderby'), 10, 2);
            }
        }
        public function admin_dashboard_notice()
        {
            $options = $this->getAdminOptions();
            if (empty($options)) {
                ?>
				<div class="updated re_order">
						<p><?php echo sprintf(__('First of all, you need to <a href="%s">save your settings for <em>ReOrder Posts in Categories</em></a>.', 'reorder-post-within-categories'), admin_url('options-general.php?page=reorder-posts-within-categories.php')); ?></p>
				</div>
				<?php
            }
        }
        public function reOrder_query_join($args, $wp_query)
        {
            global $wpdb;

            $table_name = $wpdb->prefix . $this->deefuse_ReOrder_tableName;

            $queriedObj = $wp_query->get_queried_object();

            if (isset($queriedObj->slug) && isset($queriedObj->term_id)) {
                $category_id = $queriedObj->slug;
                $theID = $queriedObj->term_id;
            } else {
                return $args;
            }


            if (!$category_id) {
                $category_id = $this->custom_cat;
            }

            $userOrderOptionSetting = $this->getOrderedCategoriesOptions();
            if (!empty($userOrderOptionSetting[$theID]) && $userOrderOptionSetting[$theID] == "true" && $this->stop_join == false) {
                $args .= " INNER JOIN $table_name ON ".$wpdb->posts.".ID = ".$table_name.".post_id and incl = 1  ";
                //echo $args;
            }

            return $args;
        }
        public function reOrder_query_where($args, $wp_query)
        {
            global $wpdb;

            $table_name = $wpdb->prefix . $this->deefuse_ReOrder_tableName;

            $queriedObj = $wp_query->get_queried_object();

            if (isset($queriedObj->slug) && isset($queriedObj->term_id)) {
                $category_id = $queriedObj->slug;
                $theID = $queriedObj->term_id;
            } else {
                return $args;
            }


            if (!$category_id) {
                $category_id = $this->custom_cat;
            }

            $userOrderOptionSetting = $this->getOrderedCategoriesOptions();
            if (!empty($userOrderOptionSetting[$theID]) && $userOrderOptionSetting[$theID] == "true" && $this->stop_join == false) {
                //$args .= " INNER JOIN $table_name ON ".$wpdb->posts.".ID = ".$table_name.".post_id and incl = 1  ";
                $args .= " AND $table_name".".category_id = '".$theID."'";
                //echo $args;
            }

            return $args;
        }
        public function reOrder_query_orderby($args, $wp_query)
        {
            global $wpdb;

            $table_name = $wpdb->prefix . $this->deefuse_ReOrder_tableName;

            $queriedObj = $wp_query->get_queried_object();

            if (isset($queriedObj->slug) && isset($queriedObj->term_id)) {
                $category_id = $queriedObj->slug;
                $theID = $queriedObj->term_id;
            } else {
                return $args;
            }

            if (!$category_id) {
                $category_id = $this->custom_cat;
            }

            $userOrderOptionSetting = $this->getOrderedCategoriesOptions();
            if (!empty($userOrderOptionSetting[$theID]) && $userOrderOptionSetting[$theID] == "true" && $this->stop_join == false) {
                $args = $table_name.".id ASC";
            }
            return $args;
        }


        /**
         * When a post is deleted we remove all entries from the custom table
         * @param type $post_id
         */
        public function deletePost_callBack($post_id)
        {
            global $wpdb;
            $table_name = $wpdb->prefix . $this->deefuse_ReOrder_tableName;
            $sql = $wpdb->prepare("DELETE FROM $table_name WHERE (post_id =%d)", $post_id);
            $wpdb->query($sql);
        }
        /**
         * When a new post is created several actions are required
         * We need to inspect all associated taxonomies
         * @param type $post_id
         */
        public function savePost_callBack($post_id)
        {
            $orderedSettingOptions = $this->getAdminOptions();
            if (empty($orderedSettingOptions)) {
                return;
            } //order settings not saved yet
            //verify post is not a revision
            if (!wp_is_post_revision($post_id)) {
                global $wpdb;

                $table_name = $wpdb->prefix . $this->deefuse_ReOrder_tableName;
                //let's get the options first

                // Type de post
                $post_type = get_post_type($post_id);
                $post_type = get_post_type_object($post_type);
                //echo "<h1>Enregistrement d'un article ".$post_type->name."</h1>";
                // Liste des taxonomies associÃ©e Ã  ce post
                $taxonomies = get_object_taxonomies($post_type->name, 'objects');

                if (count($taxonomies) > 0 && array_key_exists($post_type->name, $orderedSettingOptions['categories_checked'])) {
                    //echo "<p>On liste maintenant toutes les taxonomies associÃ© au post_type <strong>".$post_type->name.'</strong></p>';
                    //echo '<ul>';
                    $orderedSettingOptions = $orderedSettingOptions['categories_checked'][$post_type->name];
                    // for each CPT taxonomy, look at only the hierarchical ones
                    foreach ($taxonomies as $taxonomie) {
                        if ($taxonomie->hierarchical == 1 && is_array($orderedSettingOptions) && in_array($taxonomie->name, $orderedSettingOptions)) {
                            //echo "<li>".$taxonomie->name."</li>";
                            $terms = get_terms($taxonomie->name);

                            $terms_of_the_post = wp_get_post_terms($post_id, $taxonomie->name);
                            $term_ids_of_the_post = wp_list_pluck($terms_of_the_post, 'term_id');
                            //echo "<pre>";
                            //print_r($terms);
                            //echo "</pre>";
                            if (count($terms) > 0) {
                                //echo "<ul>";
                                foreach ($terms as $term) {
                                    //$terms_of_the_post = wp_get_post_terms( $post_id, $taxonomie->name );
                                    //echo "<li>";
                                    //echo "<p>--" . $term->name . " (" . $term->term_id .")</p>";
                                    //if(in_array($term, $terms_of_the_post))
                                    if (in_array($term->term_id, $term_ids_of_the_post)) {
                                        $trieEnCoursEnDb = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE category_id=%d", $term->term_id));
                                        if ($trieEnCoursEnDb != 0) {
                                            $nbligne = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE post_id=%d AND category_id=%d", $post_id, $term->term_id));
                                            if ($nbligne == 0) {
                                                $wpdb->insert(
                            $table_name,
                            array(
                                'category_id'    => $term->term_id,
                                'post_id'    => $post_id
                            )
                            );
                                            }
                                        }
                                    } else {
                                        $wpdb->query($wpdb->prepare("DELETE FROM $table_name WHERE post_id=%d AND category_id=%d", $post_id, $term->term_id));
                                        // Une fois supprimÃ©, on regarde combien il reste de post en base dont on trie;
                                        //S'il reste moins de deux poste, alors on le supprime
                                        $nbPostRestant =  $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE category_id=%d", $term->term_id));
                                        if ($nbPostRestant < 2) {
                                            $wpdb->query($wpdb->prepare("DELETE FROM $table_name WHERE category_id=%d", $term->term_id));
                                        }
                                        // echo "Il reste encore ".$nbPostRestant." pour ce trie lÃ ";
                                    }
                                    //echo "</li>";
                                }
                                //echo "</ul>";
                            }
                        }
                    }
                    //echo '</ul>';
                }
            }
        }
        /**
         * Launched when the plugin is being activated
         * NOTE: Added multisite compatibility (wordpress.syllogic.in Dec 2015)
         */
        public function reOrder_install($networkwide)
        {
            global $wpdb;
            if (function_exists('is_multisite') && is_multisite()) {
                // check if it is a network activation - if so, run the activation function for each blog id
                if ($networkwide) {
                    $old_blog = $wpdb->blogid;
                    // Get all blog ids
                    $blogids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
                    foreach ($blogids as $blog_id) {
                        switch_to_blog($blog_id);
                        $this->_reOrder_install();
                    }
                    switch_to_blog($old_blog);
                    return;
                }
            }
            $this->_reOrder_install();
        }
        private function _reOrder_install()
        {
            global $wpdb;
            $table_name = $wpdb->prefix . $this->deefuse_ReOrder_tableName;
            if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name) {
                $sqlCreateTable = "CREATE TABLE IF NOT EXISTS $table_name (
							 `id` int(11) NOT NULL AUTO_INCREMENT,
							 `category_id` int(11) NOT NULL,
							 `post_id` int(11) NOT NULL,
							 `incl` tinyint(1) NOT NULL DEFAULT '1',
							 PRIMARY KEY (`id`)
							 ) ;";
                require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
                dbDelta($sqlCreateTable);
            }
            add_option($this->deefuse_ReOrder_dbOptionVersionName, $this->deefuse_ReOrder_db_version);
        }

        public function multisite_new_blog($blog_id, $user_id, $domain, $path, $site_id, $meta)
        {
            global $wpdb;

            if (is_plugin_active_for_network('reorder-post-within-categories/reorder-posts-within-categories.php')) {
                $old_blog = $wpdb->blogid;
                switch_to_blog($blog_id);
                $this->_reOrder_install();
                switch_to_blog($old_blog);
            }
        }

        /**
        * Launched when the plugin is being desactivated
        */
        public function reOrder_uninstall($networkwide){
          global $wpdb;
          if (function_exists('is_multisite') && is_multisite()) {
            // check if it is a network activation - if so, run the activation function for each blog id
            if ($networkwide) {
              $old_blog = $wpdb->blogid;
              // Get all blog ids
              $blogids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
              foreach ($blogids as $blog_id) {
                switch_to_blog($blog_id);
                $this->_reOrder_deactivate();
              }
              switch_to_blog($old_blog);
              return;
            }
          }
          $this->_reOrder_deactivate();
        }
        private function _reOrder_deactivate(){
          $delete_data = apply_filters('reorder_post_within_categories_delete_custom_table', false);
          if($delete_data){
            global $wpdb;
            $table_name = $wpdb->prefix . $this->deefuse_ReOrder_tableName;

            $sqlDropTable = "DROP TABLE IF EXISTS $table_name";
            $wpdb->query($sqlDropTable);
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sqlDropTable);

            delete_option($this->deefuse_ReOrder_dbOptionVersionName);

            $sqlClearOption = "delete from  wp_options where option_name like 'deefuse_ReOrder%'";
            $wpdb->query($sqlClearOption);
            dbDelta($sqlClearOption);
          }
        }

        public function user_orderingTraiment()
        {
            if (!isset($_POST['deefuseNounceUserOrdering']) || !wp_verify_nonce($_POST['deefuseNounceUserOrdering'], 'nonce-UserOrderingChange')) {
                return;
            }

            global $wpdb;
            $order = explode(",", $_POST['order']);
            $category = $_POST['category'];

            $table_name = $wpdb->prefix . $this->deefuse_ReOrder_tableName;
            $total = $wpdb->get_var($wpdb->prepare("select count(*) as total from `$table_name` where category_id = %d", $category));

            // if category has not been sorted as yet
            if ($total == 0) {
                foreach ($order as $post_id) {
                    $value[] = "($category, $post_id)";
                }
                $sql = sprintf("insert into $table_name (category_id,post_id) values %s", implode(",", $value));
                $wpdb->query($sql);
            } else {
                $results = $wpdb->get_results($wpdb->prepare("select * from `$table_name` where category_id = %d order by id", $category));
                foreach ($results as $index => $result_row) {
                    $result_arr[$result_row->post_id] = $result_row;
                }
                $start = 0;
                foreach ($order as $post_id) {
                    $inc_row = $result_arr[$post_id];
                    $incl = 1; //$inc_row->incl; @toto
                    $row = $results[$start];
                    ++$start;
                    $id = $row->id;
                    $sql = $wpdb->prepare("update $table_name set post_id = %d,incl = %d where id = %d", $post_id, $incl, $id);
                    $wpdb->query($sql);
                }
            }



            die();
        }


        public function cat_orderedChangeTraiment()
        {
            if (!isset($_POST['deefuseNounceOrder']) || !wp_verify_nonce($_POST['deefuseNounceOrder'], 'nonce-CatOrderedChange')) {
                return;
            }

            $orderedSettingOptions = $this->getOrderedCategoriesOptions();
            $orderedSettingOptions[$_POST['current_cat']] = $_POST['valueForManualOrder'];
            update_option($this->orderedCategoriesOptionName, $orderedSettingOptions);

            // Toujours laisser le die() final;
            die();
        }


        /**
         * Returns an array of admin options
         */
        public function getAdminOptions()
        {
            $adminOptions = array();
            $settingsOptions = get_option($this->adminOptionsName);
            if (!empty($settingsOptions)) {
                foreach ($settingsOptions as $key => $option) {
                    $adminOptions[$key] = $option;
                }
            }
            update_option($this->adminOptionsName, $adminOptions);
            return $adminOptions;
        }

        public function getOrderedCategoriesOptions()
        {
            $orderedOptions = array();
            $orderedSettingOptions = get_option($this->orderedCategoriesOptionName);
            if (!empty($orderedSettingOptions)) {
                foreach ($orderedSettingOptions as $key => $option) {
                    $orderedOptions[$key] = $option;
                }
            }
            update_option($this->orderedCategoriesOptionName, $orderedOptions);
            return $orderedOptions;
        }

        /**
         * Show admin pages for sorting posts
         * (as per settings options of plugin);
         */
        public function add_order_pages(){
          //On liste toutes les catÃ©gorie dont on veut avoir la main sur le trie
          $settingsOptions = $this->getAdminOptions();

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
                $the_page = add_submenu_page('upload.php', 'Re-order', 'Reorder', $capability, 're-orderPost-'.$post_type, array(&$this,'printOrderPage'));
                break;
              case 'post':
                $the_page = add_submenu_page('edit.php', 'Re-order', 'Reorder', $capability, 're-orderPost-'.$post_type, array(&$this,'printOrderPage'));
                break;
              default:
                $the_page =  add_submenu_page('edit.php?post_type='.$post_type, 'Re-order', 'Reorder', $capability, 're-orderPost-'.$post_type, array(&$this,'printOrderPage'));
                break;
              }
              add_action('admin_head-'. $the_page, array(&$this,'myplugin_admin_header'));
            }
        }
        public function myplugin_admin_header()
        {
            wp_enqueue_style("reOrderDeefuse", plugins_url('style.css', __FILE__));
            wp_enqueue_script('deefusereorderAjax', plugin_dir_url(__FILE__).'js/reorderAjax.js', array('jquery'));
            wp_enqueue_script('jquery-ui-sortable', '/wp-includes/js/jquery/ui/jquery.ui.sortable.min.js', array('jquery-ui-core', 'jquery-ui-mouse'), '1.8.20', 1);
            wp_localize_script('deefusereorderAjax', 'deefusereorder_vars', array(
           'deefuseNounceCatReOrder' =>  wp_create_nonce('nonce-CatOrderedChange'),
           'deefuseNounceUserOrdering' =>  wp_create_nonce('nonce-UserOrderingChange')
       ));
        }
        public function deleteUnecessaryEntries_callBack()
        {
            //Pour chaque catÃ©gorie non cochÃ©e, on efface toutes les entrÃ©es en base qui ont
            // comme categoy_id les term->id des catÃ©gory non cochÃ©e...
            $post_types = get_post_types(array( 'show_in_nav_menus' => true,'public'=>true, 'show_ui'=>true, 'hierarchical' => false ), 'object');
            $categories_checked = $this->getAdminOptions();
            $categories_checked = $categories_checked['categories_checked'];

            $taxoPostToDelete = array();
            if ($post_types) :
        foreach ($post_types as $post_type) {
            $taxonomies = get_object_taxonomies($post_type->name, 'objects');
            if (count($taxonomies) > 0) {
                foreach ($taxonomies as $taxonomie) {
                    if ($taxonomie->hierarchical == 1) {
                        if (isset($categories_checked[$post_type->name])) {
                            if (!in_array($taxonomie->name, $categories_checked[$post_type->name])) {
                                $taxoPostToDelete[] = $taxonomie->name;
                            }
                        } else {
                            $taxoPostToDelete[] = $taxonomie->name;
                        }
                    }
                }
            }
        }
            endif;

            $cat_to_delete_in_db = array();
            $listTerms = get_terms($taxoPostToDelete);
            foreach ($listTerms as $term) {
                $cat_to_delete_in_db[] = $term->term_id;
            }

            $nbCatToDelete = count($cat_to_delete_in_db);

            global $wpdb;
            $table_name = $wpdb->prefix . $this->deefuse_ReOrder_tableName;
            if ($nbCatToDelete > 0) {
                $sql = "DELETE FROM $table_name WHERE (";

                for ($d = 0; $d < $nbCatToDelete ; $d++) {
                    if ($d > 0) {
                        $sql .= "OR";
                    }
                    $sql .= sprintf(" (category_id = %d) ", $cat_to_delete_in_db[$d]);
                }

                $sql.= ")";
                $wpdb->query($sql);
            }

            $nbligne = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
            if ($nbligne == 0) {
                $sql = "ALTER TABLE $table_name AUTO_INCREMENT =1";
                $wpdb->query($sql);
            }
        }
        public function saveOptionPlugin()
        {
            // Si le formulaire a Ã©tÃ© soumis, on rÃ©-enregistre les catÃ©gorie dont on veut trier les Ã©lÃ©ments
            if (!empty($_POST) && isset($_POST['nounceUpdateOptionReorder']) && wp_verify_nonce($_POST['nounceUpdateOptionReorder'], 'updateOptionSettings')) {
                if (isset($_POST['selection'])) {
                    $categories_checked = $_POST['selection'];
                } else {
                    $categories_checked = array();
                }
                $settingsOptions['categories_checked'] = $categories_checked;
                update_option($this->adminOptionsName, $settingsOptions);
            }
        }


        public function printOrderPage()
        {
            // On rÃ©cupÃ¨re le VPT sur lequel on travaille
            $page_name = $_GET['page'];
            $cpt_name = substr($page_name, 13, strlen($page_name));
            $post_type = get_post_types(array('name' => $cpt_name), 'objects');
            $post_type_detail  = $post_type[$cpt_name];
            unset($post_type, $page_name, $cpt_name);

            // On charge les prÃ©fÃ©rences
            $settingsOptions = $this->getAdminOptions();

            // Si le formulaire a Ã©tÃ© soumis
            if (!empty($_POST) && check_admin_referer('loadPostInCat', 'nounceLoadPostCat') && isset($_POST['nounceLoadPostCat']) && wp_verify_nonce($_POST['nounceLoadPostCat'], 'loadPostInCat')) {
                if (isset($_POST['cat_to_retrive']) && !empty($_POST['cat_to_retrive']) && $_POST['cat_to_retrive'] != null) {
                    $cat_to_retrieve_post = $_POST['cat_to_retrive'];
                    $taxonomySubmitted = $_POST['taxonomy'];

                    // Si il y a une catÃ©gorie
                    if ($cat_to_retrieve_post > 0) {
                        global $wpdb;

                        // On sÃ©lectionne les posts trie dans notre table pour la catÃ©gorie concernÃ©.
                        $table_name = $wpdb->prefix . $this->deefuse_ReOrder_tableName;
                        $sql = $wpdb->prepare("select * from $table_name where category_id = '%d' order by id", $cat_to_retrieve_post);
                        $order_result = $wpdb->get_results($sql);
                        $nbResult = count($order_result);

                        for ($k =0 ;$k < $nbResult; ++$k) {
                            $order_result_incl[$order_result[$k]->post_id] = $order_result[$k]->incl;
                        }

                        // arguments pour la requete des post de la catÃ©gory $taxonomySubmitted classÃ© dans la taxonomy d'id $category;
                        $args = array(
                                    'tax_query' => array(
                                                    array('taxonomy' => $taxonomySubmitted, 'operator' => 'IN', 'field' => 'id', 'terms' => $cat_to_retrieve_post)
                                                ),
                                    'posts_per_page'            => -1,
                                    'post_type'       => $post_type_detail->name,
                                    'orderby'            => 'title',
                                    'post_status'     => 'publish',
                                    'order' => 'ASC'
                                );

                        $args = apply_filters('reorder_post_within_category_query_args', $args);
                        $this->stop_join = true;
                        $this->custom_cat = $cat_to_retrieve_post;
                        $query = new WP_Query($args);
                        $this->stop_join = false;
                        $this->custom_cat = 0;
                        $posts_array = $query->posts;

                        // CrÃ©ation d'un tableau dont les clÃ© sont les ID des posts et les valeur les posts eux-mÃªme
                        $temp_order = array();
                        for ($j = 0; $j < count($posts_array); ++$j) {
                            $temp_order[$posts_array[$j]->ID] = $posts_array[$j];
                        }
                    }
                }
            } ?>
	    <div class="wrap">
	    	<div class="icon32 icon32-posts-<?php echo $post_type_detail->name; ?>" id="icon-edit"><br></div>
		<h2><?php echo sprintf(__('Sort items such as "%s"', 'reorder-post-within-categories'), $post_type_detail->labels->menu_name); ?></h2>
		<p>
		    <?php echo sprintf(__('Select a category to sort the items of type <b>%s</b>.', 'reorder-post-within-categories'), $post_type_detail->labels->name); ?>
		</p>

		<form method="post" id="chooseTaxomieForm">
		<?php
            wp_nonce_field('loadPostInCat', 'nounceLoadPostCat');
            $listCategories = $settingsOptions['categories_checked'][$post_type_detail->name];
            $taxonomies= '';
            $taxonomy= '';
            $term_selected = '';
            if (count($listCategories) > 0) {
                echo '<select id="selectCatToRetrieve" name="cat_to_retrive">';
                echo '<option value="null" disabled="disabled" selected="selected">Select</option>';
                $catDisabled = false;
                foreach ($listCategories as $categorie) {
                    $taxonomies = get_taxonomies(array('name'=> $categorie), 'object');
                    $taxonomy = $taxonomies[$categorie];

                    // On liste maintenant les terms disponibles pour la taxonomie concernÃ©e
                    /** @since 1.5.0 allow term query to be filtered.*/
                    $term_query = array('taxonomy'=>$taxonomy->name);
                    $term_query = apply_filters('reorder_post_within_category_taxonomy_term_query_args', $term_query, $taxonomy->name);
                    $list_terms = get_terms($term_query);
                    if (count($list_terms) > 0) {
                        echo '<optgroup id="'.$taxonomy->name.'" label="'.$taxonomy->labels->name.'">';
                        foreach ($list_terms as $term) {
                            $selected = '';
                            if (isset($cat_to_retrieve_post) && ($cat_to_retrieve_post == $term->term_id)) {
                                $selected = ' selected = "selected"';
                                $term_selected = $term->name;
                            }
                            $disabled = '';
                            if ($term->count < 2) {
                                $disabled = ' disabled = "disabled"';
                                $catDisabled = true;
                            }
                            echo '<option' . $selected . $disabled.' value="'.$term->term_id.'">' . $term->name . '</option>';
                        }
                        echo '</optgroup>';
                    }
                }
                echo '</select>';
                if ($catDisabled) {
                    echo '<br/><span class="description">' . __('Greyed-out categories contain too few posts and aren’t available for sorting.', "reorder-post-within-categories") .'</span>';
                }

                $valueTaxonomyField = (isset($taxonomySubmitted) ? $taxonomySubmitted : '');
                echo '<input type="hidden" id="taxonomyHiddenField" name="taxonomy" value="'.$valueTaxonomyField.'"/>';
            } ?>
		</form>
		<form id="form_result" method="post">
		<?php
            if (isset($posts_array)) {
                echo '<div id="result">';
                echo '<div id="sorter_box">';
                echo '<h3>' . __('Use the manual sorting for this category?', 'reorder-post-within-categories') .'</h3>';
                echo '<div id="catOrderedRadioBox">';

                // on regarde si un des radio est cochÃ©
                $checkedRadio1 = '';
                $checkedRadio2 = ' checked = "checked"';
                $orderedSettingOptions = $this->getOrderedCategoriesOptions();
                if (isset($orderedSettingOptions[$cat_to_retrieve_post]) && $orderedSettingOptions[$cat_to_retrieve_post] == 'true') {
                    $checkedRadio1 = $checkedRadio2;
                    $checkedRadio2 = '';
                }

                echo '<label for="yes"><input type="radio"'.$checkedRadio1.' class="option_order" id="yes" value="true" name="useForThisCat"/> <span>'.__('Yes', 'reorder-post-within-categories').'</span></label><br/>';
				// translators: Opposite of Yes
                echo '<label for="no"><input type="radio"'.$checkedRadio2.' class="option_order" id="no" value="false" name="useForThisCat"/> <span>'.__('No', 'reorder-post-within-categories').'</span></label>';
                echo '<input type="hidden" name="termID" id="termIDCat" value="'.$cat_to_retrieve_post.'">';
                echo '<span class="spinner" id="spinnerAjaxRadio"></span>';
                echo '</div>';

                echo '<h3 class="floatLeft">' . sprintf(__('List of "%s" posts, classified as "%s":', 'reorder-post-within-categories'), $post_type_detail->labels->name, $term_selected) . '</h3>';
                echo '<span id="spinnerAjaxUserOrdering" class="spinner"></span><div class="clearBoth"></div>';
                echo '<ul id="sortable-list" class="order-list" rel ="'.$cat_to_retrieve_post.'">';

                // On liste les posts du tableau $posts_array pour le trie
                for ($i = 0; $i < count($order_result); ++$i) {
                    $post_id = $order_result[$i]->post_id;
                    $post = $temp_order[$post_id];
                    unset($temp_order[$post_id]);
                    $od = $order_result_incl[$post->ID];

                    echo '<li id="'.$post->ID.'">';
                    echo '<span class="title"><a href="'.admin_url('post.php?post='.$post_id.'&action=edit').'">'.$post->post_title.'</a></span>';
                    echo '</li>';
                }

                // On liste maintenant les posts qu'il reste et qui ne sont pas encore dans notre table
                foreach ($temp_order as $temp_order_id => $temp_order_post) {
                    $post_id = $temp_order_id;
                    $post = $temp_order_post;

                    echo '<li id="'.$post->ID.'">';
                    echo '<span class="title"><a href="'.admin_url('post.php?post='.$post_id.'&action=edit').'">'.$post->post_title.'</a></span>';
                    echo '</li>';
                }

                echo "</ul>";
                echo '</div>';
                echo '</div>';
            } ?>
		</form>
		<div id="debug">

		</div>
	    </div>
	    <?php
        }

        /**
         *
         */
        public function printAdminPage()
        {
            if (!empty($_POST) && check_admin_referer('updateOptionSettings', 'nounceUpdateOptionReorder') && wp_verify_nonce($_POST['nounceUpdateOptionReorder'], 'updateOptionSettings')) {
                do_action("deleteUnecessaryEntries"); ?>
		<div class="updated"><p><strong><?php _e("Options saved.", "reorder-post-within-categories"); ?></strong> <?php _e("A sub-menu is now placed under each post type menu in your dashboard to access the sorting page.", "reorder-post-within-categories"); ?></p></div>
		<?php
            }
            $settingsOptions = $this->getAdminOptions(); ?>
	    <div class="wrap">
		<div class="icon32" id="icon-options-general"><br/></div>
		<h2><?php _e('Re-Order Posts within category', 'reorder-post-within-categories'); ?></h2>
		<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
		    <?php wp_nonce_field('updateOptionSettings', 'nounceUpdateOptionReorder'); ?>
		    <p><?php _e("Select the categories you want to manually sort the items. Once you have checked and confirmed this information, a sub-menu will appear under each post type menu.", "reorder-post-within-categories"); ?></p>
		    <h3><?php _e("Post Types available:", "reorder-post-within-categories"); ?></h3>
		    <?php
            // On liste tout les post_types
            //$post_types = get_post_types( array( 'show_in_nav_menus' => true,'public'=>true, 'show_ui'=>true, 'hierarchical' => false ), 'object' );
      /**
      * improve the post selection, select post with taxobnomies only
      * @since 1.2.2
      */
      $args = array(
        'show_ui' => true,
        // '_builtin' => false
      );
            $post_types = get_post_types($args, 'object');
            if ($post_types) :
                // Pour chaque post_type, on regarde s'il y a des taxonomies associÃ©es
                foreach ($post_types as $post_type) {
                    $taxonomies = get_object_taxonomies($post_type->name, 'objects');
                    if (empty($taxonomies)) {
                        continue;
                    } //no taxonomies to order post in terms.
                    else {
                        $taxonomy_ui = false;
                        foreach ($taxonomies as $taxonomy) {
                            if ($taxonomy->show_ui) {
                                $taxonomy_ui = true;
                            }
                        }
                        if (!$taxonomy_ui) {
                            continue;
                        } //no taxonomies to oder post in terms.
                    }

                    echo "<strong>" . $post_type->labels->menu_name . "</strong>";

                    // Pour chaque taxonomie associÃ© au CPT, on ne liste que celles qui ont la propriÃ©tÃ© hierarchical Ã©gale Ã  1 (ie comme les catÃ©gorie)
                    foreach ($taxonomies as $taxonomie) {
                        if (!$taxonomie->show_ui) {
                            continue;
                        }
                        if ($taxonomie->hierarchical == 1 || apply_filters('reorder_post_within_categories_and_tags', false)) {
                            $ischecked = '';
                            if (isset($settingsOptions['categories_checked'][$post_type->name])) {
                                if (in_array($taxonomie->name, $settingsOptions['categories_checked'][$post_type->name])) {
                                    $ischecked = ' checked = "checked"';
                                }
                            }
                            echo '<p>&nbsp;&nbsp;<label><input type="checkbox"'.$ischecked.' value="'.$taxonomie->name.'" name="selection['.$post_type->name.'][]"> '. $taxonomie->labels->name .'</label></p>';
                        }
                    }
                }
            echo '<p class="submit"><input id="submit" class="button button-primary" type="submit" value="'.__('Allow sorting for selected categories', 'reorder-post-within-categories').'" name="submit"/>';
            endif; ?>
		</form>
	    </div>
	    <?php
        }

        /**
         * Add an option age link for the administrator only
         */
        public function add_setting_page()
        {
            if (function_exists('add_options_page')) {
                add_options_page(__('ReOrder Post within Categories', 'reorder-post-within-categories'), __('ReOrder Post', 'reorder-post-within-categories'), 'manage_options', basename(__FILE__), array(&$this, 'printAdminPage'));
            }
        }

        /**
         * Dispplay a link to setting page inside the plugin description
         */
        public function display_settings_link($links)
        {
            $settings_link = '<a href="options-general.php?page=reorder-posts-within-categories.php">' . __('Settings', 'reorder-post-within-categories') . '</a>';
            array_unshift($links, $settings_link);
            return $links;
        }
    }


    /* Instantiate the plugin */
    $ReOrderPostWithinCategory_instance = new ReOrderPostWithinCategory();
}
