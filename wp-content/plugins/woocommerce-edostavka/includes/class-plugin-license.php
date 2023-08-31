<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WD_Plugin_License_Legacy {
	
	private static $status;
	
	public static function init() {
		self::$status = get_option( 'wc_edostavka_license_status', 'activate_license' );
		add_filter( 'woocommerce_edostavka_shipping_methods_classes', array( __CLASS__, 'methods_classes' ) );
	}
	
	public static function methods_classes( $classes ) {
		
		if( defined( 'YWTENV_INIT' ) ) {
			return $classes;
		}
		
		$settings 		= get_option( 'woocommerce_edostavka-integration_settings' );
		$license_key 	= isset( $settings['license_key'] ) ? trim( $settings['license_key'] ) : null;
		$license_params = array(
			'edd_action'	=> 'check_license',
			'license'		=> $license_key,
			'item_id'		=> WC_Edostavka::WD_ITEM,
			'version'		=> WC_Edostavka::VERSION,
			'status'		=> self::$status,
			'url'        	=> home_url()
		);
		
		$transient_name = 'wc_edostavka_license_status_' . md5( wp_json_encode( $license_params ) );
		$license = get_transient( $transient_name );
		
		if( false === $license || 'valid' !== $license->license ) {
			$request = self::check_license( $license_params );
			if( $request && is_object( $request ) ) {
				$license = $request;
				set_transient( $transient_name, $license, DAY_IN_SECONDS * 2 );
			}
		}
		
		if( ! $license || ( isset( $license->license ) && 'valid' !== $license->license ) ) {
			$classes = array(
				'edostavka-package-door' => 'WC_Edostavka_Shipping_Package_Door'
			);
		}
		
		return $classes;
	}
	
	public static function check_license( $params = array() ) {
		
		$api_params = wp_parse_args( $params, array(
			'edd_action'	=> 'check_license',
			'license'		=> '',
			'item_id'		=> '',
			'url'        	=> home_url()
		) );
		
		$request = wp_remote_post( 'https://woodev.ru', array(
			'timeout' 	=> 10,
			'body' 		=> $api_params,
			'sslverify'	=> apply_filters( 'https_local_ssl_verify', false )
		) );
		
		if ( ! is_wp_error( $request ) ) {
			return json_decode( wp_remote_retrieve_body( $request ) );
		}

		return false;
	}
	
	public static function request( $params = array() ) {
		
		$params = wp_parse_args( $params, array(
			'edd_action'	=> self::$status,
			'license'		=> '',
			'item_id'		=> '',
			'url'        	=> home_url()
		) );
		
		try {
			$response = wp_remote_post( 'https://woodev.ru', array( 
				'timeout' 	=> 10,
				'body' 		=> $params,
				'sslverify'	=> apply_filters( 'https_local_ssl_verify', false )
			) );
			
			if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
				throw new Exception( 'При проверке лицензии произошла ошибка. Попробуй еще раз.' );
			} else {
				
				$license_data = json_decode( wp_remote_retrieve_body( $response ) );
				
				if ( false === $license_data->success && isset( $license_data->error ) ) {
					
					if( self::$status !== 'activate_license' ) {
						update_option( 'wc_edostavka_license_status', 'activate_license' );
					}
					
					switch( $license_data->error ) {
						case 'expired' :
							throw new Exception( sprintf(
								'Срок действия вашего лицензионного ключа %s.',
								date_i18n( get_option( 'date_format' ), strtotime( $license_data->expires, current_time( 'timestamp' ) ) )
							) );
							break;
						case 'revoked' :
							throw new Exception( 'Ваш лицензионный ключ отключен.' );
							break;
						case 'missing' :
							throw new Exception( 'Недействительная лицензия.' );
							break;
						case 'invalid' :
						case 'site_inactive' :
							throw new Exception( 'Ваша лицензия не активна для этого сайта.' );
							break;
						case 'item_name_mismatch' :
							throw new Exception( sprintf( 'Это недействительный лицензионный ключ для %s.', 'CDEK WooCommerce Shipping Method' ) );
							break;
						case 'no_activations_left':
							throw new Exception( 'Ваш лицензионный ключ достиг своего предела активации.' );
							break;
						default :
							throw new Exception( sprintf( 'Произошла ошибка %s. Пожалуйста, попробуйте еще раз.', $license_data->error ) );
							break;
					}
				} elseif( $license_data->success && ! empty( $license_data->license ) ) {
					
					switch( $license_data->license ) {
						case 'valid' :
							if( self::$status !== 'check_license' ) {
								update_option( 'wc_edostavka_license_status','check_license' );
							}
							throw new Exception( sprintf( 'Ваша лицензия для %s активна до %s.', $license_data->item_name, date_i18n( get_option( 'date_format' ), strtotime( $license_data->expires, current_time( 'timestamp' ) ) ) ) );
							break;
						case 'invalid' :
						case 'site_inactive' :
							if( self::$status !== 'activate_license' ) {
								update_option( 'wc_edostavka_license_status','activate_license' );
							}
							throw new Exception( 'Ваша лицензия не активна для этого сайта.' );
							break;
						default :
							throw new Exception( sprintf( 'Произошла ошибка %s. Пожалуйста, попробуйте еще раз.', $license_data->license ) );
							break;
					}
					
				}
			}
		
		} catch ( Exception $e ) {
			return $e->getMessage();
		}	
	}
}

WD_Plugin_License_Legacy::init();