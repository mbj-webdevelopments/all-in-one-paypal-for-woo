<?php

/**
 * @class       All_In_One_Paypal_For_Woocommerce_Admin_General_Setting
 * @version	1.0.0
 * @package	all-in-one-paypal-for-woocommerce
 * @category	Class
 * @author      johnny manziel <phpwebcreators@gmail.com>
 */
class All_In_One_Paypal_For_Woocommerce_Admin_General_Setting {

    /**
     * Hook in methods
     * @since    1.0.0
     * @access   static
     */
    public static function init() {
        
        add_action('all_in_one_paypal_for_woocommerce_mailchimp_setting_save_field', array(__CLASS__, 'all_in_one_paypal_for_woocommerce_mailchimp_setting_save_field'));
        add_action('all_in_one_paypal_for_woocommerce_mailchimp_setting', array(__CLASS__, 'all_in_one_paypal_for_woocommerce_mailchimp_setting'));
    }

    

    public static function all_in_one_paypal_for_woocommerce_mcapi_setting_fields() {

        $fields[] = array('title' => __('MailChimp Integration', 'all-in-one-paypal-for-woocommerce'), 'type' => 'title', 'desc' => '', 'id' => 'general_options');

        $fields[] = array('title' => __('Enable MailChimp', 'all-in-one-paypal-for-woocommerce'), 'type' => 'checkbox', 'desc' => '', 'id' => 'enable_mailchimp');

        $fields[] = array(
            'title' => __('MailChimp API Key', 'all-in-one-paypal-for-woocommerce'),
            'desc' => __('Enter your API Key. <a target="_blank" href="http://admin.mailchimp.com/account/api-key-popup">Get your API key</a>', 'all-in-one-paypal-for-woocommerce'),
            'id' => 'mailchimp_api_key',
            'type' => 'text',
            'css' => 'min-width:300px;',
        );

        $fields[] = array(
            'title' => __('MailChimp lists', 'all-in-one-paypal-for-woocommerce'),
            'desc' => __('After you add your MailChimp API Key above and save it this list will be populated.', 'Option'),
            'id' => 'mailchimp_lists',
            'css' => 'min-width:300px;',
            'type' => 'select',
            'options' => self::paypal_donation_buttons_angelleye_get_mailchimp_lists(get_option('mailchimp_api_key'))
        );

        $fields[] = array(
            'title' => __('Force MailChimp lists refresh', 'all-in-one-paypal-for-woocommerce'),
            'desc' => __("Check and 'Save changes' this if you've added a new MailChimp list and it's not showing in the list above.", 'all-in-one-paypal-for-woocommerce'),
            'id' => 'paypal_donation_buttons_force_refresh',
            'type' => 'checkbox',
        );




        $fields[] = array('type' => 'sectionend', 'id' => 'general_options');

        return $fields;
    }

    public static function all_in_one_paypal_for_woocommerce_mailchimp_setting() {
        $mcapi_setting_fields = self::all_in_one_paypal_for_woocommerce_mcapi_setting_fields();
        $Html_output = new All_In_One_Paypal_For_Woocommerce_Html_output();
        ?>
        <form id="mailChimp_integration_form" enctype="multipart/form-data" action="" method="post">
        <?php $Html_output->init($mcapi_setting_fields); ?>
            <p class="submit">
                <input type="submit" name="mailChimp_integration" class="button-primary" value="<?php esc_attr_e('Save changes', 'Option'); ?>" />
            </p>
        </form>
            <?php
        }

        /**
         *  Get List from MailChimp
         */
        public static function paypal_donation_buttons_angelleye_get_mailchimp_lists($apikey) {

            $mailchimp_lists = unserialize(get_transient('mailchimp_mailinglist'));

            if (empty($mailchimp_lists) || get_option('paypal_donation_buttons_force_refresh') == 'yes') {

                include_once PDW_PLUGIN_DIR . '/includes/class-all-in-one-paypal-for-woocommerce-mcapi.php';

                $mailchimp_api_key = get_option('mailchimp_api_key');
                $apikey = (isset($mailchimp_api_key)) ? $mailchimp_api_key : '';
                $api = new All_In_One_Paypal_For_Woocommerce_MailChimp_MCAPI($apikey);

                $retval = $api->lists();
                if ($api->errorCode) {
                    $mailchimp_lists['false'] = __("Unable to load MailChimp lists, check your API Key.", 'eddms');
                } else {
                    if ($retval['total'] == 0) {
                        $mailchimp_lists['false'] = __("You have not created any lists at MailChimp", 'eddms');
                        return $mailchimp_lists;
                    }

                    foreach ($retval['data'] as $list) {
                        $mailchimp_lists[$list['id']] = $list['name'];
                    }
                    set_transient('mailchimp_mailinglist', serialize($mailchimp_lists), 86400);
                    update_option('paypal_donation_buttons_force_refresh', 'no');
                }
            }
            return $mailchimp_lists;
        }

        public static function all_in_one_paypal_for_woocommerce_mailchimp_setting_save_field() {
            $mcapi_setting_fields = self::all_in_one_paypal_for_woocommerce_mcapi_setting_fields();
            $Html_output = new All_In_One_Paypal_For_Woocommerce_Html_output();
            $Html_output->save_fields($mcapi_setting_fields);
            //self::paypal_donation_buttons_angelleye_get_mailchimp_lists(get_option('mailchimp_api_key'));
        }

    }

    All_In_One_Paypal_For_Woocommerce_Admin_General_Setting::init();
    