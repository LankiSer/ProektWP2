<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class WC_Edostavka_Shipping_International_Express_Door extends WC_Edostavka_Shipping {

	protected $code = 8;
	
	protected $delivery_type = 'door';
	
	protected $weight_limit = 30;
	
	public function __construct( $instance_id = 0 ) {
		$this->id           	= 'edostavka-international-express-door';
		$this->method_title 	= 'Международный экспресс грузы дверь-дверь (СДЭК)';

		parent::__construct( $instance_id );
		
		$this->method_description 	= 'Экспресс-доставка за/из-за границы грузов и посылок до 30 кг.';
	}
}