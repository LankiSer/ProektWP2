jQuery(function($){
	
	$(document).ready(function(){
		
		$( '.edostavka-map' ).each( function( index, element ) {
			var $root = $( element );
			var map = false;
			
			$root.on( 'update_edostavka_map', function() {
				WCEdostavkaMaps.ready(function () {
					
					var $city_name = $( 'select[name="edostavka-map"] option:selected' ).text();
					
					WCEdostavkaMaps.geocode( $city_name, { results: 1 } ).then( function( response ){
						var getCoordinats = response.geoObjects.get(0).geometry.getCoordinates();
						if( ! map ) {
							map = new WCEdostavkaMaps.Map( element, {
								center: getCoordinats,
								zoom: 10,
								controls: [],
								duration: 300
							});
							
							map.controls.add( new WCEdostavkaMaps.control.ZoomControl(), {
                                position: {
                                    left: 12,
                                    bottom: 70
                                }
                            } );
							
							map.events.add( 'boundschange', function( e ) {
								//console.log( e );
							} );
						
						} else {
							map.setCenter( getCoordinats );
							map.setZoom(10);
						}
						
						var clusterer = new WCEdostavkaMaps.Clusterer( {
							gridSize: 50,
							preset: 'islands#ClusterIcons',
							clusterIconColor: '#0a8c37',
							hasBalloon: false,
							groupByCoordinates: false,
							clusterDisableClickZoom: false,
							maxZoom: 11,
							zoomMargin: [45],
							clusterHideIconOnBalloonOpen: false,
							geoObjectHideIconOnBalloonOpen: false
						} );
						
						if ( typeof( map.geoObjects.removeAll ) !== 'undefined' && false ) {
							map.geoObjects.removeAll();
						} else {
							do {
								map.geoObjects.each( function (e) {
									map.geoObjects.remove(e);
								});
							} while ( map.geoObjects.getBounds() );
						}
						
						var geoMarks = [];
						
						$.getJSON( edostavka_map_params.points_url, { city_id: $root.data( 'city_to' ) }, function( data ) {
							
							if( data.success && data.data.points ) {
								
								$.map( data.data.points, function( point ) {
									
									placeMark = new WCEdostavkaMaps.Placemark( [ point.coordY, point.coordX ], {
										balloonContentBody: [
											'<address classs="edostavka-map__placemark">',
											'ПВЗ: <strong>' + point.name + '</strong><br/>',
											'Адрес: ' + point.fullAddress + '<br/>',
											'Телефон: ' + point.phone + '<br/>',
											'Время работы: ' + point.workTime + '<br/>',
											point.isDressingRoom ? 'Примерочная: ' + (point.isDressingRoom ? 'Есть' : 'Нету') + '<br/>' : '',
											point.haveCashless ? 'Терминал оплаты: ' + (point.haveCashless ? 'Да' : 'Нет') + '<br/>' : '',
											point.allowedCod ? 'Наложенный платёж: ' + (point.allowedCod ? 'Да' : 'Нет') + '<br/>' : '',
											point.nearestStation ? 'Остановка ОТ: ' + point.nearestStation + '<br/>' : '',
											point.metroStation ? 'Станция метро: ' + point.metroStation + '<br/>' : '',
											point.site ? 'Сайт: <a href="' + point.site + '" target="_blank">Посмотреть</a><br/>' : '',
											point.note ? 'Дополнительно: ' + point.note : '',
											'</address>'
										].join('')
									}, {
										iconLayout: 'default#image',
										iconImageHref: edostavka_map_params.na_active_icon,
										iconImageSize: [40, 43],
										iconImageOffset: [-15, -15]
									});
									
									geoMarks.push( placeMark );
									
									placeMark.events.add( ['balloonopen', 'click'], function ( metka ) {
										//console.log( metka );
									});
									
									placeMark.events.add( ['mouseenter'], function ( metka ) {
										metka.get('target').options.set( {
											iconImageHref: edostavka_map_params.active_icon
										} );
									});
									
									placeMark.events.add( ['mouseleave'], function ( metka ) {
										metka.get('target').options.set( {
											iconImageHref: edostavka_map_params.na_active_icon
										} );
									});
								
								} );
								
								clusterer.add( geoMarks );
								map.geoObjects.add( clusterer );
								map.setBounds( clusterer.getBounds(), {
									zoomMargin: 45,
									checkZoomRange: true,
									duration: 500
								} );
							}
						} );
					});
				});
			} ).trigger( 'update_edostavka_map' );
			
			if( $().select2 ) {
				
				$( 'select[name="edostavka-map"]' ).select2({
					placeholder: 'Выберите город',
					placeholderOption: 'first',
					minimumInputLength: 3,
					width: '100%',
					escapeMarkup: function( m ) {
						return m;
					},
					ajax: {
						quietMillis: 250,
						url: edostavka_map_params.ajax_url,
						dataType: "json",
						method: 'POST',
						data: function( params ) {
							return {
								city_name: params.term
							};
						},
						processResults: function( data ) {
							var terms = [];
							if ( data.success && data.data ) {
								$.each( data.data, function( id, item ) {
									terms.push({
										id: item.city_id,
										text: item.city_name + ' ( ' + item.state + ' )'
									});
								});
							}
							return {
								results: terms
							};
						},
						cache: true
					},
					multiple: false,
					language: {
						inputTooShort: function( args ) {
							var remainingChars = args.minimum - args.input.length;
							return 'Введите ещё ' + remainingChars + ' символа для поиска.';
						},
						noResults: function() {
							return 'Нет рузультатов по запросу.';
						},
						searching: function() {
							return 'Идёт загрузка списка...';
						}
					}
				}).addClass( 'enhanced' ).on('select2:selecting', function( event ) {
					$root.data( 'city_to', event.params.args.data.id );
					$root.trigger( 'update_edostavka_map' );
				});
			}	
		} );
	});
});