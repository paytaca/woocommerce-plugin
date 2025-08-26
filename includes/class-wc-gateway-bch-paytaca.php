<?php
if (!defined('ABSPATH')) exit;

require_once __DIR__ . '/invoice-client.php';

class WC_Gateway_BCH_Paytaca extends WC_Payment_Gateway {
    public $wallet_hash, $project_id, $xpub;

    public function __construct() {
        $this->id                 = 'bch_paytaca';
        $this->method_title       = 'Bitcoin Cash (Paytaca)';
        $this->method_description = 'Accept BCH payments via Paytaca Payment Hub.';
        $this->has_fields         = true;
        $this->icon               = plugins_url('../assets/paytaca-icon.png', __FILE__);

        $this->init_form_fields();
        $this->init_settings();

        $this->title        = $this->get_option('title');
        $this->xpub         = $this->get_option('xpub');
        $this->wallet_hash  = $this->get_option('wallet_hash');

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
        add_action('admin_notices', [$this, 'admin_notices']);
    }

    public function get_icon() {
        return '<img src="' . esc_url(plugins_url('../assets/bch.png', __FILE__)) . '" alt="Bitcoin Cash" style="max-width:34px; vertical-align:middle;" />';
    }

    public function init_form_fields() {
        $this->form_fields = [
            'enabled' => [
                'title'   => 'Enable',
                'type'    => 'checkbox',
                'default' => 'no',
            ],
            'title' => [
                'title'   => 'Title',
                'type'    => 'text',
                'default' => 'Bitcoin Cash (BCH)',
            ],
            'store_name' => [
                'title'   => 'Store Name',
                'type'    => 'text',
                'default' => '',
                'description' => 'Auto-filled after setup.',
                'custom_attributes' => ['readonly' => 'readonly'],
            ],
            'store_id' => [
                'title'   => 'Store ID',
                'type'    => 'text',
                'default' => '',
                'description' => 'Auto-filled after setup.',
                'custom_attributes' => ['readonly' => 'readonly'],
            ],
            'webhook_secret_key' => [
                'title'   => 'Webhook Secret Key',
                'type'    => 'text',
                'default' => '',
                'description' => 'Auto-filled after setup.',
                'custom_attributes' => ['readonly' => 'readonly'],
            ],
            'xpub' => [
                'title'   => 'xPub Key',
                'type'    => 'text',
                'default' => '',
                'description' => 'Required',
            ],
            'paytaca_store_name' => [
                'title'   => 'Paytaca Store Name',
                'type'    => 'text',
                'default' => '',
                'description' => 'Required',
            ],
            'wallet_hash' => [
                'title'   => 'Wallet Hash',
                'type'    => 'text',
                'default' => '',
                'description' => 'Required',
            ],
        ];
    }

    public function process_admin_options() {
        parent::process_admin_options();

        $store_name_input = sanitize_text_field($_POST['woocommerce_bch_paytaca_paytaca_store_name'] ?? '');
        $wallet_hash = sanitize_text_field($_POST['woocommerce_bch_paytaca_wallet_hash'] ?? '');

        if ($store_name_input && $wallet_hash) {
            $this->handle_store_creation($store_name_input, $wallet_hash);
        }
    }

    private function handle_store_creation($store_name, $wallet_hash) {
        $webhook_url = trailingslashit(home_url('/paytaca/webhook'));

        $secret_key = wp_hash($store_name . $wallet_hash . time());

        $payload = [
            'name'               => $store_name,
            'wallet_hash'        => $wallet_hash,
            'webhook_url'        => $webhook_url,
            'webhook_secret_key' => $secret_key
        ];

        error_log('[Paytaca] Store creation payload: ' . json_encode($payload));

        $response = wp_remote_post('https://payment-hub.paytaca.com/api/stores/', [
            'headers' => ['Content-Type' => 'application/json'],
            'body'    => json_encode($payload),
        ]);

        if (is_wp_error($response)) {
            error_log('[Paytaca] Store creation error: ' . $response->get_error_message());
            add_action('admin_notices', fn() => print('<div class="notice notice-error"><p>Paytaca Store creation failed: ' . esc_html($response->get_error_message()) . '</p></div>'));
            return;
        }

        $body = wp_remote_retrieve_body($response);
        error_log('[Paytaca] Store creation response: ' . $body);

        $data = json_decode($body, true);

        if (!isset($data['success']) || !$data['success'] || empty($data['store_id'])) {
            $error_message = $data['message'] ?? 'Unknown error';
            add_action('admin_notices', fn() => print('<div class="notice notice-error"><p>Paytaca Store creation failed: ' . esc_html($error_message) . '</p></div>'));
            return;
        }

        // Save options
        $this->update_option('store_name', $store_name);
        $this->update_option('store_id', sanitize_text_field($data['store_id']));
        $this->update_option('wallet_hash', $wallet_hash);

        // Optional: Save returned wallet_hash and xpub if provided
        if (!empty($data['wallet_hash'])) {
            $this->update_option('wallet_hash_readonly', sanitize_text_field($data['wallet_hash']));
        }

        if (!empty($data['xpub'])) {
            $this->update_option('xpub', sanitize_text_field($data['xpub']));
        }

        // Save returned wallet_secret_key if provided
        if (!empty($data['wallet_secret_key'])) {
            $this->update_option('wallet_secret_key', sanitize_text_field($data['wallet_secret_key']));
        }

        // Also store webhook_secret_key (the one we generated and sent)
        $this->update_option('webhook_secret_key', $secret_key);

        add_action('admin_notices', fn() => print('<div class="notice notice-success"><p>Paytaca Store successfully created and saved.</p></div>'));
    }

    public function admin_notices() {
        if ($this->get_option('enabled') === 'yes' && !$this->get_option('store_id')) {
            echo '<div class="notice notice-warning"><p><strong>Paytaca Gateway:</strong> Please generate a Store ID before accepting BCH payments.</p></div>';
        }
    }

    public function payment_fields() {
        echo '<input type="hidden" name="payment_method" value="' . esc_attr($this->id) . '" />';
    }

    public function process_payment($order_id) {
        $order = wc_get_order($order_id);

        if (empty($this->get_option('store_id'))) {
            throw new Exception('Missing Paytaca Store ID.');
        }

        try {
            $amount   = round((float)$order->get_total(), 2);
            $currency = $order->get_currency();
            $memo     = "Order #$order_id";

            $invoice_response_json = create_paytaca_invoice(
                $this->get_option('store_id'),
                $this->get_option('xpub'),
                $this->get_option('wallet_hash'),
                $amount,
                $currency,
                $memo,
                $order_id
            );

            $invoice = json_decode($invoice_response_json, true);
            if (!isset($invoice['invoice_id'])) {
                throw new Exception("Invoice creation failed.");
            }

            $order->update_meta_data('_paytaca_invoice_id', $invoice['invoice_id']);
            $order->update_meta_data('_paytaca_invoice_expires', $invoice['expires']);
            $order->save();

            $order->update_status('on-hold', 'Awaiting BCH payment via Paytaca.');

            return [
                'result'   => 'success',
                'redirect' => "https://payment-hub.paytaca.com/invoice/{$invoice['invoice_id']}",
            ];
        } catch (Exception $e) {
            wc_add_notice('Payment error: ' . $e->getMessage(), 'error');
            return ['result' => 'failure'];
        }
    }
}
