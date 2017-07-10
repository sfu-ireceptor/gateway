<?php

namespace App;

use Illuminate\Support\Facades\Log;

use GuzzleHttp\Post\PostFile;
use GuzzleHttp\Exception\ClientException;

class Agave {

	private $client;

	function __construct() {
		 $this->initGuzzleRESTClient();
	}

	public function isUp()
	{
		$url = config('services.agave.tenant_url');
		$apiKey = config('services.agave.api_key');
		$apiSecret = config('services.agave.api_token');

		// user created specifically to test if AGAVE is up
		$username = config('services.agave.test_user_username');;
		$password = config('services.agave.test_user_password');;

		// try to get OAuth token
		$t = $this->getToken($url, $username, $password, $apiKey, $apiSecret);

		return $t != NULL;	
	}

	public function getTokenForUser($username, $password)
	{
		$url = config('services.agave.tenant_url');
		$apiKey = config('services.agave.api_key');
		$apiSecret = config('services.agave.api_token');

		// try to get token
		$t = $this->getToken($url, $username, $password, $apiKey, $apiSecret);

		// return NULL or array with token and refresh token
		return $t; 
	}

	public function getToken($url, $username, $password, $api_key, $api_secret)
	{
		$request = $this->client->createRequest('POST', '/token', ['auth' => [$api_key, $api_secret]]);
		$request->addHeader('Content-Type', 'application/x-www-form-urlencoded');
		
		$postBody = $request->getBody();
		$postBody->setField('grant_type', 'password');
		$postBody->setField('username', $username);
		$postBody->setField('password', $password);
		$postBody->setField('scope', 'PRODUCTION');

		try {
			$response = $this->client->send($request);

			$response = json_decode($response->getBody());
			$this->raiseExceptionIfAgaveError($response);						
		} catch (ClientException $e) {
			return NULL;
		}		

		return $response;
	}

	public function getAdminToken()
	{
		$url = config('services.agave.tenant_url');
		$apiKey = config('services.agave.api_key');
		$apiSecret = config('services.agave.api_token');

		// admin user allowed to create user accounts
		$username = config('services.agave.admin_username');
		$password = config('services.agave.admin_password');

		$t = $this->getToken($url, $username, $password, $apiKey, $apiSecret);

		return $t->access_token;			
	}

	public function renewToken($url, $refresh_token, $api_key, $api_secret)
	{
		$request = $this->client->createRequest('POST', '/token', ['auth' => [$api_key, $api_secret]]);
		$request->addHeader('Content-Type', 'application/x-www-form-urlencoded');
		
		$postBody = $request->getBody();
		$postBody->setField('grant_type', 'refresh_token');
		$postBody->setField('refresh_token', $refresh_token);
		$postBody->setField('scope', 'PRODUCTION');

		try {
			$response = $this->client->send($request);

			$response = json_decode($response->getBody());
			$this->raiseExceptionIfAgaveError($response);						
		} catch (ClientException $e) {
			return NULL;
		}		

		return $response;
	}

	public function createSystem($token, $config)
	{
		$url = '/systems/v2/?pretty=true';
		return $this->doPOSTRequestWithJSON($url, $token, $config);
	}	

	public function createApp($token, $config)
	{
		$url = '/apps/v2/?pretty=true';
		return $this->doPOSTRequestWithJSON($url, $token, $config);
	}

	public function createJob($token, $config)
	{
		$url = '/jobs/v2/?pretty=true';
		return $this->doPOSTRequestWithJSON($url, $token, $config);
	}

	public function createUser($token, $first_name, $last_name, $email)
	{
		$first_name_stripped = $string = str_replace(' ', '', $first_name);
		$last_name_stripped = $string = str_replace(' ', '', $last_name);
        $username = strtolower($first_name_stripped) . '_' . strtolower($last_name_stripped);
        $password = str_random(24);

		$url = '/profiles/v2/?pretty=true';
	
		$variables = array (
			'username' => $username,
			'password' => $password,
			'email' => $email,
			'first_name' => $first_name,
			'last_name' => $last_name
		);

		$this->doPOSTRequest($url, $token, $variables);

		return $variables;
	}

	public function updateUser($token, $username, $first_name, $last_name, $email, $password = '')
	{
		$url = '/profiles/v2/' . $username;
	
		$variables = array (
			'first_name' => $first_name,
			'last_name' => $last_name,
			'email' => $email
		);

		if ($password != '')
		{
			$variables['password'] = $password;
		}

		$this->doPUTRequest($url, $token, $variables);

		return $variables;
	}

