<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class WC_Edostavka_Shipping_Express_Light_Door_Postamat extends WC_Edostavka_Shipping {

	protected $code = 361;
	
	protected $delivery_type = 'postamat';
	
	protected $weight_limit = 30;
	
	public function __construct( $instance_id = 0 ) {
		$this->id           	= 'edostavka-express-light-door-postamat';
		$this->method_title		= 'Экспресс лайт дверь-постамат (СДЭК)';

		parent::__construct( $instance_id );
		
		$this->method_description 	= 'Услуга доставки товаров по России до постоватов.';
	}
}