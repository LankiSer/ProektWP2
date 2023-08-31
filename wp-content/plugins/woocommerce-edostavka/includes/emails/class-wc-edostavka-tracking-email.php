<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if( ! class_exists( 'WC_CDEK_Tracking_Email' ) ) :

class WC_CDEK_Tracking_Email extends WC_Email {


	public function __construct() {
		$this->id               = 'edostavka_tracking';
		$this->title            = 'Трекинг код СДЭК';
		$this->customer_email   = true;
		$this->description      = 'Этот email отправляется при настройке кода отслеживания в заказе';
		$this->heading          = 'Ваш заказ отправлен';
		$this->subject          = '[{site_title}] Ваш заказ {order_number} отправлен через курьерскую службу СДЭК.';
		$this->message          = 'Здравствуйте. Ваш заказ на сайте {site_title} был отправлен через курьерскую службу СДЭК. Чтобы отследить доставку, воспользуйтесь трекинг-кодом {tracking_code} на сайте https://www.cdek.ru/tracking или свяжитесь с нами.';
		$this->tracking_message = $this->get_option( 'tracking_message', $this->message );
		$this->template_html    = 'emails/edostavka-tracking-code.php';
		$this->template_plain   = 'emails/plain/edostavka-tracking-code.php';
		
		$this->placeholders   = array(
			'{order_number}'		=> '',
			'{data}'				=> '',
			'{tracking_code_url}'	=> '',
			'{tracking_code}'		=> '',
		);

		parent::__construct();

		$this->template_base = WC_Edostavka::get_templates_path();
	}


	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'   => 'Вкл/Выкл',
				'type'    => 'checkbox',
				'label'   => 'Включить это email оповещение.',
				'default' => 'yes',
			),
			'subject' => array(
				'title'       => 'Тема',
				'type'        => 'text',
				'description' => sprintf( 'Укажите тему письма. Если оставить пустым то будет использоваться тема по умолчанию: <code>%s</code>.', $this->subject ),
				'placeholder' => $this->subject,
				'default'     => '',
				'desc_tip'    => true,
			),
			'heading' => array(
				'title'       => 'Заголовок',
				'type'        => 'text',
				'description' => sprintf( 'Укажите заголовок письма. По умолчанию: <code>%s</code>.', $this->heading ),
				'placeholder' => $this->heading,
				'default'     => '',
				'desc_tip'    => true,
			),
			'tracking_message' => array(
				'title'       => 'Тело письма',
				'type'        => 'textarea',
				'description' => 'Вы можете использовать спецтэги в теле письма: <code>{order_number}</code> - номер заказа, <code>{data}</code> - дата выгрузки заказа в СДЭК, <code>{tracking_code_url}</code> - ссылка на личный кабинет с номером отслеживания, <code>{tracking_code}</code> - номер отслеживания',
				'placeholder' => $this->message,
				'default'     => '',
				'desc_tip'    => false,
			),
			'email_type' => array(
				'title'       => 'Тип сообщения',
				'type'        => 'select',
				'description' => 'Выберите тип сообщения',
				'default'     => 'html',
				'class'       => 'email_type wc-enhanced-select',
				'options'     => $this->get_custom_email_type_options(),
				'desc_tip'    => true,
			),
		);
	}


	protected function get_custom_email_type_options() {
		if ( method_exists( $this, 'get_email_type_options' ) ) {
			return $this->get_email_type_options();
		}

		$types = array( 'plain' => 'Обычный текст' );

		if ( class_exists( 'DOMDocument' ) ) {
			$types['html']      = 'HTML';
			$types['multipart'] = 'Multipart';
		}

		return $types;
	}


	public function get_tracking_message() {
		return apply_filters( 'woocommerce_edostavka_email_tracking_message', $this->format_string( $this->tracking_message ), $this->object );
	}


	public function get_tracking_code_url( $tracking_code ) {
		
		if( is_array( $tracking_code ) ) {
			$tracking_code = implode( ',', $tracking_code );
		}
		$url = sprintf( '<a href="%s#wc-edostavka-tracking">%s</a>', $this->object->get_view_order_url(), $tracking_code );

		return apply_filters( 'woocommerce_edostavka_email_tracking_core_url', $url, $tracking_code, $this->object );
	}


	public function get_tracking_code( $tracking_code ) {
		return $this->get_tracking_code_url( $tracking_code );
	}


	public function trigger( $order_id, $order = false, $tracking_code = '' ) {
		
		$this->setup_locale();
		
		if ( $order_id && ! is_a( $order, 'WC_Order' ) ) {
			$order = wc_get_order( $order_id );
		}

		if( is_a( $order, 'WC_Order' ) ) {
			$this->object = $order;

			if ( method_exists( $order, 'get_billing_email' ) ) {
				$this->recipient = $order->get_billing_email();
			} else {
				$this->recipient = $order->billing_email;
			}
			
			$tracking_code = empty( $tracking_code ) ? wc_edostavka_get_tracking_code( $order ) : $tracking_code;
			
			$this->placeholders['{date}'] = date_i18n( wc_date_format(), time() );
			$this->placeholders['{order_number}'] = $order->get_order_number();
			$this->placeholders['{tracking_code_url}'] = $this->get_tracking_code( $tracking_code );
			$this->placeholders['{tracking_code}'] = $tracking_code;
		
		}

		if ( $this->is_enabled() && $this->get_recipient() ) {
			$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
		}
		
		$this->restore_locale();
	}


	public function get_content_html() {
		return wc_get_template_html(
			$this->template_html,
			array(
				'order'				=> $this->object,
				'email_heading'		=> $this->get_heading(),
				'tracking_message'	=> $this->get_tracking_message(),
				'sent_to_admin'		=> false,
				'plain_text'		=> false,
				'email'				=> $this,
			),
			'',
			$this->template_base
		);
	}


	public function get_content_plain() {

		$message = $this->get_tracking_message();
		$message = str_replace( '<ul>', "\n", $message );
		$message = str_replace( '<li>', "\n - ", $message );
		$message = str_replace( array( '</ul>', '</li>' ), '', $message );
		
		return wc_get_template_html(
			$this->template_plain,
			array(
				'order'				=> $this->object,
				'email_heading'		=> $this->get_heading(),
				'tracking_message'	=> $message,
				'sent_to_admin'		=> true,
				'plain_text'		=> true,
				'email'				=> $this
			),
			'',
			$this->template_base
		);
	}
}

endif;

return new WC_CDEK_Tracking_Email();
