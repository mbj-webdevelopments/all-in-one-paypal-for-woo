<?php

/**
 * @class       All_In_One_Paypal_For_Woocommerce_Admin_WooCommerce_PayPal_Pro
 * @version	1.0.0
 * @package	all-in-one-paypal-for-woocommerce
 * @category	Class
 * @author      mbj-webdevelopment <mbjwebdevelopment@gmail.com>
 */
class All_In_One_Paypal_For_Woocommerce_Admin_WooCommerce_PayPal_Adaptive_Payments extends WC_Payment_Gateway {

    function __construct() {
        $this->id = 'paypal_adaptive_payment';
        $this->method_title = 'PayPal Adaptive Split Payment';
        $this->has_fields = true;
        $this->icon = plugins_url('images/paypal.png', __FILE__);
        $this->init_form_fields();
        $this->init_settings();
        $this->split_by = $this->get_option('_split_by');
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->testmode = $this->get_option('testmode');
        $this->notify_url = esc_url_raw(add_query_arg(array('ipn' => 'set'), site_url('/')));
        $this->security_user_id = $this->get_option('security_user_id');
        $this->security_password = $this->get_option('security_password');
        $this->security_signature = $this->get_option('security_signature');
        $this->security_application_id = $this->get_option('security_application_id');
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('init', array($this, 'check_ipn'));
        add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'), 10, 1);
    }

