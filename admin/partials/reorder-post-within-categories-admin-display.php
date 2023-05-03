<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://profiles.wordpress.org/aurovrata/
 * @since      2.0.0
 *
 * @package    Reorder_Post_Within_Categories
 * @subpackage Reorder_Post_Within_Categories/admin/partials
 */
 $listCategories = $settingsOptions['categories_checked'][ $post_type_detail->name ];
 $taxonomies     = '';
 $taxonomy       = '';
 $term_selected  = '';
?>
<div class="wrap">
	<div class="icon32 icon32-posts-<?php esc_attr_e( $post_type_detail->name ); ?>" id="icon-edit"><br></div>
  <h2><?php /* translators: post type labels menu-name */ esc_html_e( sprintf( __( 'Manually rank your "%s"', 'reorder-post-within-categories' ), $post_type_detail->labels->menu_name ) ); ?></h2>
  <p>
	<?php
		/* translators: post type labels name */
		echo wp_kses_post( sprintf( __( 'Select a taxonomy to sort <b>%s</b>.', 'reorder-post-within-categories' ), $post_type_detail->labels->name ) );
	?>
  </p>

  <form method="post" id="chooseTaxomieForm">
  <?php
	wp_nonce_field( 'loadPostInCat', 'nounceLoadPostCat' );
	if ( count( $listCategories ) > 0 ) :
		?>
	<select id="selectCatToRetrieve" name="cat_to_retrive" disabled="true">
	  <option value="null" disabled="disabled" selected="selected"><?php esc_html_e( __( 'Select', 'reorder-post-within-categories' ) ); ?></option>
		<?php
		foreach ( $listCategories as $idx => $categorie ) :
			$taxonomies = get_taxonomies( array( 'name' => $categorie ), 'object' );
			if ( ! isset( $taxonomies[ $categorie ] ) ) { // this taxonomy no longer exists.
				unset( $settingsOptions['categories_checked'][ $post_type_detail->name ][ $idx ] );
				$this->save_admin_options( $settingsOptions );
				continue;
			}
			$taxonomy = $taxonomies[ $categorie ];
			// On liste maintenant les terms disponibles pour la taxonomie concernÃ©e
			$term_query = array(
				'taxonomy'   => $taxonomy->name,
				'hide_empty' => false,
				'parent'     => 0,
			);
			$list_terms = get_terms( $term_query );
			if ( count( $list_terms ) > 0 ) :
				?>
			<optgroup id="<?php esc_attr_e( $taxonomy->name ); ?>" label="<?php esc_attr_e( $taxonomy->labels->name ); ?>">
				<?php
				/** @since 2.7.1 fix for term counts */
				$post_counts = $this->count_posts_in_term( $post_type_detail->name, wp_list_pluck( $list_terms, 'term_id' ) );
				foreach ( $list_terms as $term ) :
					$selected = '';
					if ( $cat_to_retrieve_post == $term->term_id ) {
						$selected      = ' selected = "selected"';
						$term_selected = $term->name;
					}
					$disabled = '';
					if ( isset( $post_counts[ $term->term_id ] ) && $post_counts[ $term->term_id ] < 2 ) {
						$disabled = ' disabled = "disabled"';
					}
					?>
			  <option <?php esc_attr_e( $selected . $disabled ); ?> value="<?php esc_attr_e( $term->term_id ); ?>"><?php esc_html_e( $term->name ); ?></option>
					<?php
					$this->display_child_terms( $post_type_detail->name, $taxonomy->name, $term->term_id, $cat_to_retrieve_post );
		  endforeach; // foreach ($list_terms as $term).
				?>
			</optgroup>
				<?php
	  endif; // if (count($list_terms) > 0).
	endforeach; // foreach ($listCategories as $categorie).
		?>
		</select>
		<br/><span class="description"><?php _e( 'Greyed-out categories contain too few posts and aren’t available for sorting.', 'reorder-post-within-categories' ); ?></span>
		<?php
		$valueTaxonomyField = ( isset( $taxonomySubmitted ) ? $taxonomySubmitted : '' );
		?>
	<input type="hidden" id="taxonomyHiddenField" name="taxonomy" value="<?php esc_attr_e( $valueTaxonomyField ); ?>"/>
	<input type="hidden" id="rpwc2-post-start" name="post_start" value="<?php esc_attr_e( $start_submitted ); ?>"/>
		<input type="hidden" id="rpwc2-post-end" name="post_end" value="<?php esc_attr_e( $end_submitted ); ?>"/>
  <?php endif;// if (count($listCategories) > 0). ?>
	</form>
	<form id="form_result" method="post">
