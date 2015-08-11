<?php
global $woocommerce, $pp_settings;
$pp_settings = get_option('woocommerce_paypal_express_settings');
class All_In_One_Paypal_For_Woocommerce_Admin_WooCommerce_PayPal_Express extends WC_Payment_Gateway {
    public function __construct() {
        $this->id = 'paypal_express';
        $this->method_title = __('PayPal Express Checkout ', 'paypal-for-woocommerce');
        $this->method_description = __('PayPal Express Checkout is designed to make the checkout experience for buyers using PayPal much more quick and easy than filling out billing and shipping forms.  Customers will be taken directly to PayPal to sign in and authorize the payment, and are then returned back to your store to choose a shipping method, review the final order total, and complete the payment.', 'paypal-for-woocommerce');
        $this->has_fields = false;
        $this->supports = array(
            'products',
            'refunds'
        );
        $this->init_form_fields();
        $this->init_settings();
        $this->enabled = $this->settings['enabled'];
        $this->title = $this->settings['title'];
        $this->description = $this->settings['description'];
        $this->api_username = $this->settings['api_username'];
        $this->api_password = $this->settings['api_password'];
        $this->api_signature = $this->settings['api_signature'];
        $this->testmode = $this->settings['testmode'];
        $this->debug = $this->settings['debug'];
        $this->error_email_notify = isset($this->settings['error_email_notify']) && $this->settings['error_email_notify'] == 'yes' ? true : false;
        $this->invoice_id_prefix = isset($this->settings['invoice_id_prefix']) ? $this->settings['invoice_id_prefix'] : '';
        $this->show_on_checkout = isset($this->settings['show_on_checkout']) ? $this->settings['show_on_checkout'] : 'both';
        $this->paypal_account_optional = isset($this->settings['paypal_account_optional']) ? $this->settings['paypal_account_optional'] : '';
        $this->error_display_type = isset($this->settings['error_display_type']) ? $this->settings['error_display_type'] : '';
        $this->landing_page = isset($this->settings['landing_page']) ? $this->settings['landing_page'] : '';
        $this->checkout_logo = isset($this->settings['checkout_logo']) ? $this->settings['checkout_logo'] : '';
        $this->checkout_logo_hdrimg = isset($this->settings['checkout_logo_hdrimg']) ? $this->settings['checkout_logo_hdrimg'] : '';
        $this->show_paypal_credit = isset($this->settings['show_paypal_credit']) ? $this->settings['show_paypal_credit'] : '';
        $this->brand_name = isset($this->settings['brand_name']) ? $this->settings['brand_name'] : '';
        $this->customer_service_number = isset($this->settings['customer_service_number']) ? $this->settings['customer_service_number'] : '';
        $this->use_wp_locale_code = isset($this->settings['use_wp_locale_code']) ? $this->settings['use_wp_locale_code'] : '';
        $this->paypal_express_skip_text = isset($this->settings['paypal_express_skip_text']) ? $this->settings['paypal_express_skip_text'] : '';
        $this->skip_final_review = isset($this->settings['skip_final_review']) ? $this->settings['skip_final_review'] : '';
        $this->payment_action = isset($this->settings['payment_action']) ? $this->settings['payment_action'] : 'Sale';
        $this->billing_address = isset($this->settings['billing_address']) ? $this->settings['billing_address'] : 'no';
        $this->send_items = isset($this->settings['send_items']) && $this->settings['send_items'] == 'yes' ? true : false;
        $this->customer_id = get_current_user_id();
        if ($this->testmode == 'yes') {
            $this->API_Endpoint = "https://api-3t.sandbox.paypal.com/nvp";
            $this->PAYPAL_URL = "https://www.sandbox.paypal.com/webscr?cmd=_express-checkout&token=";
            $this->api_username = $this->settings['sandbox_api_username'];
            $this->api_password = $this->settings['sandbox_api_password'];
            $this->api_signature = $this->settings['sandbox_api_signature'];
        } else {
            $this->API_Endpoint = "https://api-3t.paypal.com/nvp";
            $this->PAYPAL_URL = "https://www.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=";
        }
        $this->version = "64";
        add_action('woocommerce_api_all_in_one_paypal_for_woocommerce_admin_woocommerce_paypal_express', array($this, 'paypal_express_checkout'));
        add_action('woocommerce_receipt_paypal_express', array($this, 'receipt_page'));
        add_action('woocommerce_update_options_payment_gateways', array($this, 'process_admin_options'));
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        if ($this->enabled == 'yes' && ($this->show_on_checkout == 'top' || $this->show_on_checkout == 'both')) {
            add_action('woocommerce_before_checkout_form', array($this, 'checkout_message'), 5);
        }
        add_action('woocommerce_ppe_do_payaction', array($this, 'get_confirm_order'));
        add_action('woocommerce_after_checkout_validation', array($this, 'regular_checkout'));
        add_action('woocommerce_before_cart_table', array($this, 'top_cart_button'));
    }

    public function get_icon() {
        $image_path = WP_PLUGIN_URL . "/" . plugin_basename(dirname(dirname(__FILE__))) . '/partials/images/paypal.png';
        if ($this->show_paypal_credit == 'yes') {
            $image_path = WP_PLUGIN_URL . "/" . plugin_basename(dirname(dirname(__FILE__))) . '/partials/images/paypal-credit.png';
        }
        $icon = "<img src=\"$image_path\" alt='" . __('Pay with PayPal', 'paypal-for-woocommerce') . "'/>";
        return apply_filters('woocommerce_gateway_icon', $icon, $this->id);
    }

    public function admin_options() {
        ?>
        <h3><?php echo isset($this->method_title) ? $this->method_title : __('Settings', 'paypal-for-woocommerce'); ?></h3>
        <?php echo isset($this->method_description) ? wpautop($this->method_description) : ''; ?>
        <table class="form-table">
            <?php $this->generate_settings_html(); ?>
        </table>
        <?php
        $this->scriptAdminOption();
    }

