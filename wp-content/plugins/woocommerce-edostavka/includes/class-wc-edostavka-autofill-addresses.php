<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Edostavka_Autofill_Addresses {

	public static $table = 'edostavka_address';
	
	protected $ajax_endpoint = 'edostavka_autofill_address';
	
	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
	}
	
	public function init() {

		$this->maybe_install();
			
		add_action( 'wc_ajax_' . $this->ajax_endpoint, array( $this, 'ajax_autofill' ) );
		add_action( 'wp_ajax_' . $this->ajax_endpoint, array( $this, 'ajax_autofill' ) );
	}
	
	protected static function get_validity() {
		return apply_filters( 'woocommerce_edostavka_autofill_addresses_validity', 'forever' );
	}
	
	public static function get_all_address() {
		global $wpdb;
		$table = $wpdb->prefix . self::$table;
		return $wpdb->get_results( "SELECT * FROM $table ORDER BY count_query", ARRAY_A );
	}
	
	public static function get_city_by_id( $city_id = 0 ) {
		global $wpdb;
		$table = $wpdb->prefix . self::$table;
		$city = $wpdb->get_row( $wpdb->prepare( "SELECT city_id, country, city_name, state, full_name FROM $table WHERE 1 = 1 AND city_id = %d", $city_id ), ARRAY_A );
		
		if( ! isset( $city['city_id'] ) || empty( $city['city_id'] ) ) {
			$city = self::get_remote_city_by( array( 'cityCode' => $city_id ) );
			if( $city && ! empty( $city ) ) {
				self::save_address( $city );
			}
		}
		
		return apply_filters( 'woocommerce_edostavka_autofill_addresses_get_city_by_id', array(
			'city_id' 	=> isset( $city['city_id'] ) ? $city['city_id'] : null,
			'country' 	=> isset( $city['country'] ) ? $city['country'] : null,
			'state' 	=> isset( $city['state'] ) ? $city['state'] : null,
			'city_name' => isset( $city['city_name'] ) ? $city['city_name'] : null,
			'full_name' => isset( $city['full_name'] ) ? $city['full_name'] : null,
		), $city_id );
	}
	
	public static function get_remote_city_by( $query = array() ) {
		$addreses = array();
		
		$query = wp_parse_args( $query, array(
			'lang'			=> 'rus',
			'page'			=> 0,
			'size'			=> 100,
			'countryCode'	=> 'RU',
			'cityCode'		=> ''
		) );
		
		$query_url = add_query_arg( $query, esc_url( 'http://integration.cdek.ru/v1/location/cities/json' ) );
		$response = wp_safe_remote_get( $query_url );
		
		if ( ! is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) === 200 ) {
			$result = json_decode( wp_remote_retrieve_body( $response ), true );
			
			if( ! empty( $result ) ) {
				$search_args = array();
				
				foreach( array( 'countryCode', 'cityName', 'cityCode' ) as $field_name ) {
					
					if( isset( $query[ $field_name ] ) && ! empty( $query[ $field_name ] ) ) {
						$search_args[ $field_name ] = $query[ $field_name ];
					}
				}
				
				$cities = wp_list_filter( $result, $search_args );
				
				if( ! empty( $cities ) ) {
					return apply_filters( 'woocommerce_edostavka_autofill_addresses_get_remote_city_by', array(
						'city_id' 	=> $cities[0]['cityCode'],
						'country'	=> $cities[0]['countryCode'],
						'state'		=> $cities[0]['region'],
						'city_name'	=> $cities[0]['cityName'],
						'full_name'	=> sprintf( '%s (%s)', $cities[0]['cityName'], $cities[0]['region'] )
					), $query );
				}

			}
		} elseif( is_wp_error( $response ) ) {
			throw new Exception( $response->get_error_message() );
		}
		
		return false;
	}
	
	public static function get_address( $city_name, $country = '' ) {
		global $wpdb;
		
		if ( empty( $city_name ) ) {
			return null;
		}
		
		$country = ! empty( $country ) ? $country : WC()->countries->get_base_country();
		
		$table    = $wpdb->prefix . self::$table;
		$address  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table WHERE country = %s AND city_name LIKE %s;", $country, $wpdb->esc_like( $city_name ) . '%' ), ARRAY_A );
		
		try {
			
			if ( empty( $address ) || is_null( $address ) ) {
				$address = self::fetch_address( $city_name, $country );
				if ( ! empty( $address ) && is_array( $address ) ) foreach( $address as $new_address ) self::save_address( $new_address );		
			} else {
				
				$_address = array_merge( $address, self::fetch_address( $city_name, $country ) );
				
				$address = self::array_unique_deep( $_address, 'city_id' );
				
				foreach( $address as $has_address ) {
					if( ! isset( $has_address['is_new'] ) && self::check_if_expired( $has_address['last_query'] ) ) {
						self::update_address( $has_address );
					} elseif( isset( $has_address['is_new'] ) && $has_address['is_new'] ) {
						self::save_address( $has_address );
					}
				}
			}
			
			return apply_filters( 'woocommerce_edostavka_autofill_addresses_get_address', $address, $city_name, $country );
		
		} catch( Exception $e ) {
			throw $e;
		}
	}
	
	private static function array_unique_deep( $array, $key ) {
		$result = array();
		$keys = self::disallow_ids();
		foreach( $array as $index => $value ) {
			if( isset( $value[$key] ) && ! in_array( $value[$key], $keys ) ) {
				$keys[] = $value[$key];
				$result[$index] = $value;
			}
		}
		
		return $result;
	}
	
	protected static function check_if_expired( $last_query ) {
		$validity = self::get_validity();
		
		if ( 'forever' !== $validity && strtotime( '+' . $validity . ' months', strtotime( $last_query ) ) < current_time( 'timestamp' ) ) {
			return true;
		}
		return false;
	}
	
	protected static function save_address( $address ) {
		global $wpdb;
		
		if( isset( $address['is_new'] ) ) unset( $address['is_new'] );
		
		$default = array(
			'city_name'		=> '',
			'full_name'		=> '',
			'city_id'		=> '',
			'state'			=> '',
			'country'		=> '',
			'count_query'	=> 1,
			'last_query'	=> current_time( 'mysql' ),
		);
		
		$table    = $wpdb->prefix . self::$table;
		if( $found_address = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table WHERE city_id = %s", $address['city_id'] ), ARRAY_A ) ) {
			if( is_array( $found_address ) ) foreach( $found_address as $found ) self::delete_address( $found['city_name'], $found['state'], $found['country'] );
		}
		
		$address = wp_parse_args( $address, $default );
		
		$result = $wpdb->insert(
			$wpdb->prefix . self::$table,
			$address,
			array( '%s', '%s', '%s', '%s', '%s', '%d', '%s' )
		);
		
		return false !== $result;
	}
	
	protected static function delete_address( $city, $state, $country ) {
		global $wpdb;
		$wpdb->delete( $wpdb->prefix . self::$table, array( 'city_name' => $city, 'state' => $state, 'country' => $country ), array( '%s', '%s', '%s' ) );
	}
	
	protected static function update_address( $address ) {
		self::delete_address( $address['city_name'], $address['state'], $address['country'] );
		return self::save_address( $address );
	}
	
	protected static function fetch_address( $city_name, $country ) {
	
		$addreses = array();
		
		$query_url = add_query_arg( array( 'q' => $city_name, 'countryCodeList' => $country ), esc_url( 'https://api.cdek.ru/city/getListByTerm/json.php' ) );
		$response = wp_safe_remote_get( $query_url, array( 'sslverify' => false, 'timeout' => 10 ) );
		
		if ( ! is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) === 200 ) {
			$result = json_decode( wp_remote_retrieve_body( $response ), true );
			if( ! empty( $result['geonames'] ) && is_array( $result['geonames'] ) ) foreach( $result['geonames'] as $address ) {
				
				if( empty( $address['id'] ) || in_array( $address['id'], self::disallow_ids() ) ) continue;
				
				/*
				* Переписываем значение cityName если в названии города так же содержится название региона.
				* Характерно для Белорусии. Когда название населёного типа имеет такое значение как "Минск, Минская обл."
				*/
				if( $address['regionName'] && ! empty( $address['regionName'] ) ) {
					list( $city, $region ) = explode( ', ', $address['cityName'] );
					if( $address['regionName'] == $region ) {
						$address['cityName'] = $city;
					}
				}
				
				$addreses[] = array(
					'city_id'		=> $address['id'],
					'city_name'		=> $address['cityName'],
					'full_name'		=> $address['name'],
					'state'			=> isset( $address['regionName'] ) ? $address['regionName'] : '',
					'country'		=> $address['countryIso'],
					'last_query'	=> current_time( 'mysql' ),
					'is_new'		=> true
				);
			}
		} elseif( is_wp_error( $response ) ) {
			throw new Exception( sprintf( __( 'Error: %s', 'woocommerce' ), $response->get_error_message() ) );
		}
		
		return $addreses;
	}
	
	public function ajax_autofill() {
		if( empty( $_POST['city_name'] ) ) {
			wp_send_json_success( self::get_preloaded_cities() );
			//wp_send_json_error( array( 'message' => 'Не указан параметр Город' ) );
		}
		
		$city_name = trim( wp_unslash( $_POST['city_name'] ) );
		$default_location = wc_get_customer_default_location();
		$country = isset( $_POST['country'] ) ? esc_attr( $_POST['country'] ) : $default_location['country'];
		
		$address = self::get_address( $city_name, $country );
		
		if ( empty( $address ) ) {
			wp_send_json_error( array( 'message' => sprintf( 'По запросу %s ничего не найдено', $city_name ) ) );
		}
		
		$address = array_filter( array_map( array( $this, 'clean_address_data' ), $address ) );
		
		wp_send_json_success( $address );
	}
	
	protected function clean_address_data( $array ) {
		unset( $array['ID'] );
		unset( $array['last_query'] );
		return $array;
	}
	
	public function maybe_install() {
		$version = get_option( 'woocommerce_edostavka_autofill_addresses_db_version' );
		if ( empty( $version ) || version_compare( $version, '1.0.1', '<' ) ) {
			self::create_database();
			update_option( 'woocommerce_edostavka_autofill_addresses_db_version', '1.0.1' );
		}
	}
	
	private static function disallow_ids() {
		return apply_filters( 'woocommerce_edostavka_disallow_city_ids', array(
			'79395',
			'76380',
			'55949',
			'78194',
			'80655'
		) );
	}
	
	public static function get_preloaded_cities() {
		return apply_filters( 'woocommerce_edostavka_preloaded_cities_list', array(
			array(
				'city_id'		=> 44,
				'city_name'		=> 'Москва',
				'full_name'		=> 'Москва, Москва, Россия',
				'state'			=> 'Москва',
				'country'		=> 'RU',
			),
			array(
				'city_id'		=> 137,
				'city_name'		=> 'Санкт-Петербург',
				'full_name'		=> 'Санкт-Петербург, Санкт-Петербург, Россия',
				'state'			=> 'Санкт-Петербург',
				'country'		=> 'RU',
			),
			array(
				'city_id'		=> 424,
				'city_name'		=> 'Казань',
				'full_name'		=> 'Казань, Татарстан респ., Россия',
				'state'			=> 'Татарстан респ.',
				'country'		=> 'RU',
			),
			array(
				'city_id'		=> 94,
				'city_name'		=> 'Владимир',
				'full_name'		=> 'Владимир, Владимирская обл., Россия',
				'state'			=> 'Владимирская обл.',
				'country'		=> 'RU',
			),
			array(
				'city_id'		=> 506,
				'city_name'		=> 'Воронеж',
				'full_name'		=> 'Воронеж, Воронежская обл., Россия',
				'state'			=> 'Воронежская обл.',
				'country'		=> 'RU',
			),
			array(
				'city_id'		=> 414,
				'city_name'		=> 'Нижний Новгород',
				'full_name'		=> 'Нижний Новгород, Нижегородская обл., Россия',
				'state'			=> 'Нижегородская обл.',
				'country'		=> 'RU',
			),
			array(
				'city_id'		=> 250,
				'city_name'		=> 'Екатеринбург',
				'full_name'		=> 'Екатеринбург, Свердловская обл., Россия',
				'state'			=> 'Свердловская обл.',
				'country'		=> 'RU',
			),
			array(
				'city_id'		=> 152,
				'city_name'		=> 'Калининград',
				'full_name'		=> 'Калининград, Калининградская обл., Россия',
				'state'			=> 'Калининградская обл.',
				'country'		=> 'RU',
			),
			array(
				'city_id'		=> 437,
				'city_name'		=> 'Сочи',
				'full_name'		=> 'Сочи, Краснодарский край, Россия',
				'state'			=> 'Краснодарский край',
				'country'		=> 'RU',
			)
		) );
	}

	public static function create_database() {
		global $wpdb;
		
		$wpdb->hide_errors();
		
		$charset_collate = $wpdb->get_charset_collate();
		
		$table_name = $wpdb->prefix . self::$table;
		
		if ( $wpdb->get_var( "SHOW TABLES LIKE $table_name;" ) ) {
			if( ! $wpdb->get_var( "SHOW COLUMNS FROM `$table_name` LIKE 'full_name';" ) ) {
				$wpdb->query( "ALTER TABLE $table_name ADD `full_name` varchar(200);" );
			}
		}
		
		$sql = "CREATE TABLE $table_name (
			ID bigint(20) NOT NULL auto_increment,
			city_name longtext NULL,
			city_id bigint(20) NULL,
			state longtext NULL,
			country char(2) NULL,
			count_query bigint(20) NULL,
			last_query datetime NULL,
			full_name varchar(200) NULL,
			PRIMARY KEY  (ID),
			KEY city_id (city_id)
		) $charset_collate;";
		
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		
		dbDelta( $sql );
	}
}

new WC_Edostavka_Autofill_Addresses();