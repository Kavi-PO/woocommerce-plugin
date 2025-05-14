<?php
/*
Plugin Name: WooCommerce Payment Options 
Plugin URI: https://www.paymentoptions.com/
Description: Plugin to process payment
Version: 2.0.0
Author: Payment Options
Author URI: https://www.paymentoptions.com/
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

$active_plugins = apply_filters('active_plugins', get_option('active_plugins'));

add_filter('woocommerce_payment_gateways', 'add_payment_sbpo');
function add_payment_sbpo( $gateways ){
    $gateways[] = 'WC_Payment_sbpo';
    return $gateways;
}

add_action('plugins_loaded', 'init_payment_sbpo');
function init_payment_sbpo(){
    require 'class-woocommerce-sbpo.php';
}

/**
 * @return bool
 */
function wpruby_custom_payment_is_woocommerce_active() {
    $active_plugins = (array) get_option('active_plugins', array());

    if (is_multisite()) {
        $active_plugins = array_merge($active_plugins, get_site_option('active_sitewide_plugins', array()));
    }

    return in_array('woocommerce/woocommerce.php', $active_plugins) || array_key_exists('woocommerce/woocommerce.php', $active_plugins);
}