    public function scriptAdminOption() {
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function ($) {
                $("#woocommerce_paypal_express_customer_service_number").attr("maxlength", "16");
                if ($("#woocommerce_paypal_express_checkout_with_pp_button_type").val() == "customimage") {
                    jQuery('.form-table tr td #woocommerce_paypal_express_pp_button_type_my_custom').each(function (i, el) {
                        jQuery(el).closest('tr').show();
                    });
                } else {
                    jQuery('.form-table tr td #woocommerce_paypal_express_pp_button_type_my_custom').each(function (i, el) {
                        jQuery(el).closest('tr').hide();
                    });
                }
                if ($("#woocommerce_paypal_express_checkout_with_pp_button_type").val() == "textbutton") {
                    jQuery('.form-table tr td #woocommerce_paypal_express_pp_button_type_text_button').each(function (i, el) {
                        jQuery(el).closest('tr').show();
                    });
                } else {
                    jQuery('.form-table tr td #woocommerce_paypal_express_pp_button_type_text_button').each(function (i, el) {
                        jQuery(el).closest('tr').hide();
                    });
                }
                $("#woocommerce_paypal_express_checkout_with_pp_button_type").change(function () {
                    if ($(this).val() == "customimage") {
                        jQuery('.form-table tr td #woocommerce_paypal_express_pp_button_type_my_custom').each(function (i, el) {
                            jQuery(el).closest('tr').show();
                        });
                        jQuery('.form-table tr td #woocommerce_paypal_express_pp_button_type_text_button').each(function (i, el) {
                            jQuery(el).closest('tr').hide();
                        });
                    } else if ($(this).val() == "textbutton") {
                        jQuery('.form-table tr td #woocommerce_paypal_express_pp_button_type_text_button').each(function (i, el) {
                            jQuery(el).closest('tr').show();
                        });
                        jQuery('.form-table tr td #woocommerce_paypal_express_pp_button_type_my_custom').each(function (i, el) {
                            jQuery(el).closest('tr').hide();
                        });
                    } else {
                        jQuery('.form-table tr td #woocommerce_paypal_express_pp_button_type_my_custom').each(function (i, el) {
                            jQuery(el).closest('tr').hide();
                        });
                        jQuery('.form-table tr td #woocommerce_paypal_express_pp_button_type_text_button').each(function (i, el) {
                            jQuery(el).closest('tr').hide();
                        });
                    }
                });
                jQuery("#woocommerce_paypal_express_pp_button_type_my_custom").css({float: "left"});
                jQuery("#woocommerce_paypal_express_pp_button_type_my_custom").after('<a href="#" id="upload" class="button_upload button">Upload</a>');
        <?php if ($this->is_ssl()) { ?>
                    jQuery("#woocommerce_paypal_express_checkout_logo").after('<a href="#" id="checkout_logo" class="button_upload button">Upload</a>');
                    jQuery("#woocommerce_paypal_express_checkout_logo_hdrimg").after('<a href="#" id="checkout_logo_hdrimg" class="button_upload button">Upload</a>');
            <?php
        }
        ?>
                var custom_uploader;
                $('.button_upload').click(function (e) {
                    var BTthis = jQuery(this);
                    e.preventDefault();
                    custom_uploader = wp.media.frames.file_frame = wp.media({
                        title: '<?php _e('Choose Image', 'paypal-for-woocommerce'); ?>',
                        button: {
                            text: '<?php _e('Choose Image', 'paypal-for-woocommerce'); ?>'
                        },
                        multiple: false
                    });
                    custom_uploader.on('select', function () {
                        var attachment = custom_uploader.state().get('selection').first().toJSON();
                        var pre_input = BTthis.prev();
                        var url = attachment.url;
                        if (BTthis.attr('id') != 'upload') {
                            if (attachment.url.indexOf('http:') > -1) {
                                url = url.replace('http', 'https');
                            }
                        }
                        pre_input.val(url);
                    });
                    custom_uploader.open();
                });
            });
        </script>
        <?php
    }

    public function get_confirm_order($order) {
        $this->confirm_order_id = $order->id;
    }

    function is_available() {
        if ($this->enabled == 'yes' && ( $this->show_on_checkout == 'regular' || $this->show_on_checkout == 'both'))
            return true;
        return false;
    }

    function add_log($message) {
        if ($this->debug == 'yes') {
            if (empty($this->log))
                $this->log = new WC_Logger();
            $this->log->add('paypal_express', $message);
        }
    }

    function is_ssl() {
        if (is_ssl() || get_option('woocommerce_force_ssl_checkout') == 'yes' || class_exists('WordPressHTTPS'))
            return true;
        return false;
    }

    function init_form_fields() {
        $require_ssl = '';
        if (!$this->is_ssl()) {
            $require_ssl = __('This image requires an SSL host.  Please upload your image to <a target="_blank" href="http://www.sslpic.com">www.sslpic.com</a> and enter the image URL here.', 'paypal-for-woocommerce');
        }
        $woocommerce_enable_guest_checkout = get_option('woocommerce_enable_guest_checkout');
        if (isset($woocommerce_enable_guest_checkout) && ( $woocommerce_enable_guest_checkout === "no" )) {
            $skip_final_review_option_not_allowed = ' (This is not available because your WooCommerce orders require an account.)';
        } else {
            $skip_final_review_option_not_allowed = '';
        }
        $args = array(
            'sort_order' => 'ASC',
            'sort_column' => 'post_title',
            'hierarchical' => 1,
            'exclude' => '',
            'include' => '',
            'meta_key' => '',
            'meta_value' => '',
            'authors' => '',
            'child_of' => 0,
            'parent' => -1,
            'exclude_tree' => '',
            'number' => '',
            'offset' => 0,
            'post_type' => 'page',
            'post_status' => 'publish'
        );
        $pages = get_pages($args);
        $cancel_page = array();
        foreach ($pages as $p) {
            $cancel_page[$p->ID] = $p->post_title;
        }
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'paypal-for-woocommerce'),
                'label' => __('Enable PayPal Express', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'description' => '',
                'default' => 'no'
            ),
            'title' => array(
                'title' => __('Title', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'paypal-for-woocommerce'),
                'default' => __('PayPal Express', 'paypal-for-woocommerce')
            ),
            'description' => array(
                'title' => __('Description', 'paypal-for-woocommerce'),
                'type' => 'textarea',
                'description' => __('This controls the description which the user sees during checkout.', 'paypal-for-woocommerce'),
                'default' => __("Pay via PayPal; you can pay with your credit card if you don't have a PayPal account", 'paypal-for-woocommerce')
            ),
            'sandbox_api_username' => array(
                'title' => __('Sandbox API User Name', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('Create sandbox accounts and obtain API credentials from within your <a href="http://developer.paypal.com">PayPal developer account</a>.', 'paypal-for-woocommerce'),
                'default' => ''
            ),
            'sandbox_api_password' => array(
                'title' => __('Sandbox API Password', 'paypal-for-woocommerce'),
                'type' => 'password',
                'default' => ''
            ),
            'sandbox_api_signature' => array(
                'title' => __('Sandbox API Signature', 'paypal-for-woocommerce'),
                'type' => 'password',
                'default' => ''
            ),
            'api_username' => array(
                'title' => __('Live API User Name', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('Get your live account API credentials from your PayPal account profile under the API Access section <br />or by using <a target="_blank" href="https://www.paypal.com/us/cgi-bin/webscr?cmd=_login-api-run">this tool</a>.', 'paypal-for-woocommerce'),
                'default' => ''
            ),
            'api_password' => array(
                'title' => __('Live API Password', 'paypal-for-woocommerce'),
                'type' => 'password',
                'default' => ''
            ),
            'api_signature' => array(
                'title' => __('Live API Signature', 'paypal-for-woocommerce'),
                'type' => 'password',
                'default' => ''
            ),
            'testmode' => array(
                'title' => __('PayPal Sandbox', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable PayPal Sandbox', 'paypal-for-woocommerce'),
                'default' => 'yes',
                'description' => __('The sandbox is PayPal\'s test environment and is only for use with sandbox accounts created within your <a href="http://developer.paypal.com" target="_blank">PayPal developer account</a>.', 'paypal-for-woocommerce')
            ),
            'debug' => array(
                'title' => __('Debug', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable logging <code>/wp-content/uploads/wc-logs/paypal_express-{tag}.log</code>', 'paypal-for-woocommerce'),
                'default' => 'no'
            ),
            'error_email_notify' => array(
                'title' => __('Error Email Notifications', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable admin email notifications for errors.', 'paypal-for-woocommerce'),
                'default' => 'yes',
                'description' => __('This will send a detailed error email to the WordPress site administrator if a PayPal API error occurs.', 'paypal-for-woocommerce')
            ),
            'invoice_id_prefix' => array(
                'title' => __('Invoice ID Prefix', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('Add a prefix to the invoice ID sent to PayPal. This can resolve duplicate invoice problems when working with multiple websites on the same PayPal account.', 'paypal-for-woocommerce'),
            ),
            'checkout_with_pp_button_type' => array(
                'title' => __('Checkout Button Type', 'paypal-for-woocommerce'),
                'type' => 'select',
                'label' => __('Use Checkout with PayPal image button', 'paypal-for-woocommerce'),
                'class' => 'checkout_with_pp_button_type',
                'options' => array(
                    'paypalimage' => __('PayPal Image', 'paypal-for-woocommerce'),
                    'textbutton' => __('Text Button', 'paypal-for-woocommerce'),
                    'customimage' => __('Custom Image', 'paypal-for-woocommerce')
                )
            ),
            'pp_button_type_my_custom' => array(
                'title' => __('Select Image', 'paypal-for-woocommerce'),
                'type' => 'text',
                'label' => __('Use Checkout with PayPal image button', 'paypal-for-woocommerce'),
                'class' => 'pp_button_type_my_custom',
            ),
            'pp_button_type_text_button' => array(
                'title' => __('Custom Text', 'paypal-for-woocommerce'),
                'type' => 'text',
                'class' => 'pp_button_type_text_button',
                'default' => 'Proceed to Checkout',
            ),
            'show_on_cart' => array(
                'title' => __('Cart Page', 'paypal-for-woocommerce'),
                'label' => __('Show Express Checkout button on shopping cart page.', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'default' => 'yes'
            ),
            'button_position' => array(
                'title' => __('Cart Button Position', 'paypal-for-woocommerce'),
                'label' => __('Where to display PayPal Express Checkout button(s).', 'paypal-for-woocommerce'),
                'description' => __('Set where to display the PayPal Express Checkout button(s).'),
                'type' => 'select',
                'options' => array(
                    'top' => 'At the top, above the shopping cart details.',
                    'bottom' => 'At the bottom, below the shopping cart details.',
                    'both' => 'Both at the top and bottom, above and below the shopping cart details.'
                ),
                'default' => 'bottom'
            ),
            'show_on_checkout' => array(
                'title' => __('Checkout Page Display', 'paypal-for-woocommerce'),
                'type' => 'select',
                'options' => array(
                    'no' => __("Do not display on checkout page.", 'paypal-for-woocommerce'),
                    'top' => __('Display at the top of the checkout page.', 'paypal-for-woocommerce'),
                    'regular' => __('Display in general list of enabled gatways on checkout page.', 'paypal-for-woocommerce'),
                    'both' => __('Display both at the top and in the general list of gateways on the checkout page.')),
                'default' => 'top',
                'description' => __('Displaying the checkout button at the top of the checkout page will allow users to skip filling out the forms and can potentially increase conversion rates.')
            ),
            'show_on_product_page' => array(
                'title' => __('Product Page', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Show the Express Checkout button on product detail pages.', 'paypal-for-woocommerce'),
                'default' => 'no',
                'description' => __('Allows customers to checkout using PayPal directly from a product page.', 'paypal-for-woocommerce')
            ),
            'paypal_account_optional' => array(
                'title' => __('PayPal Account Optional', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Allow customers to checkout without a PayPal account using their credit card.', 'paypal-for-woocommerce'),
                'default' => 'no',
                'description' => __('PayPal Account Optional must be turned on in your PayPal account profile under Website Preferences.', 'paypal-for-woocommerce')
            ),
            'landing_page' => array(
                'title' => __('Landing Page', 'paypal-for-woocommerce'),
                'type' => 'select',
                'description' => __('Type of PayPal page to display as default. PayPal Account Optional must be checked for this option to be used.', 'paypal-for-woocommerce'),
                'options' => array('login' => __('Login', 'paypal-for-woocommerce'),
                    'billing' => __('Billing', 'paypal-for-woocommerce')),
                'default' => 'login',
            ),
            'error_display_type' => array(
                'title' => __('Error Display Type', 'paypal-for-woocommerce'),
                'type' => 'select',
                'label' => __('Display detailed or generic errors', 'paypal-for-woocommerce'),
                'class' => 'error_display_type_option',
                'options' => array(
                    'detailed' => __('Detailed', 'paypal-for-woocommerce'),
                    'generic' => __('Generic', 'paypal-for-woocommerce')
                ),
                'description' => __('Detailed displays actual errors returned from PayPal.  Generic displays general errors that do not reveal details
									and helps to prevent fraudulant activity on your site.', 'paypal-for-woocommerce')
            ),
            'show_paypal_credit' => array(
                'title' => __('Enable PayPal Credit', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Show the PayPal Credit button next to the Express Checkout button.', 'paypal-for-woocommerce'),
                'default' => 'yes'
            ),
            'use_wp_locale_code' => array(
                'title' => __('Use WordPress Locale Code', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Pass the WordPress Locale Code setting to PayPal in order to display localized PayPal pages to buyers.', 'paypal-for-woocommerce'),
                'default' => 'yes'
            ),
            'brand_name' => array(
                'title' => __('Brand Name', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('This controls what users see as the brand / company name on PayPal review pages.', 'paypal-for-woocommerce'),
                'default' => __(get_bloginfo('name'), 'paypal-for-woocommerce')
            ),
            'checkout_logo' => array(
                'title' => __('PayPal Checkout Logo (190x90px)', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('This controls what users see as the logo on PayPal review pages. ', 'paypal-for-woocommerce') . $require_ssl,
                'default' => ''
            ),
            'checkout_logo_hdrimg' => array(
                'title' => __('PayPal Checkout Banner (750x90px)', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('This controls what users see as the header banner on PayPal review pages. ', 'paypal-for-woocommerce') . $require_ssl,
                'default' => ''
            ),
            'customer_service_number' => array(
                'title' => __('Customer Service Number', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('This controls what users see for your customer service phone number on PayPal review pages.', 'paypal-for-woocommerce'),
                'default' => ''
            ),
            'paypal_express_skip_text' => array(
                'title' => __('Express Checkout Message', 'paypal-for-woocommerce'),
                'type' => 'text',
                'description' => __('This message will be displayed next to the PayPal Express Checkout button at the top of the checkout page.'),
                'default' => __('Skip the forms and pay faster with PayPal!', 'paypal-for-woocommerce')
            ),
            'skip_final_review' => array(
                'title' => __('Skip Final Review', 'paypal-for-woocommerce'),
                'label' => __('Enables the option to skip the final review page.', 'paypal-for-woocommerce'),
                'description' => __('By default, users will be returned from PayPal and presented with a final review page which includes shipping and tax in the order details.  Enable this option to eliminate this page in the checkout process.' . $skip_final_review_option_not_allowed),
                'type' => 'checkbox',
                'default' => 'no'
            ),
            'payment_action' => array(
                'title' => __('Payment Action', 'paypal-for-woocommerce'),
                'label' => __('Whether to process as a Sale or Authorization.', 'paypal-for-woocommerce'),
                'description' => __('Sale will capture the funds immediately when the order is placed.  Authorization will authorize the payment but will not capture the funds.  You would need to capture funds through your PayPal account when you are ready to deliver.'),
                'type' => 'select',
                'options' => array(
                    'Sale' => 'Sale',
                    'Authorization' => 'Authorization',
                ),
                'default' => 'Sale'
            ),
            'billing_address' => array(
                'title' => __('Billing Address', 'paypal-for-woocommerce'),
                'label' => __('Set billing address in WooCommerce using the address returned by PayPal.', 'paypal-for-woocommerce'),
                'description' => __('PayPal only returns a shipping address back to the website.  Enable this option if you would like to use this address for both billing and shipping in WooCommerce.'),
                'type' => 'checkbox',
                'default' => 'no'
            ),
            'cancel_page' => array(
                'title' => __('Cancel Page', 'paypal-for-woocommerce'),
                'description' => __('Sets the page users will be returned to if they click the Cancel link on the PayPal checkout pages.'),
                'type' => 'select',
                'options' => $cancel_page,
            ),
            'send_items' => array(
                'title' => __('Send Item Details', 'paypal-for-woocommerce'),
                'label' => __('Send line item details to PayPal.', 'paypal-for-woocommerce'),
                'type' => 'checkbox',
                'description' => __('Include all line item details in the payment request to PayPal so that they can be seen from the PayPal transaction details page.', 'paypal-for-woocommerce'),
                'default' => 'yes'
            ),
        );
        $this->form_fields = apply_filters('paypal_express_ec_form_fields', $this->form_fields);
    }

    function checkout_message() {
        global $pp_settings;
        if (WC()->cart->total > 0) {
            wp_enqueue_script('paypal_express_button');
            $payment_gateways = WC()->payment_gateways->get_available_payment_gateways();
            unset($payment_gateways['paypal_pro']);
            unset($payment_gateways['paypal_pro_payflow']);
            echo '<div id="checkout_paypal_message" class="woocommerce-info info">';
            echo '<div id="paypal_box_button">';
            if (empty($pp_settings['checkout_with_pp_button_type']))
                $pp_settings['checkout_with_pp_button_type'] = 'paypalimage';
            switch ($pp_settings['checkout_with_pp_button_type']) {
                case "textbutton":
                    if (!empty($pp_settings['pp_button_type_text_button'])) {
                        $button_text = $pp_settings['pp_button_type_text_button'];
                    } else {
                        $button_text = __('Proceed to Checkout', 'woocommerce');
                    }
                    echo '<a class="paypal_checkout_button paypal_checkout_button_text button alt" href="' . esc_url(add_query_arg('pp_action', 'expresscheckout', add_query_arg('wc-api', 'All_In_One_Paypal_For_Woocommerce_Admin_WooCommerce_PayPal_Express', home_url('/')))) . '">' . $button_text . '</a>';
                    break;
                case "paypalimage":
                    echo '<div id="paypal_ec_button">';
                    echo '<a class="paypal_checkout_button" href="' . esc_url(add_query_arg('pp_action', 'expresscheckout', add_query_arg('wc-api', 'All_In_One_Paypal_For_Woocommerce_Admin_WooCommerce_PayPal_Express', home_url('/')))) . '">';
                    echo "<img src='https://www.paypal.com/" . All_In_One_Paypal_For_Woocommerce_Admin_WooCommerce_PayPal_Express::get_button_locale_code() . "/i/btn/btn_xpressCheckout.gif' border='0' alt='" . __('Pay with PayPal', 'paypal-for-woocommerce') . "'/>";
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
            if ($this->show_paypal_credit == 'yes') {
                $paypal_credit_button_markup = '<div id="paypal_ec_paypal_credit_button">';
                $paypal_credit_button_markup .= '<a class="paypal_checkout_button" href="' . esc_url(add_query_arg('use_paypal_credit', 'true', add_query_arg('pp_action', 'expresscheckout', add_query_arg('wc-api', 'All_In_One_Paypal_For_Woocommerce_Admin_WooCommerce_PayPal_Express', home_url('/'))))) . '" >';
                $paypal_credit_button_markup .= "<img src='https://www.paypalobjects.com/webstatic/en_US/i/buttons/ppcredit-logo-small.png' alt='Check out with PayPal Credit'/>";
                $paypal_credit_button_markup .= '</a>';
                $paypal_credit_button_markup .= '</div>';
                echo $paypal_credit_button_markup;
            }
            echo '<div class="woocommerce_paypal_ec_checkout_message">';
            if (!isset($this->settings['paypal_express_skip_text'])) {
                echo '<p class="checkoutStatus">', __('Skip the forms and pay faster with PayPal!', 'paypal-for-woocommerce'), '</p>';
            } else {
                echo '<p class="checkoutStatus">', $this->paypal_express_skip_text, '</p>';
            }
            echo '</div>';
            echo '<div class="clear"></div></div>';
            ?>
            <div class="blockUI blockOverlay paypal_expressOverlay" style="display:none;z-index: 1000; border: none; margin: 0px; padding: 0px; width: 100%; height: 100%; top: 0px; left: 0px; opacity: 0.6; cursor: default; position: absolute; background: url(<?php echo WC()->plugin_url(); ?>/partials/images/ajax-loader@2x.gif) 50% 50% / 16px 16px no-repeat rgb(255, 255, 255);"></div>
            <?php
            echo '</div>';
            echo '<div style="clear:both; margin-bottom:10px;"></div>';
        }
    }

    function paypal_express_checkout($posted = null) {
        if (!empty($posted) || ( isset($_GET['pp_action']) && $_GET['pp_action'] == 'expresscheckout' )) {
            if (sizeof(WC()->cart->get_cart()) > 0) {
                if (!defined('WOOCOMMERCE_CHECKOUT'))
                    define('WOOCOMMERCE_CHECKOUT', true);
                $this->add_log('Start Express Checkout');
                if (isset($_GET['use_paypal_credit']) && 'true' == $_GET['use_paypal_credit']) {
                    $usePayPalCredit = true;
                } else {
                    $usePayPalCredit = false;
                }
                WC()->cart->calculate_totals();
                $paymentAmount = number_format(WC()->cart->total, 2, '.', '');
                $review_order_page_url = get_permalink(wc_get_page_id('review_order'));
                if (!$review_order_page_url) {
                    $this->add_log(__('Review Order Page not found, re-create it. ', 'paypal-for-woocommerce'));
                    include_once( WC()->plugin_path() . '/includes/admin/wc-admin-functions.php' );
                    $page_id = wc_create_page(esc_sql(_x('review-order', 'page_slug', 'woocommerce')), 'woocommerce_review_order_page_id', __('Checkout &rarr; Review Order', 'paypal-for-woocommerce'), '[woocommerce_review_order]', wc_get_page_id('checkout'));
                    $review_order_page_url = get_permalink($page_id);
                }
                $returnURL = urlencode(add_query_arg('pp_action', 'revieworder', $review_order_page_url));
                $cancelURL = isset($this->settings['cancel_page']) ? get_the_permalink($this->settings['cancel_page']) : WC()->cart->get_cart_url();
                $cancelURL = apply_filters('paypal_express_express_cancel_url', urlencode($cancelURL));
                $resArray = $this->CallSetExpressCheckout($paymentAmount, $returnURL, $cancelURL, $usePayPalCredit, $posted);
                $ack = strtoupper($resArray["ACK"]);
                if ($ack == "SUCCESS" || $ack == "SUCCESSWITHWARNING") {
                    $this->add_log('Redirecting to PayPal');
                    if (is_ajax()) {
                        $result = array(
                            'redirect' => $resArray['REDIRECTURL'],
                            'result' => 'success'
                        );
                        echo '<!--WC_START-->' . json_encode($result) . '<!--WC_END-->';
                        exit;
                    } else {
                        wp_redirect($resArray['REDIRECTURL']);
                        exit;
                    }
                } else {
                    $ErrorCode = urldecode($resArray["L_ERRORCODE0"]);
                    $ErrorShortMsg = urldecode($resArray["L_SHORTMESSAGE0"]);
                    $ErrorLongMsg = urldecode($resArray["L_LONGMESSAGE0"]);
                    $ErrorSeverityCode = urldecode($resArray["L_SEVERITYCODE0"]);
                    $this->add_log(__('SetExpressCheckout API call failed. ', 'paypal-for-woocommerce'));
                    $this->add_log(__('Detailed Error Message: ', 'paypal-for-woocommerce') . $ErrorLongMsg);
                    $this->add_log(__('Short Error Message: ', 'paypal-for-woocommerce') . $ErrorShortMsg);
                    $this->add_log(__('Error Code: ', 'paypal-for-woocommerce') . $ErrorCode);
                    $this->add_log(__('Error Severity Code: ', 'paypal-for-woocommerce') . $ErrorSeverityCode);
                    $message = '';
                    if ($this->error_email_notify) {
                        $admin_email = get_option("admin_email");
                        $message .= __("SetExpressCheckout API call failed.", "paypal-for-woocommerce") . "\n\n";
                        $message .= __('Error Code: ', 'paypal-for-woocommerce') . $ErrorCode . "\n";
                        $message .= __('Error Severity Code: ', 'paypal-for-woocommerce') . $ErrorSeverityCode . "\n";
                        $message .= __('Short Error Message: ', 'paypal-for-woocommerce') . $ErrorShortMsg . "\n";
                        $message .= __('Detailed Error Message: ', 'paypal-for-woocommerce') . $ErrorLongMsg . "\n";
                        $error_email_notify_mes = apply_filters('paypal_express_ec_error_email_notify_message', $message, $ErrorCode, $ErrorSeverityCode, $ErrorShortMsg, $ErrorLongMsg);
                        $subject = "PayPal Express Checkout Error Notification";
                        $error_email_notify_subject = apply_filters('paypal_express_ec_error_email_notify_subject', $subject);
                        wp_mail($admin_email, $error_email_notify_subject, $error_email_notify_mes);
                    }
                    if ($this->error_display_type == 'detailed') {
                        $sec_error_notice = $ErrorCode . ' - ' . $ErrorLongMsg;
                        $error_display_type_message = sprintf(__($sec_error_notice, 'paypal-for-woocommerce'));
                    } else {
                        $error_display_type_message = sprintf(__('There was a problem paying with PayPal.  Please try another method.', 'paypal-for-woocommerce'));
                    }
                    $error_display_type_message = apply_filters('paypal_express_ec_display_type_message', $error_display_type_message, $ErrorCode, $ErrorLongMsg);
                    wc_add_notice($error_display_type_message, 'error');
                    if (!is_ajax()) {
                        wp_redirect(get_permalink(wc_get_page_id('cart')));
                        exit;
                    } else
                        return;
                }
            }
        }
        elseif (isset($_GET['pp_action']) && $_GET['pp_action'] == 'revieworder') {
            wc_clear_notices();
            if (!defined('WOOCOMMERCE_CHECKOUT')) {
                define('WOOCOMMERCE_CHECKOUT', true);
            }
            $this->add_log('Start Review Order');
            if (isset($_GET['token'])) {
                $token = $_GET['token'];
                $this->set_session('TOKEN', $token);
            }
            if (isset($_GET['PayerID'])) {
                $payerID = $_GET['PayerID'];
                $this->set_session('PayerID', $payerID);
            }
            $this->add_log("...Token:" . $this->get_session('TOKEN'));
            $this->add_log("...PayerID: " . $this->get_session('PayerID'));
            $result = $this->CallGetShippingDetails($this->get_session('TOKEN'));
            if (!empty($result)) {
                $this->set_session('RESULT', serialize($result));
                if (isset($result['SHIPTOCOUNTRYCODE'])) {
                    if (!array_key_exists($result['SHIPTOCOUNTRYCODE'], WC()->countries->get_allowed_countries())) {
                        wc_add_notice(sprintf(__('We do not sell in your country, please try again with another address.', 'paypal-for-woocommerce')), 'error');
                        wp_redirect(get_permalink(wc_get_page_id('cart')));
                        exit;
                    };
                    WC()->customer->set_shipping_country($result['SHIPTOCOUNTRYCODE']);
                }
                if (isset($result['SHIPTONAME']))
                    WC()->customer->shiptoname = $result['SHIPTONAME'];
                if (isset($result['SHIPTOSTREET']))
                    WC()->customer->set_address($result['SHIPTOSTREET']);
                if (isset($result['SHIPTOCITY']))
                    WC()->customer->set_city($result['SHIPTOCITY']);
                if (isset($result['SHIPTOCOUNTRYCODE']))
                    WC()->customer->set_country($result['SHIPTOCOUNTRYCODE']);
                if (isset($result['SHIPTOSTATE']))
                    WC()->customer->set_state($this->get_state_code($result['SHIPTOCOUNTRYCODE'], $result['SHIPTOSTATE']));
                if (isset($result['SHIPTOZIP']))
                    WC()->customer->set_postcode($result['SHIPTOZIP']);
                if (isset($result['SHIPTOSTATE']))
                    WC()->customer->set_shipping_state($this->get_state_code($result['SHIPTOCOUNTRYCODE'], $result['SHIPTOSTATE']));
                if (isset($result['SHIPTOZIP']))
                    WC()->customer->set_shipping_postcode($result['SHIPTOZIP']);
                $this->set_session('company', isset($result['BUSINESS']) ? $result['BUSINESS'] : '');
                $this->set_session('firstname', isset($result['FIRSTNAME']) ? $result['FIRSTNAME'] : '');
                $this->set_session('lastname', isset($result['LASTNAME']) ? $result['LASTNAME'] : '');
                $this->set_session('shiptoname', isset($result['SHIPTONAME']) ? $result['SHIPTONAME'] : '');
                $this->set_session('shiptostreet', isset($result['SHIPTOSTREET']) ? $result['SHIPTOSTREET'] : '');
                $this->set_session('shiptostreet2', isset($result['SHIPTOSTREET2']) ? $result['SHIPTOSTREET2'] : '');
                $this->set_session('shiptocity', isset($result['SHIPTOCITY']) ? $result['SHIPTOCITY'] : '');
                $this->set_session('shiptocountrycode', isset($result['SHIPTOCOUNTRYCODE']) ? $result['SHIPTOCOUNTRYCODE'] : '');
                $this->set_session('shiptostate', isset($result['SHIPTOSTATE']) ? $result['SHIPTOSTATE'] : '');
                $this->set_session('shiptozip', isset($result['SHIPTOZIP']) ? $result['SHIPTOZIP'] : '');
                $this->set_session('payeremail', isset($result['EMAIL']) ? $result['EMAIL'] : '');
                $this->set_session('customer_notes', isset($result['PAYMENTREQUEST_0_NOTETEXT']) ? $result['PAYMENTREQUEST_0_NOTETEXT'] : '');
                $this->set_session('phonenum', isset($result['PHONENUM']) ? $result['PHONENUM'] : '');
                WC()->cart->calculate_totals();
            }
            else {
                $this->add_log("...ERROR: GetShippingDetails returned empty result");
            }
            if ($this->skip_final_review == 'yes' && get_option('woocommerce_enable_guest_checkout') === "yes") {
                $url = add_query_arg(array('wc-api' => 'All_In_One_Paypal_For_Woocommerce_Admin_WooCommerce_PayPal_Express', 'pp_action' => 'payaction'), home_url());
                wp_redirect($url);
                exit();
            }
            if (isset($_POST['createaccount'])) {
                $this->customer_id = apply_filters('woocommerce_checkout_customer_id', get_current_user_id());
                if (empty($_POST['username'])) {
                    wc_add_notice(__('Username is required', 'paypal-for-woocommerce'), 'error');
                } elseif (username_exists($_POST['username'])) {
                    wc_add_notice(__('This username is already registered.', 'paypal-for-woocommerce'), 'error');
                } elseif (empty($_POST['email'])) {
                    wc_add_notice(__('Please provide a valid email address.', 'paypal-for-woocommerce'), 'error');
                } elseif (empty($_POST['password']) || empty($_POST['repassword'])) {
                    wc_add_notice(__('Password is required.', 'paypal-for-woocommerce'), 'error');
                } elseif ($_POST['password'] != $_POST['repassword']) {
                    wc_add_notice(__('Passwords do not match.', 'paypal-for-woocommerce'), 'error');
                } elseif (get_user_by('email', $_POST['email']) != false) {
                    wc_add_notice(__('This email address is already registered.', 'paypal-for-woocommerce'), 'error');
                } else {
                    $username = !empty($_POST['username']) ? $_POST['username'] : '';
                    $password = !empty($_POST['password']) ? $_POST['password'] : '';
                    $email = $_POST['email'];
                    try {
                        if (!empty($_POST['email_2'])) {
                            throw new Exception(__('Anti-spam field was filled in.', 'woocommerce'));
                            wc_add_notice('<strong>' . __('Anti-spam field was filled in.', 'paypal-for-woocommerce') . ':</strong> ', 'error');
                        }
                        $new_customer = wc_create_new_customer(sanitize_email($email), wc_clean($username), $password);
                        if (is_wp_error($new_customer)) {
                            wc_add_notice($user->get_error_message(), 'error');
                        }
                        if (apply_filters('paypal-for-woocommerce_registration_auth_new_customer', true, $new_customer)) {
                            wc_set_customer_auth_cookie($new_customer);
                        }
                        $creds = array(
                            'user_login' => wc_clean($username),
                            'user_password' => $password,
                            'remember' => true,
                        );
                        $user = wp_signon($creds, false);
                        if (is_wp_error($user)) {
                            wc_add_notice($user->get_error_message(), 'error');
                        } else {
                            wp_set_current_user($user->ID);
                            $secure_cookie = is_ssl() ? true : false;
                            wp_set_auth_cookie($user->ID, true, $secure_cookie);
                        }
                    } catch (Exception $e) {
                        wc_add_notice('<strong>' . __('Error', 'paypal-for-woocommerce') . ':</strong> ' . $e->getMessage(), 'error');
                    }
                    $this->customer_id = $user->ID;
                    WC()->session->set('reload_checkout', true);
                    WC()->cart->calculate_totals();
                    require_once("lib/NameParser.php");
                    $parser = new FullNameParser();
                    $split_name = $parser->split_full_name($result['SHIPTONAME']);
                    $shipping_first_name = $split_name['fname'];
                    $shipping_last_name = $split_name['lname'];
                    $full_name = $split_name['fullname'];
                    if (isset($result)) {
                        update_user_meta($this->customer_id, 'first_name', isset($result['FIRSTNAME']) ? $result['FIRSTNAME'] : '');
                        update_user_meta($this->customer_id, 'last_name', isset($result['LASTNAME']) ? $result['LASTNAME'] : '');
                        update_user_meta($this->customer_id, 'shipping_first_name', $shipping_first_name);
                        update_user_meta($this->customer_id, 'shipping_last_name', $shipping_last_name);
                        update_user_meta($this->customer_id, 'shipping_company', isset($result['BUSINESS']) ? $result['BUSINESS'] : '' );
                        update_user_meta($this->customer_id, 'shipping_address_1', isset($result['SHIPTOSTREET']) ? $result['SHIPTOSTREET'] : '');
                        update_user_meta($this->customer_id, 'shipping_address_2', isset($result['SHIPTOSTREET2']) ? $result['SHIPTOSTREET2'] : '');
                        update_user_meta($this->customer_id, 'shipping_city', isset($result['SHIPTOCITY']) ? $result['SHIPTOCITY'] : '' );
                        update_user_meta($this->customer_id, 'shipping_postcode', isset($result['SHIPTOZIP']) ? $result['SHIPTOZIP'] : '');
                        update_user_meta($this->customer_id, 'shipping_country', isset($result['SHIPTOCOUNTRYCODE']) ? $result['SHIPTOCOUNTRYCODE'] : '');
                        update_user_meta($this->customer_id, 'shipping_state', isset($result['SHIPTOSTATE']) ? $result['SHIPTOSTATE'] : '' );
                        $user_submit_form = maybe_unserialize(WC()->session->checkout_form);
                        if ((isset($user_submit_form) && !empty($user_submit_form) && is_array($user_submit_form))) {
                            update_user_meta($this->customer_id, 'billing_first_name', isset($user_submit_form['billing_first_name']) ? $user_submit_form['billing_first_name'] : $result['FIRSTNAME']);
                            update_user_meta($this->customer_id, 'billing_last_name', isset($user_submit_form['billing_last_name']) ? $user_submit_form['billing_last_name'] : $result['LASTNAME']);
                            update_user_meta($this->customer_id, 'billing_address_1', isset($user_submit_form['billing_address_1']) ? $user_submit_form['billing_address_1'] : $result['SHIPTOSTREET']);
                            update_user_meta($this->customer_id, 'billing_address_2', isset($user_submit_form['billing_address_2']) ? $user_submit_form['billing_address_2'] : $result['SHIPTOSTREET2']);
                            update_user_meta($this->customer_id, 'billing_city', isset($user_submit_form['billing_city']) ? $user_submit_form['billing_city'] : $result['SHIPTOCITY']);
                            update_user_meta($this->customer_id, 'billing_postcode', isset($user_submit_form['billing_postcode']) ? $user_submit_form['billing_postcode'] : $result['SHIPTOZIP']);
                            update_user_meta($this->customer_id, 'billing_country', isset($user_submit_form['billing_country']) ? $user_submit_form['billing_country'] : $result['SHIPTOCOUNTRYCODE']);
                            update_user_meta($this->customer_id, 'billing_state', isset($user_submit_form['billing_state']) ? $user_submit_form['billing_state'] : $result['SHIPTOSTATE']);
                            update_user_meta($this->customer_id, 'billing_phone', isset($user_submit_form['billing_phone']) ? $user_submit_form['billing_phone'] : $result['PHONENUM']);
                            update_user_meta($this->customer_id, 'billing_email', isset($user_submit_form['billing_email']) ? $user_submit_form['billing_email'] : $result['EMAIL']);
                        } else {
                            update_user_meta($this->customer_id, 'billing_first_name', $shipping_first_name);
                            update_user_meta($this->customer_id, 'billing_last_name', $shipping_last_name);
                            update_user_meta($this->customer_id, 'billing_address_1', isset($result['SHIPTOSTREET']) ? $result['SHIPTOSTREET'] : '');
                            update_user_meta($this->customer_id, 'billing_address_2', isset($result['SHIPTOSTREET2']) ? $result['SHIPTOSTREET2'] : '');
                            update_user_meta($this->customer_id, 'billing_city', isset($result['SHIPTOCITY']) ? $result['SHIPTOCITY'] : '');
                            update_user_meta($this->customer_id, 'billing_postcode', isset($result['SHIPTOZIP']) ? $result['SHIPTOZIP'] : '');
                            update_user_meta($this->customer_id, 'billing_country', isset($result['SHIPTOCOUNTRYCODE']) ? $result['SHIPTOCOUNTRYCODE'] : '');
                            update_user_meta($this->customer_id, 'billing_state', isset($result['SHIPTOSTATE']) ? $result['SHIPTOSTATE'] : '');
                            update_user_meta($this->customer_id, 'billing_phone', isset($result['PHONENUM']) ? $result['PHONENUM'] : '');
                            update_user_meta($this->customer_id, 'billing_email', isset($result['EMAIL']) ? $result['EMAIL'] : '');
                        }
                    }
                }
            }
        } elseif (isset($_GET['pp_action']) && $_GET['pp_action'] == 'payaction') {
            if (isset($_POST) || ($this->skip_final_review == 'yes' && get_option('woocommerce_enable_guest_checkout') === "yes")) {
                $chosen_shipping_methods = WC()->session->get('chosen_shipping_methods');
                if (isset($_POST['shipping_method']) && is_array($_POST['shipping_method']))
                    foreach ($_POST['shipping_method'] as $i => $value)
                        $chosen_shipping_methods[$i] = wc_clean($value);
                WC()->session->set('chosen_shipping_methods', $chosen_shipping_methods);
                if (WC()->cart->needs_shipping()) {
                    $packages = WC()->shipping->get_packages();
                    WC()->checkout()->shipping_methods = WC()->session->get('chosen_shipping_methods');
                }
                $this->add_log('Start Pay Action');
                if (!defined('WOOCOMMERCE_CHECKOUT')) {
                    define('WOOCOMMERCE_CHECKOUT', true);
                }
                WC()->cart->calculate_totals();
                $order_id = WC()->checkout()->create_order();
                require_once("lib/NameParser.php");
                $parser = new FullNameParser();
                $split_name = $parser->split_full_name($this->get_session('shiptoname'));
                $shipping_first_name = $split_name['fname'];
                $shipping_last_name = $split_name['lname'];
                $full_name = $split_name['fullname'];
                $this->set_session('firstname', isset($result['FIRSTNAME']) ? $result['FIRSTNAME'] : $shipping_first_name);
                $this->set_session('lastname', isset($result['LASTNAME']) ? $result['LASTNAME'] : $shipping_last_name);
                update_post_meta($order_id, '_payment_method', $this->id);
                update_post_meta($order_id, '_payment_method_title', $this->title);
                if (is_user_logged_in()) {
                    $userLogined = wp_get_current_user();
                    update_post_meta($order_id, '_billing_email', $userLogined->user_email);
                } else {
                    update_post_meta($order_id, '_billing_email', $this->get_session('payeremail'));
                }
                $checkout_form_data = maybe_unserialize($this->get_session('checkout_form'));
                if (isset($checkout_form_data) && !empty($checkout_form_data)) {
                    foreach ($checkout_form_data as $key => $value) {
                        if (strpos($key, 'billing_') !== false && !empty($value) && !is_array($value)) {
                            if ($checkout_form_data['ship_to_different_address'] == false) {
                                $shipping_key = str_replace('billing_', 'shipping_', $key);
                                update_user_meta($this->customer_id, $shipping_key, $value);
                                update_post_meta($order_id, '_' . $shipping_key, $value);
                            }
                            update_user_meta($this->customer_id, $key, $value);
                            update_post_meta($order_id, '_' . $key, $value);
                        } elseif (WC()->cart->needs_shipping() && strpos($key, 'shipping_') !== false && !empty($value) && !is_array($value)) {
                            update_user_meta($this->customer_id, $key, $value);
                            update_post_meta($order_id, '_' . $key, $value);
                        }
                    }
                    do_action('woocommerce_checkout_update_user_meta', $this->customer_id, $checkout_form_data);
                } else {
                    update_post_meta($order_id, '_shipping_first_name', $this->get_session('firstname'));
                    update_post_meta($order_id, '_shipping_last_name', $this->get_session('lastname'));
                    update_post_meta($order_id, '_shipping_full_name', $full_name);
                    update_post_meta($order_id, '_shipping_company', $this->get_session('company'));
                    update_post_meta($order_id, '_billing_phone', $this->get_session('phonenum'));
                    update_post_meta($order_id, '_shipping_address_1', $this->get_session('shiptostreet'));
                    update_post_meta($order_id, '_shipping_address_2', $this->get_session('shiptostreet2'));
                    update_post_meta($order_id, '_shipping_city', $this->get_session('shiptocity'));
                    update_post_meta($order_id, '_shipping_postcode', $this->get_session('shiptozip'));
                    update_post_meta($order_id, '_shipping_country', $this->get_session('shiptocountrycode'));
                    update_post_meta($order_id, '_shipping_state', $this->get_state_code($this->get_session('shiptocountrycode'), $this->get_session('shiptostate')));
                    update_post_meta($order_id, '_customer_user', get_current_user_id());
                    if ($this->billing_address == 'yes') {
                        update_post_meta($order_id, '_billing_first_name', $this->get_session('firstname'));
                        update_post_meta($order_id, '_billing_last_name', $this->get_session('lastname'));
                        update_post_meta($order_id, '_billing_full_name', $full_name);
                        update_post_meta($order_id, '_billing_company', $this->get_session('company'));
                        update_post_meta($order_id, '_billing_address_1', $this->get_session('shiptostreet'));
                        update_post_meta($order_id, '_billing_address_2', $this->get_session('shiptostreet2'));
                        update_post_meta($order_id, '_billing_city', $this->get_session('shiptocity'));
                        update_post_meta($order_id, '_billing_postcode', $this->get_session('shiptozip'));
                        update_post_meta($order_id, '_billing_country', $this->get_session('shiptocountrycode'));
                        update_post_meta($order_id, '_billing_state', $this->get_state_code($this->get_session('shiptocountrycode'), $this->get_session('shiptostate')));
                    }
                }
                $this->add_log('...Order ID: ' . $order_id);
                $order = new WC_Order($order_id);
                do_action('woocommerce_ppe_do_payaction', $order);
                $this->add_log('...Order Total: ' . $order->order_total);
                $this->add_log('...Cart Total: ' . WC()->cart->get_total());
                $this->add_log("...Token:" . $this->get_session('TOKEN'));
                $result = $this->ConfirmPayment($order->order_total);
                if (!get_current_user_id()) {
                    update_post_meta($order_id, '_billing_first_name', $this->get_session('firstname'));
                    update_post_meta($order_id, '_billing_last_name', $this->get_session('lastname'));
                }
                if ($this->get_session('customer_notes') != '') {
                    $order->add_order_note(__('Customer Notes: ', 'paypal-for-woocommerce') . $this->get_session('customer_notes'));
                }
                if ($result['ACK'] == 'Success' || $result['ACK'] == 'SuccessWithWarning') {
                    $this->add_log('Payment confirmed with PayPal successfully');
                    $result = apply_filters('woocommerce_payment_successful_result', $result);
                    $order->add_order_note(__('PayPal Express payment completed', 'paypal-for-woocommerce') .
                            ' ( Response Code: ' . $result['ACK'] . ", " .
                            ' TransactionID: ' . $result['PAYMENTINFO_0_TRANSACTIONID'] . ' )');
                    $REVIEW_RESULT = unserialize($this->get_session('RESULT'));
                    $payerstatus_note = __('Payer Status: ', 'paypal-for-woocommerce');
                    $payerstatus_note .= ucfirst($REVIEW_RESULT['PAYERSTATUS']);
                    $order->add_order_note($payerstatus_note);
                    $addressstatus_note = __('Address Status: ', 'paypal-for-woocommerce');
                    $addressstatus_note .= ucfirst($REVIEW_RESULT['ADDRESSSTATUS']);
                    $order->add_order_note($addressstatus_note);
                    $order->payment_complete($result['PAYMENTINFO_0_TRANSACTIONID']);
                    do_action('woocommerce_checkout_order_processed', $order_id);
                    WC()->cart->empty_cart();
                    wp_redirect($this->get_return_url($order));
                    exit();
                } else {
                    $this->add_log('...Error confirming order ' . $order_id . ' with PayPal');
                    $this->add_log('...response:' . print_r($result, true));
                    $ErrorCode = urldecode($result["L_ERRORCODE0"]);
                    $ErrorShortMsg = urldecode($result["L_SHORTMESSAGE0"]);
                    $ErrorLongMsg = urldecode($result["L_LONGMESSAGE0"]);
                    $ErrorSeverityCode = urldecode($result["L_SEVERITYCODE0"]);
                    $this->add_log('SetExpressCheckout API call failed. ');
                    $this->add_log('Detailed Error Message: ' . $ErrorLongMsg);
                    $this->add_log('Short Error Message: ' . $ErrorShortMsg);
                    $this->add_log('Error Code: ' . $ErrorCode);
                    $this->add_log('Error Severity Code: ' . $ErrorSeverityCode);
                    if ($ErrorCode == '10486') {
                        $this->RedirectToPayPal($this->get_session('TOKEN'));
                    }
                    $message = '';
                    if ($this->error_email_notify) {
                        $admin_email = get_option("admin_email");
                        $message .= __("DoExpressCheckoutPayment API call failed.", "paypal-for-woocommerce") . "\n\n";
                        $message .= __('Error Code: ', 'paypal-for-woocommerce') . $ErrorCode . "\n";
                        $message .= __('Error Severity Code: ', 'paypal-for-woocommerce') . $ErrorSeverityCode . "\n";
                        $message .= __('Short Error Message: ', 'paypal-for-woocommerce') . $ErrorShortMsg . "\n";
                        $message .= __('Detailed Error Message: ', 'paypal-for-woocommerce') . $ErrorLongMsg . "\n";
                        $message .= __('Order ID: ') . $order_id . "\n";
                        $message .= __('Customer Name: ') . $this->get_session('shiptoname') . "\n";
                        $message .= __('Customer Email: ') . $this->get_session('payeremail') . "\n";
                        $error_email_notify_mes = apply_filters('paypal_express_ec_error_email_notify_message', $message, $ErrorCode, $ErrorSeverityCode, $ErrorShortMsg, $ErrorLongMsg);
                        $subject = "PayPal Express Checkout Error Notification";
                        $error_email_notify_subject = apply_filters('paypal_express_ec_error_email_notify_subject', $subject);
                        wp_mail($admin_email, $error_email_notify_subject, $error_email_notify_mes);
                    }
                    if ($this->error_display_type == 'detailed') {
                        $sec_error_notice = $ErrorCode . ' - ' . $ErrorLongMsg;
                        $error_display_type_message = sprintf(__($sec_error_notice, 'paypal-for-woocommerce'));
                    } else {
                        $error_display_type_message = sprintf(__('There was a problem paying with PayPal.  Please try another method.', 'paypal-for-woocommerce'));
                    }
                    $error_display_type_message = apply_filters('paypal_express_ec_display_type_message', $error_display_type_message, $ErrorCode, $ErrorLongMsg);
                    wc_add_notice($error_display_type_message, 'error');
                    wp_redirect(get_permalink(wc_get_page_id('cart')));
                    exit();
                }
            }
        }
    }

    public function CallSetExpressCheckout($paymentAmount, $returnURL, $cancelURL, $usePayPalCredit = false, $posted) {
        if (sizeof(WC()->cart->get_cart()) == 0) {
            $ms = sprintf(__('Sorry, your session has expired. <a href=%s>Return to homepage &rarr;</a>', 'paypal-for-woocommerce'), '"' . home_url() . '"');
            $set_ec_message = apply_filters('paypal_express_set_ec_message', $ms);
            wc_add_notice($set_ec_message, "error");
        }
        if (!class_exists('PayPal_Express_PayPal')) {
            require_once( 'lib/paypal-php-library/includes/paypal.class.php' );
        }
        $PayPalConfig = array(
            'Sandbox' => $this->testmode == 'yes' ? TRUE : FALSE,
            'APIUsername' => $this->api_username,
            'APIPassword' => $this->api_password,
            'APISignature' => $this->api_signature
        );
        $PayPal = new PayPal_Express_PayPal($PayPalConfig);
        $maxAmount = $paymentAmount * 2;
        $SECFields = array(
            'token' => '',
            'maxamt' => $maxAmount,
            'returnurl' => urldecode($returnURL),
            'cancelurl' => urldecode($cancelURL),
            'callback' => '',
            'callbacktimeout' => '',
            'callbackversion' => '',
            'reqconfirmshipping' => '',
            'noshipping' => '',
            'allownote' => '',
            'addroverride' => '',
            'localecode' => ($this->use_wp_locale_code == 'yes' && WPLANG != '') ? WPLANG : '',
            'pagestyle' => '',
            'hdrimg' => $this->checkout_logo_hdrimg,
            'logourl' => $this->checkout_logo,
            'hdrbordercolor' => '',
            'hdrbackcolor' => '',
            'payflowcolor' => '',
            'skipdetails' => $this->skip_final_review == 'yes' ? '1' : '0',
            'email' => '',
            'channeltype' => '',
            'giropaysuccessurl' => '',
            'giropaycancelurl' => '',
            'banktxnpendingurl' => '',
            'brandname' => $this->brand_name,
            'customerservicenumber' => $this->customer_service_number,
            'buyeremailoptionenable' => '',
            'surveyquestion' => '',
            'surveyenable' => '',
            'totaltype' => '',
            'notetobuyer' => '',
            'buyerid' => '',
            'buyerusername' => '',
            'buyerregistrationdate' => '',
            'allowpushfunding' => '',
            'taxidtype' => '',
            'taxid' => ''
        );
        if ($usePayPalCredit) {
            $SECFields['solutiontype'] = 'Sole';
            $SECFields['landingpage'] = 'Billing';
            $SECFields['userselectedfundingsource'] = 'BML';
        } elseif (strtolower($this->paypal_account_optional) == 'yes' && strtolower($this->landing_page) == 'billing') {
            $SECFields['solutiontype'] = 'Sole';
            $SECFields['landingpage'] = 'Billing';
            $SECFields['userselectedfundingsource'] = 'CreditCard';
        } elseif (strtolower($this->paypal_account_optional) == 'yes' && strtolower($this->landing_page) == 'login') {
            $SECFields['solutiontype'] = 'Sole';
            $SECFields['landingpage'] = 'Login';
        }

        $SurveyChoices = array('Choice 1', 'Choice2', 'Choice3', 'etc');

        if (get_option('woocommerce_prices_include_tax') == 'yes') {
            $shipping = WC()->cart->shipping_total + WC()->cart->shipping_tax_total;
            $tax = '0.00';
        } else {
            $shipping = WC()->cart->shipping_total;
            $tax = WC()->cart->get_taxes_total();
        }

        if ('yes' === get_option('woocommerce_calc_taxes') && 'yes' === get_option('woocommerce_prices_include_tax')) {
            $tax = wc_round_tax_total(WC()->cart->tax_total + WC()->cart->shipping_tax_total);
        }
        $Payments = array();
        $Payment = array(
            'amt' => number_format(WC()->cart->total, 2, '.', ''),
            'currencycode' => get_woocommerce_currency(),
            'shippingamt' => number_format($shipping, 2, '.', ''),
            'shippingdiscamt' => '',
            'insuranceamt' => '',
            'insuranceoptionoffered' => '',
            'handlingamt' => '',
            'taxamt' => $tax,
            'desc' => '',
            'custom' => '',
            'invnum' => '',
            'notifyurl' => '',
            'shiptoname' => '',
            'shiptostreet' => '',
            'shiptostreet2' => '',
            'shiptocity' => '',
            'shiptostate' => '',
            'shiptozip' => '',
            'shiptocountrycode' => '',
            'shiptophonenum' => '',
            'notetext' => '',
            'allowedpaymentmethod' => '',
            'paymentaction' => $this->payment_action == 'Authorization' ? 'Authorization' : 'Sale',
            'paymentrequestid' => '',
            'sellerpaypalaccountid' => ''
        );

        if (!empty($posted) && WC()->cart->needs_shipping()) {
            $SECFields['addroverride'] = 1;
            if (@$posted['ship_to_different_address']) {
                $Payment['shiptoname'] = $posted['shipping_first_name'] . ' ' . $posted['shipping_last_name'];
                $Payment['shiptostreet'] = $posted['shipping_address_1'];
                $Payment['shiptostreet2'] = @$posted['shipping_address_2'];
                $Payment['shiptocity'] = @$posted['shipping_city'];
                $Payment['shiptostate'] = @$posted['shipping_state'];
                $Payment['shiptozip'] = @$posted['shipping_postcode'];
                $Payment['shiptocountrycode'] = @$posted['shipping_country'];
                $Payment['shiptophonenum'] = @$posted['shipping_phone'];
            } else {
                $Payment['shiptoname'] = $posted['billing_first_name'] . ' ' . $posted['billing_last_name'];
                $Payment['shiptostreet'] = $posted['billing_address_1'];
                $Payment['shiptostreet2'] = @$posted['billing_address_2'];
                $Payment['shiptocity'] = @$posted['billing_city'];
                $Payment['shiptostate'] = @$posted['billing_state'];
                $Payment['shiptozip'] = @$posted['billing_postcode'];
                $Payment['shiptocountrycode'] = @$posted['billing_country'];
                $Payment['shiptophonenum'] = @$posted['billing_phone'];
            }
        }
        $PaymentOrderItems = array();
        $ctr = $total_items = $total_discount = $total_tax = $order_total = 0;
        foreach (WC()->cart->get_cart() as $cart_item_key => $values) {

            $_product = $values['data'];
            $qty = absint($values['quantity']);
            $sku = $_product->get_sku();
            $values['name'] = html_entity_decode($_product->get_title(), ENT_NOQUOTES, 'UTF-8');

            if ($_product->product_type == 'variation') {
                $meta = WC()->cart->get_item_data($values, true);
                if (empty($sku)) {
                    $sku = $_product->parent->get_sku();
                }
                if (!empty($meta)) {
                    $values['name'] .= " - " . str_replace(", \n", " - ", $meta);
                }
            }

            if (get_option('woocommerce_prices_include_tax') == 'yes') {
                $product_price = number_format($_product->get_price_including_tax(), 2, '.', '');
            } else {
                $product_price = number_format($_product->get_price_excluding_tax(), 2, '.', '');
            }
            $quantity = absint($values['quantity']);
            $Item = array(
                'name' => $values['name'],
                'desc' => '',
                'amt' => round($values['line_subtotal'] / $quantity, 2),
                'number' => $sku,
                'qty' => $quantity,
                'taxamt' => '',
                'itemurl' => '',
                'itemcategory' => '',
                'itemweightvalue' => '',
                'itemweightunit' => '',
                'itemheightvalue' => '',
                'itemheightunit' => '',
                'itemwidthvalue' => '',
                'itemwidthunit' => '',
                'itemlengthvalue' => '',
                'itemlengthunit' => '',
                'ebayitemnumber' => '',
                'ebayitemauctiontxnid' => '',
                'ebayitemorderid' => '',
                'ebayitemcartid' => ''
            );
            array_push($PaymentOrderItems, $Item);
            $total_items += $values['line_subtotal'];
            $ctr++;
        }
        foreach (WC()->cart->get_fees() as $fee) {
            $Item = array(
                'name' => $fee->name,
                'desc' => '',
                'amt' => number_format($fee->amount, 2, '.', ''),
                'number' => $fee->id,
                'qty' => 1,
                'taxamt' => '',
                'itemurl' => '',
                'itemcategory' => '',
                'itemweightvalue' => '',
                'itemweightunit' => '',
                'itemheightvalue' => '',
                'itemheightunit' => '',
                'itemwidthvalue' => '',
                'itemwidthunit' => '',
                'itemlengthvalue' => '',
                'itemlengthunit' => '',
                'ebayitemnumber' => '',
                'ebayitemauctiontxnid' => '',
                'ebayitemorderid' => '',
                'ebayitemcartid' => ''
            );
            array_push($PaymentOrderItems, $Item);
            $total_items += $fee->amount * $Item['qty'];
            $ctr++;
        }
        if (WC()->cart->get_cart_discount_total() > 0) {
            foreach (WC()->cart->get_coupons('cart') as $code => $coupon) {
                $Item = array(
                    'name' => 'Cart Discount',
                    'number' => $code,
                    'qty' => '1',
                    'amt' => '-' . number_format(WC()->cart->coupon_discount_amounts[$code], 2, '.', '')
                );
                array_push($PaymentOrderItems, $Item);
            }
            $total_discount -= WC()->cart->get_cart_discount_total();
        }
        if (!$this->is_wc_version_greater_2_3()) {
            if (WC()->cart->get_order_discount_total() > 0) {
                foreach (WC()->cart->get_coupons('order') as $code => $coupon) {
                    $Item = array(
                        'name' => 'Order Discount',
                        'number' => $code,
                        'qty' => '1',
                        'amt' => '-' . number_format(WC()->cart->coupon_discount_amounts[$code], 2, '.', '')
                    );
                    array_push($PaymentOrderItems, $Item);
                }
                $total_discount -= WC()->cart->get_order_discount_total();
            }
        }
        if ($tax > 0) {
            $tax_round = number_format($tax, 2, '.', '');
        }
        if ($shipping > 0) {
            $shipping_round = number_format($shipping, 2, '.', '');
        }
        if (isset($total_discount)) {
            $total_discount = round($total_discount, 2);
        }
        if ($this->send_items) {
            $Payment['order_items'] = $PaymentOrderItems;
            $Payment['itemamt'] = round($total_items + $total_discount, 2);
        } else {
            $Payment['order_items'] = array();
            $Payment['itemamt'] = round($total_items + $total_discount, 2);
        }
        array_push($Payments, $Payment);
        $BuyerDetails = array(
            'buyerid' => '',
            'buyerusername' => '',
            'buyerregistrationdate' => ''
        );
        $ShippingOptions = array();
        $Option = array(
            'l_shippingoptionisdefault' => '',
            'l_shippingoptionname' => '',
            'l_shippingoptionlabel' => '',
            'l_shippingoptionamount' => ''
        );
        array_push($ShippingOptions, $Option);
        $BillingAgreements = array();
        $Item = array(
            'l_billingtype' => '',
            'l_billingagreementdescription' => '',
            'l_paymenttype' => '',
            'l_billingagreementcustom' => ''
        );
        array_push($BillingAgreements, $Item);
        $PayPalRequestData = array(
            'SECFields' => $SECFields,
            'SurveyChoices' => $SurveyChoices,
            'Payments' => $Payments,
        );
        if (trim(WC()->cart->total) !== trim($total_items + $total_discount + $tax + number_format($shipping, 2, '.', ''))) {
            if (get_option('woocommerce_prices_include_tax') == 'yes') {
                $shipping = WC()->cart->shipping_total + WC()->cart->shipping_tax_total;
            } else {
                $shipping = WC()->cart->shipping_total;
            }
            if ($shipping > 0) {
                $PayPalRequestData['Payments'][0]['shippingamt'] = $this->cut_off($shipping, 2);
            } elseif ($tax > 0) {
                $PayPalRequestData['Payments'][0]['taxamt'] = $this->cut_off($tax, 2);
            }
        }
        $PayPalResult = $PayPal->SetExpressCheckout($PayPalRequestData);
        $this->add_log('Test Mode: ' . $this->testmode);
        $this->add_log('Endpoint: ' . $this->API_Endpoint);
        $PayPalRequest = isset($PayPalResult['RAWREQUEST']) ? $PayPalResult['RAWREQUEST'] : '';
        $PayPalResponse = isset($PayPalResult['RAWRESPONSE']) ? $PayPalResult['RAWRESPONSE'] : '';
        $this->add_log('Request: ' . print_r($PayPal->NVPToArray($PayPal->MaskAPIResult($PayPalRequest)), true));
        $this->add_log('Response: ' . print_r($PayPal->NVPToArray($PayPal->MaskAPIResult($PayPalResponse)), true));
        if ($PayPal->APICallSuccessful($PayPalResult['ACK'])) {
            $token = urldecode($PayPalResult["TOKEN"]);
            $this->set_session('TOKEN', $token);
        }
        return $PayPalResult;
    }

    function CallGetShippingDetails($token) {
        if (sizeof(WC()->cart->get_cart()) == 0) {
            $ms = sprintf(__('Sorry, your session has expired. <a href=%s>Return to homepage &rarr;</a>', 'paypal-for-woocommerce'), '"' . home_url() . '"');
            $ec_cgsd_message = apply_filters('paypal_express_get_shipping_ec_message', $ms);
            wc_add_notice($ec_cgsd_message, "error");
        }
        if (!class_exists('PayPal_Express_PayPal')) {
            require_once( 'lib/paypal-php-library/includes/paypal.class.php' );
        }
        $PayPalConfig = array(
            'Sandbox' => $this->testmode == 'yes' ? TRUE : FALSE,
            'APIUsername' => $this->api_username,
            'APIPassword' => $this->api_password,
            'APISignature' => $this->api_signature
        );
        $PayPal = new PayPal_Express_PayPal($PayPalConfig);
        $PayPalResult = $PayPal->GetExpressCheckoutDetails($token);
        $this->add_log('Test Mode: ' . $this->testmode);
        $this->add_log('Endpoint: ' . $this->API_Endpoint);
        $PayPalRequest = isset($PayPalResult['RAWREQUEST']) ? $PayPalResult['RAWREQUEST'] : '';
        $PayPalResponse = isset($PayPalResult['RAWRESPONSE']) ? $PayPalResult['RAWRESPONSE'] : '';
        $this->add_log('Request: ' . print_r($PayPal->NVPToArray($PayPal->MaskAPIResult($PayPalRequest)), true));
        $this->add_log('Response: ' . print_r($PayPal->NVPToArray($PayPal->MaskAPIResult($PayPalResponse)), true));
        if ($PayPal->APICallSuccessful($PayPalResult['ACK'])) {
            $this->set_session('payer_id', $PayPalResult['PAYERID']);
        }
        return $PayPalResult;
    }

    function ConfirmPayment($FinalPaymentAmt) {
        if (sizeof(WC()->cart->get_cart()) == 0) {
            $ms = sprintf(__('Sorry, your session has expired. <a href=%s>Return to homepage &rarr;</a>', 'paypal-for-woocommerce'), '"' . home_url() . '"');
            $ec_confirm_message = apply_filters('paypal_express_ec_confirm_message', $ms);
            wc_add_notice($ec_confirm_message, "error");
        }
        if (!class_exists('PayPal_Express_PayPal')) {
            require_once( 'lib/paypal-php-library/includes/paypal.class.php' );
        }
        $PayPalConfig = array(
            'Sandbox' => $this->testmode == 'yes' ? TRUE : FALSE,
            'APIUsername' => $this->api_username,
            'APIPassword' => $this->api_password,
            'APISignature' => $this->api_signature
        );
        $PayPal = new PayPal_Express_PayPal($PayPalConfig);
        if (!empty($this->confirm_order_id)) {
            $order = new WC_Order($this->confirm_order_id);
            $invoice_number = preg_replace("/[^0-9,.]/", "", $order->get_order_number());
            if ($order->customer_note) {
                $customer_notes = wptexturize($order->customer_note);
            }
            $shipping_first_name = $order->shipping_first_name;
            $shipping_last_name = $order->shipping_last_name;
            $shipping_address_1 = $order->shipping_address_1;
            $shipping_address_2 = $order->shipping_address_2;
            $shipping_city = $order->shipping_city;
            $shipping_state = $order->shipping_state;
            $shipping_postcode = $order->shipping_postcode;
            $shipping_country = $order->shipping_country;
        }
        $DECPFields = array(
            'token' => urlencode($this->get_session('TOKEN')),
            'payerid' => urlencode($this->get_session('payer_id')),
            'returnfmfdetails' => '',
            'buyermarketingemail' => '',
            'surveyquestion' => '',
            'surveychoiceselected' => '',
            'allowedpaymentmethod' => ''
        );
        $Payments = array();
        $Payment = array(
            'amt' => number_format($FinalPaymentAmt, 2, '.', ''),
            'currencycode' => get_woocommerce_currency(),
            'shippingdiscamt' => '',
            'insuranceoptionoffered' => '',
            'handlingamt' => '',
            'desc' => '',
            'custom' => '',
            'invnum' => $this->invoice_id_prefix . $invoice_number,
            'notifyurl' => '',
            'shiptoname' => $shipping_first_name . ' ' . $shipping_last_name,
            'shiptostreet' => $shipping_address_1,
            'shiptostreet2' => $shipping_address_2,
            'shiptocity' => $shipping_city,
            'shiptostate' => $shipping_state,
            'shiptozip' => $shipping_postcode,
            'shiptocountrycode' => $shipping_country,
            'shiptophonenum' => '',
            'notetext' => $this->get_session('customer_notes'),
            'allowedpaymentmethod' => '',
            'paymentaction' => $this->payment_action == 'Authorization' ? 'Authorization' : 'Sale',
            'paymentrequestid' => '',
            'sellerpaypalaccountid' => '',
            'sellerid' => '',
            'sellerusername' => '',
            'sellerregistrationdate' => '',
            'softdescriptor' => ''
        );
        $PaymentOrderItems = array();
        $ctr = $total_items = $total_discount = $total_tax = $shipping = 0;
        $ITEMAMT = 0;
        if (sizeof($order->get_items()) > 0) {
            if ($this->send_items) {
                foreach ($order->get_items() as $values) {
                    $_product = $order->get_product_from_item($values);
                    $qty = absint($values['qty']);
                    $sku = $_product->get_sku();
                    $values['name'] = html_entity_decode($values['name'], ENT_NOQUOTES, 'UTF-8');
                    if ($_product->product_type == 'variation') {
                        if (empty($sku)) {
                            $sku = $_product->parent->get_sku();
                        }
                        $item_meta = new WC_Order_Item_Meta($values['item_meta']);
                        $meta = $item_meta->display(true, true);
                        if (!empty($meta)) {
                            $values['name'] .= " - " . str_replace(", \n", " - ", $meta);
                        }
                    }
                    if (get_option('woocommerce_prices_include_tax') == 'yes') {
                        $product_price = $order->get_item_subtotal($values, true, false);
                    } else {
                        $product_price = $order->get_item_subtotal($values, false, true);
                    }
                    $Item = array(
                        'name' => $values['name'],
                        'desc' => '',
                        'amt' => round($values['line_subtotal'] / $qty, 2),
                        'number' => $sku,
                        'qty' => $qty,
                        'taxamt' => '',
                        'itemurl' => '',
                        'itemcategory' => '',
                        'itemweightvalue' => '',
                        'itemweightunit' => '',
                        'itemheightvalue' => '',
                        'itemheightunit' => '',
                        'itemwidthvalue' => '',
                        'itemwidthunit' => '',
                        'itemlengthvalue' => '',
                        'itemlengthunit' => '',
                        'ebayitemnumber' => '',
                        'ebayitemauctiontxnid' => '',
                        'ebayitemorderid' => '',
                        'ebayitemcartid' => ''
                    );
                    array_push($PaymentOrderItems, $Item);
                    $ITEMAMT += $values['line_subtotal'];
                    ;
                }
                foreach (WC()->cart->get_fees() as $fee) {
                    $Item = array(
                        'name' => $fee->name,
                        'desc' => '',
                        'amt' => number_format($fee->amount, 2, '.', ''),
                        'number' => $fee->id,
                        'qty' => 1,
                        'taxamt' => '',
                        'itemurl' => '',
                        'itemcategory' => '',
                        'itemweightvalue' => '',
                        'itemweightunit' => '',
                        'itemheightvalue' => '',
                        'itemheightunit' => '',
                        'itemwidthvalue' => '',
                        'itemwidthunit' => '',
                        'itemlengthvalue' => '',
                        'itemlengthunit' => '',
                        'ebayitemnumber' => '',
                        'ebayitemauctiontxnid' => '',
                        'ebayitemorderid' => '',
                        'ebayitemcartid' => ''
                    );
                    array_push($PaymentOrderItems, $Item);
                    $ITEMAMT += $fee->amount * $Item['qty'];
                    $ctr++;
                }
                if (!$this->is_wc_version_greater_2_3()) {
                    if ($order->get_cart_discount() > 0) {
                        foreach (WC()->cart->get_coupons('cart') as $code => $coupon) {
                            $Item = array(
                                'name' => 'Cart Discount',
                                'number' => $code,
                                'qty' => '1',
                                'amt' => '-' . number_format(WC()->cart->coupon_discount_amounts[$code], 2, '.', '')
                            );
                            array_push($PaymentOrderItems, $Item);
                        }
                        $ITEMAMT -= $order->get_cart_discount();
                    }
                    if ($order->get_order_discount() > 0) {
                        foreach (WC()->cart->get_coupons('order') as $code => $coupon) {
                            $Item = array(
                                'name' => 'Order Discount',
                                'number' => $code,
                                'qty' => '1',
                                'amt' => '-' . number_format(WC()->cart->coupon_discount_amounts[$code], 2, '.', '')
                            );
                            array_push($PaymentOrderItems, $Item);
                        }
                        $ITEMAMT -= $order->get_order_discount();
                    }
                } else {
                    if ($order->get_total_discount() > 0) {
                        $Item = array(
                            'name' => 'Total Discount',
                            'qty' => 1,
                            'amt' => - round($order->get_total_discount(), 2),
                        );
                        array_push($PaymentOrderItems, $Item);
                        $ITEMAMT -= $order->get_total_discount();
                    }
                }
            }
            if (get_option('woocommerce_prices_include_tax') == 'yes') {
                $shipping = $order->get_total_shipping() + $order->get_shipping_tax();
                $tax = 0;
            } else {
                $shipping = $order->get_total_shipping();
                $tax = $order->get_total_tax();
            }
            if ('yes' === get_option('woocommerce_calc_taxes') && 'no' === get_option('woocommerce_prices_include_tax')) {
                $tax = $order->get_total_tax();
            }
            if ($tax > 0) {
                $tax = number_format($tax, 2, '.', '');
            }
            if ($shipping > 0) {
                $shipping = number_format($shipping, 2, '.', '');
            }
            if ($total_discount) {
                $total_discount = round($total_discount, 2);
            }
            if ($this->send_items) {
                $Payment['itemamt'] = number_format($ITEMAMT, 2, '.', '');
            } else {
                $PaymentOrderItems = array();
                $Payment['itemamt'] = WC()->cart->total - $tax - $shipping;
            }
            if ($tax > 0) {
                $Payment['taxamt'] = number_format($tax, 2, '.', '');
            }
            if ($shipping > 0) {
                $Payment['shippingamt'] = number_format($shipping, 2, '.', '');
            }
        }
        $Payment['order_items'] = $PaymentOrderItems;
        array_push($Payments, $Payment);
        $UserSelectedOptions = array(
            'shippingcalculationmode' => '',
            'insuranceoptionselected' => '',
            'shippingoptionisdefault' => '',
            'shippingoptionamount' => '',
            'shippingoptionname' => '',
        );
        $PayPalRequestData = array(
            'DECPFields' => $DECPFields,
            'Payments' => $Payments,
        );
        if (trim(WC()->cart->total) !== trim($Payment['itemamt'] + $tax + number_format($shipping, 2, '.', ''))) {
            if (get_option('woocommerce_prices_include_tax') == 'yes') {
                $shipping = WC()->cart->shipping_total + WC()->cart->shipping_tax_total;
            } else {
                $shipping = WC()->cart->shipping_total;
            }
            if ($shipping > 0) {
                $PayPalRequestData['Payments'][0]['shippingamt'] = $this->cut_off($shipping, 2);
            } elseif ($tax > 0) {
                $PayPalRequestData['Payments'][0]['taxamt'] = $this->cut_off($tax, 2);
            }
        }
        $PayPalResult = $PayPal->DoExpressCheckoutPayment($PayPalRequestData);
        $this->add_log('Test Mode: ' . $this->testmode);
        $this->add_log('Endpoint: ' . $this->API_Endpoint);
        $PayPalRequest = isset($PayPalResult['RAWREQUEST']) ? $PayPalResult['RAWREQUEST'] : '';
        $PayPalResponse = isset($PayPalResult['RAWRESPONSE']) ? $PayPalResult['RAWRESPONSE'] : '';
        $this->add_log('Request: ' . print_r($PayPal->NVPToArray($PayPal->MaskAPIResult($PayPalRequest)), true));
        $this->add_log('Response: ' . print_r($PayPal->NVPToArray($PayPal->MaskAPIResult($PayPalResponse)), true));
        if ($PayPal->APICallSuccessful($PayPalResult['ACK'])) {
            $this->remove_session('TOKEN');
        }
        return $PayPalResult;
    }

    function RedirectToPayPal($token) {
        $payPalURL = $this->PAYPAL_URL . $token;
        wp_redirect($payPalURL, 302);
        exit;
    }

    function deformatNVP($nvpstr) {
        $intial = 0;
        $nvpArray = array();
        while (strlen($nvpstr)) {
            $keypos = strpos($nvpstr, '=');
            $valuepos = strpos($nvpstr, '&') ? strpos($nvpstr, '&') : strlen($nvpstr);
            $keyval = substr($nvpstr, $intial, $keypos);
            $valval = substr($nvpstr, $keypos + 1, $valuepos - $keypos - 1);
            $nvpArray[urldecode($keyval)] = urldecode($valval);
            $nvpstr = substr($nvpstr, $valuepos + 1, strlen($nvpstr));
        }
        return $nvpArray;
    }

    function get_state_code($country, $state) {
        if ($country != 'US') {
            $local_states = WC()->countries->states[WC()->customer->get_country()];
            if (!empty($local_states) && in_array($state, $local_states)) {
                foreach ($local_states as $key => $val) {
                    if ($val == $state) {
                        $state = $key;
                    }
                }
            }
        }
        return $state;
    }

    private function set_session($key, $value) {
        WC()->session->$key = $value;
    }

    private function get_session($key) {
        return WC()->session->$key;
    }

    private function remove_session($key) {
        WC()->session->$key = "";
    }

    static function woocommerce_before_cart() {
        global $pp_settings, $pp_pro, $pp_payflow;
        $payment_gateways_count = 0;
        echo "<style>table.cart td.actions .input-text, table.cart td.actions .button, table.cart td.actions .checkout-button {margin-bottom: 0.53em !important}</style>";
        if ((@$pp_settings['enabled'] == 'yes') && 0 < WC()->cart->total) {
            $payment_gateways = WC()->payment_gateways->get_available_payment_gateways();
            unset($payment_gateways['paypal_pro']);
            unset($payment_gateways['paypal_pro_payflow']);
            if ((isset($pp_settings['show_on_checkout']) && $pp_settings['show_on_checkout'] == 'regular')) {
                $payment_gateways_count = 1;
            }
            if ((empty($payment_gateways) || @$pp_settings['enabled'] == 'yes') && (count($payment_gateways) == $payment_gateways_count)) {
                if (@$pp_pro['enabled'] == 'yes' || @$pp_payflow['enabled'] == 'yes') {
                    echo '<script type="text/javascript">
                                jQuery(document).ready(function(){
                                    if (jQuery(".checkout-button").is("input")) {
                                        jQuery(".checkout-button").val("' . __('Pay with Credit Card', 'paypal-for-woocommerce') . '");
                                    } else jQuery(".checkout-button").html("<span>' . __('Pay with Credit Card', 'paypal-for-woocommerce') . '</span>");
                                });
                              </script>';
                } elseif (empty($pp_settings['show_on_cart']) || $pp_settings['show_on_cart'] == 'yes') {
                    echo '<style> input.checkout-button,
                                 a.checkout-button {
                                    display: none !important;
                                }</style>';
                }
            }
        }
    }

    public function woocommerce_paypal_express_checkout_button_paypal_express() {
        global $pp_settings, $pp_pro, $pp_payflow;
        $payment_gateways = WC()->payment_gateways->get_available_payment_gateways();
        unset($payment_gateways['paypal_pro']);
        unset($payment_gateways['paypal_pro_payflow']);
        echo '<div class="clear"></div>';
        if (@$pp_settings['enabled'] == 'yes' && (empty($pp_settings['show_on_cart']) || $pp_settings['show_on_cart'] == 'yes') && 0 < WC()->cart->total) {
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
                    echo "<img src='https://www.paypal.com/" . All_In_One_Paypal_For_Woocommerce_Admin_WooCommerce_PayPal_Express::get_button_locale_code() . "/i/btn/btn_xpressCheckout.gif' border='0' alt='" . __('Pay with PayPal', 'paypal-for-woocommerce') . "'/>";
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
            <?php
            echo "<div class='clear'></div></div>";
        }
    }

    static function get_button_locale_code() {
        $locale_code = defined("WPLANG") && WPLANG != '' ? WPLANG : 'en_US';
        switch ($locale_code) {
            case "de_DE": $locale_code = "de_DE/DE";
                break;
        }
        return $locale_code;
    }

    public function process_refund($order_id, $amount = null, $reason = '') {
        $order = wc_get_order($order_id);
        $this->add_log('Begin Refund');
        $this->add_log('Order: ' . print_r($order, true));
        $this->add_log('Transaction ID: ' . print_r($order->get_transaction_id(), true));
        $this->add_log('API Username: ' . print_r($this->api_username, true));
        $this->add_log('API Password: ' . print_r($this->api_password, true));
        $this->add_log('API Signature: ' . print_r($this->api_signature, true));
        if (!$order || !$order->get_transaction_id() || !$this->api_username || !$this->api_password || !$this->api_signature) {
            return false;
        }
        $this->add_log('Include Class Request');
        if (!class_exists('PayPal_Express_PayPal')) {
            require_once( 'lib/paypal-php-library/includes/paypal.class.php' );
        }
        $PayPalConfig = array(
            'Sandbox' => $this->testmode == 'yes' ? TRUE : FALSE,
            'APIUsername' => $this->api_username,
            'APIPassword' => $this->api_password,
            'APISignature' => $this->api_signature
        );
        $PayPal = new PayPal_Express_PayPal($PayPalConfig);
        if ($reason) {
            if (255 < strlen($reason)) {
                $reason = substr($reason, 0, 252) . '...';
            }
            $reason = html_entity_decode($reason, ENT_NOQUOTES, 'UTF-8');
        }
        $RTFields = array(
            'transactionid' => $order->get_transaction_id(),
            'payerid' => '',
            'invoiceid' => '',
            'refundtype' => $order->get_total() == $amount ? 'Full' : 'Partial',
            'amt' => number_format($amount, 2, '.', ''),
            'currencycode' => $order->get_order_currency(),
            'note' => $reason,
            'retryuntil' => '',
            'refundsource' => '',
            'merchantstoredetail' => '',
            'refundadvice' => '',
            'refunditemdetails' => '',
            'msgsubid' => '',
            'storeid' => '',
            'terminalid' => ''
        );
        $PayPalRequestData = array('RTFields' => $RTFields);
        $this->add_log('Refund Request: ' . print_r($PayPalRequestData, true));
        $PayPalResult = $PayPal->RefundTransaction($PayPalRequestData);
        $this->add_log('Refund Information: ' . print_r($PayPalResult, true));
        if ($PayPal->APICallSuccessful($PayPalResult['ACK'])) {
            $order->add_order_note('Refund Transaction ID:' . $PayPalResult['REFUNDTRANSACTIONID']);
            $order->update_status('refunded');
            if (ob_get_length())
                ob_end_clean();
            return true;
        } else {
            $ec_message = apply_filters('paypal_express_ec_refund_message', $PayPalResult['L_LONGMESSAGE0'], $PayPalResult['L_ERRORCODE0'], $PayPalResult);
            return new WP_Error('ec_refund-error', $ec_message);
        }
    }

    public function top_cart_button() {
        if (!empty($this->settings['button_position']) && ($this->settings['button_position'] == 'top' || $this->settings['button_position'] == 'both')) {
            $this->woocommerce_paypal_express_checkout_button_paypal_express();
        }
    }

    function regular_checkout($posted) {
        if ($posted['payment_method'] == 'paypal_express' && wc_notice_count('error') == 0) {
            if (!is_user_logged_in() && get_option('woocommerce_enable_guest_checkout') != 'yes') {
                $this->customer_id = apply_filters('woocommerce_checkout_customer_id', get_current_user_id());
                $username = !empty($posted['account_username']) ? $posted['account_username'] : '';
                $password = !empty($posted['account_password']) ? $posted['account_password'] : '';
                $new_customer = wc_create_new_customer($posted['billing_email'], $username, $password);
                if (is_wp_error($new_customer)) {
                    throw new Exception($new_customer->get_error_message());
                }
                $this->customer_id = $new_customer;
                wc_set_customer_auth_cookie($this->customer_id);
                WC()->session->set('reload_checkout', true);
                WC()->cart->calculate_totals();
                if ($posted['billing_first_name'] && apply_filters('woocommerce_checkout_update_customer_data', true, $this)) {
                    $userdata = array(
                        'ID' => $this->customer_id,
                        'first_name' => $posted['billing_first_name'] ? $posted['billing_first_name'] : '',
                        'last_name' => $posted['billing_last_name'] ? $posted['billing_last_name'] : '',
                        'display_name' => $posted['billing_first_name'] ? $posted['billing_first_name'] : ''
                    );
                    wp_update_user(apply_filters('woocommerce_checkout_customer_userdata', $userdata, $this));
                }
            }
            $this->set_session('checkout_form', serialize($posted));
            $this->paypal_express_checkout($posted);
            return;
        }
    }

    function cut_off($number) {
        $parts = explode(".", $number);
        $newnumber = $parts[0] . "." . $parts[1][0] . $parts[1][1];
        return $newnumber;
    }

    public function is_wc_version_greater_2_3() {
        return $this->get_wc_version() && version_compare($this->get_wc_version(), '2.3', '>=');
    }

    public function get_wc_version() {
        return defined('WC_VERSION') && WC_VERSION ? WC_VERSION : null;
    }

}
