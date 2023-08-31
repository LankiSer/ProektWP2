<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Edostavka_Shipping_Oversize_Express_Stock extends WC_Edostavka_Shipping {

    protected $code = 15;

    protected $delivery_type = 'stock';

    public function __construct( $instance_id = 0 ) {
        $this->id                   = 'edostavka-oversize-express-stock';
        $this->method_title         = 'Экспресс тяжеловесы склад-склад (СДЭК)';

        parent::__construct( $instance_id );
		
		$this->method_description 	= 'Классическая экспресс-доставка по России грузов.';
    }
}