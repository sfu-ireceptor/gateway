<?php

namespace App;

use phpseclib\Crypt\RSA;
use GuzzleHttp\Post\PostFile;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\ClientException;

class Agave
{
    private $client;

    public function __construct()
    {
        $this->initGuzzleRESTClient();
    }

    public function isUp()
    {
        $url = config('services.agave.tenant_url');
        $apiKey = config('services.agave.api_key');
        $apiSecret = config('services.agave.api_token');

        // user created specifically to test if AGAVE is up
        $username = config('services.agave.test_user_username');
        $password = config('services.agave.test_user_password');

        // try to get OAuth token
        $t = $this->getToken($url, $username, $password, $apiKey, $apiSecret);

        return $t != null;
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
        $headers = [];
        $headers['Content-Type'] = 'application/x-www-form-urlencoded';

        $auth = [$api_key, $api_secret];

        $params = [];
        $params['grant_type'] = 'password';
        $params['username'] = $username;
        $params['password'] = $password;
        $params['scope'] = 'PRODUCTION';

        try {
            $response = $this->client->request('POST', '/token', ['auth' => $auth, 'headers' => $headers, 'form_params' => $params]);

            $response = json_decode($response->getBody());
            $this->raiseExceptionIfAgaveError($response);
        } catch (ClientException $e) {
            return;
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
        $headers = [];
        $headers['Content-Type'] = 'application/x-www-form-urlencoded';

        $auth = [$api_key, $api_secret];

        $params = [];
        $params['grant_type'] = 'refresh_token';
        $params['refresh_token'] = $refresh_token;
        $params['scope'] = 'PRODUCTION';

        try {
            $response = $this->client->request('POST', '/token', ['auth' => $auth, 'headers' => $headers, 'form_params' => $params]);
            $response = json_decode($response->getBody());
            $this->raiseExceptionIfAgaveError($response);
        } catch (ClientException $e) {
            return;
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
        $username = strtolower($first_name_stripped).'_'.strtolower($last_name_stripped);
        $password = str_random(24);

        $url = '/profiles/v2/?pretty=true';

        $variables = [
            'username' => $username,
            'password' => $password,
            'email' => $email,
            'first_name' => $first_name,
            'last_name' => $last_name,
        ];

        $this->doPOSTRequest($url, $token, $variables);

        return $variables;
    }

    public function updateUser($token, $username, $first_name, $last_name, $email, $password = '')
    {
        $url = '/profiles/v2/'.$username;

        $variables = [
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
        ];

        if ($password != '') {
            $variables['password'] = $password;
        }

        $this->doPUTRequest($url, $token, $variables);

        return $variables;
    }

    public function deleteUser($token, $username)
    {
        $url = '/profiles/v2/'.$username;
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
        $url = '/jobs/v2/'.$job_id.'/history?pretty=true';

        return $this->doGETRequest($url, $token, true);
    }

    public function getExcutionSystemConfig($name, $host, $username, $privateKey, $publicKey)
    {
        $t = [
            'id' => $name,
            'name' => $name,
            'type' => 'EXECUTION',
            'executionType' => 'HPC',
            'scheduler' => 'PBS',
            'queues' => [
                    [
                        'name' => 'pre',
                    ],
                ],
            'login' => [
                    'protocol' => 'SSH',
                    'host' => $host,
                    'port' => 22,
                    'auth' => [
                            'type' => 'SSHKEYS',
                            'username' => $username,
                            'publicKey' => $publicKey,
                            'privateKey' => $privateKey,
                        ],
                ],
            'storage' => [
                    'protocol' => 'SFTP',
                    'host' => $host,
                    'port' => 22,
                    'auth' => [
                            'type' => 'SSHKEYS',
                            'username' => $username,
                            'publicKey' => $publicKey,
                            'privateKey' => $privateKey,
                        ],
                    'rootDir' => '/home'.'/'.$username.'/scratch',
                ],
        ];

        return $t;
    }

    public function getStorageSystemConfig($name, $host, $auth, $rootDir)
    {
        $t = [
            'id' => $name,
            'name' => $name,
            'type' => 'STORAGE',
            'storage' => [
                    'protocol' => 'SFTP',
                    'host' => $host,
                    'port' => 22,
                    'auth' => $auth,
                    'rootDir' => $rootDir,
                ],
        ];

        return $t;
    }

    public function getAppConfig($id, $name, $executionSystem, $deploymentSystem, $deploymentPath)
    {
        $params = [];
        $inputs = [];

        if ($id == 1) {
            $params = [
                [
                    'id' => 'param1',
                    'value' => [
                        'type' => 'string',
                    ],
                ],
            ];

            $inputs = [
                [
                    'id' => 'file1',
                ],
            ];
        } elseif ($id == 2) {
            $params = [
                [
                    'id' => 'param1',
                    'value' => [
                        'type' => 'string',
                    ],
                ],
                [
                    'id' => 'red',
                    'value' => [
                        'type' => 'number',
                    ],
                ],
                [
                    'id' => 'green',
                    'value' => [
                        'type' => 'number',
                    ],
                ],
                [
                    'id' => 'blue',
                    'value' => [
                        'type' => 'number',
                    ],
                ],
            ];

            $inputs = [
                [
                    'id' => 'file1',
                ],
            ];
        } elseif ($id == 3) {
            $inputs = [
                [
                    'id' => 'file1',
                ],
            ];
        }

        $t = [
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
        ];

        return $t;
    }

    public function getJobConfig($name, $app_id, $storage_archiving, $notification_url, $inputFolder, $params, $inputs)
    {
        $t = [
            'name' => $name,
            'appId' => $app_id,
            'parameters' => $params,
            'inputs' => $inputs,
            'maxRunTime' => '00:10:00',
            'archive' => true,
            'archiveSystem' => $storage_archiving,
            'archivePath' => $inputFolder,
            'notifications' => [
                [
                    'url' => $notification_url.'/agave/update-status/${JOB_ID}/${JOB_STATUS}',
                    'event' => '*',
                    'persistent' => true,
                ],
            ],
        ];

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
        $url = '/profiles/v2/'.$username;

        return $this->doGETRequest($url, $token);
    }

    public function generateSSHKeys()
    {
        $rsa = new RSA();
        $rsa->setPublicKeyFormat(RSA::PUBLIC_FORMAT_OPENSSH);
        $keys = $rsa->createKey();

        return ['public' => $keys['publickey'], 'private' => $keys['privatekey']];
    }

    private function initGuzzleRESTClient()
    {
        $defaults = [];

        // set tenant URL
        $tenant_url = config('services.agave.tenant_url');
        $defaults['base_uri'] = $tenant_url;

        // accept self-signed SSL certificates
        $defaults['verify'] = false;

        $this->client = new \GuzzleHttp\Client($defaults);
    }

    private function raiseExceptionIfAgaveError($response)
    {
        if ($response == null) {
            throw new \Exception('AGAVE error: response was empty');
        }
        if (property_exists($response, 'error')) {
            throw new \Exception('AGAVE error: '.$response->error.': '.$response->error_description);
        }
        if (property_exists($response, 'status') && $response->status == 'error') {
            throw new \Exception('AGAVE error: '.$response->message);
        }
    }

    private function doGETRequest($url, $token, $raw_json = false)
    {
        $headers = [];
        $headers['Content-Type'] = 'application/x-www-form-urlencoded';
        $headers['Authorization'] = 'Bearer '.$token;

        $response = $this->client->request('GET', $url, ['headers' => $headers]);

        // return response as object
        $json = $response->getBody();
        Log::info('json response -> '.$json);
        if ($raw_json) {
            return $json;
        } else {
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
        Log::info('json request -> '.$json);

        // add to files array
        $files = [];
        $files['fileToUpload'] = $json;

        return $this->doPOSTRequest($url, $token, [], $files);
    }

    public function doPOSTRequest($url, $token, $variables = [], $files = [])
    {
        // build request
        $request = $this->client->createRequest('POST', $url);
        $request->addHeader('Authorization', 'Bearer '.$token);
        if (count($files) > 0) {
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
        Log::info('json response -> '.$json);
        $response = json_decode($json);
        $this->raiseExceptionIfAgaveError($response);

        return $response;
    }

    public function doPUTRequest($url, $token, $variables = [], $files = [])
    {
        // build request
        $request = $this->client->createRequest('PUT', $url);
        $request->addHeader('Authorization', 'Bearer '.$token);
        if (count($files) > 0) {
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
        Log::info('json response -> '.$json);
        $response = json_decode($json);
        $this->raiseExceptionIfAgaveError($response);

        return $response;
    }

    public function doDELETERequest($url, $token)
    {
        // build request
        $request = $this->client->createRequest('DELETE', $url);
        $request->addHeader('Authorization', 'Bearer '.$token);

        // execute request
        try {
            $response = $this->client->send($request);
        } catch (ClientException $exception) {
            $response = $exception->getResponse();
            Log::error($response);
        }

        // return response as object
        $json = $response->getBody();
        Log::info('json response -> '.$json);
        $response = json_decode($json);
        $this->raiseExceptionIfAgaveError($response);

        return $response;
    }
}
