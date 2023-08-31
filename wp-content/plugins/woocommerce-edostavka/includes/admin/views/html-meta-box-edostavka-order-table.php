<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$order = wc_get_order( $post->ID );
$request_number = get_option( 'woocommerce_edostavka_request_number', 1 );
$date_now = date( 'Y-m-d' );
?>

<div class="edostavka-order">
	<div class="notice">Перед отправкой убедитесь что все поля таблицы заполнены верно.</div>
	<form method="post">
	<table id="DeliveryRequest">
		<thead>
			<tr>
				<th>Параметр</th>
				<th>Значение</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td>Номер акта</td>
				<td><input type="text" name="request_number" value="<?php echo $request_number;?>" disabled="disabled" /></td>
			</tr>
			<tr>
				<td>Дата заказа</td>
				<td><input type="date" name="request_number" value="<?php echo $date_now;?>" placeholder="Дата в формате 2017-01-01" min="<?php echo $date_now;?>"/></td>
			</tr>
		</tbody>
	</table>
	<?php //wp_nonce_field( '' );?>
	</form>
</div>