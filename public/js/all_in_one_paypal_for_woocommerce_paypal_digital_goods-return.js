jQuery(document).ready(function($){
	var timeoutMessage;
	timeoutMessage = window.setTimeout(function () {
		$("#message").text(all_in_one_paypal_for_woocommerce_paypal_digital_goods.msgWaiting);
	}, 7000);
	$.ajax({
		url:  all_in_one_paypal_for_woocommerce_paypal_digital_goods.ajaxUrl,
		data: 'action=all_in_one_paypal_for_woocommerce_paypal_digital_goods_do_express_checkout&' + all_in_one_paypal_for_woocommerce_paypal_digital_goods.queryString,
		success: function(response) {
			try {
				var response = $.parseJSON(response);
				if ('success' == response.result) {
					$('#message').text(all_in_one_paypal_for_woocommerce_paypal_digital_goods.msgComplete);
					if (window!=top) {
						top.location.replace(decodeURI(response.redirect));
					} else {
						window.location = decodeURI(response.redirect);
					}
				} else {
					response = response.message;
					throw response.message;
				}
			} catch(err) {
				if (response.indexOf('woocommerce_error') == -1 && response.indexOf('woocommerce_message') == -1) {
					response = '<div class=\"woocommerce_error\">' + response + '</div>';
				}
				if ($('form.checkout').length > 0) {
					$('form.checkout').prepend(response);
					$('html, body').animate({
					    scrollTop: ($('form.checkout').offset().top - 100)
					}, 1000);
				} else {
					window.clearTimeout(timeoutMessage);
					$('#message').html(response).css({
						'font-style': 'normal',
						'color': '#CC0000',
					});
					$('#message').siblings('img').hide();
				}
			}
		},
		dataType: 'html'
	});
});
