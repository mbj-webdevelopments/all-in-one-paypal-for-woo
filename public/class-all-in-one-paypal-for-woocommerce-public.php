<?php

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    All_In_One_Paypal_For_Woocommerce
 * @subpackage All_In_One_Paypal_For_Woocommerce/public
 * @author     mbj-webdevelopment <mbjwebdevelopment@gmail.com>
 */
class All_In_One_Paypal_For_Woocommerce_Public {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of the plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version) {

        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/all-in-one-paypal-for-woocommerce-public.css', array(), $this->version, 'all');
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/all-in-one-paypal-for-woocommerce-public.js', array('jquery'), $this->version, false);
    }

    public function all_in_one_paypal_for_woocommerce_paypal_digital_goods_paypal_return() {
        global $woocommerce, $wp;
        if (!isset($_GET['all_in_one_paypal_for_woocommerce_paypal_digital_goods'])) {
            return;
        }
        $all_in_one_paypal_for_woocommerce_paypal_digital_goods_gateway = new All_In_One_Paypal_For_Woocommerce_Admin_Paypal_Digital_Goods();
        $is_paying = ( 'paid' == $_GET['all_in_one_paypal_for_woocommerce_paypal_digital_goods'] ) ? true : false;
        unset($_GET['all_in_one_paypal_for_woocommerce_paypal_digital_goods']);
        if (isset($wp->query_vars['order-received'])) {
            $order_id = $_GET['all_in_one_paypal_for_woocommerce_paypal_digital_goods_order'] = $wp->query_vars['order-received'];
        } elseif (isset($_GET['order_id'])) {
            $order_id = $_GET['all_in_one_paypal_for_woocommerce_paypal_digital_goods_order'] = $_GET['order_id'];
        } else {
            $order_id = $_GET['all_in_one_paypal_for_woocommerce_paypal_digital_goods_order'] = $_GET['order'];
        }
        $order = new WC_Order($order_id);
        $paypal_object = $all_in_one_paypal_for_woocommerce_paypal_digital_goods_gateway->get_paypal_object($order->id);
        wp_register_style('all_in_one_paypal_for_woocommerce_paypal_digital_goods-iframe', plugins_url('/css/all_in_one_paypal_for_woocommerce_paypal_digital_goods-iframe.css', __FILE__));
        wp_register_script('all_in_one_paypal_for_woocommerce_paypal_digital_goods-return', plugins_url('/js/all_in_one_paypal_for_woocommerce_paypal_digital_goods-return.js', __FILE__), 'jquery');
        $all_in_one_paypal_for_woocommerce_paypal_digital_goods_params = array(
            'ajaxUrl' => (!is_ssl() ) ? str_replace('https', 'http', admin_url('admin-ajax.php')) : admin_url('admin-ajax.php'),
            'queryString' => http_build_query($_GET),
            'msgWaiting' => __("This won't take a minute", 'all-in-one-paypal-for-woocommerce'),
            'msgComplete' => __('Payment Processed', 'all-in-one-paypal-for-woocommerce'),
        );
        wp_localize_script('all_in_one_paypal_for_woocommerce_paypal_digital_goods-return', 'all_in_one_paypal_for_woocommerce_paypal_digital_goods', $all_in_one_paypal_for_woocommerce_paypal_digital_goods_params);
        ?>
        <html>
            <head>
                <title><?php __('Processing...', 'all-in-one-paypal-for-woocommerce'); ?></title>
                <?php wp_print_styles('all_in_one_paypal_for_woocommerce_paypal_digital_goods-iframe'); ?>
                <?php if ($is_paying) : ?>
                    <?php wp_print_scripts('jquery'); ?>
                    <?php wp_print_scripts('all_in_one_paypal_for_woocommerce_paypal_digital_goods-return'); ?>
                <?php endif; ?>
                <meta name="viewport" content="width=device-width">
            </head>
            <body>
                <div id="left_frame">
                    <div id="right_frame">
                        <p id="message">
                            <?php if ($is_paying) { ?>
                                <?php _e('Processing payment', 'all-in-one-paypal-for-woocommerce'); ?>
                                <?php $location = remove_query_arg(array('all_in_one_paypal_for_woocommerce_paypal_digital_goods', 'token', 'PayerID')); ?>
                            <?php } else { ?>
                                <?php _e('Cancelling Order', 'all-in-one-paypal-for-woocommerce'); ?>
                                <?php $location = html_entity_decode($order->get_cancel_order_url()); ?>
                            <?php } ?>
                        </p>
                        <img src="https://www.paypal.com/en_US/i/icon/icon_animated_prog_42wx42h.gif" alt="Processing..." />
                        <div id="right_bottom">
                            <div id="left_bottom">
                            </div>
                        </div>
                    </div>
                </div>
                <?php if (!$is_paying) : ?>
                    <script type="text/javascript">
                        setTimeout('if (window!=top) {top.location.replace("<?php echo $location; ?>");}else{location.replace("<?php echo $location; ?>");}', 1500);
                    </script>
                <?php endif; ?>
            </body>
        </html>
        <?php
        exit();
    }

}