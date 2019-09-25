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
	 var $sortable=$();
	 $(document).ready(function() {
		$sortable = $('#sortable-list');
	 	initSelectCategory();
	 	initMainForm();
	 });

	 function sortableItems(){
		 var min = 0;
		 if($( "#range-min" ).is(':visible')) min = $( "#range-min" ).val()*1-1;

		 $sortable.sortable({
 			handle:'img',
 			animation:150,
 			dataIdAttr: 'data-id',
 			multiDrag: true, // Enable multi-drag
 			selectedClass: 'selected',
 			multiDragKey: 'CTRL',
 			onSelect: function(event){ //enable shift of items.
 			},
 			onDeselect: function(event){ //if no more selected, disable shift.
				console.log('deselecting...');
				console.log(event);
 			},
 	 		onUpdate: function( event ) {

 	 			$('#spinnerAjaxUserOrdering').show();

  				var data = {
  					'action'					: 'user_ordering',
  					'order'						: this.toArray().toString(),
 					  'start'           : min,
  					'category'				: $(this.el).attr("rel"),
  					'deefuseNounceUserOrdering'	: rpwc2.deefuseNounceUserOrdering
  				}
  				$.post(ajaxurl, data, function (response){
  					$('#spinnerAjaxUserOrdering').hide();
  				});
  			},
 			onRemove:function(event){
 			}
 	 	});
	 }
	 function updateSortableList(response){
		 $sortable.sortable('destroy');
		 $sortable.html('');
		 for (var idx in response.data) {
			 var $html = $('<div data-id="'+response.data[idx].id+'" class="sortable-items"></div>');
			 $html.append($('<img src="'+response.data[idx].img+'">'));
			 $html.append($(
				 '<span class="title">').append($('<a href="'+response.data[idx].link+'">'+response.data[idx].title+'</a>')));
			 $sortable.append($html);
		 }
		 sortableItems();
	 }

	 /** @since 2.0.0.
	 *retrieve more posts for sorting.
	 */
	 function updatePosts(start, end, reset=false){

		 $('#spinnerAjaxUserOrdering').show();
		 var total = $sortable.data('count');
		 var data = {
			 'action'					: 'get_more_posts',
			 'start'					:start-1,
			 'offset'         :end-start+1, /*+1 to include upper limit*/
			 'term'           : $('#selectCatToRetrieve').val(),
			 'post-type'      : $('#post-type').val(),
			 'deefuseNounceUserOrdering'	: rpwc2.deefuseNounceUserOrdering
		 }
     if(reset) data['reset'] = true;
		$.post(ajaxurl, data, function (response){
			$('#spinnerAjaxUserOrdering').hide();
			updateSortableList(response);
		 });
	 }

	 /**
	  * Initialise le formulaire principale :
	  * - rend la liste des articles triable
	  * - Au clic sur un des boutons radio, on enregistre la préférence concernée *
	  */
	function initMainForm(){
    var sliderChange, upperRange,
			$removeItems=$('#remove-items'),
			$rangeMin = $( "#range-min" ),
			$rangeMax = $( "#range-max" ),
			$slider = $( "#slider-range" ),
			$reset = $('input#enable-reset'),
			$resetButton = $('div#reset-order').find('div a.button'),
			totalPosts = $( "#slider-range" ).data('max');
    upperRange = 20;
		sliderChange = false;
    if(totalPosts>upperRange){
      $( "#slider-range" ).slider({
        range: true,
        min: 1,
        max: totalPosts,
        values: [ 1, 20 ],
        slide: function( event, ui ) {
					sliderChange = true;
					var gridw = $sortable.width()/$sortable.children().first().outerWidth(true);
					gridw = Math.floor(gridw);
					gridw = gridw*gridw-1;
					var low= ui.value, hi = ui.values[1]-1;
					if(ui.values[1]-ui.values[0]>gridw){
						if(ui.value == ui.values[1]){
							low = ui.values[0]+1;
							hi = ui.value;
						}
						$(this).slider('option','values',[low, hi]);
					}
					$( "#range-min" ).val( ui.values[ 0 ]);
          $( "#range-max" ).val( ui.values[ 1 ]);
        },
				stop: function( event, ui) {
					updatePosts(ui.values[ 0 ], ui.values[ 1 ]);
				}
      });
      $rangeMin.val($slider.slider( "values", 0 ));
      $rangeMax.val($slider.slider( "values", 1 ));
			$( ".input-range" ).on('change', function(){
				if(sliderChange){
					sliderChange = false;
					return;
				}else updatePosts($rangeMin.val(), $rangeMax.val());
				switch(true){
					case $(this).is('#range-min'):
						$slider.slider( "values", 0, $(this).val() );
						break;
					case $(this).is('#range-max'):
						$slider.slider( "values", 1, $(this).val() );
						break;
				}
			});
			//show insert order input.
			$removeItems.show();
    }else{
      $slider.hide();
      $( "#range-text" ).hide();
    }
		$reset.on('click', function(e){
			if($reset.is(':checked')){
				$resetButton.removeClass('disabled');
			}else{
				$resetButton.addClass('disabled');
			}
		});
		$resetButton.on('click', function(e){
			if($resetButton.is('.disabled')) return false;
			updatePosts(1,-5, true); //negative will take all.
		});
	 	// On rend la liste triable.
		sortableItems();
	 	// Au clic sur les boutons radio on enrehistre les préférences //1,9,11,7,14
	 	$("#form_result input.option_order").change(function (){
	 		$('#spinnerAjaxRadio').show();

	 		if($("#form_result input.option_order:checked").val() ==  "true" && $("#sortable-list li").length >=2){
	 			$('#spinnerAjaxUserOrdering').show();

	 			var data = {
	 				'action'					: 'user_ordering',
	 				'order'						: $sortable.sortable('toArray').toString(),
					'start'           :$( "#range-min" ).val()-1,
	 				'category'					: $sortable.attr("rel"),
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
	 	});
		$('input[name="insert-order"]', $removeItems).on('pointerup mouseup touchend', function(e){
			e.stopPropagation();
		});
		$removeItems.on('change','input[name="insert-order"]', function(event){
			var $this = $(event.target),
				rank = $this.val()*1,
				min = $rangeMin.val()*1,
				max = $rangeMax.val()*1;
			var $msg = $this.parent().next('span.error').text('');
			if(''==$this.val()) return;
			if((rank>=min && $this.val()<=max) || rank <1 || rank > $this.attr('max')*1){
				$msg.text(rpwc2.insertRange);
				$this.val('');
				return;
			}else{ //if value is valid, remove items and move them
				$('#spinnerAjaxUserOrdering').show();
				var items=[], first, last, move='';
				var $selected = $sortable.children('.selected');
				if(0==$selected.length){
					$msg.text(rpwc2.insertRange);
					$this.val('');
					return;
				}
        $selected.each(function(){
          items[items.length]=$(this).data('id');
				});
        first = $sortable.children('.sortable-items').index($selected.get(0)) +min;
        last = $sortable.children('.sortable-items').index($selected.get(items.length-1))+min;

        if(rank<min){
					first = rank; //move up  the order.
					move = 'up';
				}else if(rank>max){
					last = rank; //move down the order.
					move = 'down';
				}
	 			var data = {
	 				'action'		: 'user_shuffle',
	 				'items'			: items,
					'start'     : first,
					'end'       : last,
					'move'      : move,
					'range_start' : min-1,
					'offset'    : max-min+1,
					'post'      : $('#post-type').val(),
	 				'category'	: $sortable.attr("rel"),
	 				'deefuseNounceUserOrdering'	: rpwc2.deefuseNounceUserOrdering
	 			}
	 			$.post(ajaxurl, data, function (response){
	 				$('#spinnerAjaxUserOrdering').hide();
          //update sortable items.
					updateSortableList(response);
					$this.val('');
	 			});
			}
		});
	}//end initMainForm

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
