<?php

namespace App\Jobs;

use App\Job;
use App\LocalJob;
use App\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ProcessJobNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // number of times the job may be attempted.
    public $tries = 2;

    protected $id;
    protected $status;
    protected $localJobId;

    // create job instance
    public function __construct($id, $status, $localJobId)
    {
        $this->id = $id;
        $this->status = $status;
        $this->localJobId = $localJobId;
    }

    // execute job
    public function handle()
    {
        $localJob = LocalJob::find($this->localJobId);
        $localJob->setRunning();

        // save job status in DB
        $job = Job::where('agave_id', '=', $this->id)->first();

        // If the AGAVE job doesn't exist, ignore the update, we have
        // deleted the job already.
        if ($job == null) {
            $localJob->setFinished();

            return;
        }

        // Get the user and the date
        $user = User::where('id', $job->user_id)->first();
        $gateway_job_id = $job->id;
        $date_str = Carbon::now();
        Log::debug('ProcessJobNotification - status = ' . $this->status);

        // Set up the data we need to send an email message
        $t = [];
        $t['username'] = $user->username;
        $t['error_message'] = '';
        $t['job_id'] = $job->id;
        $t['job_url'] = config('app.url') . '/jobs/view/' . $job->id;
        $t['jobs_page_url'] = config('app.url') . '/jobs';
        $t['support_email'] = config('ireceptor.email_support');
        $t['date_str'] = $date_str;

        // If the status is CANCELLED the job has been halted by the user. We need to send an emal.
        // If new status is FINISHED and the old status isn't FINISHED (we are transitioning
        // to FINISHED) then send an email. Same for the transition to FAILED
        if ($this->status == 'CANCELLED') {
            // Send the user a STOPPED email if we can
            if ($user != null && $user->email != '') {
                $email = $user->email;
                Log::info('ProcessJobNotification - sending STOPPED email to ' . $user->username . '(' . $user->email . ')');
                // Send a notficiation email, catch errors if the email can't be delivered.
                try {
                    Log::debug('ProcessJobNotifications::handle - Sending job stopped email');
                    Mail::send(['text' => 'emails.job_stopped'], $t, function ($message) use ($email, $date_str, $gateway_job_id) {
                        $message->to($email)->subject('[iReceptor] Your job (Job ' . $gateway_job_id . ') was stopped at ' . $date_str);
                    });
                } catch (\Exception $e) {
                    Log::error('ProcessJobNotifications::handle - User email delivery failed');
                    Log::error('ProcessJobNotifications::handle - ' . $e->getMessage());
                }
            } else {
                Log::error('Error email not send. Could not find email for user ' . $user->username);
            }
        } elseif ($this->status == 'FAILED' && $job->agave_status != 'FAILED') {
            // Send the user a FAILED email if we can
            if ($user != null && $user->email != '') {
                $email = $user->email;
                Log::info('ProcessJobNotification - sending FAILED email to ' . $user->username . '(' . $user->email . ')');
                try {
                    Log::debug('ProcessJobNotifications::handle - Sending job failed email');
                    Mail::send(['text' => 'emails.job_failed'], $t, function ($message) use ($email, $date_str, $gateway_job_id) {
                        $message->to($email)->subject('[iReceptor] Your job (Job ' . $gateway_job_id . ') failed at ' . $date_str);
                    });
                } catch (\Exception $e) {
                    Log::error('ProcessJobNotifications::handle - User email delivery failed');
                    Log::error('ProcessJobNotifications::handle - ' . $e->getMessage());
                }
            } else {
                Log::error('Error email not send. Could not find email for user ' . $user->username);
            }

            // Send support a notficiation email, catch errors of the email can't be delivered.
            $username = $user->username;
            try {
                $t['error_message'] = 'Job Failed';
                Log::debug('ProcessJobNotifications::handle - Sending support job failed email');
                Mail::send(['text' => 'emails.job_support_error'], $t, function ($message) use ($username, $gateway_job_id) {
                    $message->to(config('ireceptor.email_support'))->subject('Gateway Job ' . $gateway_job_id . ' failed for ' . $username);
                });
            } catch (\Exception $e) {
                Log::error('ProcessJobNotifications::handle - Support email delivery failed');
                Log::error('ProcessJobNotifications::handle - ' . $e->getMessage());
            }
        } elseif ($this->status == 'FINISHED' && $job->agave_status != 'FINISHED') {
            // Send the user a FINISHED email if we can
            if ($user != null && $user->email != '') {
                $email = $user->email;
                Log::info('ProcessJobNotification - sending FINISHED email to ' . $user->username . '(' . $user->email . ')');

                // Send a notficiation email, catch errors if the email can't be delivered.
                try {
                    Log::debug('ProcessJobNotifications::handle - Sending job FINISHED email');
                    Mail::send(['text' => 'emails.job_successful'], $t, function ($message) use ($email, $date_str, $gateway_job_id) {
                        $message->to($email)->subject('[iReceptor] Your job (Job ' . $gateway_job_id . ') finished at ' . $date_str);
                    });
                } catch (\Exception $e) {
                    Log::error('ProcessJobNotifications::handle - User email delivery failed');
                    Log::error('ProcessJobNotifications::handle - ' . $e->getMessage());
                }
            } else {
                Log::error('Error email not send. Could not find email for user ' . $user->username);
            }
        }

        // ignore the status update if the job has already FAILED, FINISHED, or STOPPED
        if ($job->agave_status == 'FAILED' || $job->agave_status == 'FINISHED' ||
            $job->agave_status == 'STOPPED') {
            $localJob->setFinished();

            return;
        }

        $job->updateStatus($this->status);

        // $j->delete(); // remove job from Laravel queue
        $localJob->setFinished();
    }
}
