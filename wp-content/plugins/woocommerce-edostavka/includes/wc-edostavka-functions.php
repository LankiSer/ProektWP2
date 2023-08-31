<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function wc_edostavka_get_customer_state_id() {

	if( wc_edostavka_is_admin_scope() || ! WC()->session ) {
		return;
	}

	$customer_id = apply_filters( 'woocommerce_checkout_customer_id', get_current_user_id() );
	$session_state_id = WC()->session->get( 'billing_state_id', null );
	$state_id = apply_filters( 'woocommerce_edostavka_default_state_id', 0 );
	
	if( $customer_id > 0 ) {
		
		$customer = new WC_Customer( $customer_id );
		
		if( $customer->meta_exists( 'billing_state_id' ) ) {
			$state_id = $customer->get_meta( 'billing_state_id' );
			
			if( ! is_null( $session_state_id ) && $state_id !== $session_state_id ) {
				$state_id = $session_state_id;
				$customer->update_meta_data( 'billing_state_id', $state_id );
				$customer->save();
			}
			
			$state_id = $state_id;
		}
	
	} elseif( ! is_null( $session_state_id ) ) {
		$state_id = $session_state_id;
	}
	
	return apply_filters( 'woocommerce_edostavka_customer_state_id', $state_id, $session_state_id, $customer_id );
}

function wc_edostavka_set_customer_state_id( $state_id ) {
	
	if( wc_edostavka_is_admin_scope() || ! WC()->session ) {
		return;
	}
	
	$customer_id = apply_filters( 'woocommerce_checkout_customer_id', get_current_user_id() );
	
	if( wc_edostavka_get_customer_state_id() !== $state_id ) {
		
		WC()->session->set( 'billing_state_id', $state_id );	
		
		if( $customer_id > 0 ) {
			$customer = new WC_Customer( $customer_id );
			$customer->update_meta_data( 'billing_state_id', $state_id );
			$customer->save();
		}
	}
	
	do_action( 'wc_edostavka_set_customer_state_id', $state_id );
}

function wc_edostavka_reset_delivery_point_data() {
	if( WC()->session ) {
		WC()->session->set( 'chosen_delivery_point', array() );	
	}
}

function wc_edostavka_set_delivery_point_data( $data = array() ) {
	
	if( WC()->session ) {
		
		$args = wp_parse_args( $data, array(
			'city_id' 	=> wc_edostavka_get_customer_state_id(),
			'point_id'	=> '',
			'type' 		=> '',
			'address' 	=> ''
		) );
		
		if( ! empty( $args['type'] ) && ! empty( $args['address'] ) && ! empty( $args['point_id'] ) ) {
			$chosen_delivery_point = ( array ) WC()->session->get( 'chosen_delivery_point', array() );
			if( is_array( $chosen_delivery_point ) ) {
				$chosen_delivery_point[ $args['city_id'] ][ strtolower( $args['type'] ) ] = array(
					'id'		=> $args['point_id'],
					'address'	=> $args['address']
				);
				WC()->session->set( 'chosen_delivery_point', $chosen_delivery_point );
			} else {
				throw new Exception( 'Значение параметра chosen_delivery_point не явяется массивом.' );
			}
		}
	
	} else {
		throw new Exception( 'Объек session класса WC не инициирован.' );
	}
}

function wc_edostavka_get_delivery_point_data( $city_id, $type = '' ) {
	
	if( WC()->session && $city_id ) {
		$chosen_delivery_point = WC()->session->get( 'chosen_delivery_point', array() );
		if( isset( $chosen_delivery_point[ $city_id ] ) ) {
			if( ! empty( $type ) && in_array( $type, array( 'pvz', 'postamat' ), true ) && isset( $chosen_delivery_point[ $city_id ][ $type ] ) ) {
				return $chosen_delivery_point[ $city_id ][ $type ];
			} elseif( empty( $type ) ) {
				return $chosen_delivery_point[ $city_id ];
			}
		}
	}
	
	return false;
}

function wc_edostavka_convert_method_delivery_type( $method ) {
	if( $method && method_exists( $method, 'get_delivery_type' ) ) {
		return $method->get_delivery_type() == 'stock' ? 'pvz' : $method->get_delivery_type();
	}
	return false;
}

function wc_edostavka_add_log( $message, $type = 'info', $log_name = 'edostavka' ) {
	if( apply_filters( 'woocommerce_edostavka_enable_debug', false ) && ! empty( $message ) && function_exists( 'wc_get_logger' ) ) {
		$logger = wc_get_logger();
		if( method_exists( $logger, $type ) ) {
			$logger->$type( $message, array( 'source' => $log_name ) );
		}
	}
}

