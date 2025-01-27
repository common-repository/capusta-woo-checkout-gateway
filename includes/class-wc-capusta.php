<?php
/**
 * Main class
 *
 * @package Capusta/WC_Capusta
 */
defined('ABSPATH') || exit;

class WC_Capusta
{
	/**
	 * The single instance of the class
	 *
	 * @var WC_Capusta
	 */
	protected static $_instance = null;

	/**
	 * Logger
	 *
	 * @var WC_Capusta_Logger
	 */
	public $logger = false;

	/**
     * Api Capusta
     *
	 * @var WC_Capusta_Api
	 */
	public $capusta_api = false;

	/**
	 * WooCommerce version
	 *
	 * @var
	 */
	public $wc_version = '';

	/**
	 * WooCommerce currency
	 *
	 * @var string
	 */
	public $wc_currency = 'RUB';

	/**
     * Result url
     *
	 * @var string
	 */
	private $result_url = '';

	/**
     * Fail url
     *
	 * @var string
	 */
	private $fail_url = '';

	/**
     * Success url
     *
	 * @var string
	 */
	private $success_url = '';

	/**
	 * WC_Capusta constructor
	 */
	public function __construct()
	{
		// hook
		do_action('WC_Capusta_loading');

		$this->init_includes();

		$this->init_hooks();

		// hook
		do_action('WC_Capusta_loaded');
	}

	/**
	 * Main WC_Capusta instance
	 *
	 * @return WC_Capusta
	 */
	public static function instance()
	{
		if (is_null(self::$_instance))
		{
			self::$_instance = new self();
		}

		return self::$_instance;
	}
	
	/**
	 * Init required files
	 */
	public function init_includes()
	{
		/**
		 * @since 3.0.0
		 */
		do_action('WC_Capusta_before_includes');

		require_once WC_CAPUSTA_PLUGIN_DIR . 'includes/class-wc-capusta-method.php';

		/**
		 * @since 3.0.0
		 */
		do_action('WC_Capusta_after_includes');
	}

	/**
     * Get current currency
     *
	 * @return string
	 */
	public function get_wc_currency()
    {
        return $this->wc_currency;
    }

	/**
     * Set current currency
     *
	 * @param $wc_currency
	 */
    public function set_wc_currency($wc_currency)
    {
        $wc_currency = sanitize_text_field($wc_currency);
        $this->wc_currency = $wc_currency;
    }

	/**
     * Get current WooCommerce version installed
     *
	 * @return mixed
	 */
	public function get_wc_version()
    {
		return $this->wc_version;
	}

	/**
     * Set current WooCommerce version installed
     *
	 * @param mixed $wc_version
	 */
	public function set_wc_version($wc_version)
    {
        $wc_version = sanitize_text_field($wc_version);
		$this->wc_version = $wc_version;
	}

	/**
	 * Hooks (actions & filters)
	 */
	private function init_hooks()
	{
		add_action('init', array($this, 'init'), 0);

		if(is_admin())
		{
			add_action('init', array($this, 'admin_init'), 0);
			add_action('admin_notices', array($this, 'WC_Capusta_admin_notices'), 10);

			add_filter('plugin_action_links_' . WC_CAPUSTA_PLUGIN_NAME, array($this, 'links_left'), 10);
			add_filter('plugin_row_meta', array($this, 'links_right'), 10, 2);

			$this->page_explode();
		}
	}

	/**
	 * Init plugin gateway
	 *
	 * @return void
	 */
	public function WC_Capusta_gateway_init()
	{
		// hook
		do_action('WC_Capusta_gateway_init_before');

		if(class_exists('WC_Payment_Gateway') !== true)
		{
			$this->get_logger()->emergency('WC_Payment_Gateway not found');
		}

		add_filter('woocommerce_payment_gateways', array($this, 'add_wc_gateway_method'), 10);

		// hook
		do_action('WC_Capusta_gateway_init_after');
	}

