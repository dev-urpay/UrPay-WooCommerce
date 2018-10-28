<?php
final class UrPayUtil {

    public function get(
        $type = 'post',
        $key = '',
        $filter = FILTER_SANITIZE_SPECIAL_CHARS
    ) {

        switch ($type) {

            case 'post':
                return $this->manageObtainPostVars($key, $filter);

            case 'get':
                return $this->manageObtainGetVars($key, $filter);

        }

        return;

    }

    private function manageObtainPostVars($key, $filter) {

        if (!empty($key)) {

            if (isset($_POST[$key])) {

                $var = (is_array($_POST[$key])) ? filter_var_array($_POST[$key], $filter) : filter_var($_POST[$key], $filter);

                return $var;

            } else {
                return '';
            }

        } else {
            return !empty($filter) ? filter_var_array($_POST, $filter) : $_POST;
        }

    }

    private function manageObtainGetVars($key, $filter) {

        if (!empty($key)) {

            if (isset($_GET[$key])) {

                $var = (is_array($_GET[$key])) ? filter_var_array($_GET[$key], $filter) : filter_var($_GET[$key], $filter);

                return $var;

            } else {
                return '';
            }

        } else {
            return !empty($filter) ? filter_var_array($_GET, $filter) : $_GET;
        }

    }

    public function validateSignatureResponse($a_commerce, $i_commerce, $tx_reference, $tx_amount, $tx_currency, $tx_state, $tx_state_text, $tx_signature) {

        $payu = new WC_UrPay;
        $public_key = $a_commerce;
        $id_commerce = $i_commerce;

        $public_key_site = $urpay->getPublicKey();
        $id_commerce_site = $urpay->getIdCommerce();
        $private_key_site = $urpay->getPrivateKey();

        if ( ($public_key === $public_key_site) && ($id_commerce === $id_commerce_site) ) {

            return $this->validateSignData($public_key_site, $id_commerce_site, $tx_reference, $tx_amount, $tx_currency, $tx_state, $tx_state_text, $tx_signature, $private_key_site);

        } else {
            return false;
        }

    }

    private function validateSignData($public_key, $commerce_id, $tx_reference, $tx_amount, $tx_currency, $tx_state, $tx_state_text, $tx_signature, $private_key) {

        $data = $public_key.'%'.$commerce_id.'%'.$tx_reference.'%'.$tx_amount.'%'.$tx_currency.'%'.$tx_state.'%'.$tx_state_text;

        $hash_available = hash_algos();

        if ( in_array('sha512/256', $hash_available) ) {
            $h = 'sha512/256';
        } else if ( in_array('sha512', $hash_available) ) {
            $h = 'sha512';
        } else if (in_array('sha384', $hash_available)) {
            $h = 'sha384';
        } else if ( in_array('sha256', $hash_available) ) {
            $h = 'sha256';
        } else if (in_array('md5', $hash_available)) {
            $h = 'md5';
        } else {
            return false;
        }

        $hash = hash_hmac($h, $data, $private_key);

        if ( (!empty($hash)) && ($hash !== false) ) {

            if ( $hash === $tx_signature ) {
                return true;
            } else {
                return false;
            }

        } else {
            return false;
        }

    }

    public function generateSignatureOrder($public_key, $commerce_id, $tx_reference, $tx_amount, $tx_currency, $private_key) {

        $data = $public_key.'%'.$commerce_id.'%'.$tx_reference.'%'.$tx_amount.'%'.$tx_currency;

        $hash_available = hash_algos();

        if (in_array('sha512/256', $hash_available)) {
            $h = 'sha512/256';
        } else if (in_array('sha512', $hash_available)) {
            $h = 'sha512';
        } else if (in_array('sha384', $hash_available)) {
            $h = 'sha384';
        } else if (in_array('sha256', $hash_available)) {
            $h = 'sha256';
        } else if (in_array('md5', $hash_available)) {
            $h = 'md5';
        } else {
            return false;
        }

        $hash = hash_hmac($h, $data, $private_key);

        if ((!empty($hash)) && ($hash !== false)) {
            return $hash;
        } else {
            return false;
        }

    }

}
?>