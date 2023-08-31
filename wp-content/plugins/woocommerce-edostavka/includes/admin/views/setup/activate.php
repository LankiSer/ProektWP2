<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>

<h1>Ключи активации</h1>
<form method="post" class="activate-plugin">
<?php wp_nonce_field( 'wc-setup' ); ?>
	<label class="location-prompt" for="license_key">Лицензионный ключ</label>
	<input type="text" id="_license_key" class="location-input" name="_license_key" required value="<?php echo esc_attr( $licence_key ); ?>" />
	<small>Без активного ключа вы не сможете получать обновления и техническую поддержку плагина.</small>
	<p>При покупке плагина, данный ключ генерируется автоматически. Вы можете посомтреть его в <a href="https://woodev.ru/my-account" target="_blank">личном кабинете</a>. Так же, ключ, был отправлен вам на почту после покупки.</p>
	
	<label class="location-prompt" for="api_login">Секретный логин API</label>
	<input type="text" id="api_login" class="location-input" name="api_login" value="<?php echo esc_attr( $api_login ); ?>" />
	
	<label class="location-prompt" for="api_password">Пароль API</label>
	<input type="text" id="api_login" class="location-input" name="api_password" value="<?php echo esc_attr( $api_password ); ?>" />
	
	<p>Секретный логин и пароль API вам должны выдать в техническом отделе СДЭК. Если вы до сих пор не получили их, то отправьте запрос на почту integrator@cdek.ru с просьбой прислать вам эти данные. Без этих данных вы не сможете отправлять заказ и расчёт стомости доставки будет без учёта ваших индивидуальных скидок.</p>
	
	<p class="wc-setup-actions step">
		<button type="submit" class="button-primary button button-large button-next" value="Далее" name="save_step">Далее</button>
	</p>
</form>