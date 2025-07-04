<?php
function subscribe_watchtower($address, $project_id, $wallet_hash, $wallet_index) {
    $response = wp_remote_post('https://watchtower.cash/api/subscription/', [
        'headers' => ['Content-Type' => 'application/json'],
        'body' => json_encode([
            'address' => $address,
            'project_id' => $project_id,
            'wallet_hash' => $wallet_hash,
            'wallet_index' => $wallet_index,
        ])
    ]);

    $body = wp_remote_retrieve_body($response);
    $result = json_decode($body, true);

    if (!is_array($result)) {
        error_log("[BCH Paytaca] 🔥 Invalid response from Watchtower:");
        error_log("[BCH Paytaca] HTTP Code: " . wp_remote_retrieve_response_code($response));
        error_log("[BCH Paytaca] Body: " . var_export($body, true));
        throw new Exception("Invalid JSON from Watchtower response.");
    }

    return $result;
}