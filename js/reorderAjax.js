jQuery(document).ready(function() {
	initSelectCategory();
	
	
	initMainForm();
});



/**
 * Initialise le formulaire principale :
 * - rend la liste des articles triable
 * - Au clic sur un des boutons radio, on enregistre la préférence concernée *
 */
function initMainForm(){
	
	// On rend la liste triable
	jQuery("#sortable-list").sortable(
		{
			 update: function( event, ui ) {
				
				jQuery('#spinnerAjaxUserOrdering').show();
				
				data = {
					'action'					: 'user_ordering',
					'order'						: jQuery(this).sortable('toArray').toString(),
					'category'					: jQuery(this).attr("rel"),
					'deefuseNounceUserOrdering'	: deefusereorder_vars.deefuseNounceUserOrdering
				}
				jQuery.post(ajaxurl, data, function (response){
					//alert(response);
					jQuery('#spinnerAjaxUserOrdering').hide();
				});
			 }
		}
	);
	
	// Au clic sur les boutons radio on enrehistre les préférences //1,9,11,7,14
	jQuery("#form_result input.option_order").change(function (){
		jQuery('#spinnerAjaxRadio').show();
		
		if(jQuery("#form_result input.option_order:checked").val() ==  "true" && jQuery("#sortable-list li").length >=2){
			jQuery('#spinnerAjaxUserOrdering').show();
			
			data = {
				'action'					: 'user_ordering',
				'order'						: jQuery("#sortable-list").sortable('toArray').toString(),
				'category'					: jQuery("#sortable-list").attr("rel"),
				'deefuseNounceUserOrdering'	: deefusereorder_vars.deefuseNounceUserOrdering
			}
			jQuery.post(ajaxurl, data, function (response){
				//alert(response);
				jQuery('#spinnerAjaxUserOrdering').hide();
			});
			
		}
		
		
		jQuery("#form_result input.option_order").attr('disabled', 'disabled');
		
		data = {
			'action'				: 'cat_ordered_changed',
			'current_cat'			: jQuery("#termIDCat").val(),
			'valueForManualOrder'	: jQuery("#form_result input.option_order:checked").val(),
			'deefuseNounceOrder'	: deefusereorder_vars.deefuseNounceCatReOrder
		}
		
		jQuery.post(ajaxurl, data, function (response){
			jQuery('#debug').html(response);
			jQuery('#spinnerAjaxRadio').hide();
			jQuery("#form_result input.option_order").attr('disabled', false);
		});
		
		return false;
	})
}

/**
 * Initialise le comportement JavaScript lors du choix de catégorie (premier formulaire)
 * Au changement, on stocke le slug de la taxonomie concerné dans un champs caché
 * et on soulet le formulaire
 */
function initSelectCategory(){
	jQuery("#selectCatToRetrieve").change(
		function(event){
			var taxonomy = jQuery("#selectCatToRetrieve option:selected").parent().attr("id");
			jQuery("#taxonomyHiddenField").val(taxonomy);
			
			jQuery("form#chooseTaxomieForm").submit();
		}
	);
}