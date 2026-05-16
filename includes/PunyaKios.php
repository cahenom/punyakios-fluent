<?php
/**
 * PunyaKios SDK for WordPress
 */

namespace PunyaKios;

if (!defined('ABSPATH')) {
    exit;
}

class PunyaKios {
    private $apiKey;
    private $baseUrl = 'https://punyakios.web.id/api/merchant';

    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
    }

    public function createPaymentRequest($data) {
        return $this->request('POST', '/payment-request', $data);
    }

    public function getProfile() {
        return $this->request('POST', '/profile');
    }

    public function getTransactions() {
        return $this->request('POST', '/transactions');
    }

    public function getTransactionStatus($external_id) {
        return $this->request('POST', '/check-status', ['external_id' => $external_id]);
    }

    public static function parseCallback() {
        $json = file_get_contents('php://input');
        return json_decode($json, true);
    }

    private function request($method, $endpoint, $data = null) {
        $url = $this->baseUrl . $endpoint;
        
        $args = array(
            'method'    => $method,
            'headers'   => array(
                'X-API-Key'    => $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ),
            'timeout'   => 30,
            'sslverify' => true,
        );

        if ($data) {
            $args['body'] = json_encode($data);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            throw new \Exception('PunyaKios API Error: ' . esc_html($response->get_error_message()));
        }

        $body = wp_remote_retrieve_body($response);
        $code = wp_remote_retrieve_response_code($response);

        return array(
            'status_code' => $code,
            'data'        => json_decode($body, true)
        );
    }
}
