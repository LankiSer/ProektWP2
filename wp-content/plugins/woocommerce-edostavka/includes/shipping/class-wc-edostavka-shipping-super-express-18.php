<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Edostavka_Shipping_Super_Express_18_Door_Door extends WC_Edostavka_Shipping {

    protected $code = 3;

    protected $delivery_type = 'door';

    public function __construct( $instance_id = 0 ) {
        $this->id                   = 'edostavka-super-express-18-door-door';
        $this->method_title         = 'СДЭК Супер-экспресс до 18 (СДЭК)';

        parent::__construct( $instance_id );
		
		$this->method_description 	= 'Срочная доставка документов и грузов «из рук в руки» по России к определенному часу.';
    }
}