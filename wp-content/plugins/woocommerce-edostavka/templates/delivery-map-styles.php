<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$button_base_bg = wc_edostavka_integration_get_option( 'popup_map_action_button_color', '#00bc4c' );
$button_text_color = wc_light_or_dark( $button_base_bg, '#323232', '#ffffff' );
$button_hover_bg = wc_hex_is_light( $button_base_bg ) ? wc_hex_darker( $button_base_bg, 10 ) : wc_hex_lighter( $button_base_bg, 10 );
$button_hover_text_color = wc_hex_is_light( $button_text_color ) ? wc_hex_darker( $button_text_color, 10 ) : wc_hex_lighter( $button_text_color, 10 );

?>

button.wc-edostavka-choose-delivery-point {
	background-color: <?php echo esc_attr( $button_base_bg ); ?>;
	border: 1px solid <?php echo esc_attr( wc_hex_darker( $button_base_bg, 20 ) ); ?>;
	color: <?php echo esc_attr( $button_text_color ); ?>;
}

button.wc-edostavka-choose-delivery-point:hover,
button.wc-edostavka-choose-delivery-point:active,
button.wc-edostavka-choose-delivery-point.active {
	background-color: <?php echo esc_attr( $button_hover_bg ); ?>;
	border: 1px solid <?php echo esc_attr( wc_hex_darker( $button_hover_bg, 20 ) ); ?>;
	color: <?php echo esc_attr( $button_hover_text_color ); ?>;
}