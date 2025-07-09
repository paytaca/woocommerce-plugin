<?php
// Dynamically locate wp-load.php
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
        error_log("[Paytaca Webhook] Error: wp-load.php not found.");
        exit('Critical Error: Could not load WordPress.');
    }
}

header('Content-Type: application/json');

// Log the raw POST data
$raw_post_data = file_get_contents("php://input");
error_log("[Paytaca Webhook] Received raw POST: " . $raw_post_data);

$data = json_decode($raw_post_data, true);

if (!$data || empty($data['invoice_id'])) {
    error_log("[Paytaca Webhook] Error: Missing 'invoice_id'.");
    http_response_code(400);
    echo json_encode(['error' => 'Missing invoice_id']);
    exit;
}

$invoice_id = sanitize_text_field($data['invoice_id']);

// Find the WooCommerce order by matching meta_key _paytaca_invoice_id
$orders = wc_get_orders([
    'limit'      => 1,
    'meta_key'   => '_paytaca_invoice_id',
    'meta_value' => $invoice_id,
]);

if (empty($orders)) {
    error_log("[Paytaca Webhook] Error: No order found with invoice_id $invoice_id");
    http_response_code(404);
    echo json_encode(['error' => 'Order not found']);
    exit;
}

$order = $orders[0];
$order_id = $order->get_id();
$payment_status = strtolower($data['status'] ?? '');

if (in_array($payment_status, ['paid', 'completed'])) {
    if ($order->get_status() !== 'completed') {
        $order->payment_complete();
        $order->add_order_note("Payment confirmed via Paytaca webhook. TX: " . ($data['transaction_id'] ?? 'N/A'));

        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if ($product && $product->managing_stock()) {
                $product->decrease_stock($item->get_quantity());
            }
        }
    }

    if (WC()->cart) {
        WC()->cart->empty_cart();
    }

    error_log("[Paytaca Webhook] Order #$order_id marked as completed.");
} else {
    if ($order->get_status() !== 'failed') {
        $order->update_status('failed', "Payment failed or unknown status: $payment_status");
    }

    if (WC()->cart) {
        WC()->cart->empty_cart();
    }

    error_log("[Paytaca Webhook] Order #$order_id marked as failed. Status: $payment_status");
}

http_response_code(200);
echo json_encode(['success' => true]);
