<?php

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    All_In_One_Paypal_For_Woocommerce
 * @subpackage All_In_One_Paypal_For_Woocommerce/includes
 * @author     mbj-webdevelopment <mbjwebdevelopment@gmail.com>
 */
class All_In_One_Paypal_For_Woocommerce {

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      All_In_One_Paypal_For_Woocommerce_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct() {

        $this->plugin_name = 'all-in-one-paypal-for-woocommerce';
        $this->version = '1.0.0';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - All_In_One_Paypal_For_Woocommerce_Loader. Orchestrates the hooks of the plugin.
     * - All_In_One_Paypal_For_Woocommerce_i18n. Defines internationalization functionality.
     * - All_In_One_Paypal_For_Woocommerce_Admin. Defines all hooks for the admin area.
     * - All_In_One_Paypal_For_Woocommerce_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {

        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-all-in-one-paypal-for-woocommerce-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-all-in-one-paypal-for-woocommerce-i18n.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-all-in-one-paypal-for-woocommerce-admin.php';

        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-all-in-one-paypal-for-woocommerce-public.php';

        $this->loader = new All_In_One_Paypal_For_Woocommerce_Loader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the All_In_One_Paypal_For_Woocommerce_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale() {

        $plugin_i18n = new All_In_One_Paypal_For_Woocommerce_i18n();
        $plugin_i18n->set_domain($this->get_plugin_name());

        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {

        $plugin_admin = new All_In_One_Paypal_For_Woocommerce_Admin($this->get_plugin_name(), $this->get_version());
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('all_in_one_paypal_for_woocommerce_paypal_digital_goods_check_subscription_status', $plugin_admin, 'all_in_one_paypal_for_woocommerce_paypal_digital_goods_subscription_status', 10, 2);
        $this->loader->add_action('valid-paypal-standard-ipn-request', $plugin_admin, 'all_in_one_paypal_for_woocommerce_paypal_digital_goods_process_ipn_request', 1);
        $this->loader->add_action('wp_ajax_all_in_one_paypal_for_woocommerce_paypal_digital_goods_do_express_checkout', $plugin_admin, 'all_in_one_paypal_for_woocommerce_paypal_digital_goods_ajax_do_express_checkout');
        $this->loader->add_action('wp_ajax_nopriv_all_in_one_paypal_for_woocommerce_paypal_digital_goods_do_express_checkout', $plugin_admin, 'all_in_one_paypal_for_woocommerce_paypal_digital_goods_ajax_do_express_checkout');
        $this->loader->add_action('woocommerce_order_status_on-hold_to_processing', $plugin_admin, 'all_in_one_paypal_for_woocommerce_paypal_pro_capture_payment');
        $this->loader->add_action('woocommerce_order_status_on-hold_to_completed', $plugin_admin, 'all_in_one_paypal_for_woocommerce_paypal_pro_capture_payment');
        $this->loader->add_action('woocommerce_order_status_on-hold_to_cancelled', $plugin_admin, 'all_in_one_paypal_for_woocommerce_paypal_pro_cancel_payment');
        $this->loader->add_action('woocommerce_order_status_on-hold_to_refunded', $plugin_admin, 'all_in_one_paypal_for_woocommerce_paypal_pro_cancel_payment');
        if (is_admin()) {
            $this->loader->add_action('admin_notices', $plugin_admin, 'all_in_one_paypal_for_woocommerce_paypal_pro_ssl_check');
        }
        $this->loader->add_action('admin_init', $plugin_admin, 'all_in_one_paypal_for_woocommerce_paypal_pro_update_ssl_nag');
        $this->loader->add_action('plugins_loaded', $plugin_admin, 'load_plugin_extend_lib');
        $this->loader->add_filter('woocommerce_payment_gateways', $plugin_admin, 'all_in_one_paypal_for_woocommerce_add_gateway', 99, 1);
        $this->loader->add_action('admin_head', $plugin_admin, 'apap_add_validation_script');
        $this->loader->add_action('init', $plugin_admin, 'apap_check_ipn');
        $this->loader->add_action('woocommerce_product_options_general_product_data', $plugin_admin, 'apap_display_product_meta');
        $this->loader->add_action('woocommerce_process_product_meta', $plugin_admin, 'apap_save_product_meta', 10, 1);
        $this->loader->add_action('edit_term', $plugin_admin, 'apap_category_save', 10, 3);
        $this->loader->add_action('created_term', $plugin_admin, 'apap_category_save', 10, 3);
        $this->loader->add_action('woocommerce_checkout_process', $plugin_admin, 'apap_cart_validation_for_rec_limit');
        $this->loader->add_action('product_cat_add_form_fields', $plugin_admin, 'apap_category_new_fields');
        $this->loader->add_action('product_cat_edit_form_fields', $plugin_admin, 'apap_category_edit_fields', 10, 2);
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_filter('woocommerce_paypal_args', $plugin_admin, 'paypal_express_checkout_woocommerce_standard_parameters');
        $this->loader->add_action('admin_notices', $plugin_admin, 'admin_notices');
        $this->loader->add_action('admin_init', $plugin_admin, 'set_ignore_tag');
        $this->loader->add_action('parse_request', $plugin_admin, 'woocommerce_paypal_express_review_order_page_paypal_express');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'onetarek_wpmut_admin_scripts');
        $this->loader->add_action('admin_print_styles', $plugin_admin, 'onetarek_wpmut_admin_styles');
        remove_action('woocommerce_proceed_to_checkout', 'woocommerce_paypal_express_checkout_button', 12);
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks() {

        $plugin_public = new All_In_One_Paypal_For_Woocommerce_Public($this->get_plugin_name(), $this->get_version());
        $this->loader->add_action('get_header', $plugin_public, 'all_in_one_paypal_for_woocommerce_paypal_digital_goods_paypal_return', 11);
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'woocommerce_paypal_express_init_styles', 12);
        $this->loader->add_filter('woocommerce_product_title', $plugin_public, 'woocommerce_product_title');
        $this->loader->add_action('woocommerce_after_add_to_cart_button', $plugin_public, 'buy_now_button');
        $this->loader->add_action('woocommerce_after_mini_cart', $plugin_public, 'mini_cart_button');
        $this->loader->add_action('woocommerce_add_to_cart_redirect', $plugin_public, 'add_to_cart_redirect');
        $this->loader->add_action('woocommerce_after_single_variation', $plugin_public, 'buy_now_button_js');
        $this->loader->add_action('woocommerce_before_add_to_cart_button', $plugin_public, 'add_div_before_add_to_cart_button', 25);
        $this->loader->add_action('woocommerce_after_add_to_cart_button', $plugin_public, 'add_div_after_add_to_cart_button', 35);
        remove_action('init', 'woocommerce_paypal_express_review_order_page');
        remove_shortcode('woocommerce_review_order');
        add_shortcode('woocommerce_review_order', array($plugin_public, 'get_woocommerce_review_order_paypal_express'));
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    All_In_One_Paypal_For_Woocommerce_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }

}
