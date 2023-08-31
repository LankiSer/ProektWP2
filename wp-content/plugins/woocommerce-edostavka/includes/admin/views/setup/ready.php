<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<h1>Почти всё готово!</h1>
<p>Вы сделали основные настройки плагина WC eDostavka. Для более детальной настройки можете перейти на <a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=integration' ) ); ?>">страницу настроек плагина</a>. <br />Если у вас есть какие либо вопросы по настроке плагина, то <a href="https://woodev.ru/plagin-integratsiya-sdek.html?utm=plugin-wizard-setup" target="_blank">ознакомьтесь с документацией</a>.</p>
<p>Если у вас ещё нет созданных зон доставки, то самое время сделать это.<p>

<ul class="wc-wizard-next-steps">
	<li class="wc-wizard-next-step-item">
		<div class="wc-wizard-next-step-description">
			<p class="next-step-heading">Следующий этап</p>
			<h3 class="next-step-description">Создать новые зоны доставки</h3>
			<p class="next-step-extra-info">Старайтесь создавать зоны с ассоциативными названиями, что бы потом не запутаться в созданных зонах.</p>
		</div>
		<div class="wc-wizard-next-step-action">
			<p class="wc-setup-actions step">
				<a class="button button-primary button-large" href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=shipping&zone_id=new' ) ); ?>">Добавить зону</a>
			</p>
		</div>
	</li>
	<?php if( ! empty( $existing_zones ) ) : ?>
	<li class="wc-wizard-next-step-item">
		<div class="wc-wizard-next-step-description">
			<p class="next-step-heading">Есть созданные зоны?</p>
			<h3 class="next-step-description">Добавить методы в зоны</h3>
			<p class="next-step-extra-info">Вы можете добавить методы доставки СДЭК в уже созданные зоны доставки.</p>
		</div>
		<div class="wc-wizard-next-step-action">
			<p class="wc-setup-actions step">
				<?php foreach( $existing_zones as $zone ) printf( '<a class="button button-small" href="%s">%s</a>', esc_url( admin_url( 'admin.php?page=wc-settings&tab=shipping&zone_id=' . $zone['zone_id'] ) ), $zone['zone_name'] );?>
			</p>
		</div>
	</li>
	<?php endif; ?>
</ul>