function wc_edostavka_get_chosen_shipping_method_ids() {
	if( ! wc_edostavka_is_admin_scope() && function_exists( 'wc_get_chosen_shipping_method_ids' ) ) {
		return wc_get_chosen_shipping_method_ids();
	}
	return array();
}

function wc_edostavka_get_estimating_delivery( $name, $days, $additional_days = 0 ) {
	$total = intval( $days ) + intval( $additional_days );

	if ( $total > 0 ) {
		$name .= sprintf(' (<span>%s</span>)',  human_time_diff( strtotime("+$total day") ) );
	}

	return apply_filters( 'woocommerce_edostavka_get_estimating_delivery', $name, $days, $additional_days );
}

function wc_edostavka_integration_get_option( $option_name, $default = null ) {
	
	if( WC()->integrations && method_exists( 'WC_Integrations', 'get_integration' ) ) {
		return WC()->integrations->get_integration( 'edostavka-integration' )->get_option( $option_name, $default );
	} else {
		
		$settings = get_option( 'woocommerce_edostavka-integration_settings' );
		if( $option_name && isset( $settings[ $option_name ] ) ) {
			return $settings[ $option_name ];
		}
	}

	return $default;
}

function wc_edostavka_chosen_method() {
	$_chosen_method = array();
	$shipping_methods = WC()->shipping->get_shipping_methods();
	foreach( $shipping_methods as $method_id => $method ) {
		if( ! method_exists( $method, 'is_edostavka' ) ) {
			continue;
		}
		
		if( $method->is_edostavka() ) {
			$_chosen_method[$method_id] = $method;
		}
	}
	
	return $_chosen_method;
}

function wc_edostavka_delivery_points_field( $atts = array() ) {

	$customer_state_id 			= wc_edostavka_get_customer_state_id();
	$chosen_shipping_method 	= wc_edostavka_get_chosen_shipping_method_ids();	
	$_chosen_method 			= wc_edostavka_chosen_method();
	$chosen_method 				= array_shift( $chosen_shipping_method );
	$method 					= isset( $_chosen_method[ $chosen_method ] ) ? $_chosen_method[ $chosen_method ] : false;
	$method_delivery_type		= wc_edostavka_convert_method_delivery_type( $method );
	$chosen_delivery_point		= wc_edostavka_get_delivery_point_data( $customer_state_id, $method_delivery_type );
	$delivery_points_select 	= '';
	
	$args = wp_parse_args( $atts, array(
		'label' 	=> '',
		'class' 	=> '',
		'priority' 	=> '',
		'points'	=> array()
	) );
	
	if( isset( $atts['points'] ) && ! empty( $atts['points'] ) ) {
		$args['points'] = $atts['points'];
	} else {
		$args['points'] = WC_Edostavka_Delivery_Points::get_points( $customer_state_id, false, array( 'type' => strtoupper( $method_delivery_type ) ) );
	}
	
	if( ! empty( $args['points'] ) && 'select' == wc_edostavka_integration_get_option( 'delivery_points_field_type', 'text' ) ) {
		$delivery_points_select .= sprintf( '<select class="select-delivery-points" placeholder="%s" name="billing_delivery_points">', __( 'Select Pick-up point', 'woocommerce-edostavka' ) );
		$delivery_points_select .= sprintf( '<option value="">%s</option>', __( '--Select Pick-up point--', 'woocommerce-edostavka' ) );
		foreach( $args['points'] as $point ) {
			$chosen_delivery_point_id = ( $chosen_delivery_point && isset( $chosen_delivery_point['id'] ) ) ? $chosen_delivery_point['id'] : null;
			$delivery_points_select .= sprintf( '<option value="%s" data-description="%s" data-address="%s" data-type="%s" %s>%s %s</option>', $point['code'], esc_attr( $point['addressComment'] ), esc_attr( $point['fullAddress'] ), strtolower( $method_delivery_type ), selected( $point['code'], $chosen_delivery_point_id, false ), $point['address'], ! empty( $point['metroStation'] ) ? '(' . $point['metroStation'] . ')' : '' );
		}
		$delivery_points_select .= '</select>';
	}
	
	if( $method_delivery_type && in_array( $method_delivery_type, array( 'pvz', 'postamat' ), true ) ) {
		$map_block = sprintf( '<div id="edostavka_map" data-delivery_type="%s"></div>', $method_delivery_type );
		if( empty( $args['points'] ) ) {
			$args['class'][] = 'hidden';
		}
	} elseif( empty( $args['points'] ) || ! $method || 'door' == $method_delivery_type ) {
		$args['class'][] = 'hidden';
		$map_block = '';
	}
	
	return sprintf("<div id='delivery_points' class='form-row form-row-wide delivery-points-field %s' data-priority='%d'><label>%s</label>%s%s</div>", esc_attr( implode( ' ', $args['class'] ) ), $args['priority'], $args['label'], $delivery_points_select, $map_block );
}

