<?php

namespace App\Jobs;

use App\LocalJob;
use App\Sequence;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class DownloadSequences implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // number of times the job may be attempted.
    public $tries = 1;

    protected $username;
    protected $localJobId;
    protected $filters;
    protected $url;
    protected $sample_filters;

    // create job instance
    public function __construct($username, $localJobId, $filters, $url, $sample_filters)
    {
        $this->username = $username;
        $this->localJobId = $localJobId;
        $this->filters = $filters;
        $this->url = $url;
        $this->sample_filters = $sample_filters;
    }

    // execute job
    public function handle()
    {
        $localJob = LocalJob::find($this->localJobId);
        $localJob->setRunning();

        try {
            $t = Sequence::sequencesTSV($this->filters, $this->username, $this->url, $this->sample_filters);
            $tsvFilePath = $t['public_path'];
            Log::debug('Download was sucessful: ' . $tsvFilePath);
            
            // TODO send notification email

            $localJob->setFinished();
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Log::error($e);

            // TODO send notification email

            $localJob->setFailed();
        }
    }
}
