<?php

namespace App\Jobs;

use App\Job;
use App\Agave;
use App\LocalJob;
use App\Sequence;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;



class PrepareDataForThirdPartyAnalysis implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // number of times the job may be attempted.
    public $tries = 2;

    protected $jobId;
    protected $f;
    protected $username;
    protected $localJobId;

    // create job instance
    public function __construct($jobId, $f, $username, $localJobId)
    {
        $this->jobId = $jobId;
        $this->f = $f;
        $this->username = $username;
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
            $t = Sequence::sequencesTSVFolder($filters, $this->username);

            $job->input_folder = basename($t['folder_path']);
            $job->save();

            $job->updateStatus('FINISHED');
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