	/**
	 * Initialization
	 */
	public function init()
	{
		if($this->load_logger() === false)
		{
			return false;
		}

		add_action('init', array($this, 'WC_Capusta_gateway_init'), 5);

		$this->load_plugin_text_domain();
		$this->load_wc_version();
		$this->load_currency();

		return true;
	}

	/**
	 * Admin initialization
	 */
	public function admin_init()
	{
		/**
		 * Load URLs for settings
		 */
		$this->load_urls();
	}

	/**
	 * Load WooCommerce current currency
	 */
	public function load_currency()
    {
	    $wc_currency = WC_Capusta_get_wc_currency();

	    /**
	     * WooCommerce Currency Switcher
	     */
	    if(class_exists('WOOCS'))
	    {
		    global $WOOCS;

		    // log
		    $this->get_logger()->alert('load_currency WooCommerce Currency Switcher detect');

		    $wc_currency = strtoupper($WOOCS->storage->get_val('woocs_current_currency'));
	    }

	    // log
	    $this->get_logger()->debug('load_currency $wc_version', $wc_currency);

	    $this->set_wc_currency($wc_currency);
    }

	/**
	 * Load current WC version
	 */
	public function load_wc_version()
    {
    	$wc_version = WC_Capusta_get_wc_version();

	    // log
	    $this->get_logger()->debug('load_wc_version $wc_version', $wc_version);

	    $this->set_wc_version($wc_version);
    }

	/**
	 * Load localisation files
	 */
	public function load_plugin_text_domain()
	{
		/**
		 * WP 5.x or later
		 */
		if(function_exists('determine_locale'))
		{
			$locale = determine_locale();
		}
		else
		{
			$locale = is_admin() && function_exists('get_user_locale') ? get_user_locale() : get_locale();
		}

		/**
		 * Change locale from external code
		 *
		 * @since 2.4.0
		 */
		$locale = apply_filters('plugin_locale', $locale, 'wc-capusta');

		// log
		$this->get_logger()->debug('load_plugin_text_domain $locale', $locale);

		/**
		 * Unload & load
		 */
		unload_textdomain('wc-capusta');
		load_textdomain('wc-capusta', WP_LANG_DIR . '/wc-capusta/wc-capusta-' . $locale . '.mo');
		load_textdomain('wc-capusta', WC_CAPUSTA_PLUGIN_DIR . 'languages/wc-capusta-' . $locale . '.mo');
	}

	/**
	 * Add the gateway to WooCommerce
	 *
	 * @param $methods - all WooCommerce initialized gateways
	 *
	 * @return array - new WooCommerce initialized gateways
	 */
	public function add_wc_gateway_method($methods)
	{
	    $default_class_name = 'WC_Capusta_Method';

		$capusta_method_class_name = apply_filters('WC_Capusta_method_class_name_add', $default_class_name);

		if(!class_exists($capusta_method_class_name))
		{
			$capusta_method_class_name = $default_class_name;
		}

		$methods[] = $capusta_method_class_name;

		return $methods;
	}

	/**
	 * Load logger
	 *
	 * @return boolean
	 */
	public function load_logger()
	{
		try
		{
			$logger = new WC_Capusta_Logger();
		}
		catch(Exception $e)
		{
			return false;
		}

		if(function_exists('wp_upload_dir'))
		{
			$wp_dir = wp_upload_dir();

			$logger->set_name('wc-capusta.boot.log');
			$logger->set_level(400);
			$logger->set_path($wp_dir['basedir']);

			$this->set_logger($logger);

			return true;
		}

		return false;
	}

	/**
	 * Set logger
	 *
	 * @param $logger
	 *
	 * @return $this
	 */
	public function set_logger($logger)
	{
		$this->logger = $logger;

		return $this;
	}

	/**
	 * Get logger
	 *
	 * @return WC_Capusta_Logger|null
	 */
	public function get_logger()
	{
		return $this->logger;
	}

	/**
	 * Setup left links
	 *
	 * @param $links
	 *
	 * @return array
	 */
	public function links_left($links)
	{
		return array_merge(array('settings' => '<a href="https://dev.capusta.space" target="_blank">' . __('Capusta Space Docs', 'wc-capusta') . '</a>'), $links);
	}

