<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

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
            case 'SENDING JOB FOR ANALYSIS':
                $progress = 10;
                break;
            case 'JOB ACCEPTED FOR ANALYSIS. PENDING.':
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
            case 'STAGING_JOB':
                $progress = 50;
                break;
            case 'SUBMITTING_JOB':
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
            case 'STOPPED':
                $progress = 100;
                $status = 3;
                break;
            case 'INTERNAL_ERROR':
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

    public function getJobID()
    {
        // Return the ID of the internal processing ID (not $this->id)
        return $this->agave_id;
    }

    public function getJobStatus()
    {
        // Return the internal status of the job
        return $this->agave_status;
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
        // If we have a job without a JobStep handle the case elegantly.
        if ($firstJobStep != null) {
            $from = $firstJobStep->created_at;

            return $to->diffForHumans($from, true);
        } else {
            return 'Unknown';
        }
    }
}
