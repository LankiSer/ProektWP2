<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class WC_Edostavka_Shipping_Package_Door_Stock extends WC_Edostavka_Shipping {

	protected $code = 138;
	
	protected $delivery_type = 'stock';
	
	protected $weight_limit = 30;
	
	public function __construct( $instance_id = 0 ) {
		$this->id           	= 'edostavka-package-door-stock';
		$this->method_title 	= 'Посылка дверь-склад (СДЭК)';

		parent::__construct( $instance_id );
		
		$this->method_description 	= 'Услуга экономичной доставки товаров по России для компаний, осуществляющих дистанционную торговлю.';
	}
}