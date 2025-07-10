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
            'name'        => $this->name,
            'title'       => $this->settings['title'] ?? 'Bitcoin Cash (BCH)',
            'description' => __('You will be redirected to the Paytaca Payment Hub to complete your BCH payment securely.', 'woocommerce'),
            'supports'    => ['products'],
            'icons'       => [
                [
                    'src' => plugins_url('assets/bch.png', dirname(__FILE__, 2)),
                    'alt' => 'Bitcoin Cash',
                ],
            ],
        ];
    }

    public function get_payment_method_script_handles() {
        return ['bch-paytaca-blocks'];
    }
}

