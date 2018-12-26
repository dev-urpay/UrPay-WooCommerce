<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__.'/class.urpayutil.php';
require_once __DIR__.'/urpay-core.php';

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

if ( $util->validateSignatureResponse($a_commerce, $i_commerce, $tx_reference, $tx_amount, $tx_currency, $tx_state, $tx_state_text, $tx_signature) ) {

	$order = new WC_Order($tx_reference);

	if ( ($tx_state === '1') && ($tx_state_text == 'COMPLETE') ) {
		$order->payment_complete();
	} else if ( ($tx_state === '2') && ($tx_state_text == 'PENDING') ) {
		$order->update_status('pending', __('Transaccion pendiente', 'woothemes'));
	} else if ( ($tx_state === '3') && ($tx_state_text == 'REJECTED') ) {
		$order->update_status('refunded', __('Transaccion rechazada', 'woothemes'));
	} else if ( ($tx_state === '4') && ($tx_state_text == 'FAILED') ) {
		$order->update_status('failed', __('Transaccion fallida', 'woothemes'));
	} else {
		$order->update_status('failed', __('Transaccion fallida', 'woothemes'));
	}

}
?>