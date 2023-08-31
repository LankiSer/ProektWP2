( function( $, w, d, wp, params ){
	
	$( function() {
	
		var MapModel = Backbone.Model.extend(),
			MapView = Backbone.View.extend( {
				initialize: function() {
					
					$( d.body ).on( 'wc_backbone_modal_loaded', { view: this }, this.onModalLoaded );
					$( d.body ).on( 'click', '.wc-edostavka-choose-delivery-point', { view: this }, this.onModalInit );
					$( d.body ).on( 'wc_backbone_modal_removed', this.onModalRemoved );
					//$( d.body ).on( 'updated_checkout', this.onModalRemoved );
				},
				onModalLoaded: function( event, target ) {
					$( '#wc-edostavka-map-container' ).trigger( 'WDYandexMap.destroy' );
					event.data.view.render();
				},
				render: function() {
					$( '.modal-map-container' ).empty();
					this.mapLoad();
				},
				onModalInit: function( event ) {
					event.preventDefault();
					//event.data.view.onModalRemoved();
							
					$( this ).WCBackboneModal( { template : 'wc-modal-edostavka-map' } );
				},
				onModalRemoved:function() {
					$( '#wc-edostavka-map-container' ).trigger( 'WDYandexMap.destroy' );
				},
				mapLoad: function() {
					
					$( '#wc-edostavka-map-container' ).WDYandexMap( {
						points_url: params.points_url,
						set_delivery_point_ajax: params.set_delivery_point_ajax,
						city_id: $( 'button.wc-edostavka-choose-delivery-point' ).data( 'city_id' ),
						delivery_type: $( 'button.wc-edostavka-choose-delivery-point' ).data( 'delivery_type' ),
						postamat_icon: params.postamat_icon,
						pvz_icon: params.pvz_icon,
						current_pvz: params.chosen_delivery_point,
						disable_postcode_field: params.disable_postcode_field,
						show_search_field: params.show_search_field,
						need_update_checkout: true,
						search_control_placeholder: params.i18n_strings.search_control_placeholder,
						hide_modal_on_close_confirm: function() {
							$( '.modal-close' ).click();
						}
					} );
				}
				
			} ),
			mapView = new MapView( { model: new MapModel() } );
	
	} );
	
} )( jQuery, window, document, wp, edostavka_params );