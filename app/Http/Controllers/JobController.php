<?php

namespace App\Http\Controllers;

use App\Job;
use App\Agave;
use App\System;
use App\JobStep;
use App\LocalJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use App\Jobs\PrepareDataForThirdPartyAnalysis;
use App\Jobs\LaunchAgaveJob;

class JobController extends Controller
{
    public function getIndex()
    {
        $job_list = Job::findJobsGroupByMonthForUser(auth()->user()->id);

        $data = [];
        $data['job_list_grouped_by_month'] = $job_list;

        $data['notification'] = session()->get('notification');

        return view('job/list', $data);
    }

    public function getJobData($job_id)
    {
        $job = Job::findJobForUser($job_id, auth()->user()->id);

        if ($job == null) {
            return;
        }

        $data = [];

        $data['status'] = $job->status;
        $data['agave_status'] = $job->agave_status;
        $data['submission_date_relative'] = $job->createdAtRelative();

        $d = [];
        $d['job'] = $job;
        $data['progress'] = view()->make('job/progress', $d)->render();

        $d = [];
        $d['steps'] = JobStep::findAllForJob($job_id);
        $data['steps'] = view()->make('job/steps', $d)->render();

        $json = json_encode($data);

        return $json;
    }

    public function getJobListGroupedByMonth()
    {
        $job_list = Job::findJobsGroupByMonthForUser(auth()->user()->id);

        $data = [];
        $data['job_list_grouped_by_month'] = $job_list;

        return view('job/listGroupedByMonth', $data);
    }

    public function postLaunchApp(Request $request)
    {
        $f = $request->all();
        $token = auth()->user()->password;

        $appId = intval($f['app_id']);
        $gw_username = auth()->user()->username;

        // 3rd-party analysis
        if ($appId == 999) {
            Log::info('999');
            $appHumanName = 'Third-party analysis';
            $jobDescription = 'Data federation';
        }

        else {
            // create Agave app
            $executionSystem = System::getCurrentSystem(auth()->user()->id);
            $username = $executionSystem->username;
            $appExecutionSystem = $executionSystem->name;
            $appDeploymentSystem = $systemDeploymentName = config('services.agave.system_deploy.name_prefix') . $username;
            $params = [];
            $inputs = [];
            $appHumanName = '';
            $jobDescription = 'Data federation + submission to AGAVE';

            if ($appId == 1) {
                Log::info('1');
                $appName = 'app-histogram--' . $executionSystem->name;
                $appDeploymentPath = 'histogram';
                $params['param1'] = 'junction_nt_length';
                // $inputs['file1'] = 'data.tsv.zip';
                $appHumanName = 'Standard Histogram Generator';
            } elseif ($appId == 2) {
                Log::info('2');
                $appName = 'app-histogram2--' . $executionSystem->name;
                $appDeploymentPath = 'histogram2';
                $params['param1'] = 'junction_nt_length';

                $colorStr = $f['color'];
                $colorArray = explode('_', $colorStr);

                $params['red'] = floatval($colorArray[0]);
                $params['green'] = floatval($colorArray[1]);
                $params['blue'] = floatval($colorArray[2]);
                // $inputs['file1'] = 'data.tsv.zip';
                $appHumanName = 'Amazing Historgram Generator';
            } elseif ($appId == 3) {
                Log::info('3');
                $appName = 'app-nishanth01--' . $executionSystem->name;
                $appDeploymentPath = 'nishanth01';
                // $inputs['file1'] = 'data.tsv.zip';
                $appHumanName = 'Nishanth App 01';
            }

            $agave = new Agave;
            $config = $agave->getAppConfig($appId, $appName, $appExecutionSystem, $appDeploymentSystem, $appDeploymentPath);
            $response = $agave->createApp($token, $config);
            $agaveAppId = $response->result->id;
            Log::info('app created: ' . $appId);

            // config parameters for the job
            $executionSystem = System::getCurrentSystem(auth()->user()->id);
            $tenant_url = config('services.agave.tenant_url');
            $systemStaging = config('services.agave.system_staging.name_prefix') . $username;
            $notificationUrl = config('services.agave.gw_notification_url');
        }

        // create job in DB
        $job = new Job;
        $job->user_id = auth()->user()->id;
        $job->url = $f['data_url'];
        $job->app = $appHumanName;
        $job->save();
        $job->updateStatus('WAITING');
        $jobId = $job->id;

        $lj = new LocalJob;
        $lj->description = 'Job ' . $jobId . ' (' . $jobDescription . ')';
        // $lg->user = auth()->user()->username;
        // $lg->job_id = $jobId;
        $lj->save();
        $localJobId = $lj->id;

        // queue job
        if ($appId == 999) {
            PrepareDataForThirdPartyAnalysis::dispatch($jobId, $f, $gw_username, $localJobId);            
        }
        else {
            LaunchAgaveJob::dispatch($jobId, $f, $tenant_url, $token, $username, $systemStaging, $notificationUrl, $agaveAppId, $gw_username, $params, $inputs, $localJobId);            
        }

        return redirect('jobs/view/' . $jobId);
    }

    public function getView($id)
    {
        $job = Job::findJobForUser($id, auth()->user()->id);
        //$job = Job::where('id', '=', $id)->first();
        if ($job == null) {
            return App::abort(401, 'Not authorized.');
        }

        $data = [];
        $data['job'] = $job;

        $data['files'] = [];
        if ($job['input_folder'] != '') {
            $folder = 'storage/' . $job['input_folder'];
            if (File::exists($folder)) {
                $data['files'] = File::allFiles($folder);
                $data['filesHTML'] = dir_to_html($folder);
            }
        }

        $data['steps'] = JobStep::findAllForJob($id);

        return view('job/view', $data);
    }

    public function getAgaveHistory($id)
    {
        $job = Job::where('id', '=', $id)->first();
        if ($job != null && $job->agave_id != '') {
            $job_agave_id = $job->agave_id;
            $token = auth()->user()->password;

            $agave = new Agave;
            $response = $agave->getJobHistory($job_agave_id, $token);
            echo '<pre>' . $response . '</pre>';
        }
    }

    // ajax-called from view page
    public function getStatus($id)
    {
        $job = Job::where('id', '=', $id)->first();
        echo $job->agave_status;
    }

    public function getDelete($id)
    {
        $userId = auth()->user()->id;
        $job = Job::get($id, $userId);

        if ($job != null) {
            // delete job files
            if ($job['input_folder']) { // IMPORTANT: this "if" prevents accidental deletion of ALL jobs data
                $dataFolder = 'data/' . $job['input_folder'];
                File::deleteDirectory($dataFolder);
            }

            // delete job history
            $jobSteps = JobStep::findAllForJob($job->id);
            foreach ($jobSteps as $step) {
                $step->delete();
            }

            // delete job
            $job->delete();

            return redirect('jobs')->with('notification', 'The job <strong>' . $job->id . '</strong> was successfully deleted.');
        }

        return redirect('jobs');
    }
}
