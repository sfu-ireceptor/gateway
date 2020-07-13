<?php

namespace App\Jobs;

use App\Download;
use App\Agave;
use App\LocalJob;
use App\Sequence;
use App\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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

        $this->download->setRunning();
        $this->download->start_date = Carbon::now();
        $this->download->save();

        $t = Sequence::sequencesTSV($this->filters, $this->username, $this->url, $this->sample_filters);
        $file_path = $t['public_path'];
        $this->download->file_url = $file_path;

        $this->download->setDone();
        $this->download->end_date = Carbon::now();
        $this->download->save();

        // send notification email
        $agave = new Agave;
        $token = $agave->getAdminToken();
        $user = $agave->getUserWithUsername($this->username, $token);
        $email = $user->email;

        $t = [];
        $t['page_url'] = config('app.url') . $this->download->page_url;
        $t['file_url'] = config('app.url') . '/' . $this->download->file_url;
        $t['download_page_url'] = config('app.url') . '/downloads';

        Mail::send(['text' => 'emails.download_successful'], $t, function ($message) use ($email) {
            $message->to($email)->subject('Your download is ready');
        });

        $localJob->setFinished();
    }

    public function failed(\Exception $e)
    {
        Log::error($e->getMessage());
        Log::error($e);

        $localJob = LocalJob::find($this->localJobId);
        $localJob->setFailed();    
        
        $this->download->setFailed();
        $this->download->end_date = Carbon::now();
        $this->download->save();

        // send notification email
        $agave = new Agave;
        $token = $agave->getAdminToken();
        $user = $agave->getUserWithUsername($this->username, $token);
        $email = $user->email;

        $t = [];
        $t['page_url'] = config('app.url') . $this->download->page_url;
        $t['download_page_url'] = config('app.url') . '/downloads';
        $t['support_email'] = config('ireceptor.email_support');

        Mail::send(['text' => 'emails.download_failed'], $t, function ($message) use ($email) {
            $message->to($email)->subject('Download error');
        });
    }
}
