<?php

namespace App\Jobs;

use App\Agave;
use App\Job;
use App\LocalJob;
use App\Query;
use App\Sequence;
use App\SequenceCell;
use App\SequenceClone;
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
    protected $tenant_url;
    protected $token;
    protected $username;
    protected $systemStaging;
    protected $notificationUrl;
    protected $agaveAppId;
    protected $gw_username;
    protected $params;
    protected $job_params;
    protected $inputs;
    protected $localJobId;
    protected $jobType;

    // create job instance
    public function __construct($jobId, $request_data, $tenant_url, $token, $username, $systemStaging, $notificationUrl, $agaveAppId, $gw_username, $params, $inputs, $job_params, $localJobId, $jobType)
    {
        $this->jobId = $jobId;
        $this->request_data = $request_data;
        $this->tenant_url = $tenant_url;
        $this->token = $token;
        $this->username = $username;
        $this->systemStaging = $systemStaging;
        $this->notificationUrl = $notificationUrl;
        $this->agaveAppId = $agaveAppId;
        $this->gw_username = $gw_username;
        $this->params = $params;
        $this->inputs = $inputs;
        $this->job_params = $job_params;
        $this->localJobId = $localJobId;
        $this->jobType = $jobType;
    }

    // execute job
    public function handle()
    {
        try {
            // refresh AGAVE token
            $agave = new Agave;
            $user = User::where('username', $this->gw_username)->first();
            if ($user == null) {
                throw new \Exception('User ' . $this->gw_username . ' could not be found in local database.');
            }
            $rt = $user->refresh_token;
            $r = $agave->renewToken($rt);
            $user->updateToken($r);
            $user->save();
            $this->token = $user->password;

            // Get the local Job
            $localJob = LocalJob::find($this->localJobId);
            $localJob->setRunning();

            // find job in DB
            $job = Job::find($this->jobId);

            // generate csv file
            $job->updateStatus('FEDERATING DATA');
            Log::info('########## $this->request_data = ' . json_encode($this->request_data));
            Log::info('$request_data[filters_json]' . $this->request_data['filters_json']);
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

            // Generate the ZIP file for the data that meets the query criteria.
            if ($this->jobType == 'sequence') {
                $t = Sequence::sequencesTSV($filters, $this->gw_username, $job->url, $sample_filter_fields);
            } elseif ($this->jobType == 'clone') {
                $t = SequenceClone::clonesTSV($filters, $this->gw_username, $job->url, $sample_filter_fields);
            } elseif ($this->jobType == 'cell') {
                $t = SequenceCell::cellsTSV($filters, $this->gw_username, $job->url, $sample_filter_fields);
            }
            // Get the path to where the data and ZIP file is.
            $base_path = $t['base_path'];
            // Get the public storage path (this is relative to the gateway's public data).
            $dataFilePath = $t['public_path'];

            // The Gateway sets the download_file input as it controls the data
            // that is processed by the application.
            //$inputs['download_file'] = 'agave://' . $this->systemStaging . '/' . basename($dataFilePath);
            $inputs['download_file'] = 'agave://' . $this->systemStaging . '/' . $t['zip_name'];
            foreach ($inputs as $key => $value) {
                Log::debug('Job input ' . $key . ' = ' . $value);
            }

            // Since we have the ZIP file of the download, we don't need to keep the
            // original data file directory. We are a bit careful that we don't remove
            // all of the data in $base_path if we have an obscure error condition where
            // $base_name is empty.
            if ($t['base_name'] != '') {
                File::deleteDirectory($base_path . $t['base_name']);
            }

            // Create the folder for storing the output (job base_name with _output suffix)
            // from the job and store the name in the database
            $archive_folder = $t['base_name'] . '_output';
            $archive_folder_path = $base_path . $archive_folder;
            Log::debug('Creating archive folder: ' . $archive_folder_path);
            $old = umask(0);
            mkdir($archive_folder_path, 0770);
            umask($old);
            $job->input_folder = $archive_folder;
            $job->save();

            // refresh AGAVE token again, as downloads can take a long time.
            $agave = new Agave;
            $user = User::where('username', $this->gw_username)->first();
            if ($user == null) {
                throw new \Exception('User ' . $this->gw_username . ' could not be found in local database.');
            }
            $rt = $user->refresh_token;
            $r = $agave->renewToken($rt);
            $user->updateToken($r);
            $user->save();
            $this->token = $user->password;

            // submit AGAVE job
            $job->updateStatus('SENDING JOB TO AGAVE');

            $config = $agave->getJobConfig('irec-job-' . $this->jobId, $this->agaveAppId, $this->systemStaging, $this->notificationUrl, $archive_folder, $this->params, $inputs, $this->job_params);
            $response = $agave->createJob($this->token, $config);

            $job->agave_id = $response->result->id;
            $job->updateStatus('JOB ACCEPTED BY AGAVE. PENDING.');

            $localJob->setFinished();
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Log::error($e);
            $job = Job::find($this->jobId);
            $job->updateStatus('FAILED');

            $localJob = LocalJob::find($this->localJobId);
            $localJob->setFailed();
        }
    }

    /**
     * The job failed to process.
     *
     * @param  Exception  $exception
     * @return void
     */
    public function failed(Exception $exception)
    {
        // Print an error message
        Log::error($e->getMessage());
        Log::error($e);
        
        // Mark the job as failed.
        $job = Job::find($this->jobId);
        $job->updateStatus('FAILED');

        // Mark the local job as failed.
        $localJob = LocalJob::find($this->localJobId);
        $localJob->setFailed();
    }
}
