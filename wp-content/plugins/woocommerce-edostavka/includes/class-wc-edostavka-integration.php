<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Edostavka_Integration extends WC_Integration {
	
	public function __construct() {
		$this->id           = 'edostavka-integration';
		$this->method_title = sprintf( 'СДЭК (eDostavka) v%s', WC_Edostavka::VERSION );
		
		$this->init_form_fields();
		
		$this->init_settings();
		
		$this->license_key 			= $this->get_option( 'license_key' );
		$this->api_login 			= $this->get_option( 'api_login' );
		$this->api_password 		= $this->get_option( 'api_password' );
		$this->city_origin 			= $this->get_option( 'city_origin' );
		$this->default_state_id 	= $this->get_option( 'default_state_id' );
		$this->sender_street 		= $this->get_option( 'sender_street' );
		$this->sender_house 		= $this->get_option( 'sender_house' );
		$this->sender_flat 			= $this->get_option( 'sender_flat' );
		$this->sender_phone 		= $this->get_option( 'sender_phone' );
		$this->minimum_weight 		= $this->get_option( 'minimum_weight' );
		$this->minimum_height 		= $this->get_option( 'minimum_height' );
		$this->minimum_width 		= $this->get_option( 'minimum_width' );
		$this->minimum_length 		= $this->get_option( 'minimum_length' );
		$this->order_prefix 		= $this->get_option( 'order_prefix' );
		$this->send_order_status 	= $this->get_option( 'send_order_status' );
		$this->only_current_country = $this->get_option( 'only_current_country' );
		$this->enable_debug 		= $this->get_option( 'debug' );
		$this->format_city 			= $this->get_option( 'format_city' );
		$this->add_insurance_cost 	= $this->get_option( 'add_insurance_cost', 'no' );
		
		add_action( 'woocommerce_update_options_integration_' . $this->id, array( $this, 'process_admin_options' ) );
		
		add_filter( 'woocommerce_edostavka_api_login', array( $this, 'set_api_login' ) );
		add_filter( 'woocommerce_edostavka_api_password', array( $this, 'set_api_password' ) );
		add_filter( 'woocommerce_edostavka_city_origin', array( $this, 'set_city_origin' ) );
		add_filter( 'woocommerce_edostavka_default_state_id', array( $this, 'default_state_id' ) );
		
		/*
		* Sender data
		*/
		add_filter( 'woocommerce_edostavka_sender_address_street', array( $this, 'sender_address_street' ) );
		add_filter( 'woocommerce_edostavka_sender_address_house', array( $this, 'sender_address_house' ) );
		add_filter( 'woocommerce_edostavka_sender_address_flat', array( $this, 'sender_address_flat' ) );
		add_filter( 'woocommerce_edostavka_sender_phone', array( $this, 'get_sender_phone' ) );
		
		add_filter( 'woocommerce_edostavka_package_weight', array( $this, 'set_package_weight' ) );
		add_filter( 'woocommerce_edostavka_package_height', array( $this, 'set_package_height' ) );
		add_filter( 'woocommerce_edostavka_package_width', array( $this, 'set_package_width' ) );
		add_filter( 'woocommerce_edostavka_package_length', array( $this, 'set_package_length' ) );
		add_filter( 'woocommerce_edostavka_enable_debug', array( $this, 'set_enable_debug' ) );
		
		add_filter( 'woocommerce_edostavka_autofill_addresses_validity', array( $this, 'setup_autofill_address_validity' ) );
		
		/*
		* API V2.0
		*/
		$this->oauth_uri	= 'http://api.cdek.ru/v2/oauth/token';
		$this->orders_uri	= 'https://api.cdek.ru/v2/orders';
		$this->redirect_uri	= WC()->api_request_url( 'woocommerce_edostavka_integration' );
		
		add_action( 'woocommerce_api_woocommerce_edostavka_integration' , array( $this, 'oauth_redirect' ) );
		
		if ( is_admin() ) {
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		}
		
		if ( isset( $_POST['woocommerce_edostavka_integration_redirect'] ) && $_POST['woocommerce_edostavka_integration_redirect'] && empty( $_POST['save'] ) ) {
			add_action( 'woocommerce_update_options_integration_' . $this->id, array( $this, 'process_edostavka_integration_redirect' ) );
		}
	}
	
	public function process_edostavka_integration_redirect() {
		
		$oauth_url = add_query_arg(
			array(
				'redirect_uri'    => $this->redirect_uri,
				'response_type'   => 'code',
				'client_id'       => $this->get_option( 'api_login' ),
				'approval_prompt' => 'force',
				'access_type'     => 'offline',
			),
			$this->oauth_uri
		);

		wp_redirect( $oauth_url );
		exit;
	}
	
	public function oauth_redirect() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Permission denied!', 'woocommerce-edostavka' ) );
		}

		$redirect_args = array(
			'page'    => 'wc-settings',
			'tab'     => 'integration',
			'section' => $this->id,
		);
		
		$access_token = $this->get_access_token();

		if ( '' != $access_token ) {
			$redirect_args['woocommerce_edostavka_oauth'] = 'success';

			wp_redirect( add_query_arg( $redirect_args, admin_url( 'admin.php' ) ), 301 );
			exit;
		} else {

			$redirect_args['woocommerce_edostavka_oauth'] = 'fail';

			wp_redirect( add_query_arg( $redirect_args, admin_url( 'admin.php' ) ), 301 );
			exit;
		}

		wp_die( __( 'Invalid request!', 'woocommerce-edostavka' ) );
	}
	
	public function admin_notices() {
		$screen = get_current_screen();

		if ( 'woocommerce_page_wc-settings' == $screen->id && isset( $_GET['woocommerce_edostavka_oauth'] ) ) {
			if ( 'success' == $_GET['woocommerce_edostavka_oauth'] ) {
				echo '<div class="updated fade"><p><strong>Интеграция СДЭК</strong> - Ваш аккаунт успешно подключён.</p></div>';
			} else {
				echo '<div class="error fade"><p><strong>Интеграция СДЭК</strong> - Во время подключеня к аккаунту СДЭК произошла ошибка. Возможно вы указали неверный секретынй логин или пароль. Для более детальной информации включите режим логирования и посмотрите логи.</p></div>';
			}
		}
	}
	
	public function get_access_token() {

		$access_token = get_transient( 'woocommerce_edostavka_access_token' );

		if ( false !== $access_token ) {
			return $access_token;
		}

		$params = array(
			'body'      => http_build_query( array(
				'client_id'     => $this->api_login,
				'client_secret' => $this->api_password,
				'grant_type'    => 'client_credentials',
			) ),
			'sslverify' => false,
			'timeout'   => 60,
			'headers'   => array(
				'Content-Type' => 'application/x-www-form-urlencoded',
			),
		);

		$response = wp_remote_post( $this->oauth_uri, $params );

		if ( ! is_wp_error( $response ) && 200 == wp_remote_retrieve_response_code( $response ) ) {
			$response_data = json_decode( wp_remote_retrieve_body( $response ), true );
			
			if( isset( $response_data['access_token'] ) && ! empty( $response_data['access_token'] ) ) {
				
				set_transient( 'woocommerce_edostavka_access_token', $response_data['access_token'], $response_data['expires_in'] );
				return $response_data['access_token'];
			}
			
		} else {
			return '';
		}
	}
	
	public function init_form_fields() {
		
		$this->form_fields = array(
			'license_key'  => array(
				'title'		  => 'Лицензионный ключ продукта',
				'description' => 'Чтобы получать обновления, введите действующий лицензионный ключ Лицензионного программного обеспечения.',
				'desc_tip'    => true,
				'custom_attributes'	  => array( 'required' => 'required' )
			),
			'api_login' => array(
				'title' 		=> 'API логин',
				'type' 			=> 'text',
				'description'	=> 'Логин, выдается компанией СДЭК по вашему запросу. Обязательны для учета индивидуальных тарифов и учета условий доставок по тарифам «посылка». Запрос необходимо отправить на адрес integrator@cdek.ru с указанием номера договора со СДЭК. Важно: Учетная запись для интеграции не совпадает с учетной записью доступа в Личный Кабинет СДЭК.',
				'default'		=> '',
				'desc_tip'      => true,
				'custom_attributes'	  => array( 'required' => 'required' )
			),
			'api_password' => array(
				'title' 		=> 'API секретный ключ',
				'type' 			=> 'text',
				'description'	=> 'Пароль, выдаётся компанией СДЭК по вашему запросу',
				'default'		=> '',
				'desc_tip'      => true,
				'custom_attributes'	  => array( 'required' => 'required' )
			),
			'city_origin' => array(
				'title' 		=> 'Город отправитель',
				'description'	=> 'Укажите город откуда будут отправлять посылки.',
				'desc_tip'      => true,
				'class'       	=> 'edostavka-ajax-load',
				'type'			=> 'city_origin',
				'custom_attributes'	  => array( 'required' => 'required' )
			),
			'default_state_id'	=> array(
				'title'			=> 'Город получатель по умолчанию',
				'description'	=> 'Укажите город получатель по умолчанию. Данный город будет использован для пользователей которые ещё не выбирали свой населённый пункт.',
				'desc_tip'      => true,
				'class'       	=> 'edostavka-ajax-load allowClear',
				'type'			=> 'city_origin',
			),
			'sender_address' => array(
				'title'            => 'Данные отправителя (для отправки "от двери")',
				'type'             => 'title'
			),
			'sender_street'		=> array(
				'title'		=> 'Адрес отправителя',
				'type'		=> 'text',
				'desc_tip'	=> 'Улица отправителя. Рекомендуем по возможности не указывать префиксы значений, вроде «ул.»'
			),
			'sender_house'		=> array(
				'title'		=> 'Дом отправителя',
				'type'		=> 'text',
				'desc_tip'	=> 'Дом, корпус, строение отправителя.  Рекомендуем по возможности не указывать префиксы значений, вроде «дом»'
			),
			'sender_flat'		=> array(
				'title'		=> 'Квартира/офис отправителя',
				'type'		=> 'text',
				'desc_tip'	=> 'Квартира/Офис отправителя.  Рекомендуем по возможности не указывать префиксы значений, вроде «кв.»'
			),
			'sender_phone'	=> array(
				'title'		=> 'Номер телефона',
				'type'		=> 'tel',
				'desc_tip'	=> 'Укажите номер телефона отправителя.'
			),
			'additional_settings' => array(
				'title'            => 'Дополнительные настройки',
				'type'             => 'title'
			),
			'order_prefix'		=> array(
				'title'		=> 'Префикс для заказов',
				'type' 		=> 'text',
				'desc_tip'	=> 'Укажите префикс который будет добавляться к номеру заказа при отправке в СДЭК.'
			),
			'send_order_status'	=> array(
				'title'		=> 'Автоматическая отправка заявки',
				'desc_tip'	=> 'Укажите статус при котром заявка будет автоматически отправляться в СДЭК.',
				'type'		=> 'select',
				'class'		=> 'wc-enhanced-select',
				'default'	=> 'none',
				'options'	=> array( 'none' => 'Не использовать' ) + wc_get_order_statuses()
			),
			'autofill_validity' => array(
				'title'       => 'Актуальность городов',
				'type'        => 'select',
				'default'     => '1',
				'class'       => 'wc-enhanced-select',
				'description' => 'Выберите как долго вы хотите хранить данные о городах в базе.',
				'options'     => array(
					'1'     => 'Один месяц',
					'2'     => sprintf( '%d месяца', 2 ),
					'3'     => sprintf( '%d месяца', 3 ),
					'4'     => sprintf( '%d месяца', 4 ),
					'5'     => sprintf( '%d месяцев', 5 ),
					'6'     => sprintf( '%d месяцев', 6 ),
					'7'     => sprintf( '%d месяцев', 7 ),
					'8'     => sprintf( '%d месяцев', 8 ),
					'9'     => sprintf( '%d месяцев', 9 ),
					'10'    => sprintf( '%d месяцев', 10 ),
					'11'    => sprintf( '%d месяцев', 11 ),
					'12'    => 'Год',
					'forever' => 'Всегда',
				),
			),
			'delivery_points_field_type'	=> array(
				'type'		=> 'select',
				'class'		=> 'wc-enhanced-select',
				'default'	=> 'text',
				'title'		=> 'Тип поля ПВЗ',
				'desc_tip'	=> 'Выберите как отображать поле адреса выдачи заказов. Выпадающий список или простое текстовое поле (отключённое).',
				'options'	=> array(
					'text'		=> 'Текстовое поле',
					'hidden'	=> 'Скрытое поле',
					'select'	=> 'Выпадающий список'
				)
			),
			'hide_single_counrty' => array(
				'title'       => 'Скрыть поле "Страна"',
				'type'        => 'checkbox',
				'description' => 'Скрыть поле "Страна" если в магазине доступна только одна страна для доставки.',
				'desc_tip'	  => true,
				'label'       => 'Да',
				'default'     => 'no'
			),
			'required_address_1' => array(
				'title'       => 'Сделать поле "Адрес" не обязательным',
				'description' => 'Опция делает поле "Адрес" необязательным если покутель выбрал метод доставки не СДЭК.',
				'desc_tip'	  => true,
				'type'        => 'checkbox',
				'label'       => 'Да',
				'default'     => 'no'
			),
			'disable_postcode_field' => array(
				'title'		=> 'Отключить поле "Почтовый индекс"',
				'desc_tip'	=> 'Данная опция отключает поле индекс и делает его необязательным если выбран метод доставки СДЭК "до склада"',
				'type'		=> 'checkbox',
				'label'		=> 'Отключить',
				'default'	=> 'yes'
			),
			'disable_state_field'	=> array(
				'title'		=> 'Отключить поле "Область/Район"',
				'desc_tip'	=> 'Данная опция отключает поле "область". Рекомендуется включить эту опцию, так как данное поле не нужно для СДЭК.',
				'type'		=> 'checkbox',
				'label'		=> 'Отключить',
				'default'	=> 'yes'
			),
			'enable_custom_city' => array(
				'title'		=> 'Разрешить города не из списка',
				'desc_tip'	=> 'Включив данную опцию, вы разрешите выбирать "несуществующие" города из списка. Не рекомендуется вкючать, так как если города нету в списке, значит СДЭК не производит доставку в этот НП.',
				'type'        => 'checkbox',
				'label'       => 'Да',
				'default'     => 'no'
			),
			'only_current_country' => array(
				'title'		=> 'Города только текущей страны.',
				'desc_tip'	=> 'Ограничить список городов в выпадающем списке только для выбранной на текущей момент страны. К примеру если покупатель выбрал страну Россия, то в выпадающем списке "Минск" не появится.',
				'type'        => 'checkbox',
				'label'       => 'Да',
				'default'     => 'yes'
			),
			'disable_dropdown_cities_list' => array(
				'title'		=> 'Cписок городов для зон доставки',
				'desc_tip'	=> 'Отключить выпадающий список городов в случае если ниодин метод СДЭК не добавлен для текущий зоны доставки',
				'type'		=> 'checkbox',
				'label'		=> 'Отключить',
				'default'	=> 'no'
			),
			'format_city'		=> array(
				'title'		=> 'Шаблон строки населенных пунктов.',
				'type'		=> 'text',
				'description'	=> 'Данный шаблон указывает как будет выглядеть строки в выпадающем списке городов. Можно использовать ключи <code>%city_name%</code> - название города и <code>%state%</code> - область. Если не знаете как с этим работать то лучше не трогайте.',
				'default'	  	=> '%city_name% (%state%)',
				'custom_attributes'	  => array( 'required' => 'required' )
			),
			'add_insurance_cost'	=> array(
				'title'		=> 'Прибавлять страховку',
				'desc_tip'	=> 'Прибавлять стоимость страховки (0.75% от стоимости товаров) к стоимости доставки если выбран метод оплаты "наложенный платёж/оплата при получении".',
				'type'        => 'checkbox',
				'label'       => 'Да',
				'default'     => 'no'
			),
			'popup_map_settings'	=> array(
				'title'			=> 'Настройки карты выбора ПВЗ',
				'type'			=> 'title'
			),
			'delivery_point_map_position' => array(
				'type'		=> 'select',
				'class'		=> 'wc-enhanced-select',
				'default'	=> 'popup_widget',
				'title'		=> 'Вид отображения карты ПВЗ',
				'desc_tip'	=> 'Выберите как вы хотите отображать карту ПВЗ',
				'options'	=> array(
					'popup_widget'	=> 'Во всплывающем окне (рекомендуется)',
					'under_address'	=> 'Под полем адрес'
				)
			),
			'popup_map_action_button_color' => array(
				'title'		=> 'Цвет кнопки вызова карты',
				'type'		=> 'color',
				'css'      	=> 'width:6em;',
				'default'  	=> '#00bc4c',
				'autoload' 	=> false,
				'desc_tip' 	=> 'Цвет кнопки для вызова вплывающей карты выбора ПВЗ',
			),
			'choose_delivery_point_button_color' => array(
				'title'		=> 'Цвет кнопки выбора ПВЗ',
				'type'		=> 'color',
				'css'      	=> 'width:6em;',
				'default'  	=> '#00bc4c',
				'autoload' 	=> false,
				'desc_tip' 	=> 'Цвет кнопки для выбора ПВЗ',
			),
			'show_search_field_on_map'	=> array(
				'title'		=> 'Показывать поле поиска на карте',
				'desc_tip'	=> 'Опция включает отображение поля для поиска ПВЗ на карте',
				'type'		=> 'checkbox',
				'label'		=> 'Да',
				'default'	=> 'yes'
			),
			'delivery_point_type'	=> array(
				'type'		=> 'select',
				'class'		=> 'wc-enhanced-select',
				'default'	=> 'PVZ',
				'title'		=> 'Тип пункта выдачи',
				'desc_tip'	=> 'Выберите какие типы пунктов выдачи отображать на карте',
				'options'	=> array(
					'ALL'		=> 'Все виды ПВЗ',
					'PVZ'		=> 'Только склады/пвз',
					'POSTAMAT'	=> 'Только постоматы'
				)
			),
			'address_validate'	=> array(
				'title'			=> 'Валидация адреса',
				'type'			=> 'title',
				'description'	=> 'Валидация адреса производится сервисом <a href="https://dadata.ru/?ref=6846" target="_blank">DADATA.RU</a>, поэтому для использвания необходимо ввести <a href="https://dadata.ru/profile/?ref=6846#info" target="_blank">токен и секретный ключ от API</a>'
			),
			'address_validate_enable' => array(
				'title'		=> 'Использовать валидацию',
				'desc_tip'	=> 'Включить пренудительную валидацию поля "адрес"? При включённой опции, оформление заказа не возможно в случае если пользователь ввел "неверный" адрес.',
				'type'      => 'checkbox',
				'label'     => 'Да',
				'default'   => 'no'
			),
			'dadata_token'	=> array(
				'title'		=> 'Токен Dadata',
				'type'		=> 'text',
				'desc_tip'	=> 'Введите ваш токен от API. В личном кабинете он подписан как "API-ключ"'
			),
			'dadata_secret'	=> array(
				'title'		=> 'Секретный ключ',
				'type'		=> 'text',
				'desc_tip'	=> 'Введите ваш секретный ключ от API.'
			),
			'enable_suggestions_address'	=> array(
				'title'		=> 'Автоподсказки для поля адрес',
				'desc_tip'	=> 'Включить автоподсказки для поля "Адрес". Работает только в случае если вы указали Токен и Секретный ключ от сервиса DADATA',
				'type'      => 'checkbox',
				'label'     => 'Включить',
				'default'   => 'no'
			),
			'checkout' => array(
				'title'            => 'Настройки для расчёта',
				'type'             => 'title'
			),
			'minimum_weight' => array(
				'title' 		=> 'Масса по умолчанию (кг)',
				'type' 			=> 'decimal',
				'description' 	=> 'Укажите массу одного товара по умолчанию. Эта масса будет использоваться в расчете доставки одной единицы товара, если у товара не будет указана его масса в карточке товара. (Указывать в килограммах!)',
				'default'		=> 0.5,
				'desc_tip'      => true,
				'custom_attributes'	  => array( 'required' => 'required' )
			),
			'minimum_height' => array(
				'title' 		=> 'Высота по умолчанию (см)',
				'type' 			=> 'decimal',
				'description' 	=> 'Укажите высоту одного товара по умолчанию в установленных еденицах измерения. (Указывать в сантиметрах!)',
				'default'		=> 15,
				'desc_tip'      => true,
				'custom_attributes'	  => array( 'required' => 'required' )
			),
			'minimum_width' => array(
				'title' 		=> 'Ширина по умолчанию (см)',
				'type' 			=> 'decimal',
				'description' 	=> 'Укажите ширину одного товара по умолчанию в установленных еденицах измерения. (Указывать в сантиметрах!)',
				'default'		=> 15,
				'desc_tip'      => true,
				'custom_attributes'	  => array( 'required' => 'required' )
			),
			'minimum_length' => array(
				'title' 		=> 'Длина по умолчанию (см)',
				'type' 			=> 'decimal',
				'description' 	=> 'Укажите длину одного товара по умолчанию в установленных еденицах измерения. (Указывать в сантиметрах!)',
				'default'		=> 15,
				'desc_tip'      => true,
				'custom_attributes'	  => array( 'required' => 'required' )
			),
			'debug' => array(
				'title'            => 'Режим отладки',
				'type'             => 'checkbox',
				'label'            => 'Включить логирование в режиме отладки',
				'default'          => 'no',
				'description'      => sprintf( __( '<p>Все логи будут записаны в <code>%s</code>.</p><p>Посмотреть логи можно на <a href="%s">странице отчётов</a></p>' ), wc_get_log_file_path( $this->id ), add_query_arg( array( 'page' => 'wc-status', 'tab' => 'logs' ), admin_url( 'admin.php') ) )
			),
			'authorization' => array(
				'title'       => 'Авторизация',
				'type'        => 'authorization',
				'description'	=> 'Данная опция нужна лишь для того, что бы понять позволяют ли введённые вами логин с паролем использовать API СДЭК.'
			),
		);
		
		if( defined( 'YWTENV_INIT' ) && ! current_user_can( 'manage_options' ) ) {
			unset( $this->form_fields['license_key'] );
			unset( $this->form_fields['api_login'] );
			unset( $this->form_fields['api_password'] );
			unset( $this->form_fields['dadata_token'] );
			unset( $this->form_fields['dadata_secret'] );
			unset( $this->form_fields['authorization'] );
		}
	}
	
	public function validate_address_validate_enable_field( $key, $value ) {
		
		if( ! is_null( $value ) ) {
			
			$need_fill = false;
			$post_data = $this->get_post_data();
			
			if( ! isset( $post_data[ 'woocommerce_edostavka-integration_dadata_token' ] ) || empty( $post_data[ 'woocommerce_edostavka-integration_dadata_token' ] ) ) {
				WC_Admin_Settings::add_error( 'Вы не указали токен от API Dadata' );
				$need_fill = true;
			}
			
			if( ! isset( $post_data[ 'woocommerce_edostavka-integration_dadata_secret' ] ) || empty( $post_data[ 'woocommerce_edostavka-integration_dadata_secret' ] ) ) {
				WC_Admin_Settings::add_error( 'Вы не указали секретный ключ от API Dadata' );
				$need_fill = true;
			}
			
			if( $need_fill ) {
				return 'no';
			}
		}
		
		return $this->validate_checkbox_field( $key, $value );
	}
	
	public function validate_authorization_field( $key ) {
		return '';
	}
	
	public function generate_authorization_html( $key, $data ) {
		$client_id     = isset( $_POST[ $this->get_field_key( 'api_login' ) ] ) ? sanitize_text_field(  $_POST[ $this->get_field_key( 'api_login' ) ] ) : $this->api_login;
		$client_secret = isset( $_POST[ $this->get_field_key( 'api_password' ) ] ) ? sanitize_text_field( $_POST[ $this->get_field_key( 'api_password' ) ] ) : $this->api_password;
		$access_token  = $this->get_access_token();

		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<?php echo wp_kses_post( $data['title'] ); ?>
			</th>
			<td class="forminp">
				<input type="hidden" name="woocommerce_edostavka_integration_redirect" id="woocommerce_edostavka_integration_redirect">
				<?php if ( ! $access_token && ( $client_id && $client_secret ) ) : ?>
					<p class="submit"><a class="button button-primary" onclick="jQuery('#woocommerce_edostavka_integration_redirect').val('1'); jQuery('#mainform').submit();">Подключиться к СДЭК</a></p>
				<?php elseif ( $access_token ) : ?>
					<p>Аккаунт подключён</p>
				<?php else : ?>
					<p>Ваш аккаунт от API СДЭК не подключён. Вы должны указать ваш секретный логин и пароль от учётной записи API.</p>
				<?php endif; ?>
			</td>
		</tr>
		<?php
		return ob_get_clean();
	}
	
	public function generate_city_origin_html( $key, $data ) {
		
		$key_value = $this->get_option( $key );
		
		if( ! empty( $key_value ) ) {
			$city_origin = WC_Edostavka_Autofill_Addresses::get_city_by_id( $this->get_option( $key ) );
				
			if( ! empty( $city_origin['city_id'] ) ) {
				$data['options'] = array(
					$city_origin['city_id'] => sprintf( '%s (%s)', $city_origin['city_name'], $city_origin['state'] )
				);
			}
		}
		
		$data['type'] = 'select';
		
		return $this->generate_select_html( $key, $data );
	}
	
	public function admin_options() {
		parent::admin_options();
		wp_enqueue_script( $this->id . '-integration', plugins_url( 'assets/js/admin/integration.js', WC_Edostavka::get_main_file() ), array( 'jquery' ), WC_Edostavka::VERSION, true );
		wp_localize_script( $this->id . '-integration', 'wc_edostavka_params', array(
			'ajax_url'		=> WC()->ajax_url(),
			'country_iso'	=> WC()->countries->get_base_country()
		) );
	}
	
	public function set_city_origin() {
		return $this->city_origin;
	}
	
	public function default_state_id() {
		return $this->default_state_id;
	}
	
	public function sender_address_street() {
		return $this->sender_street;
	}
	
	public function sender_address_house() {
		return $this->sender_house;
	}
	
	public function sender_address_flat() {
		return $this->sender_flat;
	}
	
	public function get_sender_phone() {
		return $this->sender_phone;
	}
	
	public function set_api_login() {
		return $this->api_login;
	}
	
	public function set_api_password() {
		return $this->api_password;
	}
	
	public function set_package_weight( $weight = 0 ) {
		return $weight > 0 ? $weight : $this->minimum_weight;
	}
	
	public function set_package_height( $height = 0 ) {
		return $height > 0 ? $height : $this->minimum_height;
	}
	
	public function set_package_width( $width = 0 ) {
		return $width > 0 ? $width : $this->minimum_width;
	}
	
	public function set_package_length( $length = 0 ) {
		return $length > 0 ? $length : $this->minimum_length;
	}
	
	public function set_enable_debug() {
		return 'yes' === $this->enable_debug;
	}
	
	public function setup_autofill_address_validity() {
		return $this->get_option( 'autofill_validity' );
	}
	
	public function process_admin_options() {
		parent::process_admin_options();
		
		if( class_exists( 'WC_Admin_Notices' ) ) {
			
			if( ! empty( $this->settings['license_key'] ) ) {
				
				$message = WD_Plugin_License_Legacy::request( array(
					'license'		=> trim( $this->settings['license_key'] ),
					'item_id'		=> 216
				) );
				
				WC_Admin_Notices::add_custom_notice( $this->id . '_license_notices', $message );
				
				if( WC_Admin_Notices::has_notice( $this->id . '_empty_license' ) ) {
					WC_Admin_Notices::remove_notice( $this->id . '_empty_license' );
				}
			
			} else {
				
				if( WC_Admin_Notices::has_notice( $this->id . '_license_notices' ) ) {
					WC_Admin_Notices::remove_notice( $this->id . '_license_notices' );
				}
				
				WC_Admin_Notices::add_custom_notice( $this->id . '_empty_license', 'Вы не вводили лицензионный ключ. Без лицензионного ключа вы не сможете получать обновления.' );
				update_option( 'wc_edostavka_license_status', 'activate_license' );
			}
		}
	}
}