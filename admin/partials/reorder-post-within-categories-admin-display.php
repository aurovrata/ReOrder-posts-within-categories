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

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$list_categories   = $settings_options['categories_checked'][ $post_type_detail->name ];
$taxonomies        = '';
$taxnmy            = '';
$tax_term_selected = '';
?>
<div class="wrap">
	<div class="icon32 icon32-posts-<?php echo esc_attr( $post_type_detail->name ); ?>" id="icon-edit"><br></div>
	<h2>
	<?php
		echo sprintf(
			/* translators: post type labels menu-name */
			esc_html__( 'Manually rank your "%s"', 'reorder-post-within-categories' ),
			esc_html( $post_type_detail->labels->menu_name )
		);
		?>
	</h2>
	<p>
	<?php
		echo sprintf(
			/* translators: post type label name */
			esc_html__( 'Select a taxonomy to sort %s.', 'reorder-post-within-categories' ),
			'<b>' . esc_html( $post_type_detail->labels->name ) . '</b>'
		);
		?>
	</p>

	<form method="post" id="chooseTaxomieForm">
	<?php
	wp_nonce_field( 'loadPostInCat', 'nounceLoadPostCat' );
	if ( count( $list_categories ) > 0 ) :
		?>
	<select id="selectCatToRetrieve" name="cat_to_retrive" disabled="true">
		<option value="null" disabled="disabled" selected="selected"><?php esc_html_e( 'Select', 'reorder-post-within-categories' ); ?></option>
		<?php
		foreach ( $list_categories as $idx => $categorie ) :
			$taxonomies = get_taxonomies( array( 'name' => $categorie ), 'object' );
			if ( ! isset( $taxonomies[ $categorie ] ) ) { // this taxonomy no longer exists.
				unset( $settings_options['categories_checked'][ $post_type_detail->name ][ $idx ] );
				$this->save_admin_options( $settings_options );
				continue;
			}
			$taxnmy = $taxonomies[ $categorie ];
			// On liste maintenant les terms disponibles pour la taxonomie concernÃ©e.
			$tax_term_query = array(
				'taxonomy'   => $taxnmy->name,
				'hide_empty' => false,
				'parent'     => 0,
			);
			$list_terms     = get_terms( $tax_term_query );
			if ( count( $list_terms ) > 0 ) :
				?>
			<optgroup id="<?php echo esc_attr( $taxnmy->name ); ?>" label="<?php echo esc_attr( $taxnmy->labels->name ); ?>">
				<?php
				/** NB @since 2.7.1 fix for term counts */
				$post_counts = $this->count_posts_in_term( $post_type_detail->name, wp_list_pluck( $list_terms, 'term_id' ) );
				foreach ( $list_terms as $tax_term ) :
					$selected = '';
					if ( $cat_to_retrieve_post === $tax_term->term_id ) {
						$selected          = ' selected = "selected"';
						$tax_term_selected = $tax_term->name;
					}
					$disabled = '';
					if ( isset( $post_counts[ $tax_term->term_id ] ) && $post_counts[ $tax_term->term_id ] < 2 ) {
						$disabled = ' disabled = "disabled"';
					}
					?>
				<option <?php echo esc_attr( $selected . $disabled ); ?> value="<?php echo esc_attr( $tax_term->term_id ); ?>"><?php echo esc_html( $tax_term->name ); ?></option>
					<?php
					$this->display_child_terms( $post_type_detail->name, $taxnmy->name, $tax_term->term_id, $cat_to_retrieve_post );
			endforeach; // foreach ($list_terms as $tax_term).
				?>
			</optgroup>
				<?php
		endif; // if (count($list_terms) > 0).
	endforeach; // foreach ($list_categories as $categorie).
		?>
		</select>
		<br/><span class="description"><?php esc_html_e( 'Greyed-out categories contain too few posts and aren’t available for sorting.', 'reorder-post-within-categories' ); ?></span>
		<?php
		$value_taxonomy_field = ( isset( $taxnmy_submitted ) ? $taxnmy_submitted : '' );
		?>
	<input type="hidden" id="taxonomyHiddenField" name="taxonomy" value="<?php echo esc_attr( $value_taxonomy_field ); ?>"/>
	<input type="hidden" id="rpwc2-post-start" name="post_start" value="<?php echo esc_attr( $start_submitted ); ?>"/>
		<input type="hidden" id="rpwc2-post-end" name="post_end" value="<?php echo esc_attr( $end_submitted ); ?>"/>
	<?php endif;// if (count($list_categories) > 0). ?>
	</form>
	<form id="form_result" method="post">
