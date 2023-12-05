<?php
/**
 * Display admin notices
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://profiles.wordpress.org/aurovrata/
 * @since      2.0.0
 *
 * @package    Reorder_Post_Within_Categories
 * @subpackage Reorder_Post_Within_Categories/admin/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div class="updated re_order">
	<p><?php /* translators: link to settings page */echo wp_kses_post( sprintf( __( 'First of all, you need to <a href="%s">save your settings for <em>ReOrder Posts in Categories</em></a>.', 'reorder-post-within-categories' ), esc_url( admin_url( 'options-general.php?page=class-reorder-post-within-categories-admin.php' ) ) ) ); ?></p>
</div>
