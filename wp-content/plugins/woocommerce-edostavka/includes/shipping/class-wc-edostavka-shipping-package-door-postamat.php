<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class WC_Edostavka_Shipping_Package_Door_Postamat extends WC_Edostavka_Shipping {

	protected $code = 366;
	
	protected $delivery_type = 'postamat';
	
	protected $weight_limit = 30;
	
	public function __construct( $instance_id = 0 ) {
		$this->id           	= 'edostavka-package-door-postamat';
		$this->method_title		= 'Посылка дверь-постамат (СДЭК)';

		parent::__construct( $instance_id );
		
		$this->method_description 	= 'Услуга экономичной доставки товаров по России до постоватов.';
	}
}