jQuery( function( $ ){
    
	if ( typeof edostavka_params === 'undefined' ) {
		return false;
	}
	
	if( ! edostavka_params.format_city ) {
		edostavka_params.format_city = '%city_name% (%state%)';
	}
			
	var load_autocomplate_states = function() {

        try {
			
			var self = $( '#billing_city, #shipping_city' ),
				billing_country = typeof $('#billing_country').val() !== 'undefined' ? $('#billing_country').val() : edostavka_params.country_iso,
				input_name = self.attr( 'name' ),
				input_id = self.attr( 'id' ),
				input_class = self.attr( 'class' ),
				enable_dropdown = 'yes' == edostavka_params.dropdown_cities_list ? $.inArray( billing_country, edostavka_params.allowed_zone_locations ) >= 0 : true;
				
			if( enable_dropdown && self.length > 0 && $().select2 ) {
						
				var edostavka_request = {
					billing_country : billing_country,
					xhr: false,
					init_edostavka: function(){								
						
						if( self.is( 'input' ) ) {
							self.replaceWith( '<select name="' + input_name + '" id="' + input_id + '" class="select " placeholder="' + edostavka_params.i18n_strings.select_city + '"></select>' );
							self = $( '#' + input_id );
							
							$.post( wc_checkout_params.wc_ajax_url.toString().replace( '%%endpoint%%', 'edostavka_get_city_by_id' ), {
								city_id: $('#billing_state_id').length > 0 ? $('#billing_state_id').val() : edostavka_params.customer_state_id
							}, function( data ) {
								if( data.success && data.data && data.data.country == billing_country ) {
									self.html( $( '<option />', { value: data.data.city_name, text: data.data.city_name, selected:true } ) );
								}
							} );
							
						}
						
						self.on( 'select2:selecting', function( event ) {
							
							$('#billing_state_id').val( event.params.args.data.city_id );
							$('#billing_state, #shipping_state').val( event.params.args.data.state );
									
							if( 'yes' != edostavka_params.enable_custom_city ) {
								$('#billing_country').val( event.params.args.data.country ).trigger( 'change' );
							}
									
							$( document.body ).trigger( 'update_checkout' );
						
						} ).select2( {
							placeholder: self.attr( 'placeholder' ) !== '' ? self.attr( 'placeholder' ) : edostavka_params.i18n_strings.select_city,
							placeholderOption: 'first',
							width: '100%',
							ajax: {
								url: edostavka_params.ajax_url,
								method: 'POST',
								dataType: 'json',
								delay: 250,
								data: function ( params ) {
									return {
										city_name: params.term,
										country: edostavka_request.billing_country
									};
										  
								},
								transport: function transport( xhr_params, success, failure ) {
										
									if ( edostavka_request.xhr ) {
										edostavka_request.xhr.abort();
									}
									
									edostavka_request.xhr = $.ajax( xhr_params );
									edostavka_request.xhr.then( success );
									edostavka_request.xhr.fail( failure );
											
									return edostavka_request.xhr;
								},
								processResults: function ( data, params ) {
									var terms = [];
									params.page = params.page || 1;
									
									if( data.success && data.data ) {
										
										$.each( data.data, function( id, item ) {
											
											if( 'yes' == edostavka_params.only_current_country && item.country != edostavka_request.billing_country ) {
												return;
											}
											
											terms.push( {
												id: 36447 == item.city_id ? item.city_name + ' (' + item.state + ')' : item.city_name,
												title: item.full_name || null,
												city_id: item.city_id,
												state: item.state,
												full_name: item.full_name || null,
												country: item.country,
												text: edostavka_params.format_city.replace( "%city_name%", item.city_name ).replace( "%state%", item.state )
											} );
										} );
									}
									
									return {
										results: terms,
										pagination: {
											more: ( params.page * 30 ) < Object.keys( data.data ).length
										}
									};
								},
								cache: true
							},
							templateSelection: function( item ) {
								return item.id || item.text;
							},
							templateResult: function( item ) {
								
								if ( item.loading || item.disabled || ! item.full_name ) {
									return item.text;
								}
								
								return $( [
									'<div class="select2-result-item">',
									item.text,
									'<br />',
									'<small style="color:#cbcbcb;">',
									item.full_name,
									'</small>',
									'</div>'
								].join('') );
							},
							tags: 'yes' == edostavka_params.enable_custom_city,
							minimumInputLength: 0,
							language: {
								errorLoading: function() {
									return wc_country_select_params.i18n_searching;
								},
								formatAjaxError: function( jqXHR, textStatus, errorThrown ) {
									return wc_country_select_params.i18n_ajax_error;
								},
								inputTooLong: function( args ) {
									var overChars = args.input.length - args.maximum;

									if ( 1 === overChars ) {
										return wc_country_select_params.i18n_input_too_long_1;
									}

									return wc_country_select_params.i18n_input_too_long_n.replace( '%qty%', overChars );
								},
								inputTooShort: function( args ) {
									var remainingChars = args.minimum - args.input.length;

									if ( 1 === remainingChars ) {
										return wc_country_select_params.i18n_input_too_short_1;
									}

									return wc_country_select_params.i18n_input_too_short_n.replace( '%qty%', remainingChars );
								},
								loadingMore: function() {
									return wc_country_select_params.i18n_load_more;
								},
								maximumSelected: function( args ) {
									if ( args.maximum === 1 ) {
										return wc_country_select_params.i18n_selection_too_long_1;
									}

									return wc_country_select_params.i18n_selection_too_long_n.replace( '%qty%', args.maximum );
								},
								noResults: function() {
									return wc_country_select_params.i18n_no_matches;
								},
								searching: function( args ) {
									return ( args.term && args.term.length > 1 ) ? wc_country_select_params.i18n_searching : 'Загрузка списка городов...';
								}
							}
						} );
					}
				};
						
				edostavka_request.init_edostavka();

			} else if( ! enable_dropdown ) {
				if ( self.hasClass( 'select2-hidden-accessible' ) ) {
					//self.select2( 'destroy' );
				}
				
				self.filter( '.select2-hidden-accessible' ).select2( 'destroy' );
				
				if( self.is( 'select' ) ) {
					
					self.replaceWith( '<input type="text" class="input-text" name="' + input_name + '" id="' + input_id + '" placeholder="' + edostavka_params.i18n_strings.enter_sity_name + '" />' );
				}
			}
		} catch( error ) {
			console.log( error );
		}
    }
	
	var edostavka_select_select2 = function() {
					
		$( 'select.select-delivery-points' ).each( function() {

			$( this ).on( 'select2:selecting', function( event ) {
							
				$( '#billing_address_1' ).val( event.params.args.data.text ).trigger( 'change' );
				
				$.post( edostavka_params.set_delivery_point_ajax, { code: event.params.args.data.id, type: event.params.args.data.element.dataset.type, address: event.params.args.data.text }, function( data ) {
					//console.log( data );
				} );
			} ).selectWoo( {
				placeholderOption: 'first',
				width: '100%',
				templateResult: function( point ) {
					if( point.element ) {
						var data = $( point.element ).data();
						return $( [
							'<div class="select2-result-point">',
							point.text,
							data.description ? '<br /><small>' + data.description + '</small>' : '',
							'</div>'
						].join('') );
					}
					return point.text;
				}
			} );
						
			$( this ).on( 'select2:select', function() {
				$( this ).focus();
			} );
		} );
	};	
	
	$( document.body ).on( 'updated_checkout', function(){
		
		load_autocomplate_states();
		edostavka_select_select2();
		
		if( edostavka_params.map_position == 'under_address' ) {
			
			if ( ! $( '#edostavka_map' ).is(':empty') ){
				$( '#edostavka_map' ).trigger( 'WDYandexMap.destroy' );
			}
			
			$( '#edostavka_map' ).WDYandexMap( {
				points_url: edostavka_params.points_url,
				set_delivery_point_ajax: edostavka_params.set_delivery_point_ajax,
				city_id: $('#billing_state_id').length > 0 ? $('#billing_state_id').val() : edostavka_params.customer_state_id,
				delivery_type: $( '#edostavka_map' ).data( 'delivery_type' ),
				postamat_icon: edostavka_params.postamat_icon,
				pvz_icon: edostavka_params.pvz_icon,
				current_pvz: edostavka_params.chosen_delivery_point,
				disable_postcode_field: edostavka_params.disable_postcode_field,
				show_search_field: edostavka_params.show_search_field,
				need_update_checkout: false,
				search_control_placeholder: edostavka_params.i18n_strings.search_control_placeholder
			} );
		}
		
				
		var $form = $( 'form[name="checkout"]' );
				
		if( $( '#billing_state_id', $form ).length == 0 ) {
			$form.append( $( '<input type="hidden" name="billing_state_id" id="billing_state_id" value="' + edostavka_params.customer_state_id + '" />' ) );
		}
				
		$( '#billing_address_1' ).bind( 'keyup blur', function( e ) {
			$( '#billing_to_door_address' ).val( e.target.value );
		} );
	} );
} );