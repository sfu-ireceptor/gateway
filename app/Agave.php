<?php

namespace App;

use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Log;
use phpseclib\Crypt\RSA;

class Agave
{
    private $client;
    private $appTemplates;
    private $jobParameters;
    private $maxRunTime;
    private $processorsPerNode;
    private $memoryPerProcessor;

    public function __construct()
    {
        // Initialize the rest client interface.
        $this->initGuzzleRESTClient();

        // Update the AppTemplates at start up.
        $this->updateAppTemplates();

        // Maximum run time for a job in hours.
        //$this->maxRunTime = 4;
        $this->maxRunTime = config('services.agave.system_execution.max_run_time');
        // Maximum number of processors per job. For now all serial jobs.
        //$this->processorsPerNode = 1;
        $this->processorsPerNode = config('services.agave.system_execution.processors_per_node');
        // Amount of memory per processor (in GB)
        //$this->memoryPerProcessor = 8;
        $this->memoryPerProcessor = config('services.agave.system_execution.memory_per_processor');

        // Set up the default job contorl parameters used by AGAVE
        $this->jobParameters = [];
        // Run time parameter
        $job_parameter = [];
        $job_parameter['label'] = 'maxRunTime';
        $job_parameter['type'] = 'string';
        $job_parameter['name'] = 'Maximum run time (hh:mm:ss)';
        $job_parameter['description'] = 'Maximum run time for the job in hh:mm:ss. If the job takes longer than this to complete, the job will be terminated. A run time of longer than ' . strval($this->maxRunTime) . ' hours is not allowed.';
        $job_parameter['default'] = strval($this->maxRunTime) . ':00:00';
        $this->jobParameters[$job_parameter['label']] = $job_parameter;

        // Processors per node parameter
        $job_parameter = [];
        $job_parameter['label'] = 'processorsPerNode';
        $job_parameter['type'] = 'integer';
        $job_parameter['name'] = 'Number of CPUs (max ' . strval($this->processorsPerNode) . ')';
        $job_parameter['description'] = 'Number of CPUs used by the job, with a maximum of ' . strval($this->processorsPerNode) . ' per job. Note not all jobs will scale well so adding more CPUs may not reduce execution time.';
        $job_parameter['default'] = $this->processorsPerNode;
        $this->jobParameters[$job_parameter['label']] = $job_parameter;

        // Memory per node parameter
    // This doesn't seem to be working. Tapis does not seem to pass this on to
    // the scheduler, so changing this has no effect. We go with 4GB/CPU through
    // the default system config with a custom directive.
        //$job_parameter = [];
        //$job_parameter['label'] = 'memoryPerNode';
        //$job_parameter['type'] = 'string';
        //#$job_parameter['name'] = 'Memory per node';
        //$job_parameter['description'] = 'Amount of memory allocated per node used by the job';
        //$job_parameter['default'] = '4GB';
        //$this->jobParameters[$job_parameter['label']] = $job_parameter;

    // Number of nodes to use parameter
    // We don't want to have jobs cross nodes, so we limit to one only
    // Leaving this here in case we want to change that...
        //$job_parameter = [];
        //$job_parameter['label'] = 'nodeCount';
        //$job_parameter['type'] = 'integer';
        //$job_parameter['name'] = 'Number of nodes';
        //$job_parameter['description'] = 'Number of nodes used by the job';
        //$job_parameter['default'] = 1;
        //$this->jobParameters[$job_parameter['label']] = $job_parameter;
    }

    public function maxRunTime()
    {
        return $this->maxRunTime;
    }

    public function processorsPerNode()
    {
        return $this->processorsPerNode;
    }

    public function memoryPerProcessor()
    {
        return $this->memoryPerProcessor;
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
        Log::debug('Agave::getTokenForUser - Trying to get token for user: ' . $username);
        $t = $this->getToken($url, $username, $password, $apiKey, $apiSecret);
        Log::debug('Agave::getTokenForUser - Token info for user ' . $username . ' = ' . json_encode($t));

        // return NULL or array with token and refresh token
        return $t;
    }