<?php if ( isset( $posts_array ) ) : ?>
	<input id="post-type" type="hidden" name="post-type" value="<?php echo esc_attr( $post_type_detail->name ); ?>">

	<div id="result">
		<div id="sorter_box">
			<h3 style="margin: 5px 0"><?php esc_html_e( 'Use the manual sorting for this category?', 'reorder-post-within-categories' ); ?></h3>
			<p style="margin:2px 0">
				<?php esc_html_e( 'This switches the manual sorting on the front-end on or off.  You can switch it off and manually sort your posts below until the new order is ready and you can then proceed to switch this on to showcase the new order on the front-end.', 'reorder-post-within-categories' ); ?>
			</p>
				<div id="catOrderedRadioBox">
			<?php
					// on regarde si un des radios est coche.
					$checked_radio1 = '';
					$override       = '';
					$disabled       = 'disabled="disabled" ';
					$checked_radio2 = ' checked = "checked" ';
					$ptype          = $post_type_detail->name;
					$tax_options    = get_option( RPWC_OPTIONS_2, array() );
			if ( isset( $tax_options[ $ptype ] ) && isset( $tax_options[ $ptype ][ $cat_to_retrieve_post ] ) ) {
				if ( $tax_options[ $ptype ][ $cat_to_retrieve_post ]['order'] ) {
					$checked_radio1         = $checked_radio2;
							$checked_radio2 = '';
							$disabled       = '';
				}
						/** NB @since 2.6.0 enable override setting */
				if ( $tax_options[ $ptype ][ $cat_to_retrieve_post ]['override'] ) {
					$override = 'checked="checked" ';
				}
			} else { /** NB @since 2.7.8 save unordered terms */
				if ( ! isset( $tax_options[ $ptype ] ) ) {
					$tax_options[ $ptype ] = array();
				}
				if ( ! isset( $tax_options[ $ptype ][ $cat_to_retrieve_post ] ) ) {
					$tax_options[ $ptype ][ $cat_to_retrieve_post ] = array(
						'order'    => 0,
						'override' => 0,
					);
				}
				update_option( RPWC_OPTIONS_2, $tax_options );
			}
			?>
					<label for="yes">
			<input type="radio" <?php echo esc_attr( $checked_radio1 ); ?>class="option_order settings" id="yes" value="true" name="useForThisCat"/>
			<span><?php esc_html_e( 'Yes', 'reorder-post-within-categories' ); ?></span>
			</label><br/>
			<!-- translators: Opposite of Yes -->
					<label for="no">
			<input type="radio" <?php echo esc_attr( $checked_radio2 ); ?>class="option_order settings" id="no" value="false" name="useForThisCat"/>
			<span><?php esc_html_e( 'No', 'reorder-post-within-categories' ); ?></span>
			</label><br/>
			<?php
					$message = __( '<strong>Caution: </strong>Overriding &apos;orderby&apos; query attribute can have important consequences on WooCommerce listings where themes can display products ranked on various parameters such as price.  This option overrides all other sortings, read <a href="https://wordpress.org/plugins/reorder-post-within-categories/#faq">FAQ #10</a> to see how to gain a finer control over this.', 'reorder-post-within-categories' );
			/** NB @since 2.12.0 force override by default for WooCommerce products */
			if ( is_plugin_active( 'woocommerce/woocommerce.php' ) && 'product' === $ptype ) {
				$disabled        = 'disabled="disabled" ';
				$override        = 'checked="checked" ';
						$message = __( '<strong>NOTE: </strong> On WooCommerce listings, the override is now set by default to ensure your manual sorting is reflected on your product page. The plugin will only override the default sorting directive.  WooCommerce themes that provide sorting by other factors (price, popularity...) should not be affected.  Please read <a href="https://wordpress.org/plugins/reorder-post-within-categories/#faq">FAQ #10</a> to see how to gain a finer control over this.', 'reorder-post-within-categories' );
			}
			?>
			<label for="override-orderby">
			<input type="checkbox" <?php echo esc_attr( $disabled ); ?><?php echo esc_attr( $override ); ?>id="override-orderby" class="settings"/>
			<span><?php esc_html_e( "Override 'orderby' query attribute", 'reorder-post-within-categories' ); ?></span>
			</label>
					<p><?php echo wp_kses_post( $message ); ?></p>
		</br/>
			<div id="reset-order">
						<h4 style="margin:5px 0"><?php esc_html_e( 'Reset the order!', 'reorder-post-within-categories' ); ?></h4>
			<label for="reset-button">
				<input type="checkbox" value="reset-button" id="enable-reset" />
								<?php esc_html_e( 'reset order for all posts, <strong>careful</strong>, this cannot be undone!', 'reorder-post-within-categories' ); ?>
			</label>
			<div>
							<a class="button disabled"><?php esc_html_e( 'Reset order', 'reorder-post-within-categories' ); ?></a>
						</div>
			</div>
					<input type="hidden" name="termID" id="termIDCat" value="<?php echo esc_attr( $cat_to_retrieve_post ); ?>">
					<span class="spinner" id="spinnerAjaxRadio"></span>
				</div>
			<?php if ( $this->old_ranking_exists ) : ?>
				<p class="warning">
					<?php
					echo wp_kses(
						__( 'NOTE: the plugin has detected that you have a v1.X legacy data table with an existing ranking for this term and has loaded the manual order found.  However, please delete the legacy ranking data once you have successfully ranked all your posts, for more information (see <a href="https://wordpress.org/plugins/reorder-post-within-categories/#faq">FAQ #17</a>)', 'reorder-post-within-categories' ),
						array(
							'a' => array(
								'href'   => true,
								'target' => true,
							),
						),
					);
					?>
				</p>
			<?php endif; ?>
				<h3 class="floatLeft">
					<?php
						echo esc_html(
							sprintf(
								/* translators: post_type labels name | category term */
								__( 'Grid of %1$s, classified as %2$s:', 'reorder-post-within-categories' ),
								$post_type_detail->labels->name,
								$tax_term_selected
							)
						);
					?>
				</h3>
				<span id="spinnerAjaxUserOrdering" class="spinner"></span>
				<div class="clearBoth"></div>
				<p id="range-text">
					<span class="title"><?php esc_html_e( 'Post range:' ); ?></span>
					<input id="range-min" min="1" max="<?php echo esc_attr( ( $total - 1 ) ); ?>" class="input-range" type="number">&#8212;<input id="range-max" max="<?php echo esc_attr( $total ); ?>" min="<?php echo esc_attr( ( $total - 1 ) ); ?>"  class="input-range" type="number"/>
					<span id="remove-items" style="display:none">
						<label for="insert-order"><?php esc_html_e( 'Move items to rank:', 'reorder-post-within-categories' ); ?>
							<input type="number" min="1" max="<?php echo esc_attr( $total ); ?>" name="insert-order" value=""/>
						</label><span class="error"></span>
						<span class="display-block"><?php esc_html_e( 'Select single/multiple items to move out of the current displayed range and insert towards the beginning or end of your list by selecting a suitable rank', 'reorder-post-within-categories' ); ?></span>
					</span>
				</p>
				<div id="slider-range" data-max="<?php echo esc_attr( $total ); ?>"></div>
				<p class="instructions">
					<?php
					echo wp_kses_post(
						sprintf(
							/* translators: CTRL / SHIFT */
							__( '<em>Use</em> %1$s <em>and/or</em> %2$s keys to select multiple items.', 'reorder-post-within-categories' ),
							'<strong>CTRL</strong>',
							'<strong>SHIFT</strong>'
						)
					);
					?>
				</p>
				<div id="sortable-list" class="order-list" rel ="<?php echo esc_attr( $cat_to_retrieve_post ); ?>" data-count="<?php echo esc_attr( $total ); ?>">
					<?php
					foreach ( $ranking as $idx => $pid ) :
						$p   = $posts[ $pid ];
						$img = Reorder_Post_Within_Categories_Admin::get_thumbnail_url( $p );
						?>

					<div data-id="<?php echo esc_attr( $pid ); ?>" class="sortable-items">
						<img src="<?php echo esc_url( $img ); ?>">
						<span class="title <?php echo esc_attr( $p->post_status ); ?>">
							<a href="<?php echo esc_url( admin_url( 'post.php?post=' . $pid . '&action=edit' ) ); ?>">
							<?php echo wp_kses_post( apply_filters( 'reorder_posts_within_category_card_text', get_the_title( $p ), $p, $cat_to_retrieve_post ) ); ?>
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
