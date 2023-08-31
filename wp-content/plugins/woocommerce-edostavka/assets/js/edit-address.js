(function( $, d, address_params ){
	
	var $billing_city = $( '#billing_city' );
	
	if( $billing_city.length > 0 && $().select2 ) {
		
		var $form = $billing_city.closest( 'form' );
		
		if( $( '#billing_state_id', $form ).length === 0 ) {
			$form.append( '<input type="hidden" name="billing_state_id" id="billing_state_id" value="' + address_params.state_id + '" />' );
		}
		
		$billing_city.on( 'select2:selecting', function( event ) {
			$('#billing_state_id').val( event.params.args.data.city_id );
			$('#billing_state, #shipping_state').val( event.params.args.data.state );
			$('#billing_country').val( event.params.args.data.country );
		}).select2({
			placeholder: 'Выберите город',
			placeholderOption: 'first',
			width: '100%',
			escapeMarkup: function( m ) {
				return m;
			},
			ajax: {
				url: address_params.ajax_url,
				method: 'POST',
				dataType: "json",
				delay: 250,
				data: function ( params ) {
					return {
						city_name: params.term,
						country: $( '#billing_country' ).val()
					};
				},
				processResults: function ( data ) {
					var terms = [];
					if( data.success && data.data ) {
						$.each( data.data, function( id, item ) {
							if( ! item || item.country == null || item.country !== $( '#billing_country' ).val() ) return;
							terms.push( {
								id: item.city_name,
								city_id: item.city_id,
								state: item.state,
								country: item.country,
								text: item.city_name + ' (' + item.state + ')'
							} );
						} );
					}
											
					return {
						results: terms
					};
				},
				cache: false,
			},
			tags: true,
			minimumInputLength: 3,
			language: {
				errorLoading: function() {
					return address_params.i18n_searching;
				},
				inputTooLong: function( args ) {
					var overChars = args.input.length - args.maximum;

					if ( 1 === overChars ) {
						return address_params.i18n_input_too_long_1;
					}

					return address_params.i18n_input_too_long_n.replace( '%qty%', overChars );
				},
				inputTooShort: function( args ) {
					var remainingChars = args.minimum - args.input.length;

					if ( 1 === remainingChars ) {
						return address_params.i18n_input_too_short_1;
					}

					return address_params.i18n_input_too_short_n.replace( '%qty%', remainingChars );
				},
				loadingMore: function() {
					return address_params.i18n_load_more;
				},
				maximumSelected: function( args ) {
					if ( args.maximum === 1 ) {
						return address_params.i18n_selection_too_long_1;
					}

					return address_params.i18n_selection_too_long_n.replace( '%qty%', args.maximum );
				},
				noResults: function() {
					return address_params.i18n_no_matches;
				},
				searching: function() {
					return address_params.i18n_searching;
				}
			}
		});
	}

})( jQuery, document, edostavka_edit_address_params );