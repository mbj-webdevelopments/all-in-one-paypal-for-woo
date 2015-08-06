<?php

/**
 * @class       All_In_One_Paypal_For_Woocommerce_Admin_PayPal_Advanced
 * @version	1.0.0
 * @package	All_In_One_Paypal_For_Woocommerce
 * @category	Class
 * @author      mbj-webdevelopment <mbjwebdevelopment@gmail.com>
 */
class All_In_One_Paypal_For_Woocommerce_Admin_PayPal_Advanced extends WC_Payment_Gateway {

    /**
     * @since    1.0.0
     */
    public function __construct() {
        $this->id = 'paypal_advanced';
        $this->icon = apply_filters('woocommerce_paypal_advanced_icon', '');
        $this->has_fields = true;
        $this->home_url = is_ssl() ? home_url('/', 'https') : home_url('/');
        $this->testurl = 'https://pilot-payflowpro.paypal.com';
        $this->liveurl = 'https://payflowpro.paypal.com';
        $this->paypal_advance_return_response_url = add_query_arg('wc-api', 'All_In_One_Paypal_For_Woocommerce_Admin_PayPal_Advanced', $this->home_url);
        $this->method_title = __('PayPal Advanced', 'all-in-one-paypal-for-woocommerce');
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
        $this->loginid = $this->settings['loginid'];
        $this->resellerid = $this->settings['resellerid'];
        $this->transtype = $this->settings['transtype'];
        $this->password = $this->settings['password'];
        $this->debug = $this->settings['debug'];
        $this->invoice_prefix = $this->settings['invoice_prefix'];
        $this->page_collapse_bgcolor = $this->settings['page_collapse_bgcolor'];
        $this->page_collapse_textcolor = $this->settings['page_collapse_textcolor'];
        $this->page_button_bgcolor = $this->settings['page_button_bgcolor'];
        $this->page_button_textcolor = $this->settings['page_button_textcolor'];
        $this->label_textcolor = $this->settings['label_textcolor'];
        switch ($this->settings['layout']) {
            case 'A': $this->layout = 'TEMPLATEA';
                break;
            case 'B': $this->layout = 'TEMPLATEB';
                break;
            case 'C': $this->layout = 'MINLAYOUT';
                break;
        }
        $this->user = $this->settings['user'] == '' ? $this->settings['loginid'] : $this->settings['user'];
        $this->hostaddr = $this->testmode == 'yes' ? $this->testurl : $this->liveurl;
        if ($this->debug == 'yes') {
            $this->log = new WC_Logger();
        }
        add_action('admin_notices', array($this, 'check_required_field'));
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_receipt_paypal_advanced', array($this, 'paypal_advance_receipt_page'));
        add_action('woocommerce_api_all_in_one_paypal_for_woocommerce_admin_paypal_advanced', array($this, 'paypal_advance_return_response'));
        if (!$this->is_available()) {
            $this->enabled = false;
        }
    }

    /**
     * @since    1.0.0
     */
    public function check_required_field() {
        if ($this->enabled == 'no') {
            return;
        }
        if (!$this->loginid) {
            echo '<div class="error"><p>' . sprintf(__('Paypal Advanced : Enter your PayPal Advanced Account Merchant Login <a href="%s">here</a>', 'all-in-one-paypal-for-woocommerce'), admin_url('admin.php?page=wc-settings&tab=checkout&section=' . strtolower('All_In_One_Paypal_For_Woocommerce_Admin_PayPal_Advanced'))) . '</p></div>';
        } elseif (!$this->resellerid) {
            echo '<div class="error"><p>' . sprintf(__('Paypal Advanced : Enter your PayPal Advanced Account Partner <a href="%s">here</a>', 'all-in-one-paypal-for-woocommerce'), admin_url('admin.php?page=wc-settings&tab=checkout&section=' . strtolower('All_In_One_Paypal_For_Woocommerce_Admin_PayPal_Advanced'))) . '</p></div>';
        } elseif (!$this->password) {
            echo '<div class="error"><p>' . sprintf(__('Paypal Advanced : Enter your PayPal Advanced Account Password <a href="%s">here</a>', 'all-in-one-paypal-for-woocommerce'), admin_url('admin.php?page=wc-settings&tab=checkout&section=' . strtolower('All_In_One_Paypal_For_Woocommerce_Admin_PayPal_Advanced'))) . '</p></div>';
        }
        return;
    }

