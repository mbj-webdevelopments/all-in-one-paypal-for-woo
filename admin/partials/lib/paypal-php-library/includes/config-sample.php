<?php

date_default_timezone_set('America/Chicago');
if (!session_id())
    session_start();
$host_split = explode('.', $_SERVER['HTTP_HOST']);
$sandbox = $host_split[0] == 'sandbox' && $host_split[1] == 'domain' ? TRUE : FALSE;
$domain = $sandbox ? 'http://sandbox.domain.com/' : 'http://www.domain.com/';
if ($sandbox) {
    error_reporting(E_ALL | E_STRICT);
    ini_set('display_errors', '1');
}
$api_version = '112.0';
$application_id = $sandbox ? 'APP-80W284485P519543T' : '';
$developer_account_email = '';
$api_username = $sandbox ? 'SANDBOX_API_USERNAME' : 'LIVE_API_USERNAME';
$api_password = $sandbox ? 'SANDBOX_API_PASSWORD' : 'LIVE_API_PASSWORD';
$api_signature = $sandbox ? 'SANDBOX_API_SIGNATURE' : 'LIVE_API_SIGNATURE';
$payflow_username = $sandbox ? 'SANDBOX_PAYFLOW_USERNAME' : 'LIVE_PAYFLOW_USERNAME';
$payflow_password = $sandbox ? 'SANDBOX_PAYFLOW_PASSWORD' : 'LIVE_PAYFLOW_PASSWORD';
$payflow_vendor = $sandbox ? 'SANDBOX_PAYFLOW_VENDOR' : 'LIVE_PAYFLOW_VENDOR';
$payflow_partner = $sandbox ? 'SANDBOX_PAYFLOW_PARTNER' : 'LIVE_PAYFLOW_PARTNER';
$rest_client_id = $sandbox ? 'SANDBOX_CLIENT_ID' : 'LIVE_CLIENT_ID';
$rest_client_secret = $sandbox ? 'SANDBOX_CLIENT_ID' : 'LIVE_SECRET_ID';
$finance_access_key = $sandbox ? 'SANDBOX_ACCESS_KEY' : 'LIVE_ACCESS_KEY';
$finance_client_secret = $sandbox ? 'SANDBOX_CLIENT_SECRET' : 'LIVE_CLIENT_SECRET';
$api_subject = '';
$device_id = '';
$device_ip_address = $_SERVER['REMOTE_ADDR'];
?>