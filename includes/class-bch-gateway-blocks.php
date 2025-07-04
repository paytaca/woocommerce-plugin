<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

if (!defined('ABSPATH')) exit;

class WC_BCH_Paytaca_Blocks_Payment_Method extends AbstractPaymentMethodType {
    protected $name = 'bch_paytaca';

    public function initialize() {
        $this->settings = get_option('woocommerce_bch_paytaca_settings', []);
    }

    public function is_active() {
        return isset($this->settings['enabled']) && $this->settings['enabled'] === 'yes';
    }

    public function get_payment_method_data() {
        return [
            'title'       => $this->settings['title'] ?? 'Bitcoin Cash (BCH)',
            'description' => __('Pay securely with Bitcoin Cash via Paytaca.', 'woocommerce'),
            'supports'    => ['products'],
            'icons'       => [
                [
                    'src' => plugins_url('../assets/bch.png', __FILE__),
                    'alt' => 'Bitcoin Cash',
                ],
            ],
        ];
    }
}
