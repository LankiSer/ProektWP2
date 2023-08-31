<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class WC_Edostavka_Shipping_Magistral_Super_Express_Stock extends WC_Edostavka_Shipping {

	protected $code = 63;
	
	protected $delivery_type = 'stock';
	
	public function __construct( $instance_id = 0 ) {
		$this->id           	= 'edostavka-magistral-super-express-stock';
		$this->method_title 	= 'Магистральный супер-экспресс склад-склад (СДЭК)';

		parent::__construct( $instance_id );
		
		$this->method_description 	= 'Быстрая экономичная доставка грузов к определенному часу';
	}
}