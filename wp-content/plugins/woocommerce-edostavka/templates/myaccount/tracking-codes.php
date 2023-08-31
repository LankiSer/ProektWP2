<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<h2 id="#wc-edostavka-tracking" class="wc-edostavka-tracking__title"><?php _e( 'Delivery tracking code', 'woocommerce-edostavka' );?></h2>

<table class="wc-edostavka-tracking__table woocommerce-table shop_table shop_table_responsive">
	<thead>
		<tr>
			<th colspan="2"><? php _e( 'Code', 'woocommerce-edostavka' );?><th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td><?php echo esc_html( $code ); ?></td>
			<td>
				<form method="GET" target="_blank" action="https://www.cdek.ru/ru/tracking" class="wc-edostavka-tracking__form">
					<input type="hidden" name="order_id" value="<?php echo esc_attr( $code ); ?>">
					<input class="wc-edostavka-tracking__button button" type="submit" value="<?php esc_attr_e( 'View status', 'woocommerce-edostavka' );?>">
				</form>
			</td>
		</tr>
	</tbody>
</table>
