<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__.'/class.urpayutil.php';
require_once __DIR__.'/urpay-core.php';

get_header('shop');

$util = new UrPayUtil;

$a_commerce = $util->get('get', 'a_commerce');
$i_commerce = $util->get('get', 'i_commerce');
$tx_transaction_id = $util->get('get', 'tx_id');
$tx_signature = $util->get('get', 'tx_signature');
$tx_reference = $util->get('get', 'tx_reference');
$tx_description = $util->get('get', 'tx_description');
$tx_amount = $util->get('get', 'tx_amount');
$tx_currency = $util->get('get', 'tx_currency');
$tx_state = $util->get('get', 'tx_status');
$tx_state_text = $util->get('get', 'tx_status_text');

if (!empty($tx_amount)) {
	$tx_amount = number_format($tx_amount, 2, '.', '');
}

if ($util->validateSignatureResponse($a_commerce, $i_commerce, $tx_reference, $tx_amount, $tx_currency, $tx_state, $tx_state_text, $tx_signature)) {

	$order = new WC_Order($tx_reference);

	$thanks_msg = 'Gracias por usar UrPay.';

	if (($tx_state === '1') && ($tx_state_text == 'COMPLETE')) {
		$status_tx = 'Transacción aprobada';
		$thanks_msg = '¡Gracias por tu compra!';
		$order->payment_complete();
	} else if (($tx_state === '2') && ($tx_state_text == 'PENDING')) {
		$status_tx = 'Transacción pendiente de aprobación.';
		$order->update_status('pending', __('Transaccion pendiente', 'woothemes'));
	} else if (($tx_state === '3') && ($tx_state_text == 'REJECTED')) {
		$status_tx = 'Transacción rechazada';
		$order->update_status('failed', __('Transaccion rechazada', 'woothemes'));
	} else if (($tx_state === '4') && ($tx_state_text == 'FAILED')) {
		$status_tx = 'Transacción fallida';
		$order->update_status('failed', __('Transaccion fallida', 'woothemes'));
	} else {
		$status_tx = 'Transacción fallida';
		$order->update_status('failed', __('Transaccion fallida', 'woothemes'));
	}

	$html = '
	<center>
		<table style="width: 42%; margin-top: 100px;">
			<tr align="center">
				<th colspan="2">DATOS DE LA COMPRA</th>
			</tr>
			<tr align="right">
				<td>Estado de la transacci&oacute;n</td>
				<td>'.$status_tx.'</td>
			</tr>
			<tr align="right">
				<td>ID de la transacci&oacute;n - UrPay</td>
				<td>'.$tx_transaction_id.'</td>
			</tr>
			<tr align="right">
				<td>Referencia de la transacci&oacute;n - Comercio</td>
				<td>'.$tx_reference.'</td>
			</tr>
			<tr align="right">
				<td>Valor total</td>
				<td>'.$tx_amount.'</td>
			</tr>
			<tr align="right">
				<td>Moneda</td>
				<td>'.$tx_currency.'</td>
			</tr>
			<tr align="right">
				<td>Descripción</td>
				<td>'.$tx_description.'</td>
			</tr>
		</table>
		<p/>
		<h1>'.$thanks_msg.'</h1>
	</center>
	';

	echo $html;

} else {
	echo '<h3 style="text-align:center;">Ocurrió un error validando los datos de la transacción.</h3>';
}
get_footer('shop');
?>