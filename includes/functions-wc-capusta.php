<?php
/**
 * Main instance of WC_Capusta
 * @since 3.0.0
 *
 * @return WC_Capusta|false
 */
function WC_Capusta()
{
	if(is_callable('WC_Capusta::instance'))
	{
		return WC_Capusta::instance();
	}

	return false;
}

/**
 * Get current version WooCommerce
 *
 * @since 3.0.2
 */
function wc_capusta_get_wc_version()
{
	if(function_exists('is_woocommerce_active') && is_woocommerce_active())
	{
		global $woocommerce;

		if(isset($woocommerce->version))
		{
			return $woocommerce->version;
		}
	}

	if(!function_exists('get_plugins'))
	{
		require_once(ABSPATH . 'wp-admin/includes/plugin.php');
	}

	$plugin_folder = get_plugins('/woocommerce');
	$plugin_file = 'woocommerce.php';

	if(isset($plugin_folder[$plugin_file]['Version']))
	{
		return $plugin_folder[$plugin_file]['Version'];
	}

	return null;
}

/**
 * Get WooCommerce currency code
 *
 * @since 3.0.2
 *
 * @return string
 */
function wc_capusta_get_wc_currency()
{
	return get_woocommerce_currency();
}