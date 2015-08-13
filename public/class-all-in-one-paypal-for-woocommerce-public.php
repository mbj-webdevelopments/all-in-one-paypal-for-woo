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

    public function woocommerce_paypal_express_init_styles() {
        global $pp_settings;
        $pp_settings = get_option('woocommerce_paypal_express_settings');
        wp_register_script($this->plugin_name . 'mbj_button', plugin_dir_url(__FILE__) . 'js/mbj-button.js', array('jquery'), $this->version, false);
        if (!is_admin() && is_cart()) {
            wp_enqueue_style($this->plugin_name . 'pe_cart', plugin_dir_url(__FILE__) . 'css/cart.css', array(), $this->version, 'all');
            wp_enqueue_script($this->plugin_name . 'mbj_button');
        }
        if (!is_admin() && is_checkout() && @$pp_settings['enabled'] == 'yes' && @$pp_settings['show_on_checkout'] == 'yes')
            wp_enqueue_style($this->plugin_name . 'pe_checkout', plugin_dir_url(__FILE__) . 'css/checkout.css', array(), $this->version, 'all');
        if (!is_admin() && is_single() && @$pp_settings['enabled'] == 'yes' && @$pp_settings['show_on_product_page'] == 'yes') {
            wp_enqueue_style($this->plugin_name . 'pe_single', plugin_dir_url(__FILE__) . 'css/single.css', array(), $this->version, 'all');
            wp_enqueue_script($this->plugin_name . 'mbj_button');
        }
        if (is_page(wc_get_page_id('review_order'))) {
            wp_enqueue_script('wc-checkout', plugins_url('/js/checkout.js', __FILE__), array('jquery'), WC_VERSION, true);
            wp_localize_script('wc-checkout', 'wc_checkout_params', apply_filters('wc_checkout_params', array(
                'ajax_url' => WC()->ajax_url(),
                'ajax_loader_url' => apply_filters('woocommerce_ajax_loader_url', $assets_path . 'images/ajax-loader@2x.gif'),
                'update_order_review_nonce' => wp_create_nonce("update-order-review"),
                'apply_coupon_nonce' => wp_create_nonce("apply-coupon"),
                'option_guest_checkout' => get_option('woocommerce_enable_guest_checkout'),
                'checkout_url' => add_query_arg('action', 'woocommerce_checkout', WC()->ajax_url()),
                'is_checkout' => 1
            )));
        }
    }

    public function woocommerce_product_title($title) {
        $title = str_replace(array("&#8211;", "&#8211"), array("-"), $title);
        return $title;
    }

    public function buy_now_button() {
        global $pp_settings, $post;
        if (@$pp_settings['enabled'] == 'yes' && @$pp_settings['show_on_product_page'] == 'yes') {
            ?>
            <div class="paypal_express_button_single">
                <?php
                $_product = wc_get_product($post->ID);
                $hide = '';
                if ($_product->product_type == 'variation' ||
                        $_product->is_type('external') ||
                        $_product->get_price() == 0 ||
                        $_product->get_price() == '') {
                    $hide = 'display:none;';
                }
                if (empty($pp_settings['checkout_with_pp_button_type']))
                    $pp_settings['checkout_with_pp_button_type'] = 'paypalimage';
                switch ($pp_settings['checkout_with_pp_button_type']) {
                    case "textbutton":
                        if (!empty($pp_settings['pp_button_type_text_button'])) {
                            $button_text = $pp_settings['pp_button_type_text_button'];
                        } else {
                            $button_text = __('Proceed to Checkout', 'woocommerce');
                        }
                        $add_to_cart_action = add_query_arg('express_checkout', '1');
                        echo '<div id="paypal_ec_button_product">';
                        echo '<input data-action="' . $add_to_cart_action . '" type="submit" style="float:left;margin-left:10px;', $hide, '" class="single_variation_wrap_paypal_express paypal_checkout_button button alt" name="express_checkout"  onclick="', "jQuery('form.cart').attr('action','", $add_to_cart_action, "');jQuery('form.cart').submit();", '" value="' . $button_text . '"/>';
                        echo '</div>';
                        echo '<div class="clear"></div>';
                        break;
                    case "paypalimage":
                        $add_to_cart_action = add_query_arg('express_checkout', '1');
                        $button_img = "https://www.paypal.com/" . self::get_button_locale_code() . "/i/btn/btn_xpressCheckout.gif";
                        echo '<div id="paypal_ec_button_product">';
                        echo '<input data-action="' . $add_to_cart_action . '" type="image" src="', $button_img, '" style="float:left;margin-left:10px;', $hide, '" class="single_variation_wrap_paypal_express" name="express_checkout" value="' . __('Pay with PayPal', 'paypal-for-woocommerce') . '"/>';
                        echo '</div>';
                        echo '<div class="clear"></div>';
                        break;
                    case "customimage":
                        $add_to_cart_action = add_query_arg('express_checkout', '1');
                        $button_img = $pp_settings['pp_button_type_my_custom'];
                        echo '<div id="paypal_ec_button_product">';
                        echo '<input data-action="' . $add_to_cart_action . '" type="image" src="', $button_img, '" style="float:left;margin-left:10px;', $hide, '" class="single_variation_wrap_paypal_express" name="express_checkout" value="' . __('Pay with PayPal', 'paypal-for-woocommerce') . '"/>';
                        echo '</div>';
                        echo '<div class="clear"></div>';
                        break;
                }
                ?>
            </div>
            <?php
        }
    }

    public function mini_cart_button() {
        global $pp_settings;
        if (@$pp_settings['enabled'] == 'yes' && (empty($pp_settings['show_on_cart']) || $pp_settings['show_on_cart'] == 'yes') && WC()->cart->cart_contents_count > 0) {
            echo '<div class="paypal_box_button" style="position: relative;">';
            if (empty($pp_settings['checkout_with_pp_button_type']))
                $pp_settings['checkout_with_pp_button_type'] = 'paypalimage';
            switch ($pp_settings['checkout_with_pp_button_type']) {
                case "textbutton":
                    if (!empty($pp_settings['pp_button_type_text_button'])) {
                        $button_text = $pp_settings['pp_button_type_text_button'];
                    } else {
                        $button_text = __('Proceed to Checkout', 'woocommerce');
                    }
                    echo '<a class="paypal_checkout_button button alt" href="' . esc_url(add_query_arg('pp_action', 'expresscheckout', add_query_arg('wc-api', 'All_In_One_Paypal_For_Woocommerce_Admin_WooCommerce_PayPal_Express', home_url('/')))) . '">' . $button_text . '</a>';
                    break;
                case "paypalimage":
                    echo '<div id="paypal_ec_button">';
                    echo '<a class="paypal_checkout_button" href="' . esc_url(add_query_arg('pp_action', 'expresscheckout', add_query_arg('wc-api', 'All_In_One_Paypal_For_Woocommerce_Admin_WooCommerce_PayPal_Express', home_url('/')))) . '">';
                    echo "<img src='https://www.paypal.com/" . self::get_button_locale_code() . "/i/btn/btn_xpressCheckout.gif' border='0' alt='" . __('Pay with PayPal', 'paypal-for-woocommerce') . "'/>";
                    echo "</a>";
                    echo '</div>';
                    break;
                case "customimage":
                    $button_img = $pp_settings['pp_button_type_my_custom'];
                    echo '<div id="paypal_ec_button">';
                    echo '<a class="paypal_checkout_button" href="' . esc_url(add_query_arg('pp_action', 'expresscheckout', add_query_arg('wc-api', 'All_In_One_Paypal_For_Woocommerce_Admin_WooCommerce_PayPal_Express', home_url('/')))) . '">';
                    echo "<img src='{$button_img}' width='150' border='0' alt='" . __('Pay with PayPal', 'paypal-for-woocommerce') . "'/>";
                    echo "</a>";
                    echo '</div>';
                    break;
            }
            if (isset($pp_settings['show_paypal_credit']) && $pp_settings['show_paypal_credit'] == 'yes') {
                $paypal_credit_button_markup = '<div id="paypal_ec_paypal_credit_button">';
                $paypal_credit_button_markup .= '<a class="paypal_checkout_button" href="' . esc_url(add_query_arg('use_paypal_credit', 'true', add_query_arg('pp_action', 'expresscheckout', add_query_arg('wc-api', 'All_In_One_Paypal_For_Woocommerce_Admin_WooCommerce_PayPal_Express', home_url('/'))))) . '" >';
                $paypal_credit_button_markup .= "<img src='https://www.paypalobjects.com/webstatic/en_US/i/buttons/ppcredit-logo-small.png' alt='Check out with PayPal Credit'/>";
                $paypal_credit_button_markup .= '</a>';
                $paypal_credit_button_markup .= '</div>';
                echo $paypal_credit_button_markup;
            }
            ?>
            <div class="blockUI blockOverlay paypal_expressOverlay" style="display:none;z-index: 1000; border: none; margin: 0px; padding: 0px; width: 100%; height: 100%; top: 0px; left: 0px; opacity: 0.6; cursor: default; position: absolute; background: url(<?php echo WC()->plugin_url(); ?>/assets/images/ajax-loader@2x.gif) 50% 50% / 16px 16px no-repeat rgb(255, 255, 255);"></div>
            <script type="text/javascript">
                jQuery(document).ready(function ($) {
                    $(".paypal_checkout_button").click(function () {
                        $(".paypal_expressOverlay").show();
                        return true;
                    });
                });
            </script>
            <?php
            echo "<div class='clear'></div></div>";
        }
    }

    function add_to_cart_redirect($url) {
        if (isset($_REQUEST['express_checkout']) || isset($_REQUEST['express_checkout_x'])) {
            $url = add_query_arg('pp_action', 'expresscheckout', add_query_arg('wc-api', 'All_In_One_Paypal_For_Woocommerce_Admin_WooCommerce_PayPal_Express', home_url('/')));
        }
        return $url;
    }

    public function buy_now_button_js() {
        global $pp_settings;
        if (@$pp_settings['enabled'] == 'yes' && @$pp_settings['show_on_product_page'] == 'yes') {
            ?>
            <script type="text/javascript">
                jQuery(document).ready(function () {
                    jQuery('input.single_variation_wrap_paypal_express').appendTo(".variations_button");
                });
            </script>
            <?php
        }
    }

    public function add_div_before_add_to_cart_button() {
        ?>
        <div class="paypal_express_buton_box_relative" style="position: relative;">
            <?php
        }

        function add_div_after_add_to_cart_button() {
            ?>
            <div class="blockUI blockOverlay paypal_expressOverlay" style="display:none;z-index: 1000; border: none; margin: 0px; padding: 0px; width: 100%; height: 100%; top: 0px; left: 0px; opacity: 0.6; cursor: default; position: absolute; background: url(<?php echo WC()->plugin_url(); ?>/assets/images/ajax-loader@2x.gif) 50% 50% / 16px 16px no-repeat rgb(255, 255, 255);"></div>
        </div>
        <?php
    }

    public static function get_button_locale_code() {
        $locale_code = defined("WPLANG") && WPLANG != '' ? WPLANG : 'en_US';
        switch ($locale_code) {
            case "de_DE": $locale_code = "de_DE/DE";
                break;
        }
        return $locale_code;
    }

    public function get_woocommerce_review_order_paypal_express($atts) {
        global $woocommerce;
        return WC_Shortcodes::shortcode_wrapper(array($this, 'woocommerce_review_order_paypal_express'), $atts);
    }

    public function woocommerce_review_order_paypal_express() {
        global $woocommerce;
        wc_print_notices();
        echo "
			<script>
			jQuery(document).ready(function($) {

                $('form.checkout').unbind( 'submit' );
			});
			</script>
			";
        $template = PEC_PLUGIN_DIR_PATH . 'template/';
        wc_get_template('paypal-review-order.php', array(), '', $template);
        do_action('woocommerce_ppe_checkout_order_review');
    }

}
