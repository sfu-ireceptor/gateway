<?php

namespace App\Jobs;

use App\Cell;
use App\Clones;
use App\Job;
use App\LocalJob;
use App\Query;
use App\Sequence;
use App\System;
use App\Tapis;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Throwable;

class LaunchJob implements ShouldQueue
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

            // Get a Tapis object to work with.
            $tapis = new Tapis;

            // Get the Tapis App config for the app in question. The AppID is in the request.
            $appId = $this->request_data['app_id'];
            Log::info('LaunchJob::handle - app_id = ' . $appId);
            $appTemplateInfo = $tapis->getAppTemplate($appId);
            $appTemplateConfig = $appTemplateInfo['config'];

            // Check to see if this App requires downloads.
            $download_data = true;
            if (array_key_exists('download', $appTemplateInfo) && $appTemplateInfo['download'] == 'FALSE') {
                Log::info('LaunchJob::handle - App does not require downloads');
                $download_data = false;
            } else {
                Log::info('LaunchJob::handle - App requires downloads');
                $download_data = true;
            }

            // generate csv file
            $job->updateStatus('FEDERATING DATA');
            Log::debug('LaunchJob::handle - $this->request_data = ' . json_encode($this->request_data));
            Log::debug('LaunchJob::handle - $request_data[filters_json]' . $this->request_data['filters_json']);
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
            Log::debug('LaunchJob::handle - Job type = ' . $jobType);

            // Generate the ZIP file for the data that meets the query criteria.
            // Return object contains attributes that describe the ZIP archive that was
            // created.
            if ($jobType == 'sequence') {
                $zip_info = Sequence::sequencesTSV($filters, $gw_username, $job->url,
                    $sample_filter_fields, $download_data);
            } elseif ($jobType == 'clone') {
                $zip_info = Clones::clonesTSV($filters, $gw_username, $job->url,
                    $sample_filter_fields, $download_data);
            } elseif ($jobType == 'cell') {
                $zip_info = Cell::cellsTSV($filters, $gw_username, $job->url,
                    $sample_filter_fields, $download_data);
            }

            // Get the path to where the data and ZIP file is in the app local file system.
            $base_path = $zip_info['base_path'];
            Log::debug('LaunchJob::handle - base_path  = ' . $base_path);

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
            Log::debug('LaunchJob::handle - Creating archive folder: ' . $archive_folder_path);
            $old = umask(0);
            mkdir($archive_folder_path, 0770);
            umask($old);
            $job->input_folder = $archive_folder;
            $job->save();

            // Create systems for this user if they don't exist.
            System::createDefaultSystemsForUser($gw_username, $gw_userid);

            // Get the current system for the current user.
            $executionSystem = System::getCurrentSystem($gw_userid);
            // Get the username on the execution system
            $username = $executionSystem->username;
            // Get the Tapis name for the execution system
            $appExecutionSystem = $executionSystem->name;

            // Call back URL to use for tapis notifications
            $notificationUrl = config('services.tapis.gw_notification_url');

            // Tapis name of the system where the data is staged. Essentially the
            // Tapis system name for the Gateway. This is where the
            // data is stored.
            $systemStaging = config('services.tapis.system_staging.name_prefix');
            // Tapis name for the deployment system. This is where the Apps are stored.
            $appDeploymentSystem = config('services.tapis.system_deploy.name_prefix');

            // Set up the App Tapis name, the human name, and the deployment path.
            // The path for the app is the same as the appID
            //$appName = $appId . '-' . $executionSystem->name;
            $appName = $appId;
            $appDeploymentPath = $appId;
            $appHumanName = $appTemplateConfig['description'];

            // Based on the above, create the Tapis App.
            $appConfig = $tapis->getAppConfig($appId, $appName, $appExecutionSystem, $appDeploymentSystem, $appDeploymentPath);
            // Try to get the App if it already exists.
            $appResponse = $tapis->getApp($appName);
            //Log::debug('LaunchJob::handle - App info = ' . json_encode($appResponse));
            if ($appResponse->status == 'success') {
                // If it exists, update it in case the config has changed, throw
                // an error of the update fails.
                Log::debug('LaunchJob::handle - Updating app: ' . $appId);
                $tapisAppId = $appResponse->result->uuid;
                $response = $tapis->updateApp($appName, $appConfig);
                $tapis->raiseExceptionIfTapisError($response);
                Log::debug('LaunchJob::handle - app updated: ' . $appId);
            } else {
                // If it doesn't exist, create it and throw an error if the creation
                // fails.
                Log::debug('LaunchJob::handle - Creating app: ' . $appId);
                $response = $tapis->createApp($appConfig);
                $tapis->raiseExceptionIfTapisError($response);
                $tapisAppId = $response->result->uuid;
                Log::debug('LaunchJob::handle - app created: ' . $appId);
            }

            // The Gateway sets the download_file input as it controls the data
            // that is processed by the application.
            $inputs = [
                'name' => 'gateway_download_zip',
                'sourceUrl' => 'tapis://' . $systemStaging . '/' . $zip_info['zip_name'],
            ];

            // Process the App parameters
            $params = [];
            foreach ($appConfig['jobAttributes']['parameterSet']['appArgs'] as $parameter_info) {
                Log::debug('   Processing parameter ' . $parameter_info['name']);
                // If it visible, we want to pass on the input to the job.
                if ($parameter_info['inputMode'] != 'FIXED') {
                    $param = new \stdClass();
                    $param->name = $parameter_info['name'];
                    $param->arg = $this->request_data[$parameter_info['name']];
                    $params[] = $param;
                    Log::debug('   Parameter value = ' . $this->request_data[$parameter_info['name']]);
                }
            }

            $env_variables = [
                ['key' => 'PYTHONNOUSERSITE', 'value' => '1'],
                ['key' => 'download_file', 'value' => $zip_info['zip_name']],
            ];

            // Process the job parameters
            $job_params = [];
            // Get the possible list of parameters that can be set. The Tapis class
            // manages which job parameters can be set.
            $job_parameter_list = $tapis->getJobParameters();
            foreach ($job_parameter_list as $job_parameter_info) {
                // If the parameter is provided, keep track of it so we can give it to the job.
                Log::debug('   Processing job parameter ' . $job_parameter_info['label']);
                if (isset($this->request_data[$job_parameter_info['label']])) {
                    $job_params[$job_parameter_info['label']] = $this->request_data[$job_parameter_info['label']];
                    Log::debug('   Parameter value = ' . $this->request_data[$job_parameter_info['label']]);
                }
            }

            // submit Tapis job
            $job->updateStatus('SENDING JOB FOR ANALYSIS');
            $job_config = $tapis->getJobConfig($this->jobId, 'ireceptor-' . $this->jobId, $appName, $zip_info['zip_name'], $systemStaging, $notificationUrl, $archive_folder, $params, $inputs, $job_params);
            $response = $tapis->createJob($job_config);
            //Log::debug('LaunchJob::handle submit response = ' . json_encode($response));
            $job->agave_id = $response->result->uuid;
            $job->updateStatus('JOB ACCEPTED FOR ANALYSIS. PENDING.');

            // Now that we are done and the Tapis job is running, this LocalJob is done.
            $localJob->setFinished();
        } catch (\Exception $e) {
            Log::error('LaunchJob::handle - ' . $e->getMessage());
            Log::error($e);
            $job = Job::find($this->jobId);
            $job->updateStatus('INTERNAL_ERROR');

            $localJob = LocalJob::find($this->localJobId);
            $localJob->setFailed();
            throw new \Exception('Job failed.');
        }
    }

    /**
     * The job failed to process.
     *
     * @param  Exception  $exception
     * @return void
     */
    public function failed(Throwable $exception)
    {
        // Print an error message
        Log::error('LaunchJob::failed - ' . $exception->getMessage());
        Log::error($exception);

        // Mark the job as failed.
        $job = Job::find($this->jobId);
        $job->updateStatus('INTERNAL_ERROR');

        // Mark the local job as failed.
        $localJob = LocalJob::find($this->localJobId);
        $localJob->setFailed();
    }
}
