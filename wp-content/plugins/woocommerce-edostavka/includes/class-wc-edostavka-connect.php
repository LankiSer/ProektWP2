<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
class WC_Edostavka_Connect {
	
	private $api_version = "1.0";
	
	private $api_url = 'http://api.cdek.ru/calculator/calculate_tarifflist.php';
	
	public function __construct( $package = array(), $args = array() ) {
		$this->date				= current_time( 'mysql' );
		$this->code				= $args['code'];
		$this->method_title		= $args['title'];
		$this->package 			= $package;
		$this->package_data		= new WC_Edostavka_Package( $package );
		$this->goods			= $this->get_goods( $package );
		$this->login			= apply_filters( 'woocommerce_edostavka_api_login', '' );
		$this->password			= apply_filters( 'woocommerce_edostavka_api_password', '' );
		
		add_filter( 'woocommerce_edostavka_webservice_url', array( $this, 'edostavka_calculator_url' ) );
	}
	
	private function get_goods( $package ) {
		$goods = array();
		$count  = 0;
		
		foreach ( $package['contents'] as $item_id => $values ) {
			if ( ! $values['data']->needs_shipping() ) {
				continue;
			}
			
			$_height = apply_filters( 'woocommerce_edostavka_package_height', wc_get_dimension( max( 0, $values['data']->get_height() ), 'cm' ) );
			$_width  = apply_filters( 'woocommerce_edostavka_package_width', wc_get_dimension( max( 0, $values['data']->get_width() ), 'cm' ) );
			$_length = apply_filters( 'woocommerce_edostavka_package_length', wc_get_dimension( max( 0, $values['data']->get_length() ), 'cm' ) );
			$_weight = apply_filters( 'woocommerce_edostavka_package_weight', wc_get_weight( max( 0, $values['data']->get_weight() ), 'kg' ) );
			
			$goods[ $count ] = array(
				'weight'	=> $_weight,
				'length'	=> $_length,
				'width'		=> $_width,
				'height'	=> $_height
			);
			
			if ( $values['quantity'] > 1 ) {
				$n = $count;
				for ( $i = 0; $i < $values['quantity']; $i++ ) {
					$goods[ $n ] = array(
						'weight'	=> $_weight,
						'length'	=> $_length,
						'width'		=> $_width,
						'height'	=> $_height
					);
					$n++;
				}
				$count = $n;
			} else {
				$count++;
			}
		}
		
		return $goods;
	}
	
	public function get_rate() {
		
		$rate = array();
		
		$args = array(
			'version'				=> $this->api_version,
			'receiverCountryCode'	=> $this->package['destination']['country'],
			'receiverCityId'		=> $this->package['destination']['state_id'],
			'receiverCity'			=> $this->package['destination']['city'],
			'receiverCityPostCode'	=> $this->package['destination']['postcode'],
			'senderCityId'			=> apply_filters( 'woocommerce_edostavka_city_origin', 0 ),
			'goods'					=> $this->goods,
			'tariffId'				=> $this->code,
			'currency'				=> get_woocommerce_currency(),
			'services'				=> array()
		);
		
		if( ! empty( $this->login ) && ! empty( $this->password ) ) {
			$args['authLogin'] 		= $this->login;
			$args['secure'] 		= md5( $this->date . '&' . $this->password );
			$args['dateExecute']	= $this->date;
		}
		
		if( 'yes' === wc_edostavka_integration_get_option( 'add_insurance_cost', 'no' ) && 'cod' == WC()->session->get('chosen_payment_method') ) {
			$args['services'] = array(
				array(
					'id'	=> 2,
					'param'	=> WC()->cart->get_cart_contents_total()
				)
			);
		}
		
		$args = apply_filters( 'woocommerce_edostavka_get_rate_atts', $args, $this );
		
		wc_edostavka_add_log( 'Параметры запроса: ' . wc_print_r( $args, true ) );
		
		if ( class_exists( 'WC_Cache_Helper' ) ) {
			
			$hash_data = array(
				'receiver_id'		=> $args['receiverCityId'],
				'receiver_country'	=> $args['receiverCountryCode'],
				'receiver_city'		=> $args['receiverCity'],
				'receiver_postcode'	=> $args['receiverCityPostCode'],
				'sender_id'			=> $args['senderCityId'],
				'products'			=> $args['goods'],
				'currency'			=> $args['currency'],
				'tariff'			=> $args['tariffId'],
				'login'				=> $this->login,
				'pass'				=> $this->password,
				'services'			=> $args['services']
			);
			
			$rate_hash 		= 'wc_ship_' . md5( wp_json_encode( $hash_data ) . WC_Cache_Helper::get_transient_version( 'edostavka_rate' ) );
			$session_key	= 'shipping_for_edostavka_rate_package_' . $rate_hash;
			$stored_rates 	= WC()->session->get( $session_key );
			
			if ( ! is_array( $stored_rates ) || $rate_hash !== $stored_rates['rate_hash'] ) {

				$response = $this->dispatch( $args );

				WC()->session->set( $session_key, array(
						'rate_hash'	=> $rate_hash,
						'response'	=> $response,
					)
				);
			} elseif( isset( $stored_rates['response'] ) ) {
				$response = $stored_rates['response'];
			}
		
		} else {
			$response = $this->dispatch( $args );
		}
		
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
				
		return false;
	}
	
	private function dispatch( $args ) {
		return wp_safe_remote_post( apply_filters( 'woocommerce_edostavka_webservice_url', $this->api_url ), array(
				'timeout'	=> 10,
				'sslverify'	=> is_ssl(),
				'blocking'	=> true,
				'cookies'   => $_COOKIE,
				'headers'	=> array( 'Content-Type' => 'application/json' ),
				'body'		=> json_encode( $args )
			)
		);
	}
	
	public function edostavka_calculator_url( $url ) {
		if( wc_site_is_https() ) {
			$url = str_replace( 'http://', 'https://', $url );
		}
		return $url;
	}
}