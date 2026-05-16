<?php

if (!defined('ABSPATH')) {
    exit;
}

class PunyaKiosFluentGateway {
    protected $app;
    protected $key = 'punyakios';

    public function __construct($app) {
        $this->app = $app;
        $this->init();
    }

    public function init() {
        add_filter('fluentform/payment_methods', array($this, 'addPaymentMethod'));
        add_filter('fluentform/payment_settings_fields', array($this, 'addSettingsFields'), 10, 2);
        add_action('fluentform/process_payment_' . $this->key, array($this, 'handlePayment'), 10, 6);
        add_action('fluentform/payment_callback_' . $this->key, array($this, 'handleCallback'));
    }

    public function addPaymentMethod($methods) {
        $methods[$this->key] = array(
            'title' => __('PunyaKios (QRIS)', 'fluentform'),
            'enabled' => $this->getSetting('enabled') == 'yes',
            'method_value' => $this->key,
            'settings' => $this->getSettings(),
            'image' => plugin_dir_url(dirname(__FILE__)) . 'assets/logo.png'
        );
        return $methods;
    }

    public function addSettingsFields($fields, $method) {
        if ($method !== $this->key) {
            return $fields;
        }

        return array(
            'title' => __('PunyaKios Settings', 'fluentform'),
            'fields' => array(
                array(
                    'name' => 'enabled',
                    'label' => __('Enable PunyaKios', 'fluentform'),
                    'type' => 'yes_no',
                    'default' => 'no'
                ),
                array(
                    'name' => 'api_key',
                    'label' => __('Merchant API Key', 'fluentform'),
                    'type' => 'password',
                    'placeholder' => __('Enter your PunyaKios API Key', 'fluentform'),
                    'help' => __('Get your API Key from PunyaKios Dashboard', 'fluentform')
                )
            )
        );
    }

    public function handlePayment($submissionId, $form, $methodSettings, $subscription, $totalAmount, $transaction) {
        require_once dirname(__FILE__) . '/PunyaKios.php';
        
        $apiKey = $this->getSetting('api_key');
        $sdk = new \PunyaKios\PunyaKios($apiKey);

        try {
            $response = $sdk->createPaymentRequest([
                'external_id' => (string)$submissionId,
                'amount' => (int)$totalAmount,
                'description' => 'Payment for Form: ' . $form->title,
                'callback_url' => add_query_arg([
                    'fluentform_payment_callback' => 'yes',
                    'payment_method' => $this->key
                ], home_url('/'))
            ]);

            if ($response['status_code'] === 200) {
                wp_redirect($response['data']['data']['checkout_url']);
                exit;
            } else {
                throw new Exception($response['data']['message']);
            }
        } catch (Exception $e) {
            wp_die(__('Payment Error: ', 'fluentform') . esc_html($e->getMessage()));
        }
    }

    public function handleCallback() {
        require_once dirname(__FILE__) . '/PunyaKios.php';
        $data = \PunyaKios\PunyaKios::parseCallback();

        if ($data && $data['status'] === 'PAID') {
            $submissionId = $data['external_id'];
            
            // Fluent Forms specific logic to update transaction
            // This is a simplified version, usually involves using FluentForm\App\Models\Submission
            do_action('fluentform/payment_status_updated', $submissionId, 'paid', 'PunyaKios');
            
            status_header(200);
            exit('OK');
        }
        
        status_header(400);
        exit;
    }

    protected function getSetting($key) {
        $settings = $this->getSettings();
        return isset($settings[$key]) ? $settings[$key] : '';
    }

    protected function getSettings() {
        return get_option('_fluentform_payment_settings_' . $this->key, array());
    }
}
