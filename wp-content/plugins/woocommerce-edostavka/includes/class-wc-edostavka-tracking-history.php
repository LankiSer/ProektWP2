<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class WC_Edostavka_Tracking_History {

	public function __construct() {
		add_action( 'woocommerce_order_details_after_order_table', array( $this, 'view' ), 1 );
	}

	protected function get_tracking_history( $order, $tracking_code ) {
		if( ! class_exists( 'WC_Edostavka_Orders' ) ) {
			include_once( WC_Edostavka::get_plugin_path() . '/includes/admin/class-wc-edostavka-orders.php' );
		}
		
		$xml = new WC_Edostavka_Orders( $order );
		$response = wp_safe_remote_post( esc_url( 'https://integration.cdek.ru/status_report_h.php' ), array( 'body' => array( 'xml_request' => $xml->generate_status_report( $tracking_code ) ) ) );
		if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) == 200 ) {
			return wc_edostavka_xml_to_array( wp_remote_retrieve_body( $response ) );
		}
	}


	public function view( $order ) {
		$objects = array();

		$tracking_code = wc_edostavka_get_tracking_code( $order );

		if ( empty( $tracking_code ) ) {
			return;
		}
		
		$history = $this->get_tracking_history( $order, $tracking_code );
		
		

		if ( ! empty( $history ) && is_array( $history ) ) {
			foreach ( ( array ) $history as $history_order ) {
				
				if( ! isset( $history_order['Status'] ) ) continue;
				
				wc_get_template( 'myaccount/tracking-history-table.php', array(
						'status' 	=> $history_order['Status'],
						'reason'   	=> $history_order['Reason'],
						'code'		=> $tracking_code
					),
					'',
					WC_Edostavka::get_templates_path()
				);
			}
		} else {
			wc_get_template( 'myaccount/tracking-codes.php', array( 'code' => $tracking_code, ), '', WC_Edostavka::get_templates_path() );
		}
	}
}

new WC_Edostavka_Tracking_History();
