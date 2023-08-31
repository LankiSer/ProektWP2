<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class WC_Edostavka_Shipping_Econom_Door extends WC_Edostavka_Shipping {

	protected $code = 233;
	
	protected $delivery_type = 'door';
	
	protected $weight_limit = 50;
	
	public function __construct( $instance_id = 0 ) {
		
		$this->id           		= 'edostavka-econom-door';
		$this->method_title 		= 'Экономичная посылка склад-дверь (СДЭК)';
		
		parent::__construct( $instance_id );
		
		$this->method_description 	= 'Услуга экономичной наземной доставки товаров по России для компаний, осуществляющих дистанционную торговлю. Услуга действует по направлениям из Москвы в подразделения СДЭК, находящиеся за Уралом и в Крым.';
	}
}