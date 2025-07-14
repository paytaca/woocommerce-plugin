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

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
if ($order_id <= 0) {
    error_log("[Paytaca] Missing order_id in verify-payment.");
    wp_redirect(wc_get_checkout_url());
    exit;
}

$order = wc_get_order($order_id);
if (!$order) {
    error_log("[Paytaca] Order #$order_id not found in verify-payment.");
    wp_redirect(wc_get_checkout_url());
    exit;
}

// Get expiration timestamp
$expires_raw = $order->get_meta('_paytaca_invoice_expires');
if (!$expires_raw) {
    error_log("[Paytaca] Missing _paytaca_invoice_expires in order meta (Order #$order_id).");
    wp_redirect(wc_get_checkout_url());
    exit;
}

$expires_timestamp = strtotime($expires_raw);
$current_timestamp = time();

error_log("[Paytaca] Current: $current_timestamp (" . gmdate('Y-m-d H:i:s', $current_timestamp) . ")");
error_log("[Paytaca] Expires: $expires_timestamp (" . gmdate('Y-m-d H:i:s', $expires_timestamp) . ")");

// Common cleanup function
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

if ($current_timestamp <= $expires_timestamp) {
    // Still valid → complete if not already
    if ($order->get_status() !== 'completed') {
        if (!$order->get_payment_method()) {
            $order->set_payment_method('bch_paytaca');
        }

        $order->update_status('completed', 'Manually set to completed after verifying payment.');

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

    wp_redirect(plugins_url('includes/purchase-success.php', dirname(__FILE__)) . "?order_id={$order_id}&status=paid");
    exit;
} else {
    // Expired
    error_log("[Paytaca] Invoice expired for Order #$order_id.");

    $current_status = $order->get_status();
    if (in_array($current_status, ['pending', 'on-hold', 'failed'])) {
        $order->update_status('failed', 'Invoice expired. Marked as failed.');
        $order->save();
    }

    paytaca_clear_wc_cache($order);

    wp_redirect(plugins_url('includes/purchase-success.php', dirname(__FILE__)) . "?order_id={$order_id}&status=expired");
    exit;
}
