<?php
if (!defined('ABSPATH')) exit;

require_once __DIR__ . '/bch-address-generator.php';
require_once __DIR__ . '/watchtower-client.php';
require_once __DIR__ . '/invoice-client.php';

class WC_Gateway_BCH_Paytaca extends WC_Payment_Gateway {

    public $xpub;
    public $wallet_hash;
    public $project_id;

    public function __construct() {
        $this->id = 'bch_paytaca';
        $this->has_fields = false;

        $this->method_title = 'Bitcoin Cash (Paytaca)';
        $this->method_description = 'Accept BCH payments via Paytaca Payment Hub.';
        $this->icon = plugins_url('assets/paytaca-icon.png', __FILE__);

        $this->init_form_fields();
        $this->init_settings();

        $this->title        = sanitize_text_field((string) ($this->get_option('title') ?? 'Bitcoin Cash (BCH)'));
        $this->xpub         = sanitize_text_field((string) ($this->get_option('xpub') ?? ''));
        $this->wallet_hash  = sanitize_text_field((string) ($this->get_option('wallet_hash') ?? ''));

        $this->project_id = 'a3938a95-705f-43a8-8dc9-eecd06768922';

        // Save settings
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);

    }

    // BCH icon shown at checkout
    public function get_icon() {
        $bch_icon_url = plugins_url('../assets/bch.png', __FILE__);
        return '<img src="' . esc_url($bch_icon_url) . '" alt="Bitcoin Cash" style="max-width:34px; max-height:34px; vertical-align:middle;" />';
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
            'xpub' => [
                'title'   => 'XPUB Key',
                'type'    => 'text',
                'default' => '',
            ],
            'wallet_hash' => [
                'title'   => 'Wallet Hash',
                'type'    => 'text',
                'default' => '',
            ],
        ];
    }

    public function process_payment($order_id) {
        $order = wc_get_order($order_id);

        if (empty($this->xpub) || empty($this->wallet_hash)) {
            throw new Exception('Plugin misconfiguration: XPUB or Wallet Hash missing.');
        }

        try {
            // 1. Generate BCH Address
            $bch_address = generate_bch_address($this->xpub, $order_id);
            $this->log("Generated address: $bch_address");

            // 2. Subscribe to Watchtower
            $watchtower_result = subscribe_watchtower(
                $bch_address,
                $this->project_id,
                $this->wallet_hash,
                $order_id
            );
            $this->log("Watchtower: " . json_encode($watchtower_result));

            // 3. Create Paytaca Invoice
            $amount   = round(floatval($order->get_total()), 2);
            $currency = $order->get_currency();
            $memo     = "Order #$order_id";

            $invoice_response_json = create_paytaca_invoice($bch_address, $amount, $currency, $memo, $order_id);
            $invoice_response = json_decode($invoice_response_json, true);
            $this->log("Invoice: " . $invoice_response_json);

            if (!isset($invoice_response['invoice_id'])) {
                throw new Exception("Invoice creation failed: invoice_id not returned");
            }

            $invoice_id = $invoice_response['invoice_id'];
            $redirect_url = "https://payment-hub.paytaca.com/invoice/{$invoice_id}";

            $order->update_status('on-hold', 'Awaiting BCH payment via Paytaca.');
            $order->add_order_note("Paytaca Invoice ID: {$invoice_id}");

            return [
                'result'   => 'success',
                'redirect' => $redirect_url,
            ];

        } catch (Exception $e) {
            $this->log("Error: " . $e->getMessage());
            wc_add_notice('Payment error: ' . $e->getMessage(), 'error');
            return [
                'result'   => 'failure',
                'redirect' => '',
            ];
        }
    }

    private function log($msg) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[BCH Paytaca] " . $msg);
        }
    }
}
