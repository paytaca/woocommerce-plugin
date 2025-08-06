<?php
if (!defined('ABSPATH')) exit;

function create_paytaca_invoice($store_id, $xpub_key, $wallet_hash, $amount, $currency, $memo, $order_id) {
    $success_url = trailingslashit(home_url('/paytaca/success/' . intval($order_id))); // Fix: ensure trailing slash

    $payload = [
        'recipients' => [[
            'amount'       => $amount,
            'xpub_key'     => $xpub_key,
            'index'        => $order_id,
            'wallet_hash'  => $wallet_hash,
            'description'  => 'Payment for goods or services'
        ]],
        'currency'     => $currency,
        'memo'         => $memo,
        'store_id'     => $store_id,
        'redirect_url' => $success_url,
        'reference'    => $order_id
    ];

    error_log("[Paytaca] Creating invoice for Order #$order_id: " . json_encode($payload));

    $response = wp_remote_post('https://payment-hub.paytaca.com/api/invoices/', [
        'headers' => ['Content-Type' => 'application/json'],
        'body'    => json_encode($payload)
    ]);

    if (is_wp_error($response)) {
        error_log("[Paytaca] Failed to contact Paytaca API: " . $response->get_error_message());
        return json_encode(['error' => 'Invoice creation failed.']);
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    error_log("[Paytaca] Invoice response: " . $body);

    return json_encode($data);
}
