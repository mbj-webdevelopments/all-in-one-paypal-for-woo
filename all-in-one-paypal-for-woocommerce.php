<?php

/**
 * @wordpress-plugin
 * Plugin Name:       All in One PayPal for WooCommerce
 * Plugin URI:        http://localleadminer.com/
 * Description:       All in One PayPal for WooCommerce Developed by an Certified PayPal Developer, official PayPal Partner.
 * Version:           1.0.0
 * Author:            mbj-webdevelopment <mbjwebdevelopment@gmail.com>
 * Author URI:        http://localleadminer.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       all-in-one-paypal-for-woocommerce
 * Domain Path:       /languages
 */
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

if (!defined('PPFW_PLUGIN_DIR')) {
    define('PPFW_PLUGIN_DIR', dirname(__FILE__));
}

if (!defined('PPFW_PLUGIN_DIR_BASE')) {
    define('PPFW_PLUGIN_DIR_BASE', plugin_basename(__FILE__));
}

if (!defined('PEC_PLUGIN_DIR'))
    define('PEC_PLUGIN_DIR', dirname(__FILE__));

if (!defined('PEC_PLUGIN_DIR_BASE'))
    define('PEC_PLUGIN_DIR_BASE', plugin_basename(__FILE__));

if (!defined('PEC_PLUGIN_DIR_PATH'))
    define('PEC_PLUGIN_DIR_PATH', plugin_dir_path( __FILE__ ));

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-all-in-one-paypal-for-woocommerce-activator.php
 */
function activate_all_in_one_paypal_for_woocommerce() {
    require_once plugin_dir_path(__FILE__) . 'includes/class-all-in-one-paypal-for-woocommerce-activator.php';
    All_In_One_Paypal_For_Woocommerce_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-all-in-one-paypal-for-woocommerce-deactivator.php
 */
function deactivate_all_in_one_paypal_for_woocommerce() {
    require_once plugin_dir_path(__FILE__) . 'includes/class-all-in-one-paypal-for-woocommerce-deactivator.php';
    All_In_One_Paypal_For_Woocommerce_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_all_in_one_paypal_for_woocommerce');
register_deactivation_hook(__FILE__, 'deactivate_all_in_one_paypal_for_woocommerce');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-all-in-one-paypal-for-woocommerce.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_all_in_one_paypal_for_woocommerce() {

    $plugin = new All_In_One_Paypal_For_Woocommerce();
    $plugin->run();
}

run_all_in_one_paypal_for_woocommerce();