<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


class Edostavka_Admin {
	
	public function __construct() {
		add_action( 'init', array( $this, 'includes' ) );
		add_action( 'admin_init', array( $this, 'admin_redirects' ) );
		add_action( 'admin_menu', array( $this, 'demo_settings_menu' ) );
	}
	
	public function includes() {
		if ( ! empty( $_GET['page'] ) ) {
			switch ( $_GET['page'] ) {
				case 'edostavka-setup' :
					include_once( dirname( __FILE__ ) . '/class-wc-edostavka-admin-setup-wizard.php' );
				break;
			}
		}
	}
	
	public function admin_redirects() {

		if ( get_transient( '_edostavka_activation_redirect' ) ) {
			delete_transient( '_edostavka_activation_redirect' );

			if ( ( ! empty( $_GET['page'] ) && in_array( $_GET['page'], array( 'edostavka-setup' ) ) ) || is_network_admin() || ! current_user_can( 'manage_woocommerce' ) ) {
				return;
			}

			wp_safe_redirect( admin_url( 'index.php?page=edostavka-setup' ) );
			exit;
		}
	}
	
	public function demo_settings_menu() {
		if( defined( 'YWTENV_INIT' ) && current_user_can( 'manage_demo_plugin' ) ) {
			add_submenu_page( 'woocommerce', 'Настройки плагина СДЭК', 'Настройки плагина СДЭК', 'manage_demo_plugin', 'admin.php?page=wc-settings&tab=integration&section=edostavka-integration', '' );
		}
	}
}

return new Edostavka_Admin();