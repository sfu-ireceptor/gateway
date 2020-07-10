<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Download extends Model
{
    const STATUS_QUEUED = 'Queued';
    const STATUS_RUNNING = 'Running';
    const STATUS_CANCELED = 'Canceled';
    const STATUS_DONE = 'Done';
    const STATUS_FAILED = 'Failed';

    protected $table = 'download';
    protected $guarded = ['id'];

    public function setQueued()
    {
        $this->status = self::STATUS_QUEUED;
    }

    public function setRunning()
    {
        $this->status = self::STATUS_RUNNING;
    }

    public function setCanceled()
    {
        $this->status = self::STATUS_CANCELED;
    }

    public function setDone()
    {
        $this->status = self::STATUS_DONE;
    }

    public function setFailed()
    {
        $this->status = self::STATUS_FAILED;
    }

    public function isQueued()
    {
        return $this->status == self::STATUS_QUEUED;
    }

    public function isRunning()
    {
        return $this->status == self::STATUS_RUNNING;
    }

    public function isCanceled()
    {
        return $this->status == self::STATUS_CANCELED;
    }

    public function isDone()
    {
        return $this->status == self::STATUS_DONE;
    }

    public function isFailed()
    {
        return $this->status == self::STATUS_FAILED;
    }

    public function createdAt()
    {
        return Carbon::parse($this->created_at)->format('D M j, Y');
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

    public function duration()
    {
        return $this->end_date->diff($this->start_date);
    }

     public function durationHuman()
    {
        $to = Carbon::parse($this->end_date);
        if ($to == NULL) {
            $to = Carbon::now();
        } 

        return $to->diffForHumans($this->start_date, true);
    }
}
