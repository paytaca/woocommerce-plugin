<?php
function create_paytaca_invoice($address, $amount, $currency, $memo, $order_id) {
    // Proper redirect URL to purchase-success.php
    $redirect_url = plugins_url('includes/purchase-success.php', dirname(__FILE__)) . '?order_id=' . intval($order_id);

    $payload = [
        'recipients' => [[
            'amount' => $amount,
            'address' => $address,
            'description' => 'Paid to merchant'
        ]],
        'currency' => $currency,
        'memo' => $memo,
        'redirect_url' => $redirect_url // ✅ User lands here after successful Paytaca payment
    ];

    $response = wp_remote_post('https://payment-hub.paytaca.com/api/invoices/', [
        'headers' => ['Content-Type' => 'application/json'],
        'body' => json_encode($payload)
    ]);

    $body = wp_remote_retrieve_body($response);
    $result = json_decode($body, true);
    if (!is_array($result)) {
        throw new Exception("Invalid JSON from Paytaca invoice API.");
    }

    return $body;
}
