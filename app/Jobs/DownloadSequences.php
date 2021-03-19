<?php

namespace App\Jobs;

use App\Download;
use App\LocalJob;
use App\Query;
use App\QueryLog;
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

    const DAYS_AVAILABLE = 5;

    // number of times the job may be attempted.
    public $tries = 1;

    protected $username;
    protected $localJobId;
    protected $url;
    protected $download;
    protected $nb_sequences;
    protected $query_id;

    // create job instance
    public function __construct($username, $localJobId, $query_id, $url, $nb_sequences, $download)
    {
        $this->username = $username;
        $this->localJobId = $localJobId;
        $this->query_id = $query_id;
        $this->url = $url;
        $this->download = $download;
        $this->nb_sequences = $nb_sequences;
    }

    // execute job
    public function handle()
    {
        $filters = Query::getParams($this->query_id);

        // get sample filters
        $sample_filter_fields = [];
        if (isset($filters['sample_query_id'])) {
            $sample_query_id = $filters['sample_query_id'];

            // sample filters
            $sample_filters = Query::getParams($sample_query_id);
            $sample_filter_fields = [];
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

        $query_log_id = QueryLog::start_job($this->url, $filters, $this->nb_sequences, $this->username);

        $localJob = LocalJob::find($this->localJobId);
        $localJob->setRunning();

        // if download was canceled, don't do anything
        if ($this->download->isCanceled()) {
            $localJob->setFinished();

            return;
        }

        $this->download->setRunning();
        $this->download->start_date = Carbon::now();
        $this->download->query_log_id = $query_log_id;
        $this->download->save();

        $t = Sequence::sequencesTSV($filters, $this->username, $this->url, $sample_filter_fields);
        $file_path = $t['public_path'];
        $this->download->file_url = $file_path;

        if ($t['is_download_incomplete']) {
            $this->download->incomplete = true;
            $this->download->incomplete_info = $t['download_incomplete_info'];
            $this->download->save();
        }

        $this->download->setDone();
        $this->download->end_date = Carbon::now();
        $this->download->file_url_expiration = Carbon::now()->addDays(self::DAYS_AVAILABLE);

        $this->download->save();

        // send notification email
        $user = User::where('username', $this->username)->first();
        $email = $user->email;
        $date_str = $this->download->createdAtShort();

        $t = [];
        $t['page_url'] = config('app.url') . $this->download->page_url;
        $t['file_url'] = config('app.url') . '/' . $this->download->file_url;
        $t['download_page_url'] = config('app.url') . '/downloads';
        $t['download_days_available'] = self::DAYS_AVAILABLE;
        $t['date_str'] = $date_str;

        Mail::send(['text' => 'emails.download_successful'], $t, function ($message) use ($email, $date_str) {
            $message->to($email)->subject('[iReceptor] Your download from ' . $date_str . ' is ready');
        });

        $localJob->setFinished();

        QueryLog::end_job($query_log_id);
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
        $user = User::where('username', $this->username)->first();
        $email = $user->email;

        $t = [];
        $t['page_url'] = config('app.url') . $this->download->page_url;
        $t['download_page_url'] = config('app.url') . '/downloads';
        $t['support_email'] = config('ireceptor.email_support');

        Mail::send(['text' => 'emails.download_failed'], $t, function ($message) use ($email) {
            $message->to($email)->subject('[iReceptor] Download error');
        });

        $error_message = $e->getMessage();
        $query_log_id = QueryLog::get_query_log_id();
        QueryLog::end_job($query_log_id, 'error', $error_message);
    }
}
