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
 $listCategories = $settingsOptions['categories_checked'][$post_type_detail->name];
 $taxonomies= '';
 $taxonomy= '';
 $term_selected = '';
?>
<div class="wrap">
	<div class="icon32 icon32-posts-<?= $post_type_detail->name;?>" id="icon-edit"><br></div>
  <h2><?= sprintf(__('Manually rank your "%s"', 'reorder-post-within-categories'), $post_type_detail->labels->menu_name);?></h2>
  <p>
  	<?= sprintf(__('Select a taxonomy to sort <b>%s</b>.', 'reorder-post-within-categories'), $post_type_detail->labels->name);?>
  </p>

  <form method="post" id="chooseTaxomieForm">
  <?php
	wp_nonce_field('loadPostInCat', 'nounceLoadPostCat');
	if (count($listCategories) > 0):?>
    <select id="selectCatToRetrieve" name="cat_to_retrive">
      <option value="null" disabled="disabled" selected="selected"><?= __('Select','reorder-post-within-categories')?></option>
    <?php
			$catDisabled = false;
			foreach ($listCategories as $idx=>$categorie) :
				$taxonomies = get_taxonomies(array('name'=> $categorie), 'object');
				if(!isset($taxonomies[$categorie])){ //this taxonomy no longer exists.
					unset($settingsOptions['categories_checked'][$post_type_detail->name][$idx]);
					$this->save_admin_options($settingsOptions);
					continue;
				}
				$taxonomy = $taxonomies[$categorie];
				// On liste maintenant les terms disponibles pour la taxonomie concernÃ©e
				$term_query = array('taxonomy'=>$taxonomy->name);
				$list_terms = get_terms($term_query);
				if (count($list_terms) > 0) :?>
			<optgroup id="<?=$taxonomy->name?>" label="<?=$taxonomy->labels->name?>">
        <?php
					foreach ($list_terms as $term):
						$selected = '';
						if (isset($cat_to_retrieve_post) && ($cat_to_retrieve_post == $term->term_id)) {
							$selected = ' selected = "selected"';
							$term_selected = $term->name;
						}
						$disabled = '';
						if ($term->count < 2) {
							$disabled = ' disabled = "disabled"';
							$catDisabled = true;
						}?>
			  <option <?=$selected.$disabled?> value="<?=$term->term_id?>"><?=$term->name?></option>
			<?php endforeach; //foreach ($list_terms as $term).?>
			</optgroup>
    <?php
      endif; //if (count($list_terms) > 0).
    endforeach; //foreach ($listCategories as $categorie).?>
		</select>
  <?php if ($catDisabled):?>
		<br/><span class="description"><?= __('Greyed-out categories contain too few posts and aren’t available for sorting.', "reorder-post-within-categories")?></span>
	<?php endif;
    $valueTaxonomyField = (isset($taxonomySubmitted) ? $taxonomySubmitted : '');?>
		<input type="hidden" id="taxonomyHiddenField" name="taxonomy" value="<?=$valueTaxonomyField?>"/>
  <?php endif;//if (count($listCategories) > 0).?>
	</form>
	<form id="form_result" method="post">
