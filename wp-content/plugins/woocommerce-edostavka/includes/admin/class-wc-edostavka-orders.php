<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Edostavka_Orders {
	
	public $order;
	
	protected $xml;
	
	public function __construct( $order ) {
		if( is_numeric( $order ) ) {
			$this->order = wc_get_order( $order );
		} elseif( is_a( $order, 'WC_Order' ) ) {
			$this->order = $order;
		} else {
			return false;
		}
		
		$this->init();
		
		$this->login = apply_filters( 'woocommerce_edostavka_api_login', '' );
		$this->secret = apply_filters( 'woocommerce_edostavka_api_password', '' );
		
		add_filter( 'woocommerce_edostavka_order_delivery_request', array( $this, 'add_extra_service' ) );
		add_filter( 'woocommerce_edostavka_order_number', array( $this, 'order_number' ) );
	}
	
	private function init() {
		if( ! class_exists( 'WC_API_XML_Handler' ) ) {
			$path = version_compare( WC_VERSION, '3.7', '<' ) ? WC_ABSPATH . 'includes/api/legacy/v1/' : WC_ABSPATH . 'includes/legacy/api/v1/';
			include_once( $path . 'interface-wc-api-handler.php' );
			include_once( $path . 'class-wc-api-xml-handler.php' );
		}
		
		$this->xml = new WC_API_XML_Handler();
	}
	
	public function is_edostavka() {
		return $this->order->get_meta( '_shipping_delivery_tariff' ) > 0;
	}
	
	public function add_extra_service( $atts ) {
		if( isset( $_POST['service'] ) && ! empty( $_POST['service'] ) ) {
			foreach( ( array ) $_POST['service'] as $service ) {
				$atts['Order']['AddService'][]['@attributes']['ServiceCode'] = $service;
			}
			
		}
		return $atts;
	}
	
	public function order_number( $number ) {
		$order_prefix = wc_edostavka_integration_get_option( 'order_prefix' );
		if( ! empty( $order_prefix ) ) {
			$number = sprintf( '%s%s', $order_prefix, $number );
		}
		return $number;
	}
	
	public function generate_delivery_request() {
		
		$date = date('Y-m-d');
		$secure = md5( $date . '&' . $this->secret );
		$request_number = rand();
		$order = array();
		$address = array();
		$package = array();
		
		$order_number = method_exists( $this->order, 'get_order_number' ) ? $this->order->get_order_number() : $this->order->get_id();
		$order_number = apply_filters( 'woocommerce_edostavka_order_number', $order_number, $this );
		
		$order_data = array(
			'Number' 				=> $order_number,
			'RecipientName' 		=> $this->order->get_formatted_billing_full_name(),
			'Phone'					=> $this->order->get_billing_phone(),
			'RecipientEmail' 		=> $this->order->get_billing_email(),
			'SendCityCode'			=> apply_filters( 'woocommerce_edostavka_city_origin', 0 ),
			'RecCityCode' 			=> method_exists( $this->order, 'get_meta' ) ? $this->order->get_meta( '_billing_state_id' ) : $this->order->billing_state_id,
			'TariffTypeCode' 		=> $this->order->get_meta( '_shipping_delivery_tariff' ),
			'DeliveryRecipientCost' => 'cod' == $this->order->get_payment_method() ? $this->order->get_shipping_total() : 0,
			'Comment'				=> $this->order->get_customer_note(),
			'SellerName'			=> wp_specialchars_decode( esc_attr( get_bloginfo( 'name', 'display' ) ), ENT_QUOTES )
		);
		
		
		$full_address_array = array(
			'state'		=> $this->order->get_billing_state(),
			'city'		=> $this->order->get_billing_city(),
			'address'	=> $this->order->get_billing_address_1()
		);
		
		$full_address = implode( ', ', array_map( 'trim', $full_address_array ) );
		
		$order['@attributes'] = apply_filters( 'woocommerce_edostavka_order_atts_array', $order_data, $this );
		
		$address_attr = array();
		
		if( 'yes' == $this->order->get_meta( '_shipping_delivery_door' ) ) {
			if( in_array( $this->order->get_billing_country(), array( 'RU', 'KZ', 'BY', 'UA', 'UZ', 'AR' ) ) ) {
				$dadata_response = edostavka_get_formatted_address_via_dadata( $full_address );
				if( 'yes' == wc_edostavka_integration_get_option( 'address_validate_enable' ) && $dadata_response && isset( $dadata_response['street'], $dadata_response['house'], $dadata_response['flat'] ) ) {
					$address_attr = array(
						'Street'	=> $dadata_response['street'],
						'House'		=> $dadata_response['house'],
						'Flat'		=> $dadata_response['flat']
					);
				} else {
				$address_attr = $this->get_formatted_address_part( $this->order->get_billing_address_1() );
				}
			} else {
				$address_attr = array( 'Street' => $this->order->get_billing_address_1() );
			}
		} else {
			$address_attr = array( 'PvzCode' => $this->order->get_meta( '_shipping_delivery_point' ) );
		}
		
		$address['@attributes'] = $address_attr;
		
		$dimension = $this->get_order_dimension( $this->order );
		
		$package['@attributes'] = array(
			'Number'	=> $order_number,
			'BarCode'	=> $order_number,
			'Weight'	=> max( 500, wc_get_weight( $dimension['weight'], 'g', 'kg' ) ),
			'SizeA'		=> max( 1, $dimension['length'] ),
			'SizeB'		=> max( 1, $dimension['width'] ),
			'SizeC'		=> max( 1, $dimension['height'] )
		);
		
		foreach( $this->order->get_items() as $item ) {
			$_product 	= $this->order->get_product_from_item( $item );
			
			if( ! $_product->needs_shipping() ) {
				continue;
			}
			
			$weight 	= wc_get_weight( ( $_product->get_weight() > 0 ? $_product->get_weight() : wc_edostavka_integration_get_option( 'minimum_weight' ) ), 'g' );
			$sku 		= $_product->get_sku();
			$wareKey 	= ! empty( $sku ) ? $sku : $_product->get_id();
			$package['Item'][] = array( '@attributes' => apply_filters( 'woocommerce_edostavka_order_package_item', array(
				'Cost' 		=> $this->order->get_item_total( $item ),
				'Payment' 	=> 'cod' == $this->order->get_payment_method() ? $this->order->get_item_total( $item ) : 0,
				'Amount'	=> $item->get_quantity(),
				'Weight' 	=> $weight,
				'WareKey' 	=> $wareKey,
				'Comment' 	=> $_product->get_name()
			), $item, $this ) );
		}
		
		$data['DeliveryRequest'] = apply_filters( 'woocommerce_edostavka_order_delivery_request', array(
			'@attributes' 		=> array(
				'Number' 		=> $request_number,
				'date' 			=> $date,
				'Account' 		=> $this->login,
				'Secure' 		=> $secure,
				'OrderCount' 	=> 1,
				'Currency'		=> $this->order->get_currency()
			),
			'Order'	=> array_merge( $order, array(
				'Sender'	=> array( 
					'Address' 	=> array( '@attributes' => array(
						'Street'	=> apply_filters( 'woocommerce_edostavka_sender_address_street', '' ),
						'House'		=> apply_filters( 'woocommerce_edostavka_sender_address_house', '' ),
						'Flat'		=> apply_filters( 'woocommerce_edostavka_sender_address_flat', '' )
					) )
				),
				'Address' 	=> $address,
				'Package'	=> $package
			) )
		), $this->order, $package );
		
		$sender_phone = apply_filters( 'woocommerce_edostavka_sender_phone', '' );
		$sender_phone = wc_format_phone_number( $sender_phone );
		if( ! empty( $sender_phone ) ) {
			$sender_phone = function_exists( 'wc_sanitize_phone_number' ) ? wc_sanitize_phone_number( $sender_phone ) : $sender_phone;
			$data['DeliveryRequest']['Order']['Sender']['Phone'] = $sender_phone;
		}
		
		wc_edostavka_add_log( sprintf( 'Передаваемые параметры заказа: %s', wc_print_r( $data, true ) ), 'info', 'edostavka_orders' );
		
		return $this->xml->generate_response( $data );
	}
	
	public function generate_delete_request() {
		$date = date('Y-m-d');
		$secure = md5( $date . '&' . $this->secret );
		$request_number = rand();
		
		$data['DeleteRequest'] = array( '@attributes' => array( 'Number' => $request_number, 'Date' => $date, 'Account' => $this->login, 'Secure' => $secure, 'OrderCount' => 1 ),
			'Order' => array( '@attributes' => array( 'Number' => apply_filters( 'woocommerce_edostavka_order_number', $this->order->get_id(), $this ) ) )
		);
		
		return $this->xml->generate_response( $data );
	}
	
	public function generate_status_report( $dispatch ) {
		$date = date('Y-m-d');
		$secure = md5( $date . '&' . $this->secret );
		
		$data['StatusReport'] = array(
			'@attributes' => array( 'Date' => $date, 'Account' => $this->login, 'Secure' => $secure, 'ShowHistory' => true ),
			'Order' => array( '@attributes' => array( 'DispatchNumber' => $dispatch ) )
		);
		return $this->xml->generate_response( $data );
	}
	
	public function generate_orders_print() {
		
		$date = date('Y-m-d');
		$secure = md5( $date . '&' . $this->secret );
		
		$data['OrdersPrint'] = array(
			'@attributes' => array( 'Date' => $date, 'Account' => $this->login, 'Secure' => $secure, 'OrderCount' => 1, 'CopyCount' => 2 ),
			'Order' => array( '@attributes' => array( 'DispatchNumber' => wc_edostavka_get_tracking_code( $this->order->get_id() ) ) )
		);
		
		return $this->xml->generate_response( $data );
	}
	
	private function get_formatted_address_part( $address ) {
    
		preg_match('/^[^0-9]*/', $address, $match );
		
		$address = str_replace( $match[0], "", $address );
		$street = trim( $match[0] );
		
		if ( strlen( $address == 0 ) ) {
			return array(
				'Street' 	=> $street
			);
		}
		
		$addrArray = explode( " ", $address );
		
		$housenumber = array_shift( $addrArray );
		
		if ( count( $addrArray ) == 0 ) {
			return array(
				'Street' 	=> $street,
				'House'		=> $housenumber
			);
		}
		
		$extension = implode(" ", $addrArray);
		
		return array(
			'Street' 	=> $street,
			'House'		=> $housenumber,
			'Flat'		=> $extension
		);
		
	}
	
	private function get_package_weight( $order ) {
		
		$weight = 0;
		
		foreach( $order->get_items() as $item ) {
			if ( $item['product_id'] > 0 ) {
				$_product = $order->get_product_from_item( $item );
				if ( ! $_product->is_virtual() ) {
					$product_weight = $_product->get_weight();
					$weight += wc_get_weight( ( $product_weight > 0 ? $product_weight : wc_edostavka_integration_get_option( 'minimum_weight' ) ), 'g' ) * $item['qty'];
				}
			}
		}
		
		return $weight;
	}
	
	private function get_order_dimension( $order ) {
		if( ! class_exists( 'WC_Edostavka_Package' ) ) {
			include_once dirname( WC_Edostavka::get_main_file() ) . '/includes/class-wc-edostavka-package.php';
		}
		
		$package_content	= array();
		
		foreach( $order->get_items( 'line_item' ) as $item ) {
			$product = $item->get_product();
				
			if( ! $product->needs_shipping() ) {
				continue;
			}
			
			$package_content['contents'][] = array(
				'quantity' 	=> $item->get_quantity(),
				'data'		=> $product
			);
		}
		
		$package = new WC_Edostavka_Package( $package_content );
		$package_data = $package->get_data();
		
		return array(
			'length'	=> $package_data['length'],
			'width'		=> $package_data['width'],
			'height'	=> $package_data['height'],
			'weight'	=> $package_data['weight']
		);
	}
}