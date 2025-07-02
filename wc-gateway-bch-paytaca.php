<?php
/**
 * Plugin Name: Bitcoin Cash Payments - Paytaca
 * Description: Accept Bitcoin Cash payments via Paytaca.
 * Version: 1.0
 * Author: Paytaca
 */

if (!defined('ABSPATH')) exit;

// Only hook when WooCommerce is loaded
add_action('plugins_loaded', 'init_wc_gateway_bch_paytaca', 11);

function init_wc_gateway_bch_paytaca() {
    if (!class_exists('WC_Payment_Gateway')) {
        add_action('admin_notices', function () {
            echo '<div class="error"><p><strong>WooCommerce BCH Paytaca Gateway</strong> requires WooCommerce to be installed and activated.</p></div>';
        });
        return;
    }

    $gateway_file = __DIR__ . '/includes/class-wc-gateway-bch-paytaca.php';
    if (file_exists($gateway_file)) {
        require_once $gateway_file;
    }

    add_filter('woocommerce_payment_gateways', function ($methods) {
        $methods[] = 'WC_Gateway_BCH_Paytaca';
        return $methods;
    });
}
