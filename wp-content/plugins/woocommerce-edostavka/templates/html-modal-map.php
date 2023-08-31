<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$point_button_color = wc_edostavka_integration_get_option( 'choose_delivery_point_button_color', '#00bc4c' );
$point_button_text_color = wc_light_or_dark( $point_button_color, '#323232', '#ffffff' );
?>

<script type="text/template" id="tmpl-wc-modal-edostavka-map">
	<div class="wc-backbone-modal">
		<div class="wc-backbone-modal-content">
			<section class="wc-backbone-modal-main" role="main">
				<header class="wc-backbone-modal-header">
					<h1><?php _e( 'Choose Pick-up point', 'woocommerce-edostavka' );?></h1>
					<button class="modal-close modal-close-link dashicons dashicons-no-alt">
						<span class="screen-reader-text"><?php _e( 'Close', 'woocommerce-edostavka' );?></span>
					</button>
				</header>
				<article id="wc-edostavka-map-container" class="modal-map-container"></article>
			</section>
		</div>
	</div>
	<div class="wc-backbone-modal-backdrop modal-close"></div>
</script>

<script type="text/template" id="tmpl-wc-modal-edostavka-map-balloon">
	<div class="balloon">
		<div class="balloon__header">
			<div class="balloon__title">
				<span class="balloon__index">{{ data.data.name }} [{{ data.data.code }}]</span>
			</div>
		</div>
		<div class="balloon__content">
			<span class="balloon__address">
				{{ data.data.postalCode}}, {{ data.data.address }}
			</span>
			<div class="balloon__payments">
			<# if ( !! data.data.haveCashless ) { #><span class="balloon__payment-item balloon__payment-item_card"><?php _e( 'Pay by Card', 'woocommerce-edostavka' );?></span><# } #>
			<# if ( data.data.haveCash ) { #><span class="balloon__payment-item balloon__payment-item_cash"><?php _e( 'Pay by Cash', 'woocommerce-edostavka' );?></span><# } #>
			<# if ( ! data.data.haveCashless && ! data.data.haveCash ) { #><span class="balloon__payment-item balloon__payment-item_prepayment"><?php _e( 'Prepay only', 'woocommerce-edostavka' );?></span><# } #>
			</div>
			<div class="balloon__schedule">{{ data.data.workTime }}</div>
			<div class="balloon__options">
				<span class="balloon__option-item balloon__option-item_<# if( data.data.isDressingRoom ) { #>check<# } else { #>error<# } #>"><?php _e( 'Fitting room available', 'woocommerce-edostavka' );?></span>
			</div>
			<span class="balloon__description">{{ data.data.note }}</span>
		</div>
		<button type="button" class="balloon__button <# if( data.currentPVZ == data.data.code ) { #>balloon__button_disabled hidden<# } #>" data-placemarkid="{{ data.data.code }}" style="background-color:<?php echo esc_attr( $point_button_color ); ?>; color:<?php echo esc_attr( $point_button_text_color ); ?>;"><?php _e( 'Pick-up here', 'woocommerce-edostavka' );?> <span class="dashicons dashicons-location-alt"></span></button>
		<button type="button" class="balloon__close modal-close <# if( data.currentPVZ != data.data.code ) { #>hidden<# } #>" style="background-color:<?php echo esc_attr( $point_button_color ); ?>; color:<?php echo esc_attr( $point_button_text_color ); ?>;"><?php _e( 'Continue checkout', 'woocommerce-edostavka' );?> <span class="dashicons dashicons-cart"></span></button>
	</div>
</script>