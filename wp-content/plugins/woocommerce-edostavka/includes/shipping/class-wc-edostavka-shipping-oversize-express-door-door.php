<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Edostavka_Shipping_Oversize_Express_Door_Door extends WC_Edostavka_Shipping {

    protected $code = 18;

    protected $delivery_type = 'door';

    public function __construct( $instance_id = 0 ) {
        $this->id                   = 'edostavka-oversize-express-door-door';
        $this->method_title         = 'Экспресс тяжеловесы дверь-дверь (СДЭК)';

        parent::__construct( $instance_id );
		
		$this->method_description 	= 'Классическая экспресс-доставка по России грузов.';
    }
}