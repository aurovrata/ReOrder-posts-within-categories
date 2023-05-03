(function( $ ) {
	'use strict';

	 let $sortable;
	$( document ).ready(
		function() {
			$sortable = $( '#sortable-list' );
			initSelectCategory();
			initMainForm();
			/** @since 2.5.7 fix refresh issue on open admin reorder tags. */
			let hidden, visibilityState, visibilityChange;
			if (typeof document.hidden !== "undefined") {
				hidden = "hidden", visibilityChange = "visibilitychange", visibilityState = "visibilityState";
			} else if (typeof document.msHidden !== "undefined") {
				hidden = "msHidden", visibilityChange = "msvisibilitychange", visibilityState = "msVisibilityState";
			}
			let document_hidden = document[hidden];
			document.addEventListener(
				visibilityChange,
				function() {
					if (document_hidden != document[hidden]) {
						if ( ! document[hidden]) {
							$( "form#chooseTaxomieForm" ).submit();
						}
						document_hidden = document[hidden];
					}
				}
			);
		}
	);

	function sortableItems(){
		let min = 0;
		if ($( "#range-min" ).is( ':visible' )) {
			min = $( "#range-min" ).val() * 1 - 1;
		}

		// $sortable.sortable({
		if ('undefined' == typeof $sortable[0]) {
			return;
		}
		new Sortable(
			$sortable[0],
			{
				handle:'img',
				animation:150,
				dataIdAttr: 'data-id',
				multiDrag: true, // Enable multi-drag
				selectedClass: 'selected',
				multiDragKey: 'CTRL',
				onSelect: function(event){ // enable shift of items.
				},
				onDeselect: function(event){ // if no more selected, disable shift.
					// console.log('deselecting...');
					// console.log(event);
				},
				onUpdate: function( event ) {
					$( '#spinnerAjaxUserOrdering' ).addClass( 'is-active' );

					let data = {
						'action'					: 'user_ordering',
						'order'						: this.toArray().toString(),
						'start'           : min,
						'category'				: $( this.el ).attr( "rel" ),
						'taxonomy'        : $( "#taxonomyHiddenField" ).val(),
						'post_type'       : $( "#post-type" ).val(),
						'deefuseNounceUserOrdering'	: rpwc2.deefuseNounceUserOrdering
					}
					$.post(
						ajaxurl,
						data,
						function (response){
							$( '#spinnerAjaxUserOrdering' ).removeClass( 'is-active' );
						}
					);
				},
				onRemove:function(event){
				}
			}
		);
	}
	function updateSortableList(response){
		Sortable.get( $sortable[0] ).destroy();
		$sortable.html( '' );
		for (let idx in response.data) {
			let $html = $( '<div data-id="' + response.data[idx].id + '" class="sortable-items"></div>' );
			$html.append( $( '<img src="' + response.data[idx].img + '">' ) );
			$html.append(
				$(
					'<span class="title ' + response.data[idx].status + '">'
				).append( $( '<a href="' + response.data[idx].link + '">' + response.data[idx].title + '</a>' ) )
			);
			$sortable.append( $html );
		}
		sortableItems();
	}

	 /** @since 2.0.0.
	  * retrieve more posts for sorting.
	  */
	function updatePosts(start, end, reset=false){

		$( '#spinnerAjaxUserOrdering' ).addClass( 'is-active' );
		let total = $sortable.data( 'count' );
		let data  = {
			'action'					: 'get_more_posts',
			'start'					:start - 1,
			'offset'         :end - start + 1, /*+1 to include upper limit*/
			'term'           : $( '#selectCatToRetrieve' ).val(),
			'post-type'      : $( '#post-type' ).val(),
			'taxonomy'        : $( "#taxonomyHiddenField" ).val(),
			'deefuseNounceUserOrdering'	: rpwc2.deefuseNounceUserOrdering
		}
		if (reset) {
			data['reset'] = true;
		}
		$.post(
			ajaxurl,
			data,
			function (response){
				$( '#spinnerAjaxUserOrdering' ).removeClass( 'is-active' );
				updateSortableList( response );
			}
		);
	}

	 /**
	  * Initialise le formulaire principale :
	  * - rend la liste des articles triable
	  * - Au clic sur un des boutons radio, on enregistre la préférence concernée *
	  */
	function initMainForm(){
		let sliderChange, lowerRange = 1, upperRange = 20,
			$removeItems             = $( '#remove-items' ),
			$rangeMin                = $( "#range-min" ),
			$rangeMax                = $( "#range-max" ),
			$slider                  = $( "#slider-range" ),
			$reset                   = $( 'input#enable-reset' ),
			$resetButton             = $( 'div#reset-order' ).find( 'div a.button' ),
			totalPosts               = $( "#slider-range" ).data( 'max' ),
			$override                = $( '#override-orderby' ),
			$start                   = $( '#rpwc2-post-start' ),
			$end                     = $( '#rpwc2-post-end' );

		lowerRange   = $start.val() * 1.0;
		upperRange   = $end.val() * 1.0;
		sliderChange = false;
		if (totalPosts > (upperRange - lowerRange + 1)) {
			$( "#slider-range" ).slider(
				{
					range: true,
					min: 1,
					max: totalPosts,
					values: [ lowerRange, upperRange ],
					slide: function( event, ui ) {
						sliderChange = true;
						let gridw    = $sortable.width() / $sortable.children().first().outerWidth( true );
						gridw        = Math.floor( gridw );
						gridw        = gridw * gridw - 1;
						let low      = ui.value, hi = ui.values[1] - 1;
						if (ui.values[1] - ui.values[0] > gridw) {
							if (ui.value == ui.values[1]) {
								low = ++ui.values[0];
								hi  = ui.value;
							}
							$( this ).slider( 'option','values',[low, hi] );
						}
						$( "#range-min" ).val( ui.values[ 0 ] );
						$start.val( (ui.values[ 0 ]) );
						$( "#range-max" ).val( ui.values[ 1 ] );
						$end.val( ui.values[ 1 ] );

					},
					stop: function( event, ui) {
						updatePosts( ui.values[ 0 ], ui.values[ 1 ] );
					}
				}
			);
			$rangeMin.val( $slider.slider( "values", 0 ) );
			$rangeMax.val( $slider.slider( "values", 1 ) );
				$( ".input-range" ).on(
					'change',
					function(){
						if (sliderChange) {
							sliderChange = false;
							return;
						} else {
							updatePosts( $rangeMin.val(), $rangeMax.val() );
						}
						switch (true) {
							case $( this ).is( '#range-min' ):
								$slider.slider( "values", 0, $( this ).val() );
								break;
							case $( this ).is( '#range-max' ):
								$slider.slider( "values", 1, $( this ).val() );
								break;
						}
					}
				);
				// show insert order input.
				$removeItems.show();
		} else {
			$slider.hide();
			$( "#range-text" ).hide();
		}
		$reset.on(
			'click',
			function(e){
				if ($reset.is( ':checked' )) {
					$resetButton.removeClass( 'disabled' );
				} else {
					$resetButton.addClass( 'disabled' );
				}
			}
		);
		$resetButton.on(
			'click',
			function(e){
				if ($resetButton.is( '.disabled' )) {
					return false;
				}
				updatePosts( 1,-5, true ); // negative will take all.
			}
		);
		// On rend la liste triable.
		sortableItems();
		// Au clic sur les boutons radio on enrehistre les préférences //1,9,11,7,14
		$( '#catOrderedRadioBox' ).change(
			'input.settings',
			function (event){
				/** @since 2.5.10 */
				let $yes = $( 'input#yes', $( this ) ), order = 'false',
				$radio   = $( 'input.option_order', $( this ) );

				$( '#spinnerAjaxRadio' ).show();

				if ( $yes.is( ':checked' ) ) {
					$override.prop( 'disabled',false );
					order = 'true';
				} else {
					$override.prop( 'disabled',true );
				}

				$radio.prop( 'disabled', true );

				let data = {
					'action'				: 'cat_ordered_changed',
					'current_cat'			: $( "#termIDCat" ).val(),
					'post_type'       : $( "#post-type" ).val(),
					'valueForManualOrder'	: order,
					'override'        : $override.is( ':checked' ),
					'deefuseNounceOrder'	: rpwc2.deefuseNounceCatReOrder
				}

				$.post(
					ajaxurl,
					data,
					function (response){
						$( '#debug' ).html( response );
						$( '#spinnerAjaxRadio' ).hide();
						$radio.prop( 'disabled', false );
					}
				);

				return false;
			}
		);

		$( 'input[name="insert-order"]', $removeItems ).on(
			'pointerup mouseup touchend',
			function(e){
				e.stopPropagation();
			}
		);

		$removeItems.on(
			'change',
			'input[name="insert-order"]',
			function(event){
				let $this = $( event.target ),
				rank      = $this.val() * 1,
				min       = $rangeMin.val() * 1,
				max       = $rangeMax.val() * 1;
				let $msg  = $this.parent().next( 'span.error' ).text( '' );
				if ('' == $this.val()) {
					return;
				}
				if ((rank >= min && $this.val() <= max) || rank < 1 || rank > $this.attr( 'max' ) * 1) {
					$msg.text( rpwc2.insertRange );
					$this.val( '' );
					return;
				} else { // if value is valid, remove items and move them
					$( '#spinnerAjaxUserOrdering' ).addClass( 'is-active' );
					let items     = [], first, last, move = '';
					let $selected = $sortable.children( '.selected' );
					if (0 == $selected.length) {
						$msg.text( rpwc2.noselection );
						$this.val( '' );
						return;
					}
					$selected.each(
						function(){
							  items[items.length] = $( this ).data( 'id' );
						}
					);
					first = $sortable.children( '.sortable-items' ).index( $selected.get( 0 ) ) + min;
					last  = $sortable.children( '.sortable-items' ).index( $selected.get( items.length - 1 ) ) + min;

					if (rank < min) {
						first = rank; // move up  the order.
						move  = 'up';
					} else if (rank > max) {
						last = rank; // move down the order.
						move = 'down';
					}
					let data = {
						'action'		: 'user_shuffle',
						'items'			: items,
						'start'     : first,
						'end'       : last,
						'move'      : move,
						'range_start' : min - 1,
						'offset'    : max - min + 1,
						'post'      : $( '#post-type' ).val(),
						'category'	: $sortable.attr( "rel" ),
						'valueForManualOrder'	: $( "#form_result input.option_order:checked" ).val(),
						'deefuseNounceUserOrdering'	: rpwc2.deefuseNounceUserOrdering
					}
					$.post(
						ajaxurl,
						data,
						function (response){
							$( '#spinnerAjaxUserOrdering' ).removeClass( 'is-active' );
							// update sortable items.
							updateSortableList( response );
							$this.val( '' );
						}
					);
				}
			}
		);
	}//end initMainForm

	 /**
	  * Initialise le comportement JavaScript lors du choix de catégorie (premier formulaire)
	  * Au changement, on stocke le slug de la taxonomie concerné dans un champs caché
	  * et on soulet le formulaire
	  */
	function initSelectCategory(){
		$( "#selectCatToRetrieve" ).prop( 'disabled', false ).change(
			function(event){
				let taxonomy = $( "#selectCatToRetrieve option:selected" ).parent().attr( "id" );
				$( "#taxonomyHiddenField" ).val( taxonomy );
				$( "form#chooseTaxomieForm" ).submit();
			}
		)}
})( jQuery );