function wc_edostavka_xml_to_array( $xml ) {
	if ( function_exists( 'simplexml_load_string' ) && function_exists( 'libxml_disable_entity_loader' ) ) {
		$loader = libxml_disable_entity_loader( true );
		$XMLobject = simplexml_load_string( $xml, 'SimpleXMLElement', LIBXML_NOENT );
		$return = _wc_edostavka_simple_xml_to_array( $XMLobject );
		libxml_disable_entity_loader( $loader );
		return $return;
	} else {
		wc_edostavka_add_log( 'Не установлена обязательная библиотека Simple XML', 'notice',  'edostavka_errors' );
		return false;
	}
	return false;
}

function _wc_edostavka_simple_xml_to_array( $XMLobject ) {
	if ( ! is_object( $XMLobject ) && ! is_array( $XMLobject ) ) {
		return $XMLobject;
	}
	$XMLarray = ( is_object( $XMLobject ) ? get_object_vars( $XMLobject ) : $XMLobject );
	foreach ($XMLarray as $key => $value) {
		$XMLarray[$key] = _wc_edostavka_simple_xml_to_array( $value );
	}
	return $XMLarray;
}

function wc_edostavka_safe_load_xml( $source, $options = 0 ) {
	$old = null;

	if ( function_exists( 'libxml_disable_entity_loader' ) ) {
		$old = libxml_disable_entity_loader( true );
	}

	$dom    = new DOMDocument();
	$return = $dom->loadXML( trim( $source ), $options );

	if ( ! is_null( $old ) ) {
		libxml_disable_entity_loader( $old );
	}

	if ( ! $return ) {
		return false;
	}

	if ( isset( $dom->doctype ) ) {
		throw new Exception( 'Небезопасный DOCTYPE Обнаружен во время разбора XML' );

		return false;
	}

	return simplexml_import_dom( $dom );
}

function wc_edostavka_trigger_tracking_code_email( $order, $tracking_code ) {
	$mailer       = WC()->mailer();
	$notification = $mailer->emails['WC_CDEK_Tracking_Email'];

	if ( 'yes' === $notification->enabled ) {
		if ( method_exists( $order, 'get_id' ) ) {
			$notification->trigger( $order->get_id(), $order, $tracking_code );
		} else {
			$notification->trigger( $order->id, $order, $tracking_code );
		}
	}
}

function wc_edostavka_get_tracking_code( $order ) {
	if ( is_numeric( $order ) ) {
		$order = wc_get_order( $order );
	}

	if ( method_exists( $order, 'get_meta' ) ) {
		$code = $order->get_meta( '_edostavka_tracking_code' );
	} else {
		$code = $order->edostavka_tracking_code;
	}

	return $code;
}

function wc_edostavka_update_tracking_code( $order, $tracking_code, $remove = false ) {
	$tracking_code = sanitize_text_field( $tracking_code );


	if ( is_numeric( $order ) ) {
		$order = wc_get_order( $order );
	}
	
	if( ! $remove && ! empty( $tracking_code ) ) {
		if ( method_exists( $order, 'update_meta_data' ) ) {
			$order->update_meta_data( '_edostavka_tracking_code', $tracking_code );
			$order->save();
		} else {
			update_post_meta( $order->get_id(), '_edostavka_tracking_code', $tracking_code );
		}
		
		$order->add_order_note( sprintf( '%s: %s', __( 'Tracking code added', 'woocommerce-edostavka' ), $tracking_code ) );
		
		wc_edostavka_trigger_tracking_code_email( $order, $tracking_code );
		
		return true;
	} elseif( $remove && ( $tracking_code = wc_edostavka_get_tracking_code( $order ) ) ) {
		if ( method_exists( $order, 'update_meta_data' ) ) {
			$order->delete_meta_data( '_edostavka_tracking_code' );
			$order->save();
		} else {
			delete_post_meta( $order->get_id(), '_edostavka_tracking_code' );
		}

		$order->add_order_note( sprintf( 'Код трекинга СДЭК удалён: %s', __( 'Tracking code deleted', 'woocommerce-edostavka' ), $tracking_code ) );

		return true;
	}

	return false;
}

