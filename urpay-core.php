<?php
/*
Plugin Name: UrPay WooCommerce
Plugin URI: https://github.com/dev-urpay/UrPay-WooCommerce
Description: Plugin para Wordpress de UrPay
Version: 1.0
Author: UrPay
Author URI: http://www.urpay.co/
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action('plugins_loaded', 'woocommerce_urpay_gateway', 0);

function woocommerce_urpay_gateway() {

	if (!class_exists('WC_Payment_Gateway')) {
        return;
    }

	class WC_UrPay extends WC_Payment_Gateway {

		public function __construct() {

			$this->id					= 'urpay';
			$this->icon					= apply_filters('woocomerce_icon_urpay', plugins_url('/images/urpay-woo-plugin.png', __FILE__));
			$this->has_fields			= false;
			$this->method_title			= 'UrPay - Pagos rápidos y seguros';
			$this->method_description	= 'Integración de Urpay para WooCommerce';

			$this->init_form_fields();
			$this->init_settings();

			$this->title = $this->settings['title'];
			$this->commerce_id = $this->settings['commerceid'];
            $this->public_key = $this->settings['public_key'];
            $this->private_key = $this->settings['private_key'];
			$this->gateway_url = 'https://checkout.urpay.co/f/pay/';
			$this->response_page = $this->settings['response_page'];
			$this->confirmation_page = $this->settings['confirmation_page'];

			if (version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=' )) {
                add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
            } else {
                add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
            }

            add_action('woocommerce_receipt_urpay', array(&$this, 'receipt_page'));

        }

        public function getPublicKey() {
            return $this->public_key;
        }

        public function getIdCommerce() {
            return $this->commerce_id;
        }

        public function getPrivateKey() {
            return $this->private_key;
        }

		/**
		 * Funcion que define los campos que iran en el formulario en la configuracion
		 * de la pasarela de UrPay
		 *
		 * @access public
		 * @return void
		 */
		function init_form_fields() {

            $wp_domain = site_url();

			$this->form_fields = array(

				'enabled' => array(
                    'title' => __('Habilitar/Deshabilitar', 'lg_urpay'),
                    'type' => 'checkbox',
                    'label' => __('Habilita la pasarela de pago UrPay', 'lg_urpay'),
                    'default' => 'no'
                ),
                'title' => array(
                    'title' => __('Título', 'lg_urpay'),
                    'type'=> 'text',
                    'description' => __('Título que el usuario verá durante checkout.', 'lg_urpay'),
                    'default' => __('UrPay', 'lg_urpay')
                ),
                'commerceid' => array(
                    'title' => __('ID del comercio', 'lg_urpay'),
                    'type' => 'text',
                    'description' => __('ID del comercio (Lo encuentras en el panel de administración de UrPay).', 'lg_urpay')
                ),
                'public_key' => array(
                    'title' => __('Public Key', 'lg_urpay'),
                    'type' => 'text',
                    'description' => __('Public Key (Lo encuentras en el panel de administración de UrPay).', 'lg_urpay')
                ),
                'private_key' => array(
                    'title' => __('Private Key', 'lg_urpay'),
                    'type' => 'text',
                    'description' => __('Clave secreta de tu cuenta (La encuentras en el panel de administración de UrPay).', 'lg_urpay')
                ),
                'response_page' => array(
                    'title' => __('Página de respuesta'),
                    'type' => 'text',
                    'description' => __('URL de la página mostrada después de finalizar el pago. No olvide cambiar su dominio.', 'lg_urpay'),
                    'default' => __(''.$wp_domain.'/response-urpay/', 'lg_urpay')
                ),
                'confirmation_page' => array(
                    'title' => __('Página de confirmación'),
                    'type' => 'text',
                    'description' => __('URL de la página que recibe la respuesta definitiva sobre los pagos. No olvide cambiar su dominio.', 'lg_urpay'),
                    'default' => __(''.$wp_domain.'/confirm-pay-urpay/', 'lg_urpay')
                )

			);

		}

		/**
         * Muestra el fomrulario en el admin con los campos de configuracion del gateway UrPay
		 *
		 * @access public
         * @return void
         */
        public function admin_options() {
			echo '<h3>'.__('UrPay - Pagos en línea', 'lg_urpay').'</h3>';
			echo '<table class="form-table">';
			$this->generate_settings_html();
			echo '</table>';
		}

		/**
		 * Atiende el evento de checkout y genera la pagina con el formularion de pago.
		 * Solo para la versiones anteriores a la 2.1.0 de WC
         *
         * @access public
         * @return void
		 */
		function receipt_page($order){
			echo '<p>'.__('Gracias por su pedido, da clic en el botón que aparece para continuar el pago con UrPay.', 'lg_urpay').'</p>';
			echo $this->generateFormUrPay($order);
		}

		/**
		 * Construye un arreglo con todos los parametros que seran enviados al gateway de UrPay
         *
         * @access public
         * @return void
		 */
		public function get_params_post($order_id) {

            global $woocommerce;

            $order = new WC_Order($order_id);
            require_once __DIR__.'/class.urpayutil.php';
            $util = new UrPayUtil;

			$tx_currency = get_woocommerce_currency();
            $tx_amount = number_format(($order->get_total()), 2, '.', '');
            $tx_signature = $util->generateSignatureOrder($this->public_key, $this->commerce_id, $order->id, $tx_amount, $tx_currency, $this->private_key);
            $tx_description = '';

            $products = $order->get_items();

			foreach ($products as $product) {
				$tx_description .= $product['name'] . ',';
			}

            if (strlen($tx_description) > 255){
                $tx_description = substr($tx_description, 0, 240).' y más...';
            }

            if ( $order->get_total_tax() == 0 ) {
                $tx_tax = 0;
            } else {
                $tx_tax = number_format(($order->get_total_tax()), 2, '.', '');
            }

			$parameters_args = array(
				'tx_reference' => $order->id,
				'tx_description' => trim($tx_description, ','),
				'tx_amount' => $tx_amount,
				'tx_tax' => $tx_tax,
                'tx_signature' => $tx_signature,
                'tx_commerce' => $this->public_key,
				'tx_commerce_id' => $this->commerce_id,
				'tx_currency' => $tx_currency,
				'by_email' => $order->billing_email,
				'st_confirmation' => $this->confirmation_page,
				'st_response' => $this->response_page,
				'sp_address' => $order->shipping_address_1,
				'sp_country' => $order->shipping_country,
				'sp_city' => $order->shipping_city,
				'py_address' => $order->billing_address_1,
				'py_country' => $order->billing_country,
				'py_city' => $order->billing_city,
				'tx_extra_1' => 'WOOCOMMERCE_URPAY'
            );

            return $parameters_args;

		}

		/**
		 * Metodo que genera el formulario con los datos de pago
         *
         * @access public
         * @return void
		 */
		public function generateFormUrPay($order_id) {

			$parameters_args = $this->get_params_post($order_id);

            $urpay_args = array();

			foreach ($parameters_args as $key => $value) {
				$urpay_args[] = $key.'='.$value;
            }

			$params_post = implode('&', $urpay_args);

            $urpay_args = array();

			foreach ($parameters_args as $key => $value) {
			    $urpay_args[] = '<input type="hidden" name="'.$key.'" value="'.$value.'"/>';
            }

            $form = '<form action="' . $this->gateway_url . '" method="post" enctype="application/x-www-form-urlencoded" id="urpay_form">';
            $form .= implode('', $urpay_args);
            $form .= '<input type="submit" name="pay_urpay" id="pay_urpay" value="' . __('Pagar', 'lg_urpay') . '" />
            </form>';

            return $form;

		}

		/**
		 * Procesa el pago
         *
         * @access public
         * @return void
		 */
		function process_payment($order_id) {

            global $woocommerce;

            $order = new WC_Order($order_id);

            $woocommerce->cart->empty_cart();

			if (version_compare(WOOCOMMERCE_VERSION, '2.0.19', '<=' )) {

				return array('result' => 'success', 'redirect' => add_query_arg('order',
					$order->id, add_query_arg('key', $order->order_key, get_permalink(get_option('woocommerce_pay_page_id'))))
                );

			} else {

				$parameters_args = $this->get_params_post($order_id);

                $urpay_args = array();

				foreach ($parameters_args as $key => $value) {
					$urpay_args[] = $key . '=' . $value;
                }

				$params_post = implode('&', $urpay_args);

				return array(
					'result' => 'success',
					'redirect' =>  $order->get_checkout_payment_url( true )
                );

			}
		}

	}

	/**
	 * Ambas funciones son utilizadas para notifcar a WC la existencia de UrPay
	 */
	function add_urpay($methods) {
		$methods[] = 'WC_UrPay';
		return $methods;
    }

	add_filter('woocommerce_payment_gateways', 'add_urpay' );

    function rw( $wp_rewrite ) {

        $wp_rewrite->rules = array_merge(
            ['response-urpay/?$' => 'index.php?pay-response=1'],
            ['confirm-pay-urpay/?$' => 'index.php?pay-confirmation=1'],
            $wp_rewrite->rules
        );

        return $wp_rewrite->rules;

    }

    function rw_c( $query_vars ) {
        $query_vars[] = 'pay-response';
        return $query_vars;
    }

    function rw_r() {

        global $wp_query;

        $wp_query->is_404 = false;

        $custom = intval( get_query_var( 'pay-response' ) );

        if ( !empty($custom) ) {
            header("HTTP/1.1 200 OK");
            require_once(plugin_dir_path( __FILE__ ) . 'response.php');
            exit;
        }

    }

    function rwc_c( $query_vars ) {
        $query_vars[] = 'pay-confirmation';
        return $query_vars;
    }

    function rwc_r() {

        $custom = intval( get_query_var( 'pay-confirmation' ) );

        if ( !empty($custom) ) {
            header("HTTP/1.1 200 OK");
            require_once(plugin_dir_path( __FILE__ ) . 'confirmation.php');
            exit;
        }

    }

    add_filter('generate_rewrite_rules', 'rw');
    add_filter('query_vars', 'rw_c');
    add_action('template_redirect', 'rw_r');

    add_filter('query_vars', 'rwc_c');
    add_action('template_redirect', 'rwc_r');

}
?>