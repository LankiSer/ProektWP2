<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class WC_Edostavka_Shipping_Express_Door_Door extends WC_Edostavka_Shipping {

	protected $code = 480;
	
	protected $delivery_type = 'door';
	
	public function __construct( $instance_id = 0 ) {
		$this->id           		= 'edostavka-express-door-door';
		$this->method_title 		= 'Экспресс дверь-дверь (СДЭК)';

		parent::__construct( $instance_id );
		
		$this->method_description 	= 'Классическая экспресс-доставка документов и грузов по стандартным срокам доставки внутри страны (Россия, Белоруссия, Армения, Киргизия, Казахстан). Также действует по направлениям между странами таможенного союза (Россия, Белоруссия, Армения, Киргизия, Казахстан). Без ограничений по весу';
	}
}