<?php
$script_path = dirname(__FILE__);
$path = realpath($script_path . '/./');
$file_path = explode('wp-content', $path);
define('WP_USE_THEMES', false);

require('' . $file_path[0] . '/wp-blog-header.php');
require_once __DIR__.'/class.urpayutil.php';
require_once './urpay-core.php';

$util = new UrPayUtil;

$a_commerce = $util->get('post', 'a_commerce');
$i_commerce = $util->get('post', 'i_commerce');
$tx_signature = $util->get('post', 'tx_signature');
$tx_reference = $util->get('post', 'tx_reference');
$tx_amount = $util->get('post', 'tx_amount');
$tx_currency = $util->get('post', 'tx_currency');
$tx_state = $util->get('post', 'tx_status');
$tx_state_text = $util->get('post', 'tx_status_text');

if ( $util->validateSignatureResponse($a_commerce, $i_commerce, $tx_reference, $tx_amount, $tx_currency, $tx_state, $tx_state_text, $tx_signature) ) {

	$order = new WC_Order($tx_reference);

	if ( ($tx_state === 1) && ($tx_state_text == 'COMPLETE') ) {
		$order->payment_complete();
	} else if ( ($tx_state === 2) && ($tx_state_text == 'PENDING') ) {
		$order->update_status('pending', __('Transaccion pendiente', 'woothemes'));
	} else if ( ($tx_state == 3) && ($tx_state_text == 'REJECTED') ) {
		$order->update_status('refunded', __('Transaccion rechazada', 'woothemes'));
	} else if ( ($tx_state === 4) && ($tx_state_text == 'FAILED') ) {
		$order->update_status('failed', __('Transaccion fallida', 'woothemes'));
	} else {
		$order->update_status('failed', __('Transaccion fallida', 'woothemes'));
	}

}
return;
?>