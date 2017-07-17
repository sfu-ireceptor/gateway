<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class JobStep extends Model
{
    protected $table = 'job_step';
    protected $guarded = ['id'];

    public static function findLastForJob($job_id)
    {
        $step = static::where('job_id', '=', $job_id)->orderBy('id', 'desc')->first();

        return $step;
    }

    public static function findFirstForJob($job_id)
    {
        $step = static::where('job_id', '=', $job_id)->orderBy('id', 'asc')->first();

        return $step;
    }

    public static function add($job_id, $status)
    {
        // update previous step date
        $previousStep = static::findLastForJob($job_id);
        if ($previousStep != null) {
            if ($previousStep->status == $status) {
                // if status is the same as the previous step, no need to create a new step
                return $previousStep;
            }

            $previousStep->touch();
        }

        // create new step
        $step = new self();
        $step->job_id = $job_id;
        $step->status = $status;
        $step->save();

        return $step;
    }

    public static function findAllForJob($job_id)
    {
        $l = static::where('job_id', '=', $job_id)->orderBy('id', 'desc')->get();

        return $l;
    }

    // ***************************************************************************
    // dynamic attributes

    public function duration()
    {
        return $this->updated_at->diff($this->created_at);
    }

    public function isLast()
    {
        $last = self::findLastForJob($this->job_id);
        if ($last->id == $this->id) {
            return true;
        } else {
            return false;
        }
    }

    public function durationHuman()
    {
        if ($this->updated_at == $this->created_at && $this->isLast()) {
            $job = Job::find($this->job_id);
            if ($job->status < 2) { // is job still running?
                $from = Carbon::now();
            } else {
                $from = $this->created_at;
            }
        } else {
            $from = $this->updated_at;
        }

        if ($from->diffInSeconds($this->created_at) < 5) {
            return '';
        }

        return $from->diffForHumans($this->created_at, true);
    }

    public function statusHuman()
    {
        return str_replace('_', ' ', $this->status);
    }

    public function durationSeconds()
    {
        if ($this->updated_at == $this->created_at && $this->isLast()) {
            $job = Job::find($this->job_id);
            if ($job->status < 2) { // is job still running?
                $from = Carbon::now();
            } else {
                $from = $this->created_at;
            }
        } else {
            $from = $this->updated_at;
        }

        if ($from->diffInSeconds($this->created_at) < 5) {
            return '';
        }

        return $from->diffInSeconds($this->created_at, true);
    }

    public function createdAt()
    {
        return Carbon::parse($this->created_at)->format('D j, Y H:i');
    }
}