	public function deleteUser($token, $username)
	{
		$url = '/profiles/v2/' . $username;
		$this->doDELETERequest($url, $token);
	}

	public function listApps($token)
	{
		$url = '/apps/v2/?pretty=true';
		return $this->doGETRequest($url, $token);
	}

	public function listSystems($token)
	{
		$url = '/systems/v2/?pretty=true';
		return $this->doGETRequest($url, $token);
	}

	public function getJobHistory($job_id, $token)
	{
		$url = '/jobs/v2/' . $job_id . '/history?pretty=true';
		return $this->doGETRequest($url, $token, true);
	}

	public function getExcutionSystemConfig($name, $host, $username, $privateKey, $publicKey)
	{
		$t = array (
			'id' => $name,
			'name' => $name,
			'type' => 'EXECUTION',
			'executionType' => 'HPC',
			'scheduler' => 'PBS',
			'queues' => 
				array (
					array (
						'name' => 'pre',
					)
				),
			'login' => 
				array (
					'protocol' => 'SSH',
					'host' => $host,
					'port' => 22,
					'auth' => 
						array (
							'type' => 'SSHKEYS',
							'username' => $username,
							'publicKey' => $publicKey,
							'privateKey' => $privateKey,
						),
				),
			'storage' => 
				array (
					'protocol' => 'SFTP',
					'host' => $host,
					'port' => 22,
					'auth' => 
						array (
							'type' => 'SSHKEYS',
							'username' => $username,
							'publicKey' => $publicKey,
							'privateKey' => $privateKey,
						),
					'rootDir' => '/home' . '/' . $username . '/scratch',
				),
		);

		return $t;
	}

	public function getStorageSystemConfig($name, $host, $auth, $rootDir)
	{
		$t = array (
			'id' => $name,
			'name' => $name,
			'type' => 'STORAGE',
			'storage' => 
				array (
					'protocol' => 'SFTP',
					'host' => $host,
					'port' => 22,
					'auth' => $auth,
					'rootDir' => $rootDir,
				),
		);

		return $t;
	}

	public function getAppConfig($id, $name, $executionSystem, $deploymentSystem, $deploymentPath)
	{
		$params = array();
		$inputs = array();

		if($id == 1)
		{
			$params = array (
				array (
					'id' => 'param1',
					'value' => 
					array (
						'type' => 'string',
					),
				),
			);

			$inputs = array (
				array (
					'id' => 'file1',
				),
			);
		}
		else if($id == 2)
		{
			$params = array (
				array (
					'id' => 'param1',
					'value' => 
					array (
						'type' => 'string',
					),
				),
				array (
					'id' => 'red',
					'value' => 
					array (
						'type' => 'number',
					),
				),
				array (
					'id' => 'green',
					'value' => 
					array (
						'type' => 'number',
					),
				),
				array (
					'id' => 'blue',
					'value' => 
					array (
						'type' => 'number',
					),
				)				
			);

			$inputs = array (
				array (
					'id' => 'file1',
				),
			);
		}
		else if($id == 3)
		{
			$inputs = array (
				array (
					'id' => 'file1',
				),
			);
		}

		$t = array (
			'name' => $name,
			'version' => '1.00',
			'executionSystem' => $executionSystem,
			'parallelism' => 'SERIAL',
			'executionType' => 'CLI',
			'deploymentSystem' => $deploymentSystem,
			'deploymentPath' => $deploymentPath,
			'templatePath' => 'app.sh',
			'testPath' => 'test.sh',
			'parameters' => $params,
			'inputs' => $inputs,
		);

		return $t;
	}

	public function getJobConfig($name, $app_id, $storage_archiving, $notification_url, $inputFolder, $params, $inputs)
	{
		$t = array (
			'name' => $name,
			'appId' => $app_id,
			'parameters' => $params,
			'inputs' => $inputs,
			'maxRunTime' => '00:10:00',
			'archive' => true,
			'archiveSystem' => $storage_archiving,
			'archivePath' => $inputFolder,
			'notifications' => 
			array (
				array (
					'url' => $notification_url . '/agave/update-status/${JOB_ID}/${JOB_STATUS}',
					'event' => '*',
					'persistent' => true,
				),
			),
		);

		return $t;
	}

	public function getUsers($token)
	{
		$url = '/profiles/v2/?pretty=true';
		$response = $this->doGETRequest($url, $token);
		return $response->result;
	}

	public function getUser($username, $token)
	{
		$url = '/profiles/v2/' . $username;
		return $this->doGETRequest($url, $token);
	}

