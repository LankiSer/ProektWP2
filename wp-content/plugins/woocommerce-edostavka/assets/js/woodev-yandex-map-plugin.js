;( function ( factory ) {
	if ( typeof define === 'function' && define.amd ) {
		define(['jquery'], factory);
	} else if ( typeof exports === 'object' ) {
		module.exports = factory;
	} else {
		factory( jQuery );
	}
}( function( $ ) {
	'use strict';
	
	$.fn.WDYandexMap = function( options ) {
		return this.each( function() {
			( new $.WDYandexMap( $( this ), options ) );
		});
	};
	
	$.WDYandexMap = function( element, options ) {
		
		var settings = $.extend( {
			namespace: 'WCEdostavkaMaps',
			template: 'wc-modal-edostavka-map-balloon',
			points_url: '',
			set_delivery_point_ajax:'',
			city_id: '',
			delivery_type: 'ALL',
			active_point_icon: '',
			point_icon: '',
			current_pvz:'',
			disable_postcode_field: false,
			show_search_field: false,
			need_update_checkout:false,
			search_control_placeholder: 'Найти пункт выдачи',
			hide_modal_on_close_confirm: false
		}, options );
		
		if ( window[settings.namespace] !== undefined ) {
			
			window[settings.namespace].ready().done( function ( ym ) {
				$( element ).bind( 'WDYandexMap.destroy', ( new $.WDYandexMap.mapLoad( ym, element.get(0).id, settings ) ), function( event, action ) {
					if( action && action.refresh && event.data ) {
						event.data.geoObjects.removeAll();
					} else if( event.data && typeof event.data.destroy === 'function' ) {
						event.data.destroy();
					}
				} );
			} );
			
		} else {
			throw new Error( 'The yandex map API is NOT loaded yet' );
		}
	}
	
	$.WDYandexMap.mapLoad = function( ym, elem, settings ) {
		
		var map,
			currentPVZ = settings.current_pvz ? settings.current_pvz : null,
			$balloon_template = wp.template( settings.template );
		
		if( ! map ) {
			map = new ym.Map( elem, {
				center: [55.76, 37.64],
				zoom: 10,
				controls: [],
				duration: 300
			} );
		}
		
		map.controls.add( new ym.control.ZoomControl(), {
            position: {
                left: 12,
                bottom: 70
            }
        } );
		
		var ballonObject = new $.WDYandexMap.objectManager( settings );
		
		ballonObject.objects.events.add( "balloonopen", function( balloon ) {
			
			var element = ballonObject.objects.getById( balloon.get("objectId") );
			
			element.properties.balloonContent = function( data ) {
				return $balloon_template( { data, currentPVZ } );
			} ( element.properties.data );
								
			ballonObject.objects.balloon.setData( ballonObject.objects.balloon.getData() );
								
			$( document ).on("click", ".balloon__button", function( button ) {
									
			if ( element.id != currentPVZ ) {
										
				Backbone.ajax({
					method: 'POST',
					dataType: 'json',
					url: settings.set_delivery_point_ajax,
					data: { code: element.id, address: element.properties.data.address, type: element.properties.data.type },
					beforeSend: function(){
						$( button.target ).addClass( 'balloon__button_disabled' );
						$( button.target ).prop( { disabled: true } );
						$( '.my-balloon' ).block( {
							message: null,
							overlayCSS: {
								background: '#fff',
								opacity: 0.6
							}
						} );
					},
					complete: function(){
						$( '.my-balloon' ).unblock();
					},
					success: function( response ) {
												
						if( response && response.success ) {
													
							$( '#billing_address_1' ).val( element.properties.data.address ).trigger( 'change' );
										
							if( $( 'select.select-delivery-points' ).length > 0 ) {
								$( 'select.select-delivery-points' ).val( element.properties.data.code ).trigger( 'change' );
							}
													
							if( 'yes' != settings.disable_postcode_field ) {
								$( '#billing_postcode' ).val( element.properties.data.postalCode ).trigger( 'change' );
							}
													
							var newOverlay = ballonObject.objects.overlays.getById( element.id );
							if( newOverlay ) {
								var setActiveIconImage = new $.WDYandexMap.setActiveIconImage( ballonObject, element.id, settings );
								newOverlay.events.remove( 'mapchange', setActiveIconImage );
							}
													
							if( currentPVZ ) {													
								var oldOverlay = ballonObject.objects.overlays.getById( currentPVZ );
														
								if( oldOverlay ) {
									var setNonActiveIconImage = new $.WDYandexMap.setNonActiveIconImage( ballonObject, currentPVZ, settings );
									oldOverlay.events.remove( 'mapchange', setNonActiveIconImage );
								}
							}
													
							currentPVZ = element.id;
								
							if( settings.need_update_checkout ) {
								$( button.target ).addClass( 'hidden' );
								$( button.target ).next().removeClass( 'hidden' );
														
								$( document.body ).trigger( 'update_checkout' );
							}
						
						} else {
							$( button.target ).removeClass( 'balloon__button_disabled' );
							$( button.target ).prop( { disabled: false } );
						}
					}
				});
			}
			} );
		} );
		
		ballonObject.objects.events.add( "balloonclose", function() {
			$( document ).off( "click", ".balloon__button" );
		} );
		
		Backbone.ajax({
			method:   'GET',
			dataType: 'json',
			url:      settings.points_url,
			data:     {
				city_id: settings.city_id,
				delivery_type: settings.delivery_type
			},
			beforeSend: function(){
				$( '#' + elem ).block( {
					message: null,
					overlayCSS: {
						background: '#fff',
						opacity: 0.6
					}
				} );
			},
			complete: function(){
				if( ballonObject && typeof ballonObject.getBounds === 'function' && ballonObject.getBounds() ) {
					map.setBounds( ballonObject.getBounds(), {
						zoomMargin: 15,
						checkZoomRange: true,
						duration: 400
					} );
				} else {
					$.alert( {
						closeIcon: true,
						backgroundDismiss: true,
						escapeKey: true,
						animationBounce: 1,
						useBootstrap: false,
						theme: 'material',
						boxWidth: '420px',
						animateFromElement: false,
						type: 'orange',
						title: 'Пункты выдачи не найдены!',
						icon: 'fa fa-exclamation-triangle',
						content: function() {
							return [
								'К сожалению, в выбранном городе не найден ни один пункт для получения заказа, удовлетворяющий условиям доставки.',
								'Пожалуйста, выберите другой доступный метод доставки.'
							].join( '<br />' );
						},
						buttons: {
							close: {
								text: 'Я понял!',
								btnClass: 'btn-warning',
								action: function ( button ) {
									if( settings.hide_modal_on_close_confirm && typeof settings.hide_modal_on_close_confirm === 'function' ) {
										settings.hide_modal_on_close_confirm.call( this, map );
									}
								}
							}
						}
					} );
				}
				
				$( '#' + elem ).unblock();
			},
			success: function( response ) {
				if( response.success && response.data ) {
					
					if( response.data.chosen_delivery_point && response.data.chosen_delivery_point.id ) {
						currentPVZ = response.data.chosen_delivery_point.id;
					}
					
					ballonObject.add( function( result ){
													
						return $.map( result, function( elem ) {
														
							return {
								type: "Feature",
								id: elem.code,
								geometry: {
									type: "Point",
									coordinates: [ elem.coordY, elem.coordX ]
								},
								options: {
									balloonLayout: new $.WDYandexMap.templateLayout( settings ),
									balloonPanelMaxMapArea: 0,
									balloonAutoPan: true,
									openEmptyBalloon: true,
									hideIconOnBalloonOpen: false,
									iconLayout: "default#image",
									//iconImageHref: elem.code === currentPVZ ? settings.active_point_icon : settings.point_icon,
									iconImageHref: elem.type === 'PVZ' ? settings.pvz_icon : settings.postamat_icon,
									iconImageSize: elem.code === currentPVZ ? [50, 70] : [40, 40],
									iconImageOffset: elem.code === currentPVZ ? [-25, -40] : [-20, -20]
								},
								properties: {
									balloonContent: null,
									placemarkId: elem.code,
									data: elem
								}
							}
													
						} );
												
					}( response.data.points ) );
				}
			}
		});
		
		map.geoObjects.add( ballonObject );
		
		if( 'yes' == settings.show_search_field ) {
							
			map.controls.add( new window[settings.namespace].control.SearchControl( {
				options: {
					provider: {
						geocode: function( request, options ) {
							var deferred = new window[settings.namespace].vow.defer(),
								offset = options.skip || 0,
								limit = options.results || 10,
								points = [],
								request_text = request.toLowerCase();
												
							var collection = new window[settings.namespace].GeoObjectCollection();
											
							$.map( ballonObject.objects.getAll(), function( object ) {
								var point_name = object.properties.data.name.toLowerCase(),
									point_address = object.properties.data.fullAddress.toLowerCase(),
									point_short_address = object.properties.data.address.toLowerCase(),
									point_index = object.properties.data.postalCode,
									point_comment = object.properties.data.addressComment.toLowerCase();
													
								if ( point_name.indexOf( request_text ) != -1 || point_address.indexOf( request_text ) != -1 || point_short_address.indexOf( request_text ) != -1 || point_comment.indexOf( request_text ) != -1 || point_index == request_text ) {
									points.push( object );
								}	
							} );
											
							points = points.splice( offset, limit );
											
							for (var i = 0, l = points.length; i < l; i++) {
								collection.add( new window[settings.namespace].Placemark( points[i].geometry.coordinates, {
									name: points[i].properties.data.name,
									description: points[i].properties.data.name.address,
									balloonContentBody: points[i].properties.data.name.note,
									boundedBy: [points[i].geometry.coordinates, points[i].geometry.coordinates]
								} ) );
							}
											
							deferred.resolve({
								geoObjects: collection,
								metaData: {
									geocoder: {
										request: request,
										found: collection.getLength(),
										results: limit,
										skip: offset
									}
								}
							});										
											
							return deferred.promise();
						}
					},
					noPlacemark: true,
					noSuggestPanel: false,
					resultsPerPage: 10,
					placeholderContent: settings.search_control_placeholder
				}
			} ) );
		}
		
		return map;
		
	};
	
	$.WDYandexMap.objectManager = function( settings ) {
		
		return new window[settings.namespace].ObjectManager( {
			clusterize: true,
			clusterIconColor: "#0a8c37",
			/*clusterIcons: [
				{
                    href: 'https://www.cdek.ru/map/cluster2.svg',
                    size: [40, 40],
                    offset: [-20, -20]
                }
			]*/
		} );
	}
	
	$.WDYandexMap.templateLayout = function( settings ) {
							
		return window[settings.namespace].templateLayoutFactory.createClass( '<div class="my-balloon"><a class="my-balloon__close-button" href="#">&times;</a>$[[options.contentLayout]]</div>', {
			build: function() {
				this.constructor.superclass.build.call( this );
				this.element = $( '.my-balloon', this.getParentElement() );
				this.element.find( '.my-balloon__close-button' ).on("click", $.proxy( this.onCloseClick, this ) );
			},
			onCloseClick: function( event ) {
				event.preventDefault(),
				this.events.fire("userclose")
			},
			getShape: function() {
									
				if ( ! this.isElement( this.element ) ) return this.constructor.superclass.getShape.call( this );
									
				var position = this.element.position();
									
				return new window[settings.namespace].shape.Rectangle( new window[settings.namespace].geometry.pixel.Rectangle( [
					[ position.left, position.top ],
					[ position.left + this.element[0].offsetWidth, position.top + this.element[0].offsetHeight ]
				] ) )
			},
			isElement: function( element ) {
				return element && element[0]
			}
		} );
	}
	
	$.WDYandexMap.setActiveIconImage = function( objectManager, objectId, settings ) {
		objectManager.objects.setObjectOptions( objectId, {
			//iconImageHref: settings.active_point_icon,
			iconImageSize: [50, 70],
			iconImageOffset: [-25, -40]
		} );
	}

	$.WDYandexMap.setNonActiveIconImage = function( objectManager, objectId, settings ) {
		objectManager.objects.setObjectOptions( objectId, {
			//iconImageHref: settings.point_icon,
			iconImageSize: [40, 40],
			iconImageOffset: [-20, -20]
		} );
	}
	
} ) );