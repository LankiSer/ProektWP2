<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class WC_Edostavka_Tariff_Calculator extends WC_Edostavka_Connect {
	
	public function __construct( $package = array(), $args = array() ) {
		parent::__construct( $package, $args );
		
	}
	
	public function get_rate() {
		
		$args = array(
			'date'			=> $this->date,
			'currency'		=> get_woocommerce_currency(),
			'tariff_code'	=> $this->code,
			'from_location'	=> array(
				'code'	=> apply_filters( 'woocommerce_edostavka_city_origin', 0 )
			),
			'to_location'	=> array(
				'code'			=> $this->package['destination']['state_id'],
				'postal_code'	=> $this->package['destination']['postcode'],
				'country_code'	=> $this->package['destination']['country'],
				'city'			=> $this->package['destination']['city']
			),
			'packages'		=> $this->goods,
			'services'		=> array()
		);
		
		if( 'yes' === wc_edostavka_integration_get_option( 'add_insurance_cost', 'no' ) && 'cod' == WC()->session->get('chosen_payment_method') ) {
			$args['services'] = array(
				array(
					'code'		=> 'INSURANCE',
					'parameter'	=> WC()->cart->get_cart_contents_total()
				)
			);
		}
		
		$args = apply_filters( 'woocommerce_edostavka_get_rate_atts', $args, $this );
		
		wc_edostavka_add_log( 'Параметры запроса: ' . wc_print_r( $args, true ) );
		
		if ( class_exists( 'WC_Cache_Helper' ) ) {
			
			$hash_data = array(
				'receiver_id'		=> $args['to_location']['code'],
				'receiver_country'	=> $args['to_location']['country_code'],
				'receiver_city'		=> $args['to_location']['city'],
				'receiver_postcode'	=> $args['to_location']['postal_code'],
				'sender_id'			=> $args['from_location']['code'],
				'products'			=> $args['packages'],
				'currency'			=> $args['currency'],
				'tariff'			=> $args['tariff_code'],
				'services'			=> $args['services']
			);
			
			$rate_hash 		= 'wc_ship_' . md5( wp_json_encode( $hash_data ) . WC_Cache_Helper::get_transient_version( 'edostavka_rate' ) );
			$session_key	= 'shipping_for_edostavka_rate_package_' . $rate_hash;
			$stored_rates 	= WC()->session->get( $session_key );
			
			if ( ! is_array( $stored_rates ) || $rate_hash !== $stored_rates['rate_hash'] ) {

				$response = $this->fetch_api( $args );

				WC()->session->set( $session_key, array(
						'rate_hash'	=> $rate_hash,
						'response'	=> $response,
					)
				);
			} elseif( isset( $stored_rates['response'] ) ) {
				$response = $stored_rates['response'];
			}
		
		} else {
			$response = $this->fetch_api( $args );
		}
		
		$result = json_decode( wp_remote_retrieve_body( $response ), true );
		wc_add_notice( sprintf( '<pre>%s</pre>', wc_print_r( $result, true ) ) );
		
		/*
		if ( is_wp_error( $response ) ) {
			wc_edostavka_add_log( sprintf('Ошибка при отправке запроса к серверу СДЭК: %s', $response->get_error_message() ) );
			
		} else {
			
			if ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
				wc_edostavka_add_log( sprintf('Сервер СДЭК вернул не корректный статус: %s', wp_remote_retrieve_response_code( $response ) ) );
			} else {
				$result = json_decode( wp_remote_retrieve_body( $response ), true );
				wc_add_notice( sprintf( '<pre>%s</pre>', wc_print_r( $result, true ) ) );
			}
		}
		*/
		/*
		if ( is_wp_error( $response ) ) {
			wc_edostavka_add_log( sprintf('Ошибка при отправке запроса к серверу СДЭК: %s', $response->get_error_message() ) );
		} elseif ( wp_remote_retrieve_response_code( $response ) == 200 ) {
			
			$result = json_decode( wp_remote_retrieve_body( $response ), true );
			
			wc_edostavka_add_log( sprintf('Ответ на запрос тарифа [%s]', $this->method_title ) );
			
			if( isset( $result['error'] ) ) {
				wc_edostavka_add_log( sprintf('Сервер СДЭК вернул ошибку: %s', print_r( wp_list_pluck( $result['error'], 'text', 'code' ), true ) ) );
			}
			
			if ( ! isset( $result['result'] ) ) {
				return;
			}
				
			wc_edostavka_add_log( sprintf('Сервер СДЭК вернул результат: %s', print_r( $result['result'], true ) ) );
			
			return $result['result'];
		
		} else {
			wc_edostavka_add_log( sprintf('Сервер СДЭК вернул не корректный статус: %s', wp_remote_retrieve_response_code( $response ) ) );
		}
		*/		
		return false;
	}
	
	public function fetch_api( $args ) {
		return wp_safe_remote_post( 'https://api.cdek.ru/v2/calculator/tariff', array(
				'timeout'	=> 10,
				'sslverify'	=> is_ssl(),
				'blocking'	=> true,
				'cookies'   => $_COOKIE,
				'headers'	=> array(
					'Content-Type' => 'application/json',
					'Authorization'	=> sprintf( 'Bearer %s', WC()->integrations->get_integration( 'edostavka-integration' )->get_access_token() )
				),
				'body'		=> json_encode( $args )
			)
		);
	}
}