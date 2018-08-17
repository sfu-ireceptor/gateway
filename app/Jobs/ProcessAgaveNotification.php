<?php

namespace App\Jobs;

use App\Job;
use App\LocalJob;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ProcessAgaveNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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

        // ignore the status update if the job has already FAILED or is FINISHED
        if ($job->agave_status == 'FAILED' || $job->agave_status == 'FINISHED') {
            $localJob->setFinished();

            return;
        }

        $job->updateStatus($this->status);

        // $j->delete(); // remove job from Laravel queue
        $localJob->setFinished();
    }
}
