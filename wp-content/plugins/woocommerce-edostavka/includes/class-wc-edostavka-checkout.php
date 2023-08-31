<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Edostavka_Checkout {

	public function __construct() {
		
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), PHP_INT_MAX );
		
		add_action( 'wc_ajax_edostavka_set_delivery_point', array( $this, 'set_delivery_point' ) );
		add_action( 'wc_ajax_edostavka_get_city_by_id', array( $this, 'get_city_by_id' ) );
		add_action( 'wc_ajax_edostavka_get_suggestions_address', array( $this, 'get_suggestions_address' ) );
		
		add_filter( 'woocommerce_checkout_fields', array( $this, 'checkout_fields' ), 15 );
		add_filter( 'woocommerce_default_address_fields', array( $this, 'default_address_fields' ), 15 );
		add_filter( 'default_checkout_billing_address_1', array( $this, 'default_checkout_billing_address_1' ) );
		add_filter( 'woocommerce_form_field_hidden', array( $this, 'form_field_hidden' ), 10, 4 );
		add_filter( 'woocommerce_form_field_city', array( $this, 'form_field_city' ), 10, 3 );
		add_filter( 'woocommerce_form_field_delivery_points', array( $this, 'form_field_delivery_points' ), 10, 3 );
		add_action( 'woocommerce_checkout_update_order_review', array( $this, 'update_order_review' ) );
		add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'order_review_fragments' ) );
		add_filter( 'woocommerce_cart_shipping_packages', array( $this, 'shipping_packages' ), 10 );
		add_action( 'woocommerce_created_customer', array( $this, 'created_customer' ) );
		add_action( 'woocommerce_after_save_address_validation', array( $this, 'address_validation' ), 10, 3 );
		add_action( 'woocommerce_after_checkout_validation', array( $this, 'validate_checkout' ), 10, 2 );
		
		add_shortcode( 'edostavka_map', array( $this, 'create_map' ) );
		
		add_action( 'woocommerce_review_order_after_shipping', array( $this, 'add_delivery_points_button' ) );
		add_action( 'wp_footer', array( $this, 'add_map_template' ) );
	}
	
	public function enqueue_scripts() {
		
		if( is_admin() ) {
			return;
		}
		
		$map_lang = ( get_locale() && in_array( get_locale(), array( 'ru_RU', 'en_US', 'en_RU', 'ru_UA', 'uk_UA', 'tr_TR' ), true ) ) ? get_locale() : 'ru_RU';
		
		$map_api_url = add_query_arg( array(
			'load'		=> 'package.standard',
			'lang' 		=> $map_lang,
			'ns'		=> 'WCEdostavkaMaps',
			'apikey' 	=> apply_filters( 'wc_edostavka_yandex_map_apikey', '38f393c8-f1fa-4b1a-a356-b8d9752ff229' )
		), 'https://api-maps.yandex.ru/2.1/' );
		
		if( ! wp_style_is( 'dashicons', 'registered' ) ) {
			wp_register_style( 'dashicons', '/wp-includes/css/dashicons.css' );
		}
		
		wp_register_style( 'wc-edostavka', plugins_url( '/assets/css/edostavka.css' , WC_Edostavka::get_main_file() ), array() );
		wp_register_style( 'wc-edostavka-checkout', plugins_url( '/assets/css/checkout.css' , WC_Edostavka::get_main_file() ) );
		wp_register_style( 'jquery-confirm', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.2/jquery-confirm.min.css', array(), '3.3.2' );
		
		if( ! wp_script_is( 'selectWoo', 'registered' ) ) {
			wp_register_script( 'selectWoo', plugins_url( 'assets/js/selectWoo/selectWoo.full.js', WC_PLUGIN_FILE ), array( 'jquery' ), '1.0.6', true );
		}
		
		if( ! wp_script_is( 'select2', 'registered' ) ) {
			wp_register_script( 'select2', plugins_url( 'assets/js/select2/select2.full.js', WC_PLUGIN_FILE ), array( 'jquery' ), '4.0.3', true );
		}
		
		if( ! wp_script_is( 'wc-backbone-modal', 'registered' ) ) {
			wp_register_script( 'wc-backbone-modal', plugins_url( 'assets/js/admin/backbone-modal.js', WC_PLUGIN_FILE ), array( 'underscore', 'backbone', 'wp-util' ), WC_VERSION );
		}
		
		wp_register_script( 'edostavka-script', plugins_url( '/assets/js/edostavka.js' , WC_Edostavka::get_main_file() ), array( 'selectWoo', 'woodev-yandex-map-plugin' ), WC_Edostavka::VERSION, true );
		wp_register_script( 'edostavka-map-script', plugins_url( '/assets/js/edostavka-map.js' , WC_Edostavka::get_main_file() ), array( 'selectWoo' ), WC_Edostavka::VERSION, true );
		wp_register_script( 'edostavka-edit-address', plugins_url( '/assets/js/edit-address.js' , WC_Edostavka::get_main_file() ), array( 'select2' ), WC_Edostavka::VERSION, true );
		wp_register_script( 'edostavka-yandex-map', $map_api_url, array( 'edostavka-script' ), '2.1', true );
		wp_register_script( 'woodev-yandex-map-plugin', plugins_url( '/assets/js/woodev-yandex-map-plugin.js' , WC_Edostavka::get_main_file() ), array( 'jquery', 'underscore', 'backbone', 'wp-util', 'jquery-blockui', 'jquery-confirm' ), WC_Edostavka::VERSION, true );
		wp_register_script( 'edostavka-checkout', plugins_url( '/assets/js/checkout.js' , WC_Edostavka::get_main_file() ), array( 'jquery', 'jquery-ui-autocomplete' ), WC_Edostavka::VERSION, true );
		wp_register_script( 'edostavka_map_widget', plugins_url( '/assets/js/widget-map.js' , WC_Edostavka::get_main_file() ), array( 'jquery', 'woodev-yandex-map-plugin', 'wc-backbone-modal' ), WC_Edostavka::VERSION, true );
		wp_register_script( 'jquery-confirm', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.2/jquery-confirm.min.js', array( 'jquery' ), '3.3.2' );
		
		$chosen_method = $this->edostavka_chosen_method();
		$delivery_type = wc_edostavka_convert_method_delivery_type( $chosen_method );
		$chosen_delivery_point = $delivery_type ? wc_edostavka_get_delivery_point_data( wc_edostavka_get_customer_state_id(), $delivery_type ) : false;
		
		wp_localize_script( 'edostavka-script', 'edostavka_params', array(
			'ajax_url'					=> WC_AJAX::get_endpoint( 'edostavka_autofill_address' ),
			'set_delivery_point_ajax'	=> WC_AJAX::get_endpoint( 'edostavka_set_delivery_point' ),
			'chosen_delivery_point'		=> $chosen_delivery_point && isset( $chosen_delivery_point['id'] ) ? $chosen_delivery_point['id'] : null,
			'country_iso' 				=> WC()->customer ? WC()->customer->get_billing_country() : 'RU',
			'pin_icon'					=> plugins_url( '/assets/img/cdek-map-pin-icon.png' , WC_Edostavka::get_main_file() ),
			'enable_custom_city'		=> wc_bool_to_string( wc_edostavka_integration_get_option( 'enable_custom_city' ) ),
			'customer_state_id'			=> wc_edostavka_get_customer_state_id(),
			'delivery_points_field'		=> wc_edostavka_integration_get_option( 'delivery_points_field_type', 'text' ),
			'format_city'				=> wc_edostavka_integration_get_option( 'format_city' ),
			'only_current_country'		=> wc_edostavka_integration_get_option( 'only_current_country', 'yes' ),
			'add_insurance_cost'		=> wc_edostavka_integration_get_option( 'add_insurance_cost', 'no' ),
			'allowed_zone_locations'	=> edostavka_get_allowed_zone_locations(),
			'dropdown_cities_list'		=> wc_edostavka_integration_get_option( 'disable_dropdown_cities_list', 'no' ),
			'suggestions_address'		=> wc_edostavka_integration_get_option( 'enable_suggestions_address', 'no' ),
			'suggestions_fill_postcode'	=> apply_filters( 'wc_edostavka_suggestions_fill_postcode', true, $this ),
			'map_position'				=> wc_edostavka_integration_get_option( 'delivery_point_map_position', 'popup_widget' ),
			'show_search_field'			=> wc_edostavka_integration_get_option( 'show_search_field_on_map', 'yes' ),
			'disable_postcode_field'	=> wc_edostavka_integration_get_option( 'disable_postcode_field', 'yes' ),
			'points_url'				=> WC_AJAX::get_endpoint( 'edostavka_get_delivery_points' ),
			'active_point_icon'			=> plugins_url( '/assets/img/cdek-map-pin-active-icon.png' , WC_Edostavka::get_main_file() ),
			'point_icon'				=> plugins_url( '/assets/img/cdek-map-pin-non-active-icon.png' , WC_Edostavka::get_main_file() ),
			'pvz_icon'					=> plugins_url( '/assets/img/pinPVZ.svg' , WC_Edostavka::get_main_file() ),
			'postamat_icon'				=> plugins_url( '/assets/img/pinPostamat.svg' , WC_Edostavka::get_main_file() ),
			'i18n_strings'				=> array(
				'search_control_placeholder' 	=> __( 'Enter text for find pick-up point', 'woocommerce-edostavka' ),
				'select_city'					=> __( 'Select city', 'woocommerce-edostavka' ),
				'enter_sity_name'				=> __( 'Enter name of city', 'woocommerce-edostavka' )
			)
		) );
		
		if( is_checkout() ) {
			wp_enqueue_style( 'wc-edostavka' );
			wp_enqueue_style( 'dashicons' );
			wp_enqueue_style( 'wc-edostavka-checkout' );
			wp_enqueue_style( 'jquery-confirm' );
			wp_enqueue_style( 'jquery-ui', WC()->plugin_url() . '/assets/css/jquery-ui/jquery-ui.min.css', array() );
			wp_enqueue_script( 'edostavka-yandex-map' );
			wp_enqueue_script( 'edostavka-script' );
			wp_enqueue_script( 'edostavka-checkout' );
			
			if( 'popup_widget' == wc_edostavka_integration_get_option( 'delivery_point_map_position', 'popup_widget' ) ) {
				wp_enqueue_script( 'edostavka_map_widget' );
			}
			
		}
		
		if( is_wc_endpoint_url( 'edit-address' ) ) {
			wp_enqueue_style( 'edostavka-edit-address', plugins_url( '/assets/css/edit-address.css' , WC_Edostavka::get_main_file() ) );
			wp_enqueue_script( 'edostavka-edit-address' );
			wp_localize_script( 'edostavka-edit-address', 'edostavka_edit_address_params', array(
				'ajax_url'	=> WC_AJAX::get_endpoint( 'edostavka_autofill_address' ),
				'state_id'	=> wc_edostavka_get_customer_state_id(),
				'i18n_no_matches'           => _x( 'No matches found', 'enhanced select', 'woocommerce' ),
				'i18n_ajax_error'           => _x( 'Loading failed', 'enhanced select', 'woocommerce' ),
				'i18n_input_too_short_1'    => _x( 'Please enter 1 or more characters', 'enhanced select', 'woocommerce' ),
				'i18n_input_too_short_n'    => _x( 'Please enter %qty% or more characters', 'enhanced select', 'woocommerce' ),
				'i18n_input_too_long_1'     => _x( 'Please delete 1 character', 'enhanced select', 'woocommerce' ),
				'i18n_input_too_long_n'     => _x( 'Please delete %qty% characters', 'enhanced select', 'woocommerce' ),
				'i18n_selection_too_long_1' => _x( 'You can only select 1 item', 'enhanced select', 'woocommerce' ),
				'i18n_selection_too_long_n' => _x( 'You can only select %qty% items', 'enhanced select', 'woocommerce' ),
				'i18n_load_more'            => _x( 'Loading more results&hellip;', 'enhanced select', 'woocommerce' ),
				'i18n_searching'            => _x( 'Searching&hellip;', 'enhanced select', 'woocommerce' )
			) );
		}
		
		if( wc_post_content_has_shortcode( 'edostavka_map' ) ) {
			wp_enqueue_style( 'select2' );
			wp_enqueue_style( 'wc-edostavka' );
			wp_enqueue_script( 'edostavka-yandex-map' );
			wp_enqueue_script( 'edostavka-map-script' );
			wp_localize_script( 'edostavka-map-script', 'edostavka_map_params', array(
				'ajax_url'		=> WC_AJAX::get_endpoint( 'edostavka_autofill_address' ),
				'points_url'	=> WC_AJAX::get_endpoint( 'edostavka_get_delivery_points' ),
				'active_icon'	=> plugins_url( '/assets/img/cdek-map-pin-active-icon.png' , WC_Edostavka::get_main_file() ),
				'na_active_icon'	=> plugins_url( '/assets/img/cdek-map-pin-non-active-icon.png' , WC_Edostavka::get_main_file() )
			) );
		}
		
	}
	
	public function set_delivery_point() {
		
		if( isset( $_POST['code'] ) ) {
			try {
				
				wc_edostavka_set_delivery_point_data( array(
					'point_id'	=> wc_clean( $_POST['code'] ),
					'type' 		=> strtolower( wc_clean( $_POST['type'] ) ),
					'address' 	=> wc_clean( $_POST['address'] )
				) );
				
				wp_send_json_success( sprintf( 'Значение %s установлено.', $_POST['code'] ) );
			
			} catch( Exception $e ) {
				wp_send_json_error( $e->getMessage() );
			}
		}
		
		wp_send_json_error( 'Пустой запрос' );
	}
	
	public function get_city_by_id() {
		if( isset( $_POST['city_id'] ) ) {
			wp_send_json_success( WC_Edostavka_Autofill_Addresses::get_city_by_id( wc_clean( $_POST['city_id'] ) ) );
		}
		
		wp_send_json_error( 'Для получения инофрмации о населенном пункте, необходимо передать ID данного города.' );
	}
	
	public function get_suggestions_address() {
		$request_body = file_get_contents( 'php://input' );
		$data = json_decode( $request_body, true );
		
		if( isset( $data['address'] ) ) {
			try {
				
				$dadata_data = edostavka_get_suggestions_address_via_dadata( $data );
				wp_send_json_success( $dadata_data );
			} catch( Exception $e ) {
				wp_send_json_error( $e->getMessage() );
			}
			
		}
		
		wp_send_json_error( 'Не передан обязаетельный параметр - Адрес' );
	}
	
	public function checkout_fields( $fields ) {
		
		$edostavka_method = $this->edostavka_chosen_method();
		
		if( 'hidden' === get_option( 'woocommerce_checkout_address_2_field', 'optional' ) && isset( $fields['billing']['billing_address_2'] ) ) {
			unset( $fields['billing']['billing_address_2'] );
		}
		
		if( 'yes' == wc_edostavka_integration_get_option( 'hide_single_counrty' ) && 1 === sizeof( WC()->countries->get_shipping_countries() ) ) {
			$fields['billing']['billing_country']['class'] = array( 'hidden' );
		}
		
		if( 'yes' == wc_edostavka_integration_get_option( 'required_address_1' ) && ! $edostavka_method ) {
			$fields['billing']['billing_address_1']['required'] = false;
		}
		
		if( $edostavka_method && in_array( $edostavka_method->get_delivery_type(), array( 'stock', 'postamat' ), true ) && 'text' == wc_edostavka_integration_get_option( 'delivery_points_field_type', 'text' ) ) {
			//$fields['billing']['billing_address_1']['value'] = '';
		}
				
		return $fields;
	}
	
	public function default_address_fields( $fields ) {
		
		$is_frontend = ! ( is_cart() || is_account_page() || is_checkout() || is_customize_preview() ) ? false : true;
		
		$cities_list = array( '' => '' );
		$customer_state_id = wc_edostavka_get_customer_state_id();
		
		if( ! empty( $customer_state_id ) && $customer_state_id > 0 ) {
			
			$cities_list = WC_Edostavka_Autofill_Addresses::get_city_by_id( $customer_state_id );
			
			if( $is_frontend && $cities_list > 0 ) {
				$cities_list = array( $cities_list['city_name'] => $cities_list['city_name']  );
			}
		}
		
		
		$edostavka_method = $this->edostavka_chosen_method();
		$delivery_points_field_type = wc_edostavka_integration_get_option( 'delivery_points_field_type', 'text' );
		
		$fields = array_merge( $fields, array(
			'state' => array(
				'type'         => 'text',
				'label'        => isset( $fields['state']['label'] ) ? $fields['state']['label'] : __( 'State / County', 'woocommerce' ),
				'required'     => false,
				'class'        => 'yes' == wc_edostavka_integration_get_option( 'disable_state_field', 'yes' ) ? array( 'hidden' ) : $fields['state']['class'],
				'priority'	  => isset( $fields['state']['priority'] ) ? $fields['state']['priority'] : 0
			),
			'state_id'	=> array(
				'label'       => __( 'City ID', 'woocommerce-edostavka' ),
				'type'		  => 'hidden',
				'default'	  => $customer_state_id,
				'required'    => false,
				'priority'	  => 0
			),
			'city' => array(
				'label'        => isset( $fields['city']['label'] ) ? $fields['city']['label'] : __( 'Town / City', 'woocommerce' ),
				'placeholder'  => isset( $fields['city']['placeholder'] ) ? $fields['city']['placeholder'] : __( 'Select city', 'woocommerce-edostavka' ),
				'required'     => $edostavka_method ? true : ( isset( $fields['city']['required'] ) ? $fields['city']['required'] : true ),
				'class'        => array( 'form-row-wide', 'address-field' ),
				'class_input'  => array( 'state_select' ),
				'type' 		   => 'select',
				'options'	   => $cities_list,
				'priority'     => isset( $fields['city']['priority'] ) ? $fields['city']['priority'] : 50
			)
		) );
		
		if( 'under_address' == wc_edostavka_integration_get_option( 'delivery_point_map_position', 'popup_widget' ) ) {
			
			$fields['delivery_points']	= array(
				'label'		=> __( 'Pick-up points', 'woocommerce-edostavka' ),
				'type'		=> 'delivery_points',
				'priority'	=> isset( $fields['city']['priority'] ) ? $fields['city']['priority'] + 1 : 65,
				'required'  => false
			);
		} elseif( isset( $fields['delivery_points'] ) ) {
			unset( $fields['delivery_points'] );
		}		
		
		if( $is_frontend ) {
			
			$fields['to_door_address'] = array(
				'type'	=> 'hidden',
				'class'	=> array( 'form-row-hidden', 'hidden' ),
				'label'	=> __( 'Address to the door', 'woocommerce-edostavka' ),
				'default'	=> WC()->customer->get_billing_address_1(),
				'priority'	=> 0
			);
			
			$fields['address_1']['priority'] = isset( $fields['city']['priority'] ) ? $fields['city']['priority'] + 1 : ( isset( $fields['city']['priority'] ) ? $fields['city']['priority'] : 50 );
			
			if( $edostavka_method && in_array( $edostavka_method->get_delivery_type(), array( 'stock', 'postamat' ), true ) ) {
				
				if( 'yes' == wc_edostavka_integration_get_option( 'disable_postcode_field', 'yes' ) ) {
					$fields['postcode']['required'] = false;
					$fields['postcode']['class']	= array( 'hidden' );
				}
				
				$fields['address_1']['label'] 				= __( 'Pick-up point address', 'woocommerce-edostavka' );
				$fields['address_1']['required'] 			= false;
				
				if( in_array( $delivery_points_field_type, array( 'select', 'hidden' ) ) ) {
					$fields['address_1']['class'][] = 'hidden';
				} elseif( 'text' == $delivery_points_field_type ) {
					$fields['address_1']['placeholder'] = __( 'Select Pick-up point on the map. Address will be filled automatically.', 'woocommerce-edostavka' );
					$fields['address_1']['custom_attributes'] = array( 'readonly' => 'readonly' );
				}
			}
		}
		
		return $fields;
	}
	
	public function default_checkout_billing_address_1( $value ) {
		$edostavka_method = $this->edostavka_chosen_method();
		$delivery_point = wc_edostavka_get_delivery_point_data( wc_edostavka_get_customer_state_id(), wc_edostavka_convert_method_delivery_type( $edostavka_method ) );
		
		if( $edostavka_method && in_array( $edostavka_method->get_delivery_type(), array( 'stock', 'postamat' ), true ) && ! $delivery_point ) {
			return null;
		}
		
		return $value;
	}
	
	public function form_field_delivery_points( $field, $key, $args ) {
		
		return wc_edostavka_delivery_points_field( $args );
	}
	
	public function form_field_city( $field, $key, $args ) {
		
		$options = wp_list_pluck( WC_Edostavka_Autofill_Addresses::get_all_address(), 'city_name', 'city_id' );
		
		if( is_array( $options ) && ! empty( $options ) ) {
			$field .= sprintf( '<select name="%s" id="%s" class="state_select" data-placeholder="%s"><option value="">%s</option>', esc_attr( $key ), esc_attr( $args['id'] ), esc_attr( $args['placeholder'] ), __( 'Select city', 'woocommerce-edostavka' ) );
			foreach( $options as $city_id => $city ) {
				$field .= '<option value="' . esc_attr( $city_id ) . '" ' . selected( wc_edostavka_get_customer_state_id(), $city_id, false ) . '>' . $city . '</option>';
			}
			$field .= '</select>';
		}
		
		$label = '<label for="' . esc_attr( $args['id'] ) . '" class="' . esc_attr( implode( ' ', $args['label_class'] ) ) . '">' . $args['label'] . '</label>';
		
		return sprintf('<p class="form-row %1$s" id="%2$s" style="display: none">%3$s%4$s</p>', esc_attr( implode( ' ', $args['class'] ) ), esc_attr( $args['id'] ) . '_field', $label, $field );
	}
	
	public function form_field_hidden( $field, $key, $args, $value ) {
		if ( is_null( $value ) ) {
			$value = $args['default'];
		}
		return sprintf('<input type="hidden" name="%1$s" id="%1$s" value="%2$s" />', esc_attr( $key ), $value );
	}
	
	public function update_order_review( $post_data ) {
		$post_data = wp_parse_args( $post_data );
		$state_id = isset( $post_data['billing_state_id'] ) ? $post_data['billing_state_id'] : null;
		wc_edostavka_set_customer_state_id( $state_id );
		WC()->session->set( 'to_door_address', $post_data['billing_to_door_address'] );
	}
	
	private function edostavka_chosen_method() {
		$chosen_shipping_method 	= wc_edostavka_get_chosen_shipping_method_ids();	
		$_chosen_method 			= wc_edostavka_chosen_method();
		
		foreach( $chosen_shipping_method as $method ) {
			if( isset( $_chosen_method[ $method ] ) ) {
				return $_chosen_method[ $method ];
			}
		}
		
		return false;
	}
	
	public function order_review_fragments( $fragments ) {
		
		$checkout_fields 			= WC()->checkout->get_checkout_fields( 'billing' );
		$method 					= $this->edostavka_chosen_method();
		$delivery_points_field_type	= wc_edostavka_integration_get_option( 'delivery_points_field_type', 'text' );
		$delivery_points_map_type	= wc_edostavka_integration_get_option( 'delivery_point_map_position', 'popup_widget' );
		
		$billing_address_args 		= array();
		$billing_postcode_args 		= array();
		
		if( isset( $checkout_fields['billing_address_1'] ) ) {
			$billing_address_args = $checkout_fields['billing_address_1'];
		}
		
		if( isset( $checkout_fields['billing_postcode'] ) ) {
			$billing_postcode_args = $checkout_fields['billing_postcode'];
		}
		
		$billing_address_args['class'] 		= array( 'form-row-wide' );
		$billing_address_args['return']		= true;
		$billing_address_args['default']	= WC()->session->get( 'to_door_address' );
		
		$billing_postcode_args['return']	= true;
		$billing_postcode_args['default']	= WC()->customer->get_billing_postcode();
		
		if( $delivery_points_map_type == 'under_address' ) {
			$delivery_points_args = array(
				'label'		=> __( 'Pick-up points', 'woocommerce-edostavka' ),
				'class'		=> array( 'hidden' ),
				'points'	=> array(),
				'priority'	=> isset( $checkout_fields['billing_city'], $checkout_fields['billing_city']['priority'] ) ? $checkout_fields['billing_city']['priority'] + 1 : 65
			);
		}
			
		
		if( $method && in_array( $method->get_delivery_type(), array( 'stock', 'postamat' ), true ) ) {
			
			$packages = WC()->shipping->get_packages();
			$chosen_chosen_delivery_point = wc_edostavka_get_delivery_point_data( wc_edostavka_get_customer_state_id(), wc_edostavka_convert_method_delivery_type( $method ) );
			$package = array_shift( $packages );
			$billing_address = null;
			
			if( $chosen_chosen_delivery_point ) {
				if( ! empty( $chosen_chosen_delivery_point['address'] ) ) {
					$billing_address = $chosen_chosen_delivery_point['address'];
				} elseif( ! empty( $chosen_chosen_delivery_point['id'] ) && isset( $package['delivery_points'][ $chosen_chosen_delivery_point['id'] ] ) ) {
					$billing_address = $package['delivery_points'][ $chosen_chosen_delivery_point['id'] ]['address'];
				}
			}
			
			$billing_address_args['custom_attributes'] = array( 'readonly' => 'readonly' );
			$billing_address_args['default'] = $billing_address;
			$billing_address_args['autofocus'] = false;
			$billing_address_args['label'] = __( 'Pick-up point address', 'woocommerce-edostavka' );
			$billing_address_args['placeholder'] =  __( 'Select Pick-up point on the map. Address will be filled automatically.', 'woocommerce-edostavka' );
			$billing_address_args['class'] = in_array( $delivery_points_field_type, array( 'select', 'hidden' ) ) ? array( 'hidden' ) : $billing_address_args['class'];
			
			if( $delivery_points_map_type == 'under_address' ) {
				$delivery_points_args['class'] 		= array();
				$delivery_points_args['points'] 	= $package['delivery_points'];
				$delivery_points_args['required'] 	= true;
			}
		}
		
		$billing_address_args 	= apply_filters( 'edostavka_update_order_review_address_args', $billing_address_args, $method );
		$billing_postcode_args 	= apply_filters( 'edostavka_update_order_review_postcode_args', $billing_postcode_args, $method );
		
		$fragments['#billing_address_1_field'] = woocommerce_form_field( 'billing_address_1', $billing_address_args );
		$fragments['#billing_postcode_field'] = woocommerce_form_field( 'billing_postcode', $billing_postcode_args );
		
		if( $delivery_points_map_type == 'under_address' ) {
			$delivery_points_args 	= apply_filters( 'edostavka_update_order_review_delivery_points_args', $delivery_points_args, $method );
			$fragments['#delivery_points'] = wc_edostavka_delivery_points_field( $delivery_points_args );
		}
		
		return $fragments;
	}
	
	public function shipping_packages( $packages ) {

		$new_packages = array();

		foreach( $packages as $index => $package ) {
			$new_packages[$index] = $package;
			$new_packages[$index]['destination']['state_id'] = wc_edostavka_get_customer_state_id();
			$new_packages[$index]['delivery_points'] = WC_Edostavka_Delivery_Points::get_points( wc_edostavka_get_customer_state_id(), false, array( 'type' => wc_edostavka_convert_method_delivery_type( $this->edostavka_chosen_method() ) ) );
			if( 'yes' === wc_edostavka_integration_get_option( 'add_insurance_cost', 'no' ) ) {
				$new_packages[$index]['payment_method'] = WC()->session ? WC()->session->get( 'chosen_payment_method' ) : null;
			}
		}

		return $new_packages;
	}
	
	public function created_customer( $customer_id ) {
		
		if( $customer_id > 0 && WC()->session ) {
			$customer = new WC_Customer( $customer_id );
			$customer->update_meta_data( 'billing_state_id', WC()->session->get( 'billing_state_id', null ) );
			$customer->save();
		}
	
	}
	
	public function address_validation( $user_id, $load_address, $address ) {
		$key = 'billing_state_id';
		
		if( isset( $_POST[$key] ) ) {
			$customer = new WC_Customer( $user_id );
			$state_id = wc_clean( $_POST[$key] );
			
			WC()->session->set( $key, $state_id );
			$customer->update_meta_data( $key, $state_id );
			$customer->save();
		}
	}
	
	public function validate_checkout( $data, $errors ) {
		
		$required_field_errors = $errors->get_error_data( 'required-field' );
		
		if( ! empty( $required_field_errors ) ) {
			return;
		}
		
		if( false === edostavka_only_virtual_products_in_cart() ) {
			
			$raw_address = array();
			
			foreach( $data as $key => $posted ) {
				if( false === strpos( $key, 'billing_' ) ) {
					continue;
				}
				
				$raw_address[ str_replace( 'billing_', '', $key ) ] = $posted;
			}
			
			$edostavka_method = $this->edostavka_chosen_method();
			$formatted_address = edostavka_get_formatted_address_via_dadata( WC()->countries->get_formatted_address( $raw_address, ', ' ) );
			
			if( 'yes' == wc_edostavka_integration_get_option( 'address_validate_enable' ) && $edostavka_method && 'door' == $edostavka_method->get_delivery_type() ) {
				
				if ( ! $formatted_address || ( isset( $formatted_address['street'] ) && empty( $formatted_address['street'] ) ) ) {
					$errors->add( 'shipping',  __( 'To continue with your order, please <strong>fill your address</strong> correctly.', 'woocommerce-edostavka' ) );
				}
			}
			
			if( $edostavka_method && in_array( $edostavka_method->get_delivery_type(), array( 'stock', 'postamat' ), true ) ) {
				
				$chosen_delivery_point = wc_edostavka_get_delivery_point_data( wc_edostavka_get_customer_state_id(), wc_edostavka_convert_method_delivery_type( $edostavka_method ) );
				
				if( ! $chosen_delivery_point ) {
					$errors->add( 'shipping', __( 'To continue with your order, please <strong>choose pick-up point</strong>.', 'woocommerce-edostavka' ) );
				}
			}
			
		}
	}
	
	public function create_map( $atts ) {
		shortcode_atts( array(
			'city_form' 	=> wc_edostavka_integration_get_option( 'city_origin' ),
			'city_to' 		=> wc_edostavka_integration_get_option( 'default_state_id' )
		), $atts );
		
		return wc_edostavka_create_map( $atts );
	}
	
	public function add_delivery_points_button() {
		$edostavka_method = $this->edostavka_chosen_method();
		
		if( is_checkout() && 'popup_widget' == wc_edostavka_integration_get_option( 'delivery_point_map_position', 'popup_widget' ) && $edostavka_method && in_array( $edostavka_method->get_delivery_type(), array( 'stock', 'postamat' ), true ) ) {
			$out = '';
			$button_text = __( 'Choose Pick-up point', 'woocommerce-edostavka' );
			$button_classes = array( 'wc-edostavka-choose-delivery-point' );
			
			ob_start();
			wc_get_template( 'delivery-map-styles.php', array(), '', WC_Edostavka::get_templates_path() );
			$css = ob_get_clean();
			$chosen_delivery_point = wc_edostavka_get_delivery_point_data( wc_edostavka_get_customer_state_id(), wc_edostavka_convert_method_delivery_type( $edostavka_method ) );
				
			if( $chosen_delivery_point && ! empty( $chosen_delivery_point['address'] ) ) {
				
				$out .= sprintf( '<p>%s: <strong>%s</strong></p>', __( 'Chosen pick-up point', 'woocommerce-edostavka' ), $chosen_delivery_point['address'] );
				$button_text = __( 'Choose another pickpoint', 'woocommerce-edostavka' );
				$button_classes[] = 'wc-edostavka-choose-delivery-point--chosen';
			}
				
			$out .= sprintf( '<button class="button %s" data-city_id="%s" data-delivery_type="%s"><span class="dashicons dashicons-location"></span> %s</button>', implode( ' ', $button_classes ), wc_edostavka_get_customer_state_id(), $edostavka_method->get_delivery_type(), $button_text );
			
			$out = '<style type="text/css">' . $css . '</style>' . $out;
			
			$botton_html_wrapper = apply_filters( 'woocommerce_edostavka_cart_delivery_points_template', sprintf( '<tr class="cart-delivery-points"><th>%s: </th><td>%s</td></tr>', __( 'Pick-up point of CDEK', 'woocommerce-edostavka' ), $out ), $out );
			
			echo $botton_html_wrapper;
		}
	}
	
	public function add_map_template() {
		if( is_checkout() ) {
			wc_get_template( 'html-modal-map.php', array(), '', WC_Edostavka::get_templates_path() );
		}
	}
}

new WC_Edostavka_Checkout();