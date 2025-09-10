<?php
/**
 * Plugin Name: Bitcoin Cash Payments - Paytaca
 * Description: Accept Bitcoin Cash payments via Paytaca.
 * Version: 0.1.3
 * Author: Paytaca
 */

if (!defined('ABSPATH')) exit;

// Add rewrite rule for pretty URL
add_action('init', function () {
    add_rewrite_rule(
        '^paytaca/success/([0-9]+)/?$',
        'index.php?paytaca_action=success&order_id=$matches[1]',
        'top'
    );
    
    // Check if our rewrite rule exists, if not flush rules
    $rules = get_option('rewrite_rules');
    if (!isset($rules['^paytaca/success/([0-9]+)/?$'])) {
        flush_rewrite_rules();
    }
});

add_action('init', function () {
    add_rewrite_rule(
        '^paytaca/webhook/?$',
        'index.php?paytaca_action=webhook',
        'top'
    );
    
    // Check if webhook rewrite exists, if not flush rules
    $rules = get_option('rewrite_rules');
    if (!isset($rules['^paytaca/webhook/?$'])) {
        flush_rewrite_rules();
    }
});


// Register query vars
add_filter('query_vars', function ($vars) {
    $vars[] = 'paytaca_action';
    $vars[] = 'order_id';
    $vars[] = 'status';
    return $vars;
});

// Handle custom actions
add_action('template_redirect', function () {
    $action = get_query_var('paytaca_action');
    if (!$action) return;

    error_log("[Paytaca] Handling paytaca_action via query_var: " . $action);

    switch ($action) {
        case 'verify':
            require_once __DIR__ . '/includes/verify-payment.php';
            exit;
        case 'success':
            require_once __DIR__ . '/includes/purchase-success.php';
            exit;
        case 'webhook':
            require_once __DIR__ . '/includes/bch-webhook.php';
            exit;
    }
});

// Flush rewrite rules on activation
register_activation_hook(__FILE__, function () {
    flush_rewrite_rules();
});

// Clean up rewrite rules on deactivation
register_deactivation_hook(__FILE__, function () {
    flush_rewrite_rules();
});

// Add admin notice if rewrite rules need flushing
add_action('admin_notices', function () {
    if (isset($_GET['page']) && $_GET['page'] === 'wc-settings' && isset($_GET['tab']) && $_GET['tab'] === 'checkout') {
        $rules = get_option('rewrite_rules');
        if (!isset($rules['^paytaca/success/([0-9]+)/?$'])) {
            echo '<div class="notice notice-warning"><p><strong>Paytaca Gateway:</strong> Rewrite rules need to be updated. <a href="' . admin_url('options-permalink.php') . '">Go to Permalinks</a> and click "Save Changes" to fix this.</p></div>';
        }
    }
});

// Add manual fix function
add_action('wp_ajax_fix_paytaca_rewrite_rules', function () {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }
    
    flush_rewrite_rules();
    wp_send_json_success('Rewrite rules flushed successfully');
});

// Add debug endpoint
add_action('template_redirect', function () {
    if (strpos($_SERVER['REQUEST_URI'], '/paytaca/debug') === 0) {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $rules = get_option('rewrite_rules');
        $has_rule = isset($rules['^paytaca/success/([0-9]+)/?$']);
        
        echo '<h1>Paytaca Debug Info</h1>';
        echo '<p><strong>Rewrite rule exists:</strong> ' . ($has_rule ? 'Yes' : 'No') . '</p>';
        echo '<p><strong>Current rules:</strong></p>';
        echo '<pre>' . print_r(array_keys($rules), true) . '</pre>';
        
        if (!$has_rule) {
            echo '<p><a href="' . admin_url('options-permalink.php') . '">Go to Permalinks to fix</a></p>';
        }
        exit;
    }
});

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

// Blocks support
add_action('woocommerce_blocks_loaded', function () {
    if (!class_exists(\Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType::class)) {
        return;
    }

    require_once __DIR__ . '/includes/class-bch-gateway-blocks.php';

    add_action('woocommerce_blocks_payment_method_type_registration', function ($registry) {
        $registry->register(new WC_BCH_Paytaca_Blocks_Payment_Method());
    });
});

// Scripts
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
