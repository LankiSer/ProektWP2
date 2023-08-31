<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class WC_Edostavka_Shipping_Oversize_Express_Door_Stock extends WC_Edostavka_Shipping {

    protected $code = 17;

    protected $delivery_type = 'stock';

    public function __construct( $instance_id = 0 ) {
        $this->id                   = 'edostavka-oversize-express-door-stock';
        $this->method_title         = 'Экспресс тяжеловесы дверь-склад (СДЭК)';

        parent::__construct( $instance_id );
		
		$this->method_description 	= 'Классическая экспресс-доставка по России грузов.';
    }
}