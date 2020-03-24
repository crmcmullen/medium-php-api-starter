<?php

if ((!defined('CONST_INCLUDE_KEY')) || (CONST_INCLUDE_KEY !== 'd4e2ad09-b1c3-4d70-9a9a-0e6149302486')) {
	// If someone tries to browse directly to this PHP file, send 404 and exit. It can only included
	// as part of our API.
	header("Location: /404.html", TRUE, 404);
	echo file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/404.html');
	die;
}

//----------------------------------------------------------------------------------------------------------------------
// Build the class mapping array
$mapping = [
   
    // app classes
    'API_Handler' => './src/api_handler.php',
    'App_Response' => './src/app_response.php',
    'JWT' => './src/app_jwt.php',

    // database classes
    'Data_Access' => './src/db_classes/data_access.php',
    'App_API_Key' => './src/db_classes/app_api_key.php'
    
 ];

//----------------------------------------------------------------------------------------------------------------------
spl_autoload_register(function ($class) use ($mapping) {
    if (isset($mapping[$class])) {
        require_once $mapping[$class];
    }
}, true);