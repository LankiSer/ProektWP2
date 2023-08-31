<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class WC_Edostavka_Shipping_CDEK_Express_Door_Door extends WC_Edostavka_Shipping {

	protected $code = 293;
	
	protected $delivery_type = 'door';
	
	public function __construct( $instance_id = 0 ) {
		$this->id           		= 'edostavka-cdek-express-door-door';
		$this->method_title 		= 'CDEK Express дверь-дверь (СДЭК)';

		parent::__construct( $instance_id );
		
		$this->method_description 	= 'Сервис по доставке товаров из-за рубежа в Россию, Украину, Казахстан, Киргизию, Узбекистан с услугами по таможенному оформлению.';
	}
}