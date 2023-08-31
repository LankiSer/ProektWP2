<?php
/**
 * Tracking history table.
 *
 * @author  Claudio_Sanches
 * @package WooCommerce_edostavka/Templates
 * @version 3.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<h2 id="#wc-edostavka-tracking" class="wc-edostavka-tracking__title"><?php _e( 'Transaction history', 'woocommerce-edostavka' );?> <strong><?php echo esc_html( $code ); ?></h2>

<table class="wc-edostavka-tracking__table woocommerce-table shop_table shop_table_responsive">
	<thead>
		<tr>
			<th><?php _e( 'Date', 'woocommerce-edostavka' );?></th>
			<th><?php _e( 'Location', 'woocommerce-edostavka' );?></th>
			<th><?php _e( 'Status', 'woocommerce-edostavka' );?></th>
		</tr>
	</thead>
	<tbody>

	<?php foreach ( $status['State'] as $state ) : ?>
		<tr>
			<td><?php echo date_i18n( 'd-m-Y H:s', strtotime( $state['Date'] ) ); ?></td>
			<td><?php echo esc_html( $state['CityName'] ); ?></td>
			<td><?php echo esc_html( $state['Description'] ); ?></td>
		</tr>
	<?php endforeach;?>
	</tbody>
	<tfoot>
		<tr>
			<td colspan="3">
				<form method="GET" target="_blank" action="https://www.cdek.ru/ru/tracking" class="wc-edostavka-tracking__form">
					<input type="hidden" name="order_id" value="<?php echo esc_attr( $code ); ?>">
					<input class="wc-edostavka-tracking__button button" type="submit" value="<?php esc_attr_e( 'View on CDEK website', 'woocommerce-edostavka' );?>">
				</form>
			</td>
		</tr>
	</tfoot>
</table>
