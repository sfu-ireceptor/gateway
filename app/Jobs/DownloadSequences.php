<?php

namespace App\Jobs;

use App\Cell;
use App\Clones;
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
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

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
    protected $type;

    // create job instance
    public function __construct($username, $localJobId, $query_id, $url, $nb_sequences, $download, $type)
    {
        $this->username = $username;
        $this->localJobId = $localJobId;
        $this->query_id = $query_id;
        $this->url = $url;
        $this->download = $download;
        $this->nb_sequences = $nb_sequences;
        $this->type = $type;
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

        if ($this->type == 'sequence') {
            $t = Sequence::sequencesTSV($filters, $this->username, $this->url, $sample_filter_fields);
        } elseif ($this->type == 'clone') {
            $t = Clones::clonesTSV($filters, $this->username, $this->url, $sample_filter_fields);
        } elseif ($this->type == 'cell') {
            $t = Cell::cellsTSV($filters, $this->username, $this->url, $sample_filter_fields);
        }

        //$file_path = $t['public_path'];
        //$this->download->file_url = $file_path;
        $this->download->file_url = $t['system_path'];
        $this->download->file_size = filesize($t['system_path']);

        $this->download->incomplete = false;
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
        if ($user != null && $user->email != '') {
            $email = $user->email;

            $date_str = $this->download->createdAtShort();

            $t = [];
            $t['page_url'] = config('app.url') . $this->download->page_url;
            $t['file_url'] = config('app.url') . '/' . $this->download->file_url;
            $t['download_page_url'] = config('app.url') . '/downloads';
            $t['download_days_available'] = self::DAYS_AVAILABLE;
            $t['date_str'] = $date_str;

            // Send a notficiation email, catch errors of the email can't be delivered.
            try {
                Log::debug('DownloadSequences::handle - Sending user download completed email');
                Mail::send(['text' => 'emails.download_successful'], $t, function ($message) use ($email, $date_str) {
                    $message->to($email)->subject('[iReceptor] Your download from ' . $date_str . ' is ready');
                });
            } catch (\Exception $e) {
                Log::error('DownloadSequences::handle - User email delivery failed');
                Log::error('DownloadSequences::handle - ' . $e->getMessage());
            }
        } else {
            Log::error('Error email not send. Could not find email for user ' . $this->username);
        }

        if ($this->download->incomplete) {
            // email notification to iReceptor support
            if (App::environment() == 'production') {
                $username = $this->username;

                $t = [];
                $t['username'] = $username;
                $t['error_message'] = 'Incomplete download';
                $t['user_query_admin_page_url'] = config('app.url') . '/admin/queries/' . $query_log_id;

                // Send support a notficiation email, catch errors of the email can't be delivered.
                try {
                    Log::debug('DownloadSequences::handle - Sending support download failed email');
                    Mail::send(['text' => 'emails.data_query_error'], $t, function ($message) use ($username) {
                        $message->to(config('ireceptor.email_support'))->subject('Gateway Download Incomplete for ' . $username);
                    });
                } catch (\Exception $e) {
                    Log::error('DownloadSequences::handle - Support email delivery failed');
                    Log::error('DownloadSequences::handle - ' . $e->getMessage());
                }
            }
        }

        $localJob->setFinished();
        QueryLog::end_job($query_log_id);
    }

    public function failed(Throwable $e)
    {
        Log::error($e->getMessage());
        Log::error($e);

        $localJob = LocalJob::find($this->localJobId);
        $localJob->setFailed();

        $this->download->setFailed();
        $this->download->end_date = Carbon::now();
        $this->download->save();

        // email notification to user
        $user = User::where('username', $this->username)->first();
        if ($user != null && $user->email != '') {
            $email = $user->email;

            $t = [];
            $t['page_url'] = config('app.url') . $this->download->page_url;
            $t['download_page_url'] = config('app.url') . '/downloads';
            $t['support_email'] = config('ireceptor.email_support');

            // Send user a notficiation email, catch errors if the email can't be delivered.
            try {
                Log::debug('DownloadSequences::failed - Sending user download failed email');
                Mail::send(['text' => 'emails.download_failed'], $t, function ($message) use ($email) {
                    $message->to($email)->subject('[iReceptor] Download error');
                });
            } catch (\Exception $e) {
                Log::error('DownloadSequences::failed - User email delivery failed');
                Log::error('DownloadSequences::failed - ' . $e->getMessage());
            }
        } else {
            Log::error('Error email not send. Could not find email for user ' . $this->username);
        }

        $error_message = $e->getMessage();
        $query_log_id = QueryLog::get_query_log_id();

        // email notification to iReceptor support
        if (App::environment() == 'production') {
            $username = $this->username;

            $t = [];
            $t['username'] = $username;
            $t['error_message'] = $error_message;
            $t['user_query_admin_page_url'] = config('app.url') . '/admin/queries/' . $query_log_id;

            // Send support a notficiation email, catch errors if the email can't be delivered.
            try {
                Log::debug('DownloadSequences::failed - Sending download failed support email');

                Mail::send(['text' => 'emails.data_query_error'], $t, function ($message) use ($username) {
                    $message->to(config('ireceptor.email_support'))->subject('Gateway Download Error for ' . $username);
                });
            } catch (\Exception $e) {
                Log::error('DownloadSequences::failed - Support email delivery failed');
                Log::error('DownloadSequences::failed - ' . $e->getMessage());
            }
        }
        QueryLog::end_job($query_log_id, 'error', $error_message);
    }
}
