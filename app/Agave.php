<?php

namespace App;

use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Log;
use phpseclib\Crypt\RSA;

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
        Log::debug('Trying to get token for user: ' . $username);
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
            $response = $this->client->request('POST', '/token', ['auth' => $auth, 'headers' => $headers, 'form_params' => $params, 'timeout' => 10]);

            $response = json_decode($response->getBody());
            $this->raiseExceptionIfAgaveError($response);
        } catch (ClientException $e) {
            Log::debug('A ClientException occurred while getting a token from Agave:');
            Log::debug($e);

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

    public function createUser($token, $username, $first_name, $last_name, $email)
    {
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
        $url = '/profiles/v2/' . $username;

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

    public function getExcutionSystemConfig($name, $host, $port, $username, $privateKey, $publicKey)
    {
        $t = [
            'id' => $name,
            'name' => $name,
            'type' => 'EXECUTION',
            'executionType' => 'HPC',
            'scheduler' => 'SLURM',
            'queues' => [
                [
                    'default' => true,
                    'name' => 'default',
                    'maxRequestedTime' => '48:00:00',
                    'maxNodes' => 1,
                    'maxProcessorsPerNode' => 8,
                    'maxMemoryPerNode' => '64GB',
                    'customDirectives' => '--mem-per-cpu=4G',
                ],
            ],

            'login' => [
                'protocol' => 'SSH',
                'host' => $host,
                'port' => $port,
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
                'port' => $port,
                'auth' => [
                    'type' => 'SSHKEYS',
                    'username' => $username,
                    'publicKey' => $publicKey,
                    'privateKey' => $privateKey,
                ],
                'rootDir' => '/home' . '/' . $username . '/scratch',
            ],
        ];

        return $t;
    }

    public function getStorageSystemConfig($name, $host, $port, $username, $privateKey, $publicKey, $rootDir)
    {
        $t = [
            'id' => $name,
            'name' => $name,
            'type' => 'STORAGE',
            'storage' => [
                'protocol' => 'SFTP',
                'host' => $host,
                'port' => $port,
                'auth' => [
                    'type' => 'SSHKEYS',
                    'username' => $username,
                    'publicKey' => $publicKey,
                    'privateKey' => $privateKey,
                ],
                'rootDir' => $rootDir,
            ],
        ];

        return $t;
    }

    public function getAppConfig($id, $name, $executionSystem, $deploymentSystem, $deploymentPath)
    {
        $params = [];
        $inputs = [];

        if ($id == 'histogram') {
            $params = [
                [
                    'id' => 'variable',
                    'value' => [
                        'type' => 'string',
                    ],
                ],
            ];

            $inputs = [
                [
                    'id' => 'download_file',
                ],
            ];
        } elseif ($id == 'histogram2') {
            $params = [
                [
                    'id' => 'variable',
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
                    'id' => 'download_file',
                ],
            ];
        } elseif ($id == 'stats') {
            $inputs = [
                [
                    'id' => 'download_file',
                ],
            ];
        } elseif ($id == 'shared_junction_aa') {
            $inputs = [
                [
                    'id' => 'download_file',
                ],
            ];
        } elseif ($id == 'genoa') {
            $inputs = [
                [
                    'id' => 'download_file',
                ],
            ];
        } elseif ($id == 'vdjbase-singularity') {
            $inputs = [
                [
                    'id' => 'download_file',
                ],
                [
                    'id' => 'singularity_image',
                ],

            ];
            $params = [
                [
                    'id' => 'variable',
                    'value' => [
                        'type' => 'string',
                    ],
                ],
            ];
        }

	#$file_path('resources/agave_apps/'.$id);
        #$f = fopen($file_path, 'r');
        $t = [
            'name' => $name,
            'version' => '1.00',
            'executionSystem' => $executionSystem,
            'parallelism' => 'SERIAL',
            'executionType' => 'HPC',
            'defaultMaxRequestedTime' => '06:00:00',
            'deploymentSystem' => $deploymentSystem,
            'deploymentPath' => $deploymentPath,
            'templatePath' => 'app.sh',
            'testPath' => 'test.sh',
            'parameters' => $params,
            'inputs' => $inputs,
        ];

        return $t;
    }

    public function getJobConfig($name, $app_id, $storage_archiving, $notification_url, $folder, $params, $inputs)
    {
        $t = [
            'name' => $name,
            'appId' => $app_id,
            'parameters' => $params,
            'inputs' => $inputs,
            'maxRunTime' => '08:00:00',
            'memoryPerNode' => '4GB',
            'archive' => true,
            'archiveSystem' => $storage_archiving,
            'archivePath' => $folder,
            'notifications' => [
                [
                    'url' => $notification_url . '/agave/update-status/${JOB_ID}/${JOB_STATUS}',
                    'event' => '*',
                    'persistent' => true,
                ],
            ],
        ];

        Log::debug('size of params = ' . count($params));
        if (count($params) == 0) {
            unset($t['parameters']);
        }

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

    public function userExists($username, $token)
    {
        $l = $this->getUsers($token);
        foreach ($l as $u) {
            if ($u->username == $username) {
                return true;
            }
        }

        return false;
    }

    public function generateUsername($first_name, $last_name, $token)
    {
        $first_name_stripped = str_replace(' ', '', $first_name);
        $last_name_stripped = str_replace(' ', '', $last_name);
        $username = strtolower($first_name_stripped) . '_' . strtolower($last_name_stripped);

        // if username already exists, append number
        if ($this->userExists($username, $token)) {
            $i = 2;
            $alternate_username = $username . $i;
            while ($this->userExists($alternate_username, $token)) {
                $i++;
                $alternate_username = $username . $i;
            }
            $username = $alternate_username;
        }

        return $username;
    }

    public function getUserWithEmail($email, $token)
    {
        $user = null;

        $user_list = $this->getUsers($token);
        foreach ($user_list as $u) {
            if ($u->email == $email) {
                $user = $u;
            }
        }

        return $user;
    }

    public function getUserWithUsername($username, $token)
    {
        $user = null;

        $user_list = $this->getUsers($token);
        foreach ($user_list as $u) {
            if ($u->username == $username) {
                $user = $u;
            }
        }

        return $user;
    }

    public function generateSSHKeys()
    {
        $rsa = new RSA();
        $rsa->setPublicKeyFormat(RSA::PUBLIC_FORMAT_OPENSSH);
        $keys = $rsa->createKey();

        return ['public' => $keys['publickey'], 'private' => $keys['privatekey']];
    }

    private function doGETRequest($url, $token, $raw_json = false)
    {
        return $this->doHTTPRequest('GET', $url, $token, [], null, $raw_json);
    }

    public function doPOSTRequest($url, $token, $variables = [], $body = null)
    {
        return $this->doHTTPRequest('POST', $url, $token, $variables, $body);
    }

    public function doPUTRequest($url, $token, $variables = [])
    {
        return $this->doHTTPRequest('PUT', $url, $token, $variables);
    }

    public function doDELETERequest($url, $token)
    {
        return $this->doHTTPRequest('DELETE', $url, $token);
    }

    public function doPOSTRequestWithJSON($url, $token, $config)
    {
        // convert config object to json
        $json = json_encode($config, JSON_PRETTY_PRINT);
        Log::info('json request -> ' . $json);

        return $this->doPOSTRequest($url, $token, [], $json);
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

    private function doHTTPRequest($method, $url, $token, $variables = [], $body = null, $raw_json = false)
    {
        $headers = [];
        $headers['Authorization'] = 'Bearer ' . $token;
        Log::debug('Bearer:' . $token);

        $data = [];
        if ($body == null) {
            $data = ['headers' => $headers, 'form_params' => $variables];
        } else {
            $headers['Content-Type'] = 'application/json';
            // dd($body);
            $data = ['headers' => $headers, 'body' => $body];
        }

        try {
            $response = $this->client->request($method, $url, $data);
        } catch (ClientException $exception) {
            $response = $exception->getResponse()->getBody()->getContents();
            Log::error($response);
            $this->raiseExceptionIfAgaveError($response);
        }

        // return response as object
        $json = $response->getBody();
        Log::info('json response -> ' . $json);
        if ($raw_json) {
            return $json;
        } else {
            $response = json_decode($json);
            $this->raiseExceptionIfAgaveError($response);

            return $response;
        }
    }

    private function raiseExceptionIfAgaveError($response)
    {
        if ($response == null) {
            throw new \Exception('AGAVE error: response was empty');
        }
        if (property_exists($response, 'error')) {
            throw new \Exception('AGAVE error: ' . $response->error . ': ' . $response->error_description);
        }
        if (property_exists($response, 'status') && $response->status == 'error') {
            throw new \Exception('AGAVE error: ' . $response->message);
        }
    }
}
