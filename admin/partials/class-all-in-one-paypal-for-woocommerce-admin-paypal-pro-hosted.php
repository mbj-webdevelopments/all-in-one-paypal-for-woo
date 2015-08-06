<?php

/**
 * @class       All_In_One_Paypal_For_Woocommerce_Admin_WooCommerce_Pro_hosted
 * @version	1.0.0
 * @package	all-in-one-paypal-for-woocommerce
 * @category	Class
 * @author      mbj-webdevelopment <mbjwebdevelopment@gmail.com>
 */
class All_In_One_Paypal_For_Woocommerce_Admin_WooCommerce_Pro_Hosted extends WC_Payment_Gateway {

    /**
     * @since    1.0.0
     */
    public function __construct() {
        $this->plug_version = '1.0.1';
        $this->id = 'paypal_pro_hosted';
        $this->icon = apply_filters('woocommerce_paypal_advanced_icon', '');
        $this->has_fields = true;
        $this->home_url = is_ssl() ? home_url('/', 'https') : home_url('/');
        $this->testurl_button = 'https://api-3t.sandbox.paypal.com/nvp';
        $this->liveurl_button = 'https://api-3t.paypal.com/nvp';
        $this->testurl = 'https://securepayments.sandbox.paypal.com/webapps/HostedSoleSolutionApp/webflow/sparta/hostedSoleSolutionProcess';
        $this->liveurl = 'https://securepayments.paypal.com/webapps/HostedSoleSolutionApp/webflow/sparta/hostedSoleSolutionProcess';
        $this->method_title = __('PayPal Pro Hosted', 'paypal-advanced-gateway-for-woocommerce');
        $this->secure_token_id = '';
        $this->securetoken = '';
        $this->supports = array(
            'products',
            'refunds'
        );
        $this->init_form_fields();
        $this->init_settings();
        $this->title = $this->settings['title'];
        $this->description = $this->settings['description'];
        $this->testmode = $this->settings['testmode'];
        $this->email = $this->get_option('email');
        $this->api_username = $this->get_option('api_username');
        $this->api_password = $this->get_option('api_password');
        $this->api_signature = $this->get_option('api_signature');
        $this->invoice_prefix = $this->get_option('invoice_prefix');
        $this->debug = $this->settings['debug'];
        $this->hostaddr = $this->testmode == 'yes' ? $this->testurl_button : $this->liveurl_button;
        $this->paypal_url = $this->testmode == 'yes' ? $this->testurl : $this->liveurl;
        $this->api_version = '112';
        if ($this->debug == 'yes') {
            $this->log = new WC_Logger();
        }

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
        add_action('woocommerce_api_all_in_one_paypal_for_woocommerce_admin_woocommerce_pro_hosted', array($this, 'relay_response'));
        if (!$this->is_available()) {
            $this->enabled = false;
        }
    }

    /**
     * @since    1.0.0
     */
    public function redirect_to($redirect_url) {
        @ob_clean();
        header('HTTP/1.1 200 OK');
        wp_redirect($redirect_url);
        exit;
    }

    /**
     * @since    1.0.0
     */
    public function relay_response() {
        $result = false;
        if (isset($_REQUEST['tx']) && !empty($_REQUEST['tx'])) {
            $result = $this->get_transaction_details($_REQUEST['tx']);
            $transaction_id = $_REQUEST['tx'];
        } elseif (isset($_REQUEST['txn_id']) && !empty($_REQUEST['txn_id'])) {
            $result = $this->get_transaction_details($_REQUEST['txn_id']);
            $transaction_id = $_REQUEST['txn_id'];
        }
        if (!is_array($result) || !isset($result['ACK']) || (($result['ACK'] != 'Success') && ($result['ACK'] != 'SuccessWithWarning'))) {
            wc_clear_notices();
            wc_add_notice(__('Error:', 'paypal-advanced-gateway-for-woocommerce') . ' "' . urldecode($result['L_LONGMESSAGE0']) . '"', 'error');
            $this->redirect_to($order->get_checkout_payment_url(true));
        }
        $Order_object = json_decode($result['CUSTOM']);
        $order = new WC_Order($Order_object->order_id);
        $status = isset($order->status) ? $order->status : $order->get_status();
        if ($status == 'processing' || $status == 'completed') {
            $this->redirect_to($this->get_return_url($order));
        }
        $order->add_order_note(sprintf(__('Received result of Inquiry Transaction for the  (Order: %s) and is successful', 'paypal-advanced-gateway-for-woocommerce'), $order->get_order_number()));
        $order->payment_complete($transaction_id);
        WC()->cart->empty_cart();
        $this->redirect_to($this->get_return_url($order));
    }

