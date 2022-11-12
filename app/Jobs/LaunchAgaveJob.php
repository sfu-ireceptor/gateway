<?php

namespace App\Jobs;

use App\Agave;
use App\Job;
use App\LocalJob;
use App\Query;
use App\Sequence;
use App\SequenceCell;
use App\SequenceClone;
use App\System;
use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class LaunchAgaveJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // number of times the job may be attempted.
    public $tries = 2;

    protected $jobId;
    protected $request_data;
    protected $gw_username;
    protected $gw_userid;
    protected $localJobId;

    // create job instance
    public function __construct($jobId, $request_data, $localJobId, $gw_username, $gw_userid)
    {
        $this->jobId = $jobId;
        $this->request_data = $request_data;
        $this->gw_username = $gw_username;
        $this->gw_userid = $gw_userid;
        $this->localJobId = $localJobId;
    }

    // execute job
    public function handle()
    {
        try {
            // Get the current user name and user ID.
            $gw_username = $this->gw_username;
            $gw_userid = $this->gw_userid;

            // Get the local beanstalk Job
            $localJob = LocalJob::find($this->localJobId);
            $localJob->setRunning();

            // Get the long running job in the DB
            $job = Job::find($this->jobId);

            // generate csv file
            $job->updateStatus('FEDERATING DATA');
            Log::debug('########## $this->request_data = ' . json_encode($this->request_data));
            Log::debug('$request_data[filters_json]' . $this->request_data['filters_json']);
            $filters = json_decode($this->request_data['filters_json'], true);

            // Get the sample filters
            $sample_filter_fields = [];
            if (isset($filters['sample_query_id'])) {
                $sample_query_id = $filters['sample_query_id'];
                Log::info('query_id = ' . $sample_query_id);

                // sample filters
                $sample_filters = Query::getParams($sample_query_id);
                foreach ($sample_filters as $k => $v) {
                    if ($v) {
                        if (is_array($v)) {
                            $sample_filter_fields[$k] = implode(', ', $v);
                        } else {
                            $sample_filter_fields[$k] = $v;
                        }
                    }
                }
                // remove gateway-specific params
                unset($sample_filter_fields['open_filter_panel_list']);
                unset($sample_filter_fields['page']);
                unset($sample_filter_fields['cols']);
                unset($sample_filter_fields['sort_column']);
                unset($sample_filter_fields['sort_order']);
                unset($sample_filter_fields['extra_field']);
            }

            // Extract the query type, either sequence, clone, or cell
            // Queries look like this: https://gateway.ireceptor.org/cells?query_id=9982
            $job_url = $job->url;
            $query_string = 'query_id=';
            $jobType = '';
            if (strpos($job_url, 'sequences?' . $query_string)) {
                $jobType = 'sequence';
            } elseif (strpos($job_url, 'clones?' . $query_string)) {
                $jobType = 'clone';
            } elseif (strpos($job_url, 'cells?' . $query_string)) {
                $jobType = 'cell';
            }
            Log::debug('LaunchAgaveJob::handle - Job type = ' . $jobType);

            // Generate the ZIP file for the data that meets the query criteria.
            // Return object contains attributes that describe the ZIP archive that was
            // created.
            if ($jobType == 'sequence') {
                $zip_info = Sequence::sequencesTSV($filters, $gw_username, $job->url, $sample_filter_fields);
            } elseif ($jobType == 'clone') {
                $zip_info = SequenceClone::clonesTSV($filters, $gw_username, $job->url, $sample_filter_fields);
            } elseif ($jobType == 'cell') {
                $zip_info = SequenceCell::cellsTSV($filters, $gw_username, $job->url, $sample_filter_fields);
            }

            // Get the path to where the data and ZIP file is in the app local file system.
            $base_path = $zip_info['base_path'];
            // Get the public storage path (this is relative to the gateway's public data).
            $dataFilePath = $zip_info['public_path'];

            // Since we have the ZIP file of the download, we don't need to keep the
            // original data file directory. We are a bit careful that we don't remove
            // all of the data in $base_path if we have an obscure error condition where
            // $base_name is empty.
            if ($zip_info['base_name'] != '') {
                File::deleteDirectory($base_path . $zip_info['base_name']);
            }

            // Create the folder for storing the output (job base_name with _output suffix)
            // from the job and store the name in the database
            $archive_folder = $zip_info['base_name'] . '_output';
            $archive_folder_path = $base_path . $archive_folder;
            Log::debug('Creating archive folder: ' . $archive_folder_path);
            $old = umask(0);
            mkdir($archive_folder_path, 0770);
            umask($old);
            $job->input_folder = $archive_folder;
            $job->save();

            // refresh AGAVE token since we are about to do some Agave work.
            $agave = new Agave;
            $user = User::where('username', $gw_username)->first();
            if ($user == null) {
                throw new \Exception('User ' . $gw_username . ' could not be found in local database.');
            }
            Log::debug('###### LaunchAgaveJob::handle - jobId = ' . $this->jobId . ', localJobId = ' . $this->localJobId);
            Log::debug('###### LaunchAgaveJob::handle - refreshing token = ' . $user->password . ', refresh_token = ' . $user->refresh_token);
            $token_info = $agave->renewToken($user->refresh_token);
            if ($token_info == null) {
                Log::error('###### LaunchAgaveJob::handle - unable to refresh token for ' . $gw_username);
                throw new \Exception('Unable to refresh token for ' . $gw_username);
            }
            $user->updateToken($token_info);
            $user->save();
            $token = $user->password;

            // Create systems for this user if they don't exist.
            System::createDefaultSystemsForUser($gw_username, $gw_userid, $token);

            // Get the current system for the current user.
            $executionSystem = System::getCurrentSystem($gw_userid);
            // Get the username on the execution system
            $username = $executionSystem->username;
            // Get the Agave name for the execution system
            $appExecutionSystem = $executionSystem->name;

            // Call back URL to use for agave notifications
            $notificationUrl = config('services.agave.gw_notification_url');

            // Agave name of the system where the data is staged. Essentially the
            // Agave system name for the Gateway for this user. This is where the
            // data is stored.
            $systemStaging = config('services.agave.system_staging.name_prefix') . str_replace('_', '-', $gw_username);
            // Agave name for the deployment system. This is where the Apps are stored.
            $appDeploymentSystem = config('services.agave.system_deploy.name_prefix') . str_replace('_', '-', $gw_username) . '-' . $username;

            // Get the App config for the app in question. The AppID is in the request.
            $appId = $this->request_data['app_id'];
            Log::info('LaunchAgaveJob::handle - app_id = ' . $appId);
            $appTemplateInfo = $agave->getAppTemplate($appId);
            $appTemplateConfig = $appTemplateInfo['config'];

            // Set up the App Tapis name, the human name, and the deployment path.
            // The path for the app is the same as the appID
            $appName = $appId . '-' . $executionSystem->name;
            $appDeploymentPath = $appId;
            $appHumanName = $appTemplateConfig['label'];

            // Based on the above, create the Tapis App.
            $appConfig = $agave->getAppConfig($appId, $appName, $appExecutionSystem, $appDeploymentSystem, $appDeploymentPath);
            Log::debug('app token: ' . $token);
            $response = $agave->createApp($token, $appConfig);
            $agaveAppId = $response->result->id;
            Log::debug('app created: ' . $appId);

            // The Gateway sets the download_file input as it controls the data
            // that is processed by the application.
            $inputs['download_file'] = 'agave://' . $systemStaging . '/' . $zip_info['zip_name'];
            foreach ($inputs as $key => $value) {
                Log::debug('Job input ' . $key . ' = ' . $value);
            }

            // Process the App parameters
            $params = [];
            foreach ($appConfig['parameters'] as $parameter_info) {
                Log::debug('   Processing parameter ' . $parameter_info['id']);
                // If it visible, we want to pass on the input to the job.
                if ($parameter_info['value']['visible']) {
                    $params[$parameter_info['id']] = $this->request_data[$parameter_info['id']];
                    Log::debug('   Parameter value = ' . $this->request_data[$parameter_info['id']]);
                }
            }

            // Process the job parameters
            $job_params = [];
            // Get the possible list of parameters that can be set. The Agave class
            // manages which job parameters can be set.
            $job_parameter_list = $agave->getJobParameters();
            foreach ($job_parameter_list as $job_parameter_info) {
                // If the parameter is provided, keep track of it so we can give it to the job.
                Log::debug('   Processing job parameter ' . $job_parameter_info['label']);
                if (isset($this->request_data[$job_parameter_info['label']])) {
                    $job_params[$job_parameter_info['label']] = $this->request_data[$job_parameter_info['label']];
                    Log::debug('   Parameter value = ' . $this->request_data[$job_parameter_info['label']]);
                }
            }

            // submit AGAVE job
            $job->updateStatus('SENDING JOB TO AGAVE');
            $job_config = $agave->getJobConfig('irec-job-' . $this->jobId, $agaveAppId, $systemStaging, $notificationUrl, $archive_folder, $params, $inputs, $job_params);
            $response = $agave->createJob($token, $job_config);
            $job->agave_id = $response->result->id;
            $job->updateStatus('JOB ACCEPTED BY AGAVE. PENDING.');

            // Now that we are done and the Agave job is running, this LocalJob is done.
            $localJob->setFinished();
        } catch (Exception $e) {
            Log::error('LaunchAgaveJob::handle - ' . $e->getMessage());
            Log::error($e);
            $job = Job::find($this->jobId);
            $job->updateStatus('STOPPED');

            $localJob = LocalJob::find($this->localJobId);
            $localJob->setFailed();
            throw new App\Jobs\Exception('Job failed.');
        }
    }

    /**
     * The job failed to process.
     *
     * @param  Exception  $exception
     * @return void
     */
    public function failed($exception)
    {
        // Print an error message
        Log::error('LaunchAgaveJob::failed - ' . $exception->getMessage());
        Log::error($exception);

        // Mark the job as failed.
        $job = Job::find($this->jobId);
        $job->updateStatus('STOPPED');

        // Mark the local job as failed.
        $localJob = LocalJob::find($this->localJobId);
        $localJob->setFailed();
    }
}
