<?php

namespace App;

use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Log;
use phpseclib\Crypt\RSA;

class Agave
{
    private $client;
    private $appTemplates;

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

    public function renewToken($refresh_token)
    {
        $api_key = config('services.agave.api_key');
        $api_secret = config('services.agave.api_token');

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

    public function updateAppTemplates()
    {
        // Get the list of app directories. Note that this is the set of names/tags
        // used for the Apps
        $app_directories = config('services.agave.app_directories');
        // Build a list of Tapis App templates.
        $this->appTemplates = [];
        foreach ($app_directories as $app_dir) {
            // Tapis Apps are stored in the resources/agave_apps directory. It is
            // expected that each App that works on the iReceptor Gateway has an
            // app.json file that is the Tapis definition of the App. We use this
            // to determine how to submit the App to Tapis and to build the UI.
            $file_path = resource_path('agave_apps/' . $app_dir . '/app.json');
            Log::debug('updateAppTemplates: Trying to open App file ' . $file_path);
            // Open the file and convert the JSON to an object.
            try {
                $app_json = file_get_contents($file_path);
            } catch (Exception $e) {
                Log::debug('updateAppTemplates: Could not open App file ' . $file_path);
                Log::debug('updateAppTemplates: Error: ' . $e->getMessage());
            }
            $app_config = json_decode($app_json, true);
            // Store the object in a dictionary keyed with 'config'. We do this because
            // we anticipate needing more information about the App that will be
            // separate from the Tapis App.
            $app_info = [];
            $app_info['config'] = $app_config;
            // Save this app template keyed by the name/tag/dir
            $this->appTemplates[$app_dir] = $app_info;
        }
        // Return the template list.
        return $this->appTemplates;
    }

    public function getAppTemplates()
    {
        // Return the list of app templates.
        return $this->appTemplates;
    }

    public function getAppTemplate($app_name)
    {
        Log::debug('getAppTemplate: looking for ' . $app_name);

        // Return the app template for the given app tap/name.
        return $this->appTemplates[$app_name];
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
        // Update the app templates. This shouldn't be necessary every
        // time, but for now we will update them every time an App
        // config is requested.
        $this->updateAppTemplates();

        // Get the app template and its config given the App ID/name
        $app_template = $this->getAppTemplate($id);
        $app_config = $app_template['config'];

        // We overwrite the systems and deployment paths so we know what
        // apps are being used from where.
        $app_config['name'] = $name;
        $app_config['executionSystem'] = $executionSystem;
        $app_config['deploymentSystem'] = $deploymentSystem;
        $app_config['deploymentPath'] = $deploymentPath;
        Log::debug('Agave::getAppConfig: App config:');
        Log::debug($app_config);

        return $app_config;
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
