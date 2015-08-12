<?php

/**
 * @class       All_In_One_Paypal_For_Woocommerce_Admin_WordPress_List
 * @version	1.0.0
 * @package	paypal-donation-for-wordpress
 * @category	Class
 * @author      johnny manziel <phpwebcreators@gmail.com>
 */
class All_In_One_Paypal_For_Woocommerce_Admin_WordPress_List {

    /**
     * Hook in methods
     * @since    1.0.0
     * @access   static
     */
    public static function init() {
        add_action('admin_print_scripts', array(__CLASS__, 'disable_autosave'));
        if (  is_admin() ) {
            add_action('init', array(__CLASS__, 'all_in_one_paypal_for_woocommerce_register_post_types'), 5);
        }
        add_action('add_meta_boxes', array(__CLASS__, 'all_in_one_paypal_for_woocommerce_remove_meta_boxes'), 10);
        add_action('manage_edit-paypal_payment_list_columns', array(__CLASS__, 'all_in_one_paypal_for_woocommerce_add_paypal_payment_list_columns'), 10, 2);
        add_action('manage_paypal_payment_list_posts_custom_column', array(__CLASS__, 'all_in_one_paypal_for_woocommerce_render_paypal_payment_list_columns'), 2);
        add_filter('manage_edit-paypal_payment_list_sortable_columns', array(__CLASS__, 'all_in_one_paypal_for_woocommerce_paypal_payment_list_sortable_columns'));
        add_action('pre_get_posts', array(__CLASS__, 'all_in_one_paypal_for_woocommerce_ipn_column_orderby'));
        add_action('add_meta_boxes', array(__CLASS__, 'all_in_one_paypal_for_woocommerce_add_meta_boxes_ipn_data_custome_fields'), 31);
    }

    /**
     * all_in_one_paypal_for_woocommerce_register_post_types function
     * @since    1.0.0
     * @access   public
     */
    public static function all_in_one_paypal_for_woocommerce_register_post_types() {
        global $wpdb;
        if (post_type_exists('paypal_payment_list')) {
            return;
        }

        do_action('all_in_one_paypal_for_woocommerce_register_post_type');

        register_post_type('paypal_payment_list', apply_filters('all_in_one_paypal_for_woocommerce_register_post_type_ipn', array(
            'labels' => array(
                'name' => __('PayPal Payment', 'all-in-one-paypal-for-woocommerce'),
                'singular_name' => __('PayPal Payment', 'all-in-one-paypal-for-woocommerce'),
                'menu_name' => _x('PayPal Payment', 'Admin menu name', 'all-in-one-paypal-for-woocommerce'),
                'add_new' => __('Add PayPal Payment', 'all-in-one-paypal-for-woocommerce'),
                'add_new_item' => __('Add New PayPal Payment', 'all-in-one-paypal-for-woocommerce'),
                'edit' => __('Edit', 'all-in-one-paypal-for-woocommerce'),
                'edit_item' => __('View PayPal Payment', 'all-in-one-paypal-for-woocommerce'),
                'new_item' => __('New PayPal Payment', 'all-in-one-paypal-for-woocommerce'),
                'view' => __('View PayPal Payment', 'all-in-one-paypal-for-woocommerce'),
                'view_item' => __('View PayPal Payment', 'all-in-one-paypal-for-woocommerce'),
                'search_items' => __('Search PayPal Payment', 'all-in-one-paypal-for-woocommerce'),
                'not_found' => __('No PayPal Payment found', 'all-in-one-paypal-for-woocommerce'),
                'not_found_in_trash' => __('No PayPal Payment found in trash', 'all-in-one-paypal-for-woocommerce'),
                'parent' => __('Parent PayPal Payment', 'all-in-one-paypal-for-woocommerce')
            ),
            'description' => __('This is where you can add new IPN to your store.', 'all-in-one-paypal-for-woocommerce'),
            'public' => false,
            'show_ui' => true,
            'capability_type' => 'post',
            'capabilities' => array(
                'create_posts' => false, // Removes support for the "Add New" function
            ),
            'map_meta_cap' => true,
            'publicly_queryable' => true,
            'exclude_from_search' => false,
            'hierarchical' => false, // Hierarchical causes memory issues - WP loads all records!
            'rewrite' => array('slug' => 'paypal_payment_list'),
            'query_var' => true,
            'menu_icon' => PDW_PLUGIN_URL . 'admin/images/all-in-one-paypal-for-woocommerce.png',
            'supports' => array('', ''),
            'has_archive' => true,
            'show_in_nav_menus' => true
                        )
                )
        );
    }

    /**
     * all_in_one_paypal_for_woocommerce_remove_meta_boxes function used for remove submitdiv meta_box for paypal_payment_list custome post type
     * https://core.trac.wordpress.org/ticket/12706
     * I have remove submitdiv meta_box because it not support custome register_post_status like  Completed | Denied
     * @since    1.0.0
     * @access   public
     */
    public static function all_in_one_paypal_for_woocommerce_remove_meta_boxes() {

        remove_meta_box('submitdiv', 'paypal_payment_list', 'side');
        remove_meta_box('slugdiv', 'paypal_payment_list', 'normal');
    }