    /**
     * @since    1.0.0
     */
    public function is_available() {
        if ($this->enabled == 'yes') {
            if (!$this->api_username) {
                return false;
            } elseif (!$this->api_password) {
                return false;
            } elseif (!$this->api_signature) {
                return false;
            } elseif (!$this->email) {
                return false;
            }
            return true;
        }
        return false;
    }

    /**
     * @since    1.0.0
     */
    public function admin_options() {
        ?>
        <h3><?php _e('PayPal Pro Hosted', 'paypal-advanced-gateway-for-woocommerce'); ?></h3>
        <table class="form-table">
            <?php
            if (!in_array(get_woocommerce_currency(), array('AUD', 'CAD', 'CHF', 'CZK', 'DKK', 'EUR', 'GBP', 'HKD', 'HUF', 'JPY', 'NOK', 'NZD', 'PLN', 'SEK', 'SGD', 'USD'))) {
                ?>
                <div class="inline error"><p><strong><?php _e('Gateway Disabled', 'paypal-advanced-gateway-for-woocommerce'); ?></strong>: <?php _e('PayPal does not support your store currency.', 'paypal-advanced-gateway-for-woocommerce'); ?></p></div>
                <?php
                return;
            } else {
                $this->generate_settings_html();
            }
            ?>
        </table>
        <?php
    }

