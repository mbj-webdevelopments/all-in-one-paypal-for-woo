<?php

/**
 * The main PayPal Digital Goods class - not ideal wrapping a class in a function, but that's the WooCommerce-Way
 */
class WC_Gateway_Paypal_Digital_Goods extends WC_Payment_Gateway {

	private $paypal_ipn_email = NULL;

	public function __construct() {
		global $woocommerce;

		$this->id           = 'paypal_digital_goods';
		$this->has_fields   = false;
		$this->liveurl      = 'https://www.paypal.com/webscr';
		$this->testurl      = 'https://www.sandbox.paypal.com/webscr';
		$this->method_title = __( 'PayPal Digital Goods', 'ppdg' );
		$this->supports     = array(
			'products',
			'refunds',
			'subscriptions',
			'subscription_suspension',
			'subscription_reactivation',
			'subscription_cancellation',
			'gateway_scheduled_payments',
		);

		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		// Define user set variables
		$this->title            = $this->get_option( 'title' );
		$this->description      = $this->get_option( 'description' );
		$this->username         = $this->get_option( 'username' );
		$this->password         = $this->get_option( 'password' );
		$this->signature        = $this->get_option( 'signature' );
		$this->testmode         = $this->get_option( 'testmode' );
		$this->debug            = $this->get_option( 'debug' );
		$this->invoice_prefix   = $this->get_option( 'invoice_prefix', '' );

		if ( $this->are_credentials_set() ) {
			PayPal_Digital_Goods_Configuration::username( $this->username );
			PayPal_Digital_Goods_Configuration::password( $this->password );
			PayPal_Digital_Goods_Configuration::signature( $this->signature );
		}

		if ( $this->testmode == 'yes' ) {
			$this->view_transaction_url = 'https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_view-a-trans&id=%s';
			PayPal_Digital_Goods_Configuration::environment( 'sandbox' );
		} else {
			$this->view_transaction_url = 'https://www.paypal.com/cgi-bin/webscr?cmd=_view-a-trans&id=%s';
			PayPal_Digital_Goods_Configuration::environment( 'live' );
		}

		PayPal_Digital_Goods_Configuration::currency( apply_filters( 'woocommerce_paypal_digital_goods_currency', get_woocommerce_currency() ) );

		// Logs
		if ( $this->debug == 'yes' ) {
			$this->log = class_exists( 'WC_Logger' ) ? new WC_Logger() : $woocommerce->logger(); // WC <> 2.1
		}

		$this->locale_code = apply_filters( 'plugin_locale', get_locale(), 'ppdg' );

		// Actions
		add_action( 'woocommerce_receipt_'.$this->id, array( &$this, 'receipt_page' ) );
		add_action( 'woocommerce_thankyou_'.$this->id, array( &$this, 'thankyou_page' ) );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) ); // WC > 2.0
		add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) ); // WC < 2.0
		add_action( 'woocommerce_after_checkout_form', array( &$this, 'hook_to_checkout' ) );

		// When a subscriber or store manager cancel's a subscription in the store, suspend it with PayPal
		add_action( 'subscription_expired_' . $this->id, array( &$this, 'cancel_subscription_with_paypal' ), 10, 2 );
		add_action( 'cancelled_subscription_' . $this->id, array( &$this, 'cancel_subscription_with_paypal' ), 10, 2 );
		add_action( 'suspended_subscription_' . $this->id, array( &$this, 'suspend_subscription_with_paypal' ), 10, 2 );
		add_action( 'reactivated_subscription_' . $this->id, array( &$this, 'reactivate_subscription_with_paypal' ), 10, 2 );

		if ( ! $this->is_valid_currency() || ! $this->are_credentials_set() || ! $this->is_ipn_email_set() ) {
			$this->enabled = false;
		}
	}

	/**
	 * Check if the gateway is enabled and available in the user's country
	 *
	 * @since 1.0
	 */
	function is_available() {
		global $woocommerce;

		$is_available = true;

		if ( $this->enabled != 'yes' ) {
			$is_available = false;
		} elseif ( ! $this->is_valid_currency() ) {
			$is_available = false;
		} elseif ( ! $this->are_credentials_set() ) {
			$is_available = false;
		} elseif ( ! $this->is_ipn_email_set() ) {
			$is_available = false;
		}

		return $is_available;
	}

	/**
	 * Hook to the checkout ajax for super fast payment.
	 *
	 * @since 1.0
	 */
	function hook_to_checkout() {
		global $woocommerce; ?>

<script src ="https://www.paypalobjects.com/js/external/dg.js" type="text/javascript"></script>
<script type="text/javascript">
jQuery(document).ready(function($){
$('form.checkout').on('checkout_place_order_<?php echo $this->id; ?>',function(event){
	var $form = $(this),
		form_data = $form.data(),
		checkout_url = ( typeof window['wc_checkout_params'] === 'undefined' ) ? woocommerce_params.checkout_url : wc_checkout_params.checkout_url; // WC 2.1 compat

	// PayPal DG flow doesn't work on iOS, so fall back to Express Checkout Mobile flow
	if(window.innerWidth <= 800 || /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
		$('<input>').attr({
			type: 'hidden',
			id: 'ppdg_mobile_checkout',
			name: 'ppdg_mobile_checkout',
			value: 'yes',
		}).appendTo($form);
		return true;
	}

	if ( form_data["blockUI.isBlocked"] != 1 ) {
		$form.block({message: null, overlayCSS: {background: '#fff url(' + woocommerce_params.ajax_loader_url + ') no-repeat center', backgroundSize: '16px 16px', opacity: 0.6}});
	}

	$.ajax({
		type:	 'POST',
		url:	 checkout_url,
		data:	 $form.serialize(),
		success: function(code) {
			$('.woocommerce_error, .woocommerce_message').remove();
			try {
				// Get the valid JSON only from the returned string
				if ( code.indexOf("<!--WC_START-->") >= 0 ) {
					code = code.split("<!--WC_START-->")[1]; // Strip off before after WC_START
				}

				if ( code.indexOf("<!--WC_END-->") >= 0 ) {
					code = code.split("<!--WC_END-->")[0]; // Strip off anything after WC_END
				}

				var result;
				try {
					result = $.parseJSON( code );
				} catch (error) {
					result = {
						result: 'failure',
						messages: $('<div/>').addClass('woocommerce-error').text(code)
					};
				}

				if (result.result=='success') {
					var dg = new PAYPAL.apps.DGFlow({trigger:'place_order'});
					try {
						dg.startFlow(result.redirect);
					} catch (error){
						$('.woocommerce-error, .woocommerce-message').remove();
						$form.prepend( $('<div/>').addClass('woocommerce-error').html('<?php _e( "Could not initiate PayPal flow. Do you have popups blocked?", "ppdg" ); ?></br>'+error) );
						$form.removeClass('processing').unblock();
						$form.find( '.input-text, select' ).blur();

						$('html, body').animate({
						    scrollTop: ($('form.checkout').offset().top - 100)
						}, 1000);
					}
				} else if (result.result=='failure') {

					$('.woocommerce-error, .woocommerce-message').remove();
					$form.prepend( result.messages );
					$form.removeClass('processing').unblock();
					$form.find( '.input-text, select' ).blur();

					if (result.refresh=='true') {
						$('body').trigger('update_checkout');
					}

					$('html, body').animate({
						scrollTop: ($form.offset().top - 100)
					}, 1000);

				} else {
					throw 'Invalid response';
				}
			}
			catch(err) {
				$('.woocommerce-error, .woocommerce-message').remove();
				$form.prepend( $('<div/>').addClass('woocommerce-error').text(err) );
				$form.removeClass('processing').unblock();
				$form.find( '.input-text, select' ).blur();

				$('html, body').animate({
				    scrollTop: ($('form.checkout').offset().top - 100)
				}, 1000);
			}
		},
		dataType: 'html'
	});
	return false;
});
});
</script>
<?php
	}

	/**
	 * Check if PayPal can be used with the store's currency.
	 *
	 * @since 1.0
	 */
	function is_valid_currency() {
		if ( ! in_array( get_woocommerce_currency(), apply_filters( 'woocommerce_paypal_supported_currencies', array( 'AUD', 'BRL', 'CAD', 'MXN', 'NZD', 'HKD', 'SGD', 'USD', 'EUR', 'JPY', 'TRY', 'NOK', 'CZK', 'DKK', 'HUF', 'ILS', 'MYR', 'PHP', 'PLN', 'SEK', 'CHF', 'TWD', 'THB', 'GBP' ) ) ) ) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Check if PayPal API Credentials are set
	 *
	 * @since 1.0
	 */
	function are_credentials_set() {
		if ( empty( $this->username ) || empty( $this->password ) || empty( $this->signature ) ) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Check if PayPal IPN email is set.
	 *
	 * WooCommerce uses the email address stored in the PayPal settings for IPN verification, so make sure it
	 * is set before allowing the gateway to be used.
	 *
	 * @since 3.1
	 */
	function is_ipn_email_set() {

		if ( NULL == $this->paypal_ipn_email ) {

			$paypal_settings = get_option( 'woocommerce_paypal_settings', array() );

			if ( isset( $paypal_settings['receiver_email'] ) && ! empty( $paypal_settings['receiver_email'] ) ) {
				$this->paypal_ipn_email = $paypal_settings['receiver_email'];
			} elseif ( isset( $paypal_settings['email'] ) ) {
				$this->paypal_ipn_email = $paypal_settings['email'];
			}
		}

		return ( NULL !== $this->paypal_ipn_email && ! empty( $this->paypal_ipn_email ) ) ? true : false;
	}

	/**
	 * Admin Panel Options
	 *
	 * @since 1.0
	 */
	public function admin_options() { ?>
		<h3><?php _e( 'PayPal Digital Goods', 'ppdg' ); ?></h3>
		<p><?php _e( 'PayPal Digital Goods offers in-context payments via PayPal for orders with your store.', 'ppdg' ); ?></p>
		<p><?php printf( __( 'If you have not already done so, you need to sign up for a PayPal business account and set it to use Digital Goods with Express Checkout. Learn how to configure your account to use Digital Goods in %sthis tutorial%s.', 'ppdg' ), '<a href="http://docs.woothemes.com/document/paypal-digital-goods-for-express-checkout-gateway/" target="_blank" tabindex="-1">', '</a>' ); ?></p>
		<table class="form-table">
			<?php if ( ! $this->is_valid_currency() ) : ?>
				<div class="inline error">
					<p><strong><?php _e( 'Gateway Disabled:', 'ppdg' ); ?></strong> <?php _e( 'PayPal does not support your store\'s currency.', 'ppdg' ); ?></p>
				</div>
			<?php elseif ( ! $this->is_ipn_email_set() ) : ?>
				<div class="inline error">
					<p><strong><?php _e( 'Gateway Disabled:', 'ppdg' ); ?></strong> <?php printf( __( 'You must set your PayPal email address on the %sPayPal Settings%s screen so that IPN mesages can be verified.', 'ppdg' ), '<a href="' . esc_url( $this->get_paypal_standard_settings_page_url() ) . '">', '</a>' ); ?></p>
				</div>
			<?php else : ?>
				<?php $this->generate_settings_html(); ?>
			<?php endif; ?>
	</table><!--/.form-table-->
	<?php
	}

	protected function get_paypal_standard_settings_page_url() {

		if ( ppdg_is_woocommerce_pre_2_1() ) {
			$payment_gateway_tab_url = admin_url( 'admin.php?page=woocommerce_settings&tab=payment_gateways&section=WC_Gateway_Paypal' );
		} else {
			$payment_gateway_tab_url = admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wc_gateway_paypal' );
		}

		return $payment_gateway_tab_url;
	}

	/**
	 * The PayPal Digital Goods Settings Form Fields
	 *
	 * @since 1.0
	 */
	function init_form_fields() {

		$this->form_fields = array(
			'enabled' => array(
				'title'   => __( 'Enable/Disable', 'ppdg' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable PayPal Digital Goods for Express Checkout', 'ppdg' ),
				'default' => 'yes'
			),
			'title'        => array(
				'title'       => __( 'Title', 'ppdg' ),
				'type'        => 'text',
				'description' => __( 'Give a title for this gateway to display to the user during checkout.', 'ppdg' ),
				'default'     => __( 'PayPal Digital Goods', 'ppdg' ),
				'desc_tip'    => true,
			),
			'description'  => array(
				'title'       => __( 'Description', 'ppdg' ),
				'type'        => 'textarea',
				'description' => __( 'This controls the description which the user sees during checkout.', 'ppdg' ),
				'default'     => __( 'The quickest way to pay with PayPal.', 'ppdg' ),
				'desc_tip'    => true,
			),
			'invoice_prefix' => array(
				'title'       => __( 'Invoice Prefix', 'ppdg' ),
				'type'        => 'text',
				'description' => __( 'Optionally enter a prefix for your invoice numbers. If you use your PayPal account for multiple stores ensure this prefix is unique as PayPal will not allow orders with the same invoice number.', 'ppdg' ),
				'default'     => '',
				'desc_tip'    => true,
			),
			'username'     => array(
				'title'       => __( 'API Username', 'ppdg' ),
				'type'        => 'text',
				'description' => sprintf( __( 'This is the API username generated by PayPal. %sLearn More &raquo;%s', 'ppdg' ), '<a href="http://docs.woothemes.com/document/paypal-digital-goods-for-express-checkout-gateway/#section-3" target="_blank" tabindex="-1">', '</a>' ),
				'default'     => '',
				'desc_tip'    => true,
			),
			'password'     => array(
				'title'       => __( 'API Password', 'ppdg' ),
				'type'        => 'text',
				'description' => sprintf( __( 'This is the API password generated by PayPal. %sLearn More &raquo;%s', 'ppdg' ), '<a href="http://docs.woothemes.com/document/paypal-digital-goods-for-express-checkout-gateway/#section-3" target="_blank" tabindex="-1">', '</a>' ),
				'default'     => '',
				'desc_tip'    => true,
			),
			'signature'    => array(
				'title'       => __( 'API Signature', 'ppdg' ),
				'type'        => 'text',
				'description' => sprintf( __( 'This is the API signature generated by PayPal. %sLearn More &raquo;%s', 'ppdg' ), '<a href="http://docs.woothemes.com/document/paypal-digital-goods-for-express-checkout-gateway/#section-3" target="_blank" tabindex="-1">', '</a>' ),
				'default'     => '',
				'desc_tip'    => true,
			),
			'testmode'     => array(
				'title'       => __( 'PayPal Sandbox', 'ppdg' ),
				'type'        => 'checkbox',
				'label'       => __( 'Use the PayPal Sandbox', 'ppdg' ),
				'default'     => 'yes'
			),
			'debug'        => array(
				'title'       => __( 'Debug', 'ppdg' ),
				'type'        => 'checkbox',
				'label'       => sprintf( __( 'Enable logging (<code>woocommerce/logs/paypal-dg-%s.txt</code>)', 'ppdg' ), sanitize_file_name( wp_hash( 'paypal-dg' ) ) ),
				'default'     => 'no'
			),
		);
	}

	/**
	 * Generate a PayPal Digital Goods object depending on whether the order
	 * is for a product purchase or a subscription.
	 *
	 * @since 1.0
	 **/
    public function get_paypal_object( $order_id ) {
		global $woocommerce;

		$order = new WC_Order( $order_id );

		if ( $this->debug=='yes' ) {
			$this->log->add( 'paypal-dg', 'PayPal Digital Goods generating payment object for order #' . $order_id . '.' );
		}

		PayPal_Digital_Goods_Configuration::return_url( $this->get_return_url( $order ) );
		PayPal_Digital_Goods_Configuration::cancel_url( $this->get_cancel_payment_url( $order ) );
		PayPal_Digital_Goods_Configuration::notify_url( $this->get_notify_url() );
		PayPal_Digital_Goods_Configuration::locale_code( $this->locale_code );

		PayPal_Digital_Goods_Configuration::currency( apply_filters( 'woocommerce_paypal_digital_goods_currency', $order->get_order_currency() ) );

		if ( isset( $_REQUEST['ppdg_mobile_checkout'] ) ) {
			PayPal_Digital_Goods_Configuration::mobile_url( 'yes' );
		}

		if ( class_exists( 'WC_Subscriptions_Order' ) && WC_Subscriptions_Order::order_contains_subscription( $order ) ) {
			$paypal_object = $this->get_subscription_object( $order );
		} else {
			$paypal_object = $this->get_purchase_object( $order );
		}

		return $paypal_object;
	}

	/**
	 * Generate a PayPal Digital Goods Purchase object.
	 *
	 * @since 1.0
	 **/
    public function get_subscription_object( $order ) {
		global $woocommerce;

		$recurring_amount          = WC_Subscriptions_Order::get_recurring_total( $order );
		$sign_up_fee_total         = WC_Subscriptions_Order::get_sign_up_fee( $order );
		$subscription_length       = WC_Subscriptions_Order::get_subscription_length( $order );
		$subscription_interval     = WC_Subscriptions_Order::get_subscription_interval( $order );
		$subscription_trial_length = WC_Subscriptions_Order::get_subscription_trial_length( $order );

		$is_synced_subscription    = WC_Subscriptions_Synchroniser::order_contains_synced_subscription( $order->id ) || WC_Subscriptions_Synchroniser::cart_contains_synced_subscription();


		// If the subscription is for one billing period with no free trial, just process it as a normal transaction
		if ( $subscription_length == $subscription_interval && 0 == $subscription_trial_length && false == $is_synced_subscription ) {
			return $this->get_purchase_object( $order );
		}

		$paypal_args = array(
			// Payment Info
			'invoice_number' => $this->invoice_prefix . $order->id,
			'custom'         => $order->order_key,
			'BUTTONSOURCE'   => 'WooThemes_Cart',
			// Price
			'amount'         => $recurring_amount,
			'average_amount' => $recurring_amount,
			// Temporal Details
			'start_date'     => apply_filters( 'woocommerce_paypal_digital_goods_subscription_start_date', gmdate( 'Y-m-d\TH:i:s', gmdate( 'U' ) + ( 13 * 60 * 60 ) ), $order ),
			'frequency'      => '1',
		);

		if ( $is_synced_subscription ) {
			$subscription            = WC_Subscriptions_Manager::get_subscription( WC_Subscriptions_Manager::get_subscription_key( $order->id ) );
			$id_for_calculation      = ! empty( $subscription['variation_id'] ) ? $subscription['variation_id'] : $subscription['product_id'];
			$first_payment_timestamp = WC_Subscriptions_Synchroniser::calculate_first_payment_date( $id_for_calculation, 'timestamp', $order->order_date );
			$paypal_args['start_date'] = gmdate( 'Y-m-d\TH:i:s', $first_payment_timestamp );
		}

		$order_items = $order->get_items();

		// Only one subscription allowed in the cart for PayPal
		$product = $order->get_product_from_item( array_shift( $order_items ) );

		$paypal_args['name'] = $product->get_title();

		$paypal_args['description'] = $product->get_title() . ' - ' . WC_Subscriptions_Order::get_order_subscription_string( $order );

		// Strip HTML
		$paypal_args['description'] = str_replace( array( '<span class="amount">', '</span>' ), '', $paypal_args['description'] );

		// Use real currency symbol (yes, dollar biased)
		$paypal_args['description'] = str_replace( '&#36;', '$', $paypal_args['description'] );

		// Subscription unit of duration
		$paypal_args['period'] = ucfirst( WC_Subscriptions_Order::get_subscription_period( $order ) );

		// Interval of subscription payments
		$paypal_args['frequency'] = WC_Subscriptions_Order::get_subscription_interval( $order );

		if ( ! $is_synced_subscription ) {
			$paypal_args['initial_amount'] = WC_Subscriptions_Order::get_total_initial_payment( $order, $product->id );
		} elseif ( $sign_up_fee_total > 0 ) {
			$paypal_args['initial_amount'] = $sign_up_fee_total;
		}

		if ( $subscription_trial_length > 0 ) {

			$paypal_args['trial_period']       = ucfirst( WC_Subscriptions_Order::get_subscription_trial_period( $order ) );
			$paypal_args['trial_frequency']    = 1;
			$paypal_args['trial_total_cycles'] = $subscription_trial_length;

		} elseif ( ! $is_synced_subscription ) {

			// We charge the first payment using an initial amount to work around PayPal's unreliable start date handling, discussed here: http://stackoverflow.com/questions/10578283/paypal-express-checkout-recurring-profile-start-date
			// Becauase of that, we need to use a free trial to account for the first payment
			$paypal_args['trial_period']       = $paypal_args['period'];
			$paypal_args['trial_frequency']    = 1;
			$paypal_args['trial_total_cycles'] = $paypal_args['frequency'];

			$subscription_length = $subscription_length - $subscription_interval;

		}

		// Number of times that subscription payments recur
		if ( $subscription_length > 0 ) {
			$paypal_args['total_cycles'] = $subscription_length / $subscription_interval;
		} else {
			$paypal_args['total_cycles'] = 0;
		}

		$paypal_args['max_failed_payments'] = 1;

		$paypal_args['add_to_next_bill'] = ( 'yes' == get_option( WC_Subscriptions_Admin::$option_prefix . '_add_outstanding_balance' ) ) ? true : false;

		$paypal_args = apply_filters( 'woocommerce_paypal_digital_goods_nvp_args', $paypal_args );

		$paypal_object = new PayPal_Subscription( $paypal_args );

		return $paypal_object;
	}

	/**
	 * Generate a PayPal Digital Goods Purchase object.
	 *
	 * @since 1.0
	 **/
    public function get_purchase_object( $order ) {
		global $woocommerce;

		if ( ! is_object( $order  ) ) { // Backward compatability
			_deprecated_argument( __FUNCTION__, '2.0', sprintf( __( '%s requires a WC_Order object, not an order ID.', 'ppdg' ), __FUNCTION__ ) );
			$order = new WC_Order( $order );
		}

		$order_total    = ( method_exists( $order, 'get_total' ) ) ? $order->get_total() : $order->get_order_total(); // WC 2.1 compat
		$shipping_total = ( method_exists( $order, 'get_total_shipping' ) ) ? $order->get_total_shipping() : $order->get_shipping(); // WC 2.1 compat

		$paypal_args = array(
			'name'           => sprintf( __( 'Order #%s' , 'ppdg' ), $order->id ),
			'description'    => sprintf( __( 'Payment for Order #%s' , 'ppdg' ), $order->id ),
			'BUTTONSOURCE'   => 'WooThemes_Cart',
			// Price
			'amount'         => number_format( $order_total, 2, '.', '' ),
			'tax_amount'     => number_format( $order->get_total_tax(), 2, '.', '' ),
			// Payment Info
			'invoice_number' => $this->invoice_prefix . $order->id,
			'custom'         => $order->order_key,
		);

		$paypal_items = array();

		// If the order includes a discount, don't pass individual items as PayPal does not provide a discount parameter
		// Also, if prices include tax, don't pass individual items because paypal does not support inc.tax prices
		// Finally, if the order includes shipping (which it really shouldn't because this is meant for digital goods only) don't pass individual items because paypal DG doesn't support shipping
		if ( $order->get_total_discount() > 0 || $shipping_total > 0 || get_option( 'woocommerce_prices_include_tax' ) == 'yes' ) :

			$paypal_items['item_name']        = sprintf( __( 'Order #%s', 'ppdg' ), $order->id );
			$paypal_items['item_description'] = sprintf( __( 'Payment for Order #%s', 'ppdg' ), $order->id );
			$paypal_items['item_number']      = $order->id;
			$paypal_items['item_quantity']    = 1;
			$paypal_items['item_amount']      = number_format( $order_total - $order->get_total_tax(), 2, '.', '' );
			$paypal_items['item_tax']         = number_format( $order->get_total_tax(), 2, '.', '' );

			$paypal_items = array( $paypal_items );

		else :
			// Cart Contents
			if ( count( $order->get_items() ) > 0 ) {
				$item_count = 0;

				foreach ( $order->get_items() as $item ) {

					if ( $item['qty'] > 0 && $order->get_item_total( $item ) > 0 ) {

						// Add the item's details
						$paypal_items[$item_count]['item_name']     = $item['name'];
						$paypal_items[$item_count]['item_quantity'] = $item['qty'];
						$paypal_items[$item_count]['item_amount']   = number_format( $order->get_item_total( $item ), 2, '.', '' );
						$paypal_items[$item_count]['item_tax']      = number_format( $order->get_item_total( $item, true ) - $order->get_item_total( $item ), 2, '.', '' );

						$product = $order->get_product_from_item( $item );

						if ( $product->get_sku() ) {
							$paypal_args[$item_count]['item_number'] = $product->get_sku();
						}

						// Maybe add item meta as a description
						$item_meta = new WC_Order_Item_Meta( $item['item_meta'] );
						if ( $meta = $item_meta->display( true, true ) ) {
							$paypal_items[$item_count]['item_description'] = $item['name'] . ' ('.$meta.')';
						}

						$item_count++;
					}
				}

				// If the order has tax, make sure the order's tax == sum of item taxes to overcome rounding bugs
				if ( $paypal_args['tax_amount'] > 0 ) {

					$total_item_tax = 0;

					foreach( $paypal_items as $paypal_item )
						$total_item_tax += $paypal_item['item_tax'] * $paypal_item['item_quantity'];

					if ( $paypal_args['tax_amount'] != $total_item_tax ) {
						$paypal_args['tax_amount'] = $total_item_tax;
					}

				}
			}

		endif;

		$paypal_args['items'] = $paypal_items;

		$paypal_args = apply_filters( 'woocommerce_paypal_digital_goods_nvp_args', $paypal_args );

		$paypal_object =  new PayPal_Purchase( $paypal_args );

		return $paypal_object;
	}

	/**
	 * Generate the PayPal button link
	 *
	 * Also output JavaScript to commence the in-context payment flow when the button is clicked.
	 *
	 * @since 1.0
	 **/
	public function get_paypal_button( $order_id ) {

		$paypal_object = $this->get_paypal_object( $order_id );

		$checkout_token = $paypal_object->request_checkout_token();

		$order = new WC_Order( $order_id );

		return    '<a class="button cancel" href="'.esc_url( $order->get_cancel_order_url() ).'">'.__('Cancel order &amp; restore cart', 'ppdg' ).'</a>'
				. $paypal_object->get_buy_button()
				. $paypal_object->get_script();
	}

	/**
	 * After a user has clicked "Place Order" on the checkout page, the receipt page is displayed.
	 *
	 * @since 1.0
	 **/
	function receipt_page( $order_id ) {

		echo '<p>'.__( 'Thank you for your order, please click the button below to pay with PayPal.', 'ppdg' ).'</p>';

		echo $this->get_paypal_button( $order_id );

	}

	/**
	 * Add the downloads to the thank you page for more convenient checkout
	 *
	 * @since 1.0
	 **/
	function thankyou_page( $order_id ) {
		global $woocommerce;

		if ( $downloads = $woocommerce->customer->get_downloadable_products() ) : ?>
		<h2><?php _e('Available downloads', 'ppdg'); ?></h2>
		<ul class="digital-downloads">
			<?php foreach ( $downloads as $download ) : ?>
				<?php if ( $download['order_id'] != $order_id  ) : ?>
					<?php continue; ?>
				<?php endif; ?>
				<li>
					<?php if ( is_numeric( $download['downloads_remaining'] ) ) : ?>
						<span class="count">
							<?php echo $download['downloads_remaining'] . _n(' download Remaining', ' downloads Remaining', $download['downloads_remaining'], 'ppdg'); ?>
						</span>
					<?php endif; ?>
					<a href="<?php echo esc_url( $download['download_url'] ); ?>"><?php echo $download['download_name']; ?></a>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php endif;
	}

	/**
	 * Process a PayPal subscription sign-up triggered after the CreateRecurringPaymentProfile call.
	 *
	 * @since 2.0
	 **/
	function process_subscription_sign_up( $transaction_details ) {

		$order = new WC_Order( (int) str_replace( $this->invoice_prefix, '', $transaction_details['PROFILEREFERENCE'] ) );

		// Order doesn't exist
		if ( ! is_object( $order ) ) {
			return;
		}

		if ( $this->debug == 'yes' ) {
			$this->log->add( 'paypal-dg', 'PayPal Digital Goods Subscription Sign-up with STATUS: ' . $transaction_details['STATUS'] );
		}

		if ( ! defined( 'PPDG_PROCESSING_SUBSCRIPTION' ) ) {
			define( 'PPDG_PROCESSING_SUBSCRIPTION', true );
		}

		$subscription = WC_Subscriptions_Manager::get_subscription( WC_Subscriptions_Manager::get_subscription_key( $order->id ) );

		// Subscription no longer exists
		if ( empty( $subscription ) ) {
			return;
		}

		switch( strtolower( $transaction_details['STATUS'] ) ) :
			case 'active' :

				// Always store PP details on order
				$this->update_paypal_details( $order->id, $transaction_details );

				// Activate subscription if needed
				if ( ! isset( $subscription['status'] ) || 'active' !== $subscription['status'] ) {

					// Activate Subscription
					WC_Subscriptions_Manager::activate_subscriptions_for_order( $order );

					// Payment completed
					$order->add_order_note( __( 'Subscription Activated via PayPal Digital Goods for Express Checkout', 'ppdg' ) );
					$order->payment_complete();

					if ( $this->debug == 'yes' ) {
						$this->log->add( 'paypal-dg', 'Subscription Activated via PayPal Digital Goods.' );
					}

					$cron_args = array( 'order_id' => (int)$order->id, 'profile_id' => $transaction_details['PROFILEID'] );

					// Check the subscription's status every 12 hours, just in case IPN is not active
					if ( false === wp_next_scheduled( 'ppdg_check_subscription_status', $cron_args ) ) {
						wp_schedule_event( time() + 60 * 60 * 24, 'twicedaily', 'ppdg_check_subscription_status', $cron_args );
					}
				}

				break;
			case 'pending' :

				// Note pending reason and update status if status is not already pending (note must be added separately to make sure it is recorded)
				$order->update_status( 'pending', __( 'Subscription Activation via PayPal Digital Goods Pending.', 'ppdg' ) );

				if ( $this->debug == 'yes' ) {
					$this->log->add( 'paypal-dg', __( 'Subscription Activation via PayPal Digital Goods Pending.', 'ppdg' ) );
				}

				// Store PP Details on order
				$this->update_paypal_details( $order->id, $transaction_details );

				// Check again in 45 seconds, just in case IPN is not active
				wp_schedule_single_event( time() + 45, 'ppdg_check_subscription_status', array( 'order_id' => (int)$order->id, 'profile_id' => $transaction_details['PROFILEID'] ) );

				break;
			case 'cancelled' :

				// Subscription already cancelled
				if ( $subscription['status'] == 'cancelled' ) {
					break;
				}

				// Cancel Subscription
				WC_Subscriptions_Manager::cancel_subscriptions_for_order( $order );

				// Payment completed
				$order->add_order_note( __( 'Subscription Cancelled via PayPal Digital Goods for Express Checkout', 'ppdg' ) );

				// Clear scheduled check of subscription's status
				wp_clear_scheduled_hook( 'ppdg_check_subscription_status', array( 'order_id' => (int)$order->id, 'profile_id' => $transaction_details['PROFILEID'] ) );

				if ( $this->debug == 'yes' ) {
					$this->log->add( 'paypal-dg', 'Subscription Cancelled via PayPal Digital Goods.' );
				}

				break;
			case 'suspended' :

				// Subscription already cancelled
				if ( $subscription['status'] == 'cancelled' || $subscription['status'] == 'trash' ) {
					break;
				}

				// Cancel Subscription
				WC_Subscriptions_Manager::put_subscription_on_hold_for_order( $order );

				// Payment completed
				$order->add_order_note( __( 'Subscription Suspended via PayPal Digital Goods for Express Checkout', 'ppdg' ) );

				if ( $this->debug == 'yes' ) {
					$this->log->add( 'paypal-dg', 'Subscription Suspended via PayPal Digital Goods.' );
				}

				break;
			default:

				if ( $this->debug == 'yes' ) {
					$this->log->add( 'paypal-dg', sprintf( __( 'In process_subscription_sign_up() with no status action, transaction details = %s', 'ppdg' ), print_r( $transaction_details, true ) ) );
				}

				break;

		endswitch;

	}

	/**
	 * Process a PayPal response from the checkout flow.
	 *
	 * @since 1.0
	 **/
	function process_payment_response( $transaction_details ) {

		$order_id = (int) str_replace( $this->invoice_prefix, '', $transaction_details['INVNUM'] );

		$order = new WC_Order( $order_id );

		if ( $order->order_key !== $transaction_details['CUSTOM'] ) {
			if ( $this->debug == 'yes' ) {
				$this->log->add( 'paypal-dg', 'PayPal Digital Goods Error: Order Key does not match invoice.' );
				$this->log->add( 'paypal-dg', 'Transaction details:' . print_r( $transaction_details, true ) );
			}
			return;
		}

		if ( $this->debug == 'yes' ) {
			$this->log->add( 'paypal-dg', 'PayPal Digital Goods Payment status: ' . $transaction_details['PAYMENTINFO_0_PAYMENTSTATUS'] );
		}

		switch( strtolower( $transaction_details['PAYMENTINFO_0_PAYMENTSTATUS'] ) ) :
			case 'completed' :

				// Check order not already completed
				if ( $order->status == 'completed' ) {
					break;
				}

				// Check valid transaction type
				if ( ! in_array( strtolower( $transaction_details['PAYMENTINFO_0_TRANSACTIONTYPE'] ), array( 'cart', 'instant', 'express_checkout', 'web_accept', 'masspay', 'send_money' ) ) ) {
					break;
				}

				// Payment completed
				$order->add_order_note( __( 'Payment Completed via PayPal Digital Goods for Express Checkout', 'ppdg' ) );
				$order->payment_complete();

				// Store PP Details on order
				$this->update_paypal_details( $order_id, $transaction_details );

				if ( $this->debug == 'yes' ) {
					$this->log->add( 'paypal-dg', 'Payment complete via PayPal Digital Goods.' );
				}

				break;
			case 'pending' :
				// Check valid transaction type
				if ( ! in_array( strtolower( $transaction_details['PAYMENTINFO_0_TRANSACTIONTYPE'] ), array( 'cart', 'instant', 'express_checkout', 'web_accept', 'masspay', 'send_money' ) ) ) {
					break;
				}

				// Pending Reason
				switch( strtolower( $transaction_details['PAYMENTINFO_0_PENDINGREASON'] ) ) {
					case 'address':
						$pending_reason = __( 'Address: The payment is pending because your customer did not include a confirmed shipping address and your Payment Receiving Preferences is set such that you want to manually accept or deny each of these payments. To change your preference, go to the Preferences section of your Profile.', 'ppdg' );
						break;
					case 'authorization':
						$pending_reason = __( 'Authorization: The payment is pending because it has been authorized but not settled. You must capture the funds first.', 'ppdg' );
						break;
					case 'echeck':
						$pending_reason = __( 'eCheck: The payment is pending because it was made by an eCheck that has not yet cleared.', 'ppdg' );
						break;
					case 'intl':
						$pending_reason = __( 'intl: The payment is pending because you hold a non-U.S. account and do not have a withdrawal mechanism. You must manually accept or deny this payment from your Account Overview.', 'ppdg' );
						break;
					case 'multicurrency':
					case 'multi-currency':
						$pending_reason = __( 'Multi-currency: You do not have a balance in the currency sent, and you do not have your Payment Receiving Preferences set to automatically convert and accept this payment. You must manually accept or deny this payment.', 'ppdg' );
						break;
					case 'order':
						$pending_reason = __( 'Order: The payment is pending because it is part of an order that has been authorized but not settled.', 'ppdg' );
						break;
					case 'paymentreview':
						$pending_reason = __( 'Payment Review: The payment is pending while it is being reviewed by PayPal for risk.', 'ppdg' );
						break;
					case 'unilateral':
						$pending_reason = __( 'Unilateral: The payment is pending because it was made to an email address that is not yet registered or confirmed.', 'ppdg' );
						break;
					case 'verify':
						$pending_reason = __( 'Verify: The payment is pending because you are not yet verified. You must verify your account before you can accept this payment.', 'ppdg' );
						break;
					case 'other':
						$pending_reason = __( 'Other: The payment is pending for a reason other than those listed above. For more information, contact PayPal customer service.', 'ppdg' );
						break;
					case 'none':
					default:
						$pending_reason = __( 'No pending reason provided.', 'ppdg' );
						break;
				}

				// Note pending reason and update status if status is not already pending (note must be added separately to make sure it is recorded)
				$order->add_order_note( sprintf( __( 'Payment via PayPal Digital Goods Pending. PayPal reason: %s.', 'ppdg' ), $pending_reason ) );
				$order->update_status( 'pending' );

				if ( $this->debug == 'yes' ) {
					$this->log->add( 'paypal-dg', sprintf( __( 'Payment via PayPal Digital Goods Pending. PayPal reason: %s.', 'ppdg' ), $pending_reason ) );
				}

				// Store PP Details on order
				$this->update_paypal_details( $order_id, $transaction_details );

				break;
			case 'denied' :
			case 'expired' :
			case 'failed' :
			case 'voided' :
				// Order failed
				$order->update_status( 'failed', sprintf( __( 'Payment %s via PayPal Digital Goods for Express Checkout.', 'ppdg' ), strtolower( $transaction_details['PAYMENTINFO_0_PAYMENTSTATUS'] ) ) );
				break;
			case "refunded" :
			case "reversed" :
			case "chargeback" :

				// Mark order as refunded
				$order->update_status( 'refunded', sprintf( __( 'Payment %s via PayPal Digital Goods for Express Checkout.', 'ppdg' ), strtolower( $transaction_details['PAYMENTINFO_0_PAYMENTSTATUS'] ) ) );

				$message = woocommerce_mail_template(
					__( 'Order refunded/reversed', 'ppdg' ),
					sprintf( __( 'Order #%s has been marked as refunded - PayPal reason code: %s', 'ppdg' ), $order->id, $transaction_details['PAYMENTINFO_0_REASONCODE'] )
				);

				// Send the mail
				woocommerce_mail( get_option( 'woocommerce_new_order_email_recipient' ), sprintf( __( 'Payment for order #%s refunded/reversed' ), $order->id ), $message );

				break;
			default:
				break;

		endswitch;
	}

	/**
	 * Process Recurring Payment IPN requests.
	 *
	 * @since 1.0
	 **/
	function process_ipn_request( $request ) {

		$allowed_transactions = array(
			'recurring_payment',
			'recurring_payment_profile_created',
			'recurring_payment_profile_cancel',
			'recurring_payment_expired',
			'recurring_payment_skipped',
			'recurring_payment_suspended',
			'recurring_payment_suspended_due_to_max_failed_payment',
			'recurring_payment_failed',
		);

		if ( ! in_array( $request['txn_type'], $allowed_transactions ) ) {
			return;
		}

		if ( 'yes' == $this->debug ) {
			$this->log->add( 'paypal-dg', 'Subscription Transaction Type: ' . $request['txn_type'] );
		}

		if ( 'yes' == $this->debug ) {
			$this->log->add( 'paypal-dg', 'Subscription transaction details: ' . print_r( $request, true ) );
		}

		// Get the $order_id & $order_key with backward compatibility
		extract( $this->get_order_id_and_key( $request ) );

		$order = new WC_Order( $order_id );

		// We have an invalid $order_id, probably because invoice_prefix has changed since the subscription was first created, so get the order by order key
		if ( false === $order_id || ! isset( $order->id ) ) {
			if ( 'yes' == $this->debug ) {
				$this->log->add( 'paypal-dg', 'Subscription IPN Error: Order could not be found.' );
			}
			exit;
		}

		// Ignore duplicate IPN requests
		if ( isset( $request['ipn_track_id'] ) ) {

			// Get array of IPN request that have already been
			$handled_ipn_requests = get_post_meta( $order->id, '_paypal_digital_goods_ipn_tracking_ids', true );

			if ( empty ( $handled_ipn_requests ) ) {
				$handled_ipn_requests = array();
			}

			// The 'ipn_track_id' is not a unique ID and is shared between different transaction types, so create a unique ID by prepending the transaction type
			$transaction_id = $request['txn_type'] . '_' . $request['ipn_track_id'];

			if ( in_array( $transaction_id, $handled_ipn_requests ) ) {
				if ( $this->debug == 'yes' ) {
					$this->log->add( 'paypal-dg', 'Subscription IPN Error: The ' . $transaction_id . ' IPN message has already been correctly handled for order ' . $order->id . ' via PayPal Digital Goods.' );
				}
				return;
			}
		}

		switch( $request['txn_type'] ) {
			case 'recurring_payment':

				if ( 'completed' == strtolower( $request['payment_status'] ) ) {
					// Store PayPal Details
					$payment_transaction_ids = get_post_meta( $order->id, '_payment_transaction_ids', true );

					if ( empty( $payment_transaction_ids ) ) {
						$payment_transaction_ids = array();
					}

					$payment_transaction_ids[] = $request['txn_id'];

					update_post_meta( $order->id, '_payment_transaction_ids', $payment_transaction_ids );

					// Subscriptions will move this to the renewal order and restore the original transaction ID
					update_post_meta( $order->id, '_transaction_id', $request['txn_id'] );

					// Keep a note of the payment
					$order->add_order_note( __( 'IPN subscription payment completed via PayPal Digital Goods.', 'ppdg' ) );

					if ( $this->debug == 'yes' ) {
						$this->log->add( 'paypal-dg', 'IPN subscription payment completed for order ' . $order->id . ' via PayPal Digital Goods.' );
					}

					WC_Subscriptions_Manager::process_subscription_payments_on_order( $order->id );

				} else {

					if ( $this->debug == 'yes' ) {
						$this->log->add( 'paypal-dg', 'IPN subscription payment notification received for order ' . $order->id  . ' with status ' . $request['payment_status'] );
					}

				}

				break;

			case 'recurring_payment_failed' :
			case 'recurring_payment_suspended_due_to_max_failed_payment' :

				$order = new WC_Order( $order->id );

				// Keep a note of the payment
				$order->add_order_note( __( 'IPN subscription payment failed via PayPal Digital Goods.', 'ppdg' ) );

				if ( $this->debug == 'yes' ) {
					$this->log->add( 'paypal-dg', 'IPN subscription payment failed for order ' . $order->id . ' via PayPal Digital Goods.' );
				}

				WC_Subscriptions_Manager::process_subscription_payment_failure_on_order( $order );
				break;

			case 'recurring_payment_profile_created' :
			case 'recurring_payment_profile_cancel' :
			case 'recurring_payment_suspended' :

				$transaction_details = array(
					'PROFILEID'        => $request['recurring_payment_id'],
					'PROFILEREFERENCE' => $order->id,
					'STATUS'           => $request['profile_status'],
					'EMAIL'            => $request['payer_email'],
					'FIRSTNAME'        => $request['first_name'],
					'LASTNAME'         => $request['last_name'],
				);

				if ( isset( $request['payment_type'] ) ) {
					$transaction_details['PAYMENTTYPE'] = $request['payment_type'];
				}

				if ( isset( $request['initial_payment_txn_id'] ) ) {
					$transaction_details['TRANSACTIONID'] = $request['initial_payment_txn_id'];
				}

				$this->process_subscription_sign_up( $transaction_details );
				break;

			default :

				if ( $this->debug == 'yes' ) {
					$this->log->add( 'paypal-dg', sprintf( __( 'In PayPal Digital Goods process_ipn_request with no txn_type action. Request = %s', 'ppdg' ), print_r( $request, true ) ) );
				}

				break;
		}

		// Store the transaction ID to avoid handling requests duplicated by PayPal
		if ( isset( $request['ipn_track_id'] ) ) {
			$handled_ipn_requests[] = $transaction_id;
			update_post_meta( $order->id, '_paypal_digital_goods_ipn_tracking_ids', $handled_ipn_requests );
		}

		die(); // Done processing the PPDG request, don't let it pass through to PayPal Standard
	}

	/**
	 * Process the payment and return the result
	 *
	 * @since 1.0
	 **/
	function ajax_do_express_checkout() {

		try {

			$paypal_object = $this->get_paypal_object( $_GET['ppdg_order'] );

			$response = $paypal_object->process(); // Takes the 'token' supplied as a $_GET parameter and either starts a susbcription or processes a payment

			$transaction_details = $paypal_object->get_details( $response );

			$transaction_details = array_merge( $response, $transaction_details );

			if ( isset( $transaction_details['PROFILEID'] ) ) {
				$this->process_subscription_sign_up( $transaction_details );
			} else {
				$this->process_payment_response( $transaction_details );
			}

			$result = array(
				'result'   => 'success',
				'redirect' => remove_query_arg( 'ppdg', $this->get_return_url( $_GET['ppdg_order'] ) )
			);

		} catch ( Exception $e ) {

			$result = array(
				'result'  => 'failure',
				'message' => sprintf( __( 'Unable to process payment with PayPal.<br/><br/> Response from PayPal: %s<br/><br/>Please try again.', 'ppdg' ), $e->getMessage() )
			);

		}

		echo json_encode( $result );

		exit();
	}

	/**
	 * Process the payment and return the result
	 *
	 * @since 1.0
	 **/
	function process_payment( $order_id ) {
		global $woocommerce;

		$order = new WC_Order( $order_id );

		$paypal_object = $this->get_paypal_object( $order_id );

		if ( is_ajax() ) { // Send to checkout iframe

			$result = array(
				'result' 	=> 'success',
				'redirect'	=> $paypal_object->get_checkout_url()
			);

			echo json_encode( $result );

			exit();

		} else {  // Send to payment page

			return array(
				'result' 	=> 'success',
				'redirect'	=> $order->get_checkout_payment_url( true ),
			);

		}
	}

	/**
	 * When a subscriber or store manager cancel's a subscription in the store, suspend it with PayPal
	 *
	 * @since 2.0
	 */
	function cancel_subscription_with_paypal( $order, $product_id ) {
		$response = $this->manage_subscription_with_paypal( $order, $product_id, 'Cancel' );

		$profile_id = get_post_meta( $order->id, 'PayPal Profile ID', true );

		// Clear scheduled check of subscription's status
		wp_clear_scheduled_hook( 'ppdg_check_subscription_status', array( 'order_id' => (int)$order->id, 'profile_id' => $profile_id ) );
	}

	/**
	 * When a subscriber or store manager cancel's a subscription in the store, suspend it with PayPal
	 *
	 * @since 2.0
	 */
	function suspend_subscription_with_paypal( $order, $product_id ) {
		$this->manage_subscription_with_paypal( $order, $product_id, 'Suspend' );
	}

	/**
	 * When a subscriber or store manager cancel's a subscription in the store, suspend it with PayPal
	 *
	 * @since 2.0
	 */
	function reactivate_subscription_with_paypal( $order, $product_id ) {
		$response = $this->manage_subscription_with_paypal( $order, $product_id, 'Reactivate' );
	}

	/**
	 * When a subscriber or store manager cancel's a subscription in the store, suspend it with PayPal
	 *
	 * @since 2.0
	 */
	function manage_subscription_with_paypal( $order, $product_id, $action ) {

		if ( defined( 'PPDG_PROCESSING_SUBSCRIPTION' ) && PPDG_PROCESSING_SUBSCRIPTION === true ) {
			return;
		}

		switch( $action ) {
			case 'Cancel' :
				$new_status = __( 'cancelled', 'ppdg' );
				break;
			case 'Suspend' :
				$new_status = __( 'suspended', 'ppdg' );
				break;
			case 'Reactivate' :
				$new_status = __( 'reactivated', 'ppdg' );
				break;
		}

		$paypal_object = $this->get_paypal_object( $order->id );

		$profile_id = get_post_meta( $order->id, 'PayPal Profile ID', true );

		$paypal_note = sprintf( __( 'Subscription %s at %s', 'ppdg' ), $new_status, get_bloginfo( 'name' ) );

		if ( ! empty( $profile_id ) ) {
			$response = $paypal_object->manage_subscription_status( $profile_id, $action, $paypal_note );
		} else {
			$response = array();
		}

		$item      = WC_Subscriptions_Order::get_item_by_product_id( $order, $product_id );
		$item_name = $item['name'];

		if ( isset( $response['ACK'] ) && $response['ACK'] == 'Success' ) {
			$order->add_order_note( sprintf( __( 'Subscription "%s" %s with PayPal (via Digital Goods for Express Checkout)', 'ppdg' ), $item_name, $new_status ) );
		}

		return $response;
	}

	/**
	 * Store a buyers PayPal details on an order
	 *
	 * @since 1.2
	 */
	function update_paypal_details( $order_id, $transaction_details ) {

		if ( isset( $transaction_details['EMAIL'] ) ) {
			update_post_meta( $order_id, 'Payer PayPal address', $transaction_details['EMAIL'] );
		}

		if ( isset( $transaction_details['FIRSTNAME'] ) ) {
			update_post_meta( $order_id, 'Payer first name', $transaction_details['FIRSTNAME'] );
		}

		if ( isset( $transaction_details['LASTNAME'] ) ) {
			update_post_meta( $order_id, 'Payer last name', $transaction_details['LASTNAME'] );
		}

		if ( isset( $transaction_details['PAYMENTTYPE'] ) ) {
			update_post_meta( $order_id, 'Payment type', $transaction_details['PAYMENTTYPE'] );
		}

		if ( isset( $transaction_details['TRANSACTIONID'] ) ) {
			// Display the transaction ID in custom fields until the site is updated to WC 2.2
			if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '2.2', '<' ) ) {
				update_post_meta( $order_id, 'Transaction ID', $transaction_details['TRANSACTIONID'] );
			}
			update_post_meta( $order_id, '_transaction_id', $transaction_details['TRANSACTIONID'] );
		}

		if ( isset( $transaction_details['SUBSCRIBERNAME'] ) ) {
			update_post_meta( $order_id, 'PayPay Subscriber Name', $transaction_details['SUBSCRIBERNAME'] );
		}

		if ( isset( $transaction_details['PROFILEID'] ) ) {
			update_post_meta( $order_id, 'PayPal Profile ID', $transaction_details['PROFILEID'] );
		}
	}

	/**
	 * Add the ppdg parameter to the return URL.
	 *
	 * @since 1.0
	 **/
	function get_return_url( $order = '' ) {

		if ( ! is_object( $order ) ) {
			$order = new WC_Order( $order );
		}

		$return_url = parent::get_return_url( $order );

		$return_url = add_query_arg( array( 'ppdg' => 'paid' ), $return_url );

		return $return_url;
	}

	/**
	 * Add the ppdg parameter to the cancel URL.
	 *
	 * @since 1.0
	 **/
	function get_cancel_payment_url( $order = '' ) {

		if ( ! is_object( $order ) ) {
			$order = new WC_Order( $order );
		}

		$cancel_url = parent::get_return_url( $order );

		$cancel_url = add_query_arg( array( 'ppdg' => 'cancelled' ), $cancel_url );

		return $cancel_url;
	}

	/**
	 * Returns a notify URL for core PayPal class to avoid duplicating the
	 * validation of the request.
	 *
	 * @since 2.0
	 */
	function get_notify_url() {

		$notify_url = str_replace( 'https:', 'http:', add_query_arg( 'wc-api', 'WC_Gateway_Paypal', home_url( '/' ) ) );

		return $notify_url;
	}

	/**
	 * Returns a notify URL for core PayPal class to avoid duplicating the
	 * validation of the request.
	 *
	 * @since 2.0
	 */
	private function get_order_id_and_key( $args ) {

		// First try and get the order ID by the rp_invoice_id value
		if ( isset( $args['rp_invoice_id'] ) ){

			if ( is_numeric( $args['rp_invoice_id'] ) ) {
				$order_id  = (int) $args['rp_invoice_id'];
			} elseif( is_string( $args['rp_invoice_id'] ) ) {
				$order_id  = (int) str_replace( $this->invoice_prefix, '', $args['rp_invoice_id'] );
			}

			$order_key = get_post_meta( $order_id, '_order_key', true );
			$order     = new WC_Order( $order_id );
		}

		// Couldn't find the order ID by rp_invoice_id, so the IPN is probably for a subscription imported from another system, so let's get it from the recurring_payment_id
		if ( ! isset( $order->id ) && isset( $args['recurring_payment_id'] ) ) {
			$posts = get_posts( array(
				'numberposts'      => 1,
				'orderby'          => 'ID',
				'order'            => 'ASC',
				'meta_key'         => 'PayPal Profile ID',
				'meta_value'       => $args['recurring_payment_id'],
				'post_type'        => 'shop_order',
				'post_parent'      => 0,
				'post_status'      => 'any',
				'suppress_filters' => true,
			));

			if ( ! empty( $posts ) ) {
				$order_id  = $posts[0]->ID;
				$order_key = get_post_meta( $order_id, '_order_key', true );
			} else {
				$order_id  = false;
				$order_key = false;
			}
		}

		return array( 'order_id' => $order_id, 'order_key' => $order_key );
	}


	/**
	 * Process a refund
	 * @param  int $order_id
	 * @param  float $amount
	 * @param  string $reason
	 * @return  bool|wp_error True or false based on success, or a WP_Error object
	 * @since 3.0
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {

		if ( function_exists( 'wc_get_order' ) ) {
			$order = wc_get_order( $order_id );
		} else {
			$order = new WC_Order( $order_id );
		}

		if ( ! $order || ! method_exists( $order, 'get_transaction_id' ) || ! $order->get_transaction_id() || ! $this->username || ! $this->password || ! $this->signature ) {
			return false;
		}

		$post_data = array(
			'VERSION'       => '84.0',
			'USER'          => $this->username,
			'PWD'           => $this->password,
			'SIGNATURE'     => $this->signature,
			'METHOD'        => 'RefundTransaction',
			'TRANSACTIONID' => $order->get_transaction_id(),
			'REFUNDTYPE'    => is_null( $amount ) ? 'Full' : 'Partial'
		);

		if ( ! is_null( $amount ) ) {
			$post_data['AMT']          = number_format( $amount, 2, '.', '' );
			$post_data['CURRENCYCODE'] = $order->get_order_currency();
		}

		if ( $reason ) {
			if ( 255 < strlen( $reason ) ) {
				$reason = substr( $reason, 0, 252 ) . '...';
			}

			$post_data['NOTE'] = html_entity_decode( $reason, ENT_NOQUOTES, 'UTF-8' );
		}

		$response = wp_remote_post( PayPal_Digital_Goods_Configuration::endpoint(), array(
			'method'      => 'POST',
			'body'        => $post_data,
			'timeout'     => 70,
			'user-agent'  => 'WooCommerce',
			'httpversion' => '1.1'
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( empty( $response['body'] ) ) {
			return new WP_Error( 'paypal-error', __( 'Empty Paypal response.', 'woocommerce' ) );
		}

		parse_str( $response['body'], $parsed_response );

		switch ( strtolower( $parsed_response['ACK'] ) ) {
			case 'success':
			case 'successwithwarning':
				$order->add_order_note( sprintf( __( 'Refunded %s - Refund ID: %s', 'woocommerce' ), $parsed_response['GROSSREFUNDAMT'], $parsed_response['REFUNDTRANSACTIONID'] ) );
				return true;
			break;
		}

		return false;
	}
}
