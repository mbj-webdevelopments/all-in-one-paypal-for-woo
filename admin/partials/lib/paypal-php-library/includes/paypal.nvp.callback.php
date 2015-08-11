<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once('../includes/config.php');
require_once('paypal.class.php');
$paypal_config = array('Sandbox' => $sandbox);
$paypal = new Angelleye_PayPal($paypal_config);
$method = isset($_POST['METHOD']) ? $_POST['METHOD'] : '';
$token = isset($_POST['TOKEN']) ? $_POST['TOKEN'] : '';
$currency_code = isset($_POST['CURRENCYCODE']) ? $_POST['CURRENCYCODE'] : '';
$local_code = isset($_POST['LOCALECODE']) ? $_POST['LOCALECODE'] : '';
$order_items = $paypal->GetOrderItems($_POST);
$shipping_street = isset($_POST['SHIPTOSTREET']) ? $_POST['SHIPTOSTREET'] : '';
$shipping_street2 = isset($_POST['SHIPTOSTREET2']) ? $_POST['SHIPTOSTREET2'] : '';
$shipping_city = isset($_POST['SHIPTOCITY']) ? $_POST['SHIPTOCITY'] : '';
$shipping_state = isset($_POST['SHIPTOSTATE']) ? $_POST['SHIPTOSTATE'] : '';
$shipping_zip = isset($_POST['SHIPTOZIP']) ? $_POST['SHIPTOZIP'] : '';
$shipping_country_code = isset($_POST['SHIPTOCOUNTRY']) ? $_POST['SHIPTOCOUNTRY'] : '';
$CBFields = array();
$ShippingOptions = array();
$Option = array(
    'l_shippingoptionisdefault' => 'true',
    'l_shippingoptionname' => 'UPS', 
    'l_shipingpoptionlabel' => 'UPS', 
    'l_shippingoptionamount' => '5.00',
    'l_taxamt' => '0.00', 
    'l_insuranceamount' => '1.00'       
);
array_push($ShippingOptions, $Option);
$Option = array(
    'l_shippingoptionisdefault' => 'false', 
    'l_shippingoptionname' => 'UPS', 
    'l_shipingpoptionlabel' => 'UPS', 
    'l_shippingoptionamount' => '20.00', 
    'l_taxamt' => '0.00', 
    'l_insuranceamount' => '1.00'
);
array_push($ShippingOptions, $Option);
$callback_data_request_array = array(
    'CBFields' => $CBFields,
    'ShippingOptions' => $ShippingOptions
);
$callback_data_response = $paypal->CallbackResponse($callback_data_request_array);
$request_content = '';
foreach ($_POST as $var => $val) {
    $request_content .= '&' . $var . '=' . urldecode($val);
}
$response_content_body = '';
$response_content = $paypal->NVPToArray($callback_data_response);
foreach ($response_content as $var => $val) {
    $response_content_body .= $var . ': ' . urldecode($val) . '<br />';
}
echo $callback_data_response;
?>