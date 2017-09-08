<?php

namespace App\Http\Controllers;

use App\Job;
use App\LocalJob;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Illuminate\Http\Request;

class UtilController extends Controller
{
    // called by AGAVE
    public function updateAgaveStatus($id, $status)
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

    // called by GitHub hook
    public function deploy(Request $request)
    {
        if ($request->header('X-Hub-Signature') == config('app.deploy_secret')) {
            Log::info('-------- Deployment START -------- ');

            $root_path = base_path();
            $process = new Process('cd ' . $root_path . '; ./deploy.sh');
            $process->run(function ($type, $buffer) {
                echo $buffer;
            });

            Log::info('-------- Deployment END --------');
        } else {
            Log::error('Deployment attempt failed because of deploy secret mismatch.');
        }
    }
}
