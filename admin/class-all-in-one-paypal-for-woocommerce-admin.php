<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://localleadminer.com/
 * @since      1.0.0
 *
 * @package    All_In_One_Paypal_For_Woocommerce
 * @subpackage All_In_One_Paypal_For_Woocommerce/admin
 */

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
        require_once( 'partials/lib/paypal-digital-goods/paypal-purchase.class.php' );
        require_once( 'partials/lib/paypal-digital-goods/paypal-subscription.class.php' );
        
    }

    public function all_in_one_paypal_for_woocommerce_add_gateway($methods) {
        $methods[] = 'All_In_One_Paypal_For_Woocommerce_Admin_WooCommerce_Pro_Hosted';
        $methods[] = 'All_In_One_Paypal_For_Woocommerce_Admin_PayPal_Advanced';
        $methods[] = 'All_In_One_Paypal_For_Woocommerce_Admin_Paypal_Digital_Goods';
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
}
