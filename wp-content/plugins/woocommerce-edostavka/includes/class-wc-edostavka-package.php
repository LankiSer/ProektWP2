<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class WC_Edostavka_Package {

	protected $package = array();

	public function __construct( $package = array() ) {
		$this->package = $package;
	}

	protected function get_package_data() {
		$count  = 0;
		$height = array();
		$width  = array();
		$length = array();
		$weight = array();

		foreach ( $this->package['contents'] as $item_id => $values ) {
			$product = $values['data'];
			$qty     = $values['quantity'];

			if ( $qty > 0 && $product->needs_shipping() ) {
			
				$_height = apply_filters( 'woocommerce_edostavka_package_height', wc_get_dimension( (float) $product->get_height(), 'cm' ) );
				$_width  = apply_filters( 'woocommerce_edostavka_package_width', wc_get_dimension( (float) $product->get_width(), 'cm' ) );
				$_length = apply_filters( 'woocommerce_edostavka_package_length', wc_get_dimension( (float) $product->get_length(), 'cm' ) );
				$_weight = apply_filters( 'woocommerce_edostavka_package_weight', wc_get_weight( (float) $product->get_weight(), 'kg' ) );

				$height[ $count ] = $_height;
				$width[ $count ]  = $_width;
				$length[ $count ] = $_length;
				$weight[ $count ] = $_weight;

				if ( $qty > 1 ) {
					$n = $count;
					for ( $i = 0; $i < $qty; $i++ ) {
						$height[ $n ] = $_height;
						$width[ $n ]  = $_width;
						$length[ $n ] = $_length;
						$weight[ $n ] = $_weight;
						$n++;
					}
					$count = $n;
				}

				$count++;
			}
		}

		return array(
			'height' => array_values( $height ),
			'length' => array_values( $length ),
			'width'  => array_values( $width ),
			'weight' => array_sum( $weight ),
		);
	}

	protected function cubage_total( $height, $width, $length ) {
		$total       = 0;
		$total_items = count( $height );

		for ( $i = 0; $i < $total_items; $i++ ) {
			$total += $height[ $i ] * $width[ $i ] * $length[ $i ];
		}

		return $total;
	}

	protected function get_max_values( $height, $width, $length ) {
		return array(
			'height' => max( $height ),
			'width'  => max( $width ),
			'length' => max( $length ),
		);
	}


	protected function calculate_root( $height, $width, $length, $max_values ) {
		$cubage_total = $this->cubage_total( $height, $width, $length );
		$root         = 0;
		$biggest      = max( $max_values );

		if ( 0 !== $cubage_total && 0 < $biggest ) {
			$division = $cubage_total / $biggest;
			$root = round( sqrt( $division ), 1 );
		}

		return $root;
	}
	
	protected function calculate_cubic( $height, $width, $length ) {
		$cubage_total = $this->cubage_total( $height, $width, $length );
		return round( pow( $cubage_total, 1/3 ), 1 );
	}

	protected function get_cubage( $height, $width, $length ) {
        $cubage     = array();
        $max_values = $this->get_max_values( $height, $width, $length );
		$cubic      = $this->calculate_cubic( $height, $width, $length );
		
		if( $cubic > max( $max_values ) ) {
			$cubage = array(
                'height' => $cubic,
                'width'  => $cubic,
                'length' => $cubic,
            );
		} else {
			$root       = $this->calculate_root( $height, $width, $length, $max_values );
			$greatest   = array_search( max( $max_values ), $max_values, true );
			
			switch ( $greatest ) {
				case 'height' :
					$cubage = array(
						'height' => count( $length ) > 1 ? $root : max( $length ),
						'width'  => count( $width ) > 1 ? $root : max( $width ),
						'length' => max( $height ),
					);
					break;
				case 'width' :
					$cubage = array(
						'height' => count( $height ) > 1 ? $root : max( $height ),
						'width'  => count( $length ) > 1 ? $root : max( $length ),
						'length' => max( $width ),
					);
					break;
				case 'length' :
					$cubage = array(
						'height' => count( $height ) > 1 ? $root : max( $height ),
						'width'  => count( $width ) > 1 ? $root : max( $width ),
						'length' => max( $length ),
					);
					break;
				default :
					$cubage = array(
						'height' => 0,
						'width'  => 0,
						'length' => 0,
					);
					break;
			}
		}
		
		return $cubage;
    }

	public function get_data() {
		$data = $this->get_package_data();

		if ( ! empty( $data['height'] ) && ! empty( $data['width'] ) && ! empty( $data['length'] ) ) {
			$cubage = $this->get_cubage( $data['height'], $data['width'], $data['length'] );
		} else {
			$cubage = array(
				'height' => 0,
				'width'  => 0,
				'length' => 0,
			);
		}

		return array(
			'height' => $cubage['height'],
			'width'  => $cubage['width'],
			'length' => $cubage['length'],
			'weight' => $data['weight']
		);
	}
}