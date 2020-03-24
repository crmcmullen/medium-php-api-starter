<?php

header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Max-Age: 1000');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token, Authorization');

// the following constant will help ensure all other PHP files will only work as part of this API.
if (!defined('CONST_INCLUDE_KEY')) {define('CONST_INCLUDE_KEY', 'd4e2ad09-b1c3-4d70-9a9a-0e6149302486');}

// run the class autoloader
require_once ('./src/app_autoloader.php');

//--------------------------------------------------------------------------------------------------------------------
// if this API must be used with a GET, POST, PUT, DELETE or OPTIONS request
$requestMethod = $_SERVER['REQUEST_METHOD'];

// retrieve the inbound parameters based on request type.
if (in_array($requestMethod, ["GET", "POST", "PUT", "DELETE", "OPTIONS"])) {

	// Move the request array into a new variable and then unset the apiFunctionName 
	// so that we don't accidentally snag included interfaces after this.
	$requestMethodArray = array();
	$requestMethodArray = $_REQUEST;
  
	if (isset($requestMethodArray['apiKey']))				{$apiKey = $requestMethodArray['apiKey'];}  
	if (isset($requestMethodArray['apiToken']))				{$apiToken = $requestMethodArray['apiToken'];}
	if (isset($requestMethodArray['apiFunctionName']))		{$functionName = $requestMethodArray['apiFunctionName'];}
	if (isset($requestMethodArray['apiFunctionParams']))	{$functionParams = $requestMethodArray['apiFunctionParams'];}

	// decode the function parameters array.
	if (isset($functionParams) && $functionParams != '') {
		$functionParams = json_decode($functionParams, true);
	}

	// instantiate this class and validate the API request
	$cApiHandler = new API_Handler();

	// Requests should always include the API Key and JSON Web Token *UNLESS* this request is to 
	// to get a token. In that case, no validation is required here as the function itself requires 
	// the API Key as a parameter and will do its own validation.
	if ($functionName === 'getToken') {
        // default validation to a good response
		$res = App_Response::getResponse('200');
	} else {
		$res = $cApiHandler->validateRequest($apiKey, $apiToken);
	}

	if ($res['response'] !== '200') {
        // if request is not valid, then raise a bad message.
		$returnArray = json_encode($res);
		echo($returnArray);
	}
	else { 
        // if request is valid, execute command
		$res = $cApiHandler->execCommand($functionName, $functionParams);

		// encode and return
		$returnArray = json_encode($res, JSON_PRETTY_PRINT);
		echo($returnArray);

	}

	if (isset($cApiHandler)) {unset($cApiHandler);}

} else {
	$returnArray = App_Response::getResponse('405');
	echo(json_encode($returnArray));
}