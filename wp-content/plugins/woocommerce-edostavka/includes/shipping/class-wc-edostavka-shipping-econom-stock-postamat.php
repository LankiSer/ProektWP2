<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class WC_Edostavka_Shipping_Econom_Stock_Postamat extends WC_Edostavka_Shipping {

	protected $code = 378;
	
	protected $delivery_type = 'postamat';
	
	protected $weight_limit = 50;
	
	public function __construct( $instance_id = 0 ) {
		$this->id           	= 'edostavka-econom-stock-postamat';
		$this->method_title		= 'Экономичная посылка склад-постамат (СДЭК)';

		parent::__construct( $instance_id );
		
		$this->method_description 	= 'Услуга экономичной наземной доставки товаров по России до постоматов. Услуга действует по направлениям <strong>из Москвы в постоматы ОМНИСДЭК</strong>, находящиеся за Уралом и в Крым.';
	}
}