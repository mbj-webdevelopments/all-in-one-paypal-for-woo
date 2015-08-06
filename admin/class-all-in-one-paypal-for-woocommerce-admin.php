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
    }

    public function all_in_one_paypal_for_woocommerce_add_gateway($methods) {
        $methods[] = 'All_In_One_Paypal_For_Woocommerce_Admin_WooCommerce_Pro_Hosted';
        $methods[] = 'All_In_One_Paypal_For_Woocommerce_Admin_PayPal_Advanced';
        return $methods;
    }

}
