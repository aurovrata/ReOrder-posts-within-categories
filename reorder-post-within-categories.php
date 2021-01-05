<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://profiles.wordpress.org/aurovrata/
 * @since             2.0.0
 * @package           Reorder_Post_Within_Categories
 *
 * @wordpress-plugin
 * Plugin Name:       ReOrder Posts within Categories
 * Plugin URI:        https://github.com/aurovrata/ReOrder-posts-within-categories
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           2.9.1
 * Author:            Aurorata V.
 * Author URI:        https://profiles.wordpress.org/aurovrata/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       reorder-post-within-categories
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Current plugin version.
 */
define( 'REORDER_POST_WITHIN_CATEGORIES_VERSION', '2.9.1' );
//options for plugin used in both public and admin classes.
define('RPWC_OPTIONS', 'deefuse_ReOrderOrderedCategoriesOptions');
define('RPWC_OPTIONS_2', '_rpwc2_sort_options');

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-reorder-post-within-categories-activator.php
 */
function activate_reorder_post_within_categories() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-reorder-post-within-categories-activator.php';
	Reorder_Post_Within_Categories_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-reorder-post-within-categories-deactivator.php
 */
function deactivate_reorder_post_within_categories() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-reorder-post-within-categories-deactivator.php';
	Reorder_Post_Within_Categories_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_reorder_post_within_categories' );
register_deactivation_hook( __FILE__, 'deactivate_reorder_post_within_categories' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-reorder-post-within-categories.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_reorder_post_within_categories() {

	$plugin = new Reorder_Post_Within_Categories();
	$plugin->run();

}
run_reorder_post_within_categories();
