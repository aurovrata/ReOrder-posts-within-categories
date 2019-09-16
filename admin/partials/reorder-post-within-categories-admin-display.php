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
	<div class="icon32 icon32-posts-<?= $post_type_detail->name; ?>" id="icon-edit"><br></div>
  <h2><?= sprintf(__('Manually rank your "%s"', 'reorder-post-within-categories'), $post_type_detail->labels->menu_name); ?></h2>
  <p>
  	<?= sprintf(__('Select a taxonomy to sort <b>%s</b>.', 'reorder-post-within-categories'), $post_type_detail->labels->name); ?>
  </p>

  <form method="post" id="chooseTaxomieForm">
  <?php
	wp_nonce_field('loadPostInCat', 'nounceLoadPostCat');
	if (count($listCategories) > 0):?>
    <select id="selectCatToRetrieve" name="cat_to_retrive">
      <option value="null" disabled="disabled" selected="selected"><?= __('Select','reorder-post-within-categories')?></option>
    <?php
			$catDisabled = false;
			foreach ($listCategories as $categorie) :
				$taxonomies = get_taxonomies(array('name'=> $categorie), 'object');
				$taxonomy = $taxonomies[$categorie];

				// On liste maintenant les terms disponibles pour la taxonomie concernÃ©e
				/** @since 1.5.0 allow term query to be filtered.*/
				$term_query = array('taxonomy'=>$taxonomy->name);
				$term_query = apply_filters('reorder_post_within_category_taxonomy_term_query_args', $term_query, $taxonomy->name);
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
    $valueTaxonomyField = (isset($taxonomySubmitted) ? $taxonomySubmitted : '');
  ?>
		<input type="hidden" id="taxonomyHiddenField" name="taxonomy" value="<?=$valueTaxonomyField?>"/>
  <?php endif;//if (count($listCategories) > 0).?>
</form>
<form id="form_result" method="post">
<?php if (isset($posts_array)):?>
	<div id="result">
		<div id="sorter_box">
			<h3><?= __('Use the manual sorting for this category?', 'reorder-post-within-categories')?></h3>
				<div id="catOrderedRadioBox">
          <?php
					// on regarde si un des radio est cochÃ©
					$checkedRadio1 = '';
					$checkedRadio2 = ' checked = "checked"';
					$tax_options = get_option(RPWC_OPTIONS);
					if (isset($tax_options[$cat_to_retrieve_post]) && $tax_options[$cat_to_retrieve_post] == 'true') {
							$checkedRadio1 = $checkedRadio2;
							$checkedRadio2 = '';
					}
          ?>
					<label for="yes">
            <input type="radio"<?=$checkedRadio1?> class="option_order" id="yes" value="true" name="useForThisCat"/>
            <span><?=__('Yes', 'reorder-post-within-categories')?></span>
          </label><br/>
	        <!-- translators: Opposite of Yes -->
					<label for="no">
            <input type="radio"<?=$checkedRadio2?> class="option_order" id="no" value="false" name="useForThisCat"/>
            <span><?=__('No', 'reorder-post-within-categories')?></span>
          </label>
					<input type="hidden" name="termID" id="termIDCat" value="<?=$cat_to_retrieve_post?>">
					<span class="spinner" id="spinnerAjaxRadio"></span>
				</div>
				<h3 class="floatLeft"><?=sprintf( __('List of "%s" posts, classified as "%s":', 'reorder-post-within-categories'), $post_type_detail->labels->name, $term_selected)?></h3>
				<span id="spinnerAjaxUserOrdering" class="spinner"></span>
				<div class="clearBoth"></div>
				<ul id="sortable-list" class="order-list" rel ="<?=$cat_to_retrieve_post?>">
					<?php
					// On liste les posts du tableau $posts_array pour le trie
					foreach ($ranking as $post_id):
						$post = $temp_order[$post_id];
						unset($temp_order[$post_id]);?>

					<li id="<?=$post_id?>">
					 <span class="title">
						 <a href="<?=admin_url('post.php?post='.$post_id.'&action=edit')?>"><?=$post->post_title?></a>
					 </span>
					</li>
				<?php endforeach;
					// On liste maintenant les posts qu'il reste et qui ne sont pas encore dans notre table
					foreach ($temp_order as $post_id => $post) :?>
	  			<li id="<?=$post_id?>">
	  				<span class="title">
	            <a href="<?= admin_url('post.php?post='.$post_id.'&action=edit')?>"><?=$post->post_title?></a>
	          </span>
				  </li>
		<?php endforeach;?>
				</ul>
			</div>
		</div>
<?php endif; ?>
	</form>
	<div id="debug"></div>
</div>
