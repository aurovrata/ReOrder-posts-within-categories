<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://profiles.wordpress.org/aurovrata/
 * @since      1.0.0
 *
 * @package    Reorder_Post_Within_Categories
 * @subpackage Reorder_Post_Within_Categories/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Reorder_Post_Within_Categories
 * @subpackage Reorder_Post_Within_Categories/includes
 * @author     Aurorata V. <vrata@syllogic.in>
 */
class Reorder_Post_Within_Categories {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Reorder_Post_Within_Categories_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'REORDER_POST_WITHIN_CATEGORIES_VERSION' ) ) {
			$this->version = REORDER_POST_WITHIN_CATEGORIES_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'reorder-post-within-categories';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Reorder_Post_Within_Categories_Loader. Orchestrates the hooks of the plugin.
	 * - Reorder_Post_Within_Categories_i18n. Defines internationalization functionality.
	 * - Reorder_Post_Within_Categories_Admin. Defines all hooks for the admin area.
	 * - Reorder_Post_Within_Categories_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-reorder-post-within-categories-loader.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/wordpress-gurus-debug-api.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-reorder-post-within-categories-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-reorder-post-within-categories-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-reorder-post-within-categories-public.php';

		$this->loader = new Reorder_Post_Within_Categories_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Reorder_Post_Within_Categories_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Reorder_Post_Within_Categories_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Reorder_Post_Within_Categories_Admin( $this->get_plugin_name(), $this->get_version() );

		// $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'register_styles_on_plugin_php' );
		// $this->loader->add_action( 'after_plugin_row_ReOrder-posts-within-categories/reorder-post-within-categories.php', $plugin_admin, 'enable_warning_on_plugin_update',10,3 );
		// $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		$this->loader->add_filter( "plugin_action_links_{$this->plugin_name}", $plugin_admin, 'display_settings_link' );

		// hook for notices
		$this->loader->add_action( 'admin_notices', $plugin_admin, 'admin_dashboard_notice' );
		// Action qui sauvegardera le paamÃ©trage du plugin
		$this->loader->add_action( 'init', $plugin_admin, 'save_admin_options_on_init' );
		// Ajout de la page de paramÃ©trage du plugins
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_setting_page' );

		// Ajout des pages de classement des post pour les post et custom post type concernÃ©s
		/** @since 2.5.1 delay hook for learnPress reorder page */
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_order_pages', 20, 1 );
		$this->loader->add_action( 'wp_ajax_cat_ordered_changed', $plugin_admin, 'category_order_change' );
		$this->loader->add_action( 'wp_ajax_user_ordering', $plugin_admin, 'save_order' );
		$this->loader->add_action( 'wp_ajax_user_shuffle', $plugin_admin, 'shuffle_order' );
		$this->loader->add_action( 'wp_ajax_get_more_posts', $plugin_admin, 'load_posts' );

		$this->loader->add_action( 'transition_post_status', $plugin_admin, 'save_post', 10, 3 );
		// $this->loader->add_action('before_delete_post', $plugin_admin, 'unrank_post');
		$this->loader->add_action( 'trashed_post', $plugin_admin, 'unrank_post' );
		/** @since 2.9.4 reset $typenow for post admin pages. */
		$this->loader->add_action( 'admin_init', $plugin_admin, 'reset_typenow', PHP_INT_MAX );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Reorder_Post_Within_Categories_Public( $this->get_plugin_name(), $this->get_version() );

		// $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		// $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
		// filter post queries.
		if ( ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || ! is_admin() ) {
			$this->loader->add_filter( 'posts_where', $plugin_public, 'filter_posts_where', 10, 2 );
			$this->loader->add_filter( 'posts_join', $plugin_public, 'filter_posts_join', 5, 2 );
			$this->loader->add_filter( 'posts_orderby', $plugin_public, 'filter_posts_orderby', 10, 2 );
			$this->loader->add_filter( 'posts_request', $plugin_public, 'debug_sql_query', PHP_INT_MAX, 2 );
			// $this->loader->add_filter('posts_request', $plugin_public, 'filter_posts_request', 10, 2);
			/** @since 2.4.4 adjacent post query */
			$this->loader->add_filter( 'get_previous_post_join', $plugin_public, 'filter_adjacent_post_join', 5, 5 );
			$this->loader->add_filter( 'get_next_post_join', $plugin_public, 'filter_adjacent_post_join', 5, 5 );
			$this->loader->add_filter( 'get_previous_post_where', $plugin_public, 'filter_prev_post_where', 10, 5 );
			$this->loader->add_filter( 'get_next_post_where', $plugin_public, 'filter_next_post_where', 10, 5 );
			/** @since 2.12.0  override WooCommerce products*/
			$this->loader->add_filter( 'rpwc2_allow_custom_sort_orderby_override', $plugin_public, 'override_woocommerce_products', 1, 5 );

		}
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Reorder_Post_Within_Categories_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
