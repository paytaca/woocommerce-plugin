<?php
function find_wp_load($start_path) {
    $dir = $start_path;

    while ($dir !== dirname($dir)) { // Avoid infinite loop at root
        if (file_exists($dir . '/wp-load.php')) {
            return $dir . '/wp-load.php';
        }
        $dir = dirname($dir);
    }

    return false; // Not found
}

$wp_load = find_wp_load(__DIR__);

if ($wp_load) {
    require_once $wp_load;
} else {
    error_log("[Error] wp-load.php not found.");
    exit("wp-load.php not found");
}


$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if ($order_id <= 0) {
    error_log("[Paytaca] ❌ Missing order_id in verify-payment.");
    wp_redirect(wc_get_checkout_url());
    exit;
}

$order = wc_get_order($order_id);
if (!$order) {
    error_log("[Paytaca] ❌ Order #$order_id not found in verify-payment.");
    wp_redirect(wc_get_checkout_url());
    exit;
}

$expires_raw = $order->get_meta('_paytaca_invoice_expires');
if (!$expires_raw) {
    error_log("[Paytaca] ❌ Missing expires timestamp in order meta for order #$order_id.");
    wp_redirect(wc_get_checkout_url());
    exit;
}

// Compare times
$expires_timestamp = strtotime($expires_raw);
$current_timestamp = time();
error_log("[Paytaca] ⏱ Current: $current_timestamp (" . gmdate('Y-m-d H:i:s', $current_timestamp) . ")");
error_log("[Paytaca] ⏰ Expires: $expires_timestamp (" . gmdate('Y-m-d H:i:s', $expires_timestamp) . ")");


if ($current_timestamp <= $expires_timestamp) {
    // Considered paid
    if ($order->get_status() !== 'completed') {
        // Set payment method if missing
        if (!$order->get_payment_method()) {
            $order->set_payment_method('bch_paytaca');
        }

        // Manually mark as completed
        $order->update_status('completed', '✅ Manually set to completed after verifying payment.');

        // Reduce stock
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            $qty = $item->get_quantity();
            if ($product && $product->managing_stock()) {
                $product->decrease_stock($qty);
                wc_delete_product_transients($product->get_id());
            }
        }

        // Save changes
        $order->save();
    }

    if (function_exists('WC')) {
        WC()->cart->empty_cart();
    }

    wp_redirect(plugins_url('includes/purchase-success.php', dirname(__FILE__)) . "?order_id={$order_id}&status=paid");
    exit;

} else {
    // Expired
    error_log("[Paytaca] ⚠ Invoice expired for Order #$order_id.");

    $current_status = $order->get_status();
    if (in_array($current_status, ['pending', 'on-hold', 'failed'])) {
        $order->update_status('failed', '⏰ Invoice expired. Marked as failed.');
        $order->save();
    }

    if (function_exists('WC')) {
        WC()->cart->empty_cart();  // 💡 Make sure cart is emptied even if expired
    }

    wp_redirect(plugins_url('includes/purchase-success.php', dirname(__FILE__)) . "?order_id={$order_id}&status=expired");
    exit;
}