    /**
     * @since    1.0.0
     */
    public function paypal_advance_url_redirect($redirect_url) {
        @ob_clean();
        header('HTTP/1.1 200 OK');
        if ($this->layout != 'MINLAYOUT') {
            wp_redirect($redirect_url);
        } else {
            echo "<script>window.parent.location.href='" . $redirect_url . "';</script>";
        }
        exit;
    }

    /**
     * @since    1.0.0
     */
    public function paypal_advance_return_response() {
        $paypal_advance_silent_debug = ($this->debug == 'yes' && !isset($_REQUEST['silent'])) ? true : false;
        if (!isset($_REQUEST['INVOICE'])) {
            wp_redirect(home_url('/'));
            exit;
        }
        $_POST['ORDERID'] = $_REQUEST['USER1'];
        $order = new WC_Order($_POST['ORDERID']);
        $this->log(sprintf(__('Relay Response INVOICE = %s', 'all-in-one-paypal-for-woocommerce'), $_REQUEST['INVOICE']));
        $this->log(sprintf(__('Relay Response SECURETOKEN = %s', 'all-in-one-paypal-for-woocommerce'), $_REQUEST['SECURETOKEN']));
        $this->log(sprintf(__('Relay Response Order Number = %s', 'all-in-one-paypal-for-woocommerce'), $_POST['ORDERID']));
        if (isset($_REQUEST['silent']) && $_REQUEST['silent'] == 'true') {
            $this->log(sprintf(__('Silent Relay Response Triggered: %s', 'all-in-one-paypal-for-woocommerce'), print_r($_REQUEST, true)));
        } else {
            $this->log(sprintf(__('Relay Response Triggered: %s', 'all-in-one-paypal-for-woocommerce'), print_r($_REQUEST, true)));
        }
        if (!isset($_REQUEST['error'])) {
            if (get_post_meta($_POST['ORDERID'], '_secure_token', true) == $_REQUEST['SECURETOKEN']) {
                $this->log(__('Relay Response Tokens Match', 'all-in-one-paypal-for-woocommerce'));
            } else {
                $this->log(__('Relay Response Tokens Mismatch', 'all-in-one-paypal-for-woocommerce'));
                $this->paypal_advance_url_redirect($order->get_checkout_payment_url(true));
                exit;
            }
        }
        $status = isset($order->status) ? $order->status : $order->get_status();
        if ($status == 'processing' || $status == 'completed') {
            if ($paypal_advance_silent_debug) {
                $this->log(sprintf(__('Redirecting to Thank You Page for order %s', 'all-in-one-paypal-for-woocommerce'), $order->get_order_number()));
            }
            $this->paypal_advance_url_redirect($this->get_return_url($order));
        }
        if (isset($_REQUEST['error']) && $_REQUEST['error'] == 'true' && $_POST['RESULT'] != 0) {
            if ($_POST['RESULT'] == 12 && $status != 'failed') {
                $order->update_status('failed', __('Payment failed via PayPal Advanced because of.', 'all-in-one-paypal-for-woocommerce') . '&nbsp;' . $_POST['RESPMSG']);
                if ($debug == 'yes') {
                    $this->log(sprintf(__('Status has been changed to failed for order %s', 'all-in-one-paypal-for-woocommerce'), $order->get_order_number()));
                }
            }
            wc_clear_notices();
            wc_add_notice(__('Error:', 'all-in-one-paypal-for-woocommerce') . ' "' . urldecode($_POST['RESPMSG']) . '"', 'error');
            if ($paypal_advance_silent_debug) {
                $this->log(sprintf(__('Silent Error Occurred while processing %s : %s, status: %s', 'all-in-one-paypal-for-woocommerce'), $order->get_order_number(), urldecode($_POST['RESPMSG']), $_POST['RESULT']));
            } elseif ($debug == 'yes') {
                $this->log(sprintf(__('Error Occurred while processing %s : %s, status: %s', 'all-in-one-paypal-for-woocommerce'), $order->get_order_number(), urldecode($_POST['RESPMSG']), $_POST['RESULT']));
            }
            $this->paypal_advance_url_redirect($order->get_checkout_payment_url(true));
        } elseif (isset($_REQUEST['cancel_ec_trans']) && $_REQUEST['cancel_ec_trans'] == 'true' && !isset($_REQUEST['silent'])) {
            wp_redirect($order->get_cancel_order_url());
            exit;
        } elseif ($_POST['RESULT'] == 0) {
            $order->add_order_note(sprintf(__('PayPal Advanced payment completed (Order: %s). Transaction number/ID: %s. But needs to Inquiry transaction to have confirmation that it is actually paid.', 'all-in-one-paypal-for-woocommerce'), $order->get_order_number(), $_POST['PNREF']));
            $paypal_args = array(
                'USER' => $this->user,
                'VENDOR' => $this->loginid,
                'PARTNER' => $this->resellerid,
                'PWD[' . strlen($this->password) . ']' => $this->password,
                'ORIGID' => $_POST['PNREF'],
                'TENDER' => 'C',
                'TRXTYPE' => 'I',
                'BUTTONSOURCE' => 'mbjtechnolabs_SP'
            );
            $postData = '';
            foreach ($paypal_args as $key => $val) {
                $postData .='&' . $key . '=' . $val;
            }
            $postData = trim($postData, '&');
            $response = wp_remote_post($this->hostaddr, array(
                'method' => 'POST',
                'body' => $postData,
                'timeout' => 70,
                'sslverify' => false,
                'user-agent' => 'Woocommerce ' . WC_VERSION,
                'httpversion' => '1.1',
                'headers' => array('host' => 'www.paypal.com')
            ));
            if (is_wp_error($response)) {
                throw new Exception(__('There was a problem connecting to the payment gateway.', 'all-in-one-paypal-for-woocommerce'));
            }
            if (empty($response['body'])) {
                throw new Exception(__('Empty response.', 'all-in-one-paypal-for-woocommerce'));
            }
            $inquiry_result_arr = array();
            parse_str($response['body'], $inquiry_result_arr);
            if ($inquiry_result_arr['RESULT'] == 0) {
                $order->add_order_note(sprintf(__('Received result of Inquiry Transaction for the  (Order: %s) and is successful', 'all-in-one-paypal-for-woocommerce'), $order->get_order_number()));
                $order->payment_complete($_POST['PNREF']);
                WC()->cart->empty_cart();
                if ($paypal_advance_silent_debug) {
                    $this->log(sprintf(__('Redirecting to Thank You Page for order %s', 'all-in-one-paypal-for-woocommerce'), $order->get_order_number()));
                }
                $this->paypal_advance_url_redirect($this->get_return_url($order));
            }
        }
    }

