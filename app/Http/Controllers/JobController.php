<?php

namespace App\Http\Controllers;

use App\Agave;
use App\Job;
use App\Jobs\LaunchAgaveJob;
use App\Jobs\PrepareDataForThirdPartyAnalysis;
use App\JobStep;
use App\LocalJob;
use App\Query;
use App\System;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
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
        $data['agave_status'] = $job->agave_status;
        $data['submission_date_relative'] = $job->createdAtRelative();
        $data['run_time'] = $job->totalTime();
        $data['job_url'] = $job->url;
        $data['job'] = $job;

        // Create an Agave object to work with. This is constant across all jobs.
        $agave = new Agave;
        // Build the job_summary block HTML
        $data['job_summary'] = [];
        $s = '<p><b>Job Parameters</b></p>';
        $s .= 'Number of cores = ' . strval($agave->processorsPerNode()) . '<br/>\n';
        $s .= 'Maximum memory per core = ' . strval($agave->memoryPerProcessor()) . ' GB<br/>\n';
        $s .= 'Maximum run time = ' . strval($agave->maxRunTime()) . ' hours<br/>\n';
        $data['job_summary'] = explode('\n', $s);

        // Build the job control button HTML. This is rendered by the blade,
        // and controls when the job control button is enabled or not.
        $data['job_control_button'] = [];
        $s = '';
        $s .= '<a href="/jobs/cancel/' . $job->id . '">';
        if ($job->agave_id == '') {
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
        $request_data = $request->all();

        $gw_username = auth()->user()->username;
        $token = auth()->user()->password;

        // create systems
        System::createDefaultSystemsForUser($gw_username, $token);

        Log::info('Processing Job: app_id = ' . $request_data['app_id']);
        $appId = $request_data['app_id'];

        // 3rd-party analysis
        if ($appId == '999') {
            Log::info('999');
            $appHumanName = 'Third-party analysis';
            $jobDescription = 'Data federation';
        } else {
            // create Agave app
            $executionSystem = System::getCurrentSystem(auth()->user()->id);
            $username = $executionSystem->username;
            $appExecutionSystem = $executionSystem->name;
            $appDeploymentSystem = $systemDeploymentName = config('services.agave.system_deploy.name_prefix') . str_replace('_', '-', $gw_username) . '-' . $username;
            $inputs = [];
            $appHumanName = '';
            $jobDescription = 'Data federation + submission to AGAVE';

            // Create an Agave object to work with.
            $agave = new Agave;

            // Get the App config for the app in question.
            Log::info('Processing App ' . $appId);
            $app_info = $agave->getAppTemplate($appId);
            $app_config = $app_info['config'];

            // Set up the App Tapis name, the human name, and the deployment path.
            $appName = $appId . '-' . $executionSystem->name;
            $appDeploymentPath = $appId;
            $appHumanName = $app_config['label'];

            // Process the parameters for the job.
            $params = [];
            foreach ($app_config['parameters'] as $parameter_info) {
                Log::debug('   Processing parameter ' . $parameter_info['id']);
                // If it visible, we want to pass on the input to the job.
                if ($parameter_info['value']['visible']) {
                    $params[$parameter_info['id']] = $request_data[$parameter_info['id']];
                    Log::debug('   Parameter value = ' . $request_data[$parameter_info['id']]);
                }
            }

            // Process the job parameters for the job.
            $job_params = [];
            // Get the possible list of parameters that can be set. The Agave class
            // manages which job parameters can be set.
            $job_parameter_list = $agave->getJobParameters();
            foreach ($job_parameter_list as $job_parameter_info) {
                // If the parameter is provided, keep track of it so we can give it to the job.
                Log::debug('   Processing job parameter ' . $job_parameter_info['label']);
                if (isset($request_data[$job_parameter_info['label']])) {
                    $job_params[$job_parameter_info['label']] = $request_data[$job_parameter_info['label']];
                    Log::debug('   Parameter value = ' . $request_data[$job_parameter_info['label']]);
                }
            }

            // Based on the above, create the Tapis App.
            $config = $agave->getAppConfig($appId, $appName, $appExecutionSystem, $appDeploymentSystem, $appDeploymentPath);
            $response = $agave->createApp($token, $config);
            $agaveAppId = $response->result->id;
            Log::info('app created: ' . $appId);

            // config parameters for the job
            $executionSystem = System::getCurrentSystem(auth()->user()->id);
            $tenant_url = config('services.agave.tenant_url');
            $systemStaging = config('services.agave.system_staging.name_prefix') . str_replace('_', '-', $gw_username);
            $notificationUrl = config('services.agave.gw_notification_url');
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

        $lj = new LocalJob;
        $lj->description = 'Job ' . $jobId . ' (' . $jobDescription . ')';
        // $lg->user = auth()->user()->username;
        // $lg->job_id = $jobId;
        $lj->save();
        $localJobId = $lj->id;

        // queue job
        if ($appId == '999') {
            PrepareDataForThirdPartyAnalysis::dispatch($jobId, $request_data, $gw_username, $localJobId);
        } else {
            LaunchAgaveJob::dispatch($jobId, $request_data, $tenant_url, $token, $username, $systemStaging, $notificationUrl, $agaveAppId, $gw_username, $params, $inputs, $job_params, $localJobId, $query_type);
        }

        return redirect('jobs/view/' . $jobId);
    }

    public function getView($id)
    {
        $job = Job::findJobForUser($id, auth()->user()->id);
        //$job = Job::where('id', '=', $id)->first();
        if ($job == null) {
            abort(401, 'Not authorized.');
        }

        $data = [];
        $data['job'] = $job;
        Log::debug('JobController::getView: job = ' . json_encode($job, JSON_PRETTY_PRINT));

        // The analysis directory "gateway_analysis" is defined in the Gateway Utilities
        // that are used by Gateway Apps. This MUST be the same and probably should be
        // defined as a CONFIG variable some how. For now we hardcode here and in the
        // Tapis Gateway Utilities code.
        $analysis_base = 'gateway_analysis';

        $data['analysis_download_url'] = '';
        $data['output_log_url'] = '';
        $data['error_log_url'] = '';
        // Check to see if we have a folder with Gateway output. If we have gateway
        // output in just a ZIP file, extract the ZIP file. This should only happen once
        // the first time this code is run with a Gateway analysis ZIP file without the
        // unzipped directory.
        $folder = 'storage/' . $job['input_folder'];
        $analysis_folder = $folder . '/' . $analysis_base;
        if ($job['input_folder'] != '' && File::exists($folder)) {
            // If this ZIP file exists and the directory does not, the Gateway needs to
            // UNZIP the archive.
            $zip_file = $folder . '/' . $analysis_base . '.zip';
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
        $info_file = $folder . '/info.txt';
        if ($job['input_folder'] != '' && File::exists($info_file)) {
            try {
                $info_txt = file_get_contents($info_file);
                $lines = file($info_file);
            } catch (Exception $e) {
                Log::debug('JobController::getView: Could not open file ' . $info_file);
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
            $s = "<p><b>Metadata filters</b></p>\n";
            // Replace each newline with a HTML <br/> followed by the newline as
            // we want HTML here.
            $sample_summary = str_replace("\n", "<br/>\n", $sample_summary);
            $s .= $sample_summary . "<br/>\n";
            $s .= "<p><b>Sequence filters</b></p>\n";
            // Replace each newline with a HTML <br/> followed by the newline as
            // we want HTML here.
            $sequence_summary = str_replace("\n", "<br/>\n", $sequence_summary);
            $s .= $sequence_summary . "\n";

            // Split the data into lines as an array of strings based on the newline character.
            $data['summary'] = explode("\n", $s);
        }

        // Generate a set of job summary comments for the Tapis part of the job.
        $data['job_summary'] = [];
        // Create an Agave object to work with. This is constant across all jobs.
        $agave = new Agave;
        // Build the job summary HTML. This is rendered by the blade.
        $s = '<p><b>Job Parameters</b></p>';
        $s .= 'Number of cores = ' . strval($agave->processorsPerNode()) . '<br/>\n';
        $s .= 'Maximum memory per core = ' . strval($agave->memoryPerProcessor()) . ' GB<br/>\n';
        $s .= 'Maximum run time = ' . strval($agave->maxRunTime()) . ' hours<br/>\n';
        $data['job_summary'] = explode('\n', $s);

        // Build the job control button HTML. This is rendered by the blade,
        // and controls when the job control button is enabled or not.
        $s = '';
        $s .= '<a href="/jobs/cancel/' . $job->id . '">';
        if ($job->agave_id == '') {
            $s .= '<button type="button" class="btn btn-primary" aria-label="Cancel" disabled="disabled">';
        } else {
            $s .= '<button type="button" class="btn btn-primary" aria-label="Cancel">';
        }
        $s .= '<span class="glyphicon glyphicon-ban-circle" aria-hidden="true"></span> Cancel this job';
        $s .= '</button></a>';
        $data['job_control_button'] = explode('\n', $s);

        // Generate some Error summary information if the job failed
        $output_file = 'irec-job-' . $job['id'] . '-' . $job['agave_id'] . '.out';
        $error_file = 'irec-job-' . $job['id'] . '-' . $job['agave_id'] . '.err';
        $data['error_summary'] = [];
        $err_path = $analysis_folder . '/' . $error_file;
        $out_path = $analysis_folder . '/' . $output_file;
        $info_path = $analysis_folder . '/' . $info_file;

        // Error strings we expect to see if an error occured in an App
        $ireceptor_error = 'IR-ERROR';
        $gateway_error = 'GW-ERROR';
        // Determine if we should be handling errors.
        $job_errors = false;
        if ($job->agave_status == 'FAILED') {
            // If the job fails, then we need to handle error messages
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
            $s = '<em>WARNING: This job completed but errors on some stages of the processing were detected. As a result, some repertoires may not have been processed and/or some results may not be fully complete. Please refer to the Error and Output log files for more information.</em><br/>\n';
            // If the Tapis job failed get the error message.
            if ($job->agave_status == 'FAILED') {
                // Get the Tapis error status
                $agave_status = json_decode($this->getAgaveJobJSON($job->id));
                $s .= '<br/><p><b>TAPIS errors</b></p>\n';
                $s .= strval($agave_status->result->lastStatusMessage) . '<br/>\n';
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
                $agave = new Agave;
                $token = auth()->user()->password;
                $stderr_response = $agave->getJobOutputFile($job->agave_id, $token, $error_file);
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
                $agave = new Agave;
                $token = auth()->user()->password;
                $stdout_response = $agave->getJobOutputFile($job->agave_id, $token, $output_file);
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
                $agave = new Agave;
                $token = auth()->user()->password;
                $info_response = $agave->getJobOutputFile($job->agave_id, $token, $info_file);
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
            $s .= '<br/><p><b>iReceptor Gateway download errors (Download info file)</b></p>\n';
            $string_list = explode(PHP_EOL, $info_response);
            foreach ($string_list as $line) {
                if (substr($line, 0, strlen($gateway_error)) == $gateway_error ||
                    substr($line, 0, strlen($ireceptor_error)) == $ireceptor_error) {
                    $s .= $line . '<br/>\n';
                }
            }
            // Extract the error messages from the App for the Gateway.
            $s .= '<br/><p><b>iReceptor Gateway errors (Analysis Error Log)</b></p>\n';
            $string_list = explode(PHP_EOL, $stderr_response);
            foreach ($string_list as $line) {
                if (substr($line, 0, strlen($gateway_error)) == $gateway_error ||
                    substr($line, 0, strlen($ireceptor_error)) == $ireceptor_error) {
                    $s .= $line . '<br/>\n';
                }
            }
            // Extract the error messages from the App stdout for the Gateway.
            $s .= '<br/><p><b>iReceptor Gateway errors (Analysis Output Log)</b></p>\n';
            $string_list = explode(PHP_EOL, $stdout_response);
            foreach ($string_list as $line) {
                if (substr($line, 0, strlen($gateway_error)) == $gateway_error ||
                    substr($line, 0, strlen($ireceptor_error)) == $ireceptor_error) {
                    $s .= $line . '<br/>\n';
                }
            }

            // Extract the info messages from the App for the Gateway.
            /*
            $s .= '<br/><p><b>iReceptor Gateway output messages</b></p>\n';
            $string_list = explode(PHP_EOL, $stdout_response);
            $ireceptor_info = 'IR-INFO';
            $gateway_info = 'GW-INFO';
            foreach ($string_list as $line) {
                if (substr($line, 0, strlen($ireceptor_error)) == $ireceptor_error ||
                    substr($line, 0, strlen($gateway_error)) == $gateway_error ||
                    substr($line, 0, strlen($ireceptor_info)) == $ireceptor_info ||
                    substr($line, 0, strlen($gateway_info)) == $gateway_info) {
                    $s .= $line . '<br/>\n';
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
                    $data['filesHTML'] = dir_to_html($folder);
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
                                // Look for gateway specific analysis summary files. If the analysis app
                                // produces an .html/.pdf and a .txt file with the same name as the directory
                                // for this analysis unit, then we give that information to the Gateway so that
                                // it can display
                                //
                                // Build the summary file name.
                                $summary_file = '';
                                if (File::exists($analysis_folder . '/' . $file . '/' . $file . '.html')) {
                                    $summary_file = $analysis_folder . '/' . $file . '/' . $file . '.html';
                                } elseif (File::exists($analysis_folder . '/' . $file . '/' . $file . '.pdf')) {
                                    $summary_file = $analysis_folder . '/' . $file . '/' . $file . '.pdf';
                                } elseif (File::exists($analysis_folder . '/' . $file . '/' . $file . '.tsv')) {
                                    $summary_file = $analysis_folder . '/' . $file . '/' . $file . '.tsv';
                                }
                                Log::debug('summary_file = ' . $summary_file);
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
                                    $summary_object = ['repository' => '', 'name' => $file, 'label' => $label, 'url' => '/' . $summary_file];
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
                                            if (File::exists($repository_dir . '/' . $file . '/' . $file . '.html')) {
                                                $summary_file = $repository_dir . '/' . $file . '/' . $file . '.html';
                                            } elseif (File::exists($repository_dir . '/' . $file . '/' . $file . '.pdf')) {
                                                $summary_file = $repository_dir . '/' . $file . '/' . $file . '.pdf';
                                            } elseif (File::exists($analysis_folder . '/' . $file . '/' . $file . '.tsv')) {
                                                $summary_file = $analysis_folder . '/' . $file . '/' . $file . '.tsv';
                                            }
                                            Log::debug('summary_file = ' . $summary_file);
                                            $label_file = $repository_dir . '/' . $file . '/' . $file . '.txt';
                                            // If they exist, process them
                                            if (File::exists($summary_file) && File::exists($label_file)) {
                                                $filehandle = fopen($label_file, 'r');
                                                $label = $file;
                                                if (filesize($label_file) > 0) {
                                                    $label = fread($filehandle, filesize($label_file));
                                                }
                                                // Create the summary object for the Job view to display for this analysis unit.
                                                $summary_object = ['repository' => $repository_name, 'name' => $file, 'label' => $label, 'url' => '/' . $summary_file];
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
                } elseif ($job->agave_status == 'FINISHED') {
                    // In the case where the job is FINISHED and there are no output files,
                    // inform user data is not available. Note: this handles the case where
                    // the Gateway cleanup removes all the files but the directory structure
                    // still exists for some reason. So we check the count of
                    // actual files to determine if the analysis has been removed or not.
                    $msg = "<b>NOTE</b>: The data from this analysis has been removed as the archive timeout has expired, please re-run this analysis to reproduce the data.<br/><br/>\n";
                    $msg .= "<em>Remember that these analyses can be resource intensive so please remember to download your analysis results once the analysis is finished if you want to maintain a copy! Re-running analysis jobs is a waste of computational resources and will negatively impact all users of the iReceptor Platform.</em><br/>\n";
                    $data['filesHTML'] = $msg;
                }
            } else {
                // In the case where the job is FINISHED and there are no output files, tell the user
                // that the data is no longer available.
                $msg = "<b>NOTE</b>: The data from this analysis has been removed as the archive timeout has expired, please re-run this analysis to reproduce the data.<br/><br/>\n";
                $msg .= "<em>Remember that these analyses can be resource intensive so please remember to download your analysis results once the analysis is finished if you want to maintain a copy! Re-running analysis jobs is a waste of computational resources and will negatively impact all users of the iReceptor Platform.</em><br/>\n";
                $data['filesHTML'] = $msg;
            }
        }

        // Provide the step info for the job.
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

    public function getAgaveJobJSON($id)
    {
        $job = Job::where('id', '=', $id)->first();
        $response = '{}';
        if ($job != null && $job->agave_id != '') {
            $job_agave_id = $job->agave_id;
            $token = auth()->user()->password;

            $agave = new Agave;
            $response = $agave->getJob($job_agave_id, $token);
        }

        return $response;
    }

    // ajax-called from view page
    public function getStatus($id)
    {
        $job = Job::where('id', '=', $id)->first();
        echo $job->agave_status;
    }

    public function getCancel($id)
    {
        // Get the job
        $userId = auth()->user()->id;
        $job = Job::get($id, $userId);

        // If we found one, clean up
        if ($job != null) {
            // Clean up the running AGAVE job if not finished.
            if (isset($job['agave_status']) and $job['agave_status'] != 'FINISHED') {
                Log::debug('Deleting AGAVE job ' . $job['agave_id']);
                $agave = new Agave;
                $token = auth()->user()->password;
                // Kill the job and update the status.
                $response = $agave->killJob($job['agave_id'], $token);
                $job->updateStatus('STOPPED');
            }
        }

        return redirect('jobs/view/' . $id);
    }

    public function getDelete($id)
    {
        $userId = auth()->user()->id;
        $job = Job::get($id, $userId);

        Log::debug($job);
        if ($job != null) {
            // Clean up the running AGAVE job if not finished.
            if (isset($job['agave_status']) and $job['agave_status'] != 'FINISHED' and $job['agave_status'] != 'STOPPED') {
                Log::debug('Deleting AGAVE job ' . $job['agave_id']);
                $agave = new Agave;
                $token = auth()->user()->password;
                // Kill the job and update the status.
                $response = $agave->killJob($job['agave_id'], $token);
                $job->updateStatus('STOPPED');
            }

            // Delete job files. The job files are in a folder that consits of the
            // internal job name (ir_2022-09-23_0128_632d0bb8e7797) with _output as
            // the suffix.
            if ($job['input_folder']) { // IMPORTANT: this "if" prevents accidental deletion of ALL jobs data
                $dataFolder = 'storage/' . $job['input_folder'];
                Log::debug('Deleting files in ' . $dataFolder);
                if (! File::deleteDirectory($dataFolder)) {
                    Log::info('Unable to delete files in ' . $dataFolder);
                }

                // The ZIP file has the same base job ID (ir_2022-09-23_0128_632d0bb8e7797)
                // with a .zip suffix.
                $folder_name = basename($job['input_folder']);
                $zip_file = substr($folder_name, 0, strpos($folder_name, '_output')) . '.zip';
                Log::debug('Removing ZIP file ' . $zip_file);
                if (! File::delete('storage/' . $zip_file)) {
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
