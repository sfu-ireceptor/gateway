<?php

namespace App\Jobs;

use App\Download;
use App\LocalJob;
use App\Sequence;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
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
    protected $download;
    protected $nb_sequences;

    // create job instance
    public function __construct($username, $localJobId, $filters, $url, $sample_filters, $nb_sequences)
    {
        // create new Download object
        $d = new Download();
        $d->username = $username;
        $d->setQueued();
        $d->page_url = $url;
        $d->nb_sequences = $nb_sequences;
        $d->save();

        // initialize this job
        $this->username = $username;
        $this->localJobId = $localJobId;
        $this->filters = $filters;
        $this->url = $url;
        $this->sample_filters = $sample_filters;
        $this->download = $d;
    }

    // execute job
    public function handle()
    {
        $localJob = LocalJob::find($this->localJobId);
        $localJob->setRunning();

        // if download was canceled, don't do anything
        if ($this->download->isCanceled()) {
            $localJob->setFinished();

            return;
        }

        try {
            $this->download->setRunning();
            $this->download->save();

            $t = Sequence::sequencesTSV($this->filters, $this->username, $this->url, $this->sample_filters);
            $tsvFilePath = $t['public_path'];
            Log::debug('Download was sucessful: ' . $tsvFilePath);

            // TODO send notification email

            $this->download->setDone();
            $this->download->save();

            $localJob->setFinished();
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Log::error($e);

            // TODO send notification email

            $this->download->setFailed();
            $this->download->save();

            $localJob->setFailed();
        }
    }
}
