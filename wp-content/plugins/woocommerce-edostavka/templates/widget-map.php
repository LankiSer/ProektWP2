<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if( ! $city_to ) {
	$city_to = $city_form;
}

$city_to_name = WC_Edostavka_Autofill_Addresses::get_city_by_id( $city_to );

if( ! $city_to_name['city_name'] || is_null( $city_to_name['city_name'] ) ) {
	return;
}

?>

<div class="edostavka-map" data-city_from="<?php echo esc_html( $city_form );?>" data-city_to="<?php echo esc_html( $city_to );?>">
	<div class="edostavka-map__search">
		<select name="edostavka-map">
			<option value="<?php echo esc_attr( $city_to_name['city_name'] );?>"><?php echo esc_html( $city_to_name['city_name'] );?></option>
		</select>
	</div>
</div>