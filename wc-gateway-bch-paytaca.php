<?php
/**
 * Plugin Name: Bitcoin Cash Payments - Paytaca
 * Description: Accept Bitcoin Cash payments via Paytaca.
 * Version: 1.1
 * Author: Paytaca
 */

if (!defined('ABSPATH')) exit;

// Load gateway
add_action('plugins_loaded', 'init_wc_gateway_bch_paytaca', 11);

function init_wc_gateway_bch_paytaca() {
    if (!class_exists('WC_Payment_Gateway')) {
        add_action('admin_notices', function () {
            echo '<div class="error"><p><strong>WooCommerce BCH Paytaca Gateway</strong> requires WooCommerce to be installed and activated.</p></div>';
        });
        return;
    }

    require_once __DIR__ . '/includes/class-wc-gateway-bch-paytaca.php';

    add_filter('woocommerce_payment_gateways', function ($methods) {
        $methods[] = 'WC_Gateway_BCH_Paytaca';
        return $methods;
    });
}

// Add support for WooCommerce Blocks
add_action('woocommerce_blocks_loaded', function () {
    if (!class_exists(\Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType::class)) {
        return;
    }

    require_once __DIR__ . '/includes/class-bch-gateway-blocks.php';

    add_action(
        'woocommerce_blocks_payment_method_type_registration',
        function ($payment_method_registry) {
            $payment_method_registry->register(new WC_BCH_Paytaca_Blocks_Payment_Method());
        }
    );
});

// Enqueue block assets
add_action('wp_enqueue_scripts', function () {
    wp_register_script(
        'bch-paytaca-blocks',
        plugins_url('assets/js/blocks-bch-paytaca.js', __FILE__),
        ['wp-element', 'wp-api-fetch', 'wc-blocks-registry'],
        '1.0.2',
        true
    );

    wp_add_inline_script(
        'bch-paytaca-blocks',
        'window.bchPaytacaIconUrl = "' . esc_js(plugins_url('assets/bch.png', __FILE__)) . '";',
        'before'
    );

}, 20);