	public function generateSSHKeys() {
		$rsa = new Crypt_RSA();
		$rsa->setPublicKeyFormat(CRYPT_RSA_PUBLIC_FORMAT_OPENSSH);
		$keys = $rsa->createKey();

		return array('public' => $keys['publickey'], 'private' => $keys['privatekey']);
	}

	private function initGuzzleRESTClient() {
		// set tenant URL
		$tenant_url = config('services.agave.tenant_url');
		$this->client = new \GuzzleHttp\Client(['base_url' => $tenant_url]);

		// accept self-signed SSL certificates
		$this->client->setDefaultOption('verify', false);
	}

	private function raiseExceptionIfAgaveError($response)
	{
		if ($response == NULL) {
			throw new Exception('AGAVE error: response was empty');
		}
		if (property_exists($response, 'error')) {
			throw new Exception('AGAVE error: ' . $response->error . ': ' . $response->error_description);
		}
		if (property_exists($response, 'status') && $response->status == 'error') {
			throw new Exception('AGAVE error: ' . $response->message);
		}
	}

	private function doGETRequest($url, $token, $raw_json = false)
	{
		// build request
		$request = $this->client->createRequest('GET', $url);
		$request->addHeader('Content-Type', 'application/x-www-form-urlencoded');
		$request->addHeader('Authorization', 'Bearer ' .  $token);

		// execute request
		$response = $this->client->send($request);

		// return response as object		
		$json = $response->getBody();
		Log::info('json response -> ' . $json);
		if($raw_json) {
			return $json;
		}
		else {
			$response = json_decode($json);
			$this->raiseExceptionIfAgaveError($response);
			return $response;			
		}
	}

	public function doPOSTRequestWithJSON($url, $token, $config)
	{
		// Log::info('doPOSTRequestWithJSON url: ' . $url);
		// Log::info('token: ' . $token);
		// Log::info('config: ' . var_export($config, true));

		// convert config object to json
		$json = json_encode($config, JSON_PRETTY_PRINT);
		Log::info('json request -> ' . $json);	

		// add to files array
		$files = array();
		$files['fileToUpload'] = $json;		

		return $this->doPOSTRequest($url, $token, [], $files);
	}

	public function doPOSTRequest($url, $token, $variables = [], $files = [])
	{	
		// build request
		$request = $this->client->createRequest('POST', $url);
		$request->addHeader('Authorization', 'Bearer ' .  $token);
		if(count($files) > 0)
		{
			$request->addHeader('Content-Type', 'multipart/form-data');
		}

		$request->setQuery($variables);

		$postBody = $request->getBody();
		foreach ($files as $filename => $file_str) {
			$postBody->addFile(new PostFile($filename, $file_str));
		}

		// execute request
		try {
			$response = $this->client->send($request);
		} catch (ClientException $exception) {
		    $response = $exception->getResponse();
		    Log::error($response);
		}

		// return response as object		
		$json = $response->getBody();
		Log::info('json response -> ' . $json);
		$response = json_decode($json);
		$this->raiseExceptionIfAgaveError($response);
		return $response;
	}

	public function doPUTRequest($url, $token, $variables = [], $files = [])
	{	
		// build request
		$request = $this->client->createRequest('PUT', $url);
		$request->addHeader('Authorization', 'Bearer ' .  $token);
		if(count($files) > 0)
		{
			$request->addHeader('Content-Type', 'multipart/form-data');
		}

		$request->setQuery($variables);

		$postBody = $request->getBody();
		foreach ($files as $filename => $file_str) {
			$postBody->addFile(new PostFile($filename, $file_str));
		}

		// execute request
		try {
			$response = $this->client->send($request);
		} catch (ClientException $exception) {
		    $response = $exception->getResponse();
		    Log::error($response);
		}

		// return response as object		
		$json = $response->getBody();
		Log::info('json response -> ' . $json);
		$response = json_decode($json);
		$this->raiseExceptionIfAgaveError($response);
		return $response;
	}

	public function doDELETERequest($url, $token)
	{	
		// build request
		$request = $this->client->createRequest('DELETE', $url);
		$request->addHeader('Authorization', 'Bearer ' .  $token);

		// execute request
		try {
			$response = $this->client->send($request);
		} catch (ClientException $exception) {
		    $response = $exception->getResponse();
		    Log::error($response);
		}

		// return response as object		
		$json = $response->getBody();
		Log::info('json response -> ' . $json);
		$response = json_decode($json);
		$this->raiseExceptionIfAgaveError($response);
		return $response;
	}

}