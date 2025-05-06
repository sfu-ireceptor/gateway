<?php

namespace App;

use Carbon\Carbon;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Log;
use phpseclib\Crypt\RSA;
use stdClass;

class Tapis
{
    private $tapis_client;
    private $tenant_client;
    private $appTemplates;
    private $jobParameters;
    private $maxMinutes;
    private $coresPerNode;
    private $memoryMBPerNode;
    private $memoryMBPerCore;

    public function __construct()
    {
        // Initialize the rest client interface.
        $this->initGuzzleRESTClient();

        // Update the AppTemplates at start up.
        $this->updateAppTemplates();

        // Maximum run time for a job in hours.
        $this->maxMinutes = intval(config('services.tapis.system_execution.max_minutes'));
        // Maximum number of processors per job. For now all serial jobs.
        $this->coresPerNode = intval(config('services.tapis.system_execution.cores_per_node'));
        // Amount of memory per node (in MB)
        $this->memoryMBPerNode = intval(config('services.tapis.system_execution.memory_per_node'));
        // Amount of memory per processor (in MB)
        $this->memoryMBPerCore = intval(config('services.tapis.system_execution.memory_per_core'));

        // Set up the default job control parameters used by TAPIS
        $this->jobParameters = [];
        // Run time parameter
        $job_parameter = [];
        $job_parameter['label'] = 'maxMinutes';
        $job_parameter['type'] = 'integer';
        $job_parameter['name'] = 'Maximum number of minutes';
        $job_parameter['description'] = 'Maximum run time for the job in minutes. If the job takes longer than this to complete, the job will be terminated. A run time of longer than ' . strval($this->maxMinutes) . ' minutes is not allowed.';
        $job_parameter['default'] = $this->maxMinutes;
        $this->jobParameters[$job_parameter['label']] = $job_parameter;

        // Processors per node parameter
        $job_parameter = [];
        $job_parameter['label'] = 'coresPerNode';
        $job_parameter['type'] = 'integer';
        $job_parameter['name'] = 'Number of CPUs (max ' . strval($this->coresPerNode) . ')';
        $job_parameter['description'] = 'Number of CPUs used by the job, with a maximum of ' . strval($this->coresPerNode) . ' per job. Note not all jobs will scale well so adding more CPUs may not reduce execution time.';
        $job_parameter['default'] = $this->coresPerNode;
        $this->jobParameters[$job_parameter['label']] = $job_parameter;

        // Memory per node parameter
        $job_parameter = [];
        $job_parameter['label'] = 'memoryMB';
        $job_parameter['type'] = 'string';
        $job_parameter['name'] = 'Memory (MB) per node';
        $job_parameter['description'] = 'Amount of memory allocated per node used by the job';
        $job_parameter['default'] = $this->memoryMBPerNode;
        $this->jobParameters[$job_parameter['label']] = $job_parameter;

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

    public function maxRunTimeMinutes()
    {
        return $this->maxMinutes;
    }

    public function processorsPerNode()
    {
        return $this->coresPerNode;
    }

    public function memoryMBPerNode()
    {
        return $this->memoryMBPerNode;
    }

    public function isUp()
    {
        // Get the analysis username and password.
        $username = config('services.tapis.analysis_username');
        $password = config('services.tapis.analysis_password');

        // try to get token
        $t = $this->listSystems();

        return $t != null;
    }

    /* Returns a token object as per that provide by Tapis 3, which looks like this:
    ** {"access_token":"JWT TOKEN INFO DELETED","expires_at":"2023-03-25T21:13:31.081319+00:00",
    **  "expires_in":14400,"jti":"38a395a2-7496-4349-89ef-afff5c5f69ad"}
    */
    public static function getTokenForUser($username, $password)
    {
        Log::debug('Tapis::getTokenForUser - Trying to get token for user: ' . $username);
        $payload = new stdClass();
        $payload->username = $username;
        $payload->password = $password;
        $payload->grant_type = 'password';
        $tapis = new self;
        $t = $tapis->doPOSTRequestWithJSON($tapis->tenant_client, '/v3/oauth2/tokens', null, $payload);
        // try to get token
        if (isset($t->result) && isset($t->result->access_token)) {
            //Log::debug('Tapis::getTokenForUser - Token info for user ' . $username . ' = ' . json_encode($t->result->access_token));
            $token_info = $t->result->access_token;

            return $token_info;
        } else {
            Log::debug('Tapis::getTokenForUser - Could not get token for ' . $username);

            return null;
        }
    }

    public function getAdminToken()
    {
        // Get the tapis admin token from the config and return it.
        $tapis_admin_token = config('services.tapis.tapis_admin_token');
        if (strlen($tapis_admin_token) == 0) {
            return null;
        } else {
            return $tapis_admin_token;
        }
    }

    public static function getAnalysisToken()
    {
        // Get the tapis user token from the config and return it.
        $tapis_user_token = config('services.tapis.tapis_user_token');
        if (strlen($tapis_user_token) == 0) {
            return null;
        } else {
            return $tapis_user_token;
        }
    }

    public function updateAppTemplates()
    {
        // Get the list of app directories. Note that this is the set of names/tags
        // used for the Apps
        $app_base_dir = config('services.tapis.app_base_dir');
        $app_directories = config('services.tapis.app_directories');
        $app_json_file = config('services.tapis.app_json_file');
        //Log::debug('Tapis::updateAppTemplates: using directory ' . json_encode($app_directories));
        // Build a list of Tapis App templates.
        $this->appTemplates = [];
        foreach ($app_directories as $app_dir) {
            // Tapis Apps are stored in the resources/$app_base_dir directory. It is
            // expected that each App that works on the iReceptor Gateway has an
            // app.json file that is the Tapis definition of the App. We use this
            // to determine how to submit the App to Tapis and to build the UI.
            $file_path = resource_path($app_base_dir . '/' . $app_dir . '/' . $app_json_file);
            // Open the file and convert the JSON to an object.
            try {
                $app_json = file_get_contents($file_path);
            } catch (\Exception $e) {
                Log::debug('Tapis::updateAppTemplates: Could not open App file ' . $file_path);
                Log::debug('Tapis::updateAppTemplates: Error: ' . $e->getMessage());
            }
            $app_config = json_decode($app_json, true);
            // We want to overwrite/set some parameters that need to be set for all Apps.
            // We don't want the external config to be able to set these.
            $app_config['jobType'] = 'BATCH';
            $app_config['runtime'] = 'SINGULARITY';
            $app_config['runtimeOptions'] = ['SINGULARITY_RUN'];
            // We want to store information about the app that is useful in helping us
            // determine when to use it. This information is encoded in the Apps notes
            // field as an ir_hint object.
            $param_count = 0;
            $gateway_count = -1;
            $app_info = [];
            if (array_key_exists('notes', $app_config)) {
                $notes = $app_config['notes'];
                if (array_key_exists('ir_hints', $notes)) {
                    $hints = $notes['ir_hints'];
                    //foreach ($hints as $hint) {
                    if (array_key_exists('object', $hints)) {
                        // Get the object attribute - this tells us which AIRR object type this
                        // App can be applied to (e.g. Rearrangement, Clone, Cell).
                        $app_info['object'] = $hints['object'];
                    }
                    if (array_key_exists('requirements', $hints) && array_key_exists('Download', $hints['requirements'])) {
                        // Get the download attribute - this tells us whether the app needs
                        // the data from the Gateway or not. Some Apps use the queries provided
                        // to get the data rather than rely on the Gateway to download it.
                        // This is either TRUE or FALSE as a string.
                        $app_info['download'] = $hints['requirements']['Download'];
                    }
                    if (array_key_exists('resources', $hints) && array_key_exists('memory_byte_per_unit_repertoire', $hints['resources'])) {
                        // Get the memory (in bytes) required per unit for each repertoire for this App
                        $app_info['memory_byte_per_unit_repertoire'] = $hints['resources']['memory_byte_per_unit_repertoire'];
                    }
                    if (array_key_exists('resources', $hints) && array_key_exists('memory_byte_per_unit_total', $hints['resources'])) {
                        // Get the memory (in bytes) required per unit in total units for this App
                        $app_info['memory_byte_per_unit_total'] = $hints['resources']['memory_byte_per_unit_total'];
                    }
                    if (array_key_exists('resources', $hints) && array_key_exists('time_secs_per_million', $hints['resources'])) {
                        // Get the time (in ms) required per unit for this App
                        $app_info['time_secs_per_million'] = $hints['resources']['time_secs_per_million'];
                    }
                    if (array_key_exists('requirements', $hints)) {
                        // Get the list of field requirements for this App.
                        $app_info['requirements'] = $hints['requirements'];
                    }

                    //}
                }
            }
            if (array_key_exists('jobAttributes', $app_config) &&
                array_key_exists('parameterSet', $app_config['jobAttributes']) &&
                array_key_exists('envVariables', $app_config['jobAttributes']['parameterSet'])) {
                $envVariables = $app_config['jobAttributes']['parameterSet']['envVariables'];
                // Loop over the parameters and check for special ir_ parameters
                foreach ($envVariables as $variable) {
                    if (array_key_exists('key', $variable) && $variable['key'] == 'ir_gateway_url') {
                        // The Tapis App uses ir_gateway_url to provide the URL of the source
                        // gateway that is submitting the job. This used to get assets specific
                        // to the given gateway.
                        $gateway_param = $variable;
                        $gateway_param['value'] = config('app.url');
                        $gateway_count = $param_count;
                    }
                    $param_count = $param_count + 1;
                }
                // Overwrite the gateway URL parameter configuration if we got one.
                if ($gateway_count >= 0) {
                    //Log::debug('updateAppTemplates: replacing ' . json_encode($app_config['jobAttributes']['parameterSet']['envVariables'][$gateway_count]));
                    $app_config['jobAttributes']['parameterSet']['envVariables'][$gateway_count] = $gateway_param;
                }
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
        Log::debug('Tapis::getAppTemplate: looking for ' . $app_tag);

        // Return the app template for the given app tap/name.
        if (array_key_exists($app_tag, $this->appTemplates)) {
            return $this->appTemplates[$app_tag];
        } else {
            Log::debug('Tapis::getAppTemplate: could not find app ' . $app_tag);

            return null;
        }
    }

    public function getAppTemplateByLabel($app_label)
    {
        // Return the app template for the given app label.
        Log::debug('Tapis::getAppTemplateByLabel: looking for ' . $app_label);
        foreach ($this->appTemplates as $app_tag => $app_info) {
            // Get this template's label and if it is the same we found it.
            $config = $app_info['config'];
            $label = $config['description'];
            if ($label == $app_label) {
                return $app_info;
            }
        }
        // Couldn't find it if we get here.
        Log::debug('Tapis::getAppTemplateByLabel: could not find ' . $app_label);

        return null;
    }

    public function getJobParameters()
    {
        return $this->jobParameters;
    }

    public function createSystem($config)
    {
        $url = '/v3/systems';
        $token = self::getAnalysisToken();

        return $this->doPOSTRequestWithJSON($this->tapis_client, $url, $token, $config);
    }

    public function updateSystem($system, $config)
    {
        $url = '/v3/systems/' . $system;
        $token = self::getAnalysisToken();

        return $this->doPUTRequestWithJSON($this->tapis_client, $url, $token, [], $config);
    }

    public function updateSystemCredentials($system, $user, $config)
    {
        $url = '/v3/systems/credential/' . $system . '/user/' . $user;
        $token = self::getAnalysisToken();

        return $this->doPOSTRequestWithJSON($this->tapis_client, $url, $token, $config);
    }

    public function createApp($config)
    {
        $url = '/v3/apps';
        $token = self::getAnalysisToken();

        return $this->doPOSTRequestWithJSON($this->tapis_client, $url, $token, $config);
    }

    public function updateApp($app, $config)
    {
        $version = $config['version'];
        $token = self::getAnalysisToken();
        $url = '/v3/apps/' . $app . '/' . $version;

        return $this->doPUTRequestWithJSON($this->tapis_client, $url, $token, [], $config);
    }

    public function createJob($config)
    {
        $url = '/v3/jobs/submit';
        $token = self::getAnalysisToken();

        return $this->doPOSTRequestWithJSON($this->tapis_client, $url, $token, $config);
    }

    public function listApps()
    {
        $url = '/v3/apps';
        $token = self::getAnalysisToken();

        return $this->doGETRequest($this->tapis_client, $url, $token);
    }

    public function getApp($app_id)
    {
        $url = '/v3/apps/' . $app_id;
        $token = self::getAnalysisToken();

        return $this->doGETRequest($this->tapis_client, $url, $token);
    }

    public function listSystems()
    {
        $url = '/v3/systems';
        $token = self::getAnalysisToken();

        return $this->doGETRequest($this->tapis_client, $url, $token);
    }

    public function getSystem($system_id)
    {
        $url = '/v3/systems/' . $system_id;
        $token = self::getAnalysisToken();

        return $this->doGETRequest($this->tapis_client, $url, $token);
    }

    public function getJobHistory($job_id)
    {
        $url = '/v3/jobs/' . $job_id . '/history';
        $token = self::getAnalysisToken();

        return $this->doGETRequest($this->tapis_client, $url, $token, true);
    }

    public function getJobStatus($job_id)
    {
        $url = '/v3/jobs/' . $job_id . '/status';
        $token = self::getAnalysisToken();

        return $this->doGETRequest($this->tapis_client, $url, $token, true);
    }

    public function getJob($job_id)
    {
        $url = '/v3/jobs/' . $job_id;
        $token = self::getAnalysisToken();

        return $this->doGETRequest($this->tapis_client, $url, $token, true);
    }

    public function getJobOutputFile($job_id, $file)
    {
        $host_name = config('services.tapis.default_execution_system.host');
        $system_id = config('services.tapis.system_execution.name_prefix') . '-' . $host_name;

        $job_dir = config('services.tapis.system_execution.exec_job_working_dir');
        $url = '/v3/files/content/' . $system_id . $job_dir . '/jobs/' . $job_id . '/' . $file;
        $token = self::getAnalysisToken();

        return $this->doGETRequest($this->tapis_client, $url, $token, true);
    }

    public function killJob($job_id)
    {
        // Set up the URL for to kill/cancel a Tapis job.
        $url = '/v3/jobs/' . $job_id . '/cancel';
        // Get the analysis token
        $token = self::getAnalysisToken();

        // Post the request and return the result.
        return $this->doPOSTRequest($this->tapis_client, $url, $token);
    }

    public function getExecutionSystemConfig($name, $host, $port, $username)
    {
        $job_working_dir = config('services.tapis.system_execution.exec_job_working_dir');
        $t = [
            'id' => $name,
            'description' => $name,
            'host' => $host,
            'port' => $port,
            'systemType' => 'LINUX',
            'defaultAuthnMethod' => 'PKI_KEYS',
            'effectiveUserId' => $username,
            'rootDir' => '/',
            'canExec' => true,
            'canRunBatch' => true,
            'mpiCmd' => 'string',
            'jobRuntimes' => [['runtimeType' => 'SINGULARITY']],
            'jobWorkingDir' => $job_working_dir,
            'jobMaxJobs' => 12000,
            'jobMaxJobsPerUser' => -1,
            'batchScheduler' => 'SLURM',
            'batchDefaultLogicalQueue' => 'default',
            'batchLogicalQueues' => [
                [
                    'name' => 'default',
                    'hpcQueueName' => 'default',
                    'maxJobsPerUser' => 16,
                    'minNodeCount' => 1,
                    'maxNodeCount' => 1,
                    'minCoresPerNode' => 1,
                    'maxCoresPerNode' => $this->coresPerNode,
                    'minMemoryMB' => 1,
                    'maxMemoryMB' => $this->memoryMBPerNode,
                    'minMinutes' => 1,
                    'maxMinutes' => $this->maxMinutes,
                ],
            ],
        ];

        return $t;
    }

    public function getStorageSystemConfig($name, $host, $port, $username, $rootDir)
    {
        $t = [
            'id' => $name,
            'description' => $name,
            'host' => $host,
            'port' => $port,
            'systemType' => 'LINUX',
            'defaultAuthnMethod' => 'PKI_KEYS',
            'effectiveUserId' => $username,
            'canExec' => false,
            'rootDir' => $rootDir,
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
        $app_config['id'] = $name;
        $app_config['jobAttributes']['execSystemId'] = $executionSystem;
        $exec_gateway_base_dir = config('services.tapis.system_execution.exec_gateway_mount_dir');
        $exec_singularity_dir = config('services.tapis.system_execution.exec_singularity_dir');
        $singularity_image = $app_config['containerImage'];
        $app_config['containerImage'] = $exec_gateway_base_dir . '/' . $exec_singularity_dir . '/' . $singularity_image;

        // Get the container path info.
        $container_gateway_mount_dir = config('services.tapis.system_execution.container_gateway_mount_dir');
        $container_app_dir = $container_gateway_mount_dir . '/' . config('services.tapis.system_execution.container_app_dir');
        $container_app_script = config('services.tapis.system_execution.container_app_script');

        // Create a bash shell arguement for the Job. The Gateway controls where
        // jobs are run from and what shell script is run for the App. If the App
        // conforms to the Gateway App spec, then this all will work!
        $param['name'] = 'program';
        $param['arg'] = 'bash ' . $container_app_dir . '/' . $id . '/' . $container_app_script;
        $param['inputMode'] = 'FIXED';

        // Get the original args and make sure the "program" arg is the first.
        $orig_args = $app_config['jobAttributes']['parameterSet']['appArgs'];
        $final_args = array_merge([$param], $orig_args);
        $app_config['jobAttributes']['parameterSet']['appArgs'] = $final_args;

        //Log::debug('Tapis::getAppConfig: App config:');
        //Log::debug($app_config);

        return $app_config;
    }

    public function getJobConfig($gateway_jobid, $name, $app_id, $app_version, $download_file, $gateway_system, $gateway_notification_url, $gateway_dir, $params, $inputs, $job_params)
    {
        // Get the gateway environment stuff required
        $gateway_url = config('app.url');
        // Get the execution host path info
        $exec_gateway_mount_dir = config('services.tapis.system_execution.exec_gateway_mount_dir');
        $exec_singularity_dir = $exec_gateway_mount_dir . '/' . config('services.tapis.system_execution.exec_singularity_dir');

        // Get the container path info.
        $container_gateway_mount_dir = config('services.tapis.system_execution.container_gateway_mount_dir');
        $gateway_util_dir = $container_gateway_mount_dir . '/' . config('services.tapis.system_execution.container_util_dir');
        $gateway_app_dir = $container_gateway_mount_dir . '/' . config('services.tapis.system_execution.container_app_dir');

        // Set up the base Job Config.
        $t = [
            'name' => $name,
            'appId' => $app_id,
            'appVersion' => $app_version,
            'archiveSystemId' => $gateway_system,
            'archiveSystemDir' => $gateway_dir,
            'parameterSet' => [
                'appArgs' => $params,
            ],
            'fileInputs' => [$inputs],
            'subscriptions' => [
                [
                    'description' => 'Job status call back notification',
                    'enabled' => true,
                    'eventCategoryFilter' => 'JOB_NEW_STATUS',
                    'deliveryTargets' => [[
                        'deliveryMethod' => 'WEBHOOK',
                        'deliveryAddress' => $gateway_notification_url . '/job/update-status',
                    ]],
                ],
            ],
        ];

        // Set up the SLURM scheduler commands
        $t['parameterSet']['schedulerOptions'][] = ['name' => 'name', 'arg' => '--job-name ' . $name];
        $t['parameterSet']['schedulerOptions'][] = ['name' => 'output_file', 'arg' => '--output ' . $name . '.out'];
        $t['parameterSet']['schedulerOptions'][] = ['name' => 'error_file', 'arg' => '--error ' . $name . '.err'];
        // Set up the environment variables iReceptor Apps can use.
        $t['parameterSet']['envVariables'][] = ['key' => 'PYTHONNOUSERSITE', 'value' => '1'];
        $t['parameterSet']['envVariables'][] = ['key' => 'IR_DOWNLOAD_FILE', 'value' => $download_file];
        $t['parameterSet']['envVariables'][] = ['key' => 'IR_SINGULARITY', 'value' => $exec_singularity_dir];
        $t['parameterSet']['envVariables'][] = ['key' => 'IR_GATEWAY_URL', 'value' => $gateway_url];
        $t['parameterSet']['envVariables'][] = ['key' => 'IR_GATEWAY_BASE_DIR', 'value' => $container_gateway_mount_dir];
        $t['parameterSet']['envVariables'][] = ['key' => 'IR_GATEWAY_UTIL_DIR', 'value' => $gateway_util_dir];
        $t['parameterSet']['envVariables'][] = ['key' => 'IR_GATEWAY_APP_DIR', 'value' => $gateway_app_dir];
        $t['parameterSet']['envVariables'][] = ['key' => 'IR_GATEWAY_JOBID', 'value' => strval($gateway_jobid)];

        // Set up the container arguments. We want to mount external mount points.
        $t['parameterSet']['containerArgs'][] = ['name' => 'project_mount', 'arg' => '-B /project:/project'];
        $t['parameterSet']['containerArgs'][] = ['name' => 'scratch_mount', 'arg' => '-B /scratch:/scratch'];
        $t['parameterSet']['containerArgs'][] = ['name' => 'localscratch_mount', 'arg' => '-B /localscratch:/localscratch'];
        $t['parameterSet']['containerArgs'][] = ['name' => 'gateway_app_mount', 'arg' => '-B ' . $exec_gateway_mount_dir . ':' . $container_gateway_mount_dir];

        // Set up the job parameters. We loop over the possible job parameters and
        // check to see if any of them are set in the job_params provided by the caller.
        foreach ($this->getJobParameters() as $job_parameter_info) {
            Log::debug('   getJobConfig: Processing job parameter ' . $job_parameter_info['label']);
            // If the parameter is provided by the caller, process it.
            if (isset($job_params[$job_parameter_info['label']])) {
                // We need to make sure the type is correct or the JSON will fail.
                // Once convereted, we set the parameter based on the label. The label
                // in the config MUST be the correct Tapis label for that field.
                if ($job_parameter_info['type'] == 'integer') {
                    $t[$job_parameter_info['label']] = intval($job_params[$job_parameter_info['label']]);
                } else {
                    // In Tapis
                    $t[$job_parameter_info['label']] = preg_replace('/\s+/', '', $job_params[$job_parameter_info['label']]);
                }
                Log::debug('   getJobConfig: Parameter value = ' . $t[$job_parameter_info['label']]);
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

    public function getUsers()
    {
        $url = '/v3/oauth2/profiles';
        $token = self::getAnalysisToken();
        $response = $this->doGETRequest($url, $token);

        return $response->result;
    }

    public function userExists($username)
    {
        $l = $this->getUsers();
        foreach ($l as $u) {
            if ($u->username == $username) {
                return true;
            }
        }

        return false;
    }

    public function generateUsername($first_name, $last_name)
    {
        $first_name_stripped = str_replace(' ', '', $first_name);
        $last_name_stripped = str_replace(' ', '', $last_name);
        $username = strtolower($first_name_stripped) . '_' . strtolower($last_name_stripped);
        $username = iconv('UTF-8', 'ASCII//TRANSLIT', $username); // remove diacritics

        // if username already exists, append number
        if ($this->userExists($username)) {
            $i = 2;
            $alternate_username = $username . $i;
            while ($this->userExists($alternate_username)) {
                $i++;
                $alternate_username = $username . $i;
            }
            $username = $alternate_username;
        }

        return $username;
    }

    public function getUserWithEmail($email)
    {
        $user = null;

        $user_list = $this->getUsers();
        foreach ($user_list as $u) {
            if ($u->email == $email) {
                $user = $u;
            }
        }

        return $user;
    }

    public function getUserWithUsername($username)
    {
        $user = null;

        $user_list = $this->getUsers();
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

    private function doGETRequest($client, $url, $token, $raw_json = false)
    {
        return $this->doHTTPRequest($client, 'GET', $url, $token, [], null, $raw_json);
    }

    public function doPOSTRequest($client, $url, $token, $variables = [], $body = null)
    {
        return $this->doHTTPRequest($client, 'POST', $url, $token, $variables, $body);
    }

    public function doPUTRequest($client, $url, $token, $variables = [])
    {
        return $this->doHTTPRequest($client, 'PUT', $url, $token, $variables);
    }

    public function doPUTRequestWithJSON($client, $url, $token, $variables = [], $body)
    {
        // convert body object to json
        $json = json_encode($body, JSON_PRETTY_PRINT);

        return $this->doHTTPRequest($client, 'PUT', $url, $token, $variables, $json);
    }

    public function doDELETERequest($client, $url, $token)
    {
        return $this->doHTTPRequest($client, 'DELETE', $url, $token);
    }

    public function doPOSTRequestWithJSON($client, $url, $token, $config)
    {
        // convert config object to json
        $json = json_encode($config, JSON_PRETTY_PRINT);

        return $this->doPOSTRequest($client, $url, $token, [], $json);
    }

    private function initGuzzleRESTClient()
    {
        $defaults = [];

        // set tapis URL
        $tapis_url = config('services.tapis.tapis_url');
        $defaults['base_uri'] = $tapis_url;

        // accept self-signed SSL certificates
        $defaults['verify'] = false;
        $this->tapis_client = new \GuzzleHttp\Client($defaults);

        // set tenant URL
        $tenant_url = config('services.tapis.tenant_url');
        $defaults['base_uri'] = $tenant_url;

        // accept self-signed SSL certificates
        $defaults['verify'] = false;
        $this->tenant_client = new \GuzzleHttp\Client($defaults);
    }

    private function doHTTPRequest($client, $method, $url, $token, $variables = [], $body = null, $raw_json = false)
    {
        $headers = [];
        if ($token != null) {
            $headers['X-Tapis-Token'] = $token;
            //Log::debug('Tapis::doHTTPRequest - Auth headers:' . $headers['X-Tapis-Token']);
        }

        $data = [];
        if ($body == null) {
            $headers['Content-Type'] = 'application/json';
            $data = ['headers' => $headers, 'form_params' => $variables];
        } else {
            $headers['Content-Type'] = 'application/json';
            $data = ['headers' => $headers, 'body' => $body];
        }

        try {
            //Log::debug('Tapis::doHTTPRequest - data = ' . json_encode($data));
            Log::debug('Tapis::doHTTPRequest - url = ' . $url);
            $response = $client->request($method, $url, $data);
        } catch (ClientException $exception) {
            Log::error('Tapis::doHTTPRequest:: ClientException');
            $tapis_response_str = $exception->getResponse()->getBody()->getContents();
            $tapis_response = json_decode($tapis_response_str);
            Log::error('Tapis::doHTTPRequest:: ClientException - query = ' . $url);
            Log::error('Tapis::doHTTPRequest:: ClientException - response = ' . $tapis_response_str);
            // If it is a Tapis error we want the client to handle it.
            if ($this->isTapisError($tapis_response)) {
                Log::error('Tapis::doHTTPRequest:: ClientException - returning response = ' . $tapis_response_str);

                if ($raw_json) {
                    return $tapis_response_str;
                } else {
                    return $tapis_response;
                }
            } else {
                throw exception;
            }
        } catch (RequestException $exception) {
            Log::error('Tapis::doHTTPRequest:: RequestException');
            $response = $exception->getResponse()->getBody()->getContents();
            Log::error('Tapis::doHTTPRequest:: RequestException - query = ' . $url);
            Log::error('Tapis::doHTTPRequest:: RequestException - response = ' . $response);
            $this->raiseExceptionIfTapisError($response);

            return $response;
        } catch (ServerException $exception) {
            Log::error('Tapis::doHTTPRequest:: ServerException');
            $response = $exception->getResponse()->getBody()->getContents();
            Log::error('Tapis::doHTTPRequest:: ServerException - query = ' . $url);
            Log::error('Tapis::doHTTPRequest:: ServerException - response = ' . $response);
            $this->raiseExceptionIfTapisError($response);

            return $response;
        } catch (\Exception $exception) {
            Log::debug('Tapis::doHTTPRequest: error on query ' . $url);
            Log::debug('Tapis::doHTTPRequest: Error: ' . $exception->getMessage());

            return null;
        }

        // return response as object
        $json = $response->getBody();
        //Log::debug('json response -> ' . $json);
        if ($raw_json) {
            return $json;
        } else {
            $response = json_decode($json);
            $this->raiseExceptionIfTapisError($response);

            return $response;
        }
    }

    public function isTapisError($response)
    {
        if ($response == null) {
            return true;
        }
        if (property_exists($response, 'error')) {
            return true;
        }
        if (property_exists($response, 'status') && $response->status == 'error') {
            return true;
        }
        if (property_exists($response, 'fault')) {
            return true;
        }
        if (property_exists($response, 'status') && $response->status != 'success') {
            return true;
        }

        return false;
    }

    public function raiseExceptionIfTapisError($response)
    {
        if ($this->isTapisError($response)) {
            if ($response == null) {
                throw new \Exception('TAPIS error: response was empty');
            }
            if (property_exists($response, 'error')) {
                throw new \Exception('TAPIS error: ' . $response->error . ': ' . $response->error_description);
            }
            if (property_exists($response, 'status') && $response->status == 'error') {
                throw new \Exception('TAPIS error: ' . $response->message);
            }
            if (property_exists($response, 'fault')) {
                throw new \Exception('TAPIS error: ' . $response->fault->message);
            }
            if (property_exists($response, 'status') && $response->status != 'success') {
                throw new \Exception('TAPIS error: ' . $response->message);
            }
        }
    }
}