    /**
     * @since    1.0.0
     */
    function paypal_advance_get_secure_token($order) {
        static $length_error = 0;
        if ($this->debug == 'yes') {
            $this->log(sprintf(__('Requesting for the Secured Token for the order %s', 'all-in-one-paypal-for-woocommerce'), $order->get_order_number()));
        }
        $this->secure_token_id = uniqid(substr($_SERVER['HTTP_HOST'], 0, 9), true);
        $paypal_args = array();
        $template = wp_is_mobile() ? "MOBILE" : $this->layout;
        $paypal_args = array(
            'VERBOSITY' => 'HIGH',
            'USER' => $this->user,
            'VENDOR' => $this->loginid,
            'PARTNER' => $this->resellerid,
            'PWD[' . strlen($this->password) . ']' => $this->password,
            'SECURETOKENID' => $this->secure_token_id,
            'CREATESECURETOKEN' => 'Y',
            'TRXTYPE' => $this->transtype,
            'CUSTREF' => $order->get_order_number(),
            'USER1' => $order->id,
            'INVNUM' => $this->invoice_prefix . ltrim($order->get_order_number(), '#'),
            'AMT' => $order->get_total(),
            'FREIGHTAMT' => number_format($order->get_total_shipping(), 2, '.', ''),
            'COMPANYNAME[' . strlen($order->billing_company) . ']' => $order->billing_company,
            'CURRENCY' => get_woocommerce_currency(),
            'EMAIL' => $order->billing_email,
            'BILLTOFIRSTNAME[' . strlen($order->billing_first_name) . ']' => $order->billing_first_name,
            'BILLTOLASTNAME[' . strlen($order->billing_last_name) . ']' => $order->billing_last_name,
            'BILLTOSTREET[' . strlen($order->billing_address_1 . ' ' . $order->billing_address_2) . ']' => $order->billing_address_1 . ' ' . $order->billing_address_2,
            'BILLTOCITY[' . strlen($order->billing_city) . ']' => $order->billing_city,
            'BILLTOSTATE[' . strlen($order->billing_state) . ']' => $order->billing_state,
            'BILLTOZIP' => $order->billing_postcode,
            'BILLTOCOUNTRY[' . strlen($order->billing_country) . ']' => $order->billing_country,
            'BILLTOEMAIL' => $order->billing_email,
            'BILLTOPHONENUM' => $order->billing_phone,
            'SHIPTOFIRSTNAME[' . strlen($order->shipping_first_name) . ']' => $order->shipping_first_name,
            'SHIPTOLASTNAME[' . strlen($order->shipping_last_name) . ']' => $order->shipping_last_name,
            'SHIPTOSTREET[' . strlen($order->shipping_address_1 . ' ' . $order->shipping_address_2) . ']' => $order->shipping_address_1 . ' ' . $order->shipping_address_2,
            'SHIPTOCITY[' . strlen($order->shipping_city) . ']' => $order->shipping_city,
            'SHIPTOZIP' => $order->shipping_postcode,
            'SHIPTOCOUNTRY[' . strlen($order->shipping_country) . ']' => $order->shipping_country,
            'BUTTONSOURCE' => 'mbjtechnolabs_SP',
            'RETURNURL[' . strlen($this->paypal_advance_return_response_url) . ']' => $this->paypal_advance_return_response_url,
            'ERRORURL[' . strlen($this->paypal_advance_return_response_url) . ']' => $this->paypal_advance_return_response_url,
            'SILENTPOSTURL[' . strlen($this->paypal_advance_return_response_url) . ']' => $this->paypal_advance_return_response_url,
            'URLMETHOD' => 'POST',
            'TEMPLATE' => $template,
            'PAGECOLLAPSEBGCOLOR' => ltrim($this->page_collapse_bgcolor, '#'),
            'PAGECOLLAPSETEXTCOLOR' => ltrim($this->page_collapse_textcolor, '#'),
            'PAGEBUTTONBGCOLOR' => ltrim($this->page_button_bgcolor, '#'),
            'PAGEBUTTONTEXTCOLOR' => ltrim($this->page_button_textcolor, '#'),
            'LABELTEXTCOLOR' => ltrim($this->settings['label_textcolor'], '#')
        );
        if (empty($order->shipping_state)) {
            $paypal_args['SHIPTOSTATE[' . strlen($order->shipping_city) . ']'] = $order->shipping_city;
        } else {
            $paypal_args['SHIPTOSTATE[' . strlen($order->shipping_state) . ']'] = $order->shipping_state;
        }
        $cancelurl = add_query_arg('wc-api', 'All_In_One_Paypal_For_Woocommerce_Admin_PayPal_Advanced', add_query_arg('cancel_ec_trans', 'true', $this->home_url));
        $paypal_args['CANCELURL[' . strlen($cancelurl) . ']'] = $cancelurl;
        $errorurl = add_query_arg('wc-api', 'All_In_One_Paypal_For_Woocommerce_Admin_PayPal_Advanced', add_query_arg('error', 'true', $this->home_url));
        $paypal_args['ERRORURL[' . strlen($errorurl) . ']'] = $errorurl;
        $silentposturl = add_query_arg('wc-api', 'All_In_One_Paypal_For_Woocommerce_Admin_PayPal_Advanced', add_query_arg('silent', 'true', $this->home_url));
        $paypal_args['SILENTPOSTURL[' . strlen($silentposturl) . ']'] = $silentposturl;
        if ($order->prices_include_tax == 'yes' || $order->get_order_discount() > 0 || $length_error > 1) {
            $item_names = array();
            if (sizeof($order->get_items()) > 0) {
                $paypal_args['FREIGHTAMT'] = number_format($order->get_total_shipping() + $order->get_shipping_tax(), 2, '.', '');
                if ($length_error <= 1) {
                    foreach ($order->get_items() as $item) {
                        if ($item['qty']) {
                            $item_names[] = $item['name'] . ' x ' . $item['qty'];
                        }
                    }
                } else {
                    $item_names[] = "All selected items, refer to Woocommerce order details";
                }
                $items_str = sprintf(__('Order %s', 'all-in-one-paypal-for-woocommerce'), $order->get_order_number()) . " - " . implode(', ', $item_names);
                $items_names_str = $this->paypal_advanced_item_name_helper($items_str);
                $items_desc_str = $this->paypal_advanced_item_desc_helper($items_str);
                $paypal_args['L_NAME0[' . strlen($items_names_str) . ']'] = $items_names_str;
                $paypal_args['L_DESC0[' . strlen($items_desc_str) . ']'] = $items_desc_str;
                $paypal_args['L_QTY0'] = 1;
                $paypal_args['L_COST0'] = number_format($order->get_total() - round($order->get_total_shipping() + $order->get_shipping_tax(), 2), 2, '.', '');
                $paypal_args['ITEMAMT'] = $paypal_args['L_COST0'] * $paypal_args['L_QTY0'];
            }
        } else {
            $paypal_args['TAXAMT'] = $order->get_total_tax();
            $paypal_args['ITEMAMT'] = 0;
            $item_loop = 0;
            if (sizeof($order->get_items()) > 0) {
                foreach ($order->get_items() as $item) {
                    if ($item['qty']) {
                        $product = $order->get_product_from_item($item);
                        $item_name = $item['name'];
                        $item_meta = new WC_order_item_meta($item['item_meta']);
                        if ($length_error == 0 && $meta = $item_meta->display(true, true)) {
                            $item_name .= ' (' . $meta . ')';
                            $item_name = $this->paypal_advanced_item_name_helper($item_name);
                        }
                        $paypal_args['L_NAME' . $item_loop . '[' . strlen($item_name) . ']'] = $item_name;
                        if ($product->get_sku())
                            $paypal_args['L_SKU' . $item_loop] = $product->get_sku();
                        $paypal_args['L_QTY' . $item_loop] = $item['qty'];
                        $paypal_args['L_COST' . $item_loop] = $order->get_item_total($item, false, false);
                        $paypal_args['L_TAXAMT' . $item_loop] = $order->get_item_tax($item, false);
                        $paypal_args['ITEMAMT'] += $order->get_line_total($item, false, false);
                        $item_loop++;
                    }
                }
            }
        }
        $paypal_args = apply_filters('woocommerce_paypal_args', $paypal_args);
        try {
            $postData = '';
            $logData = '';
            foreach ($paypal_args as $key => $val) {
                $postData .='&' . $key . '=' . $val;
                if (strpos($key, 'PWD') === 0) {
                    $logData .='&PWD=XXXX';
                } else {
                    $logData .='&' . $key . '=' . $val;
                }
            }
            $postData = trim($postData, '&');
            $logData = trim($logData, '&');
            $this->log(sprintf(__('Requesting for the Secured Token for the order %s with following URL and Paramaters: %s', 'all-in-one-paypal-for-woocommerce'), $order->get_order_number(), $this->hostaddr . '?' . $logData));
            $response = wp_remote_post($this->hostaddr, array(
                'method' => 'POST',
                'body' => $postData,
                'timeout' => 70,
                'sslverify' => false,
                'user-agent' => 'WooCommerce ' . WC_VERSION,
                'httpversion' => '1.1',
                'headers' => array('host' => 'www.paypal.com')
            ));
            if (is_wp_error($response)) {
                throw new Exception($response->get_error_message());
            }
            if (empty($response['body'])) {
                throw new Exception(__('Empty response.', 'all-in-one-paypal-for-woocommerce'));
            }
            parse_str($response['body'], $arr);
            if ($arr['RESULT'] > 0) {
                throw new Exception(__('There was an error processing your order - ' . $arr['RESPMSG'], 'all-in-one-paypal-for-woocommerce'));
            } else {
                return $arr['SECURETOKEN'];
            }
        } catch (Exception $e) {
            if ($this->debug == 'yes') {
                $this->log(sprintf(__('Secured Token generation failed for the order %s with error: %s', 'all-in-one-paypal-for-woocommerce'), $order->get_order_number(), $e->getMessage()));
            }
            if ($arr['RESULT'] != 7) {
                wc_add_notice(__('Error:', 'all-in-one-paypal-for-woocommerce') . ' "' . $e->getMessage() . '"', 'error');
                $length_error = 0;
                return;
            } else {
                if ($this->debug == 'yes') {
                    $this->log(sprintf(__('Secured Token generation failed for the order %s with error: %s', 'all-in-one-paypal-for-woocommerce'), $order->get_order_number(), $e->getMessage()));
                }
                $length_error++;
                return $this->paypal_advance_get_secure_token($order);
            }
        }
    }

