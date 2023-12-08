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
	/**
	 * Options used to save the settings page, which taxonomy/post_type to be ordered.
	 */
	public $admin_options_slug = 'deefuse_ReOrderSettingAdminOptions';

	public $old_table_name = 'reorder_post_rel';
	/** NB @since 2.9.0 flag terms with v1.x rankings */
	private $old_ranking_exists = false;
	/**
	 * Save plugin settings, to keep track of ugrades.
	 *
	 * @since 2.0.0
	 */
	public static $settings_option_name = '_rpwc2_settings';
	public static $settings             = null;

	public $custom_cat = 0;
	public $stop_join  = false;
	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	// public function __construct( string $plugin_name, string $version ) { //php 8
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		// load settings.
		self::$settings = get_option( self::$settings_option_name, array() );

		$this->_upgrade();
		// $this->_upgrade_to_v2();//if required.
		$this->upgrade_options();
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
		$mode = get_option( 'airplane-mode', 'off' );
		if ( 'on' == $mode ) {
			wp_enqueue_style( 'jquery-ui-base', plugin_dir_url( __DIR__ ) . 'assets/jquery-ui.min.css', array(), '1.12.1', 'all' );

		} else {
			wp_enqueue_style( 'jquery-ui-base', '//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.min.css', array(), '1.12.1', 'all' );
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
		$mode = get_option( 'airplane-mode', 'off' );
		if ( 'on' == $mode ) {
			wp_enqueue_script( 'sortablejs-plugin', plugin_dir_url( __DIR__ ) . 'assets/sortable/Sortable.min.js' );
		} else {
			wp_enqueue_script( 'sortablejs-plugin', '//cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js' );
		}
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/reorder-post-within-categories-admin.js', array( 'sortablejs-plugin', 'jquery-ui-slider' ), $this->version, false );
		wp_localize_script(
			$this->plugin_name,
			'rpwc2',
			array(
				'deefuseNounceCatReOrder'   => wp_create_nonce( 'nonce-CatOrderedChange' ),
				'deefuseNounceUserOrdering' => wp_create_nonce( 'nonce-UserOrderingChange' ),
				'insertRange'               => __( 'Chose a rank either below or above your current displayed range where you wish to insert your select items.', 'reorder-post-within-categories' ),
				'noselection'               => __( 'Please select the posts from the list below to move.', 'reorder-post-within-categories' ),
			)
		);

	}
	/**
	 * function called by ajax when a category order type is changed.
	 * hooked on 'wp_ajax_cat_ordered_changed'.
	 *
	 * @since 1.0.0
	 */
	public function category_order_change() {
		if ( ! isset( $_POST['deefuseNounceOrder'] ) || ! wp_verify_nonce( $_POST['deefuseNounceOrder'], 'nonce-CatOrderedChange' ) ) {
			wp_die( 'nonce failed, reload your page' );
		}
		/** NB wpg_debug($_POST, 'ajax save rank '); */
		$key    = $_POST['current_cat'];
		$option = array();
		if ( isset( $_POST['post_type'] ) ) {
			$option[ $_POST['current_cat'] ] = array(
				'order'    => ( 'true' == $_POST['valueForManualOrder'] ) ? 1 : 0,
				'override' => ( 'true' == $_POST['override'] ) ? 1 : 0, /** NB @since 2.6.0 override orderby */
			);
			$key                             = $_POST['post_type'];
		}
		$settings = get_option( RPWC_OPTIONS_2, array() );
		// unset($settings[$key]);
		if ( isset( $settings[ $key ] ) && is_array( $settings[ $key ] ) ) {
			$option = array_replace( $settings[ $key ], $option );
		}
		$settings[ $key ] = $option;
		update_option( RPWC_OPTIONS_2, $settings );

		wp_die();
	}
	/**
	 * Returns an array of admin options
	 */
	public function get_admin_options() {
		return get_option( $this->admin_options_slug, array() );
	}
	/**
	 * Upgrade plugin options.
	 *
	 * @since 2.6.0
	 * @param string $param text_description
	 * @return string text_description
	 */
	private function upgrade_options() {
		// wpg_debug(self::$settings);
		if ( ! isset( self::$settings['options'] ) ) {
			self::$settings['options'] = $this->version;
			update_option( self::$settings_option_name, self::$settings );

			$old_options = get_option( RPWC_OPTIONS, array() );
			$new_options = array();
			foreach ( $old_options as $key => $item ) {
				if ( is_array( $item ) ) { // updated options.
					$new_options[ $key ] = array();
					foreach ( $item as $term => $flag ) {
						$new_options[ $key ][ $term ] = array(
							'order'    => ( 'true' == $flag ) ? 1 : 0,
							'override' => 1,
						);
					}
				} else { // v1.x options.
					$admin_options = $this->get_admin_options();
					foreach ( $admin_options['categories_checked'] as $pt => $taxonomies ) {
						foreach ( $taxonomies as $taxonomy ) {
							$term = get_term_by( 'id', $key, $taxonomy );
							if ( ! empty( $term ) ) {
								if ( is_array( $term ) ) {
									$term = $term[0];
								}
								if ( ! isset( $new_options[ $pt ] ) ) {
									$new_options[ $pt ] = array();
								}
								$new_options[ $pt ][ $key ] = array(
									'order'    => ( 'true' == $item ) ? 1 : 0,
									'override' => 1,
								);
							}
						}
					}
				}
			}
			// save the new options;
			update_option( RPWC_OPTIONS_2, $new_options );
		}
	}
	/**
	 * Update to new process: extract order from old custom table and insert into postmeta table.
	 *
	 * @since 2.0.0
	 */
	private function _upgrade() {
		// self::$settings = get_option(self::$settings_option_name, array());
		// wpg_debug($settings, 'upgrading...');
		/** NB @since 2.0.1*/
		$upgrade = false;
		switch ( true ) {
			case isset( self::$settings['version'] ) && self::$settings['version'] == $this->version:
				return; // no need to get any further.
			case empty( self::$settings ): // either first install or new upgrade.
				$upgrade = true;
				break;
			case isset( self::$settings['version'] ) && self::$settings['version'] == '2.0.0': // reset order.
				global $wpdb;
				// wpg_debug('deleting all ranks');
				$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => '_rpwc2' ), array( '%s' ) );
				$upgrade = true;
				break;
		}
		switch ( $upgrade ) {
			case false:
				self::$settings['version'] = $this->version;
				if ( ! isset( self::$settings['upgraded'] ) ) {
					self::$settings['upgraded'] = false;
				}
				break;
			case true: // empty = new instal or old version update.
				// update settings.
				self::$settings['version']  = $this->version;
				self::$settings['upgraded'] = false;
				global $wpdb;
				$table_name = $wpdb->prefix . $this->old_table_name;
				$categories = array();
				if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) == $table_name ) {
					self::$settings['upgraded'] = true; // upgrade settings.
					$categories                 = $wpdb->get_col( "SELECT DISTINCT category_id FROM {$table_name}" );
				}
				// wpg_debug($categories, 'found categories ');
				if ( ! empty( $wpdb->last_error ) ) {
					wpg_debug( $wpdb->last_error, 'SQL ERROR: ' );
				} else { // update db.
					foreach ( $categories as $cid ) {
						$ranking = $wpdb->get_results( $wpdb->prepare( "select * from {$table_name} where category_id = %d order by id", $cid ) );
						// wpg_debug($wpdb->last_query, 'query old table');
						$values = array();
						foreach ( $ranking as $idx => $row ) {
							$values[] = "($row->post_id, '_rpwc2', $cid)";
						}
						// for each category insert a meta_field for the post in the ranking order.
						$sql = sprintf( "insert into $wpdb->postmeta (post_id, meta_key, meta_value) values %s", implode( ',', $values ) );
						$wpdb->query( $sql );
						// wpg_debug($values, 'stored existing order for cid: '.$cid);
					}
				}
				break;
		}
		update_option( self::$settings_option_name, self::$settings );
	}
	/**
	 * function called by admn ajax to load more posts.
	 *
	 * @since 2.0.0
	 */
	public function load_posts() {
		wpg_debug( $_POST, 'posted ' );
		if ( ! isset( $_POST['deefuseNounceUserOrdering'] ) || ! wp_verify_nonce( $_POST['deefuseNounceUserOrdering'], 'nonce-UserOrderingChange' ) ) {
			wp_die( 'nonce failed, reload your page' );
		}
		$start  = 0;
		$posted = wp_unslash( $_POST );
		if ( isset( $posted['start'] ) ) {
			$start = $posted['start'];
		}
		$offset = 20;
		if ( isset( $posted['offset'] ) ) {
			$offset = $posted['offset'];
		}
		if ( $offset < 0 ) {
			$offset = 20;
		}
		$post_type = '';
		if ( isset( $posted['post-type'] ) ) {
			$post_type = $posted['post-type'];
		}
		$term_id = 0;
		if ( isset( $posted['term'] ) ) {
			$term_id = $posted['term'];
		}
		$reset = false;
		if ( isset( $posted['reset'] ) ) {
			$reset = $posted['reset'];
		}
		$results = array();

		if ( ! empty( $post_type ) && $term_id > 0 ) {
			/** NB @since 2.1.0. allow rank reset*/
			if ( $reset ) {
				$this->_unrank_all_posts( $term_id, $post_type );
			}
			$results = $this->_get_ranked_posts( $post_type, $term_id, $start, $offset );
		}
		wp_send_json_success( $results );
		wp_die();
	}
	/**
	 * get thumbnail image for dashboard post reorder list.
	 *
	 * @since 2.13.0
	 */
	// public static function get_thumbnail_url(\WP_Post $post, $size = 'thumbnail'): string php 8
	public static function get_thumbnail_url( $post, $size = 'thumbnail' ) {
		$img = get_the_post_thumbnail_url( $post, $size );
		// support attachments with/without featured images
		if ( ! $img && $post->post_type === 'attachment' ) {
			$img = wp_get_attachment_url( $post->ID, $size );
		}
		if ( ! $img ) {
			$img = plugin_dir_url( __DIR__ ) . 'assets/logo.png';
		}
		return $img;
	}
	/**
	 * function to get ranked posts details for ajax call.
	 *
	 * @since 2.0.0
	 */
	// private function _get_ranked_posts(string $post_type, int $term_id, int $start, int $offset){ php 8
	private function _get_ranked_posts( $post_type, $term_id, $start, $offset ) {
		$results = array();
		$ranking = $this->_get_order( $post_type, $term_id, $start, $offset );
		$posts   = get_posts(
			array(
				'post_status'         => 'any',
				'post_type'           => $post_type,
				'post__in'            => $ranking,
				'ignore_sticky_posts' => false,
				'posts_per_page'      => -1,
			)
		);
		/** NB @since 2.14.1 WPML  */
		$results = array();// array_fill(0, count($ranking), '');
		foreach ( $posts as $post ) {
			$img  = self::get_thumbnail_url( $post );
			$rank = array_search( $post->ID, $ranking );
			if ( $rank === false ) {
				continue;
			}
			$results[ $rank ] = array(
				'id'     => $post->ID,
				'link'   => admin_url( 'post.php?post=' . $post->ID . '&action=edit' ),
				'img'    => $img,
				'status' => $post->post_status,
				'title'  => apply_filters( 'reorder_posts_within_category_card_text', get_the_title( $post ), $post, $term_id ),
			);
		}
		return $results;
	}
	/**
	 * Ajax 'user_ordering' called function to save the new order.
	 *
	 * @since 1.0.0.
	 */
	public function save_order() {
		if ( ! isset( $_POST['deefuseNounceUserOrdering'] ) || ! wp_verify_nonce( $_POST['deefuseNounceUserOrdering'], 'nonce-UserOrderingChange' ) ) {
				wp_die( 'nonce failed, reload your page' );
		}
		$post_type = $_POST['post_type'];
		// wpg_debug($_POST['order'], 'saving order ');
		$this->_save_order( $post_type, explode( ',', $_POST['order'] ), $_POST['category'], $_POST['start'] );

		wp_die();
	}
	/**
	 * Ajax 'user_shuffle'  called function to save the new order.
	 *
	 * @since 1.0.0.
	 */
	public function shuffle_order() {
		if ( ! isset( $_POST['deefuseNounceUserOrdering'] ) || ! wp_verify_nonce( $_POST['deefuseNounceUserOrdering'], 'nonce-UserOrderingChange' ) ) {
				wp_die( 'nonce failed, reload your page' );
		}
		if ( ! isset( $_POST['items'] ) || ! isset( $_POST['start'] ) || ! isset( $_POST['end'] ) || ! isset( $_POST['category'] ) ) {
			wp_die( 'missing data, try again.' );
		}
		$items     = $_POST['items'];
		$start     = $_POST['start'];
		$end       = $_POST['end'];
		$term_id   = $_POST['category'];
		$post_type = $_POST['post'];
		$move      = $_POST['move'];
		// wpg_debug($_POST);
		if ( empty( $items ) || empty( $start ) ||
			empty( $end ) || empty( $term_id ) ||
			empty( $post_type ) || empty( $move ) ) {

			wp_die( 'missing data, try again.' );
		}
		// $items = explode(',',$items);
		$order = $this->_get_order( $post_type, $term_id, $start - 1, $end - $start + 1 );

		foreach ( $items as $post_id ) {
			if ( false !== ( $idx = array_search( $post_id, $order ) ) ) {
				unset( $order[ $idx ] ); // remove from order.
			}
		}
		// wpg_debug($order, 'purgesd orers ');

		switch ( $move ) {
			case 'up':
				$order = array_merge( $items, $order );
				break;
			case 'down':
				$order = array_merge( $order, $items );
				break;
		}
		// wpg_debug($order, 'new order ');
		$this->_save_order( $post_type, $order, $term_id, $start - 1 );
		$results = $this->_get_ranked_posts( $post_type, $term_id, $_POST['range_start'], $_POST['offset'] );
		wp_send_json_success( $results );
		wp_die();
	}

	/**
	 * allow for various post status to be filtered
	 *
	 * @since 2.6.1
	 * @param string $post_type
	 * @param integer $term_id
	 * @return string
	 */
	// protected function _get_status(string $post_type, $term_id) { php 8
	protected function _get_status( $post_type, $term_id ) {
		$default_status = array( 'publish', 'private', 'future' );
		$status         = apply_filters(
			'rpwc2_initial_rank_posts_status',
			$default_status,
			$post_type,
			$term_id
		);
		if ( ! is_array( $status ) ) {
			$status = array( $status );
		}
		$allowed_statuses = apply_filters(
			'rpwc2_initial_rank_posts_allowed_statuses',
			array( 'publish', 'private', 'future', 'pending', 'draft' )
		);
		$status           = array_intersect( $status, $allowed_statuses );
		return "('" . implode( "','", $status ) . "')";
	}

	/**
	 * function to retrieve the current order of posts.
	 *
	 * @since 2.0.0.
	 * @param string $post_type the post type for which to retrive an order.
	 * @param int $term_id the id of the category term for which the order is required.
	 * @return array an array of post_id from the postmeta table in ranking order.
	 */
	// protected function _get_order(string $post_type, int $term_id, int $start=0, int $length=null){ php 8
	protected function _get_order( $post_type, $term_id, $start = 0, $length = null ) {
		global $wpdb;
		$this->old_ranking_exists = false;

		$ranking = self::get_order( $post_type, $term_id );

		if ( empty( $ranking ) ) { // retrieve the default ranking.
			// check if v1.x table exists.
			$table_name = $wpdb->prefix . $this->old_table_name;
			/** NB @since 2.3.0 check for post_type properly */
			// wpg_debug($wpdb->get_var("SHOW TABLES LIKE '$table_name'"), "SHOW TABLES LIKE '$table_name': ");
			if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) == $table_name ) { // cehck table exits.
				$ranking = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT rpwc.post_id
					FROM {$table_name} as rpwc
					LEFT JOIN {$wpdb->posts} as wp on wp.ID = rpwc.post_id
					WHERE rpwc.category_id = %d AND wp.post_type=%s order by rpwc.id",
						$term_id,
						$post_type
					)
				);
				/** NB @since 2.9.0 display admin notice warning. */
				$this->old_ranking_exists = ! empty( $ranking );
			}
			// wpg_debug($ranking, "ranking " );
			if ( empty( $ranking ) ) {
				$orderby = 'rpwc_p.post_date';
				if ( apply_filters( 'reorder_posts_within_category_initial_orderby', false, $post_type, $term_id ) ) {
					$orderby = 'rpwc_p.post_name';
				}
				$order = 'DESC';
				if ( apply_filters( 'reorder_posts_within_category_initial_order', false, $post_type, $term_id ) ) {
					$order = 'ASC';
				}
				$status = $this->_get_status( $post_type, $term_id );
				$sql    = $wpdb->prepare(
					"SELECT rpwc_p.ID FROM {$wpdb->posts} as rpwc_p
					LEFT JOIN {$wpdb->term_relationships} AS rpwc_tr ON rpwc_p.ID=rpwc_tr.object_id
					LEFT JOIN {$wpdb->term_taxonomy} AS rpwc_tt ON rpwc_tr.term_taxonomy_id = rpwc_tt.term_taxonomy_id
					WHERE  rpwc_p.post_status IN {$status}
					AND rpwc_p.post_type=%s
					AND rpwc_tt.term_id=%d
					ORDER BY {$orderby} {$order}",
					$post_type,
					$term_id
				);
				/** NB @since 2.4.3 filter the ranking query with the hook at the end of the queue.*/
				self::filter_query( $sql, 'SELECT rpwc_p.ID' );
				$ranking = $wpdb->get_col( $sql );
				/** NB @since 2.4.0 enable programmatic default ranking */
				$filtered_ranking = apply_filters( 'rpwc2_filter_default_ranking', $ranking, $term_id, $_POST['taxonomy'], $post_type );
				if ( ! empty( $filtered_ranking ) && is_array( $filtered_ranking ) ) {
					$new_ranking = array();
					foreach ( $filtered_ranking as $post_id ) {
						if ( ( $idx = array_search( $post_id, $ranking ) ) !== false ) {
								$new_ranking[] = $post_id;
								unset( $ranking[ $idx ] );
						}
					}
					$ranking = array_merge( $new_ranking, $ranking );
				}
			}
			$this->_save_order( $post_type, $ranking, $term_id );
		}
		if ( empty( $length ) || $length > sizeof( $ranking ) ) {
			$length = sizeof( $ranking );
		}
		return array_splice( $ranking, $start, $length );
	}
	/**
	 * Expose rank retrieval for a given post type and term id
	 *
	 * @since 2.11.0
	 * @param String $post_type post type
	 * @param String $term_id term ID
	 * @return Array array of ranked post IDs
	 */
	// public static function get_order(string $post_type, int $term_id){ php 8
	public static function get_order( $post_type, $term_id ) {
		global $wpdb;
		$query = $wpdb->prepare(
			"SELECT rpwc_pm.post_id
			FROM {$wpdb->postmeta} as rpwc_pm, {$wpdb->posts} as rpwc_p
			WHERE rpwc_pm.meta_key ='_rpwc2'
			AND rpwc_pm.meta_value=%d
			AND rpwc_pm.post_id=rpwc_p.ID
			AND rpwc_p.post_type=%s
      ORDER BY rpwc_pm.meta_id",
			$term_id,
			$post_type
		);

		/** NB @since 2.4.3 */
		self::filter_query( $query, 'SELECT rpwc_pm.post_id' );
		return $wpdb->get_col( $query );
	}
	/**
	 * Display hierarchy of terms for a taxonomy in the admin reorder page dropdown list.
	 *
	 * @since 2.10.0
	 * @param string $param text_description
	 * @return string text_description
	 */
	public function display_child_terms( $post_name, $taxonomy, $parent_id, $get_id, $level = 1 ) {
		$term_query = array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
			'parent'     => $parent_id,
		);
		$list_terms = get_terms( $term_query );
		if ( count( $list_terms ) == 0 ) {
			return;
		}
		$post_counts = $this->count_posts_in_term( $post_name, wp_list_pluck( $list_terms, 'term_id' ) );
		foreach ( $list_terms as $term ) {
			$selected = '';
			if ( isset( $get_id ) && ( $get_id == $term->term_id ) ) {
				$selected      = ' selected = "selected"';
				$term_selected = $term->name;
			}
			$disabled = '';
			if ( isset( $post_counts[ $term->term_id ] ) && $post_counts[ $term->term_id ] < 2 ) {
				$disabled = ' disabled = "disabled"';
			}
			echo '<option ' . $selected . $disabled . ' value="' . $term->term_id . '">' . str_repeat( '-', $level ) . $term->name . '</option>' . PHP_EOL;
			$this->display_child_terms( $post_name, $taxonomy, $term->term_id, $get_id, $level + 1 );
		}
	}
	/**
	 * funciton to return the total count of posts in a given term for a given post type.
	 *
	 * @since 2.4.1
	 * @param string $post_type post type
	 * @param mixed $term_id array of or single value $term_id id of term to get count of posts.
	 * @return mixed int count of posts for single term, $term_id=>$count pairs for multiple terms..
	 */
	// protected function count_posts_in_term(string $post_type, $term_id){ php 8
	protected function count_posts_in_term( $post_type, $term_id ) {
		/** NB @since 2.7.1 count posts in multiple terms */
		if ( ! is_array( $term_id ) ) {
			$term_id = array( $term_id );
		}
		$terms = '(' . implode( ',', $term_id ) . ')';
		global $wpdb;
		$status = $this->_get_status( $post_type, $term_id );
		$sql    = $wpdb->prepare(
			"SELECT rpwc_tt.term_id, COUNT(rpwc_p.ID) as total FROM {$wpdb->posts} as rpwc_p
		  LEFT JOIN {$wpdb->term_relationships} AS rpwc_tr ON rpwc_p.ID=rpwc_tr.object_id
      LEFT JOIN {$wpdb->term_taxonomy} AS rpwc_tt ON rpwc_tr.term_taxonomy_id = rpwc_tt.term_taxonomy_id
    WHERE  rpwc_p.post_status IN {$status}
      AND rpwc_p.post_type=%s
      AND rpwc_tt.term_id IN {$terms}
    GROUP BY rpwc_tt.term_id",
			$post_type
		);
		$count  = $wpdb->get_results( $sql );
		// wpg_debug($sql);
		$return = array();
		switch ( true ) {
			case empty( $count ):
				break;
			default:
				foreach ( $count as $row ) {
					$return[ $row->term_id ] = $row->total;
				}
				// sql results with no post will not be returned.
				$return = $return + array_fill_keys( $term_id, 0 );
				break;
		}
		return $return;
	}
	/**
	 * General function to save a new order,
	 *
	 * @since 2.0.0
	 * @param array $order an array of $post_id in ranked order.
	 * @param int $term_id the id of the category term for which the posts need to be ranked.
	 */
	// protected function _save_order( string $post_type, array $order=array(), int $term_id=0, int $start=0){ php 8
	protected function _save_order( $post_type, $order = array(), $term_id = 0, $start = 0 ) {
		if ( empty( $order ) || 0 == $term_id ) {
			return false;
		}
		global $wpdb;
		// wpg_debug($order, 'saving order ');
		$query = $wpdb->prepare( "SELECT rpwc_pm.meta_id, rpwc_p.ID FROM {$wpdb->posts} as rpwc_p LEFT JOIN {$wpdb->postmeta} as rpwc_pm on rpwc_p.ID = rpwc_pm.post_id WHERE rpwc_p.post_type like '%s' AND rpwc_pm.meta_key ='_rpwc2' AND rpwc_pm.meta_value=%d ORDER BY rpwc_pm.meta_id ASC", $post_type, $term_id );
		/** NB @since 2.4.3 */
		self::filter_query( $query, 'SELECT rpwc_pm.meta_id, rpwc_p.ID' );
		$ranked_rows = $wpdb->get_results( $query );

		if ( empty( $ranked_rows ) ) {
			foreach ( $order as $post_id ) {
					$value[] = "($post_id, '_rpwc2', $term_id)";
			}
			$sql = sprintf( "INSERT INTO {$wpdb->postmeta} (post_id, meta_key, meta_value) VALUES %s", implode( ',', $value ) );
			// self::filter_query($query, "SELECT rpwc_pm.meta_id, rpwc_pm.post_id");
			$wpdb->query( $sql );
		} else {
			// $ranked_id=array();
			/** NB @since 2.0.0 allow for partial ranking.*/
			$old_start = 0;
			$values    = array();
			$end       = sizeof( $order );
			$last      = sizeof( $ranked_rows ); // the last rows retain the same order.
			// wpg_debug($ranked_rows, 'current ranked rows ');
			foreach ( $ranked_rows as $idx => $row ) {
				if ( $idx >= $start && ( $idx - $start ) < $end ) { // replace current order.
					$values[] = "({$row->meta_id}, {$order[$idx-$start]}, '_rpwc2', {$term_id})";
				}
			}
			// wpg_debug($values, 'saving rank '.$start.' to '.$end);
			$sql = sprintf( "REPLACE INTO {$wpdb->postmeta} VALUES %s", implode( ',', $values ) );
			$wpdb->query( $sql );
			if ( ! empty( $wpdb->last_error ) ) {
				wpg_debug( $wpdb->last_error, 'SQL ERROR: ' );
				return false;
			}
		}

		return true;
	}
	/**
	 * filter queries last to ensure proper results.
	 *
	 * @since 2.4.3
	 * @param string $query to set
	 * @param string $match string to search in query to validate.
	 */
	// protected static function filter_query(string $query, string $match){ php 8
	protected static function filter_query( $query, $match ) {
		add_filter(
			'query',
			function( $q ) use ( $query, $match ) {
				if ( strpos( $q, $match ) !== false ) {
					$q = $query;
				}
				return $q;
			},
			PHP_INT_MAX
		);
	}
	/**
	 * function to remove postmeta for terms not manually ordered.
	 *
	 * @since 2.0.0
	 */
	// private function _unrank_posts_unused_taxonomy(bool $all = false, array $post_types=array()){ php 8
	private function _unrank_posts_unused_taxonomy( $all = false, $post_types = array() ) {
		$terms_used = array();
		$settings   = $this->get_admin_options();
		if ( $all ) {
			delete_option( $this->admin_options_slug );
		} else {
			$taxonomy_checked = array();
			foreach ( $post_types as $post_type ) {
				if ( isset( $settings['categories_checked'][ $post_type ] ) ) {
					$taxonomy_checked = array_merge( $taxonomy_checked, $settings['categories_checked'][ $post_type ] );
				}
			}
			$terms_used = get_terms( array( 'taxonomy' => $taxonomy_checked ) );
			$terms_used = wp_list_pluck( $terms_used, 'term_id' );
		}
		global $wpdb;
		$query = "SELECT DISTINCT rpwc_pm.meta_value FROM $wpdb->postmeta as rpwc_pm WHERE rpwc_pm.meta_key LIKE '_rpwc2'";
		/** NB @since 2.4.3 */
		self::filter_query( $query, 'SELECT DISTINCT rpwc_pm.meta_value' );
		$terms_ordered = $wpdb->get_col( $query );
		/** @TODO delete ranking by post type */
		foreach ( $terms_ordered as $term_id ) {
			if ( empty( $terms_used ) || ! in_array( $term_id, $terms_used ) ) {
				$wpdb->delete(
					$wpdb->postmeta,
					array(
						'meta_key'   => '_rpwc2',
						'meta_value' => $term_id,
					),
					array( '%s', '%d' )
				);
			}
		}
	}
	/**
	 * Function to delete v1.x custom table.
	 *
	 * @since 2.0.0
	 */
	private function _delete_custom_table() {
		global $wpdb;
		$table_name = $wpdb->prefix . $this->old_table_name;

		$sqlDropTable = "DROP TABLE IF EXISTS $table_name";
		$wpdb->query( $sqlDropTable );
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sqlDropTable );
		// $settings = get_option(self::$settings_option_name, array());
		self::$settings['upgraded'] = false; // switchoff table delete button.
		update_option( self::$settings_option_name, self::$settings );
	}
	/**
	 * function to save options.
	 *
	 * @since 1.0.0.
	 */
	public function save_admin_options_on_init() {
		// Si le formulaire a Ã©tÃ© soumis, on rÃ©-enregistre les catÃ©gorie dont on veut trier les Ã©lÃ©ments
		if ( ! empty( $_POST ) && isset( $_POST['nounceUpdateOptionReorder'] ) && wp_verify_nonce( $_POST['nounceUpdateOptionReorder'], 'updateOptionSettings' ) ) {
			$categories_checked = array();
			if ( isset( $_POST['selection'] ) ) {
					$categories_checked = $_POST['selection'];
			}

			$settings_options['categories_checked'] = $categories_checked;
			$this->save_admin_options( $settings_options );
		}
	}
	/**
	 * Save admin options.
	 *
	 * @since 2.5.0
	 * @param array $settings array of settings.
	 */
	// public function save_admin_options(array $settings){ php 8
	public function save_admin_options( $settings ) {
		update_option( $this->admin_options_slug, $settings );
	}
	/**
	 * callback funciton to display the order page.
	 *
	 * @since 1.0.0
	 */
	public function print_order_page() {
		// On rÃ©cupÃ¨re le VPT sur lequel on travaille.
		$page_name = $_GET['page'];
		// wpg_debug('print order page '.$page_name).
		$cpt_name         = substr( $page_name, 13, strlen( $page_name ) );
		$post_type        = get_post_types( array( 'name' => $cpt_name ), 'objects' );
		$post_type_detail = $post_type[ $cpt_name ];
		unset( $post_type, $page_name, $cpt_name );
		$cat_to_retrieve_post = -1;

		// On charge les prÃ©fÃ©rences.
		$settings_options = $this->get_admin_options();
		// Si le formulaire a Ã©tÃ© soumis.
		$start_submitted = 1;
		$end_submitted   = 20;
		if ( ! empty( $_POST ) &&
		 check_admin_referer( 'loadPostInCat', 'nounceLoadPostCat' ) &&
		 isset( $_POST['nounceLoadPostCat'] ) &&
		 wp_verify_nonce( sanitize_key( $_POST['nounceLoadPostCat'] ), 'loadPostInCat' ) ) {
			if ( isset( $_POST['cat_to_retrive'] ) && ! empty( $_POST['cat_to_retrive'] ) && null !== $_POST['cat_to_retrive'] ) {
				$cat_to_retrieve_post = sanitize_key( $_POST['cat_to_retrive'] );
				// $taxonomy_submitted   = sanitize_key( $_POST['taxonomy'] );
				$start_submitted = isset( $_POST['post_start'] ) ? sanitize_key( $_POST['post_start'] ) : 1;
				$end_submitted   = isset( $_POST['post_end'] ) ? sanitize_key( $_POST['post_end'] ) : 20;
				if ( empty( $start_submitted ) ) {
					$start_submitted = 1;
				}
				if ( empty( $end_submitted ) ) {
					$end_submitted = 20;
				}

				$term = get_term( $cat_to_retrieve_post );

				// Si il y a une catÃ©gorie.
				if ( ! empty( $term ) ) {
					$cat_to_retrieve_post = $term->term_id; /** NB @since 2.14.5 to fix === validation */
					$ranking              = $this->_get_order( $post_type_detail->name, $cat_to_retrieve_post, ( $start_submitted - 1 ), $end_submitted );
					// $total = $term->count.
					$args        = array(
						'post_type'           => $post_type_detail->name,
						'post_status'         => 'any',
						'post__in'            => $ranking,
						'ignore_sticky_posts' => false,
						'posts_per_page'      => -1,
					);
					$posts_array = get_posts( $args );
					/** NB @since 2.4.1 better for multi post type */
					$total = $this->count_posts_in_term( $post_type_detail->name, $cat_to_retrieve_post );
					if ( ! empty( $total ) ) {
						$total = $total[ $cat_to_retrieve_post ]; /** NB @since 2.9.3 */
					} else {
						$total = 0;
					}
					foreach ( $posts_array as $post ) {
						$posts[ $post->ID ] = $post;
					}
				}
			}
		}
		// display partial html.
		include_once plugin_dir_path( __FILE__ ) . '/partials/reorder-post-within-categories-admin-display.php';
	}

	/**
	 *
	 */
	public function print_settings_page() {
		include_once plugin_dir_path( __FILE__ ) . '/partials/reorder-post-within-categories-settings-display.php';
	}

	/**
	 * Add an option age link for the administrator only
	 */
	public function add_setting_page() {
		if ( function_exists( 'add_options_page' ) ) {
			add_options_page( __( 'ReOrder Post within Categories', 'reorder-post-within-categories' ), __( 'ReOrder Post', 'reorder-post-within-categories' ), 'manage_options', basename( __FILE__ ), array( &$this, 'print_settings_page' ) );
		}
	}
	/**
	 * Show admin pages for sorting posts
	 * (as per settings options of plugin);
	 */
	public function add_order_pages() {
		// On liste toutes les catÃ©gorie dont on veut avoir la main sur le trie
		$settings_options = $this->get_admin_options();

		if ( ! isset( $settings_options['categories_checked'] ) ) {
				return;
		}
		// Pour chaque post_type, on regarde s'il y a des options de trie associÃ©
		// wpg_debug($settings_options);

		foreach ( $settings_options['categories_checked'] as $post_type => $taxonomies ) {
			/**
			*filter to allow other capabilities for managing orders.
			 *
			* @since 1.3.0
			*/
			$capability = 'manage_categories';
			// if('lp_course'==$post_type) $capability  = 'edit_' . LP_COURSE_CPT . 's';
			$capability = apply_filters( 'reorder_post_within_categories_capability', $capability, $post_type );
			if ( 'manage_categories' !== $capability ) { // validate capability.
				$roles    = wp_roles();
				$is_valid = false;
				foreach ( $roles->roles as $role ) {
					if ( in_array( $capability, $role['capabilities'] ) ) {
							$is_valid = true;
							break;
					}
				}
				if ( ! $is_valid ) {
					$capability = 'manage_categories';
				}
			}
			/* translators: menu label */
			$menu_label = __( 'Reorder', 'reorder-post-within-categories' );
			switch ( true ) {
				case 'attachment' == $post_type:
					$the_page = add_submenu_page( 'upload.php', 'Re-order', $menu_label, $capability, 're-orderPost-' . $post_type, array( &$this, 'print_order_page' ) );
					break;
				case 'post' == $post_type:
					$the_page = add_submenu_page( 'edit.php', 'Re-order', $menu_label, $capability, 're-orderPost-' . $post_type, array( &$this, 'print_order_page' ) );
					// wpg_debug("page hook: $the_page");
					break;
				case 'lp_course' == $post_type && is_plugin_active( 'learnpress/learnpress.php' ): /** NB @since 2.5.6 learnpress fix.*/
						$the_page = add_submenu_page( 'learn_press', 'Re-order', $menu_label, 'edit_lp_courses', 're-orderPost-' . $post_type, array( &$this, 'print_order_page' ) );
					break;
				default:
					$the_page = add_submenu_page( 'edit.php?post_type=' . $post_type, 'Re-order', $menu_label, $capability, 're-orderPost-' . $post_type, array( &$this, 'print_order_page' ) );
					break;
			}
			// enqueue styles on scripts on page specific hook.
			add_action( 'admin_head-' . $the_page, array( $this, 'enqueue_styles' ) );
			add_action( 'admin_head-' . $the_page, array( $this, 'enqueue_scripts' ) );
		}
	}
	/**
	 * Reset global $typenow to ensure post sub-menu reorder pages are not broken.
	 *
	 * @since 2.9.4
	 */
	public function reset_typenow() {
		global $pagenow, $typenow;
		if ( 'edit.php' == $pagenow && 'post' == $typenow ) {
			$typenow = '';
		}
	}

	/**
	 * Dispplay a link to setting page inside the plugin description
	 * hooked to 'plugin_action_links_{$plugin_name}'
	 */
	// public function display_settings_link(array $links){ //php 8
	public function display_settings_link( $links ) {
		$settings_link = '<a href="options-general.php?page=class-reorder-post-within-categories-admin.php">' . __( 'Settings', 'reorder-post-within-categories' ) . '</a>';
		array_push( $links, $settings_link );
		return $links;
	}
	/**
	 * display admin notice.
	 *
	 * @since 1.0
	 */
	public function admin_dashboard_notice() {
		$options = $this->get_admin_options();
		if ( empty( $options ) ) {
			include_once plugin_dir_path( __FILE__ ) . '/partials/reorder-post-within-categories-notice-display.php';
		}
	}
	/**
	 * When a new post is created several actions are required
	 * We need to inspect all associated taxonomies
	 * hooked on 'transition_post_status'
	 *
	 * @param type $post_id
	 */
	// public function save_post(string $new_status, string $old_status, \WP_Post $post){ //php 8
	public function save_post( $new_status, $old_status, $post ) {
		// Liste des taxonomies associÃ©e Ã  ce post
		$taxonomies = get_object_taxonomies( $post->post_type );
		// wpg_debug($taxonomies, $post->post_type);
		if ( empty( $taxonomies ) ) {
			return;
		}
		// verify that this post_type is manually ranked for the associated terms.
		$settings = $this->get_admin_options();
		if ( empty( $settings ) || ! isset( $settings['categories_checked'][ $post->post_type ] ) ) {
			// if there are no taxonomies checked then this post cannot be manually ranked.
			// wpg_debug($settings, 'settings ');
			return;
		}
		// taxonomies ranked for this post type.
		$ranked_tax = $settings['categories_checked'][ $post->post_type ];
		// taxonomies associated with this post that are manually ranked.
		$ranked_tax = array_intersect( $ranked_tax, $taxonomies );
		// wpg_debug($ranked_tax, 'ranked tax ');
		if ( empty( $ranked_tax ) ) {
			return;
		}

		// find if terms are currently being ranked.
		$ranked_terms = get_option( RPWC_OPTIONS_2, array() );
		// wpg_debug($ranked_terms, 'ranked terms ');

		if ( ! isset( $ranked_terms[ $post->post_type ] ) ) {
			return; // no terms ranked for this post type.
		}

		$ranked_terms = array_keys( $ranked_terms[ $post->post_type ] );
		$ranked_ids   = array();
		foreach ( $ranked_tax as $tax ) {
			$post_terms = wp_get_post_terms( $post->ID, $tax, array( 'fields' => 'ids' ) );
			$ranked_ids = array_merge( $ranked_ids, array_intersect( $post_terms, $ranked_terms ) );
		}

		$public = array( 'publish', 'private', 'future' );
		$draft  = array( 'draft', 'pending' );

		$post_ranks = get_post_meta( $post->ID, '_rpwc2', false );
		$old_ranks  = array_diff( $post_ranks, $ranked_ids );
		// these are terms which this post you to be part of and were ranked.
		foreach ( $old_ranks as $term_id ) {
			$this->unrank_post( $post->ID, $term_id );
		}

		// finally check the current status of the post.
		switch ( true ) {
			case in_array( $new_status, $public ):
				// status->publish = rank this post.
				foreach ( $ranked_ids as $term_id ) {
					/** NB @since 2.5.0 give more control of which post status to rank */
					$rank_post = apply_filters( 'rpwc2_rank_published_posts', true, $term_id, $new_status, $old_status, $term_id, $post );
					if ( ! in_array( $term_id, $post_ranks ) && $rank_post ) {
						$this->rank_post( $post, $term_id );
					} elseif ( in_array( $term_id, $post_ranks ) && ! $rank_post ) {
						$this->unrank_post( $post->ID, $term_id );
					}
				}
				break;
			case in_array( $new_status, $draft ):
				// status->draft
				foreach ( $ranked_ids as $term_id ) {
					/** NB @since 2.5.0 give more control of which post status to rank */
					$rank_post = apply_filters( 'rpwc2_rank_draft_posts', false, $new_status, $old_status, $term_id );

					if ( ! in_array( $term_id, $post_ranks ) && $rank_post ) {
						$this->rank_post( $post, $term_id );
					} elseif ( in_array( $term_id, $post_ranks ) && ! $rank_post ) {
						$this->unrank_post( $post->ID, $term_id );
					}
				}
				break;
			case 'trash' == $new_status:
				// if( in_array($term_id, $post_ranks) ) $this->unrank_post($post->ID, $term_id);
				break;
		}
	}
	/**
	 * Rank a new post.
	 *
	 * @since 2.5.0
	 * @param string $param text_description
	 * @return string text_description
	 */
	// public function rank_post(\WP_Post $post, int $term_id){ //php 8
	public function rank_post( $post, $term_id ) {
		if ( apply_filters( 'reorder_post_within_categories_new_post_first', false, $post, $term_id ) ) {
			$ranking = $this->_get_order( $post->post_type, $term_id );
			add_post_meta( $post->ID, '_rpwc2', $term_id, false );
			array_unshift( $ranking, $post->ID );
			$this->_save_order( $post->post_type, $ranking, $term_id );
		} else {
			add_post_meta( $post->ID, '_rpwc2', $term_id, false );
		}
	}
	/**
	 * When a post is deleted we remove all entries from the custom table
	 *
	 * @param type $post_id
	 */
	// public function unrank_post(int $post_id, int $term_id=-1){ //php 8
	public function unrank_post( $post_id, $term_id = -1 ) {
		// wpg_debug("unranking post $post_id from term $term_id");
		if ( $term_id < 0 ) {
			delete_post_meta( $post_id, '_rpwc2' );
		} else {
			delete_post_meta( $post_id, '_rpwc2', $term_id );
		}
	}
	/**
	 * Delete all ranks for a given term.
	 *
	 * @since 2.1.0.
	 * @param $term_id term id to unrank posts
	 * @param $post_type post type for which to unrank posts.
	 * @return boolean false if there was an issue.
	 */
	// protected function _unrank_all_posts(int $term_id, string $post_type){ //php 8
	protected function _unrank_all_posts( $term_id, $post_type ) {
		if ( empty( $term_id ) || empty( $post_type ) ) {
			wpg_debug( 'UNABLE to Unrank, not term ID and/or post_type defined' );
			return false;
		}
		if ( is_array( $post_type ) ) {
			$post_type = implode( "','", $post_type );
		}
		global $wpdb;
		$sql = $wpdb->prepare( "DELETE meta FROM {$wpdb->postmeta} as meta JOIN {$wpdb->posts} as post ON post.ID=meta.post_id WHERE meta.meta_key LIKE '_rpwc2' AND meta.meta_value LIKE %s AND post.post_type IN ('{$post_type}')", $term_id );
		$wpdb->query( $sql );
		if ( ! empty( $wpdb->last_error ) ) {
			wpg_debug( $wpdb->last_error, 'SQL ERROR:' );
			return false;
		}
		// wpg_debug($sql, 'deleted '.$post_type.' term '.$term_id);
		return true;
	}
}
/**
* funciton to retrieve ranked post IDs for a given post type and term ID.
 *
* @since 2.11.0
* @param String $post_type post type
* @param String $term_id term ID
* @return Array array of ranked post IDs
*/
if ( ! function_exists( 'get_rpwc2_order' ) ) {
	// function get_rpwc2_order(string $post_type, int $term_id){ //php 8
	function get_rpwc2_order( $post_type, $term_id ) {
		return Reorder_Post_Within_Categories_Admin::get_order( $post_type, $term_id );
	}
}
