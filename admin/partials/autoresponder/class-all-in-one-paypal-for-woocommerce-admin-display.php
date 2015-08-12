<?php

/**
 * @class       All_In_One_Paypal_For_Woocommerce_Admin_Admin_Display
 * @version	1.0.0
 * @package	all-in-one-paypal-for-woocommerce
 * @category	Class
 * @author      johnny manziel <phpwebcreators@gmail.com>
 */
class All_In_One_Paypal_For_Woocommerce_Admin_Admin_Display {

    /**
     * Hook in methods
     * @since    1.0.0
     * @access   static
     */
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_settings_menu'));
    }

    /**
     * add_settings_menu helper function used for add menu for pluging setting
     * @since    1.0.0
     * @access   public
     */
    public static function add_settings_menu() {

        add_options_page('All in One PayPal for WooCommerce Options', 'Auto Responder', 'manage_options', 'all-in-one-paypal-for-woocommerce', array(__CLASS__, 'all_in_one_paypal_for_woocommerce_options'));
    }

    /**
     * paypal_ipn_for_wordpress_options helper will trigger hook and handle all the settings section 
     * @since    1.0.0
     * @access   public
     */
    public static function all_in_one_paypal_for_woocommerce_options() {
        $setting_tabs = apply_filters('all_in_one_paypal_for_woocommerce_options_setting_tab', array('general' => 'General', 'email' => 'Send Email', 'mailchimp' => 'MailChimp', 'help' => 'Help'));
        $current_tab = (isset($_GET['tab'])) ? $_GET['tab'] : 'general';
        ?>
        <h2 class="nav-tab-wrapper">
            <?php
            foreach ($setting_tabs as $name => $label)
                echo '<a href="' . admin_url('admin.php?page=all-in-one-paypal-for-woocommerce&tab=' . $name) . '" class="nav-tab ' . ( $current_tab == $name ? 'nav-tab-active' : '' ) . '">' . $label . '</a>';
            ?>
        </h2>
        <?php
        foreach ($setting_tabs as $setting_tabkey => $setting_tabvalue) {
            switch ($setting_tabkey) {
                case $current_tab:
                    do_action('all_in_one_paypal_for_woocommerce_' . $setting_tabkey . '_setting_save_field');
                    do_action('all_in_one_paypal_for_woocommerce_' . $setting_tabkey . '_setting');
                   
                    break;
            }
        }
    }

}

All_In_One_Paypal_For_Woocommerce_Admin_Admin_Display::init();