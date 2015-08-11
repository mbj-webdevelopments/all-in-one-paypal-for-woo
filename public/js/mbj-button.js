jQuery(document).ready(function ($){
    $("#paypal_ec_button_product input").click(function(){
        var paypal_express_action = $(this).data('action');
        $('form.cart').attr( 'action', paypal_express_action );
        $(this).attr('disabled', 'disabled');
        $('form.cart').submit();
        $(".paypal_expressOverlay").show();
        return false;
    });
    $(".paypal_checkout_button").click(function(){
        $(".paypal_expressOverlay").show();
        return true;
    });
});