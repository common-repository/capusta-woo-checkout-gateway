<?php
/**
 * Plugin Name: Capusta.Woo Checkout Gateway
 * Description: This Capusta.Space plugin allows you to collect donations on Wordpress sites and accept online payments with Visa, Mastercard, Maestro, MIR bank cards for orders in projects created through Woocommerce.
 * Plugin URI: https://dev.capusta.space
 * Version: 1.2.6
 * WC requires at least: 5.0
 * WC tested up to: 6.5.1
 * Text Domain: wc-capusta
 * Domain Path: /languages
 * Author: Capusta
 * Copyright: Capusta.Space © 2021
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package capusta/wc-capusta
 */
defined('ABSPATH') || exit;

if(class_exists('Capusta') !== true)
{

    $plugin_data = get_file_data(__FILE__, array('Version' => 'Version'));
    define('WC_CAPUSTA_VERSION', $plugin_data['Version']);

    define('WC_CAPUSTA_URL', plugin_dir_url(__FILE__));
    define('WC_CAPUSTA_PLUGIN_DIR', plugin_dir_path(__FILE__));
    define('WC_CAPUSTA_PLUGIN_NAME', plugin_basename(__FILE__));

    require_once('vendor/autoload.php');
    include_once __DIR__ . '/includes/functions-wc-capusta.php';
    include_once __DIR__ . '/includes/class-wc-capusta-logger.php';
    include_once __DIR__ . '/includes/class-wc-capusta.php';
}



/*
 * This action hook registers our PHP class as a WooCommerce payment gateway
 */
add_filter( 'woocommerce_payment_gateways', 'capusta_add_gateway_class' );
function capusta_add_gateway_class( $gateways ) {
    $gateways[] = 'WC_Capusta'; // your class name is here
    return $gateways;
}

add_action('plugins_loaded', 'WC_Capusta', 5);
