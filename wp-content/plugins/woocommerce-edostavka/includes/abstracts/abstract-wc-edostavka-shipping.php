<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class WC_Edostavka_Shipping extends WC_Shipping_Method {
	
	protected $code = 0;
	
	protected $delivery_type = '';
	
	protected $weight_limit = 0;
	
	public function __construct( $instance_id = 0 ) {
		$this->instance_id        = absint( $instance_id );
		$this->method_description = sprintf( 'Метод доставки СДЭК <strong>%s</strong>', $this->method_title );
		$this->supports           = array(
			'shipping-zones',
			'instance-settings'
		);
		
		$this->init_form_fields();
		
		//$this->enabled            = $this->get_option( 'enabled' );
		$this->title              = $this->get_option( 'title' );
		$this->shipping_class_id  = (int) $this->get_option( 'shipping_class_id', '-1' );
		$this->show_delivery_time = $this->get_option( 'show_delivery_time' );
		$this->additional_time    = $this->get_option( 'additional_time' );
		$this->fee                = $this->get_option( 'fee' );
		$this->fee_type           = $this->get_option( 'fee_type' );
		$this->min_price          = $this->get_option( 'min_price' );
		$this->max_price          = $this->get_option( 'max_price' );
		$this->static_price       = $this->get_option( 'static_price' );
		$this->free          	  = $this->get_option( 'free', 0 );
		$this->cityes_limit       = $this->get_option( 'cityes_limit' );
		$this->cityes_list        = $this->get_option( 'cityes_list' );
		
		add_filter( 'woocommerce_available_payment_gateways', array( $this, 'disable_cod_payment' ) );		
		add_action( 'woocommerce_checkout_create_order', array( $this, 'checkout_create_order' ), 10, 2 );
		
		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
	}
	
	public function get_delivery_type() {
		return $this->delivery_type;
	}
	
	public function get_code() {
		return $this->code;
	}
	
	protected function get_shipping_classes_options() {
		$shipping_classes = WC()->shipping->get_shipping_classes();
		$options          = array(
			'-1' => 'Любой класс доставки',
			'0'  => 'Без класса доставки',
		);

		if ( ! empty( $shipping_classes ) ) {
			$options += wp_list_pluck( $shipping_classes, 'name', 'term_id' );
		}

		return $options;
	}
	
	public function init_form_fields() {
		$this->instance_form_fields = array(
			/*'enabled' => array(
				'title'   => 'Вкл/Выкл',
				'type'    => 'checkbox',
				'label'   => 'Включить этот метод доставки',
				'default' => 'yes',
			),*/
			'title' => array(
				'title'       => 'Заголовок',
				'type'        => 'text',
				'description' => 'Этот заголовок будет отображатся на странице оформления заказа.',
				'desc_tip'    => true,
				'default'     => $this->method_title,
			),
			'behavior_options' => array(
				'title'   => 'Дополнительные настройки',
				'type'    => 'title',
				'default' => '',
			),
			'shipping_class_id' => array(
				'title'       => 'Класс доставки',
				'type'        => 'select',
				'description' => 'При необходимости выберете класс доставки который будет применен к этому методу доставки.',
				'desc_tip'    => true,
				'default'     => '-1',
				'class'       => 'wc-enhanced-select',
				'options'     => $this->get_shipping_classes_options(),
			),
			'show_delivery_time' => array(
				'title'       => 'Срок доставки',
				'type'        => 'checkbox',
				'label'       => 'Показывать срок доставки',
				'description' => 'Отобразить предполагаемое время доставки.',
				'desc_tip'    => true,
				'default'     => 'no',
			),
			'additional_time' => array(
				'title'       => 'Добавочное время',
				'type'        => 'text',
				'description' => 'Дополнительные дни к сроку доставки.',
				'desc_tip'    => true,
				'default'     => '0',
				'placeholder' => '0',
			),
			'fee' => array(
				'title'       => 'Наценка на доставку',
				'type'        => 'text',
				'description' => 'Введите наценку котороя будет прибавляться к стоимости доставки. Например 250 или 5%. Оставьте пустым что бы не использовать эту опцию.',
				'desc_tip'    => true,
				'placeholder' => '0.00',
				'default'     => '',
			),
			'fee_type'	=> array(
				'title'		=> 'Тип наценки',
				'type'		=> 'select',
				'desc_tip'	=> 'Выберите как применять наценку',
				'default'	=> 'order',
				'options'	=> array(
					'order'		=> 'Прибавлять к стоимости заказа',
					'shipping'	=> 'Прибавлять к стоимости доставки'
				)
			),
			'min_price'	=> array(
				'title'			=> 'Минимальная сумма',
				'type'			=> 'price',
				'description'	=> 'Установите минимальную сумму заказа после которого будет отображатся этот метод. Оставьте пустым, что бы не использовать эту опцию.',
				'desc_tip'    	=> true,
				'placeholder' 	=> wc_format_localized_price( 0 )
			),
			'max_price'	=> array(
				'title'			=> 'Максимальная сумма',
				'type'			=> 'price',
				'description'	=> 'Установите максимальную сумму заказа до которой будет отображатся этот метод. Оставьте пустым, что бы не использовать эту опцию.',
				'desc_tip'    	=> true,
				'placeholder' 	=> wc_format_localized_price( 0 )
			),
			'static_price'	=> array(
				'title'			=> 'Фиксированная стоимость',
				'type'			=> 'price',
				'desc_tip'    	=> 'Укажите фиксированную стоимость для этого метода. Реальная стоимость СДЭК будет проигнорирована.',
				'placeholder' 	=> wc_format_localized_price( 0 )
			),
			'free'	=> array(
				'title'			=> 'Бесплатная доставка',
				'type'			=> 'price',
				'description'	=> 'Укажите сумму заказа при достижении которой данный метод доставки будет бесплатным. Оставьте пустым, что бы не использовать эту опцию.',
				'desc_tip'    	=> true,
				'placeholder' 	=> wc_format_localized_price( 0 )
			),
			'cityes_limit'	=> array(
				'title'			=> 'Доставка в города',
				'type'			=> 'select',
				'description'	=> 'Включить или выключить данный метод доставки в определённые города.',
				'desc_tip'		=> true,
				'default'		=> '-1',
				'class'       	=> 'wc-enhanced-select',
				'options'		=> array(
					'-1'  	=> 'Не использовать',
					'on'	=> 'Включить для указанных городов',
					'off'	=> 'Выключить для указанных городов'
				)
			),
			'cityes_list'	=> array(
				'title'			=> 'Города',
				'type'			=> 'cityes_list',
				'description'	=> 'Список городов',
				'class'       	=> 'edostavka-ajax-load'
			)
		);
	}
	
	public function admin_options() {
		
		wp_enqueue_script( $this->id . '-integration', plugins_url( 'assets/js/admin/integration.js', WC_Edostavka::get_main_file() ), array( 'selectWoo' ), WC_Edostavka::VERSION, true );
		wp_localize_script( $this->id . '-integration', 'wc_edostavka_params', array(
			'ajax_url'		=> WC()->ajax_url(),
			'country_iso'	=> WC()->countries->get_base_country()
		) );
		
		parent::admin_options();
	}
	
	public function generate_cityes_list_html( $key, $data ) {
		
		$data['options'] = array();
		$values = (array) $this->get_option( $key, array() );
		
		foreach( $values as $city_id ) {
			$city = WC_Edostavka_Autofill_Addresses::get_city_by_id( $city_id );
			$data['options'] += array(
				$city['city_id'] => sprintf( '%s (%s)', $city['city_name'], $city['state'] )
			);
		}
		
		return $this->generate_multiselect_html( $key, $data );
	}
	
	public function validate_cityes_list_field( $key, $value ) {
		return $this->validate_multiselect_field( $key, $value );
	}
	
	protected function is_door() {
		return 'door' === $this->get_delivery_type();
	}
	
	protected function get_rate( $package ) {
		//$shipping = new WC_Edostavka_Tariff_Calculator( $package, array( 'code' => $this->get_code(), 'title' => $this->title ) ); API 2.0
		$shipping = new WC_Edostavka_Connect( $package, array( 'code' => $this->get_code(), 'title' => $this->title ) );
		return $shipping->get_rate();
	}
	
	protected function get_additional_time( $package = array() ) {
		return apply_filters( 'woocommerce_edostavka_shipping_additional_time', $this->additional_time, $package );
	}
	
	protected function get_shipping_method_label( $days, $package ) {
		if ( 'yes' === $this->show_delivery_time ) {
			return wc_edostavka_get_estimating_delivery( $this->title, $days, $this->get_additional_time( $package ) );
		}

		return $this->title;
	}
	
	protected function cart_contents_total() {
		
		if( ! isset( WC()->cart->cart_contents_total ) ) {
			return false;
		}
		
		if ( WC()->cart->prices_include_tax ) {
			return WC()->cart->cart_contents_total + array_sum( WC()->cart->taxes );
		} else {
			return WC()->cart->cart_contents_total;
		}
	}
	
	protected function get_cityes_limit() {
		return $this->cityes_limit;
	}
	
	public function is_available( $package ) {
		$is_available = true;
		
		$settings = get_option( 'woocommerce_edostavka-integration_settings' );
		$license_key = isset( $settings['license_key'] ) ? trim( $settings['license_key'] ) : null;
		
		if( is_null( $license_key ) ) {
			return false;
		}
		
		$contents_weight = $this->get_contents_weight( $package );
		
		if( $this->weight_limit > 0 && $contents_weight > $this->weight_limit ) {
			wc_edostavka_add_log( sprintf( 'Метод не доступен: вес заказа %s привышает лимит %s установленный по данному тарифу.', $this->weight_limit, $contents_weight ) );
			return false;
		}
		
		if( ! isset( $package['destination']['state_id'] ) || empty( $package['destination']['state_id'] ) ) {
			return false;
		}
		
		if( 'yes' == wc_edostavka_integration_get_option( 'only_current_country', 'yes' ) ) {
			$city_info = WC_Edostavka_Autofill_Addresses::get_city_by_id( $package['destination']['state_id'] );
			if( $package['destination']['country'] !== $city_info['country'] ) {
				return false;
			}
		}
		
		/*if ( 'no' == $this->enabled ) {
			return false;
		} else*/if( '-1' !== $this->get_cityes_limit() && ! empty( $this->cityes_list ) && ! empty( $package['destination']['state_id'] ) ) {
			$allow_city = in_array( $package['destination']['state_id'], $this->cityes_list );
			switch( $this->get_cityes_limit() ) {
				case 'on' :
					$is_available = $allow_city === true;
					break;
				case 'off' : 
					$is_available = $allow_city === false;
					break;
			}
		}
		
		if( $this->max_price > 0 && $this->cart_contents_total() > $this->max_price ) {
			$is_available = false;
		}
		
		if ( $this->min_price > 0 && $this->cart_contents_total() ) {
			if ( $this->cart_contents_total() < $this->min_price ) {
				$is_available = false;
			}
		}
		
		return apply_filters( 'woocommerce_shipping_' . $this->id . '_is_available', $is_available, $package );
	}
	
	protected function has_only_selected_shipping_class( $package ) {
		$only_selected = true;

		if ( -1 === $this->shipping_class_id ) {
			return $only_selected;
		}

		foreach ( $package['contents'] as $item_id => $values ) {
			$product = $values['data'];
			$qty     = $values['quantity'];

			if ( $qty > 0 && $product->needs_shipping() ) {
				if ( $this->shipping_class_id !== $product->get_shipping_class_id() ) {
					$only_selected = false;
					break;
				}
			}
		}

		return $only_selected;
	}
	
	public function calculate_shipping( $package = array() ) {

		if( ! isset( $package['destination']['state_id'] ) || empty( $package['destination']['state_id'] ) ) {
			return;
		}
	
		if ( ! $this->has_only_selected_shipping_class( $package ) ) {
			return;
		}

		$shipping = $this->get_rate( $package );

		if ( empty( $shipping ) ) {
			return;
		}
		
		$price = ! empty( $this->static_price ) ? $this->static_price : ( 'RUB' !== $shipping['currency'] ? $shipping['priceByCurrency'] : $shipping['price'] );
		$fee = 0;
		
		if( $this->free == 0 || intval( $this->free ) > $this->cart_contents_total() ) {
			
			if( $this->fee_type == 'order' ) {
				$fee = $this->get_fee( $this->fee, $this->cart_contents_total() );
			} elseif( 'shipping' == $this->fee_type ) {
				$fee = $this->get_fee( $this->fee, $price );
			}
		}
			
		$cost  = ( $this->free > 0 && $this->cart_contents_total() > $this->free  ) ? wc_format_localized_price( 0 ) : array_sum( array( $price, $fee ) );

		$rate = apply_filters( 'woocommerce_edostavka_' . $this->id . '_rate', array(
			'id'    	=> $this->get_rate_id( $this->get_code() ),
			'label' 	=> $this->get_shipping_method_label( $shipping['deliveryPeriodMax'], $package ),
			'cost'  	=> apply_filters( 'woocommerce_edostavka_' . $this->id . '_cost', $cost, $price, $fee, $package ),
			'package'   => $package
		), $this->instance_id, $package );

		$rate = apply_filters( 'woocommerce_edostavka_shipping_methods', $rate, $package );

		$this->add_rate( $rate );
	}
	
	public function is_edostavka() {
		return in_array( $this->id, wc_edostavka_get_chosen_shipping_method_ids() );
	}
	
	public function disable_cod_payment( $gateways ) {
		
		if( is_checkout() ) {
			$allowed_cod = true;
			$packages = WC()->shipping->get_packages();
			$package = array_shift( $packages );
			$chosen_chosen_delivery_point = wc_edostavka_get_delivery_point_data( $package['destination']['state_id'], wc_edostavka_convert_method_delivery_type( $this ) );

			if( ! empty( $chosen_chosen_delivery_point ) && isset( $chosen_chosen_delivery_point['id'], $package['delivery_points'][ $chosen_chosen_delivery_point['id'] ] ) ) {
				$allowed_cod = $package['delivery_points'][ $chosen_chosen_delivery_point['id'] ]['allowedCod'] == 'true';
			}
			
			if( $this->is_edostavka() && ! $this->is_door() && ! $allowed_cod ) {
				unset( $gateways['cod'] );
			}
		}
		
		return $gateways;
	}
	
	public function checkout_create_order( $order, $data ) {
		
		if( ! is_a( $order, 'WC_Order' ) ) return;
		
		if( $this->is_edostavka() ) {
			
			$order->delete_meta_data( '_shipping_delivery_door' );
			$order->delete_meta_data( '_shipping_delivery_point' );
			$order->delete_meta_data( '_shipping_delivery_tariff' );
			
			if( $this->is_door() ) {
				$order->update_meta_data( '_shipping_delivery_door', 'yes', true );
			} else {
				
				$state_id = $order->meta_exists( '_billing_state_id' ) ? $order->get_meta( '_billing_state_id' ) : ( isset( $data['billing_state_id'] ) ? $data['billing_state_id'] : false );
				
				if( ! $state_id ) {
					throw new Exception( 'Невозможно создать заказ, так как город получатель не определён.' );
				}
				
				$delivery_point = wc_edostavka_get_delivery_point_data( $state_id, wc_edostavka_convert_method_delivery_type( $this ) );
				if( $delivery_point && isset( $delivery_point['id'] ) ) {
					$order->update_meta_data( '_shipping_delivery_point', $delivery_point['id'], true );
				} else {
					throw new Exception( 'Неудалось создать заказ, так как ПВЗ не определён. Необходимо выбрать ПВЗ или выбрать другой метод доставки.' );
				}				
			}
			
			$order->update_meta_data( '_shipping_delivery_tariff', $this->get_code(), true );
		}
	}
	
	public function get_contents_weight( $package ) {
		
		if( ! class_exists( 'WC_Edostavka_Package' ) ) {
			include_once dirname( WC_Edostavka::get_main_file() ) . '/includes/class-wc-edostavka-package.php';
		}
		
		$calculate_package = new WC_Edostavka_Package( $package );
		$package_data = $calculate_package->get_data();
		
		return isset( $package_data['weight'] ) ? $package_data['weight'] : 0;
	}
}