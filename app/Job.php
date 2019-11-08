<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

if (! function_exists('curl_file_create')) {
    function curl_file_create($filename, $mimetype = '', $postname = '')
    {
        return "@$filename;filename="
            . ($postname ?: basename($filename))
            . ($mimetype ? ";type=$mimetype" : '');
    }
}

class Job extends Model
{
    protected $table = 'job';
    protected $guarded = ['id'];

    public function createdAt()
    {
        return Carbon::parse($this->created_at)->format('D j H:i');
    }

    public function createdAtFull()
    {
        // March 11 2015, 16:28
        return Carbon::parse($this->created_at)->format('F j, Y') . ' at ' . Carbon::parse($this->created_at)->format('H:i');
    }

    public function createdAtRelative()
    {
        return Carbon::parse($this->created_at)->diffForHumans();
    }

    public static function findJobsGroupByMonthForUser($user_id)
    {
        $t = [];

        $l = static::where('user_id', '=', $user_id)->orderBy('created_at', 'desc')->get();
        foreach ($l as $job) {
            $job_month_year_str = Carbon::parse($job->created_at)->format('M Y');
            $t[$job_month_year_str][] = $job;
        }

        return $t;
    }

    public static function findJobsByIdForUser($job_id_list, $user_id)
    {
        $l = static::where('user_id', '=', $user_id)->whereIn('id', $job_id_list)->orderBy('created_at', 'desc')->get();

        return $l;
    }

    public static function findJobForUser($job_id, $user_id)
    {
        $j = static::where('user_id', '=', $user_id)->where('id', '=', $job_id)->first();

        return $j;
    }

    public static function createToken()
    {
        $tenant_url = Config::get('services.agave.tenant_url');
        $username = Config::get('services.agave.username');
        $password = Config::get('services.agave.password');
        $api_key = Config::get('services.agave.api_key');
        $api_secret = Config::get('services.agave.api_token');

        $c = curl_init();

        // url
        curl_setopt($c, CURLOPT_URL, $tenant_url);
        curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);

        // headers
        $h = [];
        $h[0] = 'Content-Type:application/x-www-form-urlencoded';
        curl_setopt($c, CURLOPT_HTTPHEADER, $h);

        // POST data
        curl_setopt($c, CURLOPT_POST, true);
        $post_data = 'grant_type=password&username=' . $username . '&password=' . $password . '&scope=PRODUCTION';
        curl_setopt($c, CURLOPT_POSTFIELDS, $post_data);

        // auth
        curl_setopt($c, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($c, CURLOPT_USERPWD, $api_key . ':' . $api_secret);

        // return output instead of displaying it
        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);

        $json = curl_exec($c);
        curl_close($c);

        Log::info('createToken -> ' . $json);
        $response = json_decode($json);

        return $response->access_token;
    }

    public static function generateJobJSON($app_id, $gw_storage_staging, $inputFolder, $gw_storage_archiving)
    {
        $notification_url = Config::get('services.agave.gw_notification_url');

        $str = <<<STR
{
    "name": "jerome_job1",
    "appId": "$app_id",

    "parameters":{
        "param1":"junction_nt_length"
    },

    "inputs":{
        "file1":"agave://$gw_storage_staging/${inputFolder}/data.csv.zip"
    },

    "maxRunTime":"00:10:00",

    "archive": true,
    "archiveSystem": "$gw_storage_archiving",
    "archivePath": "${inputFolder}",

    "notifications":
    [
        {
            "url": "$notification_url/agave/update-status/\${JOB_ID}/\${JOB_STATUS}",
            "event": "*",
            "persistent": true
        }
    ]
}
STR;
        Log::info('json file -> ' . $str);

        return $str;
    }

    public static function submitJob($token, $job_json)
    {
        $c = curl_init();

        // url
        curl_setopt($c, CURLOPT_URL, 'https://agave.iplantc.org/jobs/v2/?pretty=true');

        // headers
        $h = [];
        $h[0] = 'Content-type: multipart/form-data';
        $h[1] = 'Authorization: Bearer ' . $token;
        curl_setopt($c, CURLOPT_HTTPHEADER, $h);

        // POST
        curl_setopt($c, CURLOPT_POST, true);

        // put json in tmp file
        $f = tempnam(sys_get_temp_dir(), 'agave_job_');
        file_put_contents($f, $job_json);
        $post = [
            'fileToUpload'=>'@' . $f,
        ];
        $post_data['fileToUpload'] = curl_file_create($f);
        curl_setopt($c, CURLOPT_POSTFIELDS, $post_data);

        // return output instead of displaying it
        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);

        $json = curl_exec($c);
        curl_close($c);
        unlink($f);

        Log::info('submitJob -> ' . $json);
        $response = json_decode($json);

        return $response->result->id;
    }

    public function updateStatus($str)
    {
        $status = 0;
        $progress = 20;

        switch ($str) {
            case 'CREATED':
                $progress = 0;
                break;
            case 'WAITING':
                $progress = 3;
                break;
            case 'FEDERATING DATA':
                $progress = 5;
                break;
            case 'SENDING JOB TO AGAVE':
                $progress = 10;
                break;
            case 'JOB ACCEPTED BY AGAVE. PENDING.':
                $progress = 20;
                break;
            case 'PENDING.':
                $progress = 25;
                break;
            case 'PROCESSING_INPUTS':
                $progress = 30;
                break;
            case 'STAGING_INPUTS':
                $progress = 40;
                break;
            case 'STAGED':
                $progress = 45;
                break;
            case 'SUBMITTING':
                $progress = 50;
                break;
            case 'STAGING JOB':
                $progress = 55;
                break;
            case 'QUEUED':
                $progress = 60;
                break;
            case 'RUNNING':
                $progress = 70;
                $status = 1;
                break;
            case 'CLEANING_UP':
                $progress = 80;
                $status = 1;
                break;
            case 'ARCHIVING':
                $progress = 90;
                $status = 1;
                break;
            case 'FINISHED':
                $progress = 100;
                $status = 2;
                break;
            case 'ARCHIVING_FAILED':
                $progress = 100;
                $status = 3;
                break;
            case 'FAILED':
                $progress = 100;
                $status = 3;
                break;
        }

        $this->agave_status = $str;
        $this->status = $status;
        $this->progress = $progress;

        $this->save();

        // create job step for job history
        JobStep::add($this->id, $str);
    }

    public static function get($id, $user_id)
    {
        $job = static::where(['user_id' => $user_id, 'id' => $id])->first();

        return $job;
    }

    public function totalTime()
    {
        if ($this->status < 2) { // is job still running?
            $to = Carbon::now();
        } else {
            $lastJobStep = JobStep::findLastForJob($this->id);
            $to = $lastJobStep->updated_at;
        }

        $firstJobStep = JobStep::findFirstForJob($this->id);
        $from = $firstJobStep->created_at;

        return $to->diffForHumans($from, true);
    }
}
