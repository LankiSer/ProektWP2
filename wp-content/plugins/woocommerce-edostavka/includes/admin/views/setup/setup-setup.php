<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>

<form method="post" class="address-step">
	<?php wp_nonce_field( 'wc-setup' ); ?>
	<p class="store-setup">Этот помошник поможет вам настроить плагин, что бы вы не забыли что-то указать в настройках.</p>
	
	<label for="city_origin" class="location-prompt">Выберите город отправитель</label>
	
	<select id="city_origin" name="city_origin" required class="location-input edostavka-ajax-load">
		<?php if( ! empty( $city_origin_ajax['city_id'] ) ) printf( '<option value="%s" selected>%s (%s)</option>', $city_origin_ajax['city_id'], $city_origin_ajax['city_name'], $city_origin_ajax['state'] );?>
	</select>
	
	<label for="default_state_id" class="location-prompt">Выберите город получатель</label>
	
	<select id="default_state_id" name="default_state_id" class="location-input edostavka-ajax-load">
		<?php if( ! empty( $default_state_ajax['city_id'] ) ) printf( '<option value="%s" selected>%s (%s)</option>', $default_state_ajax['city_id'], $default_state_ajax['city_name'], $default_state_ajax['state'] );?>
	</select>
	
	<label for="autofill_validity" class="location-prompt">Актуальность списка городов</label>
	<select id="autofill_validity" name="autofill_validity" required class="location-input wc-enhanced-select dropdown">
		<option value="1">Один месяц</option>
		<option value="2">Два месяца</option>
		<option value="3">Три месяца</option>
		<option value="4">Четыре месяца</option>
		<option value="5">Пять месяцев</option>
		<option value="6">Шесть месяцев</option>
		<option value="7">Семь месяцев</option>
		<option value="8">Восемь месяцев</option>
		<option value="9">Девять месяцев</option>
		<option value="10">Десять месяцев</option>
		<option value="11">Одинадцать месяцев</option>
		<option value="12">Год</option>
		<option value="forever">Всегда</option>
	</select>

	<p>Видимость полей</p>
	<ul class="wc-wizard-services shipping">
		<li class="wc-wizard-service-item">
			<div class="wc-wizard-service-name" style="font-weight: 400;">
				<p>Страна</p>
			</div>
			<div class="wc-wizard-service-description" style="font-weight: 400;">
				<p>Скрыть поле "Страна" если в магазине доступна только одна страна для доставки.</p>
			</div>
			<div class="wc-wizard-service-enable">
				<span class="wc-wizard-service-toggle">
					<input id="hide_single_counrty" type="checkbox" name="hide_single_counrty" value="yes" <?php checked( $hide_single_counrty === 'yes', true, true );?> class="wc-wizard-shipping-method-enable" />
					<label for="hide_single_counrty">
				</span>
			</div>
		</li>
		<li class="wc-wizard-service-item">
			<div class="wc-wizard-service-name">
				<p>Квартира, апартаменты, жилое помещение</p>
			</div>
			<div class="wc-wizard-service-description">
				<p>Обычно это поле не используется. Если вы не хотите отображать это поле на странице оформления заказа, то оставьте эту опцию активной.</p>
			</div>
			<div class="wc-wizard-service-enable">
				<span class="wc-wizard-service-toggle">
					<input id="remove_address_2" type="checkbox" name="remove_address_2" value="yes" <?php checked( $remove_address_2 === 'yes', true, true );?> class="wc-wizard-shipping-method-enable" />
					<label for="remove_address_2">
				</span>
			</div>
		</li>
		<li class="wc-wizard-service-item">
			<div class="wc-wizard-service-name">
				<p>Города только текущей страны</p>
			</div>
			<div class="wc-wizard-service-description">
				<p>Ограничить список городов в выпадающем списке только для выбранной на текущей момент страны. К примеру если покупатель выбрал страну Россия, то в выпадающем списке "Минск" не появится.</p>
			</div>
			<div class="wc-wizard-service-enable">
				<span class="wc-wizard-service-toggle">
					<input id="only_current_country" type="checkbox" name="only_current_country" value="yes" <?php checked( $only_current_country === 'yes', true, true );?> class="wc-wizard-shipping-method-enable" />
					<label for="only_current_country">
				</span>
			</div>
		</li>
	</ul>
	
	<p>Параметры товара по-умочанию</p>
	<div class="wc-setup-shipping-units">
		<div>
			<label class="location-prompt" for="minimum_weight">Масса по умолчанию, (кг.)</label>
			<input  type="text" id="minimum_weight" class="location-input" name="minimum_weight" required value="<?php echo esc_attr( $minimum_weight ); ?>" />
		</div>
		<div style="width:33%;float:left;margin-right:1%;">
			<label class="location-prompt" for="minimum_height">Высота по умолчанию, (см.)</label>
			<input type="text" id="minimum_height" class="location-input" name="minimum_height" required value="<?php echo esc_attr( $minimum_height ); ?>" />
		</div>
		<div style="width:33%;float:left;margin-right:1%;">
			<label class="location-prompt" for="minimum_width">Ширина по умолчанию, (см.)</label>
			<input type="text" id="minimum_width" class="location-input" name="minimum_width" required value="<?php echo esc_attr( $minimum_width ); ?>" />
		</div>
		<div style="width:32%;float:left;margin-right:0;">
			<label class="location-prompt" for="minimum_length">Длина по умолчанию, (см.)</label>
			<input type="text" id="minimum_length" class="location-input" name="minimum_length" required value="<?php echo esc_attr( $minimum_length ); ?>" />
		</div>
		<div class="clear clearfix"></div>
	</div>
	
	<p class="wc-setup-actions step">
		<button type="submit" class="button-primary button button-large button-next" value="Далее" name="save_step">Далее</button>
	</p>
</form>