<?php

namespace App\Http\Controllers;

use App\Deployment;
use App\Jobs\ProcessJobNotification;
use App\LocalJob;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class UtilController extends Controller
{
    // URL Controller function for receiving updates from Tapis
    public function updateJobStatus(Request $request)
    {
        //Log::info('Tapis job status update: job ' . json_encode($request));
        $content = json_decode($request->getContent());
        //Log::info('Tapis job status update: content = ' . json_encode($content));
        $event = $content->event;
        $id = $content->event->subject;
        $data = json_decode($content->event->data);
        if (property_exists($data, 'newJobStatus')) {
            $status = $data->newJobStatus;
            Log::info('Tapis job status update: job ' . $id . ' has status ' . $status);
            $lj = new LocalJob('agave-notifications');

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
            ProcessJobNotification::dispatch($id, $status, $localJobId)->onQueue('agave-notifications');
        } else {
            Log::info('updateJobStatus: Got notification, ignoring: ' . $data->message);
        }
    }

    // called by GitHub hook
    public function deploy(Request $request)
    {
        Log::debug('UtilController::deploy');
        $already_running_deployment = Deployment::where('running', 1)->first();
        while ($already_running_deployment != null) {
            sleep(5);
            $already_running_deployment = Deployment::where('running', 1)->first();
        }
        Log::debug('UtilController::deploy - after checking for running');

        $start_time = Carbon::now();

        $deployment = new Deployment;
        $deployment->save();

        $githubPayload = $request->getContent();
        $githubHash = $request->header('X-Hub-Signature');

        $localToken = config('app.deploy_secret');
        Log::debug('UtilController::deploy - local secret = ' . $localToken);
        $localHash = 'sha1=' . hash_hmac('sha1', $githubPayload, $localToken, false);
        Log::debug('UtilController::deploy - githubhash = ' . $githubHash);
        Log::debug('UtilController::deploy - localhash = ' . $localHash);

        // Get the payload from the request, convert it to an object, and extract
        // the payload branch that the push request was on.
        $payload_json = $request->input('payload');
        $payload_obj = json_decode($payload_json);
        //Log::debug('Type is: ' . gettype($payload_json));
        //Log::debug('githubPayload=' . json_encode($payload_obj, JSON_PRETTY_PRINT));

        //  Get the branch that the payload says the commit happened on.
        $payloadRef = $payload_obj->ref;
        Log::debug('UtilController::deploy - Payload ref = ' . $payloadRef);

        // Get the branch that we are supposed to deploy on from the configuration.
        $localRef = config('app.deploy_branch');
        Log::debug('UtilController::deploy - Local ref = ' . $localRef);

        Log::info('-------- Deployment STARTED --------');
        // If the payload hashes are the same and the branches are the same
        // then we deploy, otherwise we ignore the github webhook callback.
        if (hash_equals($githubHash, $localHash) && $payloadRef == $localRef) {
            $root_path = base_path();
            $process = new Process(['./util/scripts/deploy.sh']);
            $process->setWorkingDirectory($root_path);
            $process->setTimeout(180);

            $process->run(function ($type, $buffer) {
                echo $buffer;
                Log::info($buffer);
            });
        } else {
            Log::info('Deployment not performed - hash/branch not correct for this gateway.');
            Log::info('githubHash = ' . $githubHash);
            Log::info('localHash  = ' . $localHash);
            Log::info('localToken = ' . $localToken);
        }
        Log::info('-------- Deployment FINISHED --------');

        $deployment->running = false;
        $deployment->save();

        $end_time = Carbon::now();
        $duration = $end_time->diffForHumans($start_time);
        Log::info('Deployment duration: ' . $duration);
    }
}
