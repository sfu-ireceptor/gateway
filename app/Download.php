<?php

namespace App;

use App\Jobs\DownloadSequences;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

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

    public function isIncomplete()
    {
        return $this->incomplete;
    }

    public function createdAt()
    {
        return Carbon::parse($this->created_at)->format('D M j, Y');
    }

    public function createdAtShort()
    {
        return Carbon::parse($this->created_at)->format('D, F j');
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

    public function expiresAt()
    {
        return Carbon::parse($this->file_url_expiration)->format('D, F j');
    }

    public function expiresAtRelative()
    {
        return Carbon::parse($this->file_url_expiration)->diffForHumans();
    }

    public function duration()
    {
        return $this->end_date->diff($this->start_date);
    }

    public function durationHuman()
    {
        $to = Carbon::parse($this->end_date);
        if ($to == null) {
            $to = Carbon::now();
        }

        return $to->diffForHumans($this->start_date, true);
    }

    public function queuePosition()
    {
        $n = static::where('id', '<', $this->id)->whereIn('status', [self::STATUS_QUEUED])->count();

        return $n + 1;
    }

    public function isExpired()
    {
        $now = Carbon::now();
        $diff_seconds = $now->diffInSeconds($this->file_url_expiration, false);

        return $diff_seconds < 0;
    }

    public static function start_sequence_download($query_id, $username, $page_url, $nb_items)
    {
        self::start_download($query_id, $username, $page_url, $nb_items, 'sequence');
    }

    public static function start_clone_download($query_id, $username, $page_url, $nb_items)
    {
        self::start_download($query_id, $username, $page_url, $nb_items, 'clone');
    }

    public static function start_cell_download($query_id, $username, $page_url, $nb_items)
    {
        self::start_download($query_id, $username, $page_url, $nb_items, 'cell');
    }

    public static function start_download($query_id, $username, $page_url, $nb_items, $type = 'sequence')
    {
        // Determine the queue to use.
        $cell_large_download_limit = config('ireceptor.cell_large_download_limit');
        $clone_large_download_limit = config('ireceptor.clone_large_download_limit');
        $sequence_large_download_limit = config('ireceptor.sequence_large_download_limit');

        $queue = 'short-downloads';
        if ($type == 'sequence' && $nb_items > $sequence_large_download_limit) {
            $queue = 'long-downloads';
        } else if ($type == 'clone' && $nb_items > $clone_large_download_limit) {
            $queue = 'long-downloads';
        } else if ($type == 'cell' && $nb_items > $cell_large_download_limit ) {
            $queue = 'long-downloads';
        }

        // create new local job
        $lj = new LocalJob($queue);
        $lj->user = $username;
        $lj->description = $type . ' download';
        $lj->save();

        // create new download
        $d = new Download();
        $d->username = $username;
        $d->setQueued();
        $d->page_url = $page_url;
        $d->nb_sequences = $nb_items;
        $d->save();

        // queue local job
        $localJobId = $lj->id;
        try {
            DownloadSequences::dispatch($username, $localJobId, $query_id, $page_url, $nb_items, $d, $type)->onQueue($queue);
        } catch (\Exception $e) {
            \Log::error('Download could not be queued:');
            Log::error($e);
            $lj->setFailed();
            $d->setFailed();
            $d->save();
            abort(500, 'Download could not be queued');
        }
    }
}
