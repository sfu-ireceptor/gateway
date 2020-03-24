<?php

namespace App\Jobs;

use App\Job;
use App\LocalJob;
use App\Sample;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CountSequences implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // number of times the job may be attempted.
    public $tries = 2;

    protected $username;
    protected $rest_service_id;
    protected $localJobId;

    // create job instance
    public function __construct($username, $rest_service_id, $localJobId)
    {
        $this->username = $username;
        $this->rest_service_id = $rest_service_id;
        $this->localJobId = $localJobId;
    }

    // execute job
    public function handle()
    {
        $localJob = LocalJob::find($this->localJobId);
        $localJob->setRunning();

        try {
			Sample::cache_sequence_counts($this->username, $this->rest_service_id);
			$localJob->setFinished();
		} catch (Exception $e) {
		        Log::error($e->getMessage());
		        Log::error($e);

		        $localJob->setFailed();
		    }
		}
}
