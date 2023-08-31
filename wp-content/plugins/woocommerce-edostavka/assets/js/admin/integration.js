jQuery(function( $ ){
	
	if( ! $().select2() || typeof( wc_edostavka_params ) == "undefined" ) return;
	
	function getEnhancedSelectFormatString() {
		return {
			'language': {
				errorLoading: function() {
					return wc_enhanced_select_params.i18n_searching;
				},
				inputTooLong: function( args ) {
					var overChars = args.input.length - args.maximum;

					if ( 1 === overChars ) {
						return wc_enhanced_select_params.i18n_input_too_long_1;
					}

					return wc_enhanced_select_params.i18n_input_too_long_n.replace( '%qty%', overChars );
				},
				inputTooShort: function( args ) {
					var remainingChars = args.minimum - args.input.length;

					if ( 1 === remainingChars ) {
						return wc_enhanced_select_params.i18n_input_too_short_1;
					}

					return wc_enhanced_select_params.i18n_input_too_short_n.replace( '%qty%', remainingChars );
				},
				loadingMore: function() {
					return wc_enhanced_select_params.i18n_load_more;
				},
				maximumSelected: function( args ) {
					if ( args.maximum === 1 ) {
						return wc_enhanced_select_params.i18n_selection_too_long_1;
					}

					return wc_enhanced_select_params.i18n_selection_too_long_n.replace( '%qty%', args.maximum );
				},
				noResults: function() {
					return wc_enhanced_select_params.i18n_no_matches;
				},
				searching: function() {
					return wc_enhanced_select_params.i18n_searching;
				}
			}
		};
	}
	
	$( ':input.edostavka-ajax-load' ).filter( ':not(.enhanced)' ).each( function( index, $select ) {
		var $self = $( $select );
		var select2_args = {
			placeholder: 'Город не установлен',
			minimumInputLength: 2,
			allowClear: $self.hasClass( 'multiselect' ) || $self.hasClass( 'allowClear' ),
			escapeMarkup: function( m ) {
				return m;
			},
			ajax: {
				url: wc_edostavka_params.ajax_url,
				method: 'POST',
				dataType: "json",
				delay: 350,
				data:function( params ) {
					return {
						city_name: params.term,
						country: wc_edostavka_params.country_iso,
						action:'edostavka_autofill_address'
					}
				},
				processResults: function ( data ) {
					if( data.success ) {
						return {
							results: $.map( data.data, function ( item ) {
								if( ! item ) return;
								return {
									id: item.city_id,
									text: item.city_name + ' (' + item.state + ')'
								}
							} )
						}
					}
				},
				cache: true
			}
		};
		
		select2_args = $.extend( select2_args, getEnhancedSelectFormatString() );
		
		$( this ).selectWoo( select2_args ).addClass( 'enhanced' );
	});
	
	$( 'html' ).on( 'click', function( event ) {
		if ( this === event.target ) {
			$( ':input.city-ajax-load' ).filter( '.select2-hidden-accessible' ).selectWoo( 'close' );
		}
	} );
	
});	