<?php
/**
 * Plugin Name: PunyaKios for Fluent Forms Payment
 * Plugin URI: https://punyakios.web.id
 * Description: Tambahkan metode pembayaran PunyaKios QRIS di Fluent Forms.
 * Version: 1.0.0
 * Author: PunyaKios Team
 */

if (!defined('ABSPATH')) exit;

add_action('fluentform/payment_gateways_initialized', function ($app) {
    require_once plugin_dir_path(__FILE__) . 'includes/class-fluent-punyakios.php';
    new PunyaKiosFluentGateway($app);
});
