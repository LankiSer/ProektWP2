<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_CSV_Exporter', false ) ) {
	include_once( WC_ABSPATH . 'includes/export/abstract-wc-csv-exporter.php' );
}

class WC_Edostavka_Order_Exporter extends WC_CSV_Exporter {
	
	protected $file;
	
	protected $page = 1;
	
	protected $export_type = 'order-print';
	
	protected $error = '';
	
	public function __construct( $order_id = 0 ) {
		$this->order_id		= $order_id;
		$upload_dir         = wp_upload_dir();
		$this->file         = trailingslashit( $upload_dir['basedir'] ) . $this->get_filename();
	}
	
	public function prepare_data_to_export() {

		$xml = new WC_Edostavka_Orders( $this->get_order_id() );
		$response = wp_safe_remote_post( esc_url( 'https://integration.cdek.ru/orders_print.php' ), array( 'body' => array( 'xml_request' => $xml->generate_orders_print() ) ) );
		
		if( is_wp_error( $response ) ) {
			$this->error = $response->get_error_message();
		} elseif( wp_remote_retrieve_response_code( $response ) == 200 ) {
			$body = wp_remote_retrieve_body( $response );
			$xml_body = wc_edostavka_xml_to_array( $body );
			if( isset( $xml_body['Order'], $xml_body['Order']['@attributes'], $xml_body['Order']['@attributes']['ErrorCode'] ) ) {
				$this->error = $xml_body['Order']['@attributes']['Msg'];
			} else {
				return $body;
			}
		}
	}
	
	public function get_file() {
		$file = '';
		if ( @file_exists( $this->file ) ) {
			$file = @file_get_contents( $this->file );
		} else {
			@file_put_contents( $this->file, '' );
			@chmod( $this->file, 0664 );
		}
		return $file;
	}
	
	public function export() {
		$this->send_headers();
		echo $this->get_file();
		@unlink( $this->file );
		die();
	}
	
	public function generate_file() {
		if ( 1 === $this->get_page() ) {
			@unlink( $this->file );
		}
		@file_put_contents( $this->file, $this->prepare_data_to_export() );
	}
	
	public function get_filename() {
		return sanitize_file_name( 'wc-edostavka-' . $this->export_type . '-' . $this->get_order_id() . '-' . date_i18n( 'Y-m-d', current_time( 'timestamp' ) ) . '.pdf' );
	}
	
	public function get_page() {
		return $this->page;
	}
	
	public function get_order_id() {
		return $this->order_id;
	}
	
	public function set_page( $page ) {
		$this->page = absint( $page );
	}
	
	public function get_total_exported() {
		return ( $this->get_page() * $this->get_limit() ) + $this->exported_row_count;
	}
	
	public function get_percent_complete() {
		return $this->total_rows ? floor( ( $this->get_total_exported() / $this->total_rows ) * 100 ) : 100;
	}
	
	public function get_error() {
		if( ! empty( $this->error ) ) {
			return $this->error;
		}
		
		return false;
	}
	
	public function send_headers() {
		if ( function_exists( 'gc_enable' ) ) {
			gc_enable();
		}
		if ( function_exists( 'apache_setenv' ) ) {
			@apache_setenv( 'no-gzip', 1 );
		}
		@ini_set( 'zlib.output_compression', 'Off' );
		@ini_set( 'output_buffering', 'Off' );
		@ini_set( 'output_handler', '' );
		ignore_user_abort( true );
		wc_set_time_limit( 0 );
		nocache_headers();
		header( 'Content-Type: application/pdf; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $this->get_filename() );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );
	}
}