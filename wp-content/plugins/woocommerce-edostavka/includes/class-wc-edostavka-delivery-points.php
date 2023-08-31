<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Edostavka_Delivery_Points {
		
	public static function get_points( $city_id = 0, $only_cod = false, $args = array() ) {
	
		$points = array();
		$locale = function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale();
		
		$points_args = apply_filters( 'wc_edostavka_get_points_args', wp_parse_args( $args, array(
			'cityid' 		=> $city_id,
			'type'			=> wc_edostavka_integration_get_option( 'delivery_point_type', 'PVZ' ),
			'lang'			=> $locale == 'ru_RU' ? 'rus' : 'eng'
		) ) );
		
		if( ! isset( $points_args['takeonly'] ) && isset( $args['takeonly'] ) ) {
			$points_args['takeonly'] = $args['takeonly'];
		}
		
		if( ! isset( $points_args['allowedcod'] ) && $only_cod ) {
			$points_args['allowedcod'] = true;
		}
		
		if( $city_id > 0 && ! empty( $points_args ) ) {
			$transient_name = 'wc_edostavka_delivery_points_' . md5( wp_json_encode( $points_args ) );
			$points = get_transient( $transient_name );
			if( false === $points || count( $points ) === 0 ) {
				$points = self::fetch_points( $points_args );
				if( ! empty( $points ) && count( $points ) > 0 ) {
					set_transient( $transient_name, $points, HOUR_IN_SECONDS * 6 );
				} else {
					delete_transient( $transient_name );
				}
			}
		}
		
		return $points;
	}
	
	private static function fetch_points( $points_args = array() ) {
		
		$points = array();
		
		$pvzlist_url = apply_filters( 'wc_edostavka_delivery_points_request_url', sprintf( '%sintegration.cdek.ru/pvzlist/v1/json', wc_site_is_https() ? 'https://' : 'http://' ) );
		
		$response = wp_safe_remote_get( add_query_arg( $points_args, esc_url( $pvzlist_url ) ) );
			
		if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) == 200 ) {
				
			$result = json_decode( wp_remote_retrieve_body( $response ) );
				
			if( $result && sizeof( $result->pvz ) > 0 ) {
				$default = array(
					'code'				=> '',
					'name'				=> '',
					'city'				=> '',
					'workTime'			=> '',
					'address'			=> '',
					'fullAddress'		=> '',
					'phone'				=> '',
					'note'				=> '',
					'coordX'			=> '',
					'coordY'			=> '',
					'isDressingRoom'	=> '',
					'haveCashless'		=> '',
					'allowedCod'		=> '',
					'nearestStation'	=> '',
					'metroStation'		=> '',
					'site'				=> '',
					'addressComment'	=> '',
				);
				
				foreach( $result->pvz as $pvz ) {
					$points[ $pvz->code ] = wp_parse_args( (array) $pvz, $default );
				}
			}
		
		} else {
			if( is_wp_error( $response ) ) {
				wc_edostavka_add_log( sprintf( 'Unable to dispatch CDEK PVZ: %s', $response->get_error_message() ), 'error', 'edostavka_errors' );
			} else {
				wc_edostavka_add_log( 'Bad Gateway CDEK PVZ', 'error', 'edostavka_errors' );
			}
		}

		return $points;
	}
}