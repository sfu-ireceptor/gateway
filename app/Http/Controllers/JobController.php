<?php

namespace App\Http\Controllers;

use App\Job;
use App\Jobs\LaunchJob;
use App\Jobs\PrepareDataForThirdPartyAnalysis;
use App\JobStep;
use App\LocalJob;
use App\Query;
use App\Tapis;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

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

        Log::debug('JobController::getJobData: job = ' . json_encode($job, JSON_PRETTY_PRINT));
        $data = [];

        // These variables are used to update the state of the web page. They
        // essentially map to <span> elements in the HTML and are updated through
        // the JS code in main.js - in the Jobs section.
        $data['status'] = $job->status;
        $data['agave_status'] = $job->getJobStatus();
        $data['submission_date_relative'] = $job->createdAtRelative();
        $data['run_time'] = $job->totalTime();
        $data['job_url'] = $job->url;
        $data['job'] = $job;

        // Create an Tapis object to work with. This is constant across all jobs.
        $tapis = new Tapis;
        // Build the job_summary block HTML
        $data['job_summary'] = [];
        $s = '<p><strong>App Parameters</strong></p>';
        $s .= '<p>';
        // Get the JSON from the Job, we need info from it.
        $param_count = 0;
        $job_json = $this->getJobJSON($job->id, $tapis);

        // If we have a JSON string for the Job, process the App parameters.
        if ($job_json != null) {
            // Get the Tapis job status and from it get the parameters.
            $job_status = json_decode($job_json);
            $app_parameters = json_decode($job_status->result->parameterSet)->appArgs;
            // For each parameter, add some text to the display string.
            foreach ($app_parameters as $param) {
                // Basic parameters have notes - special hidden parameters do not. So if
                // we don't have a notes['label'] field then we don't do anything.
                if (property_exists($param, 'notes') && json_decode($param->notes) != null &&
                    property_exists(json_decode($param->notes), 'label')) {
                    // Generate the parameters label and value.
                    $param_string = json_decode($param->notes)->label;
                    $param_value = $param->arg;
                    $s .= $param_string . ': ' . $param_value . '<br>';
                    $param_count++;
                }
            }
        }
        if ($param_count == 0) {
            $s .= 'None<br>';
        }
        $s .= '<p>';

        $s .= '<p><strong>Job Parameters</strong></p>';
        $s .= '<p>';
        $s .= 'Number of cores: ' . strval($tapis->processorsPerNode()) . '<br>';
        $s .= 'Maximum memory per node: ' . strval(round($tapis->memoryMBPerNode() / 1024, 1)) . ' GB<br>';
        $s .= 'Maximum run time: ' . strval(round($tapis->maxRunTimeMinutes() / 60, 1)) . ' hours<br>';
        $s .= '<p>';
        $data['job_summary'] = $s;

        // Build the job control button HTML. This is rendered by the blade,
        // and controls when the job control button is enabled or not.
        $data['job_control_button'] = [];
        $s = '';
        $s .= '<a href="/jobs/cancel/' . $job->id . '">';
        if ($job->getJobID() == '') {
            $s .= '<button type="button" class="btn btn-primary" aria-label="Cancel" disabled="disabled">';
        } else {
            $s .= '<button type="button" class="btn btn-primary" aria-label="Cancel">';
        }
        $s .= '<span class="glyphicon glyphicon-ban-circle" aria-hidden="true"></span> Cancel this job';
        $s .= '</button></a>';
        $data['job_control_button'] = explode('\n', $s);

        // Build the job progress info
        $d = [];
        $d['job'] = $job;
        $data['progress'] = view()->make('job/progress', $d)->render();

        // Build the job steps info
        $d = [];
        $d['steps'] = JobStep::findAllForJob($job_id);
        $data['steps'] = view()->make('job/steps', $d)->render();

        // Encode the data and return it.
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
        // Get the data for this request.
        $request_data = $request->all();
        Log::debug('JobController::PostLaunchApp - request_data = ' . json_encode($request_data));

        // Get the App ID for this request.
        Log::info('JobController::PostLaunchApp - app_id = ' . $request_data['app_id']);
        $appId = $request_data['app_id'];

        // 3rd-party analysis
        if ($appId == '999') {
            Log::info('999');
            $appHumanName = 'Third-party analysis';
            $jobDescription = 'Data federation';
        } else {
            $jobDescription = 'Data federation + submission to Tapis';
            // Get the App template for this appId
            $tapis = new Tapis;
            $app_info = $tapis->getAppTemplate($appId);
            // The 'config' attribute has the App configuration and in the
            // configuration, we want to use the application label as the
            // human readable description for this App.
            $app_config = $app_info['config'];
            $appHumanName = $app_config['description'];
            // We want to know if the job requires downloads
            if (array_key_exists('download', $app_info) && $app_info['download'] == 'FALSE') {
                Log::info('JobController::postLaunchApp - App does not require downloads');
                $download_data = false;
            } else {
                Log::info('JobController::postLaunchApp - App requires downloads');
                $download_data = true;
            }
        }

        // create job in DB
        $job = new Job;
        $job->user_id = auth()->user()->id;
        $job->url = $request_data['data_url'];
        $job->app = $appHumanName;
        $job->save();
        $job->updateStatus('WAITING');
        $jobId = $job->id;

        // Extract the query type, either sequence, clone, or cell
        // Queries look like this: https://gateway-analysis-dev.ireceptor.org/cells?query_id=9982
        $job_url = $job->url;
        $query_string = 'query_id=';
        $query_type = '';
        if (strpos($job_url, 'sequences?' . $query_string)) {
            $query_type = 'sequence';
        } elseif (strpos($job_url, 'clones?' . $query_string)) {
            $query_type = 'clone';
        } elseif (strpos($job_url, 'cells?' . $query_string)) {
            $query_type = 'cell';
        }
        Log::debug('JobController::LaunchApp - Job type = ' . $query_type);

        // Determine the queue to use
        $n_objects = $request->input('n_objects');
        $cell_large_download_limit = config('ireceptor.cell_large_download_limit');
        $clone_large_download_limit = config('ireceptor.clone_large_download_limit');
        $sequence_large_download_limit = config('ireceptor.sequence_large_download_limit');
        Log::debug('JobController::LaunchApp - Number of objects = ' . $n_objects);
        $queue = 'short-analysis-jobs';
        if ($download_data == false) {
            // If we don't download data, use the short queue. This should run quickly
            $queue = 'short-analysis-jobs';
        } elseif ($query_type == 'sequence' && $n_objects > $sequence_large_download_limit) {
            $queue = 'long-analysis-jobs';
        } elseif ($query_type == 'clone' && $n_objects > $clone_large_download_limit) {
            $queue = 'long-analysis-jobs';
        } elseif ($query_type == 'cell' && $n_objects > $cell_large_download_limit) {
            $queue = 'long-analysis-jobs';
        } else {
            $queue = 'short-analysis-jobs';
        }

        Log::debug('JobController::LaunchApp - Job queue = ' . $queue);

        // queue job
        $lj = new LocalJob($queue);
        $lj->user = auth()->user()->username;
        $lj->description = 'Job ' . $jobId . ' (' . $jobDescription . ')';
        $lj->save();
        $localJobId = $lj->id;

        // Get Gateway user info, as the Jobs don't have user state knowledge as they are
        // independent processes.
        $gw_username = auth()->user()->username;
        $gw_userid = auth()->user()->id;
        if ($appId == '999') {
            PrepareDataForThirdPartyAnalysis::dispatch($jobId, $request_data, $gw_username, $localJobId)->onQueue($queue);
        } else {
            LaunchJob::dispatch($jobId, $request_data, $localJobId, $gw_username, $gw_userid)->onQueue($queue);
        }

        return redirect('jobs/view/' . $jobId);
    }

    public function getDownloadAnalysis($id)
    {
        // Get the download folder (relative to storage_path())
        $download_folder = config('ireceptor.downloads_data_folder');

        // The analysis directory is shated between the Gateway and the Gateway Utilities
        // that are used by Gateway Apps. This MUST be the same across both environments.
        // Stored in a config on the Gateway, hardcoded in the Tapis Gateway Utilities code.
        $analysis_base = config('services.tapis.analysis_base_dir');

        // Get the job for the user.
        $job = Job::findJobForUser($id, auth()->user()->id);

        // If there isn't one, check to see if we are the admin user. If so, then
        // we can access the job info.
        if ($job == null) {
            $user = User::where('username', auth()->user()->username)->first();
            if ($user->isAdmin()) {
                $job = Job::where('id', '=', $id)->first();
            }
        }
        // If the job is still null, then we are not authorized.
        if ($job == null) {
            abort(401, 'Not authorized.');
        }

        // Get the folder where this job is stored.
        $folder = storage_path() . '/' . $download_folder . '/' . $job['input_folder'];
        Log::debug('JobController::getView: folder path = ' . $folder);
        Log::debug('JobController::getView: job[input_folder]= ' . $job['input_folder']);
        // If the job folder exists...
        if ($job['input_folder'] != '' && File::exists($folder)) {
            // If the ZIP file exists, download it...
            $zip_file = $folder . '/' . $analysis_base . '.zip';
            if (File::exists($zip_file)) {
                return response()->download($zip_file);
            }
        }

        return redirect('jobs/view/' . $id)->with('notification', 'Could not download analysis ouput.');
    }

    public function getDownloadOutput($id)
    {
        // Get the download folder (relative to storage_path())
        $download_folder = config('ireceptor.downloads_data_folder');

        // The analysis directory is shated between the Gateway and the Gateway Utilities
        // that are used by Gateway Apps. This MUST be the same across both environments.
        // Stored in a config on the Gateway, hardcoded in the Tapis Gateway Utilities code.
        $analysis_base = config('services.tapis.analysis_base_dir');

        // Get the job for the user.
        $job = Job::findJobForUser($id, auth()->user()->id);

        // If there isn't one, check to see if we are the admin user. If so, then
        // we can access the job info.
        if ($job == null) {
            $user = User::where('username', auth()->user()->username)->first();
            if ($user->isAdmin()) {
                $job = Job::where('id', '=', $id)->first();
            }
        }
        // If the job is still null, then we are not authorized.
        if ($job == null) {
            abort(401, 'Not authorized.');
        }

        // Get the outout file name.
        $output_file = 'ireceptor-' . $job['id'] . '.out';

        // Get the folder where this job is stored.
        $folder = storage_path() . '/' . $download_folder . '/' . $job['input_folder'];
        Log::debug('JobController::getView: folder path = ' . $folder);
        Log::debug('JobController::getView: job[input_folder]= ' . $job['input_folder']);
        // If the job folder exists...
        if ($job['input_folder'] != '' && File::exists($folder)) {
            // If the ZIP file exists, download it...
            $output_file_path = $folder . '/' . $analysis_base . '/' . $output_file;
            if (File::exists($output_file_path)) {
                // Gets the contents of the file as an array of strings.
                $file_str = file($output_file_path);
                // Prepare the data return to the view
                $data = [];
                $data['plain_file'] = implode('', $file_str);
                $data['job'] = $job;
                $data['title'] = 'Output Log';

                // Return the data to the view.
                return view('job/plain_file', $data);
            }
        }

        return redirect('jobs/view/' . $id)->with('notification', 'Could not access analysis ouput log.');
    }

    public function getDownloadError($id)
    {
        // Get the download folder (relative to storage_path())
        $download_folder = config('ireceptor.downloads_data_folder');

        // The analysis directory is shated between the Gateway and the Gateway Utilities
        // that are used by Gateway Apps. This MUST be the same across both environments.
        // Stored in a config on the Gateway, hardcoded in the Tapis Gateway Utilities code.
        $analysis_base = config('services.tapis.analysis_base_dir');

        // Get the job for the user.
        $job = Job::findJobForUser($id, auth()->user()->id);

        // If there isn't one, check to see if we are the admin user. If so, then
        // we can access the job info.
        if ($job == null) {
            $user = User::where('username', auth()->user()->username)->first();
            if ($user->isAdmin()) {
                $job = Job::where('id', '=', $id)->first();
            }
        }
        // If the job is still null, then we are not authorized.
        if ($job == null) {
            abort(401, 'Not authorized.');
        }

        // Get the outout file name.
        $error_file = 'ireceptor-' . $job['id'] . '.err';

        // Get the folder where this job is stored.
        $folder = storage_path() . '/' . $download_folder . '/' . $job['input_folder'];
        Log::debug('JobController::getView: folder path = ' . $folder);
        Log::debug('JobController::getView: job[input_folder]= ' . $job['input_folder']);
        // If the job folder exists...
        if ($job['input_folder'] != '' && File::exists($folder)) {
            // If the ZIP file exists, download it...
            $error_file_path = $folder . '/' . $analysis_base . '/' . $error_file;
            if (File::exists($error_file_path)) {
                // Gets the contents of the file as an array of strings.
                $file_str = file($error_file_path);
                // Prepare the data return to the view
                $data = [];
                $data['title'] = 'Error Log';
                $data['job'] = $job;
                $data['plain_file'] = implode('', $file_str);

                // Return the data to the view.
                return view('job/plain_file', $data);
            }
        }

        return redirect('jobs/view/' . $id)->with('notification', 'Could not download analysis error log.');
    }

    public function getViewJobFile(Request $request, string $id)
    {
        Log::debug('JobController::getViewJobFile: job id = ' . $id);
        // Get the download folder (relative to storage_path())
        $download_folder = config('ireceptor.downloads_data_folder');

        // The analysis directory is shated between the Gateway and the Gateway Utilities
        // that are used by Gateway Apps. This MUST be the same across both environments.
        // Stored in a config on the Gateway, hardcoded in the Tapis Gateway Utilities code.
        $analysis_base = config('services.tapis.analysis_base_dir');

        // Get the job for the user.
        $job = Job::findJobForUser($id, auth()->user()->id);

        // If there isn't one, check to see if we are the admin user. If so, then
        // we can access the job info.
        if ($job == null) {
            $user = User::where('username', auth()->user()->username)->first();
            if ($user->isAdmin()) {
                $job = Job::where('id', '=', $id)->first();
            }
        }
        // If the job is still null, then we are not authorized.
        if ($job == null) {
            abort(401, 'Not authorized.');
        }

        $folder = storage_path() . '/' . $download_folder . '/' . $job['input_folder'] . '/' . $analysis_base;
        Log::debug('JobController::getViewJobFile: folder = ' . $folder);
        $file_name = $request->input('file');
        Log::debug('JobController::getViewJobFile: file = ' . $file_name);
        $filename = basename($request->input('file'));
        Log::debug('JobController::getViewJobFile: filename = ' . $filename);
        $directory = dirname($request->input('file'));
        Log::debug('JobController::getViewJobFile: directory = ' . $directory);
        if (File::exists($folder . '/' . $file_name) && File::isFile($folder . '/' . $file_name)) {
            // Gets the contents of the file as an array of strings.
            $file_str = file($folder . '/' . $file_name);
            // Prepare the data return to the view
            $data = [];
            $data['job'] = $job;
            // Add HTML <br> to make the file look OK.
            $file_br_str = str_replace('\n', '<br>\n', $file_str);
            // Return the HTML data to the blade for rendering.
            $data['html_file'] = $file_br_str;

            return view('job/html_file', $data);
        }

        // If we can't view the file, notify the user.
        return redirect('jobs/view/' . $id)->with('notification', 'Could not view analysis file ' . $file_name);
    }

    public function getShow(Request $request)
    {
        // Check to see if the three required parameters are present.
        // If missing, respond with a Not authorized response.
        if (! $request->has('filename') || ! $request->has('directory') || ! $request->has('jobid')) {
            abort(401, 'Not authorized.');
        }

        // Get the three expected request parameters.
        $filename = $request->input('filename');
        $directory = $request->input('directory');
        $jobid = $request->input('jobid');

        // Get the job for the user.
        $job = Job::findJobForUser($jobid, auth()->user()->id);

        // If there isn't one, check to see if we are the admin user. If so, then
        // we can access the job info.
        if ($job == null) {
            $user = User::where('username', auth()->user()->username)->first();
            if ($user->isAdmin()) {
                $job = Job::where('id', '=', $jobid)->first();
            }
        }
        // If the job is still null, then we are not authorized.
        if ($job == null) {
            abort(401, 'Not authorized.');
        }
        // Get the base directory information.
        $download_folder = config('ireceptor.downloads_data_folder');
        // The analysis directory is shated between the Gateway and the Gateway Utilities
        // that are used by Gateway Apps. This MUST be the same across both environments.
        // Stored in a config on the Gateway, hardcoded in the Tapis Gateway Utilities code.
        $analysis_base = config('services.tapis.analysis_base_dir');

        // Get the path for the file relative to storage_path()
        $path = storage_path() . '/' . $download_folder . '/' . $job['input_folder'] . '/' . $analysis_base;
        Log::debug('JobController::getShow: path = ' . $path);
        Log::debug('JobController::getShow: directory = ' . $directory);
        Log::debug('JobController::getShow: filename = ' . $filename);

        return response()->file($path . '/' . $directory . '/' . $filename, ['Content-Disposition' => 'inline; filename="' . $filename . '"']);
    }

    public function getView($id)
    {
        // Set info file name
        $info_file = 'info.txt';

        // Get the download folder (relative to storage_path())
        $download_folder = config('ireceptor.downloads_data_folder');

        // Get the job for the user.
        $job = Job::findJobForUser($id, auth()->user()->id);

        // If there isn't one, check to see if we are the admin user. If so, then
        // we can access the job info.
        if ($job == null) {
            $user = User::where('username', auth()->user()->username)->first();
            if ($user->isAdmin()) {
                $job = Job::where('id', '=', $id)->first();
            }
        }
        // If the job is still null, then we are not authorized.
        if ($job == null) {
            abort(401, 'Not authorized.');
        }

        $data = [];
        $data['job'] = $job;
        Log::debug('JobController::getView: job = ' . json_encode($job, JSON_PRETTY_PRINT));

        // The analysis directory is shated between the Gateway and the Gateway Utilities
        // that are used by Gateway Apps. This MUST be the same across both environments.
        // Stored in a config on the Gateway, hardcoded in the Tapis Gateway Utilities code.
        $analysis_base = config('services.tapis.analysis_base_dir');

        $data['analysis_download_url'] = '';
        $data['output_log_url'] = '';
        $data['error_log_url'] = '';
        // Check to see if we have a folder with Gateway output. If we have gateway
        // output in just a ZIP file, extract the ZIP file. This should only happen once
        // the first time this code is run with a Gateway analysis ZIP file without the
        // unzipped directory.
        $folder = storage_path() . '/' . $download_folder . '/' . $job['input_folder'];
        Log::debug('JobController::getView: folder path = ' . $folder);
        Log::debug('JobController::getView: job[input_folder]= ' . $job['input_folder']);
        $analysis_folder = $folder . '/' . $analysis_base;
        if ($job['input_folder'] != '' && File::exists($folder)) {
            // If this ZIP file exists and the directory does not, the Gateway needs to
            // UNZIP the archive.
            $zip_file = $folder . '/' . $analysis_base . '.zip';
            Log::debug('JobController::getView: unzipping = ' . $zip_file);
            $zip_folder = $analysis_folder;
            if (File::exists($zip_file) && ! File::exists($zip_folder)) {
                Log::debug('JobController::getView - UNZIPing analysis folder');
                // Note: We us system(unzip) rather than zip.extractTo(). The extract to
                // function was returning with no error message - and was failing on unzip
                // of large studies. Even when error checking was done, behaviour was not
                // as expected.
                // Old code:
                //   $zip = new ZipArchive();
                //   $zip->open($zip_file, ZipArchive::RDONLY);
                //   $zip->extractTo($folder);
                //   $zip->close();
                // Note: system() outputs to stdout so redirect to /dev/null is necessary
                // as the JOb view will display the output.
                $unzip_result = system('unzip ' . $zip_file . ' -d ' . $folder . ' > /dev/null');
                if ($unzip_result === false) {
                    Log::debug('JobController::getView - Could not unZIP file');
                }
                Log::debug('JobController::getView - done UNZIP of analysis folder');
            }
            if (File::exists($zip_file)) {
                $data['analysis_download_url'] = $zip_file;
            }
        }

        // Generate a set of summary information about where the data came from
        $data['summary'] = [];
        $info_file_path = $folder . '/' . $info_file;
        if ($job['input_folder'] != '' && File::exists($info_file_path)) {
            $lines = [];
            try {
                $lines = file($info_file_path);
            } catch (\Exception $e) {
                Log::debug('JobController::getView: Could not open file ' . $info_file_path);
                Log::debug('JobController::getView: Error: ' . $e->getMessage());
            }
            $data['summary'] = $lines;
        } else {
            // This code gets executed when the job is actively being run AND
            // when the job is finished and the data has been deleted after the
            // download time out has expired and the gateway has removed the output.
            //
            // Extract the query id from the query URL. They look like this:
            // https:\/\/gateway-analysis.ireceptor.org\/sequences?query_id=8636
            $job_url = $job['url'];
            $query_string = 'query_id=';
            $seq_query_id = substr($job_url, strpos($job_url, $query_string) + strlen($query_string));

            // Get the query filters. Note this is the sequence query.
            $seq_query_params = Query::getParams($seq_query_id);
            $sequence_summary = Query::sequenceParamsSummary($seq_query_params);
            Log::debug('JobController::getView: sequence query summary = ' . $sequence_summary);

            // Get the sample query ID, the query parameters, and the text summary
            if (array_key_exists('sample_query_id', $seq_query_params)) {
                $sample_query_id = $seq_query_params['sample_query_id'];
                $sample_query_params = Query::getParams($sample_query_id);
                $sample_summary = Query::sampleParamsSummary($sample_query_params);
            } else {
                $sample_summary = 'None';
            }

            Log::debug('JobController::getView: sample query summary = ' . $sample_summary);

            // Split the summaries by line into an array, which is what the view expects.
            $s = "<p><strong>Metadata filters</strong></p>\n";
            $s .= "<p>\n";
            // Replace each newline with a HTML <br> followed by the newline as
            // we want HTML here.
            $sample_summary = str_replace("\n", "<br>\n", $sample_summary);
            $s .= $sample_summary;
            $s .= "</p>\n";

            $s .= "<p><strong>Data filters</strong></p>\n";
            $s .= "<p>\n";
            // Replace each newline with a HTML <br> followed by the newline as
            // we want HTML here.
            $sequence_summary = str_replace("\n", "<br>\n", $sequence_summary);
            $s .= $sequence_summary;
            $s .= "</p>\n";

            // Split the data into lines as an array of strings based on the newline character.
            $data['summary'] = explode("\n", $s);
        }

        // Generate a set of job summary comments for the Tapis part of the job.
        $data['job_summary'] = [];

        // Create an Tapis object to work with. This is constant across all jobs.
        $tapis = new Tapis;

        // Build the job summary HTML. This is rendered by the blade.
        $s = '<p><strong>App Parameters</strong></p>';
        $s .= '<p>';
        // Get the JSON from the Job, we need info from it.
        $param_count = 0;
        $job_json = $this->getJobJSON($job->id, $tapis);

        // If we have a JSON string for the Job, process the App parameters.
        if ($job_json != null) {
            // Get the Tapis job status and from it get the parameters.
            $job_status = json_decode($job_json);
            $app_parameters = json_decode($job_status->result->parameterSet)->appArgs;
            // For each parameter, add some text to the display string.
            foreach ($app_parameters as $param) {
                // Basic parameters have notes - special hidden parameters do not. So if
                // we don't have a notes['label'] field then we don't do anything.
                if (property_exists($param, 'notes') && json_decode($param->notes) != null &&
                    property_exists(json_decode($param->notes), 'label')) {
                    // Generate the parameter label and its value
                    $param_string = json_decode($param->notes)->label;
                    $param_value = $param->arg;
                    $s .= $param_string . ': ' . $param_value . '<br>\n';
                    $param_count++;
                }
            }
        }
        if ($param_count == 0) {
            $s .= 'None<br>\n';
        }
        $s .= '<p>';

        $s .= '<p><strong>Job Parameters</strong></p>';
        $s .= '<p>';
        $s .= 'Number of cores: ' . strval($tapis->processorsPerNode()) . '<br>\n';
        $s .= 'Maximum memory per node: ' . strval(round($tapis->memoryMBPerNode() / 1024, 1)) . ' GB<br>\n';
        $s .= 'Maximum run time: ' . strval(round($tapis->maxRunTimeMinutes() / 60, 1)) . ' hours<br>\n';
        $s .= '<p>';
        $data['job_summary'] = explode('\n', $s);

        // Build the job control button HTML. This is rendered by the blade,
        // and controls when the job control button is enabled or not.
        $s = '';
        $s .= '<a href="/jobs/cancel/' . $job->id . '">';
        if ($job->getJobID() == '') {
            $s .= '<button type="button" class="btn btn-primary" aria-label="Cancel" disabled="disabled">';
        } else {
            $s .= '<button type="button" class="btn btn-primary" aria-label="Cancel">';
        }
        $s .= '<span class="glyphicon glyphicon-ban-circle" aria-hidden="true"></span> Cancel this job';
        $s .= '</button></a>';
        $data['job_control_button'] = explode('\n', $s);

        // Generate some Error summary information if the job failed
        $output_file = 'ireceptor-' . $job['id'] . '.out';
        $error_file = 'ireceptor-' . $job['id'] . '.err';
        $data['error_summary'] = [];
        $err_path = $analysis_folder . '/' . $error_file;
        $out_path = $analysis_folder . '/' . $output_file;
        $info_path = $analysis_folder . '/' . $info_file;

        // Error strings we expect to see if an error occured in an App
        $ireceptor_error = 'IR-ERROR';
        $gateway_error = 'GW-ERROR';
        // Determine if we should be handling errors.
        $job_errors = false;
        if ($job->getJobStatus() == 'FAILED') {
            // If the job fails, then we need to handle error messages
            $job_errors = true;
        } elseif ($job->getJobStatus() == 'INTERNAL_ERROR') {
            $job_errors = true;
        } else {
            // If the job error file exists and has an error message then we need to handle errors
            if (File::exists($folder) && File::exists($err_path)) {
                $ir_output = [];
                $gw_output = [];
                exec('grep ' . escapeshellarg($ireceptor_error) . ' ' . $err_path, $ir_output, $result_code);
                exec('grep ' . escapeshellarg($gateway_error) . ' ' . $err_path, $gw_output, $result_code);
                if (count($ir_output) > 0 || count($gw_output) > 0) {
                    $job_errors = true;
                }
            }
            // If the job output file exists and has an error message then we need to handle errors
            if (File::exists($folder) && File::exists($out_path)) {
                $ir_output = [];
                $gw_output = [];
                exec('grep ' . escapeshellarg($ireceptor_error) . ' ' . $out_path, $ir_output, $result_code);
                exec('grep ' . escapeshellarg($gateway_error) . ' ' . $out_path, $gw_output, $result_code);
                if (count($ir_output) > 0 || count($gw_output) > 0) {
                    $job_errors = true;
                }
            }
            // If the job info file exists and has an error message then we need to handle errors
            if (File::exists($folder) && File::exists($out_path)) {
                $ir_output = [];
                $gw_output = [];
                exec('grep ' . escapeshellarg($ireceptor_error) . ' ' . $info_path, $ir_output, $result_code);
                exec('grep ' . escapeshellarg($gateway_error) . ' ' . $info_path, $gw_output, $result_code);
                if (count($ir_output) > 0 || count($gw_output) > 0) {
                    $job_errors = true;
                }
            }
        }

        if ($job_errors) {
            $s = '<em>WARNING: This job completed but errors on some stages of the processing were detected. As a result, some repertoires may not have been processed and/or some results may not be fully complete. Please refer to the Error and Output log files for more information.</em><br>\n';
            // If the Tapis job failed get the error message.
            if ($job->getJobStatus() == 'FAILED') {
                // Get the Tapis error status
                $job_json = $this->getJobJSON($job->id, $tapis);
                if ($job_json != null) {
                    Log::debug('JobController::getView: job result = ' . $job_json);
                    $job_status = json_decode($job_json);
                    $s .= '<br><p><strong>TAPIS errors</strong></p>\n';
                    $s .= '<p><em>NOTE: Tapis errors indicate a problem between the iReceptor Gateway and the compute resources being used to run anaylsis jobs. These are usually a result of either communication errors (the compute resource is not responding in a timely fashion) or run time errors such as the analysis job exceeding either memory or time constraints of the job. If the error appears to be a communication error (SSH, SFTP, MKDIR) this is likely an intermittent problem, please resubmit the job. If the problem persists please contact support@ireceptor.org. If the error is a memory or execution time error, either try to run the job an a smaller subset of the data or contact support@ireceptor.org for assistance.</em></p>';
                    $s .= strval($job_status->result->lastMessage) . '<br>\n';
                }
            }
            if ($job->getJobStatus() == 'INTERNAL_ERROR') {
                $s .= '<br><p><strong>Internal Error</strong></p>\n';
                $s .= 'Unfortunately, your job encountered an unexpected internal error. This may be due to an authentication issue, please log out and log back in and resubmit the job. If the error recurs, please send an email to support@ireceptor.org with the Job ID number and we will investigate.<br>\n';
            }

            // Get the relevant iReceptor Gateway error messages. These come from the
            // normal job ourput and error files, but are tagged with either "IR-ERROR" or
            // "IR_INFO" in the error messages. This allows App developers to provide error
            // messages that the Gateway will display for the user.

            // If there is an error, Tapis doesn't by default download the files, so if
            // it doesn't exist download it and save it. If it does exist open it.
            $stderr_response = '';
            if (File::exists($folder) && ! File::exists($err_path)) {
                // Tapis command to get the file.
                $stderr_response = $tapis->getJobOutputFile($job->getJobID(), $error_file);
                // Check for the analysis directory, create if it doesn't exist.
                if (! File::exists($analysis_folder)) {
                    mkdir($analysis_folder);
                }
                // Write it to disk so it is cached.
                $filehandle = fopen($err_path, 'w');
                fwrite($filehandle, $stderr_response);
            } elseif (File::exists($folder) && File::exists($err_path)) {
                // If it already exists, then open it.
                $filehandle = fopen($err_path, 'r');
                if (filesize($err_path) > 0) {
                    $stderr_response = fread($filehandle, filesize($err_path));
                } else {
                    $stderr_response = '';
                }
            }

            // Repeat for the output log file for the job. Download if not here, add to messages
            // if info available.
            $stdout_response = '';
            if (File::exists($folder) && ! File::exists($out_path)) {
                // Tapis command to get the file.
                $stdout_response = $tapis->getJobOutputFile($job->getJobID(), $output_file);
                // Check for the analysis directory, create if it doesn't exist.
                if (! File::exists($analysis_folder)) {
                    mkdir($analysis_folder);
                }
                // Write it to disk so it is cached.
                $filehandle = fopen($out_path, 'w');
                fwrite($filehandle, $stdout_response);
            } elseif (File::exists($folder) && File::exists($out_path)) {
                // If it already exists, then open it.
                $filehandle = fopen($out_path, 'r');
                if (filesize($out_path) > 0) {
                    $stdout_response = fread($filehandle, filesize($out_path));
                } else {
                    $stdout_response = '';
                }
            }

            // Repeat for the info for the job. Download if not here, add to messages
            // if info available.
            $info_response = '';
            if (File::exists($folder) && ! File::exists($info_path)) {
                // Tapis command to get the file.
                $info_response = $tapis->getJobOutputFile($job->getJobID(), $info_file);
                $info_object = json_decode($info_response);

                // Catch the case when the info file doesn't exist on the Tapis compute side.
                if ($info_object == null) {
                    // Check for the analysis directory, create if it doesn't exist.
                    if (! File::exists($analysis_folder)) {
                        mkdir($analysis_folder);
                    }
                    // Write it to disk so it is cached.
                    $filehandle = fopen($info_path, 'w');
                    fwrite($filehandle, $info_response);
                }
            } elseif (File::exists($folder) && File::exists($info_path)) {
                // If it already exists, then open it.
                $filehandle = fopen($info_path, 'r');
                if (filesize($info_path) > 0) {
                    $info_response = fread($filehandle, filesize($info_path));
                } else {
                    $info_response = '';
                }
            }

            // Extract the error messages from the App for the Gateway.
            $s .= '<br><p><strong>iReceptor Gateway download errors (Download info file)</strong></p>\n';
            $string_list = explode(PHP_EOL, $info_response);
            foreach ($string_list as $line) {
                if (substr($line, 0, strlen($gateway_error)) == $gateway_error ||
                    substr($line, 0, strlen($ireceptor_error)) == $ireceptor_error) {
                    $s .= $line . '<br>\n';
                }
            }
            // Extract the error messages from the App for the Gateway.
            $s .= '<br><p><strong>iReceptor Gateway errors (Analysis Error Log)</strong></p>\n';
            $string_list = explode(PHP_EOL, $stderr_response);
            foreach ($string_list as $line) {
                if (substr($line, 0, strlen($gateway_error)) == $gateway_error ||
                    substr($line, 0, strlen($ireceptor_error)) == $ireceptor_error) {
                    $s .= $line . '<br>\n';
                }
            }
            // Extract the error messages from the App stdout for the Gateway.
            $s .= '<br><p><strong>iReceptor Gateway errors (Analysis Output Log)</strong></p>\n';
            $string_list = explode(PHP_EOL, $stdout_response);
            foreach ($string_list as $line) {
                if (substr($line, 0, strlen($gateway_error)) == $gateway_error ||
                    substr($line, 0, strlen($ireceptor_error)) == $ireceptor_error) {
                    $s .= $line . '<br>\n';
                }
            }

            // Extract the info messages from the App for the Gateway.
            /*
            $s .= '<br><p><strong>iReceptor Gateway output messages</strong></p>\n';
            $string_list = explode(PHP_EOL, $stdout_response);
            $ireceptor_info = 'IR-INFO';
            $gateway_info = 'GW-INFO';
            foreach ($string_list as $line) {
                if (substr($line, 0, strlen($ireceptor_error)) == $ireceptor_error ||
                    substr($line, 0, strlen($gateway_error)) == $gateway_error ||
                    substr($line, 0, strlen($ireceptor_info)) == $ireceptor_info ||
                    substr($line, 0, strlen($gateway_info)) == $gateway_info) {
                    $s .= $line . '<br>\n';
                }
            }
            */

            // Save the exploded error summary message in the data for the view.
            $data['error_summary'] = explode('\n', $s);
        }

        // Set up the display of the output of the analysis, if it
        // exists.
        $data['files'] = [];
        $data['filesHTML'] = '';
        $data['analysis_summary'] = [];
        if ($job['input_folder'] != '') {
            if (File::exists($folder)) {
                // If we have a folder with files in it...
                $data['files'] = File::allFiles($folder);
                if (count($data['files']) > 0) {
                    // Create a list of files a baseline to display
                    $data['filesHTML'] = dir_to_html($analysis_folder, $job->id);
                    // Log::debug('JobController::getView: filesHTML = ' . $data['filesHTML']);
                    // We want to have specific info for the error and output files.
                    $data['error_log_url'] = $analysis_folder . '/' . $error_file;
                    $data['output_log_url'] = $analysis_folder . '/' . $output_file;

                    // Do special case handling if the output has an iReceptor Gateway specific
                    // output director. In this case we expect a certain structure, which is a
                    // directory per analysis unit and in the case of a multi-repository analysis,
                    // a directory per repository and within that a directory per analysis unit.
                    // Currently, the only analysis unit supported is repertoire_id.
                    $analysis_summary = [];
                    if (File::exists($analysis_folder) && is_dir($analysis_folder)) {
                        foreach (scandir($analysis_folder) as $file) {
                            // Look at each file and if it is a folder, process it.
                            if ($file !== '.' && $file !== '..' && is_dir($analysis_folder . '/' . $file)) {
                                Log::debug('JobController::getView: processing dir = ' . $file);
                                // Look for gateway specific analysis summary files. If the analysis app
                                // produces an .html/.pdf and a .txt file with the same name as the directory
                                // for this analysis unit, then we give that information to the Gateway so that
                                // it can display
                                //
                                // Build the summary file name.
                                $summary_file = '';
                                $summary_gateway_file = '';
                                if (File::exists($analysis_folder . '/' . $file . '/' . $file . '-gateway.html')) {
                                    $summary_file = $analysis_folder . '/' . $file . '/' . $file . '-gateway.html';
                                    $summary_gateway_file = $file . '/' . $file . '-gateway.html';
                                    $summary_query = $file . '/' . $file . '-gateway.html';
                                } elseif (File::exists($analysis_folder . '/' . $file . '/' . $file . '-gateway.pdf')) {
                                    $summary_file = $analysis_folder . '/' . $file . '/' . $file . '-gateway.pdf';
                                    $summary_gateway_file = $file . '/' . $file . '-gateway.pdf';
                                    $summary_query = $file . '/' . $file . '-gateway.pdf';
                                } elseif (File::exists($analysis_folder . '/' . $file . '/' . $file . '-gateway.tsv')) {
                                    $summary_file = $analysis_folder . '/' . $file . '/' . $file . '-gateway.tsv';
                                    $summary_gateway_file = $file . '/' . $file . '-gateway.tsv';
                                    $summary_query = $file . '/' . $file . '-gateway.tsv';
                                }
                                Log::debug('summary_file = ' . $summary_file);
                                Log::debug('summary_gateway_file = ' . $summary_gateway_file);
                                // Build the label file name
                                $label_file = $analysis_folder . '/' . $file . '/' . $file . '.txt';
                                // If both files exist, build a summary object for the Gateway so that it can present
                                // the summary information for this analysis unit elegantly...
                                if (File::exists($summary_file) && File::exists($label_file)) {
                                    $filehandle = fopen($label_file, 'r');
                                    $label = $file;
                                    if (filesize($label_file) > 0) {
                                        $label = fread($filehandle, filesize($label_file));
                                    }
                                    // Create the object with useful info (that the Gateway Job view expects).
                                    $summary_object = ['repository' => '', 'name' => $file, 'label' => $label,
                                        'url' => '/' . $summary_file, 'file_query' => $summary_query,
                                        'filename' => basename($summary_query), 'directory' => $file,
                                        'gateway_filename' => $summary_gateway_file];
                                    // Add to the list of summary objects.
                                    $analysis_summary[] = $summary_object;
                                } else {
                                    // If the files don't exist, then check to see if we have a repository/analysis hierarchy
                                    $repository_dir = $analysis_folder . '/' . $file;
                                    $repository_name = $file;
                                    // We repeat the process above for each directory.
                                    foreach (scandir($repository_dir) as $file) {
                                        if ($file !== '.' && $file !== '..' && is_dir($repository_dir . '/' . $file)) {
                                            // Get file names
                                            $summary_file = '';
                                            $summary_gateway_file = '';
                                            if (File::exists($repository_dir . '/' . $file . '/' . $file . '-gateway.html')) {
                                                $summary_file = $repository_dir . '/' . $file . '/' . $file . '-gateway.html';
                                                $summary_gateway_file = $repository_name . '/' . $file . '/' . $file . '-gateway.html';
                                                $summary_query = $repository_name . '/' . $file . '/' . $file . '-gateway.html';
                                            } elseif (File::exists($repository_dir . '/' . $file . '/' . $file . '-gateway.pdf')) {
                                                $summary_file = $repository_dir . '/' . $file . '/' . $file . '-gateway.pdf';
                                                $summary_gateway_file = $repository_name . '/' . $file . '/' . $file . '-gateway.pdf';
                                                $summary_query = $repository_name . '/' . $file . '/' . $file . '-gateway.pdf';
                                            } elseif (File::exists($repository_dir . '/' . $file . '/' . $file . '-gateway.tsv')) {
                                                $summary_file = $repository_dir . '/' . $file . '/' . $file . '-gateway.tsv';
                                                $summary_gateway_file = $repository_name . '/' . $file . '/' . $file . '-gateway.tsv';
                                                $summary_query = $repository_name . '/' . $file . '/' . $file . '-gateway.tsv';
                                            }
                                            Log::debug('summary_file = ' . $summary_file);
                                            Log::debug('summary_gateway_file = ' . $summary_gateway_file);
                                            // Build the label file name
                                            $label_file = $repository_dir . '/' . $file . '/' . $file . '.txt';
                                            // If they exist, process them
                                            if (File::exists($summary_file) && File::exists($label_file)) {
                                                $filehandle = fopen($label_file, 'r');
                                                $label = $file;
                                                if (filesize($label_file) > 0) {
                                                    $label = fread($filehandle, filesize($label_file));
                                                }
                                                // Create the summary object for the Job view to display for this analysis unit.
                                                $summary_object = ['repository' => $repository_name, 'name' => $file, 'label' => $label,
                                                    'url' => '/' . $summary_file, 'file_query' => $summary_query,
                                                    'filename' => basename($summary_query), 'directory' => $repository_name . '/' . $file,
                                                    'gateway_filename' => $summary_gateway_file];
                                                $analysis_summary[] = $summary_object;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    // Store the analysis summary in the data block returned to the Job view.
                    $data['analysis_summary'] = $analysis_summary;
                } elseif ($job->getJobStatus() == 'FINISHED') {
                    // In the case where the job is FINISHED and there are no output files,
                    // inform user data is not available. Note: this handles the case where
                    // the Gateway cleanup removes all the files but the directory structure
                    // still exists for some reason. So we check the count of
                    // actual files to determine if the analysis has been removed or not.
                    $msg = "<strong>NOTE</strong>: The data from this analysis has been removed as the archive timeout has expired, please re-run this analysis to reproduce the data.<br><br>\n";
                    $msg .= "<em>Remember that these analyses can be resource intensive so please remember to download your analysis results once the analysis is finished if you want to maintain a copy! Re-running analysis jobs is a waste of computational resources and will negatively impact all users of the iReceptor Platform.</em><br>\n";
                    $data['filesHTML'] = $msg;
                }
            } else {
                // In the case where the job is FINISHED and there are no output files, tell the user
                // that the data is no longer available.
                $msg = "<strong>NOTE</strong>: The data from this analysis has been removed as the archive timeout has expired, please re-run this analysis to reproduce the data.<br><br>\n";
                $msg .= "<em>Remember that these analyses can be resource intensive so please remember to download your analysis results once the analysis is finished if you want to maintain a copy! Re-running analysis jobs is a waste of computational resources and will negatively impact all users of the iReceptor Platform.</em><br>\n";
                $data['filesHTML'] = $msg;
            }
        }

        // Provide the step info for the job.
        $data['steps'] = JobStep::findAllForJob($id);

        return view('job/view', $data);
    }

    public function getJobHistory($id)
    {
        $tapis = new Tapis;

        $job = Job::where('id', '=', $id)->first();
        $response = null;
        if ($job != null && $job->getJobID() != '') {
            // Get the job info.
            $job_id = $job->getJobID();
            $job_user = User::where('id', $job->user_id)->first();
            $response = $tapis->getJobHistory($job_id);
            // If there was an error, tryas an admin user to get the same info.
            // TODO: is this true - check and fix: getJob returns a JSON string,
            // isTapisError expects an object.
            if ($tapis->isTapisError(json_decode($response))) {
                Log::debug('JobContorller::getJobHistory - got an error');
                $this_user = User::where('username', auth()->user()->username)->first();
                if ($this_user->isAdmin()) {
                    $response = $tapis->getJob($job_id);
                    // If we can get the info, return it
                    if ($tapis->isTapisError(json_decode($response))) {
                        $response = null;
                    }
                }
            }
        }
        if ($response == null) {
            echo '<pre>Job History unavailable</pre>';
        } else {
            echo '<pre>' . $response . '</pre>';
        }
    }

    public function getJobJSON($id, $tapis)
    {
        $job = Job::where('id', '=', $id)->first();
        $response = null;
        if ($job != null && $job->getJobID() != '') {
            // Get the job info
            $job_id = $job->getJobID();
            $job_user = User::where('id', $job->user_id)->first();
            $response = $tapis->getJob($job_id);
            // If there was an error, try as an admin user to get the same info.
            // TODO: is this true - check and fix: getJob returns a JSON string,
            // isTapisError expects an object.
            if ($tapis->isTapisError(json_decode($response))) {
                Log::debug('JobContorller::getJobJSON - got an error');
                $this_user = User::where('username', auth()->user()->username)->first();
                if ($this_user->isAdmin()) {
                    $response = $tapis->getJob($job_id);
                    // If there is an error, then return null.
                    if ($tapis->isTapisError(json_decode($response))) {
                        $response = null;
                    }
                } else {
                    // If we are not admin, return null
                    $response = null;
                }
            }
        }

        return $response;
    }

    // ajax-called from view page
    public function getStatus($id)
    {
        $job = Job::where('id', '=', $id)->first();
        echo $job->getJobStatus();
    }

    public function getCancel($id)
    {
        // Get user and job info
        $userId = auth()->user()->id;
        $job = Job::get($id, $userId);

        // If we found one, clean up
        if ($job != null) {
            // Clean up the running Tapis job if it exists and is not finished.
            if (isset($job['agave_id']) and isset($job['agave_status']) and $job['agave_status'] != 'FINISHED' and $job['agave_status'] != 'INTERNAL_ERROR') {
                Log::debug('Deleting Tapis job ' . $job->getJobID());
                // Kill the job and update the status.
                $tapis = new Tapis;
                $response = $tapis->killJob($job->getJobID());
                $job->updateStatus('STOPPED');
            }
        }

        return redirect('jobs/view/' . $id);
    }

    public function getDelete($id)
    {
        // Get the folder where we store the downloads (relative to storage_path())
        $download_folder = config('ireceptor.downloads_data_folder');

        // Get user and job info
        $userId = auth()->user()->id;
        $job = Job::get($id, $userId);

        Log::debug($job);
        if ($job != null) {
            // Clean up the running Tapis it exists and the job if not finished.
            if (isset($job['agave_id']) and isset($job['agave_status']) and $job['agave_status'] != 'FINISHED' and $job['agave_status'] != 'STOPPED' and $job['agave_status'] != 'INTERNAL_ERROR') {
                Log::debug('Deleting Tapis job ' . $job->getJobID());
                // Kill the job and update the status.
                $tapis = new Tapis;
                $response = $tapis->killJob($job->getJobID());
                $job->updateStatus('STOPPED');
            }

            // Delete job files. The job files are in a folder that consits of the
            // internal job name (ir_2022-09-23_0128_632d0bb8e7797) with _output as
            // the suffix.
            if ($job['input_folder']) { // IMPORTANT: this "if" prevents accidental deletion of ALL jobs data
                $dataFolder = $download_folder . '/' . $job['input_folder'];
                Log::debug('Deleting files in ' . $dataFolder);
                if (! File::deleteDirectory($dataFolder)) {
                    Log::info('Unable to delete files in ' . $dataFolder);
                }

                // The ZIP file has the same base job ID (ir_2022-09-23_0128_632d0bb8e7797)
                // with a .zip suffix.
                $folder_name = basename($job['input_folder']);
                $zip_file = substr($folder_name, 0, strpos($folder_name, '_output')) . '.zip';
                Log::debug('Removing ZIP file ' . $zip_file);
                if (! File::delete($download_folder . '/' . $zip_file)) {
                    Log::info('Unable to delete ZIP file ' . $zip_file);
                }
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