<?php if ( isset( $posts_array ) ) : ?>
  <input id="post-type" type="hidden" name="post-type" value="<?php esc_attr_e( $post_type_detail->name ); ?>">

	<div id="result">
		<div id="sorter_box">
			<h3 style="margin: 5px 0"><?php _e( 'Use the manual sorting for this category?', 'reorder-post-within-categories' ); ?></h3>
			<p style="margin:2px 0">
				<?php _e( 'This switches the manual sorting on the front-end on or off.  You can switch it off and manually sort your posts below until the new order is ready and you can then proceed to switch this on to showcase the new order on the front-end.', 'reorder-post-within-categories' ); ?>
			</p>
				<div id="catOrderedRadioBox">
		  <?php
					// on regarde si un des radio est cochÃ©
					$checkedRadio1 = $override = '';
					$disabled      = 'disabled="disabled" ';
					$checkedRadio2 = ' checked = "checked" ';
			$type                  = $post_type_detail->name;
					$tax_options   = get_option( RPWC_OPTIONS_2, array() );
			if ( isset( $tax_options[ $type ] ) && isset( $tax_options[ $type ][ $cat_to_retrieve_post ] ) ) {
				if ( $tax_options[ $type ][ $cat_to_retrieve_post ]['order'] ) {
					$checkedRadio1         = $checkedRadio2;
							$checkedRadio2 = '';
							$disabled      = '';
				}
						/** @since 2.6.0 enable override setting */
						// debug_msg($tax_options[$type], 'override ');
				if ( $tax_options[ $type ][ $cat_to_retrieve_post ]['override'] ) {
					$override = 'checked="checked" ';
				}
			} else { /** @since 2.7.8 save unordered terms */
				if ( ! isset( $tax_options[ $type ] ) ) {
					$tax_options[ $type ] = array();
				}
				if ( ! isset( $tax_options[ $type ][ $cat_to_retrieve_post ] ) ) {
					$tax_options[ $type ][ $cat_to_retrieve_post ] = array(
						'order'    => 0,
						'override' => 0,
					);
				}
				update_option( RPWC_OPTIONS_2, $tax_options );
			}
			?>
					<label for="yes">
			<input type="radio" <?php esc_attr_e( $checkedRadio1 ); ?>class="option_order settings" id="yes" value="true" name="useForThisCat"/>
			<span><?php _e( 'Yes', 'reorder-post-within-categories' ); ?></span>
		  </label><br/>
			<!-- translators: Opposite of Yes -->
					<label for="no">
			<input type="radio" <?php esc_attr_e( $checkedRadio2 ); ?>class="option_order settings" id="no" value="false" name="useForThisCat"/>
			<span><?php _e( 'No', 'reorder-post-within-categories' ); ?></span>
		  </label><br/>
		  <?php
					$message = __( '<strong>Caution: </strong>Overriding &apos;orderby&apos; query attribute can have important consequences on WooCommerce listings where themes can display products ranked on various parameters such as price.  This option overrides all other sortings, read <a href="https://wordpress.org/plugins/reorder-post-within-categories/#faq">FAQ #10</a> to see how to gain a finer control over this.', 'reorder-post-within-categories' );
			/** @since 2.12.0 force override by default for WooCommerce products */
			if ( is_plugin_active( 'woocommerce/woocommerce.php' ) && $type === 'product' ) {
				$disabled        = 'disabled="disabled" ';
				$override        = 'checked="checked" ';
						$message = __( '<strong>NOTE: </strong> On WooCommerce listings, the override is now set by default to ensure your manual sorting is reflected on your product page. The plugin will only override the default sorting directive.  WooCommerce themes that provide sorting by other factors (price, popularity...) should not be affected.  Please read <a href="https://wordpress.org/plugins/reorder-post-within-categories/#faq">FAQ #10</a> to see how to gain a finer control over this.', 'reorder-post-within-categories' );
			}
			?>
		  <label for="override-orderby">
			<input type="checkbox" <?php esc_attr_e( $disabled ); ?><?php esc_attr_e( $override ); ?>id="override-orderby" class="settings"/>
			<span><?php _e( "Override 'orderby' query attribute", 'reorder-post-within-categories' ); ?></span>
		  </label>
					<p><?php echo wp_kses_post( $message ); ?></p>
		</br/>
		  <div id="reset-order">
						<h4 style="margin:5px 0"><?php _e( 'Reset the order!', 'reorder-post-within-categories' ); ?></h4>
			<label for="reset-button">
			  <input type="checkbox" value="reset-button" id="enable-reset" />
								<?php _e( 'reset order for all posts, <strong>careful</strong>, this cannot be undone!', 'reorder-post-within-categories' ); ?>
			</label>
			<div>
							<a class="button disabled"><?php _e( 'Reset order', 'reorder-post-within-categories' ); ?></a>
						</div>
		  </div>
					<input type="hidden" name="termID" id="termIDCat" value="<?php esc_attr_e( $cat_to_retrieve_post ); ?>">
					<span class="spinner" id="spinnerAjaxRadio"></span>
				</div>
			<?php if ( $this->old_ranking_exists ) : ?>
				<p class="warning"><?php _e( 'NOTE: the plugin has detected that you have a v1.X legacy data table with an existing ranking for this term and has loaded the manual order found.  However, please delete the legacy ranking data once you have successfully ranked all your posts, for more information (see <a href="https://wordpress.org/plugins/reorder-post-within-categories/#faq">FAQ #17</a>)', 'reorder-post-within-categories' ); ?></p>
			<?php endif; ?>
				<h3 class="floatLeft"><?php /*translators: post_type labels name | category term */ esc_html_e( sprintf( __( 'Grid of %1$s, classified as %2$s:', 'reorder-post-within-categories' ), $post_type_detail->labels->name, $term_selected ) ); ?></h3>
				<span id="spinnerAjaxUserOrdering" class="spinner"></span>
				<div class="clearBoth"></div>
				<p id="range-text">
					<span class="title"><?php _e( 'Post range:' ); ?></span>
					<input id="range-min" min="1" max="<?php esc_attr_e( ( $total - 1 ) ); ?>" class="input-range" type="number">&#8212;<input id="range-max" max="<?php esc_attr_e( $total ); ?>" min="<?php esc_attr_e( ( $total - 1 ) ); ?>"  class="input-range" type="number"/>
					<span id="remove-items" style="display:none">
						<label for="insert-order"><?php _e( 'Move items to rank:', 'reorder-post-within-categories' ); ?>
							<input type="number" min="1" max="<?php esc_attr_e( $total ); ?>" name="insert-order" value=""/>
						</label><span class="error"></span>
						<span class="display-block"><?php _e( 'Select single/multiple items to move out of the current displayed range and insert towards the beginning or end of your list by selecting a suitable rank', 'reorder-post-within-categories' ); ?></span>
					</span>
				</p>
				<div id="slider-range" data-max="<?php esc_attr_e( $total ); ?>"></div>
		<p class="instructions"><?php /* translators: CTRL | SHIFT */echo wp_kses_post( sprintf( __( '<em>Use</em> %1$s <em>and/or</em> %2$s keys to select multiple items.', 'reorder-post-within-categories' ), '<strong>CTRL</strong>', '<strong>SHIFT</strong>' ) ); ?></p>
				<div id="sortable-list" class="order-list" rel ="<?php esc_attr_e( $cat_to_retrieve_post ); ?>" data-count="<?php esc_attr_e( $total ); ?>">
					<?php
					foreach ( $ranking as $idx => $post_id ) :
						$post = $posts[ $post_id ];
						$img  = Reorder_Post_Within_Categories_Admin::get_thumbnail_url( $post );
						?>

					<div data-id="<?php esc_attr_e( $post_id ); ?>" class="sortable-items">
						<img src="<?php echo esc_url( $img ); ?>">
						 <span class="title <?php esc_attr_e( $post->post_status ); ?>">
							 <a href="<?php echo esc_url( admin_url( 'post.php?post=' . $post_id . '&action=edit' ) ); ?>">
								<?php echo wp_kses_post( apply_filters( 'reorder_posts_within_category_card_text', get_the_title( $post ), $post, $cat_to_retrieve_post ) ); ?>
							</a>
						 </span>
				</div>
	 <?php	endforeach; ?>
			</div>
		</div>
	</div>
<?php endif; ?>
</form>
<div id="debug"></div>
</div>
