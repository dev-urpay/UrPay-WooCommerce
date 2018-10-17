<?php
require_once '../../../wp-blog-header.php';
require_once __DIR__.'/class.urpayutil.php';
require_once './urpay-core.php';
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

$tx_amount = number_format($tx_amount, 2, '.', '');

$urpay = new WC_UrPay;

if ($util->validateSignatureResponse($a_commerce, $i_commerce, $tx_reference, $tx_amount, $tx_currency, $tx_state, $tx_state_text, $tx_signature)) {

	$order = new WC_Order($tx_reference);

	if (($tx_state === 1) && ($tx_state_text == 'COMPLETE')) {
		$status_tx = 'Transacción aprobada';
		$thanks_msg = '¡Gracias por tu compra!';
	} else if (($tx_state === 2) && ($tx_state_text == 'PENDING')) {
		$status_tx = 'Transacción pendiente de aprobación.';
	} else if (($tx_state == 3) && ($tx_state_text == 'REJECTED')) {
		$status_tx = 'Transacción rechazada';
	} else if (($tx_state === 4) && ($tx_state_text == 'FAILED')) {
		$status_tx = 'Transacción fallida';
	} else {
		$status_tx = 'Transacción fallida';
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
				<td>ID de la transacci&oacute;n</td>
				<td>'.$tx_id.'</td>
			</tr>
			<tr align="right">
				<td>Referencia de la venta</td>
				<td>'.$tx_sale_reference.'</td>
			</tr>
			<tr align="right">
				<td>Referencia de la transacci&oacute;n</td>
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
	echo 'Ocurrió un error validando los datos de la transacción';
}
get_footer('shop');
?>