    /**
     * @since    1.0.0
     */
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'paypal-advanced-gateway-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable PayPal Advanced', 'paypal-advanced-gateway-for-woocommerce'),
                'default' => 'yes'
            ),
            'title' => array(
                'title' => __('Title', 'paypal-advanced-gateway-for-woocommerce'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'paypal-advanced-gateway-for-woocommerce'),
                'default' => __('PayPal Pro Hosted', 'paypal-advanced-gateway-for-woocommerce')
            ),
            'description' => array(
                'title' => __('Description', 'paypal-advanced-gateway-for-woocommerce'),
                'type' => 'text',
                'description' => __('This controls the description which the user sees during checkout.', 'paypal-advanced-gateway-for-woocommerce'),
                'default' => __('PayPal Advanced description', 'paypal-advanced-gateway-for-woocommerce')
            ),
            'email' => array(
                'title' => __('PayPal Email', 'woocommerce'),
                'type' => 'email',
                'description' => __('Please enter your PayPal email address; this is needed in order to take payment.', 'woocommerce'),
                'default' => '',
                'desc_tip' => true,
                'placeholder' => 'you@youremail.com'
            ),
            'api_details' => array(
                'title' => __('API Credentials', 'woocommerce'),
                'type' => 'title',
                'description' => ''
            ),
            'api_username' => array(
                'title' => __('API Username', 'woocommerce'),
                'type' => 'text',
                'description' => __('Get your API credentials from PayPal.', 'woocommerce'),
                'default' => '',
                'desc_tip' => true,
                'placeholder' => __('', 'woocommerce')
            ),
            'api_password' => array(
                'title' => __('API Password', 'woocommerce'),
                'type' => 'text',
                'description' => __('Get your API credentials from PayPal.', 'woocommerce'),
                'default' => '',
                'desc_tip' => true,
                'placeholder' => __('', 'woocommerce')
            ),
            'api_signature' => array(
                'title' => __('API Signature', 'woocommerce'),
                'type' => 'text',
                'description' => __('Get your API credentials from PayPal.', 'woocommerce'),
                'default' => '',
                'desc_tip' => true,
                'placeholder' => __('', 'woocommerce')
            ),
            'invoice_prefix' => array(
                'title' => __('Invoice Prefix', 'woocommerce'),
                'type' => 'text',
                'description' => __('Please enter a prefix for your invoice numbers. If you use your PayPal account for multiple stores ensure this prefix is unique as PayPal will not allow orders with the same invoice number.', 'woocommerce'),
                'default' => 'WC-',
                'desc_tip' => true,
            ),
            'testmode' => array(
                'title' => __('Test Mode', 'woocommerce'),
                'label' => __('Enable PayPal Sandbox/Test Mode', 'woocommerce'),
                'type' => 'checkbox',
                'description' => __('Place the payment gateway in development mode.', 'woocommerce'),
                'default' => 'no',
                'desc_tip' => true
            ),
            'debug' => array(
                'title' => __('Debug Log', 'paypal-advanced-gateway-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable logging', 'paypal-advanced-gateway-for-woocommerce'),
                'default' => 'no',
                'description' => __('Log PayPal events, such as Secured Token requests, inside <code>wp-content/uploads/wc-logs/paypal-pro-hosted.txt</code>', 'paypal-advanced-gateway-for-woocommerce'),
            )
        );
    }

    /**
     * @since    1.0.0
     */
    public function payment_fields() {
        if ($this->description) {
            echo wpautop(wptexturize($this->description));
        }
    }

    /**
     * @since    1.0.0
     */
    public function process_payment($order_id) {
        $order = new WC_Order($order_id);
        $this->log('Plugin version #' . $this->plug_version);
        $this->log('start payment process for order #' . $order_id);
        $params = array('business' => $this->email,
            'bn' => 'mbjtechnolabs_SP',
            'buyer_email' => $order->billing_email,
            'cancel_return' => str_replace("&amp;", "&", $order->get_cancel_order_url_raw()),
            'currency_code' => get_woocommerce_currency(),
            'invoice' => $this->invoice_prefix . ltrim($order->get_order_number(), '#'),
            'custom' => json_encode(array('order_id' => $order->id, 'order_key' => $order->order_key)),
            'paymentaction' => 'sale',
            'return' => add_query_arg('wc-api', 'All_In_One_Paypal_For_Woocommerce_Admin_WooCommerce_Pro_Hosted', home_url('/')),
            'notify_url' => '',
            'shipping' => number_format($order->get_total_shipping(), 2, '.', ''),
            'tax' => number_format($order->get_total_tax(), 2, '.', ''),
            'subtotal' => $order->get_subtotal(),
            'billing_first_name' => $order->billing_first_name,
            'billing_last_name' => $order->billing_last_name,
            'billing_address1' => $order->billing_address_1,
            'billing_city' => $order->billing_city,
            'billing_state' => $order->billing_state,
            'billing_zip' => $order->shipping_postcode,
            'billing_country' => $order->shipping_country,
            'night_phone_b' => $order->billing_phone,
            'template' => 'templateD',
            'item_name' => 'MBJ',
            'showBillingAddress' => 'false',
            'showShippingAddress' => 'false',
            'showHostedThankyouPage' => 'false',
            'bodyBgColor' => '#AEAEAE',
            'PageButtonBgColor' => 'Blue');
        $params['address_override'] = 'true';
        $params['first_name'] = $order->shipping_first_name;
        $params['last_name'] = $order->shipping_last_name;
        $params['address1'] = $order->shipping_address_1;
        $params['city'] = $order->shipping_city;
        $params['state'] = $order->shipping_postcode;
        $params['zip'] = $order->shipping_postcode;
        $params['country'] = $order->shipping_country;
        $counter = 0;
        $params_string = 'USER=' . urlencode(utf8_encode(trim($this->api_username))) . '&PWD=' . urlencode(utf8_encode(trim($this->api_password))) . '&SIGNATURE=' . urlencode(utf8_encode(trim($this->api_signature))) . '&VERSION=' . $this->api_version . '&METHOD=BMCreateButton&BUTTONCODE=TOKEN&BUTTONTYPE=PAYMENT&';
        foreach ($params as $key => $value) {
            $params_string .= 'L_BUTTONVAR' . $counter . '=' . $key . '=' . urlencode(utf8_encode(trim($value))) . '&';
            $counter++;
        }
        $params_string = substr($params_string, 0, -1);
        $this->log('before send request to BMCreateButton');
        $response = $this->sendTransactionToGateway($this->hostaddr, $params_string);
        $this->log('BMCreateButton Response ' . print_r($response['body'], true));
        parse_str($response['body'], $parsed_response);

        try {
            if (isset($parsed_response['ACK']) && ($parsed_response['ACK'] == 'Success' || $parsed_response['ACK'] == 'SuccessWithWarning')) {
                if (isset($parsed_response['EMAILLINK']) && !empty($parsed_response['EMAILLINK'])) {
                    update_post_meta($order->id, '_secure_response', $parsed_response['EMAILLINK']);
                    return array(
                        'result' => 'success',
                        'redirect' => $order->get_checkout_payment_url(true)
                    );
                } else {
                    $this->log('BMCreateButton Response EMAILLINK value is emptry.');
                    wc_add_notice('BMCreateButton Response EMAILLINK value is emptry.', 'error');
                }
            } else {
                if (!empty($parsed_response['L_LONGMESSAGE0'])) {
                    $error_message = $parsed_response['L_LONGMESSAGE0'];
                } elseif (!empty($parsed_response['L_SHORTMESSAGE0'])) {
                    $error_message = $parsed_response['L_SHORTMESSAGE0'];
                } elseif (!empty($parsed_response['L_SEVERITYCODE0'])) {
                    $error_message = $parsed_response['L_SEVERITYCODE0'];
                }
                $this->log('BMCreateButton Error Response ' . print_r($error_message, true));
                wc_add_notice($error_message, 'error');
            }
        } catch (Exception $e) {
            wc_add_notice($e->getMessage(), 'error');
            return false;
        }
        return;
    }

    /**
     * @since    1.0.0
     */
    public function receipt_page($order_id) {
        $order = new WC_Order($order_id);
        $this->log('receipt page called');
        $location = get_post_meta($order->id, '_secure_response', true);
        ?>
        <iframe id="paypal-pro-hosted_iframe" src="<?php echo $location; ?>" width="570" height="540" scrolling="no" frameborder="0" border="0" allowtransparency="true"></iframe>
        <?php
    }

    /**
     * @since    1.0.0
     */
    public function process_refund($order_id, $amount = null, $reason = '') {
        $order = wc_get_order($order_id);
        if (!$order || !$order->get_transaction_id() || !$this->api_username || !$this->api_password || !$this->api_signature) {
            return false;
        }
        $details = $this->get_transaction_details($order->get_transaction_id());
        if ($details && strtolower($details['PENDINGREASON']) === 'authorization') {
            $order->add_order_note(__('This order cannot be refunded due to an authorized only transaction.  Please use cancel instead.', 'all-in-one-paypal-for-woocommerce'));
            $this->log('Refund order # ' . $order_id . ': authorized only transactions need to use cancel/void instead.');
            throw new Exception(__('This order cannot be refunded due to an authorized only transaction.  Please use cancel instead.', 'all-in-one-paypal-for-woocommerce'));
        }
        $post_data = array(
            'VERSION' => $this->api_version,
            'SIGNATURE' => $this->api_signature,
            'USER' => $this->api_username,
            'PWD' => $this->api_password,
            'METHOD' => 'RefundTransaction',
            'TRANSACTIONID' => $order->get_transaction_id(),
            'REFUNDTYPE' => is_null($amount) ? 'Full' : 'Partial'
        );
        if (!is_null($amount)) {
            $post_data['AMT'] = number_format($amount, 2, '.', '');
            $post_data['CURRENCYCODE'] = $order->get_order_currency();
        }
        if ($reason) {
            if (255 < strlen($reason)) {
                $reason = substr($reason, 0, 252) . '...';
            }
            $post_data['NOTE'] = html_entity_decode($reason, ENT_NOQUOTES, 'UTF-8');
        }
        $response = wp_remote_post($this->hostaddr, array(
            'method' => 'POST',
            'headers' => array('PAYPAL-NVP' => 'Y'),
            'body' => $post_data,
            'timeout' => 70,
            'sslverify' => false,
            'user-agent' => 'WooCommerce',
            'httpversion' => '1.1'
        ));
        if (is_wp_error($response)) {
            $this->log('Error ' . print_r($response->get_error_message(), true));
            throw new Exception(__('There was a problem connecting to the payment gateway.', 'all-in-one-paypal-for-woocommerce'));
        }
        parse_str($response['body'], $parsed_response);
        switch (strtolower($parsed_response['ACK'])) {
            case 'success':
            case 'successwithwarning':
                $order->add_order_note(sprintf(__('Refunded %s - Refund ID: %s', 'all-in-one-paypal-for-woocommerce'), $parsed_response['GROSSREFUNDAMT'], $parsed_response['REFUNDTRANSACTIONID']));
                return true;
            default:
                $this->log('Parsed Response (refund) ' . print_r($parsed_response, true));
                break;
        }
        return false;
    }

    /**
     * @since    1.0.0
     */
    public function get_transaction_details($transaction_id = 0) {
        $post_data = array(
            'VERSION' => $this->api_version,
            'SIGNATURE' => $this->api_signature,
            'USER' => $this->api_username,
            'PWD' => $this->api_password,
            'METHOD' => 'GetTransactionDetails',
            'TRANSACTIONID' => $transaction_id
        );
        $response = wp_remote_post($this->hostaddr, array(
            'method' => 'POST',
            'headers' => array(
                'PAYPAL-NVP' => 'Y'
            ),
            'body' => $post_data,
            'timeout' => 70,
            'sslverify' => false,
            'user-agent' => 'WooCommerce',
            'httpversion' => '1.1'
        ));
        if (is_wp_error($response)) {
            $this->log('Error ' . print_r($response->get_error_message(), true));
            throw new Exception(__('There was a problem connecting to the payment gateway.', 'all-in-one-paypal-for-woocommerce'));
        }
        parse_str($response['body'], $parsed_response);
        switch (strtolower($parsed_response['ACK'])) {
            case 'success':
            case 'successwithwarning':
                return $parsed_response;
                break;
        }
        return false;
    }

    /**
     * @since    1.0.0
     */
    function sendTransactionToGateway($url, $parameters) {
        $response = wp_remote_post($url, array(
            'method' => 'POST',
            'body' => $parameters,
            'timeout' => 70,
            'sslverify' => false,
            'user-agent' => 'Woocommerce ' . WC_VERSION,
            'httpversion' => '1.1',
            'headers' => array('host' => 'www.paypal.com')
        ));
        if (is_wp_error($response)) {
            throw new Exception(__('There was a problem connecting to the payment gateway, PayPal server is down.', 'paypal-advanced-gateway-for-woocommerce'));
        }

        if (empty($response['body'])) {
            throw new Exception(__('Empty response.', 'paypal-advanced-gateway-for-woocommerce'));
        }
        return $response;
    }

    /**
     * @since    1.0.0
     */
    public function log($message) {
        if ($this->debug) {
            if (!isset($this->log)) {
                $this->log = new WC_Logger();
            }
            $this->log->add('paypal-pro-hosted', $message);
        }
    }

}
?>