<?php if (isset($posts_array)):?>
  <input id="post-type" type="hidden" name="post-type" value="<?=$post_type_detail->name?>">

	<div id="result">
		<div id="sorter_box">
			<h3 style="margin: 5px 0"><?= __('Use the manual sorting for this category?', 'reorder-post-within-categories')?></h3>
			<p style="margin:2px 0">
				<?= __('This switches the manual sorting on the front-end on or off.  You can switch it off and manually sort your posts below until the new order is ready and you can then proceed to switch this on to showcase the new order on the front-end.', 'reorder-post-within-categories')?>
			</p>
				<div id="catOrderedRadioBox">
          <?php
					// on regarde si un des radio est cochÃ©
					$checkedRadio1 = $override ='';
					$disabled='disabled="disabled" ';
					$checkedRadio2 = ' checked = "checked" ';
          $type = $post_type_detail->name;
					$tax_options = get_option(RPWC_OPTIONS_2, array());

          if(isset($tax_options[$type]) && isset($tax_options[$type][$cat_to_retrieve_post])){
            if($tax_options[$type][$cat_to_retrieve_post]['order']){
              $checkedRadio1 = $checkedRadio2;
							$checkedRadio2 = '';
							$disabled = '';
            }
						/** @since 2.6.0 enable override setting */
						// debug_msg($tax_options[$type], 'override ');
						if($tax_options[$type][$cat_to_retrieve_post]['override']){
							$override ='checked="checked" ';
						}
          }	?>
					<label for="yes">
            <input type="radio" <?=$checkedRadio1?>class="option_order settings" id="yes" value="true" name="useForThisCat"/>
            <span><?=__('Yes', 'reorder-post-within-categories')?></span>
          </label><br/>
	        <!-- translators: Opposite of Yes -->
					<label for="no">
            <input type="radio" <?=$checkedRadio2?>class="option_order settings" id="no" value="false" name="useForThisCat"/>
            <span><?=__('No', 'reorder-post-within-categories')?></span>
          </label><br/>
          <label for="override-orderby">
            <input type="checkbox" <?=$disabled?><?=$override?>id="override-orderby" class="settings"/>
            <span><?=__('Override &apos;orderby&apos; query attribute, (more <a href="https://wordpress.org/plugins/reorder-post-within-categories/#%0A10.%20my%20posts%20are%20not%20being%20ranked%20on%20the%20front-end%0A">detail</a>)', 'reorder-post-within-categories')?></span>
          </label>
					<p>
						<?=__('<strong>Caution: </strong>Overriding &apos;orderby&apos; query attribute can have important consequences on WooCommerce listings where themes can display products ranked on various parameters such as price.  This option overrides all other sortings, read these <a href="https://wordpress.org/plugins/reorder-post-within-categories/#%0A10.%20my%20posts%20are%20not%20being%20ranked%20on%20the%20front-end%0A">details</a> to see how to gain a finer control over this.','reorder-post-within-categories')?>
					</p>
        </br/>
          <div id="reset-order">
						<h4 style="margin:5px 0"><?=__('Reset the order!', 'reorder-post-within-categories')?></h4>
            <label for="reset-button">
              <input type="checkbox" value="reset-button" id="enable-reset"/>
              <?=__('reset order for all posts, <strong>careful</strong>, this cannot be undone!','reorder-post-within-categories')?>
            </label>
            <div>
							<a class="button disabled"><?=__('Reset order','reorder-post-within-categories')?></a>
						</div>
          </div>
					<input type="hidden" name="termID" id="termIDCat" value="<?=$cat_to_retrieve_post?>">
					<span class="spinner" id="spinnerAjaxRadio"></span>
				</div>
				<h3 class="floatLeft"><?=sprintf( __('Grid of %s, classified as %s:', 'reorder-post-within-categories'), $post_type_detail->labels->name, $term_selected)?></h3>
				<span id="spinnerAjaxUserOrdering" class="spinner"></span>
				<div class="clearBoth"></div>
				<p id="range-text">
					<span class="title"><?=__('Post range:')?></span>
					<input id="range-min" min="1" max="<?=$total -1?>" class="input-range" type="number">&#8212;<input id="range-max" max="<?=$total?>" min="<?=$total -1?>"  class="input-range" type="number"/>
					<span id="remove-items" style="display:none">
						<label for="insert-order"><?= __('Move items to rank:','reorder-post-within-categories')?>
							<input type="number" min="1" max="<?=$total?>" name="insert-order" value=""/>
						</label><span class="error"></span>
						<span class="display-block"><?= __('Select single/multiple items to move out of the current displayed range and insert towards the beginning or end of your list by selecting a suitable rank','reorder-post-within-categories')?></span>
					</span>
				</p>
				<div id="slider-range" data-max="<?=$total?>"></div>
        <p class="instructions"><?= sprintf(__('<em>Use</em> %s <em>and/or</em> %s keys to select multiple items.','reorder-post-within-categories'),'<strong>CTRL</strong>','<strong>SHIFT</strong>')?></p>
				<div id="sortable-list" class="order-list" rel ="<?=$cat_to_retrieve_post?>" data-count="<?=$total?>">
					<?php
					foreach ($ranking as $idx=>$post_id):
						$post = $posts[$post_id];
						$img = get_the_post_thumbnail_url( $post, 'thumbnail' );
						if(!$img) $img = plugin_dir_url(__DIR__).'../assets/logo.png';
						?>

					<div data-id="<?=$post_id?>" class="sortable-items">
						<img src="<?=$img?>">
					 	<span class="title <?=$post->post_status?>">
						 	<a href="<?=admin_url('post.php?post='.$post_id.'&action=edit')?>">
								<?=apply_filters('reorder_posts_within_category_card_text',get_the_title($post), $post, $cat_to_retrieve_post)?>
							</a>
					 	</span>
				</div>
	 <?php	endforeach;?>
			</div>
		</div>
	</div>
<?php endif;?>
</form>
<div id="debug"></div>
</div>