    /**
     * Define custom columns for IPN
     * @param  array $existing_columns
     * @since    1.0.0
     * @access   public
     * @return array
     */
    public static function all_in_one_paypal_for_woocommerce_add_paypal_payment_list_columns($existing_columns) {
        $columns = array();
        $columns['cb'] = '<input type="checkbox" />';
        $columns['title'] = _x('Transaction ID', 'column name');
        $columns['first_name'] = _x('Name / Company', 'column name');
        $columns['mc_gross'] = __('Amount', 'column name');
        $columns['txn_type'] = __('Transaction Type', 'column name');
        $columns['payment_status'] = __('Payment status');
        $columns['payment_date'] = _x('Date', 'column name');
        return $columns;
    }

    /**
     * all_in_one_paypal_for_woocommerce_render_paypal_payment_list_columns helper function used add own column in IPN listing
     * @since    1.0.0
     * @access   public
     */
    public static function all_in_one_paypal_for_woocommerce_render_paypal_payment_list_columns($column) {
        global $post;

        switch ($column) {
            case 'payment_date' :
                echo esc_attr(get_post_meta($post->ID, 'payment_date', true));
                break;
            case 'first_name' :
                echo esc_attr(get_post_meta($post->ID, 'first_name', true) . ' ' . get_post_meta($post->ID, 'last_name', true));
                echo (get_post_meta($post->ID, 'payer_business_name', true)) ? ' / ' . get_post_meta($post->ID, 'payer_business_name', true) : '';
                break;
            case 'mc_gross' :
                echo esc_attr(get_post_meta($post->ID, 'mc_gross', true)) . ' ' . esc_attr(get_post_meta($post->ID, 'mc_currency', true));
                break;
            case 'txn_type' :
                echo esc_attr(get_post_meta($post->ID, 'txn_type', true));
                break;

            case 'payment_status' :
                echo esc_attr(get_post_meta($post->ID, 'payment_status', true));
                break;
        }
    }

    /**
     * Disable the auto-save functionality for IPN.
     * @since    1.0.0
     * @access   public
     * @return void
     */
    public static function disable_autosave() {
        global $post;

        if ($post && get_post_type($post->ID) === 'paypal_payment_list') {
            wp_dequeue_script('autosave');
        }
    }

    /**
     * all_in_one_paypal_for_woocommerce_paypal_payment_list_sortable_columns helper function used for make column shortable.
     * @since    1.0.0
     * @access   public
     * @return $columns
     */
    public static function all_in_one_paypal_for_woocommerce_paypal_payment_list_sortable_columns($columns) {

        $custom = array(
            'title' => 'txn_id',
            'invoice' => 'invoice',
            'payment_date' => 'payment_date',
            'first_name' => 'first_name',
            'mc_gross' => 'mc_gross',
            'txn_type' => 'txn_type',
            'payment_status' => 'payment_status',
            'payment_date' => 'payment_date'
        );

        return wp_parse_args($custom, $columns);
    }

    /**
     * all_in_one_paypal_for_woocommerce_ipn_column_orderby helper function used for shorting query handler
     * @since    1.0.0
     * @access   public
     */
    public static function all_in_one_paypal_for_woocommerce_ipn_column_orderby($query) {
        global $wpdb;
        if (is_admin() && isset($_GET['post_type']) && $_GET['post_type'] == 'paypal_payment_list' && isset($_GET['orderby']) && $_GET['orderby'] != 'None') {
            $query->query_vars['orderby'] = 'meta_value';
            $query->query_vars['meta_key'] = $_GET['orderby'];
        }
    }

    /**
     * all_in_one_paypal_for_woocommerce_add_meta_boxes_ipn_data_custome_fields function used for register own meta_box for display IPN custome filed read only
     * @since    1.0.0
     */
    public static function all_in_one_paypal_for_woocommerce_add_meta_boxes_ipn_data_custome_fields() {

        add_meta_box('paypal-payment-list-ipn-data-custome-field', __('PayPal Payment List Fields', 'all-in-one-paypal-for-woocommerce'), array(__CLASS__, 'all_in_one_paypal_for_woocommerce_display_ipn_custome_fields'), 'paypal_payment_list', 'normal', 'high');
    }

    /**
     * all_in_one_paypal_for_woocommerce_display_ipn_custome_fields helper function used for display raw dump in html format
     * @since    1.0.0
     * @access   public
     */
    public static function all_in_one_paypal_for_woocommerce_display_ipn_custome_fields() {
        if ($keys = get_post_custom_keys()) {
            echo "<div class='wrap'>";
            echo "<table class='widefat'><thead>
                        <tr>
                            <th>" . __('IPN Field Name', 'all-in-one-paypal-for-woocommerce') . "</th>
                            <th>" . __('IPN Field Value', 'all-in-one-paypal-for-woocommerce') . "</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <th>" . __('IPN Field Name', 'all-in-one-paypal-for-woocommerce') . "</th>
                            <th>" . __('IPN Field Value', 'all-in-one-paypal-for-woocommerce') . "</th>

                        </tr>
                    </tfoot>";
            foreach ((array) $keys as $key) {
                $keyt = trim($key);
                if (is_protected_meta($keyt, 'post'))
                    continue;
                $values = array_map('trim', get_post_custom_values($key));
                $value = implode($values, ', ');

                /**
                 * Filter the HTML output of the li element in the post custom fields list.
                 *
                 * @since 1.0.0
                 *
                 * @param string $html  The HTML output for the li element.
                 * @param string $key   Meta key.
                 * @param string $value Meta value.
                 */
                echo "<tr><th class='post-meta-key'>$key:</th> <td>$value</td></tr>";
            }
            echo "</table>";
            echo "</div";
        }
    }

}

All_In_One_Paypal_For_Woocommerce_Admin_WordPress_List::init();