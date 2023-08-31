
jQuery( function( $ ) {

	var WCedostavkaAdminOrders = {

		init: function() {
			$( document.body )
				.on( 'click', '.edostavka-tracking-code .remove-order', this.removeTrackingCode )
				.on( 'click', '.edostavka-tracking-code .add-order', this.addTrackingCode )
				.on( 'click', '.edostavka-tracking-code .get-print', this.getPrintPDF );
		},

		block: function() {
			$( '#wc_edostavka' ).block({
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6
				}
			});
			
			$( '.tracking-code-result' ).empty();
		},

		unblock: function() {
			$( '#wc_edostavka' ).unblock();
		},
		
		addTrackingFields: function( trackingCode ) {
			var $wrap = $( 'body #wc_edostavka .edostavka-tracking-code' );
			var template = wp.template( 'tracking-code-action' );

			$( '.edostavka-tracking-code__action', $wrap ).remove();
			$wrap.prepend( template( { 'trackingCode': trackingCode } ) );
		},

		addTrackingCode: function( evt ) {
			evt.preventDefault();

			var data = {
				action: 'woocommerce_edostavka_add_tracking_code',
				security: wc_edostavka_orders_admin_params.nonces.add,
				order_id: wc_edostavka_orders_admin_params.order_id,
				service: $( '#edostavka_extra_services' ).val()
			};

			WCedostavkaAdminOrders.block();

			$.ajax({
				type: 'POST',
				url: ajaxurl,
				data: data,
				success: function( response ) {
					if( response.success ) {
						WCedostavkaAdminOrders.addTrackingFields( response.data );
					} else {
						$( '.tracking-code-result' ).html( response.data.reason ? response.data.reason : response.data );
					}
					WCedostavkaAdminOrders.unblock();
				}
			});
		},

		removeTrackingFields: function() {
			var $wrap = $( 'body #wc_edostavka .edostavka-tracking-code' );
			var template = wp.template( 'tracking-code-action' );

			$( '.edostavka-tracking-code__action', $wrap ).remove();
			$wrap.prepend( template( { 'remove': true } ) );
		},

		removeTrackingCode: function( evt ) {
			evt.preventDefault();

			if ( ! window.confirm( wc_edostavka_orders_admin_params.i18n.removeQuestion ) ) {
				return;
			}

			var data = {
				action: 'woocommerce_edostavka_remove_tracking_code',
				security: wc_edostavka_orders_admin_params.nonces.remove,
				order_id: wc_edostavka_orders_admin_params.order_id
			};

			WCedostavkaAdminOrders.block();

			$.ajax({
				type: 'POST',
				url: ajaxurl,
				data: data,
				success: function( data ) {
					if( data.success ) {
						WCedostavkaAdminOrders.removeTrackingFields();
					} else {
						$( '.tracking-code-result' ).html( data.data );
					}
					WCedostavkaAdminOrders.unblock();
				}
			});
		},
		
		getPrintPDF: function( event ) {
			event.preventDefault();
			$( event.target ).prop( 'disabled', true );
			WCedostavkaAdminOrders.processStep( 1, event.target );
		},
		
		processStep: function( step, target ) {
			
			$.ajax({
				type: 'POST',
				url: ajaxurl,
				dataType: 'json',
				data: {
					action: 'woocommerce_edostavka_get_print_order',
					security: wc_edostavka_orders_admin_params.nonces.print,
					order_id: wc_edostavka_orders_admin_params.order_id,
					step: step
				},
				success: function( response ) {
					if ( response.success ) {
						if ( 'done' === response.data.step ) {
							window.location = response.data.url;
							setTimeout( function() {
								$( target ).prop( 'disabled', false );
							}, 2000 );
							
							$( target ).text( 'Печать' );
						} else {
							WCedostavkaAdminOrders.processStep( parseInt( response.data.step, 10 ), target );
						}
					} else {
						$( '.tracking-code-result' ).html( response.data );
					}
				}
			}).fail( function( response ) {
				window.console.log( response );
			});
		}
	};

	WCedostavkaAdminOrders.init();
});
