<?php
/**
 * Plugin Name: CDEK WooCommerce Shipping Method
 * Plugin URI: https://woodev.ru/downloads/wc-edostavka-integration
 * Description: Плагин расчёта стоимости доставки <a href="http://www.edostavka.ru" target="_blank">СДЭК</a> для WooCommerce. Так же предоставляет возможность формирования заказа на достаку из админки, и многое другое.
 * Version: 2.1.9.14
 * Author: WooDev
 * WC tested up to: 6.0.0
 * WC requires at least: 3.6
 * Author URI: https://woodev.ru
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WC_Edostavka {
	
	const VERSION = '2.1.9.14';
	
	const WD_ITEM = '216';
	
	protected static $instance = null;
	
	protected static $method_id = 'edostavka';
	
	private function __construct() {
	
		add_action( 'init', array( $this, 'load_plugin_textdomain' ), -1 );
		
		if ( class_exists( 'WC_Integration' ) ) {
			$this->includes();
			
			if ( is_admin() ) {
				$this->admin_includes();
				add_action( 'admin_init', array( $this, 'init_updater' ) );
				add_action( 'admin_init', array( $this, 'action_deactivate_plugins' ) );
			}
			
			$send_order_status = wc_edostavka_integration_get_option( 'send_order_status' );
			
			if( wc_is_order_status( $send_order_status ) ) {
				$send_order_status = substr( $send_order_status, 3 );
				add_action( 'woocommerce_order_status_' . $send_order_status, array( $this, 'export_order_on_payment' ) );
			}

			add_filter( 'woocommerce_integrations', array( $this, 'include_integrations' ) );
			add_filter( 'woocommerce_shipping_methods', array( $this, 'include_methods' ) );
			add_filter( 'woocommerce_email_classes', array( $this, 'include_emails' ) );
			
			add_action( 'rest_api_init', array( $this, 'add_edostavka_webhook' ) );
			
		} else {
			add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
		}
		
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		
		add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );
	}
	
	public function load_plugin_textdomain() {
        load_plugin_textdomain( 'woocommerce-edostavka', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }
	
	public function add_edostavka_webhook() {
		register_rest_route( 'edostavka/v1', '/order/(?P<order_id>\d+)/(?P<action_name>[\a-z]+)', array(
			'methods' 	=> 'GET',
			'callback' 	=> array( $this, 'webhook_handler' ),
			'permission_callback'	=> __return_true(),
			'args' => array(
				'order_id'	=> array(
					'required'	=> true,
					'validate_callback'	=> function( $param ) {
						return is_numeric( $param );
					}
				),
				'action_name'	=> array(
					'default'	=> 'status',
					'validate_callback'	=> function( $param ) {
						return in_array( $param, array( 'status', 'print' ) );
					}
				)
			)
		) );
	}
	
	public function webhook_handler( WP_REST_Request $request ) {
		$params = $request->get_json_params();
		
		if( ! empty( $params ) ) {
			
			switch( $request->get_param( 'action_name' ) ) {
				case 'status' : $this->change_order_status( $request->get_param( 'order_id' ), $params );
				break;
			}
		}
		
		echo 'OK';
	}
	
	private function change_order_status( $order_id, $params ) {
		$order = wc_get_order( $order_id );
	}
	
	public function export_order_on_payment( $order_id ) {
		$code = wc_edostavka_get_tracking_code( $order_id );
		
		if( ! empty( $code ) ) {
			return;
		}
		
		if( ! class_exists( 'WC_Edostavka_Orders' ) ) {
			include_once dirname( __FILE__ ) . '/includes/admin/class-wc-edostavka-orders.php';
		}
		
		$order = new WC_Edostavka_Orders( $order_id );
		
		if( ! $order->is_edostavka() ) {
			return;
		}
		
		$response = wp_safe_remote_post( esc_url( 'https://integration.cdek.ru/new_orders.php' ), array( 'body' => array( 'xml_request' => $order->generate_delivery_request() ) ) );
		
		if( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) == 200 ) {
			try {
				$result = wc_edostavka_xml_to_array( wp_remote_retrieve_body( $response ) );
				$message = '';
				if( isset( $result['@attributes']['ErrorCode'], $result['@attributes']['Msg'] ) ) {
					$message = sprintf( 'ОШИБКА: %s', $result['@attributes']['Msg'] );
				} elseif( isset( $result['Order'] ) && is_array( $result['Order'] ) ) {
					if( isset( $result['Order'][0]['@attributes']['ErrorCode'] ) ) {
						$message = sprintf( 'ОШИБКА: %s', $result['Order'][0]['@attributes']['Msg'] );
					} elseif( isset( $result['Order']['@attributes']['ErrorCode'] ) ) {
						$message = sprintf( 'ОШИБКА: %s', $result['Order']['@attributes']['Msg'] );
					} elseif( isset( $result['Order'][0]['@attributes']['DispatchNumber'] ) ) {
						wc_edostavka_update_tracking_code( $order_id, $result['Order'][0]['@attributes']['DispatchNumber'] );
						$tracking_code = wc_edostavka_get_tracking_code( $order_id );
						$message = sprintf( 'Заказу присвоен трекинг-код: %s', $tracking_code );
					} else {
						$message = 'Не удалось получить код отслеживания.';
					}					
				}
				
				if( apply_filters( 'wc_edostavka_enable_admin_tracking_code_notice', false, $order_id ) ) {
					wc_mail( get_option( 'admin_email' ), sprintf( 'Отправка заказа #%s в СДЭК', $order_id ), $message );
				}
			
			} catch ( Exception $e ) {
				wc_edostavka_add_log( sprintf( 'Ошибка при автоматической отправки заявки %s: %s', $order_id, $e->getMessage() ), 'error', 'edostavka_orders' );
			}					
		}
	}
	
	public static function get_method_id() {
		return apply_filters( 'woocommerce_edostavka_shipping_id', self::$method_id );
	}
	
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}
	
	private function includes() {
		include_once dirname( __FILE__ ) . '/includes/wc-edostavka-functions.php';
		include_once dirname( __FILE__ ) . '/includes/class-wc-edostavka-connect.php';
		include_once dirname( __FILE__ ) . '/includes/class-wc-edostavka-tariff-calculator.php';
		include_once dirname( __FILE__ ) . '/includes/class-wc-edostavka-package.php';
		include_once dirname( __FILE__ ) . '/includes/class-wc-edostavka-autofill-addresses.php';
		include_once dirname( __FILE__ ) . '/includes/class-wc-edostavka-tracking-history.php';
		include_once dirname( __FILE__ ) . '/includes/class-wc-edostavka-checkout.php';
		include_once dirname( __FILE__ ) . '/includes/class-wc-edostavka-delivery-points.php';
		include_once dirname( __FILE__ ) . '/includes/class-plugin-license.php';
		
		include_once dirname( __FILE__ ) . '/includes/class-wc-edostavka-integration.php';
		
		if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '3.0.0', '>=' ) ) {
			
			include_once dirname( __FILE__ ) . '/includes/abstracts/abstract-wc-edostavka-shipping.php';
			
			foreach ( apply_filters( 'woocommerce_edostavka_shipping_methods_files', glob( plugin_dir_path( __FILE__ ) . '/includes/shipping/class-wc-edostavka-shipping-*.php' ) ) as $filename ) {
				include_once $filename;
			}
		}
	}
	
	private function admin_includes() {
		include_once dirname( __FILE__ ) . '/includes/admin/class-wc-edostavka-admin.php';
		include_once dirname( __FILE__ ) . '/includes/admin/class-wc-edostavka-export-file.php';
		include_once dirname( __FILE__ ) . '/includes/admin/class-wc-edostavka-admin-orders.php';
		include_once dirname( __FILE__ ) . '/includes/admin/class-wc-edostavka-orders.php';
		include_once dirname( __FILE__ ) . '/includes/libraries/class-plugin-updater.php';
	}
	
	public function init_updater() {
		if( class_exists( 'WD_Plugin_Updater_Legacy' ) ) {
			
			$settings = get_option( 'woocommerce_edostavka-integration_settings' );
			$license_key = isset( $settings['license_key'] ) ? trim( $settings['license_key'] ) : null;
			
			if( ! is_null( $license_key ) ) {
				
				$updater = new WD_Plugin_Updater_Legacy( 'https://woodev.ru', __FILE__, array(
					'version'	=> self::VERSION,
					'license'	=> $license_key,
					'item_id'	=> self::WD_ITEM,
					'author'	=> 'Максим Мартиросов',
					'url'		=> home_url(),
					'beta'		=> false
				) );
			}
			
		}
	}
	
	public function include_integrations( $integrations ) {
		return array_merge( array( 'WC_Edostavka_Integration' ), $integrations );
	}
	
	public function include_methods( $methods ) {
		
		if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '3.0.0', '>=' ) ) {
			$methods = array_merge( apply_filters( 'woocommerce_edostavka_shipping_methods_classes', array(
				'edostavka-package-door' 					=> 'WC_Edostavka_Shipping_Package_Door',
				'edostavka-package-door-door' 				=> 'WC_Edostavka_Shipping_Package_Door_Door',
				'edostavka-package-door-stock' 				=> 'WC_Edostavka_Shipping_Package_Door_Stock',
				'edostavka-package-stock' 					=> 'WC_Edostavka_Shipping_Package_Stock',
				'edostavka-econom-door' 					=> 'WC_Edostavka_Shipping_Econom_Door',
				'edostavka-econom-stock' 					=> 'WC_Edostavka_Shipping_Econom_Stock',
				'edostavka-express-light-door' 				=> 'WC_Edostavka_Shipping_Express_Light_Door',
				'edostavka-express-light-stock' 			=> 'WC_Edostavka_Shipping_Express_Light_Stock',
				'edostavka-express-light-door-stock' 		=> 'WC_Edostavka_Shipping_Express_Light_Door_Stock',
				'edostavka-express-light-door-door' 		=> 'WC_Edostavka_Shipping_Express_Light_Door_Door',
				'edostavka-international-express-door'		=> 'WC_Edostavka_Shipping_International_Express_Door',
				'edostavka-international-express-docs-door'	=> 'WC_Edostavka_Shipping_International_Express_Docs_Door',
				'edostavka-magistral-express-stock'			=> 'WC_Edostavka_Shipping_Magistral_Express_Stock',
				'edostavka-magistral-super-express-stock'	=> 'WC_Edostavka_Shipping_Magistral_Super_Express_Stock',
				'edostavka-oversize-express-stock'			=> 'WC_Edostavka_Shipping_Oversize_Express_Stock',
				'edostavka-oversize-express-door'			=> 'WC_Edostavka_Shipping_Oversize_Express_Door',
				'edostavka-oversize-express-door-stock'     => 'WC_Edostavka_Shipping_Oversize_Express_Door_Stock',
				'edostavka-oversize-express-door-door'		=> 'WC_Edostavka_Shipping_Oversize_Express_Door_Door',
				'edostavka-super-express-18-door-door'		=> 'WC_Edostavka_Shipping_Super_Express_18_Door_Door',
				'edostavka-cdek-express-door-door'			=> 'WC_Edostavka_Shipping_CDEK_Express_Door_Door',
				'edostavka-cdek-express-stock-stock'		=> 'WC_Edostavka_Shipping_CDEK_Express_Stock_Stock',
				'edostavka-cdek-express-door-stock'			=> 'WC_Edostavka_Shipping_CDEK_Express_Door_Stock',
				'edostavka-cdek-express-stock-door'			=> 'WC_Edostavka_Shipping_CDEK_Express_Stock_Door',			
				'edostavka-package-door-postamat'			=> 'WC_Edostavka_Shipping_Package_Door_Postamat',
				'edostavka-package-stock-postamat'			=> 'WC_Edostavka_Shipping_Package_Stock_Postamat',
				'edostavka-express-light-door-postamat' 	=> 'WC_Edostavka_Shipping_Express_Light_Door_Postamat',
				'edostavka-express-light-stock-postamat' 	=> 'WC_Edostavka_Shipping_Express_Light_Stock_Postamat',
				'edostavka-econom-stock-postamat' 			=> 'WC_Edostavka_Shipping_Econom_Stock_Postamat',
				'edostavka-express-door-door' 				=> 'WC_Edostavka_Shipping_Express_Door_Door',
				'edostavka-express-door-stock' 				=> 'WC_Edostavka_Shipping_Express_Door_Stock',
				'edostavka-express-stock-door' 				=> 'WC_Edostavka_Shipping_Express_Stock_Door',
				'edostavka-express-stock-stock' 			=> 'WC_Edostavka_Shipping_Express_Stock_Stock',
				'edostavka-express-door-postamat' 			=> 'WC_Edostavka_Shipping_Express_Door_Postamat',
				'edostavka-express-stock-postamat' 			=> 'WC_Edostavka_Shipping_Express_Stock_Postamat'
			) ), $methods );
		}
		
		return $methods;
	}
	
	public function include_emails( $emails ) {
		if ( ! isset( $emails['WC_CDEK_Tracking_Email'] ) ) {
			$emails['WC_CDEK_Tracking_Email'] = include( dirname( __FILE__ ) . '/includes/emails/class-wc-edostavka-tracking-email.php' );
		}

		return $emails;
	}
	
	public function woocommerce_missing_notice() {
		include_once dirname( __FILE__ ) . '/includes/admin/views/html-admin-missing-dependencies.php';
	}
	
	public static function get_main_file() {
		return __FILE__;
	}
	
	public static function get_plugin_path() {
		return plugin_dir_path( __FILE__ );
	}
	
	public static function get_templates_path() {
		return self::get_plugin_path() . 'templates/';
	}
	
	public function plugin_action_links( $links ) {
		return array_merge( array(
			'settings'	=> '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=integration&section=edostavka-integration' ) . '">Настройки</a>',
			'docs'		=> '<a href="https://woodev.ru/docs/plagin-integratsiya-sdek-s-woocommerce">Документация</a>'
		), $links );
	}
	
	public function plugin_row_meta( $links, $file ) {
		if ( $file === plugin_basename( __FILE__ ) ) {
			$row_meta = array(
				'support' => '<a href="https://woodev.ru/support" title="Поддержка">Поддержка</a>',
				'demo' => '<a href="http://cdek.woodev.ru" title="Посмотреть демо сайт">Демо</a>'
			);
			return array_merge( $links, $row_meta );
		}
		return (array) $links;
	}
	
	public function action_deactivate_plugins() {
		
	}
	
	public function activate() {
		
		if ( ! function_exists( 'deactivate_plugins' ) ) {
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}
		
		if( is_plugin_active( 'wc-edostavka/wc-edostavka.php' ) ) {
			deactivate_plugins( 'wc-edostavka/wc-edostavka.php' );
		}
		
		$install_plugin_version = get_option( self::get_method_id() . '_install_version', null  );
		
		if( is_null( $install_plugin_version ) || self::VERSION !== $install_plugin_version ) {
			set_transient( '_edostavka_activation_redirect', 1, 30 );
			
			$mailer = WC()->mailer();
			$message = $mailer->wrap_message( 'Плагин установлен', sprintf('<p>Плагин WC eDostavka %s был установлен на <a href="%s">сайте</a></p>', self::VERSION, site_url('/') ) );
			$mailer->send( 'maksim@martirosoff.ru', 'Установка плагина WC eDostavka', $message );
			update_option( self::get_method_id() . '_install_version', self::VERSION );
		}
		
		if ( ! isset( $_GET['edostavka-setup'] ) ) {
			wp_safe_redirect( admin_url( 'admin.php?page=wc-settings&tab=integration' ) );
			exit;
		}
			
	}
}

add_action( 'plugins_loaded', array( 'WC_Edostavka', 'get_instance' ) );

