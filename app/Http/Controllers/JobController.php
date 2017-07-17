<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

use Illuminate\Http\Request;

use App\Agave;
use App\Job;
use App\JobStep;

class JobController extends Controller
{
    public function getIndex()
    {
        $job_list = Job::findJobsGroupByMonthForUser(auth()->user()->id);

        $data = array();
        $data['job_list_grouped_by_month'] = $job_list;
        
        $data['notification'] = session()->get('notification');
        
        return view('jobList', $data);
    }

    public function getJobData($job_id) {
        $job = Job::findJobForUser($job_id, auth()->user()->id);
        
        if($job == null)
        {
            return;
        }

        $data = array();
        
        $data['status'] = $job->status;
        $data['agave_status'] = $job->agave_status;
        $data['submission_date_relative'] = $job->createdAtRelative();

        $d = array();
        $d['job'] = $job;
        $data['progress'] = View::make('jobProgress', $d)->render();

        $d = array();
        $d['steps'] = JobStep::findAllForJob($job_id);
        $data['steps'] = View::make('jobSteps', $d)->render();

        $json = json_encode($data);
        return $json;      
    }

    public function getJobListGroupedByMonth() {
        $job_list = Job::findJobsGroupByMonthForUser(auth()->user()->id);

        $data = array();
        $data['job_list_grouped_by_month'] = $job_list;
        
        return view('jobListGroupedByMonth', $data);        
    }

    public function postLaunchApp(Request $request)
    {
        $f = $request->all();
        $token = auth()->user()->password;

        // create app
        $appId = intval($f['app_id']);
        $executionSystem = System::getCurrentSystem(auth()->user()->id);
        $username = $executionSystem->username;
        $appExecutionSystem = $executionSystem->name;
        $appDeploymentSystem = $systemDeploymentName = config('services.agave.system_deploy.name_prefix') . $username;
        $params = array();
        $inputs = array();
        $appHumanName = '';

        if($appId == 1)
        {   
            Log::info('1');         
            $appName = 'app-histogram--' . $executionSystem->name;
            $appDeploymentPath = 'histogram';
            $params['param1'] = 'cdr3_length';
            $inputs['file1'] = 'data.csv.zip';
            $appHumanName = 'Standard Histogram Generator';
        } 
        else if($appId == 2)
        {
            Log::info('2');         
            $appName = 'app-histogram2--' . $executionSystem->name;
            $appDeploymentPath = 'histogram2';
            $params['param1'] = 'cdr3_length';

            $colorStr = $f['color'];
            $colorArray = explode('_', $colorStr);

            $params['red'] = floatval($colorArray[0]);
            $params['green'] = floatval($colorArray[1]);
            $params['blue'] = floatval($colorArray[2]);
            $inputs['file1'] = 'data.csv.zip';
            $appHumanName = 'Amazing Historgram Generator';
        } 
        else if($appId == 3)
        {   
            Log::info('3');         
            $appName = 'app-nishanth01--' . $executionSystem->name;
            $appDeploymentPath = 'nishanth01';
            $inputs['file1'] = 'data.csv.zip';
            $appHumanName = 'Nishanth App 01';
        }

        $agave = new Agave;
        $config = $agave->getAppConfig($appId, $appName, $appExecutionSystem, $appDeploymentSystem, $appDeploymentPath);            
        $response = $agave->createApp($token, $config);
        $agaveAppId = $response->result->id;
        Log::info('app created: ' . $appId);

        //return;

        // create job in DB
        $job = new Job;
        $job->user_id = auth()->user()->id;
        $job->url = $f['data_url'];
        $job->app = $appHumanName;
        $job->save();
        $job->updateStatus('PENDING');

        // config parameters for the job
        $jobId = $job->id;
        $executionSystem = System::getCurrentSystem(auth()->user()->id);
        $tenant_url = config('services.agave.tenant_url');
        $systemStaging = config('services.agave.system_staging.name_prefix') . $username;
        $notificationUrl = config('services.agave.gw_notification_url');
        $gw_username = auth()->user()->username;

        $lj = new LocalJob;
        $lj->description = 'Job ' . $jobId . ' (data federation + submission to AGAVE)';
        // $lg->user = auth()->user()->username;
        // $lg->job_id = $jobId;
        $lj->save();
        $localJobId = $lj->id;

        Queue::push(function($j) use ($jobId, $f, $tenant_url, $token, $username, $systemStaging, $notificationUrl, $agaveAppId, $gw_username, $params, $inputs, $localJobId)
        {
            try {
                $localJob = LocalJob::find($localJobId);
                $localJob->setRunning();

                // find job in DB
                $job = Job::find($jobId);

                // generate csv file
                $job->updateStatus('FEDERATING DATA');
                Log::info('$f[filters_json]' . $f['filters_json']);
                $filters = json_decode($f['filters_json'], true);
                $csvFilePath = RestService::sequencesCSV($filters, $gw_username);  
                $folder = dirname($csvFilePath);
                $folder = str_replace('/data/', '', $folder);

                Log::info('folder=' . $folder);
                $job->input_folder = $folder;
                $job->save();
                
                // update input paths for AGAVE job
                foreach ($inputs as $key => $value) {
                    $inputs[$key] = 'agave://' . $systemStaging. '/' . $folder . '/' . $value;
                }

                // submit AGAVE job
                $job->updateStatus('SENDING JOB TO AGAVE');

                $agave = new Agave;
                $config = $agave->getJobConfig('irec-job-' . $jobId, $agaveAppId, $systemStaging, $notificationUrl, $folder, $params, $inputs);
                $response = $agave->createJob($token, $config);

                $job->agave_id = $response->result->id;
                $job->updateStatus('JOB ACCEPTED BY AGAVE. PENDING.');

                // remove job from Laravel queue
                $j->delete();

                $localJob->setFinished();
            } catch (Exception $e) {                
                Log::error($e->getMessage());
                Log::error($e);
                $job->updateStatus('FAILED');

                // remove job from Laravel queue
                $j->delete(); 

                $localJob = LocalJob::find($localJobId);
                $localJob->setFailed();
            }

        });

        return redirect('jobs/view/' . $jobId);
    }

    public function getView($id)
    {
        $job = Job::findJobForUser($id, auth()->user()->id);
        //$job = Job::where('id', '=', $id)->first();
        if($job == null)
        {
            return App::abort(401, 'Not authorized.');
        }
        
        $data = array();
        $data['job'] = $job;
        
        $data['files'] = array();
        if($job['input_folder'] != '') {
            $folder = 'data/' . $job['input_folder'];         
            if(File::exists($folder)) {
                $data['files'] = File::allFiles($folder);        
                $data['filesHTML'] = dir_to_html($folder);          
            }
        }

        $data['steps'] = JobStep::findAllForJob($id);

        return view('jobView', $data);
    }

    public function getAgaveHistory($id)
    {
        $job = Job::where('id', '=', $id)->first();
        if($job != null && $job->agave_id != '')
        {
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

        if ($job != null)
        {
            // delete job files
            if($job['input_folder']) { // IMPORTANT: this "if" prevents accidental deletion of ALL jobs data
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