	/**
	 * Setup right links
	 *
	 * @param $links
	 * @param $file
	 *
	 * @return array
	 */
	public function links_right($links, $file)
	{
		if($file === WC_CAPUSTA_PLUGIN_NAME)
		{
			$links[] = '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=capusta') . '">' . __('Settings') . '</a>';
		}

		return $links;
	}

	/**
	 * Show admin notices
	 */
	public function WC_Capusta_admin_notices()
    {
    	$section = '';
    	if(isset($_GET['section']))
	    {
		    $section = sanitize_text_field($_GET['section']);
	    }

        $settings_version = get_option('WC_Capusta_last_settings_update_version');

        /**
         * Global notice: Require update settings
         */
        if(get_option('WC_Capusta_last_settings_update_version') !== false
           && $settings_version < WC_CAPUSTA_VERSION
           && $section !== 'capusta')
        {
        }
    }

	/**
	 *  Add page explode actions
	 */
	public function page_explode()
	{
		add_action('WC_Capusta_admin_options_form_before_show', array($this, 'page_explode_table_before'));
		add_action('WC_Capusta_admin_options_form_after_show', array($this, 'page_explode_table_after'));
		add_action('WC_Capusta_admin_options_form_right_column_show', array($this, 'admin_right_widget_one'));
		add_action('WC_Capusta_admin_options_form_right_column_show', array($this, 'admin_right_widget_two'));
	}

	/**
	 * Page explode before table
	 */
	public function page_explode_table_before()
	{
		echo '<div class="row"><div class="col-24 col-md-17">';
	}

	/**
	 * Load urls:
     * - result
     * - fail
     * - success
	 */
	public function load_urls()
    {
	    $this->set_result_url(get_site_url(null, '/?wc-api=WC_Capusta&action=result'));
	    $this->set_fail_url(get_site_url(null, '/?wc-api=WC_Capusta&action=fail&payment_id='));
	    $this->set_success_url(get_site_url(null, '/?wc-api=WC_Capusta&action=success&payment_id='));
    }

	/**
	 * Get result url
	 *
	 * @return string
	 */
	public function get_result_url()
    {
		return $this->result_url;
	}

	/**
	 * Set result url
	 *
	 * @param string $result_url
	 */
	public function set_result_url($result_url)
    {
		$this->result_url = $result_url;
	}

	/**
	 * Get fail url
	 *
	 * @return string
	 */
	public function get_fail_url()
    {
		return $this->fail_url;
	}

	/**
	 * Set fail url
	 *
	 * @param string $fail_url
	 */
	public function set_fail_url($fail_url)
    {
		$this->fail_url = $fail_url;
	}

	/**
	 * Get success url
	 *
	 * @return string
	 */
	public function get_success_url()
    {
		return $this->success_url;
	}

	/**
	 * Set success url
	 *
	 * @param string $success_url
	 */
	public function set_success_url($success_url)
    {
		$this->success_url = $success_url;
	}

	/**
	 * Page explode after table
	 */
	public function page_explode_table_after()
	{
		echo '</div><div class="col-24 d-none d-md-block col-md-6">';

		do_action('WC_Capusta_admin_options_form_right_column_show');

		echo '</div></div>';
	}

	/**
	 * Widget one
	 */
	public function admin_right_widget_one()
	{
		echo '<div class="card border-light" style="margin-top: 0;padding: 0;">
  <div class="card-header" style="padding: 10px;">
    <h5 style="margin: 0;padding: 0;">' . __('Useful information', 'wc-capusta') . '</h5>
  </div>
    <div class="card-body" style="padding: 0;">
      <ul class="list-group list-group-flush" style="margin: 0;">
    <li class="list-group-item"><a href="https://dev.capusta.space" target="_blank">' . __('Official documentation', 'wc-capusta') . '</a></li>
  </ul>
  </div>
</div>';
	}
}