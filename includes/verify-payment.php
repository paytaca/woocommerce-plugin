<?php
// Boot WordPress if not already loaded
if (!defined('ABSPATH')) {
    $dir = __DIR__;
    while ($dir !== dirname($dir)) {
        $wp_load = $dir . DIRECTORY_SEPARATOR . 'wp-load.php';
        if (file_exists($wp_load)) {
            require_once $wp_load;
            break;
        }
        $dir = dirname($dir);
    }

    if (!defined('ABSPATH')) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
        error_log("[Paytaca Verify] Error: wp-load.php not found.");
        exit('Critical Error: Could not load WordPress.');
    }
}

// Sanitize and extract order ID
$order_id_raw = $_GET['order_id'] ?? '';
$order_id = intval(preg_replace('/\D/', '', $order_id_raw));
if ($order_id <= 0) {
    error_log("[Paytaca] Invalid order_id.");
    wp_redirect(wc_get_checkout_url());
    exit;
}

// Load WooCommerce order
if (!function_exists('wc_get_order')) {
    error_log("[Paytaca Verify] WooCommerce not loaded.");
    wp_die('WooCommerce not available.', 'Plugin Error', ['response' => 500]);
}

$order = wc_get_order($order_id);
if (!$order) {
    error_log("[Paytaca] Order #$order_id not found.");
    wp_redirect(wc_get_checkout_url());
    exit;
}

// Fallback-safe status parsing
$status = $_GET['status'] ?? null;

if (!$status) {
    // Try regex fallback
    if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'status=') !== false) {
        preg_match('/status=([a-zA-Z0-9_-]+)/', $_SERVER['REQUEST_URI'], $matches);
        if (!empty($matches[1])) {
            $status = sanitize_text_field($matches[1]);
            error_log("[Paytaca] Fallback: status extracted via regex: " . var_export($status, true));
        }
    }

    // Final fallback: use WooCommerce order status
    if (!$status) {
        $status = $order->get_status();
        error_log("[Paytaca] Fallback: inferred order status from WooCommerce: $status");
    }
} else {
    error_log("[Paytaca] Fetched status from query: " . var_export($status, true));
}

// === Handle Cancelled ===
if ($status === 'cancelled') {
    error_log("[Paytaca] Marking order #$order_id as cancelled.");
    $order->update_status('cancelled', 'Payment was cancelled.');
    $order->save();
    paytaca_clear_wc_cache($order);

    $redirect_url = add_query_arg([
        'paytaca_action' => 'success',
        'order_id'       => $order_id,
        'status'         => 'cancelled'
    ], home_url('/'));

    error_log("[Paytaca] Redirecting to: $redirect_url");
    wp_redirect($redirect_url);
    exit;
}

// === Check for Expiry ===
$expires_raw = $order->get_meta('_paytaca_invoice_expires');
if (!$expires_raw) {
    error_log("[Paytaca] Missing _paytaca_invoice_expires meta.");
    wp_redirect(wc_get_checkout_url());
    exit;
}

$expires_timestamp = strtotime($expires_raw);
$current_timestamp = time();

error_log("[Paytaca] Current: $current_timestamp (" . gmdate('Y-m-d H:i:s', $current_timestamp) . "), Expires: $expires_timestamp (" . gmdate('Y-m-d H:i:s', $expires_timestamp) . ")");

// === Handle Paid ===
if ($current_timestamp <= $expires_timestamp) {
    if ($order->get_status() !== 'completed') {
        if (!$order->get_payment_method()) {
            $order->set_payment_method('bch_paytaca');
        }

        $order->update_status('completed', 'Payment verified manually.');

        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if ($product && $product->managing_stock()) {
                $product->decrease_stock($item->get_quantity());
                wc_delete_product_transients($product->get_id());
            }
        }

        $order->save();
    }

    paytaca_clear_wc_cache($order);

    $redirect_url = add_query_arg([
        'paytaca_action' => 'success',
        'order_id'       => $order_id,
        'status'         => 'paid'
    ], home_url('/'));

    error_log("[Paytaca] Redirecting to: $redirect_url");
    wp_redirect($redirect_url);
    exit;
}

// === Handle Expired ===
error_log("[Paytaca] Invoice expired for Order #$order_id.");
if (in_array($order->get_status(), ['pending', 'on-hold', 'failed'])) {
    $order->update_status('failed', 'Invoice expired.');
    $order->save();
}

paytaca_clear_wc_cache($order);

$redirect_url = add_query_arg([
    'paytaca_action' => 'success',
    'order_id'       => $order_id,
    'status'         => 'expired'
], home_url('/'));

error_log("[Paytaca] Redirecting to: $redirect_url");
wp_redirect($redirect_url);
exit;

// === Helper ===
function paytaca_clear_wc_cache($order) {
    if (function_exists('WC') && WC()->session) {
        WC()->session->set('order_awaiting_payment', false);
        unset(WC()->session->order_awaiting_payment);
    }

    wc_clear_notices();
    wc_delete_shop_order_transients($order);

    if (function_exists('WC')) {
        WC()->cart->empty_cart();
    }
}
