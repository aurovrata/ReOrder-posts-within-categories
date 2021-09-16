<?php
if (!empty($_POST) && check_admin_referer('updateOptionSettings', 'nounceUpdateOptionReorder') && wp_verify_nonce($_POST['nounceUpdateOptionReorder'], 'updateOptionSettings')) :
  $delete_data = false;
  $delete_table = false;
  if(isset($_POST['delete_data'])) $delete_data=true;
  $post_types = array();
  if(isset($_POST['selection'])) $post_types=array_keys($_POST['selection']);
  $this->_unrank_posts_unused_taxonomy($delete_data, $post_types); //removed ranks for unused taxonomy.
  if(isset($_POST['delete_table'])){
    $delete_table=true;
    $this->_delete_custom_table();
  }
  switch(true):
    case $delete_data:?>
<div class="updated">
  <p>
    <strong><?= __("Data reset complete.", "reorder-post-within-categories");?></strong> <?= __("All manually ranked order data has been deleted inclusing settings options.", "reorder-post-within-categories");?>
  </p>
</div>
<?php
      break;
    case $delete_table:?>
<div class="updated">
  <p>
    <strong><?= __("Old custom table deleted.", "reorder-post-within-categories");?></strong> <?= __("The previous version 1.x custom table used for storing manually ranked orders has been deleted.", "reorder-post-within-categories");?>
  </p>
</div>
<?php
      break;
    default:?>
<div class="updated">
  <p>
    <strong><?= __("Options saved.", "reorder-post-within-categories");?></strong> <?= __("A sub-menu is now placed under each post type menu in your dashboard to access the sorting page.", "reorder-post-within-categories");?>
  </p>
</div>
<?php
      break;
  endswitch;
endif;
$settingsOptions = $this->get_admin_options();?>
<div class="wrap">
  <div class="icon32" id="icon-options-general"><br/></div>
  <h2><?= __('Re-Order Posts within category', 'reorder-post-within-categories');?></h2>
  <form method="post" action="<?= $_SERVER["REQUEST_URI"];?>">
    <?php wp_nonce_field('updateOptionSettings', 'nounceUpdateOptionReorder');?>
    <p>
      <?= __("Select the categories you want to manually sort the items. Once you have checked and confirmed this information, a sub-menu will appear under each post type menu.", "reorder-post-within-categories");?>
    </p>
    <h3><?= __("Post Types available:", "reorder-post-within-categories");?></h3>
    <?php
    // On liste tout les post_types
    //$post_types = get_post_types( array( 'show_in_nav_menus' => true,'public'=>true, 'show_ui'=>true, 'hierarchical' => false ), 'object' );
    /**
    * improve the post selection, select post with taxobnomies only
    * @since 1.2.2
    */
    $args = array('show_ui' => true,); // '_builtin' => false.
    $post_types = get_post_types($args, 'object');
    if ($post_types) :
    // Pour chaque post_type, on regarde s'il y a des taxonomies associÃ©es
    foreach ($post_types as $post_type) :
      $taxonomies = get_object_taxonomies($post_type->name, 'objects');
      if (empty($taxonomies)) continue; //no taxonomies to order post in terms.
      else {
        $taxonomy_ui = false;
        foreach ($taxonomies as $taxonomy) {
          if ($taxonomy->show_ui) {
            $taxonomy_ui = true;
          }
        }
        if (!$taxonomy_ui) continue; //no taxonomies to oder post in terms.
      }
      echo "<strong>" . $post_type->labels->menu_name . "</strong>";
      // Pour chaque taxonomie associÃ© au CPT, on ne liste que celles qui ont la propriÃ©tÃ© hierarchical Ã©gale Ã  1 (ie comme les catÃ©gorie)
      foreach ($taxonomies as $taxonomie) :
        if (!$taxonomie->show_ui) continue;
          $ischecked = '';
          if (isset($settingsOptions['categories_checked'][$post_type->name])) {
              if (in_array($taxonomie->name, $settingsOptions['categories_checked'][$post_type->name])) {
                  $ischecked = ' checked = "checked"';
              }
          }?>
    <p>&nbsp;&nbsp;
      <label>
        <input type="checkbox"<?=$ischecked?> value="<?=$taxonomie->name?>" name="selection[<?=$post_type->name?>][]"><?= $taxonomie->labels->name?>
      </label>
    </p>
      <?php
      endforeach;
    endforeach; //foreach ($post_types as $post_type) {.?>
    <p class="submit">
      <input id="submit" class="button button-primary" type="submit" value="<?=__('Enable manual sorting for selected categories', 'reorder-post-within-categories')?>" name="submit"/>
    </p>
    <h3><?=__('Delete all ranking data and preferences', 'reorder-post-within-categories')?></h3>
    <p class="delete-data submit">
      <label>
        <input type="checkbox" name="confirm_delete" id="confirm-delete" /> <?=__('Check this box to confirm data deletion.', 'reorder-post-within-categories')?>
      </label><br/><br/>
      <script type="text/JavaScript">
        (function($){$('#confirm-delete').on('click', function(){
          $('#delete-data').attr('disabled',!$(this).is(':checked'));
        })})(jQuery)
      </script>
      <input id="delete-data" class="button" type="submit" value="<?=__('Clear all ranking data', 'reorder-post-within-categories')?>" name="delete_data" disabled="disabled"/>
    </p>
    <?php
    // self::$settings = get_option(self::$settings_option_name, array());
    if(isset(self::$settings['upgraded']) && self::$settings['upgraded']):?>
      <h3><?=__('Delete old custom table from plugin v1.x', 'reorder-post-within-categories')?></h3>
      <p class="delete-table submit">
        <label>
          <input type="checkbox" name="confirm_table_delete" id="confirm-table" /> <?=__('Check this box to confirm deletion of the custom table from plugin v1.x.  If you are still testing v2.x then conserve the table so that you may downgrade the plugin in case you come across a bug.', 'reorder-post-within-categories')?>
        </label><br/><br/>
        <script type="text/JavaScript">
          (function($){$('#confirm-table').on('click', function(){
            $('#delete-table').attr('disabled',!$(this).is(':checked'));
          })})(jQuery)
        </script>
        <input id="delete-table" class="button" type="submit" value="<?=__('Delete table', 'reorder-post-within-categories')?>" name="delete_table" disabled="disabled"/>
      </p>
    <?php endif;?>
<?php endif;?>
  </form>
</div>
