<?php

namespace App\Http\Controllers;

use App\Deployment;
use App\Job;
use App\Jobs\ProcessAgaveNotification;
use App\LocalJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class UtilController extends Controller
{
    // called by AGAVE
    public function updateAgaveStatus($id, $status)
    {
        Log::info('AGAVE job status update: job ' . $id . ' has status ' . $status);

        $lj = new LocalJob('agave');
        $lj->user = '[Agave]';
        $lj->description = 'Job ' . $id . ': ' . $status;
        $lj->save();

        // ignore this status because it happens at the same time as FINISHED
        if ($status == 'ARCHIVING_FINISHED') {
            $lj->setFinished();

            return;
        }

        $localJobId = $lj->id;

        // queue as a job (to make sure notifications are processed in order)
        ProcessAgaveNotification::dispatch($id, $status, $localJobId)->onQueue('agave-notifications');
    }

    // called by GitHub hook
    public function deploy(Request $request)
    {
        $already_running_deployment = Deployment::where('running', 1)->first();
        while ($already_running_deployment != null) {
            sleep(5);
            $already_running_deployment = Deployment::where('running', 1)->first();
        }

        $deployment = new Deployment;
        $deployment->save();

        $githubPayload = $request->getContent();
        $githubHash = $request->header('X-Hub-Signature');

        $localToken = config('app.deploy_secret');
        $localHash = 'sha1=' . hash_hmac('sha1', $githubPayload, $localToken, false);

        if (hash_equals($githubHash, $localHash)) {
            Log::info('-------- Deployment STARTED --------');

            $root_path = base_path();
            $process = new Process('cd ' . $root_path . '; ./util/scripts/deploy.sh');
            $process->run(function ($type, $buffer) {
                echo $buffer;
                Log::info($buffer);
            });

            Log::info('-------- Deployment FINISHED --------');
        } else {
            Log::error('Deployment attempt failed because of hash mismatch.');
            Log::info('$githubHash =' . $githubHash);
            Log::info('$localHash  =' . $localHash);
            Log::info('$localToken =' . $localToken);
            Log::info('$githubPayload=' . $githubPayload);
            var_dump($request->header());
        }

        $deployment->running = false;
        $deployment->save();
    }
}
