<?php

namespace App\Jobs;

use App\Agave;
use App\Job;
use App\LocalJob;
use App\Sequence;
use App\System;
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
    protected $inputs;
    protected $localJobId;

    // create job instance
    public function __construct($jobId, $f, $tenant_url, $token, $username, $systemStaging, $notificationUrl, $agaveAppId, $gw_username, $params, $inputs, $localJobId)
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
            Log::info('$f[filters_json]' . $this->f['filters_json']);
            $filters = json_decode($this->f['filters_json'], true);
            $t = Sequence::sequencesTSV($filters, $this->gw_username);
            $dataFilePath = $t['public_path'];

            // Log::debug('$dataFilePath=' . $dataFilePath );

            // $folder = dirname($dataFilePath);
            // $folder = str_replace('/data/', '', $folder);

            // Log::info('folder=' . $folder);
            // $job->input_folder = $folder;
            // $job->save();

            // update input paths for AGAVE job
            // foreach ($this->inputs as $key => $value) {
            //     $inputs[$key] = 'agave://' . $this->systemStaging . '/' . $folder . '/' . $value;
            // }
            Log::debug('#### HELLLOOOOOO');
            foreach ($this->inputs as $key => $value) {
                Log::debug('#### input1 ' . $key . ' = ' . $value);
            }

            $inputs['file1'] = 'agave://' . $this->systemStaging . '/' . basename($dataFilePath);
            $executionSystem = System::getCurrentSystem($this->gw_username);
            Log::debug('#### user = ' . auth()->user());
            Log::debug('#### executionSystem = ' . $executionSystem);

            $inputs['singularity'] = 'agave://' . $executionSystem . '/singularity/vdjbase_pipeline-1.1.01.sif';
            foreach ($inputs as $key => $value) {
                Log::debug('#### input2 ' . $key . ' = ' . $value);
            }

            $storage_folder_path = storage_path() . '/app/public/';
            $archive_folder = basename($dataFilePath, '.zip') . '_output';
            $archive_folder_path = $storage_folder_path . $archive_folder;
            Log::debug('Creating archive folder: ' . $archive_folder_path);
            $old = umask(0);
            mkdir($archive_folder_path, 0777);
            umask($old);

            $job->input_folder = $archive_folder;
            $job->save();

            // submit AGAVE job
            $job->updateStatus('SENDING JOB TO AGAVE');

            $agave = new Agave;

            $config = $agave->getJobConfig('irec-job-' . $this->jobId, $this->agaveAppId, $this->systemStaging, $this->notificationUrl, $archive_folder, $this->params, $inputs);
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
