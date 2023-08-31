<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


?>

<div class="edostavka-tracking-code">
	<div class="tracking-code-result"></div>
	<div class="edostavka-tracking-code__action">
	<?php if ( ! empty( $tracking_code ) ) : ?>		
		<strong>Трекинг код: <?php echo esc_html( $tracking_code ); ?></strong>
		<button type="button" class="button button-secondary remove-order">Отменить заказ</button>
		<button type="button" class="button button-secondary get-print">Печать</button>
	<?php else : ?>
		<p class="form-field form-field-wide">
			<select id="edostavka_extra_services" name="edostavka_extra_services[]" multiple="multiple" class="wc-enhanced-select" data-placeholder="Выберите доп.услуги">
				<option value="">-- Без доп.услуги --</option>
				<?php foreach( wc_edostavka_extra_services_list() as $key => $value ) printf( '<option value="%s">%s</option>', esc_html( $key ), esc_html( $value ) );?>
			</select>
		</p>
		<button type="button" class="button button-secondary add-order">Отправить заказ</button>
	<?php endif; ?>
	</div>
</div>

<script type="text/html" id="tmpl-tracking-code-action">
	<div class="edostavka-tracking-code__action">
		<# if ( data.trackingCode && 0 < data.trackingCode.length ) { #>
		<strong>Трекинг код: {{data.trackingCode}}</strong>
		<button type="button" class="button button-secondary remove-order">Отменить заказ</button>
		<button type="button" class="button button-secondary get-print">Печать</button>
		<# } else if ( data.remove ) { #>
		<p class="form-field form-field-wide">
			<select id="edostavka_extra_services" name="edostavka_extra_services[]" multiple="multiple" class="wc-enhanced-select" data-placeholder="Выберите доп.услуги">
				<option value="">-- Без доп.услуги --</option>
				<?php foreach( wc_edostavka_extra_services_list() as $key => $value ) printf( '<option value="%s">%s</option>', esc_html( $key ), esc_html( $value ) );?>
			</select>
		</p>
		<button type="button" class="button button-secondary add-order">Отправить заказ</button>
		<# } #>
	</div>
</script>

