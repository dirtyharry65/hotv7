<?php

$path = WP_PLUGIN_DIR . '/Mollie/API/Autoloader.php';
include_once( $path );

/*
 * Initialize the Mollie API library with your API key.
 *
 * See: https://www.mollie.com/beheer/account/profielen/
 */
$mollie = new Mollie_API_Client;
$mollie->setApiKey("test_VtVVzjR7pnPwz3jGHDVRSGVMz9vezE");
