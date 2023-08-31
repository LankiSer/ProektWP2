<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class WC_Edostavka_Shipping_Package_Door extends WC_Edostavka_Shipping {

	protected $code = 137;
	
	protected $delivery_type = 'door';
	
	protected $weight_limit = 30;
	
	public function __construct( $instance_id = 0 ) {
		$this->id           		= 'edostavka-package-door';
		$this->method_title 		= 'Посылка склад-дверь (СДЭК)';

		parent::__construct( $instance_id );
		
		$this->method_description 	= 'Услуга экономичной доставки товаров по России для компаний, осуществляющих дистанционную торговлю.';
	}
}