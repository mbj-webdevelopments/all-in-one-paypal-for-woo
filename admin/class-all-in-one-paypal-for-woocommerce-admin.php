<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    All_In_One_Paypal_For_Woocommerce
 * @subpackage All_In_One_Paypal_For_Woocommerce/admin
 * @author     mbj-webdevelopment <mbjwebdevelopment@gmail.com>
 */
class All_In_One_Paypal_For_Woocommerce_Admin {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version) {

        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in All_In_One_Paypal_For_Woocommerce_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The All_In_One_Paypal_For_Woocommerce_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/all-in-one-paypal-for-woocommerce-admin.css', array(), $this->version, 'all');
        wp_enqueue_style($this->plugin_name . 'activation', plugin_dir_url(__FILE__) . 'css/all-in-one-paypal-for-woocommerce-public-activation.css', array(), $this->version, 'all');
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in All_In_One_Paypal_For_Woocommerce_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The All_In_One_Paypal_For_Woocommerce_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/all-in-one-paypal-for-woocommerce-admin.js', array('wp-color-picker'), $this->version, true);
    }

    public function load_plugin_extend_lib() {
        if (!class_exists('WC_Payment_Gateway')) {
            return;
        }
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/class-all-in-one-paypal-for-woocommerce-admin-paypal-pro-hosted.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/class-all-in-one-paypal-for-woocommerce-admin-paypal-advanced.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/class-all-in-one-paypal-for-woocommerce-admin-paypal-digital-goods.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/class-all-in-one-paypal-for-woocommerce-admin-paypal-pro-payflow.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/class-all-in-one-paypal-for-woocommerce-admin-paypal-pro.php';
        require_once( 'partials/lib/paypal-digital-goods/paypal-purchase.class.php' );
        require_once( 'partials/lib/paypal-digital-goods/paypal-subscription.class.php' );
    }

    public function all_in_one_paypal_for_woocommerce_add_gateway($methods) {
        $methods[] = 'All_In_One_Paypal_For_Woocommerce_Admin_WooCommerce_Pro_Hosted';
        $methods[] = 'All_In_One_Paypal_For_Woocommerce_Admin_PayPal_Advanced';
        $methods[] = 'All_In_One_Paypal_For_Woocommerce_Admin_Paypal_Digital_Goods';
        $methods[] = 'All_In_One_Paypal_For_Woocommerce_Admin_WooCommerce_PayPal_Pro_PayFlow';
        $methods[] = 'All_In_One_Paypal_For_Woocommerce_Admin_WooCommerce_PayPal_Pro';
        return $methods;
    }

    public function all_in_one_paypal_for_woocommerce_paypal_digital_goods_subscription_status($order_id, $profile_id) {
        $all_in_one_paypal_for_woocommerce_paypal_digital_goods_gateway = new All_In_One_Paypal_For_Woocommerce_Admin_Paypal_Digital_Goods();
        $paypal_object = $all_in_one_paypal_for_woocommerce_paypal_digital_goods_gateway->get_paypal_object($order_id);
        $transaction_details = $paypal_object->get_details($profile_id);
        $all_in_one_paypal_for_woocommerce_paypal_digital_goods_gateway->process_subscription_sign_up($transaction_details);
    }

    public function all_in_one_paypal_for_woocommerce_paypal_digital_goods_process_ipn_request($transaction_details) {
        $all_in_one_paypal_for_woocommerce_paypal_digital_goods_gateway = new All_In_One_Paypal_For_Woocommerce_Admin_Paypal_Digital_Goods();
        $transaction_details = stripslashes_deep($transaction_details);
        $all_in_one_paypal_for_woocommerce_paypal_digital_goods_gateway->process_ipn_request($transaction_details);
    }

    public function all_in_one_paypal_for_woocommerce_paypal_digital_goods_ajax_do_express_checkout() {
        $all_in_one_paypal_for_woocommerce_paypal_digital_goods_gateway = new All_In_One_Paypal_For_Woocommerce_Admin_Paypal_Digital_Goods();
        $all_in_one_paypal_for_woocommerce_paypal_digital_goods_gateway->ajax_do_express_checkout();
    }

    public function all_in_one_paypal_for_woocommerce_paypal_pro_ssl_check() {
        global $current_user;
        get_currentuserinfo();
        $settings = get_option('woocommerce_paypal_pro_settings', array());
        if (get_option('woocommerce_force_ssl_checkout') === 'no' && !class_exists('WordPressHTTPS') && isset($settings['enabled']) && $settings['enabled'] === 'yes' && $settings['testmode'] !== 'yes' && !get_user_meta($current_user->ID, '_wc_paypal_pro_ssl_nag_hide')) {
            echo '<div class="error"><p>' . sprintf(__('PayPal Pro requires that the <a href="%s">Force secure checkout</a> option is enabled; your checkout may not be secure! Please enable SSL and ensure your server has a valid SSL certificate - PayPal Pro will only work in test mode.', 'woocommerce-gateway-paypal-pro') . ' <a href="%s">' . __('Hide Notice', 'woocommerce') . '</a>', admin_url('admin.php?page=woocommerce'), wp_nonce_url(add_query_arg('wc_paypal_pro_ssl_nag', '1'), 'wc_paypal_pro_ssl_nag_hide')) . '</p></div>';
        }
        return true;
    }

    public function all_in_one_paypal_for_woocommerce_paypal_pro_update_ssl_nag() {
        global $current_user;
        get_currentuserinfo();
        if (isset($_GET['_wpnonce']) && !wp_verify_nonce($_GET['_wpnonce'], 'wc_paypal_pro_ssl_nag_hide')) {
            return;
        }
        if (isset($_GET['wc_paypal_pro_ssl_nag']) && '1' === $_GET['wc_paypal_pro_ssl_nag']) {
            add_user_meta($current_user->ID, '_wc_paypal_pro_ssl_nag_hide', '1', true);
        }
    }

    public function all_in_one_paypal_for_woocommerce_paypal_pro_capture_payment() {
        $order = new WC_Order($order_id);
        $txn_id = get_post_meta($order_id, '_transaction_id', true);
        $captured = get_post_meta($order_id, '_paypalpro_charge_captured', true);
        if ($order->payment_method === 'paypal_pro' && $txn_id && $captured === 'no') {
            $paypalpro = new WC_Gateway_PayPal_Pro();
            $url = $paypalpro->testmode ? $paypalpro->testurl : $paypalpro->liveurl;
            $post_data = array(
                'VERSION' => $paypalpro->api_version,
                'SIGNATURE' => $paypalpro->api_signature,
                'USER' => $paypalpro->api_username,
                'PWD' => $paypalpro->api_password,
                'METHOD' => 'DoCapture',
                'AUTHORIZATIONID' => $txn_id,
                'AMT' => $order->get_total(),
                'CURRENCYCODE' => $order->get_order_currency(),
                'COMPLETETYPE' => 'Complete'
            );
            if ($paypalpro->soft_descriptor) {
                $post_data['SOFTDESCRIPTOR'] = $paypalpro->soft_descriptor;
            }
            $response = wp_remote_post($url, array(
                'method' => 'POST',
                'headers' => array(
                    'PAYPAL-NVP' => 'Y'
                ),
                'body' => $post_data,
                'timeout' => 70,
                'user-agent' => 'WooCommerce',
                'httpversion' => '1.1'
            ));
            if (is_wp_error($response)) {
                $order->add_order_note(__('Unable to capture charge!', 'woocommerce-gateway-paypal-pro') . ' ' . $response->get_error_message());
            } else {
                parse_str($response['body'], $parsed_response);
                $order->add_order_note(sprintf(__('PayPal Pro charge complete (Transaction ID: %s)', 'woocommerce-gateway-paypal-pro'), $parsed_response['TRANSACTIONID']));
                update_post_meta($order->id, '_paypalpro_charge_captured', 'yes');
                update_post_meta($order->id, '_transaction_id', $parsed_response['TRANSACTIONID']);
            }
        }
        if ($order->payment_method === 'paypal_pro_payflow' && $txn_id && $captured === 'no') {
            $paypalpro_payflow = new WC_Gateway_PayPal_Pro_PayFlow();
            $url = $paypalpro_payflow->testmode ? $paypalpro_payflow->testurl : $paypalpro_payflow->liveurl;
            $post_data = array();
            $post_data['USER'] = $paypalpro_payflow->paypal_user;
            $post_data['VENDOR'] = $paypalpro_payflow->paypal_vendor;
            $post_data['PARTNER'] = $paypalpro_payflow->paypal_partner;
            $post_data['PWD'] = $paypalpro_payflow->paypal_password;
            $post_data['TRXTYPE'] = 'D'; 
            $post_data['ORIGID'] = $txn_id;
            if ($paypalpro_payflow->soft_descriptor) {
                $post_data['MERCHDESCR'] = $paypalpro_payflow->soft_descriptor;
            }
            $response = wp_remote_post($url, array(
                'method' => 'POST',
                'body' => urldecode(http_build_query($post_data, null, '&')),
                'timeout' => 70,
                'user-agent' => 'WooCommerce',
                'httpversion' => '1.1'
            ));
            parse_str($response['body'], $parsed_response);
            if (is_wp_error($response)) {
                $order->add_order_note(__('Unable to capture charge!', 'woocommerce-gateway-paypal-pro') . ' ' . $response->get_error_message());
            } elseif ($parsed_response['RESULT'] !== '0') {
                $order->add_order_note(__('Unable to capture charge!', 'woocommerce-gateway-paypal-pro'));
                $paypalpro_payflow->log('Parsed Response ' . print_r($parsed_response, true));
            } else {
                $order->add_order_note(sprintf(__('PayPal Pro (Payflow) delay charge complete (PNREF: %s)', 'woocommerce-gateway-paypal-pro'), $parsed_response['PNREF']));
                update_post_meta($order->id, '_paypalpro_charge_captured', 'yes');
                update_post_meta($order->id, '_transaction_id', $parsed_response['PNREF']);
            }
        }
        return true;
    }

    public function all_in_one_paypal_for_woocommerce_paypal_pro_cancel_payment() {
        $order = new WC_Order($order_id);
        $txn_id = get_post_meta($order_id, '_transaction_id', true);
        $captured = get_post_meta($order_id, '_paypalpro_charge_captured', true);
        if ($order->payment_method === 'paypal_pro' && $txn_id && $captured === 'no') {
            $paypalpro = new WC_Gateway_PayPal_Pro();
            $url = $paypalpro->testmode ? $paypalpro->testurl : $paypalpro->liveurl;
            $post_data = array(
                'VERSION' => $paypalpro->api_version,
                'SIGNATURE' => $paypalpro->api_signature,
                'USER' => $paypalpro->api_username,
                'PWD' => $paypalpro->api_password,
                'METHOD' => 'DoVoid',
                'AUTHORIZATIONID' => $txn_id
            );
            $response = wp_remote_post($url, array(
                'method' => 'POST',
                'headers' => array(
                    'PAYPAL-NVP' => 'Y'
                ),
                'body' => $post_data,
                'timeout' => 70,
                'user-agent' => 'WooCommerce',
                'httpversion' => '1.1'
            ));
            if (is_wp_error($response)) {
                $order->add_order_note(__('Unable to void charge!', 'woocommerce-gateway-paypal-pro') . ' ' . $response->get_error_message());
            } else {
                parse_str($response['body'], $parsed_response);
                $order->add_order_note(sprintf(__('PayPal Pro void complete (Authorization ID: %s)', 'woocommerce-gateway-paypal-pro'), $parsed_response['AUTHORIZATIONID']));
                delete_post_meta($order->id, '_paypalpro_charge_captured');
                delete_post_meta($order->id, '_transaction_id');
            }
        }
        if ($order->payment_method === 'paypal_pro_payflow' && $txn_id && $captured === 'no') {
            $paypalpro_payflow = new WC_Gateway_PayPal_Pro_Payflow();
            $url = $paypalpro_payflow->testmode ? $paypalpro_payflow->testurl : $paypalpro_payflow->liveurl;
            $post_data = array();
            $post_data['USER'] = $paypalpro_payflow->paypal_user;
            $post_data['VENDOR'] = $paypalpro_payflow->paypal_vendor;
            $post_data['PARTNER'] = $paypalpro_payflow->paypal_partner;
            $post_data['PWD'] = $paypalpro_payflow->paypal_password;
            $post_data['TRXTYPE'] = 'V';
            $post_data['ORIGID'] = $txn_id;
            $response = wp_remote_post($url, array(
                'method' => 'POST',
                'body' => urldecode(http_build_query($post_data, null, '&')),
                'timeout' => 70,
                'user-agent' => 'WooCommerce',
                'httpversion' => '1.1'
            ));
            parse_str($response['body'], $parsed_response);
            if (is_wp_error($response)) {
                $order->add_order_note(__('Unable to void charge!', 'woocommerce-gateway-paypal-pro') . ' ' . $response->get_error_message());
            } elseif ($parsed_response['RESULT'] !== '0') {
                $order->add_order_note(__('Unable to void charge!', 'woocommerce-gateway-paypal-pro') . ' ' . $response->get_error_message());
                $paypalpro_payflow->log('Parsed Response ' . print_r($parsed_response, true));
            } else {
                $order->add_order_note(sprintf(__('PayPal Pro (Payflow) void complete (PNREF: %s)', 'woocommerce-gateway-paypal-pro'), $parsed_response['PNREF']));
                delete_post_meta($order->id, '_paypalpro_charge_captured');
                delete_post_meta($order->id, '_transaction_id');
            }
        }
    }

}