    function init_form_fields() {
        global $wp_roles;
        if (!$wp_roles) {
            $wp_roles = new WP_Roles();
        }
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'all-in-one-paypal-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('PayPal Adaptive Split Payment', 'all-in-one-paypal-for-woocommerce'),
                'default' => 'yes'
            ),
            '_payment_mode' => array(
                'title' => __('Payment Mode', 'all-in-one-paypal-for-woocommerce'),
                'type' => 'select',
                'label' => __('PayPal Adaptive', 'all-in-one-paypal-for-woocommerce'),
                'default' => 'parallel',
                'options' => array('parallel' => __('Parallel', 'all-in-one-paypal-for-woocommerce'), 'chained' => __('Chained', 'all-in-one-paypal-for-woocommerce'))
            ),
            '_payment_parallel_fees' => array(
                'title' => __('Payment Fees by', 'all-in-one-paypal-for-woocommerce'),
                'type' => 'select',
                'label' => __('Payment Fees by', 'all-in-one-paypal-for-woocommerce'),
                'default' => 'EACHRECEIVER',
                'options' => array('SENDER' => __('Sender', 'all-in-one-paypal-for-woocommerce'), 'EACHRECEIVER' => __('Each Receiver', 'all-in-one-paypal-for-woocommerce'))
            ),
            '_payment_chained_fees' => array(
                'title' => __('Payment Fees by', 'all-in-one-paypal-for-woocommerce'),
                'type' => 'select',
                'label' => __('Payment Fees by', 'all-in-one-paypal-for-woocommerce'),
                'default' => 'EACHRECEIVER',
                'options' => array('PRIMARYRECEIVER' => __('Primary Receiver', 'all-in-one-paypal-for-woocommerce'), 'EACHRECEIVER' => __('Each Receiver', 'all-in-one-paypal-for-woocommerce'))
            ),
            'title' => array(
                'title' => __('Title', 'all-in-one-paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'all-in-one-paypal-for-woocommerce'),
                'default' => __('PayPal Adaptive Split Payment', 'all-in-one-paypal-for-woocommerce'),
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => __('Description', 'all-in-one-paypal-for-woocommerce'),
                'type' => 'textarea',
                'default' => 'Pay with PayPal Adaptive Split Payment. You can pay with your credit card if you do not have a PayPal account',
                'desc_tip' => true,
                'description' => __('This controls the description which the user sees during checkout.', 'all-in-one-paypal-for-woocommerce'),
            ),
            'apidetails' => array(
                'title' => __('API Authentication', 'all-in-one-paypal-for-woocommerce'),
                'type' => 'title',
                'description' => '',
            ),
            'security_user_id' => array(
                'title' => __('API User ID', 'all-in-one-paypal-for-woocommerce'),
                'type' => 'text',
                'default' => '',
                'desc_tip' => true,
                'description' => __('Please enter your API User ID associated with your paypal account', 'all-in-one-paypal-for-woocommerce'),
            ),
            'security_password' => array(
                'title' => __('API Password', 'all-in-one-paypal-for-woocommerce'),
                'type' => 'text',
                'default' => '',
                'desc_tip' => true,
                'description' => __('Please enter your API Password associated with your paypal account', 'all-in-one-paypal-for-woocommerce'),
            ),
            'security_signature' => array(
                'title' => __('API Signature', 'all-in-one-paypal-for-woocommerce'),
                'type' => 'text',
                'default' => '',
                'desc_tip' => true,
                'description' => __('Please enter your API Signature associated with your paypal account', 'all-in-one-paypal-for-woocommerce'),
            ),
            'security_application_id' => array(
                'title' => __('Application ID', 'all-in-one-paypal-for-woocommerce'),
                'type' => 'text',
                'default' => '',
                'desc_tip' => true,
                'description' => __('Please enter your Application ID created with your paypal account', 'all-in-one-paypal-for-woocommerce'),
            ),
            'hide_product_field_user_role' => array(
                'title' => __('Hide Single Product Page PayPal Adaptive Settings for following User Roles', 'all-in-one-paypal-for-woocommerce'),
                'type' => 'multiselect',
                'css' => 'min-width:350px;',
                'default' => array(get_role('multi_vendor') != null ? 'multi_vendor' : ''),
                'options' => $wp_roles->get_names(),
                'desc_tip' => true,
                'description' => __('Hide Single Product Field based on User Role', 'all-in-one-paypal-for-woocommerce'),
            ),
            'receivers_details' => array(
                'title' => __('Receiver Details', 'all-in-one-paypal-for-woocommerce'),
                'type' => 'title',
                'description' => '',
            ),
            'pri_r_paypal_enable' => array(
                'title' => __('Enable Receiver 1', 'all-in-one-paypal-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('', 'all-in-one-paypal-for-woocommerce'),
                'default' => 'yes',
                'disabled' => true
            ),
            'pri_r_paypal_mail' => array(
                'title' => __('Receiver 1 PayPal Mail', 'all-in-one-paypal-for-woocommerce'),
                'type' => 'text',
                'default' => '',
                'desc_tip' => true,
                'description' => __('Please enter the receiver 1 paypal mail', 'all-in-one-paypal-for-woocommerce'),
            ),
            'pri_r_amount_percentage' => array(
                'title' => __('Receiver 1 Payment Percentage', 'all-in-one-paypal-for-woocommerce'),
                'type' => 'text',
                'default' => '',
                'desc_tip' => true,
                'description' => __('Please enter the receiver 1 Payment Percentage ', 'all-in-one-paypal-for-woocommerce'),
            ),
            'sec_r1_paypal_enable' => array(
                'title' => __('Enable Receiver 2', 'all-in-one-paypal-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('', 'all-in-one-paypal-for-woocommerce'),
                'default' => 'yes'
            ),
            'sec_r1_paypal_mail' => array(
                'title' => __('Receiver 2 PayPal Mail', 'all-in-one-paypal-for-woocommerce'),
                'type' => 'text',
                'default' => '',
                'desc_tip' => true,
                'description' => __('Please enter the receiver 2 paypal mail', 'all-in-one-paypal-for-woocommerce'),
            ),
            'sec_r1_amount_percentage' => array(
                'title' => __('Receiver 2 Payment Percentage', 'all-in-one-paypal-for-woocommerce'),
                'type' => 'text',
                'default' => '',
                'desc_tip' => true,
                'description' => __('Please enter the percentage of payment should be sent to receiver 2', 'all-in-one-paypal-for-woocommerce'),
            ),
            'sec_r2_paypal_enable' => array(
                'title' => __('Enable Receiver 3', 'all-in-one-paypal-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('', 'all-in-one-paypal-for-woocommerce'),
                'default' => ''
            ),
            'sec_r2_paypal_mail' => array(
                'title' => __('Receiver 3 PayPal Mail', 'all-in-one-paypal-for-woocommerce'),
                'type' => 'text',
                'default' => '',
                'desc_tip' => true,
                'description' => __('Please enter the  receiver 3 paypal mail', 'all-in-one-paypal-for-woocommerce'),
            ),
            'sec_r2_amount_percentage' => array(
                'title' => __('Receiver 3 Payment Percentage', 'all-in-one-paypal-for-woocommerce'),
                'type' => 'text',
                'default' => '',
                'desc_tip' => true,
                'description' => __('Please enter how much percentage of payment should be sent to receiver 3', 'all-in-one-paypal-for-woocommerce'),
            ),
            'sec_r3_paypal_enable' => array(
                'title' => __('Enable Receiver 4', 'all-in-one-paypal-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('', 'all-in-one-paypal-for-woocommerce'),
                'default' => ''
            ),
            'sec_r3_paypal_mail' => array(
                'title' => __('Receiver 4 PayPal Mail', 'all-in-one-paypal-for-woocommerce'),
                'type' => 'text',
                'default' => '',
                'desc_tip' => true,
                'description' => __('Please enter the receiver 4 paypal mail', 'all-in-one-paypal-for-woocommerce'),
            ),
            'sec_r3_amount_percentage' => array(
                'title' => __('Receiver 4 Payment Percentage', 'all-in-one-paypal-for-woocommerce'),
                'type' => 'text',
                'default' => '',
                'desc_tip' => true,
                'description' => __('Please enter how much percentage of payment should be sent to receiver 4', 'all-in-one-paypal-for-woocommerce'),
            ),
            'sec_r4_paypal_enable' => array(
                'title' => __('Enable Receiver 5', 'all-in-one-paypal-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('', 'all-in-one-paypal-for-woocommerce'),
                'default' => ''
            ),
            'sec_r4_paypal_mail' => array(
                'title' => __('Receiver 5 PayPal Mail', 'all-in-one-paypal-for-woocommerce'),
                'type' => 'text',
                'default' => '',
                'desc_tip' => true,
                'description' => __('Please enter the  receiver 5 paypal mail', 'all-in-one-paypal-for-woocommerce'),
            ),
            'sec_r4_amount_percentage' => array(
                'title' => __('Receiver 5 Payment Percentage', 'all-in-one-paypal-for-woocommerce'),
                'type' => 'text',
                'default' => '',
                'desc_tip' => true,
                'description' => __('Please enter how much percentage of payment should be sent to receiver 5', 'all-in-one-paypal-for-woocommerce'),
            ),
            'sec_r5_paypal_enable' => array(
                'title' => __('Enable Receiver 6', 'all-in-one-paypal-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('', 'all-in-one-paypal-for-woocommerce'),
                'default' => ''
            ),
            'sec_r5_paypal_mail' => array(
                'title' => __('Receiver 6 PayPal Mail', 'all-in-one-paypal-for-woocommerce'),
                'type' => 'text',
                'default' => '',
                'desc_tip' => true,
                'description' => __('Please enter the  receiver 6 paypal mail', 'all-in-one-paypal-for-woocommerce'),
            ),
            'sec_r5_amount_percentage' => array(
                'title' => __('Receiver 6 Payment Percentage', 'all-in-one-paypal-for-woocommerce'),
                'type' => 'text',
                'default' => '',
                'desc_tip' => true,
                'description' => __('Please enter how much percentage of payment should be sent to  receiver 6', 'all-in-one-paypal-for-woocommerce'),
            ),
            'testing' => array(
                'title' => __('Gateway Testing', 'all-in-one-paypal-for-woocommerce'),
                'type' => 'title',
                'description' => '',
            ),
            'testmode' => array(
                'title' => __('PayPal Adaptive sandbox', 'all-in-one-paypal-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable PayPal Adaptive sandbox', 'all-in-one-paypal-for-woocommerce'),
                'default' => 'no',
                'description' => sprintf(__('PayPal Adaptive sandbox can be used to test payments. Sign up for a developer account <a href="%s">here</a>.', 'all-in-one-paypal-for-woocommerce'), 'https://developer.paypal.com/'),
            ),
        );
    }

    function process_payment($order_id) {
        global $woocommerce;
        $order = new WC_Order($order_id);
        $primary_receiver_mail = $this->get_option('pri_r_paypal_mail');
        $order_total_amount = $order->order_total;
        $success_url = $this->get_return_url($order);
        $cancel_url = str_replace("&amp;", "&", $order->get_cancel_order_url());
        $security_user_id = $this->security_user_id;
        $security_password = $this->security_password;
        $security_signature = $this->security_signature;
        $security_application_id = $this->security_application_id;
        if ("yes" == $this->testmode) {
            $paypal_pay_action_url = "https://svcs.sandbox.paypal.com/AdaptivePayments/Pay";
            $paypal_pay_auth_without_key_url = "https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_ap-payment&paykey=";
        } else {
            $paypal_pay_action_url = "https://svcs.paypal.com/AdaptivePayments/Pay";
            $paypal_pay_auth_without_key_url = "https://www.paypal.com/cgi-bin/webscr?cmd=_ap-payment&paykey=";
        }
        $ipnNotificationUrl = esc_url_raw(add_query_arg(array('ipn' => 'set', 'self_custom' => $order_id), site_url('/')));
        $headers_array = array("X-PAYPAL-SECURITY-USERID" => $security_user_id,
            "X-PAYPAL-SECURITY-PASSWORD" => $security_password,
            "X-PAYPAL-SECURITY-SIGNATURE" => $security_signature,
            "X-PAYPAL-APPLICATION-ID" => $security_application_id,
            "X-PAYPAL-REQUEST-DATA-FORMAT" => "NV",
            "X-PAYPAL-RESPONSE-DATA-FORMAT" => "JSON",
        );
        $receivers_key_value = array();
        foreach ($order->get_items() as $items) {
            if ("enable_indiv" == get_post_meta($items['product_id'], "_enable_paypal_adaptive_payment", true)) {
                if (array_key_exists(get_post_meta($items['product_id'], "_apap_primary_rec_mail_id", true), $receivers_key_value)) {
                    $previous_amount = $receivers_key_value[get_post_meta($items['product_id'], "_apap_primary_rec_mail_id", true)];
                    $x_share = ($order->get_line_total($items) * get_post_meta($items['product_id'], "_apap_primary_rec_percent", true)) / 100;
                    $calculated = $previous_amount + $x_share;
                    $receivers_key_value[get_post_meta($items['product_id'], "_apap_primary_rec_mail_id", true)] = $calculated;
                } else {
                    $x_share = ($order->get_line_total($items) * get_post_meta($items['product_id'], "_apap_primary_rec_percent", true)) / 100;
                    $receivers_key_value[get_post_meta($items['product_id'], "_apap_primary_rec_mail_id", true)] = $x_share;
                }
                for ($i = 1; $i <= 5; $i++) {
                    if ("yes" == get_post_meta($items['product_id'], '_apap_sec_' . $i . '_enable', true)) {
                        if (array_key_exists(get_post_meta($items['product_id'], '_apap_sec_' . $i . '_rec_mail_id', true), $receivers_key_value)) {
                            $previous_amount = $receivers_key_value[get_post_meta($items['product_id'], '_apap_sec_' . $i . '_rec_mail_id', true)];
                            $x_share = ($order->get_line_total($items) * get_post_meta($items['product_id'], '_apap_sec_' . $i . '_rec_percent', true)) / 100;
                            $calculated = $previous_amount + $x_share;
                            $receivers_key_value[get_post_meta($items['product_id'], '_apap_sec_' . $i . '_rec_mail_id', true)] = $calculated;
                        } else {
                            $x_share = ($order->get_line_total($items) * get_post_meta($items['product_id'], '_apap_sec_' . $i . '_rec_percent', true)) / 100;
                            $receivers_key_value[get_post_meta($items['product_id'], '_apap_sec_' . $i . '_rec_mail_id', true)] = $x_share;
                        }
                    }
                }
            } elseif ("enable_category" == get_post_meta($items['product_id'], "_enable_paypal_adaptive_payment", true)) {
                $apap_product_category = wp_get_post_terms($items['product_id'], 'product_cat');
                $category_count = count($apap_product_category);
                if ($category_count > 0 && 1 >= $category_count) {
                    $categ_meta = get_metadata('woocommerce_term', $apap_product_category[0]->term_id);
                    for ($i = 1; $i <= 6; $i++) {
                        if ("yes" == $categ_meta['_apap_rec_' . $i . '_enable'][0]) {
                            if (array_key_exists($categ_meta['_apap_rec_' . $i . '_mail_id'][0], $receivers_key_value)) {
                                $previous_amount = $receivers_key_value[$categ_meta['_apap_rec_' . $i . '_mail_id'][0]];
                                $x_share = ($order->get_line_total($items) * $categ_meta['_apap_rec_' . $i . '_percent'][0]) / 100;
                                $calculated = $previous_amount + $x_share;
                                $receivers_key_value[$categ_meta['_apap_rec_' . $i . '_mail_id'][0]] = $calculated;
                            } else {
                                $x_share = ($order->get_line_total($items) * $categ_meta['_apap_rec_' . $i . '_percent'][0]) / 100;
                                $receivers_key_value[$categ_meta['_apap_rec_' . $i . '_mail_id'][0]] = $x_share;
                            }
                        }
                    }
                } else {
                    $percentagecalculator = array();
                    if (is_array($apap_product_category)) {
                        foreach ($apap_product_category as $each_product_category) {
                            $categ_meta = get_metadata('woocommerce_term', $each_product_category->term_id);
                            for ($i = 1; $i <= 6; $i++) {
                                if ("yes" == @$categ_meta['_apap_rec_' . $i . '_enable'][0]) {
                                    if (array_key_exists(@$categ_meta['_apap_rec_' . $i . '_mail_id'][0], $receivers_key_value)) {
                                        $previous_amount = @$receivers_key_value[$categ_meta['_apap_rec_' . $i . '_mail_id'][0]];
                                        $x_share = ($order->get_line_total($items) * $categ_meta['_apap_rec_' . $i . '_percent'][0]) / 100;
                                        $calculated = $previous_amount + $x_share;
                                        @$receivers_key_value[$categ_meta['_apap_rec_' . $i . '_mail_id'][0]] = $calculated;
                                    } else {
                                        $x_share = ($order->get_line_total($items) * $categ_meta['_apap_rec_' . $i . '_percent'][0]) / 100;
                                        @$receivers_key_value[$categ_meta['_apap_rec_' . $i . '_mail_id'][0]] = $x_share;
                                    }
                                    @$percentagecalculator[$each_product_category->term_id] += $categ_meta['_apap_rec_' . $i . '_percent'][0];
                                }
                            }
                            if (@$percentagecalculator[$each_product_category->term_id] == 100) {
                                break;
                            }
                        }
                    }
                }
            } elseif (("disable" == get_post_meta($items['product_id'], "_enable_paypal_adaptive_payment", true)) || ("" == get_post_meta($items['product_id'], "_enable_paypal_adaptive_payment", true))) {
                if (array_key_exists($this->get_option('pri_r_paypal_mail'), $receivers_key_value)) {
                    $previous_amount = $receivers_key_value[$this->get_option('pri_r_paypal_mail')];
                    $x_share = ($order->get_line_total($items) * $this->get_option('pri_r_amount_percentage')) / 100;
                    $calculated = $previous_amount + $x_share;
                    $receivers_key_value[$this->get_option('pri_r_paypal_mail')] = $calculated;
                } else {
                    $x_share = ($order->get_line_total($items) * $this->get_option('pri_r_amount_percentage')) / 100;
                    $receivers_key_value[$this->get_option('pri_r_paypal_mail')] = $x_share;
                }
                for ($i = 1; $i <= 5; $i++) {
                    if ("yes" == $this->get_option('sec_r' . $i . '_paypal_enable')) {
                        if (array_key_exists($this->get_option('sec_r' . $i . '_paypal_mail'), $receivers_key_value)) {
                            $previous_amount = $receivers_key_value[$this->get_option('sec_r' . $i . '_paypal_mail')];
                            $x_share = ($order->get_line_total($items) * $this->get_option('sec_r' . $i . '_amount_percentage')) / 100;
                            $calculated = $previous_amount + $x_share;
                            $receivers_key_value[$this->get_option('sec_r' . $i . '_paypal_mail')] = $calculated;
                        } else {
                            $x_share = ($order->get_line_total($items) * $this->get_option('sec_r' . $i . '_amount_percentage')) / 100;
                            $receivers_key_value[$this->get_option('sec_r' . $i . '_paypal_mail')] = $x_share;
                        }
                    }
                }
            }
        }
        $primary_user_percentage = $this->get_option('pri_r_amount_percentage');
        $primary_user_amount = round((($order_total_amount * $primary_user_percentage) / 100), 2); // rounding to avoid paypal float problem 589023
        for ($user = 1; $user <= 5; $user++) {
            ${'secondary_user' . $user . '_mail'} = $this->get_option('sec_r' . $user . '_paypal_mail');
            ${'secondary_user' . $user . '_percentage'} = $this->get_option('sec_r' . $user . '_amount_percentage');
            ${'secondary_user' . $user . '_amount'} = round((($order_total_amount * ${'secondary_user' . $user . '_percentage'}) / 100), 2);
        }
        $paymentfeesby = 'EACHRECEIVER';
        if ("parallel" == $this->get_option('_payment_mode')) {
            $paymentfeesby = $this->get_option('_payment_parallel_fees');
        } else {
            if ('chained' == $this->get_option('_payment_mode')) {
                $paymentfeesby = $this->get_option('_payment_chained_fees');
            }
        }
        $data_array = array('actionType' => 'PAY',
            'currencyCode' => get_woocommerce_currency(),
            'feesPayer' => $paymentfeesby,
            'returnUrl' => $success_url,
            'cancelUrl' => $cancel_url,
            'custom' => $order_id,
            'ipnNotificationUrl' => $ipnNotificationUrl,
            'requestEnvelope.errorLanguage' => 'en_US',
        );
        $manual_cart_total_amount = array_sum($receivers_key_value);
        $receivers_key_percent = array();
        foreach ($receivers_key_value as $key => $value) {
            $receivers_key_percent[$key] = ($value / $manual_cart_total_amount) * 100;
        }
        $receivers_mail_amount = array();
        foreach ($receivers_key_percent as $receiver => $percent) {
            $receivers_mail_amount[$receiver] = round((($order->order_total * $percent) / 100), 2);
        }
        $manual_order_total_amount = array_sum($receivers_mail_amount);
        if ($manual_order_total_amount > $order->order_total) {
            $amount_to_compensate = $manual_order_total_amount - $order->order_total;
            $first_person_count = 0;
            foreach ($receivers_mail_amount as $mail => $amount) {
                if ($first_person_count == 0) {
                    $receivers_mail_amount[$mail] = $receivers_mail_amount[$mail] - $amount_to_compensate;
                }
                $first_person_count++;
            }
        } elseif ($manual_order_total_amount < $order->order_total) {
            $amount_to_compensate = $order->order_total - $manual_order_total_amount;
            $first_person_count = 0;
            foreach ($receivers_mail_amount as $mail => $amount) {
                if ($first_person_count == 0) {
                    $receivers_mail_amount[$mail] = $receivers_mail_amount[$mail] + $amount_to_compensate;
                }
                $first_person_count++;
            }
        }
        if ("parallel" == $this->get_option('_payment_mode')) {
            $pay_count = 0;
            foreach ($receivers_mail_amount as $mail => $amount) {
                $data_array['receiverList.receiver(' . $pay_count . ').amount'] = $amount;
                $data_array['receiverList.receiver(' . $pay_count . ').email'] = $mail;
                $pay_count++;
            }
        } elseif ("chained" == $this->get_option('_payment_mode')) {
            $pay_count = 0;
            $total_amount = array_sum($receivers_mail_amount); //calculate total here too, so if compensated it will be added here correctly
            foreach ($receivers_mail_amount as $mail => $amount) {
                if ($pay_count == 0) {
                    $data_array['receiverList.receiver(' . $pay_count . ').amount'] = $total_amount; // this is a primary user so total amount
                    $data_array['receiverList.receiver(' . $pay_count . ').email'] = $mail;
                    $data_array['receiverList.receiver(' . $pay_count . ').primary'] = "true";
                } else {
                    $data_array['receiverList.receiver(' . $pay_count . ').amount'] = $amount;
                    $data_array['receiverList.receiver(' . $pay_count . ').email'] = $mail;
                    $data_array['receiverList.receiver(' . $pay_count . ').primary'] = "false";
                }
                $pay_count++;
            }
        }
        $pay_result = wp_remote_request($paypal_pay_action_url, array('method' => 'POST', 'timeout' => 20, 'headers' => $headers_array, 'body' => $data_array));
        if (is_wp_error($pay_result)) {
            $re = print_r($pay_result->get_error_message(), true);
            wc_add_notice($re, 'error');
            return;
        }
        $jso = json_decode($pay_result['body']);
        @$payment_url = $paypal_pay_auth_without_key_url . $jso->payKey;
        if ("Success" == $jso->responseEnvelope->ack) {
            return array(
                'result' => 'success',
                'redirect' => $payment_url
            );
        } else {
            $error_code = "<br>Error Code: " . $jso->error[0]->errorId;
            wc_add_notice(__($jso->error[0]->message, 'all-in-one-paypal-for-woocommerce') . $error_code, 'error');
            return;
        }
    }

    function thankyou_page($order_id) {
        global $woocommerce;
        $order = new WC_Order($order_id);
        if ($order->payment_method == 'paypal_adaptive_payment') {
            $order->update_status('on-hold', __('Awaiting IPN Response to make the Status to Processing', 'all-in-one-paypal-for-woocommerce'));
        }
        if ($downloads = $woocommerce->customer->get_downloadable_products()) :
            ?>
            <h2><?php _e('Available downloads', 'all-in-one-paypal-for-woocommerce'); ?></h2>
            <ul class="digital-downloads">
                <?php foreach ($downloads as $download) : ?>
                    <?php if ($download['order_id'] != $order_id) : ?>
                        <?php continue; ?>
                    <?php endif; ?>
                    <li>
                        <?php if (is_numeric($download['downloads_remaining'])) : ?>
                            <span class="count">
                                <?php echo $download['downloads_remaining'] . _n(' download Remaining', ' downloads Remaining', $download['downloads_remaining'], 'all-in-one-paypal-for-woocommerce'); ?>
                            </span>
                        <?php endif; ?>
                        <a href="<?php echo esc_url($download['download_url']); ?>"><?php echo $download['download_name']; ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php
        endif;
    }

}