function wc_edostavka_extra_services_list() {
	return apply_filters( 'woocommerce_edostavka_extra_services_list', array(
		'3'		=> 'Доставка в выходной день',
		'16'	=> 'Забор в городе отправителе',
		'17'	=> 'Забор в городе получателе',
		'30'	=> 'Примерка на дому',
		'36'	=> 'Частичная доставка',
		'37'	=> 'Осмотр вложения',
		'48'	=> 'Реверс',
	) );
}

function wc_edostavka_create_map( $atts = array() ) {
	
	$defaults = array(
		'city_form'	=> wc_edostavka_integration_get_option( 'city_origin' ),
		'city_to' 	=> wc_edostavka_integration_get_option( 'default_state_id' )
	);
		
	$args = wp_parse_args( $atts, $defaults );
	
	ob_start();
	
	wc_get_template( 'widget-map.php', $args, '', WC_Edostavka::get_templates_path() );
	
	return ob_get_clean();
}

add_action( 'wc_ajax_edostavka_get_delivery_points', 'wc_edostavka_get_delivery_points_ajax' );
function wc_edostavka_get_delivery_points_ajax() {
	if( empty( $_REQUEST['city_id'] ) ) {
		wp_send_json_error( array( 'message' => 'Не указан параметр Город' ) );
	}
	
	$extra_params = array();
	if( isset( $_REQUEST['delivery_type'] ) && $_REQUEST['delivery_type'] == 'postamat' ) {
		$extra_params['type'] = 'POSTAMAT';
	}
	
	$chosen_delivery_point = wc_edostavka_get_delivery_point_data( $_REQUEST['city_id'], $_REQUEST['delivery_type'] ? ( $_REQUEST['delivery_type'] == 'stock' ? 'pvz' : $_REQUEST['delivery_type'] ) : '' );
	$delivery_points = WC_Edostavka_Delivery_Points::get_points( wc_clean( $_REQUEST['city_id'] ), false, $extra_params );
	
	wp_send_json_success( array( 'points' => $delivery_points, 'chosen_delivery_point' => $chosen_delivery_point ) );
}

function wc_edostavka_is_admin_scope() {
	return apply_filters( 'wc_edostavka_is_admin', is_admin() );
}

function edostavka_get_suggestions_address_via_dadata( $search ) {
	
	$response_params = array();
	
	if( is_array( $search ) ) {
		if( isset( $search['state'] ) ) {
			//$response_params['locations'][0]['region'] = wc_clean( $search['state'] );
		}
		if( isset( $search['city'] ) ) {
			$response_params['locations'][0]['city'] = wc_clean( $search['city'] );
		}
		if( isset( $search['country'] ) ) {
			$response_params['locations'][0]['country_iso_code'] = wc_clean( $search['country'] );
		}
		if( isset( $search['address'] ) ) {
			$response_params['query'] = wc_clean( $search['address'] );
		}
		
		$response_params['restrict_value'] = true;
		$response_params['from_bound']['value'] = 'street';
		$response_params['to_bound']['value'] = 'house';
	
	} else {
		$response_params['query'] = wc_clean( $search );
	}
	
	$transient_name	= 'edostavka_suggestions_address_via_dadata_' . md5( wp_json_encode( $response_params ) . WC_Cache_Helper::get_transient_version( 'edostavka_formatted_address', true ) );
	$transient 		= get_transient( $transient_name );
	
	if ( false === $transient ) {
		
		$response = wp_remote_post( 'https://suggestions.dadata.ru/suggestions/api/4_1/rs/suggest/address', array(
			'headers'	=> array(
				'Content-Type' 	=> 'application/json',
				'Accept'		=> 'application/json',
				'Authorization'	=> sprintf( 'Token %s', apply_filters( 'wc_edostavka_auth_dadata_token', wc_edostavka_integration_get_option( 'dadata_token' ) ) )
			),
			'body'		=> wp_json_encode( $response_params )
		) );
		
		if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) == 200 ) {
			$raw_data = json_decode( wp_remote_retrieve_body( $response ), true );
			
			if( isset( $raw_data['suggestions'] ) && ! empty( $raw_data['suggestions'] ) ) {
				$transient = $raw_data['suggestions'];
				set_transient( $transient_name, $transient, DAY_IN_SECONDS );
			} else {
				
				throw new Exception( wc_print_r( array( 'request_data' => $response_params, 'response_data' => $raw_data ), true ) );
			}
		
		} elseif( is_wp_error( $response ) ) {
			throw new Exception( sprintf( __( 'Error: %s', 'woocommerce' ), $response->get_error_message() ) );
		} elseif( wp_remote_retrieve_response_code( $response ) !== 200 ) {
			
			$error_message = '';
			$error_data = json_decode( wp_remote_retrieve_body( $response ), true );
			
			switch( wp_remote_retrieve_response_code( $response ) ) {
				case 400 : $error_message = 'Некорректный запрос (невалидный JSON или XML). Проверьте корректность передаваемых данных.'; break;
				case 401 : $error_message = 'В запросе отсутствует API-ключ от DADATA'; break;
				case 403 : $error_message = 'В запросе указан несуществующий API-ключ или не подтверждена почта или исчерпан дневной лимит по количеству запросов.'; break;
				case 405 : $error_message = 'Запрос сделан с методом, отличным от POST.'; break;
				case 413 : $error_message = 'Слишком большая длина запроса или слишком много условий.'; break;
				case 429 : $error_message = 'Слишком много запросов в секунду или новых соединений в минуту.'; break;
				default : $error_message = 'Произошла внутренняя ошибка сервиса DADATA.'; break;
			}
			
			if( isset( $error_data['message'] ) && ! empty( $error_data['message'] ) ) {
				$error_message .= ' ' . $error_data['message'];
			}
			
			throw new Exception( sprintf( __( 'Error: %s', 'woocommerce' ), $error_message ) );
		}
	}
	
	return $transient;
}