    /**
     * @since    1.0.0
     */
    public function is_available() {
        if ($this->enabled == 'yes') {
            return true;
        }
        return false;
    }

    /**
     * @since    1.0.0
     */
    public function admin_options() {
        ?>
        <h3><?php _e('PayPal Advanced', 'all-in-one-paypal-for-woocommerce'); ?></h3>
        <p><?php _e('PayPal Payments Advanced uses an iframe to seamlessly integrate PayPal hosted pages into the checkout process.', 'all-in-one-paypal-for-woocommerce'); ?></p>
        <table class="form-table">
            <?php
            if (!in_array(get_woocommerce_currency(), array('USD', 'CAD'))) {
                ?>
                <div class="inline error"><p><strong><?php _e('Gateway Disabled', 'all-in-one-paypal-for-woocommerce'); ?></strong>: <?php _e('PayPal does not support your store currency.', 'all-in-one-paypal-for-woocommerce'); ?></p></div>
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
                'title' => __('Enable/Disable', 'all-in-one-paypal-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable PayPal Advanced', 'all-in-one-paypal-for-woocommerce'),
                'default' => 'yes'
            ),
            'title' => array(
                'title' => __('Title', 'all-in-one-paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'all-in-one-paypal-for-woocommerce'),
                'default' => __('PayPal Advanced', 'all-in-one-paypal-for-woocommerce')
            ),
            'description' => array(
                'title' => __('Description', 'all-in-one-paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('This controls the description which the user sees during checkout.', 'all-in-one-paypal-for-woocommerce'),
                'default' => __('PayPal Advanced description', 'all-in-one-paypal-for-woocommerce')
            ),
            'loginid' => array(
                'title' => __('Merchant Login', 'all-in-one-paypal-for-woocommerce'),
                'type' => 'text',
                'description' => '',
                'default' => ''
            ),
            'resellerid' => array(
                'title' => __('Partner', 'all-in-one-paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('Enter your PayPal Advanced Partner. If you purchased the account directly from PayPal, use PayPal.', 'all-in-one-paypal-for-woocommerce'),
                'default' => ''
            ),
            'user' => array(
                'title' => __('User (or Merchant Login if no designated user is set up for the account)', 'all-in-one-paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('Enter your PayPal Advanced user account for this site.', 'all-in-one-paypal-for-woocommerce'),
                'default' => ''
            ),
            'password' => array(
                'title' => __('Password', 'all-in-one-paypal-for-woocommerce'),
                'type' => 'password',
                'description' => __('Enter your PayPal Advanced account password.', 'all-in-one-paypal-for-woocommerce'),
                'default' => ''
            ),
            'testmode' => array(
                'title' => __('PayPal sandbox', 'all-in-one-paypal-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable PayPal sandbox', 'all-in-one-paypal-for-woocommerce'),
                'default' => 'yes',
                'description' => sprintf(__('PayPal sandbox can be used to test payments. Sign up for a developer account <a href="%s">here</a>', 'all-in-one-paypal-for-woocommerce'), 'https://developer.paypal.com/'),
            ),
            'transtype' => array(
                'title' => __('Transaction Type', 'all-in-one-paypal-for-woocommerce'),
                'type' => 'select',
                'label' => __('Transaction Type', 'all-in-one-paypal-for-woocommerce'),
                'default' => 'S',
                'description' => '',
                'options' => array('A' => 'Authorization', 'S' => 'Sale')
            ),
            'layout' => array(
                'title' => __('Layout', 'all-in-one-paypal-for-woocommerce'),
                'type' => 'select',
                'label' => __('Layout', 'all-in-one-paypal-for-woocommerce'),
                'default' => 'C',
                'description' => __('Layouts A and B redirect to PayPal\'s website for the user to pay. <br/>Layout C (recommended) is a secure PayPal-hosted page but is embedded on your site using an iFrame.', 'all-in-one-paypal-for-woocommerce'),
                'options' => array('A' => 'Layout A', 'B' => 'Layout B', 'C' => 'Layout C')
            ),
            'invoice_prefix' => array(
                'title' => __('Invoice Prefix', 'all-in-one-paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('Please enter a prefix for your invoice numbers. If you use your PayPal account for multiple stores ensure this prefix is unique as PayPal will not allow orders with the same invoice number.', 'woocommerce'),
                'default' => 'WC-PPADV',
                'desc_tip' => true,
            ),
            'page_collapse_bgcolor' => array(
                'title' => __('Page Collapse Border Color', 'all-in-one-paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('Sets the color of the border around the embedded template C.', 'all-in-one-paypal-for-woocommerce'),
                'default' => '',
                'desc_tip' => true,
                'class' => 'paypal-advanced-gateway-for-woocommerce_color_field'
            ),
            'page_collapse_textcolor' => array(
                'title' => __('Page Collapse Text Color', 'all-in-one-paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('Sets the color of the words "Pay with PayPal" and "Pay with credit or debit card".', 'all-in-one-paypal-for-woocommerce'),
                'default' => '',
                'desc_tip' => true,
                'class' => 'paypal-advanced-gateway-for-woocommerce_color_field'
            ),
            'page_button_bgcolor' => array(
                'title' => __('Page Button Background Color', 'all-in-one-paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('Sets the background color of the Pay Now / Submit button.', 'all-in-one-paypal-for-woocommerce'),
                'default' => '',
                'desc_tip' => true,
                'class' => 'paypal-advanced-gateway-for-woocommerce_color_field'
            ),
            'page_button_textcolor' => array(
                'title' => __('Page Button Text Color', 'all-in-one-paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('Sets the color of the text on the Pay Now / Submit button.', 'all-in-one-paypal-for-woocommerce'),
                'default' => '',
                'desc_tip' => true,
                'class' => 'paypal-advanced-gateway-for-woocommerce_color_field'
            ),
            'label_textcolor' => array(
                'title' => __('Label Text Color', 'all-in-one-paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('Sets the color of the text for "card number", "expiration date", ..etc.', 'all-in-one-paypal-for-woocommerce'),
                'default' => '',
                'desc_tip' => true,
                'class' => 'paypal-advanced-gateway-for-woocommerce_color_field'
            ),
            'debug' => array(
                'title' => __('Debug Log', 'all-in-one-paypal-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable logging', 'all-in-one-paypal-for-woocommerce'),
                'default' => 'no',
                'description' => __('Log PayPal events, such as Secured Token requests, inside <code>woocommerce/logs/paypal_advanced.txt</code>', 'all-in-one-paypal-for-woocommerce'),
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
        try {
            $this->securetoken = $this->paypal_advance_get_secure_token($order);
            if ($this->securetoken != "") {
                update_post_meta($order->id, '_secure_token_id', $this->secure_token_id);
                update_post_meta($order->id, '_secure_token', $this->securetoken);
                $this->log(sprintf(__('Secured Token generated successfully for the order %s', 'all-in-one-paypal-for-woocommerce'), $order->get_order_number()));
                return array(
                    'result' => 'success',
                    'redirect' => $order->get_checkout_payment_url(true)
                );
            }
        } catch (Exception $e) {
            wc_add_notice(__('Error:', 'all-in-one-paypal-for-woocommerce') . ' "' . $e->getMessage() . '"', 'error');
            $this->log('Error Occurred while processing the order ' . $order_id);
        }
        return;
    }

    /**
     * @since    1.0.0
     */
    public function process_refund($order_id, $amount = null, $reason = '') {
        $order = wc_get_order($order_id);
        if (!$order || !$order->get_transaction_id()) {
            return false;
        }
        if (!is_null($amount) && $order->get_total() > $amount) {
            return new WP_Error('paypal-advanced-error', __('Partial refund is not supported', 'woocommerce'));
        }
        $paypal_args = array(
            'USER' => $this->user,
            'VENDOR' => $this->loginid,
            'PARTNER' => $this->resellerid,
            'PWD[' . strlen($this->password) . ']' => $this->password,
            'ORIGID' => $order->get_transaction_id(),
            'TENDER' => 'C',
            'TRXTYPE' => 'C',
            'VERBOSITY' => 'HIGH'
        );
        $postData = '';
        foreach ($paypal_args as $key => $val) {
            $postData .='&' . $key . '=' . $val;
        }
        $postData = trim($postData, '&');
        $response = wp_remote_post($this->hostaddr, array(
            'method' => 'POST',
            'body' => $postData,
            'timeout' => 70,
            'sslverify' => false,
            'user-agent' => 'Woocommerce ' . WC_VERSION,
            'httpversion' => '1.1',
            'headers' => array('host' => 'www.paypal.com')
        ));
        if (is_wp_error($response)) {
            throw new Exception(__('There was a problem connecting to the payment gateway.', 'all-in-one-paypal-for-woocommerce'));
        }
        if (empty($response['body'])) {
            throw new Exception(__('Empty response.', 'all-in-one-paypal-for-woocommerce'));
        }
        $refund_result_arr = array();
        parse_str($response['body'], $refund_result_arr);
        $this->log('paypal_advanced', sprintf(__('Response of the refund transaction: %s', 'all-in-one-paypal-for-woocommerce'), print_r($refund_result_arr, true)));
        if ($refund_result_arr['RESULT'] == 0) {
            $order->add_order_note(sprintf(__('Successfully Refunded - Refund Transaction ID: %s', 'woocommerce'), $refund_result_arr['PNREF']));
        } else {
            $order->add_order_note(sprintf(__('Refund Failed - Refund Transaction ID: %s, Error Msg: %s', 'woocommerce'), $refund_result_arr['PNREF'], $refund_result_arr['RESPMSG']));
            throw new Exception(sprintf(__('Refund Failed - Refund Transaction ID: %s, Error Msg: %s', 'woocommerce'), $refund_result_arr['PNREF'], $refund_result_arr['RESPMSG']));
            return false;
        }
        return true;
    }

    /**
     * @since    1.0.0
     */
    public function paypal_advance_receipt_page($order_id) {
        $PF_MODE = $this->settings['testmode'] == 'yes' ? 'TEST' : 'LIVE';
        $order = new WC_Order($order_id);
        $this->secure_token_id = get_post_meta($order->id, '_secure_token_id', true);
        $this->securetoken = get_post_meta($order->id, '_secure_token', true);
        $this->log(sprintf(__('Browser Info: %s', 'all-in-one-paypal-for-woocommerce'), $_SERVER['HTTP_USER_AGENT']));
        if ($this->layout == 'MINLAYOUT' || $this->layout == 'C') {
            $location = 'https://payflowlink.paypal.com?mode=' . $PF_MODE . '&amp;SECURETOKEN=' . $this->securetoken . '&amp;SECURETOKENID=' . $this->secure_token_id;
            $this->log(sprintf(__('Show payment form(IFRAME) for the order %s as it is configured to use Layout C', 'all-in-one-paypal-for-woocommerce'), $order->get_order_number()));
            ?>
            <iframe id="all-in-one-paypal-for-woocommerce_iframe" src="<?php echo $location; ?>" width="550" height="565" scrolling="no" frameborder="0" border="0" allowtransparency="true"></iframe>
            <?php
        } else {
            $location = 'https://payflowlink.paypal.com?mode=' . $PF_MODE . '&SECURETOKEN=' . $this->securetoken . '&SECURETOKENID=' . $this->secure_token_id;
            $this->log(sprintf(__('Show payment form redirecting to ' . $location . ' for the order %s as it is not configured to use Layout C', 'all-in-one-paypal-for-woocommerce'), $order->get_order_number()));
            wp_redirect($location);
            exit;
        }
    }

    /**
     * @since    1.0.0
     */
    public function paypal_advanced_item_name_helper($item_name) {
        if (strlen($item_name) > 36) {
            $item_name = substr($item_name, 0, 33) . '...';
        }
        return html_entity_decode($item_name, ENT_NOQUOTES, 'UTF-8');
    }

    /**
     * @since    1.0.0
     */
    public function paypal_advanced_item_desc_helper($item_desc) {
        if (strlen($item_desc) > 127) {
            $item_desc = substr($item_desc, 0, 124) . '...';
        }
        return html_entity_decode($item_desc, ENT_NOQUOTES, 'UTF-8');
    }

    /**
     * @since    1.0.0
     */
    public function log($message) {
        if ($this->debug) {
            if (!isset($this->log)) {
                $this->log = new WC_Logger();
            }
            $this->log->add('paypal-advanced', $message);
        }
    }

}
