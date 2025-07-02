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

    $result = json_decode(wp_remote_retrieve_body($response), true);
    if (!is_array($result)) {
        throw new Exception("Invalid JSON from Watchtower response.");
    }
    return $result;

    return json_decode(wp_remote_retrieve_body($response), true);
}