function edostavka_get_formatted_address_via_dadata( $address ) {
		
	$transient_name	= 'edostavka_formatted_address_via_dadata_' . md5( wp_json_encode( $address ) . WC_Cache_Helper::get_transient_version( 'edostavka_formatted_address', true ) );
	$transient 		= get_transient( $transient_name );
		
	if ( false === $transient ) {
		
		$response = wp_remote_post( 'https://cleaner.dadata.ru/api/v1/clean/address', array(
			'headers'	=> array(
				'Content-Type' 	=> 'application/json',
				'Authorization'	=> sprintf( 'Token %s', apply_filters( 'wc_edostavka_auth_dadata_token', wc_edostavka_integration_get_option( 'dadata_token' ) ) ),
				'X-Secret'		=> apply_filters( 'wc_edostavka_auth_dadata_secret', wc_edostavka_integration_get_option( 'dadata_secret' ) )
			),
			'body'		=> wp_json_encode( array( $address ) )
		) );

		if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) == 200 ) {
			
			$raw_data = json_decode( wp_remote_retrieve_body( $response ), true );
			$result = is_array( $raw_data ) && isset( $raw_data[0] ) ? array_shift( $raw_data ) : $raw_data;
			
			$house = isset( $result['house'] ) ? $result['house'] : '';
				
			if( ! empty( $result['block'] ) ) {
				$house_type = ! empty( $result['house_type'] ) ? $result['house_type'] . '. ' : '';
				$block_type = ! empty( $result['block_type'] ) ? $result['block_type'] . '. ' : '';
				$house = sprintf( '%s%s %s%s', $house_type, $house, $block_type, $result['block'] );
			}
				
				
					
			$transient = array(
				'street'	=> isset( $result['street'] ) ? $result['street'] : '',
				'house'		=> $house,
				'flat'		=> isset( $result['flat'] ) ? $result['flat'] : ''
			);
					
			set_transient( $transient_name, $transient, YEAR_IN_SECONDS );
			
		}
			
	}
		
	return $transient;
	
}

function edostavka_get_allowed_zone_locations() {
	global $wpdb;
			
	return $wpdb->get_col( $wpdb->prepare( "
		SELECT DISTINCT zone_locations.location_code FROM {$wpdb->prefix}woocommerce_shipping_zone_locations AS zone_locations
		LEFT JOIN {$wpdb->prefix}woocommerce_shipping_zone_methods AS zone_methods ON zone_methods.zone_id = zone_locations.zone_id
		WHERE zone_locations.location_type = 'country'
		AND zone_methods.method_id LIKE %s
		AND zone_methods.is_enabled = 1
	", '%' . $wpdb->esc_like( WC_Edostavka::get_method_id() ) . '%' ) );
}

function edostavka_only_virtual_products_in_cart() {
	
	$only_virtual = false;
	
	if( WC()->cart ) {
		
		foreach( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			if ( ! $cart_item['data']->is_virtual() ) $only_virtual = false;
		}
	
	}
	
	return $only_virtual;
}