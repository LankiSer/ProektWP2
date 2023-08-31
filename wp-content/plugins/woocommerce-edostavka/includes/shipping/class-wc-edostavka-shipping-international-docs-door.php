<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class WC_Edostavka_Shipping_International_Express_Docs_Door extends WC_Edostavka_Shipping {

	protected $code = 7;
	
	protected $delivery_type = 'door';
	
	protected $weight_limit = 5;
	
	public function __construct( $instance_id = 0 ) {
		$this->id           	= 'edostavka-international-express-docs-door';
		$this->method_title 	= 'Международный экспресс документы дверь-дверь (СДЭК)';

		parent::__construct( $instance_id );
		
		$this->method_description 	= 'Экспресс-доставка за/из-за границы документов и писем.';
	}
}