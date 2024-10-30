<?php
/**
 * Main method class
 *
 * @package Capusta/WC_Capusta
 */
defined('ABSPATH') || exit;
require_once(WC_CAPUSTA_PLUGIN_DIR . 'vendor/autoload.php');

class Wc_Capusta_Method extends WC_Payment_Gateway
{

    /**
     * All support currency
     *
     * @var array
     */
    public $currency_all = array
    (
        'RUB',
        'UZS'
    );


    /**
     * MerchantEmail
     *
     * @var string
     */
    public $merchantEmail = '';

    /**
     * Merchant token
     *
     * @var string
     */
    public $token = '';


    /**
     * ProjectCode
     *
     * @var string
     */
    public $projectCode = '';

    /**
     * Unique gateway id
     *
     * @var string
     */
    public $id = 'capusta';

    /**
     * User language
     *
     * @var string
     */
    public $user_interface_language = 'ru';


    /**
     * WC_Capusta constructor
     */
    public function __construct()
    {
        /**
         * The gateway shows fields on the checkout OFF
         */
        $this->has_fields = false;

        /**
         * Admin title
         */
        $this->method_title = __('Capusta.Space', 'wc-capusta');

        /**
         * Admin method description
         */
        $this->method_description = __('Pay online', 'wc-capusta');

        /**
         * Logger
         */
        if($this->get_option('logger') !== '')
        {
            WC_Capusta()->get_logger()->set_level($this->get_option('logger'));

            $file_name = get_option('wc_capusta_log_file_name');
            if($file_name === false)
            {
                $file_name = 'wc-capusta.' . md5(mt_rand(1, 10) . 'Capusta' . mt_rand(1, 10)) . '.log';
                update_option('wc_capusta_log_file_name', $file_name, 'no');
            }

            WC_Capusta()->get_logger()->set_name($file_name);
        }

        /**
         * Initialize filters
         */
        $this->init_filters();

        /**
         * Load settings
         */
        $this->init_form_fields();
        $this->init_settings();
        $this->init_options();
        $this->init_actions();

        /**
         * Admin options
         */
        if(current_user_can('manage_options') && is_admin())
        {
            /**
             * Options save
             */
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(
                $this,
                'process_admin_options'
            ), 10);

            /**
             * Update last version
             */
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(
                $this,
                'wc_capusta_last_settings_update_version'
            ), 10);
        }

        /**
         * Receipt page
         */
        add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'), 10);

        /**
         * Payment listener/API hook
         */
        add_action('woocommerce_api_wc_' . $this->id, array($this, 'input_payment_notifications'), 10);
        //add_action( 'woocommerce_api_'.$this->id, array( $this, 'input_payment_notifications',10 ) );
    }

    /**
     * Initialize filters
     */
    public function init_filters()
    {
        add_filter('wc_capusta_init_form_fields', array($this, 'init_form_fields_main'), 10);
        add_filter('wc_capusta_init_form_fields', array($this, 'init_form_fields_interface'), 30);
        add_filter('wc_capusta_init_form_fields', array($this, 'init_form_fields_order_notes'), 45);
        add_filter('wc_capusta_init_form_fields', array($this, 'init_form_fields_technical'), 50);
    }

    /**
     * Init actions
     */
    public function init_actions()
    {
        /**
         * Payment fields description show
         */
        add_action('wc_capusta_payment_fields_show', array($this, 'payment_fields_description_show'), 10);


        /**
         * Receipt form show
         */
        add_action('wc_capusta_receipt_page_show', array($this, 'wc_capusta_receipt_page_show_form'), 10);
    }

    /**
     * Update plugin version at settings update
     */
    public function wc_capusta_last_settings_update_version()
    {
        update_option('wc_capusta_last_settings_update_version', WC_CAPUSTA_VERSION);
    }

    /**
     * Init gateway options
     */
    public function init_options()
    {
        /**
         * Gateway not enabled?
         */
        if($this->get_option('enabled') !== 'yes')
        {
            $this->enabled = false;
        }

        /**
         * Title for user interface
         */
        $this->title = $this->get_option('title');

        /**
         * Set description
         */
        $this->description = $this->get_option('description');


        /**
         * Default language for Capusta interface
         */
        $this->set_user_interface_language($this->get_option('language'));

        /**
         * Automatic language
         */
        if($this->get_option('language_auto') === 'yes')
        {
            $lang = get_locale();
            switch($lang)
            {
                case 'en_EN':
                    $this->set_user_interface_language('en');
                    break;
                default:
                    $this->set_user_interface_language('ru');
                    break;
            }
        }

        /**
         * Set order button text
         */
        $this->order_button_text = $this->get_option('order_button_text');

        /**
         * Set merchantEmail
         */
        if($this->get_option('merchantEmail') !== '')
        {
            $merchantEmail = sanitize_email($this->get_option('merchantEmail'));
            $this->set_merchantEmail($merchantEmail);
        }

        /**
         * Set token
         */
        if($this->get_option('token') !== '')
        {
            $token = sanitize_text_field($this->get_option('token'));
            $this->set_token($token);
        }

        /**
         * Set projectCode
         */
        if($this->get_option('projectCode') !== '')
        {
            $projectCode = sanitize_text_field($this->get_option('projectCode'));
            $this->set_projectCode($projectCode);
        }

        /**
         * Set icon
         */
        if($this->get_option('enable_icon') === 'yes')
        {
            $this->icon = apply_filters('woocommerce_icon_capusta', WC_CAPUSTA_URL . 'assets/img/capusta.png');
        }

        /**
         * Gateway allowed?
         */
        if($this->is_valid_for_use() === false)
        {
            $this->enabled = false;
        }
    }

    /**
     * Get merchantEmail
     *
     * @return string
     */
    public function get_merchantEmail()
    {
        return $this->merchantEmail;
    }

    /**
     * Set merchantEmail
     *
     * @param string $merchantEmail
     */
    public function set_merchantEmail($merchantEmail)
    {
        $merchantEmail = sanitize_email($merchantEmail);
        $this->merchantEmail = $merchantEmail;
    }

    /**
     * Get token
     *
     * @return string
     */
    public function get_token()
    {
        return $this->token;
    }

    /**
     * Set token
     *
     * @param string $token
     */
    public function set_token($token)
    {
        $token = sanitize_text_field($token);
        $this->token = $token;
    }

    /**
     * Get projectCode
     *
     * @return string
     */
    public function get_projectCode()
    {
        return $this->projectCode;
    }

    /**
     * Set projectCode
     *
     * @param string $projectCode
     */
    public function set_projectCode($projectCode)
    {
        $projectCode = sanitize_text_field($projectCode);
        $this->projectCode = $projectCode;
    }


    /**
     * Get user interface language
     *
     * @return string
     */
    public function get_user_interface_language()
    {
        return $this->user_interface_language;
    }

    /**
     * Set user interface language
     *
     * @param string $user_interface_language
     */
    public function set_user_interface_language($user_interface_language)
    {
        $user_interface_language = sanitize_text_field($user_interface_language);
        $this->user_interface_language = $user_interface_language;
    }

    /**
     * Initialise Gateway Settings Form Fields
     *
     * @return void
     */
    public function init_form_fields()
    {
        $this->form_fields = apply_filters('wc_capusta_init_form_fields', array());
    }

    /**
     * Add fields for main settings
     *
     * @param $fields
     *
     * @return array
     */
    public function init_form_fields_main($fields)
    {
        $fields['main'] = array
        (
            'title'       => __('Main settings', 'wc-capusta'),
            'type'        => 'title',
            'description' => __('The payment gateway will not work without these settings. Make the settings carefully.', 'wc-capusta'),
        );

        $fields['enabled'] = array
        (
            'title'       => __('Online / Offline', 'wc-capusta'),
            'type'        => 'checkbox',
            'label'       => __('Tick the checkbox if you need to activate the payment gateway.', 'wc-capusta'),
            'description' => __('On disconnection, the payment gateway will not be available for selection on the site. It is useful for payments through subsidiaries, or just in case of temporary disconnection.', 'wc-capusta'),
            'default'     => 'off'
        );

        $fields['merchantEmail'] = array
        (
            'title'       => __('Merchant Email', 'wc-capusta'),
            'type'        => 'text',
            'description' => __('Merchant Email (your login) from Capusta.', 'wc-capusta'),
            'default'     => ''
        );


        $fields['token'] = array
        (
            'title'       => __('API Token', 'wc-capusta'),
            'type'        => 'text',
            'description' => __('Please write API Token. The token is provided by Capusta Support.', 'wc-capusta'),
            'default'     => ''
        );

        $fields['projectCode'] = array
        (
            'title'       => __('Project Code', 'wc-capusta'),
            'type'        => 'text',
            'description' => __('Please write Code of your project. This code you can get from project details in Capusta personal account', 'wc-capusta'),
            'default'     => ''
        );

        $result_url_description = '<p class="input-text regular-input capusta_urls">' . WC_Capusta::instance()->get_result_url() . '</p>' . __('Address to notify the site of the results of operations in the background. Copy the address and enter it in your personal account Capusta in the technical settings.', 'wc-capusta');

        $fields['result_url'] = array
        (
            'title'       => __('Callback Url', 'wc-capusta'),
            'type'        => 'text',
            'disabled'    => true,
            'description' => $result_url_description,
            'default'     => ''
        );

        $success_url_description = '<p class="input-text regular-input capusta_urls">' . WC_Capusta::instance()->get_success_url() . '</p>' . __('The address for the user to go to the site after successful payment. Copy the address and enter it in your personal account Capusta in the technical settings.', 'wc-capusta');

        $fields['success_url'] = array
        (
            'title'       => __('Success Url', 'wc-capusta'),
            'type'        => 'text',
            'disabled'    => true,
            'description' => $success_url_description,
            'default'     => ''
        );

        $fail_url_description = '<p class="input-text regular-input capusta_urls">' . WC_Capusta::instance()->get_fail_url() . '</p>' . __('The address for the user to go to the site, after payment with an error. Copy the address and enter it in your personal account Capusta in the technical settings.', 'wc-capusta');

        $fields['fail_url'] = array
        (
            'title'       => __('Fail Url', 'wc-capusta'),
            'type'        => 'text',
            'disabled'    => true,
            'description' => $fail_url_description,
            'default'     => ''
        );

        return $fields;
    }


    /**
     * Add settings for interface
     *
     * @param $fields
     *
     * @return array
     */
    public function init_form_fields_interface($fields)
    {
        $fields['interface'] = array
        (
            'title'       => __('Interface', 'wc-capusta'),
            'type'        => 'title',
            'description' => __('Customize the appearance. Can leave it at that.', 'wc-capusta'),
        );

        $fields['enable_icon'] = array
        (
            'title'   => __('Show gateway icon?', 'wc-capusta'),
            'type'    => 'checkbox',
            'label'   => __('Show', 'wc-capusta'),
            'default' => 'yes'
        );

        $fields['language'] = array
        (
            'title'       => __('Language interface', 'wc-capusta'),
            'type'        => 'select',
            'options'     => array
            (
                'ru' => __('Russian', 'wc-capusta'),
                'en' => __('English', 'wc-capusta')
            ),
            'description' => __('What language interface displayed for the customer on Capusta?', 'wc-capusta'),
            'default'     => 'ru'
        );

        $fields['language_auto'] = array
        (
            'title'       => __('Language based on the locale?', 'wc-capusta'),
            'type'        => 'select',
            'options'     => array
            (
                'yes' => __('Yes', 'wc-capusta'),
                'no'  => __('No', 'wc-capusta')
            ),
            'description' => __('Automatic detection of the users language from the WordPress environment.', 'wc-capusta'),
            'default'     => 'no'
        );

        $fields['title'] = array
        (
            'title'       => __('Title', 'wc-capusta'),
            'type'        => 'text',
            'description' => __('This is the name that the user sees during the payment.', 'wc-capusta'),
            'default'     => __('Capusta.Space', 'wc-capusta')
        );

        $fields['order_button_text'] = array
        (
            'title'       => __('Order button text', 'wc-capusta'),
            'type'        => 'text',
            'description' => __('This is the button text that the user sees during the payment.', 'wc-capusta'),
            'default'     => __('Proceed to checkout', 'wc-capusta')
        );

        $fields['description'] = array
        (
            'title'       => __('Description', 'wc-capusta'),
            'type'        => 'textarea',
            'description' => __('Description of the method of payment that the customer will see on our website.', 'wc-capusta'),
            'default'     => __('Visa, Mastercard, Maestro, MIR, as well as Apple Pay and Google Pay through the payment service Capusta.Space', 'wc-capusta')
        );

        return $fields;
    }


    /**
     * Add settings for order notes
     *
     * @param $fields
     *
     * @return array
     */
    public function init_form_fields_order_notes($fields)
    {
        $fields['orders_notes'] = array
        (
            'title'       => __('Orders notes', 'wc-capusta'),
            'type'        => 'title',
            'description' => __('Settings for adding notes to orders. All are off by default.', 'wc-capusta'),
        );

        $fields['orders_notes_capusta_request'] = array
        (
            'title'       => __('Request from Capusta', 'wc-capusta'),
            'type'        => 'checkbox',
            'label'       => __('Enable', 'wc-capusta'),
            'description' => __('All requests from Capusta for orders will be added to the notes.', 'wc-capusta'),
            'default'     => 'no'
        );

        $fields['orders_notes_capusta_request_validate_error'] = array
        (
            'title'       => __('Validation errors of requests', 'wc-capusta'),
            'type'        => 'checkbox',
            'label'       => __('Enable', 'wc-capusta'),
            'description' => __('Adding to the notes all the data related to the check for error requests.', 'wc-capusta'),
            'default'     => 'no'
        );

        $fields['orders_notes_capusta_request_result'] = array
        (
            'title'       => __('Result requests', 'wc-capusta'),
            'type'        => 'checkbox',
            'label'       => __('Enable', 'wc-capusta'),
            'description' => __('Adding payment result data to order notes.', 'wc-capusta'),
            'default'     => 'no'
        );

        $fields['orders_notes_capusta_request_fail'] = array
        (
            'title'       => __('Failed requests', 'wc-capusta'),
            'type'        => 'checkbox',
            'label'       => __('Enable', 'wc-capusta'),
            'description' => __('Adding customers return data to the cancellation page in the notes.', 'wc-capusta'),
            'default'     => 'no'
        );

        $fields['orders_notes_capusta_request_success'] = array
        (
            'title'       => __('Success requests', 'wc-capusta'),
            'type'        => 'checkbox',
            'label'       => __('Enable', 'wc-capusta'),
            'description' => __('Adding customers return data to the successful payment page in the notes.', 'wc-capusta'),
            'default'     => 'no'
        );

        return $fields;
    }

    /**
     * Add settings for technical
     *
     * @param $fields
     *
     * @return array
     */
    public function init_form_fields_technical($fields)
    {
        $fields['technical'] = array
        (
            'title'       => __('Technical details', 'wc-capusta'),
            'type'        => 'title',
            'description' => __('Setting technical parameters. Used by technical specialists. Can leave it at that.', 'wc-capusta'),
        );

        $fields['logger'] = array
        (
            'title'       => __('Logging', 'wc-capusta'),
            'type'        => 'select',
            'description' => __('You can enable gateway logging, specify the level of error that you want to benefit from logging. All sensitive data in the report are deleted. By default, the error rate should not be less than ERROR.', 'wc-capusta'),
            'default'     => '400',
            'options'     => array
            (
                ''    => __('Off', 'wc-capusta'),
                '100' => 'DEBUG',
                '200' => 'INFO',
                '250' => 'NOTICE',
                '300' => 'WARNING',
                '400' => 'ERROR',
                '500' => 'CRITICAL',
                '550' => 'ALERT',
                '600' => 'EMERGENCY'
            )
        );

        $fields['cart_clearing'] = array
        (
            'title'       => __('Cart clearing', 'wc-capusta'),
            'type'        => 'select',
            'description' => __('Clean the customers cart if payment is successful? If so, the shopping cart will be cleaned. If not, the goods already purchased will most likely remain in the shopping cart.', 'wc-capusta'),
            'default'     => 'no',
            'options'     => array
            (
                'yes'    => __('Yes', 'wc-capusta'),
                'no' => __('No', 'wc-capusta'),
            )
        );

        $fields['fail_set_order_status_failed'] = array
        (
            'title'       => __('Mark order as cancelled?', 'wc-capusta'),
            'type'        => 'select',
            'description' => __('Change the status of the order to canceled when the user cancels the payment. The status changes when the user returns to the cancelled payment page.', 'wc-capusta'),
            'default'     => 'no',
            'options'     => array
            (
                'yes'    => __('Yes', 'wc-capusta'),
                'no' => __('No', 'wc-capusta'),
            )
        );

        return $fields;
    }

    /**
     * Check if this gateway is enabled and available in the user's country
     */
    public function is_valid_for_use()
    {
        /**
         * Check allow currency
         */
        if(!in_array(WC_Capusta()->get_wc_currency(), $this->currency_all, false))
        {
            WC_Capusta()->get_logger()->alert('is_valid_for_use: currency not supported');
            return false;
        }

        return true;
    }

    /**
     * Output settings screen
     */
    public function admin_options()
    {
        wp_enqueue_style('capusta-admin-styles', WC_CAPUSTA_URL . 'assets/css/main.css');

        // hook
        do_action('wc_capusta_admin_options_before_show');

        echo '<h2>' . esc_html($this->get_method_title());
        wc_back_link(__('Return to payment gateways', 'wc-capusta'), admin_url('admin.php?page=wc-settings&tab=checkout'));
        echo '</h2>';

        // hook
        do_action('wc_capusta_admin_options_method_description_before_show');

        echo wp_kses_post(wpautop($this->get_method_description()));

        // hook
        do_action('wc_capusta_admin_options_method_description_after_show');

        // hook
        do_action('wc_capusta_admin_options_form_before_show');

        echo '<table class="form-table">' . $this->generate_settings_html($this->get_form_fields(), false) . '</table>';

        // hook
        do_action('wc_capusta_admin_options_form_after_show');

        // hook
        do_action('wc_capusta_admin_options_after_show');
    }

    /**
     * There are no payment fields for capusta, but we want to show the description if set
     */
    public function payment_fields()
    {
        // hook
        do_action('wc_capusta_payment_fields_before_show');

        // hook
        do_action('wc_capusta_payment_fields_show');

        // hook
        do_action('wc_capusta_payment_fields_after_show');
    }

    /**
     * Show description on site
     */
    public function payment_fields_description_show()
    {
        if($this->description)
        {
            echo wpautop(wptexturize($this->description));
        }
    }


    /**
     * Process the payment and return the result
     *
     * @param int $order_id
     *
     * @return array
     */
    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);

        if($order === false)
        {
            WC_Capusta()->get_logger()->error('process_payment: $order === false');

            return array
            (
                'result' => 'failure',
                'redirect' => ''
            );
        }

        // hook
        do_action('wc_capusta_before_process_payment', $order_id, $order);

        WC_Capusta()->get_logger()->debug('process_payment: order', $order);

        if(method_exists($order, 'add_order_note'))
        {
            $order->add_order_note(__('The client started to pay.', 'wc-capusta'));
        }

        WC_Capusta()->get_logger()->info('process_payment: success');

        return array
        (
            'result' => 'success',
            'redirect' => $order->get_checkout_payment_url(true)
        );
    }

    /**
     * Receipt page
     *
     * @param $order
     *
     * @return void
     */
    public function receipt_page($order)
    {
        // hook
        do_action('wc_capusta_receipt_page_before_show', $order);

        // hook
        do_action('wc_capusta_receipt_page_show', $order);

        // hook
        do_action('wc_capusta_receipt_page_after_show', $order);
    }

    /**
     * @param $order
     *
     * @return void
     */
    public function wc_capusta_receipt_page_show_form($order)
    {
        echo $this->generate_form($order);
    }

    /**
     * Generate payments form
     *
     * @param $order_id
     *
     * @return string - payment form
     **/
    public function generate_form($order_id)
    {
        $order = wc_get_order($order_id);
        WC_Capusta()->get_logger()->debug('generate_form: $order', $order);



        $args = array();
        $args['merchantEmail'] = $this->get_merchantEmail();

        $out_sum = number_format($order->get_total(), 2, '.', '');
        $out_sum_capusta = (int)$order->get_total()*100;

        /**
         * Rewrite currency from order
         */
        if(WC_Capusta()->get_wc_currency() !== $order->get_currency('view'))
        {
            WC_Capusta()->get_logger()->info('generate_form: rewrite currency');
            WC_Capusta::instance()->set_wc_currency($order->get_currency());
        }

        //calling capusta to get bill id.
        WC_Capusta()->get_logger()->debug('generate_form: calling capusta');
        $guzzleClient = new GuzzleHttp\Client();
        $transport = new Capusta\SDK\Transport\GuzzleApiTransport($guzzleClient);
        $client = new Capusta\SDK\Client($transport);
        $client->setAuth($this->get_merchantEmail(), $this->get_token());
        $uniqueId = $order_id.'-'.$order->get_cart_hash();
        $requestArray = [
            'id' => $uniqueId, // your ID of transaction, optional.
            'amount' => [
                'currency' => $order->get_currency('view'),
                'amount' => $out_sum_capusta
            ], //array of 'amount' in minor value and 'currency'.
            'projectCode' => $this->get_option('projectCode'), //required, code can be taken from my.capusta.space
        ];

        try {
            /** @var Capusta\SDK\Model\Response\Payment\CreatePaymentResponse $createPaymentResponse */
            $createPaymentResponse = $client->createPayment($requestArray);
        } catch (\Exception $e) {
            // ...
            WC_Capusta()->get_logger()->error('Create Payment exception: $e', $e);
        }
        if (isset($createPaymentResponse)) {
            if ($createPaymentResponse->getStatus() == 'CREATED') {
                //setting payUrl to transactionId
                $payUrl = sanitize_text_field($createPaymentResponse->getPayUrl());
                $order->set_transaction_id($payUrl);
                $order->save();
            }
            WC_Capusta()->get_logger()->debug('Response data: $createPaymentResponse', $createPaymentResponse);
        } else {
            $payUrl = $order->get_transaction_id();
        }

        /**
         * Encoding
         */
        $args['Encoding'] = 'utf-8';

        /**
         * Execute filter wc_capusta_form_args
         */
        $args = apply_filters('wc_capusta_payment_form_args', $args);

        /**
         * Form inputs generic
         */

        if (isset($payUrl) && $payUrl) {

            // pay button
            $return = '<form action="' . esc_url($payUrl) . '" method="GET" id="wc_capusta_payment_form" accept-charset="utf-8">' . "\n" .
                '<input type="submit" class="button alt" id="submit_wc_capusta_payment_form" value="' . __('Pay online', 'wc-capusta') .
                '" /> <a class="button cancel" href="' . $order->get_cancel_order_url() . '">' . __('Cancel & return to cart', 'wc-capusta') . '</a>' . "\n" .
                '</form>';
        } else {
            $return = '<form action="" method="GET" id="wc_capusta_payment_form" accept-charset="utf-8">' . "\n" .
                '<a class="button cancel" href="' . $order->get_cancel_order_url() . '">' . __('Sorry, this order can not be paid', 'wc-capusta') . '</a>' . "\n" .
                '</form>';
        }
        return $return;
    }


    /**
     * Generate receipt
     *
     * @param WC_Order $order
     *
     * @return array
     */
    public function generate_receipt_items($order)
    {
        $receipt_items = array();

        WC_Capusta()->get_logger()->info('generate_receipt_items: start');

        /**
         * Order items
         */
        foreach($order->get_items() as $receipt_items_key => $receipt_items_value)
        {
            /**
             * Quantity
             */
            $item_quantity = $receipt_items_value->get_quantity();

            /**
             * Total item sum
             */
            $item_total = $receipt_items_value->get_total();

            /**
             * Build positions
             */
            $receipt_items[] = array
            (
                /**
                 * Название товара
                 *
                 * максимальная длина 128 символов
                 */
                'name' => $receipt_items_value['name'],

                /**
                 * Стоимость предмета расчета с учетом скидок и наценок
                 *
                 */
                'sum' => intval($item_total),

                /**
                 * Количество/вес
                 *
                 * максимальная длина 128 символов
                 */
                'quantity' => intval($item_quantity),

            );
        }

        /**
         * Delivery
         */
        if($order->get_shipping_total() > 0)
        {
            /**
             * Build positions
             */
            $receipt_items[] = array
            (
                /**
                 * Название товара
                 *
                 * максимальная длина 128 символов
                 */
                'name' => __('Delivery', 'wc-capusta'),

                /**
                 * Стоимость предмета расчета с учетом скидок и наценок
                 *
                 * Цена в рублях:
                 *  целая часть не более 8 знаков;
                 *  дробная часть не более 2 знаков.
                 */
                'sum' => intval($order->get_shipping_total()),

                /**
                 * Количество/вес
                 *
                 * максимальная длина 128 символов
                 */
                'quantity' => 1,


            );
        }

        WC_Capusta()->get_logger()->info('generate_receipt_items: success');

        return $receipt_items;
    }





    /**
     * Check instant payment notification
     *
     * @return void
     */
    public function input_payment_notifications()
    {
        do_action('wc_capusta_input_payment_notifications');
        $pluginName = filter_input( INPUT_GET, 'wc-api', FILTER_SANITIZE_STRING );
        if (!preg_match('/(Capusta)/', $pluginName, $output_array)) {
            WC_Capusta()->get_logger()->error('wc-api waiting : WC_Capusta');
            WC_Capusta()->get_logger()->error('wc-api received :', $pluginName);
            wp_die(__('Api request error. invalid wc-api.', 'wc-capusta'), 'Payment error', array('response' => '503'));
        }
        $action = filter_input( INPUT_GET, 'action', FILTER_SANITIZE_STRING );
        WC_Capusta()->get_logger()->debug('CALLBACK GET :', json_encode($_GET));
        $rawheaders = getallheaders();
        WC_Capusta()->get_logger()->error('headers received :', json_encode($rawheaders));

        if($action === 'result') {
            $rawdata = file_get_contents("php://input");

            $bearer = 'Bearer '.$this->get_merchantEmail().':'.$this->get_token();


            $requestData = json_decode($rawdata, true);
            if (!isset($requestData) || !is_array($requestData) || !isset($requestData['transactionId'])) {
                die('incorrect data received');
            }
            // check signature existense.
            if (isset($requestData['signature']) && strlen($requestData['signature'])) {
                if (!Capusta\SDK\Signature::check($requestData,$this->get_merchantEmail(), $this->get_token() )) {
                    WC_Capusta()->get_logger()->error('Signature error');
                    wp_die(__('Api request error. Signature error.', 'wc-capusta'), 'Payment error', array('response' => '503'));
                }
            } else {
                if (! isset($rawheaders['Authorization'])) {
                    WC_Capusta()->get_logger()->error('No auth headers found');
                    wp_die(__('Api request error. invalid Authorization.', 'wc-capusta'), 'Payment error', array('response' => '503'));
                }
                if ($rawheaders['Authorization'] !== $bearer) {
                    WC_Capusta()->get_logger()->error('bearer received :', $rawheaders['Authorization']);
                    WC_Capusta()->get_logger()->error('bearer waiting :', $bearer);
                    wp_die(__('Api request error. invalid bearer.', 'wc-capusta'), 'Payment error', array('response' => '503'));
                }
            }
            $callback_transactionId = sanitize_text_field($requestData['transactionId']);
            $callback_status = sanitize_text_field($requestData['status']);
            WC_Capusta()->get_logger()->debug('INPUT CALLBACK SEARCH : transaction_id', $callback_transactionId);
            $order_id = 0;
            $orders = wc_get_orders( array( 'transaction_id' => $callback_transactionId ) );
            WC_Capusta()->get_logger()->debug('INPUT CALLBACK ORDERS: $orders', $orders);
            $order = $this->getOrderByTransactionId($callback_transactionId);
            /**
             * Add order note
             */
            if(method_exists($order, 'add_order_note')
                && $this->get_option('orders_notes_capusta_request_result') === 'yes'
                && $callback_status === "SUCCESS"
            )
            {
                $order->add_order_note(__('Order successfully paid.', 'wc-capusta'));
            }

            /**
             * Set status is payment
             */
            if ($callback_status === "SUCCESS") {
                $order->payment_complete();
                wc_reduce_stock_levels($order->get_id());
            }
            die('OK'.$order_id);
        }

        if($action === 'success') {
            $transaction_id = filter_input( INPUT_GET, 'payment_id', FILTER_SANITIZE_STRING );
            WC_Capusta()->get_logger()->debug('RECEIVED SUCCESS transaction_id:', $transaction_id);
            $order = $this->getOrderByTransactionId($transaction_id);
            if(method_exists($order, 'add_order_note') && $this->get_option('orders_notes_capusta_request_success') === 'yes')
            {
                $order->add_order_note(__('Client return to success page.', 'wc-capusta'));
            }

            /**
             * Empty cart
             */
            if($this->get_option('cart_clearing') === 'yes')
            {
                WC()->cart->empty_cart();
            }

            /**
             * Redirect to success
             */
            wp_redirect($this->get_return_url($order));
            die();
        }

        if($action === 'fail') {
            $transaction_id = filter_input( INPUT_GET, 'payment_id', FILTER_SANITIZE_STRING );

            $order = $this->getOrderByTransactionId($transaction_id);
            /**
             * Add order note
             */
            if(method_exists($order, 'add_order_note') && $this->get_option('orders_notes_capusta_request_fail') === 'yes')
            {
                $order->add_order_note(__('The order has not been paid.', 'wc-capusta'));
            }

            /**
             * Set status is failed
             */
            if($this->get_option('fail_set_order_status_failed') === 'yes')
            {
                $order->update_status('failed');
            }

            /**
             * Redirect to cancel
             */
            wp_redirect( str_replace('&amp;', '&', $order->get_cancel_order_url() ) );
            die();
        }
        wp_die(__('Api request error. Action not found.', 'wc-capusta'), 'Payment error', array('response' => '503'));
    }

    /**
     * Check if the gateway is available for use
     *
     * @return bool
     */
    public function is_available()
    {
        $is_available = parent::is_available();

        /**
         * Change status from external code
         */
        $is_available = apply_filters('wc_capusta_main_method_get_available', $is_available);

        return $is_available;
    }

    /**
     * @param $transaction_id string
     * @return WC_Order
     * @throws Exception
     */
    private function getOrderByTransactionId($transaction_id) {
        $args = array(
            'limit'           => 1,
            '_transaction_id' => $transaction_id
        );
        $query = new WC_Order_Query( $args );
        $orders = $query->get_orders();
        if (isset($orders[0])) {
            /**
             * @var $order WC_Order
             */
            $order = $orders[0];
        }
        if (is_object($order)){
            WC_Capusta()->get_logger()->error('ORDER FOUND: ',$order->get_id());
            return $order;
        }
        WC_Capusta()->get_logger()->error('input_payment_notifications: order not found');
        wp_die(__('Order not found.', 'wc-capusta'), 'Payment error', array('response' => '503'));
    }
}