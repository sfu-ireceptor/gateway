<?php

namespace App\Http\Controllers;

use App\Job;
use App\LocalJob;
use Illuminate\Support\Facades\Log;

class AgaveController extends Controller
{
    // called by AGAVE
    public function postUpdateStatus($id, $status)
    {
        Log::info('AGAVE job status update: job ' . $id . ' has status ' . $status);

        $lj = new LocalJob('agave');
        $lj->description = 'Job ' . $id . ': ' . $status;
        $lj->save();

        // ignore this status because it happens at the same time as FINISHED
        if ($status == 'ARCHIVING_FINISHED') {
            $lj->setFinished();

            return;
        }

        $localJobId = $lj->id;
        Queue::push(function ($j) use ($id, $status, $localJobId) {
            $localJob = LocalJob::find($localJobId);
            $localJob->setRunning();

            // save job status in DB
            $job = Job::where('agave_id', '=', $id)->first();

            // ignore the status update if the job has already FAILED or is FINISHED
            if ($job->agave_status == 'FAILED' || $job->agave_status == 'FINISHED') {
                $localJob->setFinished();

                return;
            }

            $job->updateStatus($status);

            $j->delete(); // remove job from Laravel queue
            $localJob->setFinished();
        }, null, 'agave');
    }
}
