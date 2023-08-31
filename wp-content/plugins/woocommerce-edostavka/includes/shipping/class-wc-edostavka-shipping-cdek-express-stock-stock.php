<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class WC_Edostavka_Shipping_CDEK_Express_Stock_Stock extends WC_Edostavka_Shipping {

	protected $code = 291;
	
	protected $delivery_type = 'stock';
	
	public function __construct( $instance_id = 0 ) {
		$this->id           		= 'edostavka-cdek-express-stock-stock';
		$this->method_title 		= 'CDEK Express склад-склад (СДЭК)';

		parent::__construct( $instance_id );
		
		$this->method_description 	= 'Сервис по доставке товаров из-за рубежа в Россию, Украину, Казахстан, Киргизию, Узбекистан с услугами по таможенному оформлению.';
	}
}