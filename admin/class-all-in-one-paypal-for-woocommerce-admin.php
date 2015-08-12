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
        $this->load_dependencies();
    }

    private function load_dependencies() {
        /**
         * The class responsible for defining all actions that occur in the Dashboard
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/autoresponder/class-all-in-one-paypal-for-woocommerce-admin-display.php';

        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/autoresponder/class-all-in-one-paypal-for-woocommerce-general-setting.php';

        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/autoresponder/class-all-in-one-paypal-for-woocommerce-html-output.php';
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
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/class-all-in-one-paypal-for-woocommerce-admin-paypal-adaptive-payments.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/class-all-in-one-paypal-for-woocommerce-admin-paypal-express.php';
        require_once( 'partials/lib/paypal-digital-goods/paypal-purchase.class.php' );
        require_once( 'partials/lib/paypal-digital-goods/paypal-subscription.class.php' );
    }

    public function all_in_one_paypal_for_woocommerce_add_gateway($methods) {
        $methods[] = 'All_In_One_Paypal_For_Woocommerce_Admin_WooCommerce_Pro_Hosted';
        $methods[] = 'All_In_One_Paypal_For_Woocommerce_Admin_PayPal_Advanced';
        $methods[] = 'All_In_One_Paypal_For_Woocommerce_Admin_Paypal_Digital_Goods';
        $methods[] = 'All_In_One_Paypal_For_Woocommerce_Admin_WooCommerce_PayPal_Pro_PayFlow';
        $methods[] = 'All_In_One_Paypal_For_Woocommerce_Admin_WooCommerce_PayPal_Pro';
        $methods[] = 'All_In_One_Paypal_For_Woocommerce_Admin_WooCommerce_PayPal_Adaptive_Payments';
        $methods[] = 'All_In_One_Paypal_For_Woocommerce_Admin_WooCommerce_PayPal_Express';
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
            echo '<div class="error"><p>' . sprintf(__('PayPal Pro requires that the <a href="%s">Force secure checkout</a> option is enabled; your checkout may not be secure! Please enable SSL and ensure your server has a valid SSL certificate - PayPal Pro will only work in test mode.', 'all-in-one-paypal-for-woocommerce') . ' <a href="%s">' . __('Hide Notice', 'woocommerce') . '</a>', admin_url('admin.php?page=woocommerce'), wp_nonce_url(add_query_arg('wc_paypal_pro_ssl_nag', '1'), 'wc_paypal_pro_ssl_nag_hide')) . '</p></div>';
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
                $order->add_order_note(__('Unable to capture charge!', 'all-in-one-paypal-for-woocommerce') . ' ' . $response->get_error_message());
            } else {
                parse_str($response['body'], $parsed_response);
                $order->add_order_note(sprintf(__('PayPal Pro charge complete (Transaction ID: %s)', 'all-in-one-paypal-for-woocommerce'), $parsed_response['TRANSACTIONID']));
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
                $order->add_order_note(__('Unable to capture charge!', 'all-in-one-paypal-for-woocommerce') . ' ' . $response->get_error_message());
            } elseif ($parsed_response['RESULT'] !== '0') {
                $order->add_order_note(__('Unable to capture charge!', 'all-in-one-paypal-for-woocommerce'));
                $paypalpro_payflow->log('Parsed Response ' . print_r($parsed_response, true));
            } else {
                $order->add_order_note(sprintf(__('PayPal Pro (Payflow) delay charge complete (PNREF: %s)', 'all-in-one-paypal-for-woocommerce'), $parsed_response['PNREF']));
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
                $order->add_order_note(__('Unable to void charge!', 'all-in-one-paypal-for-woocommerce') . ' ' . $response->get_error_message());
            } else {
                parse_str($response['body'], $parsed_response);
                $order->add_order_note(sprintf(__('PayPal Pro void complete (Authorization ID: %s)', 'all-in-one-paypal-for-woocommerce'), $parsed_response['AUTHORIZATIONID']));
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
                $order->add_order_note(__('Unable to void charge!', 'all-in-one-paypal-for-woocommerce') . ' ' . $response->get_error_message());
            } elseif ($parsed_response['RESULT'] !== '0') {
                $order->add_order_note(__('Unable to void charge!', 'all-in-one-paypal-for-woocommerce') . ' ' . $response->get_error_message());
                $paypalpro_payflow->log('Parsed Response ' . print_r($parsed_response, true));
            } else {
                $order->add_order_note(sprintf(__('PayPal Pro (Payflow) void complete (PNREF: %s)', 'all-in-one-paypal-for-woocommerce'), $parsed_response['PNREF']));
                delete_post_meta($order->id, '_paypalpro_charge_captured');
                delete_post_meta($order->id, '_transaction_id');
            }
        }
    }

    public function apap_add_validation_script() {
        global $woocommerce;
        if (isset($_GET['section'])) {
            if ($_GET['section'] == 'all_in_one_paypal_for_woocommerce_admin_woocommerce_paypal_adaptive_payments') {
                ?>
                <script type="text/javascript">
                    jQuery(document).ready(function () {
                <?php if ((float) $woocommerce->version <= (float) ('2.2.0')) { ?>
                            jQuery('#woocommerce_paypal_adaptive_payment_hide_product_field_user_role').chosen();
                    <?php
                } else {
                    ?>
                            jQuery('#woocommerce_paypal_adaptive_payment_hide_product_field_user_role').select2();
                    <?php
                }
                ?>
                        var currentstatemode = jQuery('#woocommerce_paypal_adaptive_payment__payment_mode').val();
                        if (currentstatemode === 'parallel') {
                            jQuery('#woocommerce_paypal_adaptive_payment__payment_parallel_fees').parent().parent().parent().show();
                            jQuery('#woocommerce_paypal_adaptive_payment__payment_chained_fees').parent().parent().parent().hide();
                        } else {
                            jQuery('#woocommerce_paypal_adaptive_payment__payment_chained_fees').parent().parent().parent().show();
                            jQuery('#woocommerce_paypal_adaptive_payment__payment_parallel_fees').parent().parent().parent().hide();
                        }
                        jQuery('#woocommerce_paypal_adaptive_payment__payment_mode').change(function () {
                            var presentstate = jQuery(this).val();
                            if (presentstate === 'parallel') {
                                jQuery('#woocommerce_paypal_adaptive_payment__payment_parallel_fees').parent().parent().parent().show();
                                jQuery('#woocommerce_paypal_adaptive_payment__payment_chained_fees').parent().parent().parent().hide();
                            } else {
                                jQuery('#woocommerce_paypal_adaptive_payment__payment_chained_fees').parent().parent().parent().show();
                                jQuery('#woocommerce_paypal_adaptive_payment__payment_parallel_fees').parent().parent().parent().hide();
                            }
                        });
                        jQuery('#woocommerce_paypal_adaptive_payment_pri_r_paypal_enable').attr('checked', 'checked');
                        var apap_enable = [];
                        for (var i = 1; i <= 5; i++) {
                            apap_enable[i] = jQuery('#woocommerce_paypal_adaptive_payment_sec_r' + i + '_paypal_enable');
                        }
                        for (var k = 1; k <= 5; k++) {
                            if (apap_enable[k].is(":checked")) {
                                apap_enable[k].parent().parent().parent().parent().next().css('display', 'table-row');
                                apap_enable[k].parent().parent().parent().parent().next().next().css('display', 'table-row');
                            } else {
                                apap_enable[k].parent().parent().parent().parent().next().css('display', 'none');
                                apap_enable[k].parent().parent().parent().parent().next().next().css('display', 'none');
                            }
                        }
                        apap_enable[1].change(function () {
                            if (apap_enable[1].is(":checked")) {
                                apap_enable[1].parent().parent().parent().parent().next().css('display', 'table-row');
                                apap_enable[1].parent().parent().parent().parent().next().next().css('display', 'table-row');
                            } else {
                                apap_enable[1].parent().parent().parent().parent().next().css('display', 'none');
                                apap_enable[1].parent().parent().parent().parent().next().next().css('display', 'none');
                            }
                        });
                        apap_enable[2].change(function () {
                            if (apap_enable[2].is(":checked")) {
                                apap_enable[2].parent().parent().parent().parent().next().css('display', 'table-row');
                                apap_enable[2].parent().parent().parent().parent().next().next().css('display', 'table-row');
                            } else {
                                apap_enable[2].parent().parent().parent().parent().next().css('display', 'none');
                                apap_enable[2].parent().parent().parent().parent().next().next().css('display', 'none');
                            }
                        });
                        apap_enable[3].change(function () {
                            if (apap_enable[3].is(":checked")) {
                                apap_enable[3].parent().parent().parent().parent().next().css('display', 'table-row');
                                apap_enable[3].parent().parent().parent().parent().next().next().css('display', 'table-row');
                            } else {
                                apap_enable[3].parent().parent().parent().parent().next().css('display', 'none');
                                apap_enable[3].parent().parent().parent().parent().next().next().css('display', 'none');
                            }
                        });
                        apap_enable[4].change(function () {
                            if (apap_enable[4].is(":checked")) {
                                apap_enable[4].parent().parent().parent().parent().next().css('display', 'table-row');
                                apap_enable[4].parent().parent().parent().parent().next().next().css('display', 'table-row');
                            } else {
                                apap_enable[4].parent().parent().parent().parent().next().css('display', 'none');
                                apap_enable[4].parent().parent().parent().parent().next().next().css('display', 'none');
                            }
                        });
                        apap_enable[5].change(function () {
                            if (apap_enable[5].is(":checked")) {
                                apap_enable[5].parent().parent().parent().parent().next().css('display', 'table-row');
                                apap_enable[5].parent().parent().parent().parent().next().next().css('display', 'table-row');
                            } else {
                                apap_enable[5].parent().parent().parent().parent().next().css('display', 'none');
                                apap_enable[5].parent().parent().parent().parent().next().next().css('display', 'none');
                            }
                        });
                        function validateEmail(email)
                        {
                            var x = email;
                            var atpos = x.indexOf("@");
                            var dotpos = x.lastIndexOf(".");
                            if (atpos < 1 || dotpos < atpos + 2 || dotpos + 2 >= x.length)
                            {
                                return false;
                            } else {
                                return true;
                            }
                        }
                        jQuery('#mainform').submit(function () {
                            var apap_pri_percent = jQuery('#woocommerce_paypal_adaptive_payment_pri_r_amount_percentage');
                            var apap_mail = [];
                            for (var i = 1; i <= 5; i++) {
                                apap_mail[i] = jQuery('#woocommerce_paypal_adaptive_payment_sec_r' + i + '_paypal_mail');
                            }
                            var apap_percent = [];
                            for (var i = 1; i <= 5; i++) {
                                apap_percent[i] = jQuery('#woocommerce_paypal_adaptive_payment_sec_r' + i + '_amount_percentage');
                            }
                            var apap_total_percent = 0;
                            for (var j = 1; j <= 5; j++) {
                                if (apap_enable[j].is(":checked")) {
                                    if (!validateEmail(apap_mail[j].val())) {
                                        alert("Please Check Email address for enabled Receiver");
                                        return false;
                                    }
                                    if (apap_percent[j].val().length == 0) {
                                        alert("Percentage should not be empty for enabled Receiver");
                                        return false;
                                    } else {
                                        apap_total_percent = apap_total_percent + parseFloat(apap_percent[j].val());
                                    }
                                }
                            }
                            apap_total_percent = apap_total_percent + parseFloat(apap_pri_percent.val());
                            if (apap_total_percent != 100) {
                                alert("The Sum of enabled Receiver percentages should be equal to 100");
                                return false;
                            }
                        });
                    });</script>
                <?php
            }
        }
        if (isset($_GET['taxonomy'])) {
            if ($_GET['taxonomy'] == 'product_cat' && $_GET['post_type'] == 'product') {
                ?>
                <script type="text/javascript">
                    jQuery(document).ready(function () {
                        var apap_enable = [];
                        for (var i = 1; i <= 6; i++) {
                            apap_enable[i] = jQuery('#_apap_rec_' + i + '_enable');
                        }
                        function validateEmail(email)
                        {
                            var x = email;
                            var atpos = x.indexOf("@");
                            var dotpos = x.lastIndexOf(".");
                            if (atpos < 1 || dotpos < atpos + 2 || dotpos + 2 >= x.length)
                            {
                                return false;
                            } else {
                                return true;
                            }
                        }
                        jQuery('#edittag').submit(function () {
                            var apap_mail = [];
                            for (var i = 1; i <= 6; i++) {
                                apap_mail[i] = jQuery('#_apap_rec_' + i + '_mail_id');
                            }
                            var apap_percent = [];
                            for (var i = 1; i <= 6; i++) {
                                apap_percent[i] = jQuery('#_apap_rec_' + i + '_percent');
                            }

                            var apap_total_percent = 0;
                            for (var j = 1; j <= 6; j++) {
                                if (apap_enable[j].is(":checked")) {
                                    if (!validateEmail(apap_mail[j].val())) {
                                        alert("Please Check Email address for enabled Receiver");
                                        return false;
                                    }
                                    if (apap_percent[j].val().length == 0) {
                                        alert("Percentage should not be empty for enabled Receiver");
                                        return false;
                                    } else {
                                        apap_total_percent = apap_total_percent + parseFloat(apap_percent[j].val());
                                    }
                                }
                            }
                            console.log(apap_total_percent);
                            if (apap_total_percent != 100) {
                                alert("The Sum of enabled Receiver percentages should be equal to 100");
                                return false;
                            }
                        });
                    });</script>
                <?php
            }
        }
        if (isset($_GET['action'])) {
            if ($_GET['action'] == 'edit') {
                ?>
                <script type="text/javascript">
                    jQuery(document).ready(function () {
                        jQuery('#_apap_primary_1_enable').attr('checked', 'checked');
                        jQuery('#_apap_primary_1_enable').attr("disabled", true);
                        var apap_enable = [];
                        for (var i = 1; i <= 5; i++) {
                            apap_enable[i] = jQuery('#_apap_sec_' + i + '_enable');
                        }
                        if (jQuery('#_enable_paypal_adaptive_payment').val() != "enable_indiv") {
                            jQuery('.apap_split_indiv').css('display', 'none');
                        } else {
                            jQuery('.apap_split_indiv').css('display', 'block');
                        }
                        jQuery('#_enable_paypal_adaptive_payment').change(function () {
                            if (jQuery(this).val() != "enable_indiv") {
                                jQuery('.apap_split_indiv').css('display', 'none');
                            } else {
                                jQuery('.apap_split_indiv').css('display', 'block');
                            }
                        });
                        for (var k = 1; k <= 5; k++) {
                            if (apap_enable[k].is(":checked")) {
                                apap_enable[k].parent().next().css('display', 'block');
                                apap_enable[k].parent().next().next().css('display', 'block');
                            } else {
                                apap_enable[k].parent().next().css('display', 'none');
                                apap_enable[k].parent().next().next().css('display', 'none');
                            }
                        }
                        apap_enable[1].change(function () {
                            if (apap_enable[1].is(":checked")) {
                                apap_enable[1].parent().next().css('display', 'block');
                                apap_enable[1].parent().next().next().css('display', 'block');
                            } else {
                                apap_enable[1].parent().next().css('display', 'none');
                                apap_enable[1].parent().next().next().css('display', 'none');
                            }
                        });
                        apap_enable[2].change(function () {
                            if (apap_enable[2].is(":checked")) {
                                apap_enable[2].parent().next().css('display', 'block');
                                apap_enable[2].parent().next().next().css('display', 'block');
                            } else {
                                apap_enable[2].parent().next().css('display', 'none');
                                apap_enable[2].parent().next().next().css('display', 'none');
                            }
                        });
                        apap_enable[3].change(function () {
                            if (apap_enable[3].is(":checked")) {
                                apap_enable[3].parent().next().css('display', 'block');
                                apap_enable[3].parent().next().next().css('display', 'block');
                            } else {
                                apap_enable[3].parent().next().css('display', 'none');
                                apap_enable[3].parent().next().next().css('display', 'none');
                            }
                        });
                        apap_enable[4].change(function () {
                            if (apap_enable[4].is(":checked")) {
                                apap_enable[4].parent().next().css('display', 'block');
                                apap_enable[4].parent().next().next().css('display', 'block');
                            } else {
                                apap_enable[4].parent().next().css('display', 'none');
                                apap_enable[4].parent().next().next().css('display', 'none');
                            }
                        });
                        apap_enable[5].change(function () {
                            if (apap_enable[5].is(":checked")) {
                                apap_enable[5].parent().next().css('display', 'block');
                                apap_enable[5].parent().next().next().css('display', 'block');
                            } else {
                                apap_enable[5].parent().next().css('display', 'none');
                                apap_enable[5].parent().next().next().css('display', 'none');
                            }
                        });
                        function validateEmail(email)
                        {
                            var x = email;
                            var atpos = x.indexOf("@");
                            var dotpos = x.lastIndexOf(".");
                            if (atpos < 1 || dotpos < atpos + 2 || dotpos + 2 >= x.length)
                            {
                                return false;
                            } else {
                                return true;
                            }
                        }
                        jQuery('#post').submit(function () {
                            var apap_pri_percent = jQuery('#_apap_primary_rec_percent');
                            var apap_mail = [];
                            for (var i = 1; i <= 5; i++) {
                                apap_mail[i] = jQuery('#_apap_sec_' + i + '_rec_mail_id');
                            }
                            var apap_percent = [];
                            for (var i = 1; i <= 5; i++) {
                                apap_percent[i] = jQuery('#_apap_sec_' + i + '_rec_percent');
                            }
                            var apap_total_percent = 0;
                            if (jQuery('#_enable_paypal_adaptive_payment').length > 0) {
                                if (jQuery('#_enable_paypal_adaptive_payment').val() == 'enable_indiv') {
                                    for (var j = 1; j <= 5; j++) {
                                        if (apap_enable[j].is(":checked")) {
                                            if (!validateEmail(apap_mail[j].val())) {
                                                alert("Please Check Email address for enabled Receiver");
                                                return false;
                                            }
                                            if (apap_percent[j].val().length == 0) {
                                                alert("Percentage should not be empty for enabled Receiver");
                                                return false;
                                            } else {
                                                apap_total_percent = apap_total_percent + parseFloat(apap_percent[j].val());
                                            }
                                        }
                                    }
                                    apap_total_percent = apap_total_percent + parseFloat(apap_pri_percent.val());
                                    if (apap_total_percent != 100) {
                                        alert("The Sum of enabled Receiver percentages should be equal to 100");
                                        return false;
                                    }
                                }
                            }

                        });
                    });
                </script>
                <?php
            }
        }
    }

    public function apap_check_ipn() {
        if (isset($_GET['ipn'])) {
            $paypal_adaptive_payment = new All_In_One_Paypal_For_Woocommerce_Admin_WooCommerce_PayPal_Adaptive_Payments();
            if ("yes" == $paypal_adaptive_payment->testmode) {
                $paypal_ipn_url = "https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_notify-validate";
            } elseif ("no" == $paypal_adaptive_payment->testmode) {
                $paypal_ipn_url = "https://www.paypal.com/cgi-bin/webscr?cmd=_notify-validate";
            }
            $ipn_post = !empty($_POST) ? $_POST : false;
            if ($ipn_post) {
                header('HTTP/1.1 200 OK');
                $self_custom = $_GET['self_custom'];
                $received_post = file_get_contents("php://input");
                $posted_response = wp_remote_request($paypal_ipn_url, array('method' => 'POST', 'timeout' => 20, 'body' => $received_post));
                $received_raw_post_array = explode('&', $received_post);
                $post_maded = array();
                foreach ($received_raw_post_array as $keyval) {
                    $keyval = explode('=', $keyval);
                    if (count($keyval) == 2)
                        $post_maded[urldecode($keyval[0])] = urldecode($keyval[1]);
                }
                if (strcmp($posted_response['body'], "VERIFIED") == 0) {
                    $received_order_id = $self_custom;
                    $payment_status = $post_maded['transaction[0].status'];
                    if ($payment_status == 'Completed') {
                        $order = new WC_Order($received_order_id);
                        if (isset($order->id)) {
                            $total = 0;
                            if ($paypal_adaptive_payment->get_option('_payment_mode') == 'parallel') {
                                for ($i = 0; $i <= 5; $i++) {
                                    if (isset($post_maded["transaction[$i].amount"])) {
                                        $total = $total + preg_replace("/[^0-9,.]/", "", $post_maded["transaction[$i].amount"]);
                                    }
                                }
                            } else {
                                $total = preg_replace("/[^0-9,.]/", "", $post_maded["transaction[0].amount"]);
                            }
                            if ($total == $order->order_total) {
                                $order->payment_complete();
                            }
                            update_post_meta($order->id, 'Transaction ID', $post_maded['transaction[0].id']);
                        }
                    }
                }
            }
        }
    }

    public function apap_display_product_meta() {
        global $woocommerce, $post;
        $currency_label = get_woocommerce_currency_symbol();
        $paypal_adaptive_payment = new All_In_One_Paypal_For_Woocommerce_Admin_WooCommerce_PayPal_Adaptive_Payments();
        $gethidedroles = $paypal_adaptive_payment->settings['hide_product_field_user_role'];
        $getcurrentuser = wp_get_current_user();
        $getcurrentroles = $getcurrentuser->roles;
        $array_intersect_roles = array_intersect((array) $gethidedroles, (array) $getcurrentroles);
        if ($array_intersect_roles) {
            echo '<div class="options_group" style="display:none;">';
        } else {
            echo '<div class="options_group">';
        }
        woocommerce_wp_select(
                array(
                    'id' => '_enable_paypal_adaptive_payment',
                    'label' => __('Adaptive Payment', 'all-in-one-paypal-for-woocommerce'),
                    'options' => array(
                        'disable' => __('Use Global Settings', 'all-in-one-paypal-for-woocommerce'),
                        'enable_category' => __('Use Category Settings', 'all-in-one-paypal-for-woocommerce'),
                        'enable_indiv' => __('Use Product Settings', 'all-in-one-paypal-for-woocommerce'),
                    )
                )
        );
        woocommerce_wp_checkbox(
                array(
                    'id' => '_apap_primary_1_enable',
                    'wrapper_class' => 'apap_split_indiv',
                    'label' => __('Enable Receiver 1', 'all-in-one-paypal-for-woocommerce'),
                    'description' => __('Enable Receiver 1', 'all-in-one-paypal-for-woocommerce')
                )
        );
        woocommerce_wp_text_input(
                array(
                    'id' => '_apap_primary_rec_mail_id',
                    'wrapper_class' => 'apap_split_indiv',
                    'label' => __('Receiver 1 PayPal Mail', 'all-in-one-paypal-for-woocommerce'),
                    'placeholder' => 'Receiver 1 PayPal Mail',
                    'desc_tip' => 'true',
                    'description' => __('Enter Receiver 1 PayPal Mail', 'all-in-one-paypal-for-woocommerce')
                )
        );
        woocommerce_wp_text_input(
                array(
                    'id' => '_apap_primary_rec_percent',
                    'wrapper_class' => 'apap_split_indiv',
                    'label' => __('Receiver 1 Payment Percentage', 'all-in-one-paypal-for-woocommerce'),
                    'placeholder' => 'Receiver 1 Payment Percentage',
                    'desc_tip' => 'true',
                    'description' => __('Enter Receiver 1 Payment Percentage', 'all-in-one-paypal-for-woocommerce')
                )
        );
        woocommerce_wp_checkbox(
                array(
                    'id' => '_apap_sec_1_enable',
                    'wrapper_class' => 'apap_split_indiv',
                    'label' => __('Enable Receiver 2', 'all-in-one-paypal-for-woocommerce'),
                    'description' => __('Enable Receiver 2', 'all-in-one-paypal-for-woocommerce')
                )
        );
        woocommerce_wp_text_input(
                array(
                    'id' => '_apap_sec_1_rec_mail_id',
                    'wrapper_class' => 'apap_split_indiv',
                    'label' => __('Receiver 2 PayPal Mail', 'all-in-one-paypal-for-woocommerce'),
                    'placeholder' => 'Receiver 2 PayPal Mail',
                    'desc_tip' => 'true',
                    'description' => __('Enter Receiver 2 PayPal Mail', 'all-in-one-paypal-for-woocommerce')
                )
        );
        woocommerce_wp_text_input(
                array(
                    'id' => '_apap_sec_1_rec_percent',
                    'wrapper_class' => 'apap_split_indiv',
                    'label' => __('Receiver 2 Payment Percentage', 'all-in-one-paypal-for-woocommerce'),
                    'placeholder' => 'Receiver 2 Payment Percentage',
                    'desc_tip' => 'true',
                    'description' => __('Enter Receiver 2 Payment Percentage', 'all-in-one-paypal-for-woocommerce')
                )
        );
        woocommerce_wp_checkbox(
                array(
                    'id' => '_apap_sec_2_enable',
                    'wrapper_class' => 'apap_split_indiv',
                    'label' => __('Enable Receiver 3', 'all-in-one-paypal-for-woocommerce'),
                    'description' => __('Enable Receiver 3', 'all-in-one-paypal-for-woocommerce')
                )
        );
        woocommerce_wp_text_input(
                array(
                    'id' => '_apap_sec_2_rec_mail_id',
                    'wrapper_class' => 'apap_split_indiv',
                    'label' => __('Receiver 3 PayPal Mail', 'all-in-one-paypal-for-woocommerce'),
                    'placeholder' => 'Receiver 3 PayPal Mail',
                    'desc_tip' => 'true',
                    'description' => __('Enter Receiver 3 PayPal Mail', 'all-in-one-paypal-for-woocommerce')
                )
        );
        woocommerce_wp_text_input(
                array(
                    'id' => '_apap_sec_2_rec_percent',
                    'wrapper_class' => 'apap_split_indiv',
                    'label' => __('Receiver 3 Payment Percentage', 'all-in-one-paypal-for-woocommerce'),
                    'placeholder' => 'Receiver 3 Payment Percentage',
                    'desc_tip' => 'true',
                    'description' => __('Enter Receiver 3 Payment Percentage', 'all-in-one-paypal-for-woocommerce')
                )
        );
        woocommerce_wp_checkbox(
                array(
                    'id' => '_apap_sec_3_enable',
                    'wrapper_class' => 'apap_split_indiv',
                    'label' => __('Enable Receiver 4', 'all-in-one-paypal-for-woocommerce'),
                    'description' => __('Enable Receiver 4', 'all-in-one-paypal-for-woocommerce')
                )
        );
        woocommerce_wp_text_input(
                array(
                    'id' => '_apap_sec_3_rec_mail_id',
                    'wrapper_class' => 'apap_split_indiv',
                    'label' => __('Receiver 4 PayPal Mail', 'all-in-one-paypal-for-woocommerce'),
                    'placeholder' => 'Receiver 4 PayPal Mail',
                    'desc_tip' => 'true',
                    'description' => __('Enter Receiver 4 PayPal Mail', 'all-in-one-paypal-for-woocommerce')
                )
        );
        woocommerce_wp_text_input(
                array(
                    'id' => '_apap_sec_3_rec_percent',
                    'wrapper_class' => 'apap_split_indiv',
                    'label' => __('Receiver 4 Payment Percentage', 'all-in-one-paypal-for-woocommerce'),
                    'placeholder' => 'Receiver 4 Payment Percentage',
                    'desc_tip' => 'true',
                    'description' => __('Enter Receiver 4 Payment Percentage', 'all-in-one-paypal-for-woocommerce')
                )
        );
        woocommerce_wp_checkbox(
                array(
                    'id' => '_apap_sec_4_enable',
                    'wrapper_class' => 'apap_split_indiv',
                    'label' => __('Enable Receiver 5', 'all-in-one-paypal-for-woocommerce'),
                    'description' => __('Enable Receiver 5', 'all-in-one-paypal-for-woocommerce')
                )
        );
        woocommerce_wp_text_input(
                array(
                    'id' => '_apap_sec_4_rec_mail_id',
                    'wrapper_class' => 'apap_split_indiv',
                    'label' => __('Receiver 5 PayPal Mail', 'all-in-one-paypal-for-woocommerce'),
                    'placeholder' => 'Receiver 5 PayPal Mail',
                    'desc_tip' => 'true',
                    'description' => __('Enter Receiver 5 PayPal Mail', 'all-in-one-paypal-for-woocommerce')
                )
        );
        woocommerce_wp_text_input(
                array(
                    'id' => '_apap_sec_4_rec_percent',
                    'wrapper_class' => 'apap_split_indiv',
                    'label' => __('Receiver 5 Payment Percentage', 'all-in-one-paypal-for-woocommerce'),
                    'placeholder' => 'Receiver 5 Payment Percentage',
                    'desc_tip' => 'true',
                    'description' => __('Enter Receiver 5 Payment Percentage', 'all-in-one-paypal-for-woocommerce')
                )
        );
        woocommerce_wp_checkbox(
                array(
                    'id' => '_apap_sec_5_enable',
                    'wrapper_class' => 'apap_split_indiv',
                    'label' => __('Enable Receiver 6', 'all-in-one-paypal-for-woocommerce'),
                    'description' => __('Enable Receiver 6', 'all-in-one-paypal-for-woocommerce')
                )
        );
        woocommerce_wp_text_input(
                array(
                    'id' => '_apap_sec_5_rec_mail_id',
                    'wrapper_class' => 'apap_split_indiv',
                    'label' => __('Receiver 6 PayPal Mail', 'all-in-one-paypal-for-woocommerce'),
                    'placeholder' => 'Receiver 6 PayPal Mail',
                    'desc_tip' => 'true',
                    'description' => __('Enter Receiver 6 PayPal Mail', 'all-in-one-paypal-for-woocommerce')
                )
        );
        woocommerce_wp_text_input(
                array(
                    'id' => '_apap_sec_5_rec_percent',
                    'wrapper_class' => 'apap_split_indiv',
                    'label' => __('Receiver 6 Payment Percentage', 'all-in-one-paypal-for-woocommerce'),
                    'placeholder' => 'Receiver 6 Payment Percentage',
                    'desc_tip' => 'true',
                    'description' => __('Enter Receiver 6 Payment Percentage', 'all-in-one-paypal-for-woocommerce')
                )
        );

        echo '</div>';
    }

    public function apap_save_product_meta($post_id) {
        $primary_rec = $_POST['_apap_primary_rec_mail_id'];
        $primary_rec_percent = $_POST['_apap_primary_rec_percent'];
        for ($i = 1; $i <= 5; $i++) {
            ${'sec_rec_' . $i . 'mail'} = $_POST['_apap_sec_' . $i . '_rec_mail_id'];
            ${'sec_rec_' . $i . '_percent'} = $_POST['_apap_sec_' . $i . '_rec_percent'];
        }
        if (!empty($primary_rec)) {
            update_post_meta($post_id, '_apap_primary_rec_mail_id', esc_attr($primary_rec));
        }
        if (!empty($primary_rec_percent)) {
            update_post_meta($post_id, '_apap_primary_rec_percent', esc_attr($primary_rec_percent));
        }
        for ($i = 1; $i <= 5; $i++) {
            if (!empty(${'sec_rec_' . $i . 'mail'})) {
                update_post_meta($post_id, '_apap_sec_' . $i . '_rec_mail_id', esc_attr(${'sec_rec_' . $i . 'mail'}));
            }
            if (!empty(${'sec_rec_' . $i . '_percent'})) {
                update_post_meta($post_id, '_apap_sec_' . $i . '_rec_percent', esc_attr(${'sec_rec_' . $i . '_percent'}));
            }
            $enable_sec_rec = isset($_POST['_apap_sec_' . $i . '_enable']) ? 'yes' : 'no';
            update_post_meta($post_id, '_apap_sec_' . $i . '_enable', $enable_sec_rec);
        }
        $fp_adaptive_enable = isset($_POST['_enable_paypal_adaptive_payment']) ? 'yes' : 'no';
        update_post_meta($post_id, '_enable_paypal_adaptive_payment', esc_attr($fp_adaptive_enable));

        $fp_adaptive_select = $_POST['_enable_paypal_adaptive_payment'];
        if (!empty($fp_adaptive_select)) {
            update_post_meta($post_id, '_enable_paypal_adaptive_payment', esc_attr($fp_adaptive_select));
        }
    }

    public function apap_category_save($term_id, $tt_id, $taxonomy) {
        for ($i = 1; $i <= 6; $i++) {
            if (isset($_POST['_apap_rec_' . $i . '_mail_id'])) {
                update_woocommerce_term_meta($term_id, '_apap_rec_' . $i . '_mail_id', esc_attr($_POST['_apap_rec_' . $i . '_mail_id']));
            }
            if (isset($_POST['_apap_rec_' . $i . '_percent'])) {
                update_woocommerce_term_meta($term_id, '_apap_rec_' . $i . '_percent', esc_attr($_POST['_apap_rec_' . $i . '_percent']));
            }
            $enable_sec_rec = isset($_POST['_apap_rec_' . $i . '_enable']) ? 'yes' : 'no';
            update_woocommerce_term_meta($term_id, '_apap_rec_' . $i . '_enable', $enable_sec_rec);
        }
    }

    public function apap_cart_validation_for_rec_limit() {
        global $woocommerce;
        $count = 0;
        $receivers = array();
        foreach ($woocommerce->cart->get_cart() as $items) {
            if ("enable_indiv" == get_post_meta($items['product_id'], "_enable_paypal_adaptive_payment", true)) {
                if (!in_array(get_post_meta($items['product_id'], "_apap_primary_rec_mail_id", true), $receivers)) {
                    $receivers[] = get_post_meta($items['product_id'], "_apap_primary_rec_mail_id", true);
                    $count = $count + 1;
                }
                for ($i = 1; $i <= 5; $i++) {
                    if ("yes" == get_post_meta($items['product_id'], '_apap_sec_' . $i . '_enable', true)) {
                        if (!in_array(get_post_meta($items['product_id'], '_apap_sec_' . $i . '_rec_mail_id', true), $receivers)) {
                            $receivers[] = get_post_meta($items['product_id'], '_apap_sec_' . $i . '_rec_mail_id', true);
                            $count++;
                        }
                    }
                }
            } elseif ("enable_category" == get_post_meta($items['product_id'], "_enable_paypal_adaptive_payment", true)) {
                $apap_product_category = wp_get_post_terms($items['product_id'], 'product_cat');
                $categ_meta = get_metadata('woocommerce_term', $apap_product_category[0]->term_id);
                for ($i = 1; $i <= 6; $i++) {
                    if ("yes" == $categ_meta['_apap_rec_' . $i . '_enable'][0]) {
                        if (!in_array($categ_meta['_apap_rec_' . $i . '_mail_id'][0], $receivers)) {
                            $receivers[] = $categ_meta['_apap_rec_' . $i . '_mail_id'][0];
                            $count++;
                        }
                    }
                }
            } elseif (("disable" == get_post_meta($items['product_id'], "_enable_paypal_adaptive_payment", true)) || (" " == get_post_meta($items['product_id'], "_enable_paypal_adaptive_payment", true))) {
                $apap = new All_In_One_Paypal_For_Woocommerce_Admin_WooCommerce_PayPal_Adaptive_Payments();
                if (!in_array($apap->get_option('pri_r_paypal_mail'), $receivers)) {
                    $receivers[] = $apap->get_option('pri_r_paypal_mail');
                    $count++;
                }
                for ($i = 1; $i <= 5; $i++) {
                    if ("yes" == $apap->get_option('sec_r' . $i . '_paypal_enable')) {
                        if (!in_array($apap->get_option('sec_r' . $i . '_paypal_mail'), $receivers)) {
                            $receivers[] = $apap->get_option('sec_r' . $i . '_paypal_mail');
                            $count++;
                        }
                    }
                }
            }
        }
        if ($count > 6) {
            wc_add_notice('Please change or reduce cart products to make a successful sale. As it reached more than 6 paypal receivers', 'error');
        } else {
            
        }
    }

    public function apap_category_new_fields() {
        $term = '';
        for ($i = 1; $i <= 6; $i++) {
            ?>
            <div class = "form-field">
            <?php
            if (isset($term->term_id)) {
                $term_value = $term->term_id;
            } else {
                $term_value = "";
            }
            ?>
                <label for = "<?php echo 'receiver_' . $i . ''; ?>">Enable Receiver <?php echo $i; ?></label>
                <input style="width: auto;" id = "<?php echo '_apap_rec_' . $i . '_enable'; ?>" type = "checkbox" aria-required = "false" size = "40" value = "<?php echo get_woocommerce_term_meta($term_value, '_apap_rec_' . $i . '_enable', true); ?>"<?php checked("yes", get_woocommerce_term_meta($term_value, '_apap_rec_' . $i . '_enable', true)); ?> name = "<?php echo '_apap_rec_' . $i . '_enable'; ?>">
                <p class = "description">Enable Receiver <?php echo $i; ?></p>
            </div>
            <div class = "form-field">
                <label for = "<?php echo 'receiver_' . $i . '_mail'; ?>">Receiver <?php echo $i; ?> Email</label>
                <input id = "<?php echo '_apap_rec_' . $i . '_mail_id'; ?>" type = "text" aria-required = "false" size = "40" value = "<?php echo get_woocommerce_term_meta($term_value, '_apap_rec_' . $i . '_mail_id', true); ?>" name = "<?php echo '_apap_rec_' . $i . '_mail_id'; ?>">
                <p class = "description">Receiver <?php echo $i; ?> Mail.</p>
            </div>
            <div class = "form-field">
                <label for = "<?php echo 'receiver_' . $i . '_percent'; ?>">Receiver <?php echo $i; ?> Payment Percentage</label>
                <input id = "<?php echo '_apap_rec_' . $i . '_percent'; ?>" type = "text" aria-required = "false" size = "40" value = "<?php echo get_woocommerce_term_meta($term_value, '_apap_rec_' . $i . '_percent', true); ?>" name = "<?php echo '_apap_rec_' . $i . '_percent'; ?>">
                <p class = "description">Receiver <?php echo $i; ?> Payment Percentage</p>
            </div>
            <?php
        }
    }

    public function apap_category_edit_fields($term, $taxonomy) {
        for ($i = 1; $i <= 6; $i++) {
            ?>
            <tr class = "form-field">
                <th scope = "row">
                    <label for = "<?php echo 'receiver_' . $i . ''; ?>">Enable Receiver <?php echo $i; ?></label>
                </th>
                <td align="left">
                    <input style="width: auto;" id = "<?php echo '_apap_rec_' . $i . '_enable'; ?>" type = "checkbox" aria-required = "false" size = "40" value = "<?php echo get_woocommerce_term_meta($term->term_id, '_apap_rec_' . $i . '_enable', true); ?>"<?php checked("yes", get_woocommerce_term_meta($term->term_id, '_apap_rec_' . $i . '_enable', true)); ?> name = "<?php echo '_apap_rec_' . $i . '_enable'; ?>">
                    <p class = "description">Enable Receiver <?php echo $i; ?></p>
                </td>
            </tr>
            <tr class = "form-field">
                <th scope = "row">
                    <label for = "<?php echo 'receiver_' . $i . '_mail'; ?>">Receiver <?php echo $i; ?> Email</label>
                </th>
                <td>
                    <input id = "<?php echo '_apap_rec_' . $i . '_mail_id'; ?>" type = "text" aria-required = "false" size = "40" value = "<?php echo get_woocommerce_term_meta($term->term_id, '_apap_rec_' . $i . '_mail_id', true); ?>" name = "<?php echo '_apap_rec_' . $i . '_mail_id'; ?>">
                    <p class = "description">Receiver <?php echo $i; ?> Mail.</p>
                </td>
            </tr>
            <tr class = "form-field">
                <th scope = "row">
                    <label for = "<?php echo 'receiver_' . $i . '_percent'; ?>">Receiver <?php echo $i; ?> Payment Percentage</label>
                </th>
                <td>
                    <input id = "<?php echo '_apap_rec_' . $i . '_percent'; ?>" type = "text" aria-required = "false" size = "40" value = "<?php echo get_woocommerce_term_meta($term->term_id, '_apap_rec_' . $i . '_percent', true); ?>" name = "<?php echo '_apap_rec_' . $i . '_percent'; ?>">
                    <p class = "description">Receiver <?php echo $i; ?> Payment Percentage</p>
                </td>
            </tr>
            <?php
        }
    }

    public function admin_notices() {
        global $current_user, $pp_settings;
        $user_id = $current_user->ID;
        $pp_standard = get_option('woocommerce_paypal_settings');
        do_action('mbj_admin_notices', $pp_standard);
        if (@$pp_settings['enabled'] == 'yes' && @$pp_standard['enabled'] == 'yes' && !get_user_meta($user_id, 'ignore_pp_check')) {
            echo '<div class="error"><p>' . sprintf(__('You currently have both PayPal (standard) and Express Checkout enabled.  It is recommended that you disable the standard PayPal from <a href="' . get_admin_url() . 'admin.php?page=wc-settings&tab=checkout&section=wc_gateway_paypal">the settings page</a> when using Express Checkout. | <a href=%s>%s</a>', 'paypal-for-woocommerce'), '"' . add_query_arg("ignore_pp_check", 0) . '"', __("Hide this notice", 'paypal-for-woocommerce')) . '</p></div>';
        }
        if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins'))) && !get_user_meta($user_id, 'ignore_pp_woo') && !is_plugin_active_for_network('woocommerce/woocommerce.php')) {
            echo '<div class="error"><p>' . sprintf(__("WooCommerce PayPal Payments requires WooCommerce plugin to work normally. Please activate it or install it from <a href='http://wordpress.org/plugins/woocommerce/' target='_blank'>here</a>. | <a href=%s>%s</a>", 'paypal-for-woocommerce'), '"' . add_query_arg("ignore_pp_woo", 0) . '"', __("Hide this notice", 'paypal-for-woocommerce')) . '</p></div>';
        }
    }

    public function set_ignore_tag() {
        global $current_user;
        $plugin = plugin_basename(__FILE__);
        $plugin_data = get_plugin_data(__FILE__, false);
        if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
            deactivate_plugins(PEC_PLUGIN_DIR . '/paypal-express-checkout-woocommerce.php');
            wp_die("<strong>PayPal Pro for WooCommerce</strong> requires <strong>WooCommerce</strong> plugin to work normally. Please activate it or install it.<br /><br />Back to the WordPress <a href='" . get_admin_url(null, 'plugins.php') . "'>Plugins page</a>.");
        }
        $user_id = $current_user->ID;
        $notices = array('ignore_pp_ssl', 'ignore_pp_sandbox', 'ignore_pp_woo', 'ignore_pp_check', 'ignore_pp_donate');
        foreach ($notices as $notice)
            if (isset($_GET[$notice]) && '0' == $_GET[$notice]) {
                add_user_meta($user_id, $notice, 'true', true);
            }
    }

    public function woocommerce_paypal_express_review_order_page_paypal_express() {
        if (!empty($_GET['pp_action']) && $_GET['pp_action'] == 'revieworder') {
            $woocommerce_ppe = new All_In_One_Paypal_For_Woocommerce_Admin_WooCommerce_PayPal_Express();
            $woocommerce_ppe->paypal_express_checkout();
        }
    }

    public function onetarek_wpmut_admin_scripts() {
        $dir = plugin_dir_path(__FILE__);
        wp_enqueue_media();
        wp_enqueue_script('jquery');
    }

    public function onetarek_wpmut_admin_styles() {
        wp_enqueue_style('thickbox');
    }

}