    public function getToken($url, $username, $password, $api_key, $api_secret)
    {
        Log::debug('Agave::getToken for ' . $username);
        $headers = [];
        $headers['Content-Type'] = 'application/x-www-form-urlencoded';

        $auth = [$api_key, $api_secret];

        $params = [];
        $params['grant_type'] = 'password';
        $params['username'] = $username;
        $params['password'] = $password;
        $params['scope'] = 'PRODUCTION';

        try {
            // Normal respsonse from Tapis is:
            //  {"scope":"default","token_type":"bearer",
            //  "expires_in":14400,
            //  "refresh_token":"4e6e8a38f0a33f2cff7fe0318fe314db",
            //  "access_token":"8485748fbaa9a36efe941d8f3c36c2a1"}
            $response = $this->client->request('POST', '/token', ['auth' => $auth, 'headers' => $headers, 'form_params' => $params, 'timeout' => 10]);

            $response = json_decode($response->getBody());
            Log::debug('Agave::getToken: respsonse = ' . json_encode($response));
            $this->raiseExceptionIfAgaveError($response);
        } catch (ClientException $e) {
            Log::debug('Agave::getToken - A ClientException occurred while getting a token from Agave:');
            Log::debug('Agave::getToken - ' . $e);

            return;
        }

        Log::debug('Agave::getToken: returning respsonse = ' . json_encode($response));

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
        Log::debug('Agave::renewToken - refresh_token = ' . json_encode($refresh_token));
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
            // Normal respsonse from Tapis is:
            //  {"scope":"default","token_type":"bearer",
            //  "expires_in":14400,
            //  "refresh_token":"4e6e8a38f0a33f2cff7fe0318fe314db",
            //  "access_token":"8485748fbaa9a36efe941d8f3c36c2a1"}
            $response = $this->client->request('POST', '/token', ['auth' => $auth, 'headers' => $headers, 'form_params' => $params]);
            // Convert the body of the Guzzle response JSON to a PHP object
            $response_obj = json_decode($response->getBody());
            Log::debug('Agave::renewToken - refresh response = ' . json_encode($response_obj));
            // Check for Agave errors and raise an exception if we see one.
            $this->raiseExceptionIfAgaveError($response_obj);
        } catch (ClientException $e) {
            Log::debug('Agave::renewToken - A ClientException occurred while getting a token from Agave:');
            Log::debug('Agave::renewToken - exception = ' . json_encode($e));

            return;
        }

        Log::debug('Agave:renewToken - returning refresh response = ' . json_encode($response_obj));

        return $response_obj;
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
            //Log::debug('updateAppTemplates: Trying to open App file ' . $file_path);
            // Open the file and convert the JSON to an object.
            try {
                $app_json = file_get_contents($file_path);
            } catch (Exception $e) {
                Log::debug('updateAppTemplates: Could not open App file ' . $file_path);
                Log::debug('updateAppTemplates: Error: ' . $e->getMessage());
            }
            $app_config = json_decode($app_json, true);
            // We want to store information about the app that is useful in helping us
            // determine when to use it. This information is encoded in a JSON string in
            // the App in the hidden App parameter ir_hint.
            $param_count = 0;
            $gateway_count = -1;
            $app_info = [];
            if (array_key_exists('parameters', $app_config)) {
                $parameters = $app_config['parameters'];
                // Loop over the parameters and check for special ir_ parameters
                foreach ($parameters as $parameter) {
                    // ir_hints provides hints to the Gateway as to the capabilities
                    // of the App.
                    if (array_key_exists('id', $parameter) && $parameter['id'] == 'ir_hints') {
                        // If we found a JSON hint decode it
                        $hint_obj = json_decode($parameter['value']['default']);
                        //Log::debug('updateAppTemplates: hint_obj = ' . json_encode($hint_obj));
                        // Get the object attribute - this tells us which AIRR object type this
                        // App can be applied to (e.g. Rearrangement, Clone, Cell).
                        $app_info['object'] = $hint_obj->object;
                    } elseif (array_key_exists('id', $parameter) && $parameter['id'] == 'ir_gateway_url') {
                        // The Tapis App uses ir_gateway_url to provide the URL of the source
                        // gateway that is submitting the job. This used to get assets specific
                        // to the given gateway.
                        $gateway_param = $parameter;
                        $gateway_param['value']['default'] = config('app.url');
                        $gateway_count = $param_count;
                    }
                    $param_count = $param_count + 1;
                }
            }

            // Overwrite the gateway URL parameter configuration if we got one.
            if ($gateway_count >= 0) {
                //Log::debug('updateAppTemplates: replacing ' . json_encode($app_config['parameters'][$gateway_count]));
                $app_config['parameters'][$gateway_count] = $gateway_param;
            }

            // Store the object in a dictionary keyed with 'config'. We do this because
            // we anticipate needing more information about the App that will be
            // separate from the Tapis App.
            $app_info['config'] = $app_config;

