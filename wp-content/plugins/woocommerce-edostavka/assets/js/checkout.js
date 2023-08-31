( function($) {
	
	if( 'yes' == edostavka_params.suggestions_address ) {
		
		$( document.body ).on( 'updated_checkout', function(){
			
			$( '#billing_address_1' ).autocomplete( {
				
				source: function( request, response ) {
				
					fetch( wc_checkout_params.wc_ajax_url.toString().replace( '%%endpoint%%', 'edostavka_get_suggestions_address' ), {
						method: "POST",
						headers: {
							"Content-Type": "application/json",
							"Accept": "application/json"
						},
						body: JSON.stringify( {
							country: $( '#billing_contry' ).val() || 'RU',
							state: $( '#billing_state' ).val(),
							city: $( '#billing_city' ).val(),
							address: request.term
						} )
					} ).then( response => response.json() ).then( function( result ) {
						//console.log(result);
						if( result.success ) {
							response( result.data );
						} else {
							response( {} );
							console.error( result.data );
						}
					} ).catch( error => console.error( "Ошибка при запросе поучения данных о адресе", error ) );
				},
				select: function( event, ui ) {
					if( edostavka_params.suggestions_fill_postcode && ui.item && ui.item.data && ui.item.data.postal_code && $( '#billing_postcode' ).length > 0 ) {
						$( '#billing_postcode' ).val( ui.item.data.postal_code ).change();
					}
				},
				minLength: 2,
			} );
		} );
		
	}
	
	if( 'yes' == edostavka_params.add_insurance_cost ) {
		
		$( 'form.checkout' ).on( 'change', 'input[name^="payment_method"]', function() {
			$( document.body ).trigger( 'update_checkout', { update_shipping_method: true } );
		} );
	}

} )( jQuery );