<?php

namespace App\Jobs;

use App\Agave;
use App\Job;
use App\LocalJob;
use App\Query;
use App\Sequence;
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
    protected $f;
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

    // create job instance
    public function __construct($jobId, $f, $tenant_url, $token, $username, $systemStaging, $notificationUrl, $agaveAppId, $gw_username, $params, $inputs, $job_params, $localJobId)
    {
        $this->jobId = $jobId;
        $this->f = $f;
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
    }

    // execute job
    public function handle()
    {
        try {
            $localJob = LocalJob::find($this->localJobId);
            $localJob->setRunning();

            // find job in DB
            $job = Job::find($this->jobId);

            // generate csv file
            $job->updateStatus('FEDERATING DATA');
            Log::info('########## $this->f = ' . json_encode($this->f));
            Log::info('$f[filters_json]' . $this->f['filters_json']);
            $filters = json_decode($this->f['filters_json'], true);

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

            // Generated the file
            $t = Sequence::sequencesTSV($filters, $this->gw_username, $job->url, $sample_filter_fields);
            $dataFilePath = $t['public_path'];

            // The Gateway sets the download_file input as it controls the data
            // that is processed by the application.
            $inputs['download_file'] = 'agave://' . $this->systemStaging . '/' . basename($dataFilePath);
            foreach ($inputs as $key => $value) {
                Log::debug('Job input ' . $key . ' = ' . $value);
            }

            $executionSystem = System::getCurrentSystem($this->gw_username);

            $storage_folder_path = storage_path() . '/app/public/';
            $archive_folder = basename($dataFilePath, '.zip') . '_output';
            $archive_folder_path = $storage_folder_path . $archive_folder;
            Log::debug('Creating archive folder: ' . $archive_folder_path);
            $old = umask(0);
            mkdir($archive_folder_path, 0777);
            umask($old);

            $job->input_folder = $archive_folder;
            $job->save();

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
            $job->updateStatus('FAILED');

            $localJob = LocalJob::find($localJobId);
            $localJob->setFailed();
        }
    }
}
