<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class WC_Edostavka_Shipping_Express_Light_Door extends WC_Edostavka_Shipping {

	protected $code = 11;
	
	protected $delivery_type = 'door';
	
	protected $weight_limit = 30;
	
	public function __construct( $instance_id = 0 ) {
		$this->id           		= 'edostavka-express-light-door';
		$this->method_title 		= 'Экспресс лайт склад-дверь (СДЭК)';

		parent::__construct( $instance_id );
		
		$this->method_description 	= 'Классическая экспресс-доставка по России документов и грузов.';
	}
}