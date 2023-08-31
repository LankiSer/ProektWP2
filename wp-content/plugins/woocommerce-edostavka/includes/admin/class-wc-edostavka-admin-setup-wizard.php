<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Edostavka_Admin_Setup_Wizard {
	
	private $step = '';
	
	private $steps = array();
	
	private $deferred_actions = array();
	
	private $settings = array();
	
	public function __construct() {
		if ( current_user_can( 'manage_woocommerce' ) ) {
			add_action( 'admin_menu', array( $this, 'admin_menus' ) );
			add_action( 'admin_init', array( $this, 'setup_wizard' ) );
		}
	}
	
	public function admin_menus() {
		add_dashboard_page( '', '', 'manage_options', 'edostavka-setup', '' );
	}
	
	public function setup_wizard() {
		if ( empty( $_GET['page'] ) || 'edostavka-setup' !== $_GET['page'] ) {
			return;
		}
		
		$default_steps = array(
			'plugin_setup' => array(
				'name'    => 'Параметры WC eDostavka',
				'view'    => array( $this, 'setup_setup' ),
				'handler' => array( $this, 'setup_setup_save' ),
			),
			'activate'    => array(
				'name'    => 'Активация',
				'view'    => array( $this, 'setup_activate' ),
				'handler' => array( $this, 'setup_activate_save' ),
			),
			'next_steps'  => array(
				'name'    => 'Готово!',
				'view'    => array( $this, 'setup_ready' ),
				'handler' => '',
			),
		);
		
		$this->settings = get_option( 'woocommerce_edostavka-integration_settings', array() );
		$this->steps = $default_steps;
		$this->step  = isset( $_GET['step'] ) ? sanitize_key( $_GET['step'] ) : current( array_keys( $this->steps ) );
		$suffix      = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		
		wp_register_script( 'edostavka-setup-integration', plugins_url( 'assets/js/admin/integration.js', WC_Edostavka::get_main_file() ), array( 'jquery' ), WC_Edostavka::VERSION, true );
		wp_register_script( 'jquery-blockui', WC()->plugin_url() . '/assets/js/jquery-blockui/jquery.blockUI' . $suffix . '.js', array( 'jquery' ), '2.70', true );
		wp_register_script( 'selectWoo', WC()->plugin_url() . '/assets/js/selectWoo/selectWoo.full' . $suffix . '.js', array( 'jquery' ), '1.0.0' );
		wp_register_script( 'wc-enhanced-select', WC()->plugin_url() . '/assets/js/admin/wc-enhanced-select' . $suffix . '.js', array( 'jquery', 'selectWoo' ), WC_VERSION );
		wp_localize_script( 'wc-enhanced-select', 'wc_enhanced_select_params', array(
			'i18n_no_matches'           => _x( 'No matches found', 'enhanced select', 'woocommerce' ),
			'i18n_ajax_error'           => _x( 'Loading failed', 'enhanced select', 'woocommerce' ),
			'i18n_input_too_short_1'    => _x( 'Please enter 1 or more characters', 'enhanced select', 'woocommerce' ),
			'i18n_input_too_short_n'    => _x( 'Please enter %qty% or more characters', 'enhanced select', 'woocommerce' ),
			'i18n_input_too_long_1'     => _x( 'Please delete 1 character', 'enhanced select', 'woocommerce' ),
			'i18n_input_too_long_n'     => _x( 'Please delete %qty% characters', 'enhanced select', 'woocommerce' ),
			'i18n_selection_too_long_1' => _x( 'You can only select 1 item', 'enhanced select', 'woocommerce' ),
			'i18n_selection_too_long_n' => _x( 'You can only select %qty% items', 'enhanced select', 'woocommerce' ),
			'i18n_load_more'            => _x( 'Loading more results&hellip;', 'enhanced select', 'woocommerce' ),
			'i18n_searching'            => _x( 'Searching&hellip;', 'enhanced select', 'woocommerce' ),
			'ajax_url'                  => admin_url( 'admin-ajax.php' ),
			'search_products_nonce'     => wp_create_nonce( 'search-products' ),
			'search_customers_nonce'    => wp_create_nonce( 'search-customers' ),
		) );
		wp_localize_script( 'edostavka-setup-integration', 'wc_edostavka_params', array(
			'ajax_url'		=> WC()->ajax_url(),
			'country_iso'	=> WC()->countries->get_base_country()
		) );
		wp_enqueue_style( 'woocommerce_admin_styles', WC()->plugin_url() . '/assets/css/admin.css', array(), WC_VERSION );
		wp_enqueue_style( 'wc-setup', WC()->plugin_url() . '/assets/css/wc-setup.css', array( 'dashicons', 'install' ), WC_VERSION );

		wp_register_script( 'wc-setup', WC()->plugin_url() . '/assets/js/admin/wc-setup' . $suffix . '.js', array( 'jquery', 'wc-enhanced-select', 'jquery-blockui', 'wp-util', 'edostavka-setup-integration' ), WC_VERSION );
		wp_localize_script( 'wc-setup', 'wc_setup_params', array(
			'pending_jetpack_install' => 'no',
		) );
		
		if ( ! empty( $_POST['save_step'] ) && isset( $this->steps[ $this->step ]['handler'] ) ) {
			call_user_func( $this->steps[ $this->step ]['handler'], $this );
		}
		
		ob_start();
		$this->setup_wizard_header();
		$this->setup_wizard_steps();
		$this->setup_wizard_content();
		$this->setup_wizard_footer();
		exit;
	}
	
	public function get_next_step_link( $step = '' ) {
		if ( ! $step ) {
			$step = $this->step;
		}

		$keys = array_keys( $this->steps );
		if ( end( $keys ) === $step ) {
			return admin_url();
		}

		$step_index = array_search( $step, $keys, true );
		if ( false === $step_index ) {
			return '';
		}

		return add_query_arg( 'step', $keys[ $step_index + 1 ], remove_query_arg( 'activate_error' ) );
	}

	public function setup_wizard_header() {
		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta name="viewport" content="width=device-width" />
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
			<title>Установка плагина WC eDostavka</title>
			<?php wp_print_scripts( 'wc-setup' ); ?>
			<?php do_action( 'admin_print_styles' ); ?>
			<?php do_action( 'admin_head' ); ?>
		</head>
		<body class="wc-setup wp-core-ui">
			<h1 id="wc-logo"><a href="https://woodev.ru/downloads/wc-edostavka-integration"><img src="<?php echo plugins_url( '/assets/img/plugin-logo.png' , WC_Edostavka::get_main_file() );?>" alt="Плагин интеграии СДЭК с WooCommerce" /></a></h1>
		<?php
	}

	public function setup_wizard_footer() {
		?>
			<?php if ( 'plugin_setup' === $this->step ) : ?>
				<a class="wc-return-to-dashboard" href="<?php echo esc_url( admin_url() ); ?>">Настрою позже</a>
			<?php elseif ( 'next_steps' === $this->step ) : ?>
				<a class="wc-return-to-dashboard" href="<?php echo esc_url( admin_url() ); ?>">Вернутся в админку</a>
			<?php elseif ( 'activate' === $this->step ) : ?>
				<a class="wc-return-to-dashboard" href="<?php echo esc_url( $this->get_next_step_link() ); ?>">Пропустить этот шаг</a>
			<?php endif; ?>
			</body>
		</html>
		<?php
	}

	public function setup_wizard_steps() {
		$output_steps = $this->steps;
		?>
		<ol class="wc-setup-steps">
			<?php foreach ( $output_steps as $step_key => $step ) : ?>
				<li class="
					<?php
					if ( $step_key === $this->step ) {
						echo 'active';
					} elseif ( array_search( $this->step, array_keys( $this->steps ), true ) > array_search( $step_key, array_keys( $this->steps ), true ) ) {
						echo 'done';
					}
					?>
				"><?php echo esc_html( $step['name'] ); ?></li>
			<?php endforeach; ?>
		</ol>
		<?php
	}

	public function setup_wizard_content() {
		echo '<div class="wc-setup-content">';
		if ( ! empty( $this->steps[ $this->step ]['view'] ) ) {
			call_user_func( $this->steps[ $this->step ]['view'], $this );
		}
		echo '</div>';
	}
	
	public function setup_setup() {
		$city_origin 			= isset( $this->settings['city_origin'] ) ? $this->settings['city_origin'] : '';
		$default_state_id 		= isset( $this->settings['default_state_id'] ) ? $this->settings['default_state_id'] : '';
		$autofill_validity 		= isset( $this->settings['autofill_validity'] ) ? $this->settings['autofill_validity'] : '';
		$hide_single_counrty 	= isset( $this->settings['hide_single_counrty'] ) ? $this->settings['hide_single_counrty'] : 'no';
		$remove_address_2 		= isset( $this->settings['remove_address_2'] ) ? $this->settings['remove_address_2'] : 'no';
		$only_current_country 	= isset( $this->settings['only_current_country'] ) ? $this->settings['only_current_country'] : 'yes';
		$minimum_weight 		= isset( $this->settings['minimum_weight'] ) ? $this->settings['minimum_weight'] : '0.5';
		$minimum_height 		= isset( $this->settings['minimum_height'] ) ? $this->settings['minimum_height'] : '15';
		$minimum_width 			= isset( $this->settings['minimum_width'] ) ? $this->settings['minimum_width'] : '15';
		$minimum_length 		= isset( $this->settings['minimum_length'] ) ? $this->settings['minimum_length'] : '15';
		
		$city_origin_ajax = WC_Edostavka_Autofill_Addresses::get_city_by_id( $city_origin );
		$default_state_ajax = WC_Edostavka_Autofill_Addresses::get_city_by_id( $default_state_id );
		
		include( 'views/setup/setup-setup.php' );
	}
	
	public function setup_setup_save() {
		check_admin_referer( 'wc-setup' );
		
		update_option( 'woocommerce_edostavka-integration_settings', wp_parse_args( array(
			'city_origin'			=> sanitize_text_field( $_POST['city_origin'] ),
			'default_state_id'		=> sanitize_text_field( $_POST['default_state_id'] ),
			'autofill_validity'		=> sanitize_text_field( $_POST['autofill_validity'] ),
			'hide_single_counrty'	=> sanitize_text_field( $_POST['hide_single_counrty'] ),
			'remove_address_2'		=> sanitize_text_field( $_POST['remove_address_2'] ),
			'only_current_country'	=> sanitize_text_field( $_POST['only_current_country'] ),
			'minimum_weight'		=> wc_format_decimal( $_POST['minimum_weight'] ),
			'minimum_height'		=> wc_format_decimal( $_POST['minimum_height'] ),
			'minimum_width'			=> wc_format_decimal( $_POST['minimum_width'] ),
			'minimum_length'		=> wc_format_decimal( $_POST['minimum_length'] )
		), $this->settings ) );
		
		wp_safe_redirect( esc_url_raw( $this->get_next_step_link() ) );
		exit;
	}
	
	public function setup_activate() {
		$licence_key 	= isset( $this->settings['license_key'] ) ? $this->settings['license_key'] : '';
		$api_login 		= isset( $this->settings['api_login'] ) ? $this->settings['api_login'] : '';
		$api_password 	= isset( $this->settings['api_password'] ) ? $this->settings['api_password'] : '';
		
		include( 'views/setup/activate.php' );
	}
	public function setup_activate_save() {
		check_admin_referer( 'wc-setup' );
		
		update_option( 'woocommerce_edostavka-integration_settings', wp_parse_args( array(
			'license_key'	=> sanitize_text_field( $_POST['_license_key'] ),
			'api_login'		=> sanitize_text_field( $_POST['api_login'] ),
			'api_password'	=> sanitize_text_field( $_POST['api_password'] )
		), $this->settings ) );
		
		WD_Plugin_License_Legacy::request( array(
			'license'		=> trim( $_POST['_license_key'] ),
			'item_id'		=> 216
		) );
		
		wp_safe_redirect( esc_url_raw( $this->get_next_step_link() ) );
		exit;
	}
	
	public function setup_ready() {
		$existing_zones = WC_Shipping_Zones::get_zones();
		include( 'views/setup/ready.php' );
	}
}

new WC_Edostavka_Admin_Setup_Wizard();