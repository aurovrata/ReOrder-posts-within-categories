(function( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */
	 $(document).ready(function() {
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
	 	$("#sortable-list").sortable(
	 		{
	 			 update: function( event, ui ) {

	 				$('#spinnerAjaxUserOrdering').show();

	 				var data = {
	 					'action'					: 'user_ordering',
	 					'order'						: $(this).sortable('toArray').toString(),
	 					'category'					: $(this).attr("rel"),
	 					'deefuseNounceUserOrdering'	: rpwc2.deefuseNounceUserOrdering
	 				}
	 				$.post(ajaxurl, data, function (response){
	 					//alert(response);
	 					$('#spinnerAjaxUserOrdering').hide();
	 				});
	 			 }
	 		}
	 	);

	 	// Au clic sur les boutons radio on enrehistre les préférences //1,9,11,7,14
	 	$("#form_result input.option_order").change(function (){
	 		$('#spinnerAjaxRadio').show();

	 		if($("#form_result input.option_order:checked").val() ==  "true" && $("#sortable-list li").length >=2){
	 			$('#spinnerAjaxUserOrdering').show();

	 			var data = {
	 				'action'					: 'user_ordering',
	 				'order'						: $("#sortable-list").sortable('toArray').toString(),
	 				'category'					: $("#sortable-list").attr("rel"),
	 				'deefuseNounceUserOrdering'	: rpwc2.deefuseNounceUserOrdering
	 			}
	 			$.post(ajaxurl, data, function (response){
	 				//alert(response);
	 				$('#spinnerAjaxUserOrdering').hide();
	 			});

	 		}


	 		$("#form_result input.option_order").attr('disabled', 'disabled');

	 		var data = {
	 			'action'				: 'cat_ordered_changed',
	 			'current_cat'			: $("#termIDCat").val(),
	 			'valueForManualOrder'	: $("#form_result input.option_order:checked").val(),
	 			'deefuseNounceOrder'	: rpwc2.deefuseNounceCatReOrder
	 		}

	 		$.post(ajaxurl, data, function (response){
	 			$('#debug').html(response);
	 			$('#spinnerAjaxRadio').hide();
	 			$("#form_result input.option_order").attr('disabled', false);
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
	 	$("#selectCatToRetrieve").change(
	 		function(event){
	 			var taxonomy = $("#selectCatToRetrieve option:selected").parent().attr("id");
	 			$("#taxonomyHiddenField").val(taxonomy);

	 			$("form#chooseTaxomieForm").submit();
	 		}
	 	);
	 }
})( jQuery );
