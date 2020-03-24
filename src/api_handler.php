<?php

if ((!defined('CONST_INCLUDE_KEY')) || (CONST_INCLUDE_KEY !== 'd4e2ad09-b1c3-4d70-9a9a-0e6149302486')) {
	// If someone tries to browse directly to this PHP file, send 404 and exit. It can only included
	// as part of our API.
	header("Location: /404.html", TRUE, 404);
	echo file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/404.html');
	die;
}

class API_Handler {

	private $function_map;

	//--------------------------------------------------------------------------------------------------------------------
	public function __construct() {
		$this->loadFunctionMap();
	}

	//----------------------------------------------------------------------------------------------------------------------
	public function execCommand($varFunctionName, $varFunctionParams) {

		// get the actual function name (if necessary) and the class it belongs to.
		$returnArray = $this->getCommand($varFunctionName);

		// if we don't get a function back, then raise the error
		if ($returnArray['success'] == FALSE) {
			return $returnArray;
		}

		$class = $returnArray['dataArray']['class'];
		$functionName = $returnArray['dataArray']['function_name'];

		// Execute User Profile Commands
		$cObjectClass = new $class();
		$returnArray = $cObjectClass->$functionName($varFunctionParams);

		return $returnArray;

	}

	//----------------------------------------------------------------------------------------------------------------------
	private function getCommand($varFunctionName) {

		// get the actual function name and the class it belongs to.
		if (isset($this->function_map[$varFunctionName])) {
			$dataArray['class'] = $this->function_map[$varFunctionName]['class'];
			$dataArray['function_name'] = $this->function_map[$varFunctionName]['function_name'];
			$returnArray = App_Response::getResponse('200');
			$returnArray['dataArray'] = $dataArray;
		} else {
			$returnArray = App_Response::getResponse('405');
		}

		return $returnArray;

	}

	//----------------------------------------------------------------------------------------------------
	private function getToken($varParams) {

		// api key is required
		if (!isset($varParams['api_key']) || empty($varParams['api_key'])) {
			$returnArray = App_Response::getResponse('400');
			return $returnArray;
		}

		$apiKey = $varParams['api_key'];

		// get the api key object
		$cApp_API_Key = new App_API_Key;
		$res = $cApp_API_Key->getRecordByAPIKey($apiKey);

		// if anything looks sketchy, bail.
		if ($res['response'] !== '200') {
			return $res;
		}

		$apiSecretKey = $res['dataArray'][0]['api_secret_key'];

		$payloadArray = array();
		$payloadArray['apiKey'] = $apiKey;
		$token = JWT::encode($payloadArray, $apiSecretKey);

		$returnArray = App_Response::getResponse('200');
		$returnArray['dataArray'] = array("token" => $token);

		return $returnArray;
	}

	//----------------------------------------------------------------------------------------------------------------------
	private function loadFunctionMap() {

		// load up all public facing functions
		$this->function_map = [
			'getToken' => ['class' => 'API_Handler', 'function_name' => 'getToken'],
		];

	}

	//--------------------------------------------------------------------------------------------------------------------
	public function validateRequest($varAPIKey = NULL, $varToken = NULL) {

		// this function requires and API key and token parameters
		if (!$varAPIKey || !$varToken) {
			$returnArray = App_Response::getResponse('403');
			$returnArray['responseDescription'] .= " Missing API key or token.";
			return $returnArray;
		}

		// get the api key object
		$cApp_API_Key = new App_API_Key;
		$res = $cApp_API_Key->getRecordByAPIKey($varAPIKey);
		unset($cApp_API_Key);

		// if anything looks sketchy, bail.
		if ($res['response'] !== '200') {
			return $res;
		}

		// get the client API secret key.
		$apiSecretKey = $res['dataArray'][0]['api_secret_key'];

		// decode the token
		try {
			$payload = JWT::decode($varToken, $apiSecretKey, array('HS256'));
		}
		catch(Exception $e) {
			$returnArray = App_Response::getResponse('403');
			$returnArray['responseDescription'] .= " ".$e->getMessage();
			return $returnArray;
		}

		// get items out of the payload
		$apiKey = $payload->apiKey;
		if (isset($payload->exp)) {$expire = $payload->exp;} else {$expire = 0;}

		// if api keys don't match, kick'em out
		if ($apiKey !== $varAPIKey) {
			$returnArray = App_Response::getResponse('403');
			$returnArray['responseDescription'] .= " Invalid API Key.";
			return $returnArray;
		}

		// if token is expired, kick'em out
		$currentTime = time();
		if (($expire !== 0) && ($expire < $currentTime)) {
			$returnArray = App_Response::getResponse('403');
			$returnArray['responseDescription'] .= " Token has expired.";
			return $returnArray;
		}

		$returnArray = App_Response::getResponse('200');
		return $returnArray;

	}

} // end of class