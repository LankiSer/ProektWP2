<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Edostavka_Admin_Orders {

	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'register_metabox' ), 10, 2 );
		add_filter( 'woocommerce_resend_order_emails_available', array( $this, 'resend_tracking_code_email' ) );
		add_filter( 'woocommerce_order_actions', array( $this, 'resend_tracking_code_actions' ) );
		add_action( 'woocommerce_order_action_edostavka_tracking', array( $this, 'action_edostavka_tracking' ) );
		add_action( 'wp_ajax_woocommerce_edostavka_add_tracking_code', array( $this, 'ajax_add_tracking_code' ) );
		add_action( 'wp_ajax_woocommerce_edostavka_remove_tracking_code', array( $this, 'ajax_remove_tracking_code' ) );
		add_action( 'wp_ajax_woocommerce_edostavka_get_print_order', array( $this, 'ajax_get_print_order' ) );
		add_action( 'admin_init', array( $this, 'download_export_file' ) );

		if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '3.0.0', '>=' ) ) {
			add_action( 'manage_shop_order_posts_custom_column', array( $this, 'tracking_code_orders_list' ), 100 );
		}
	}
	
	public function tracking_code_orders_list( $column ) {
		global $post, $the_order;

		if ( 'shipping_address' === $column ) {
			if ( empty( $the_order ) || $the_order->get_id() !== $post->ID ) {
				$the_order = wc_get_order( $post->ID );
			}

			$code = wc_edostavka_get_tracking_code( $the_order );
			if ( ! empty( $code ) ) {				
				printf('<div class="edostavka-tracking-code"><small class="meta">Трекинг код СДЭК: <a href="%s" target="_blank">%s</a></small></div>', add_query_arg( array( 'order_id' => $code ), esc_url( 'https://www.cdek.ru/track.html' ) ), $code );
				
			}
		}
	}
	
	public function register_metabox( $post_type, $post ) {
		if( 'shop_order' == $post_type ) {
			$order = wc_get_order( $post->ID );
			if( $order->meta_exists( '_shipping_delivery_tariff' ) ) {
				add_meta_box( 'wc_edostavka', 'СДЭК заказ на доставку', array( $this, 'metabox_content' ), $post_type, 'side', 'default' );
			}			
		}		
	}
	
	public function metabox_content( $post ) {
		$tracking_code = wc_edostavka_get_tracking_code( $post->ID );

		wp_enqueue_style( 'woocommerce-edostavka-orders-admin', plugins_url( 'assets/css/admin/orders.css', WC_Edostavka::get_main_file() ), array(), WC_Edostavka::VERSION );
		wp_enqueue_script( 'woocommerce-edostavka-orders-admin', plugins_url( 'assets/js/admin/orders.js', WC_Edostavka::get_main_file() ), array( 'jquery', 'jquery-blockui', 'wp-util' ), WC_Edostavka::VERSION, true );
		wp_localize_script(
			'woocommerce-edostavka-orders-admin',
			'wc_edostavka_orders_admin_params',
			array(
				'order_id' => $post->ID,
				'i18n'     => array(
					'removeQuestion' => esc_js( 'Вы сможете удалить информацию о посылке из системы компании СДЭК, только если она находится в статусе «Создан». Во всех остальных статусах данная операция (удаления) будет не возможна! Вы уверены что хотите отменить заказ?' ),
				),
				'nonces'   => array(
					'add'    	=> wp_create_nonce( 'woocommerce-edostavka-add-tracking-code' ),
					'remove' 	=> wp_create_nonce( 'woocommerce-edostavka-remove-tracking-code' ),
					'print' 	=> wp_create_nonce( 'woocommerce-edostavka-print-order' )
				),
			)
		);

		include_once dirname( __FILE__ ) . '/views/html-meta-box-tracking-code.php';
		
	}
	
	public function resend_tracking_code_email( $emails ) {
		return array_merge( $emails, array( 'edostavka_tracking' ) );
	}
	
	public function resend_tracking_code_actions( $emails ) {
		$emails['edostavka_tracking'] = 'Отправить клиенту код отслеживания СДЭК';
		return $emails;
	}
	
	public function action_edostavka_tracking( $order ) {
		WC()->mailer()->emails['WC_CDEK_Tracking_Email']->trigger( $order->get_id(), $order );
	}
	
	public function ajax_add_tracking_code() {
		check_ajax_referer( 'woocommerce-edostavka-add-tracking-code', 'security' );
		
		$args = filter_input_array( INPUT_POST, array(
			'order_id'      => FILTER_SANITIZE_NUMBER_INT
		) );

		$order_id = $args['order_id'];
		$order = new WC_Edostavka_Orders( $order_id );
		
		$response = wp_safe_remote_post( esc_url( 'https://integration.cdek.ru/new_orders.php' ), array( 
			'timeout'	=> 10,
			'sslverify'	=> is_ssl(),
			'body' => array( 'xml_request' => $order->generate_delivery_request() ) 
		) );
		
		/*
		* Создание вебхука для получения статуса доставки заказа.
		*/
		/*
		if( method_exists( 'WC_Integrations', 'get_integration' ) ) {
			$token = WC()->integrations->get_integration( 'edostavka-integration' )->get_access_token();
			$webhook_response = wp_safe_remote_post( 'https://api.cdek.ru/v2/webhooks', array(
				'headers'   => array(
					'Content-Type' => 'application/json',
					'Authorization'	=> sprintf( 'Bearer %s', $token )
				),
				'body'	=> wp_json_encode( array(
					'url'	=> rest_url( sprintf( '/edostavka/v1/order/%s/status', $order_id ) ),
					'type'	=> 'ORDER_STATUS'
				) )
			) );
			
			if( ! is_wp_error( $webhook_response ) && wp_remote_retrieve_response_code( $webhook_response ) == 200 ) {
				$webhook_body = json_decode( wp_remote_retrieve_body( $webhook_response ), true );
				if( isset( $webhook_body['entity'], $webhook_body['entity']['uuid'] ) ) {
					update_post_meta( $order_id, '_edostavka_webhook_uuid', $webhook_body['entity']['uuid'] );
				}
			}
		}
		*/
		if ( is_wp_error( $response ) ) {
			wp_send_json_error( $response->get_error_message() );
		} elseif ( wp_remote_retrieve_response_code( $response ) == 200 ) {
			try {
				$result = wc_edostavka_xml_to_array( wp_remote_retrieve_body( $response ) );
				if( isset( $result['@attributes']['ErrorCode'], $result['@attributes']['Msg'] ) ) {
					wp_send_json_error( $result['@attributes']['Msg'] );
				} elseif( isset( $result['Order'] ) && is_array( $result['Order'] ) ) {
					if( isset( $result['Order'][0]['@attributes']['ErrorCode'] ) ) {
						wp_send_json_error( $result['Order'][0]['@attributes']['Msg'] );
					} elseif( isset( $result['Order']['@attributes']['ErrorCode'] ) ) {
						wp_send_json_error( $result['Order']['@attributes']['Msg'] );
					} elseif( isset( $result['Order'][0]['@attributes']['DispatchNumber'] ) ) {
						
						wc_edostavka_update_tracking_code( $order_id, $result['Order'][0]['@attributes']['DispatchNumber'] );
						$tracking_code = wc_edostavka_get_tracking_code( $order_id );
						
						if( isset( $_POST['service'] ) ) {
							update_post_meta( $order_id, '_edostavka_extra_service', $_POST['service'] );
						}
						
						do_action( 'woocommerce_edostavka_tracking_code_added', $tracking_code, $order_id );
						
						wp_send_json_success( $tracking_code );
					
					} else {
						wp_send_json_success( 'Не удалось получить код отслеживания.' );
					}					
				}
			} catch ( Exception $e ) {
				wp_send_json_error( $e->getMessage() );
			}					
		} else {
			
			$error_data = array(
				'code' 		=> wp_remote_retrieve_response_code( $response ),
				'message'	=> wp_remote_retrieve_response_message( $response )
			);
			
			if( $body_raw = wp_remote_retrieve_body( $response ) ) {
				$body = wc_edostavka_xml_to_array( $body_raw );
				$reason_text = '';
				if( isset( $body['Order'], $body['Order']['@attributes'] ) ) {
					
					if( $body['Order']['@attributes']['ErrorCode'] ) {
						$reason_text .= sprintf( '<p>Код ошибки: %s.</p>', $body['Order']['@attributes']['ErrorCode'] );
					}
					
					if( $body['Order']['@attributes']['Msg'] ) {
						$reason_text .= sprintf( '<p>Текст ошибки: %s.</p>', $body['Order']['@attributes']['Msg'] );
					}
					
					if( $body['Order']['@attributes']['ErrorCode'] && $body['Order']['@attributes']['ErrorCode'] == 'ERR_AUTH' ) {
						$reason_text .= '<p>Данная ошибка может означать, что к вашим ключам от API СДЭК не подключена версия API 1.5. Вам необходимо обратиться к вашему менеджеру с просьбой подключить к вашим ключам версию API 1.5.</p>';
					}
				}
				
				if( ! empty( $reason_text ) ) {
					$error_data['reason'] = sprintf( '<p>При экспорте заказа, сервер СДЭК вернул ошибку: %s</p>', $reason_text );
				}
				
			}
			
			wp_send_json_error( $error_data );
		}
		
		die();
	}
	
	public function ajax_remove_tracking_code() {
		check_ajax_referer( 'woocommerce-edostavka-remove-tracking-code', 'security' );
		
		$args = filter_input_array( INPUT_POST, array(
			'order_id'      => FILTER_SANITIZE_NUMBER_INT
		) );
		
		$xml = new WC_Edostavka_Orders( $args['order_id'] );
		
		$response = wp_safe_remote_post( esc_url( 'https://integration.cdek.ru/delete_orders.php' ), array( 
			'timeout'	=> 10,
			'sslverify'	=> is_ssl(),
			'body' 		=> array( 'xml_request' => $xml->generate_delete_request() ) 
		) );
		
		if ( is_wp_error( $response ) ) {
			wp_send_json_error( $response->get_error_message() );
		} elseif ( wp_remote_retrieve_response_code( $response ) == 200 ) {
			try {
				$result = wc_edostavka_xml_to_array( wp_remote_retrieve_body( $response ) );
				
				if( isset( $result['DeleteRequest']['Order'], $result['DeleteRequest']['Order']['@attributes'] ) ) {
					if( isset( $result['DeleteRequest']['Order']['@attributes']['ErrorCode'] ) ) {
						wp_send_json_error( $result['DeleteRequest']['Order']['@attributes']['Msg'] );
					} else {
						wc_edostavka_update_tracking_code( $args['order_id'], '', true );
						delete_post_meta( $args['order_id'], '_edostavka_extra_service' );
						wp_send_json_success( $result['DeleteRequest']['Order']['@attributes']['Msg'] );					
					}					
				} elseif( isset( $result['Order'] ) ) {
					$errors = array();
					$message = '';
					foreach( $result['Order'] as $attributes ) {
						if( isset( $attributes['@attributes']['ErrorCode'] ) ) {
							$errors[] = $attributes['@attributes']['Msg'];
						} elseif( isset( $attributes['@attributes']['Msg'] ) ) {
							$message .= $attributes['@attributes']['Msg'];
						}
					}
					
					if( empty( $errors ) ) {
						wc_edostavka_update_tracking_code( $args['order_id'], '', true );
						delete_post_meta( $args['order_id'], '_edostavka_extra_service' );
						wp_send_json_success( $message );
					} else {
						wp_send_json_error( implode( '. ', $errors ) );
					}					
				
				} else {
					wp_send_json_error( sprintf( 'Что то пошло не так. Проверьте запрос удаления заявки. Line %s.', __LINE__ )  );
				}
			} catch ( Exception $e ) {
				wp_send_json_error( $e->getMessage() );
			}
		}

		die();
	}
	
	public function download_export_file() {
		if ( isset( $_GET['action'], $_GET['nonce'], $_GET['order_id'] ) && wp_verify_nonce( $_GET['nonce'], 'order-print' ) && 'download_order_pdf' === $_GET['action'] ) {
			$exporter = new WC_Edostavka_Order_Exporter( esc_attr( $_GET['order_id'] ) );
			$exporter->export();
		}
	}
	
	public function ajax_get_print_order() {
		check_ajax_referer( 'woocommerce-edostavka-print-order', 'security' );
		
		$args = filter_input_array( INPUT_POST, array(
			'order_id'      => FILTER_SANITIZE_NUMBER_INT,
			'step'			=> FILTER_SANITIZE_STRING
		) );
		
		
		$step     = absint( $args['step'] );
		$exporter = new WC_Edostavka_Order_Exporter( $args['order_id'] );
		$exporter->set_page( $step );
		$exporter->generate_file();
		
		if( $exporter->get_error() ) {
			wp_send_json_error( $exporter->get_error() );
		} elseif ( 100 === $exporter->get_percent_complete() ) {
			wp_send_json_success( array(
				'step'       => 'done',
				'percentage' => 100,
				'url'        => add_query_arg( array( 'nonce' => wp_create_nonce( 'order-print' ), 'action' => 'download_order_pdf', 'order_id' => $args['order_id'] ), get_edit_post_link( $args['order_id'], '' ) ),
			) );
		} else {
			wp_send_json_success( array(
				'step'       => ++$step,
				'percentage' => $exporter->get_percent_complete()
			) );
		}
	}
}

new WC_Edostavka_Admin_Orders();