            // Save this app template keyed by the name/tag/dir
            $this->appTemplates[$app_dir] = $app_info;
        }
        // Return the template list.
        return $this->appTemplates;
    }

    public function getAppTemplates($object_type)
    {
        // Return the list of app templates based on the AIRR object type provided.
        $object_templates = [];
        // For each app, filter it out based on the matching the Apps 'object' attribute
        // with the value passed in.
        foreach ($this->appTemplates as $app_tag => $app_info) {
            if (array_key_exists('object', $app_info) && $app_info['object'] == $object_type) {
                $object_templates[$app_tag] = $app_info;
            }
        }

        return $object_templates;
    }

    public function getAppTemplate($app_tag)
    {
        Log::debug('getAppTemplate: looking for ' . $app_tag);

        // Return the app template for the given app tap/name.
        if (array_key_exists($app_tag, $this->appTemplates)) {
            return $this->appTemplates[$app_tag];
        } else {
            Log::debug('getAppTemplate: could not find app ' . $app_tag);

            return null;
        }
    }

    public function getAppTemplateByLabel($app_label)
    {
        Log::debug('getAppTemplateByLabel: looking for ' . $app_label);

        // Return the app template for the given app label.
        foreach ($this->appTemplates as $app_tag => $app_info) {
            // Get this template's label and if it is the same we found it.
            $config = $app_info['config'];
            $label = $config['label'];
            if ($label == $app_label) {
                return $app_info;
            }
        }
        // Couldn't find it if we get here.
        Log::debug('getAppTemplateByLabel: could not find ' . $app_label);

        return null;
    }

    public function getJobParameters()
    {
        return $this->jobParameters;
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

    public function getJobStatus($job_id, $token)
    {
        $url = '/jobs/v2/' . $job_id . '/status?pretty=true';

        return $this->doGETRequest($url, $token, true);
    }

    public function getJob($job_id, $token)
    {
        $url = '/jobs/v2/' . $job_id;

        return $this->doGETRequest($url, $token, true);
    }

    public function getJobOutputFile($job_id, $token, $file)
    {
        $url = '/jobs/v2/' . $job_id . '/outputs/media/' . $file;

        return $this->doGETRequest($url, $token, true);
    }

    public function killJob($job_id, $token)
    {
        $url = '/jobs/v2/' . $job_id;

        // Send the kill action to the Agave job.
        $config = ['action' => 'kill'];

        return $this->doPOSTRequestWithJSON($url, $token, $config);
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
                    'maxRequestedTime' => strval($this->maxRunTime) . ':00:00',
                    'maxNodes' => 1,
                    'maxProcessorsPerNode' => $this->processorsPerNode,
                    'maxMemoryPerNode' => '64GB',
                    'customDirectives' => '--mem-per-cpu=' . $this->memoryPerProcessor . 'G',
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

    public function getJobConfig($name, $app_id, $storage_archiving, $notification_url, $folder, $params, $inputs, $job_params)
    {
        $t = [
            'name' => $name,
            'appId' => $app_id,
            'parameters' => $params,
            'inputs' => $inputs,
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

        // Set up the job parameters. We loop over the possible job parameters and
        // check to see if any of them are set in the job_params provided by the caller.
        foreach ($this->getJobParameters() as $job_parameter_info) {
            Log::debug('   getJobConfig: Processing job parameter ' . $job_parameter_info['label']);
            // If the parameter is provided by the caller, process it.
            if (isset($job_params[$job_parameter_info['label']])) {
                Log::debug('   getJobConfig: Parameter value = ' . $job_params[$job_parameter_info['label']]);
                // We need to make sure the type is correct or the JSON will fail.
                // Once convereted, we set the parameter based on the label. The label
                // in the config MUST be the correct Tapis label for that field.
                if ($job_parameter_info['type'] == 'integer') {
                    $t[$job_parameter_info['label']] = intval($job_params[$job_parameter_info['label']]);
                } else {
                    $t[$job_parameter_info['label']] = $job_params[$job_parameter_info['label']];
                }
            } else {
                Log::debug('   getJobConfig: default value = ' . $job_parameter_info['default']);
                $t[$job_parameter_info['label']] = $job_parameter_info['default'];
            }
        }

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
        $username = iconv('UTF-8', 'ASCII//TRANSLIT', $username); // remove diacritics

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
        Log::debug('Agave::doHTTPRequest - Bearer:' . $token);

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
            Log::error('Agave::doHTTPRequest:: ClientException - query = ' . $url);
            Log::error('Agave::doHTTPRequest:: ClientException - response = ' . $response);
            $this->raiseExceptionIfAgaveError($response);

            return $response;
        }

        // return response as object
        $json = $response->getBody();
        //Log::debug('json response -> ' . $json);
        if ($raw_json) {
            return $json;
        } else {
            $response = json_decode($json);
            $this->raiseExceptionIfAgaveError($response);

            return $response;
        }
    }

    public function isAgaveError($response)
    {
        if ($response == null) {
            return true;
        }
        if (property_exists($response, 'error')) {
            return true;
        }
        if (property_exists($response, 'fault')) {
            return true;
        }
        if (property_exists($response, 'status') && $response->status == 'error') {
            return true;
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
        if (property_exists($response, 'fault')) {
            throw new \Exception('AGAVE error: ' . $response->fault->message);
        }
    }
}
