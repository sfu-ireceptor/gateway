<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class LocalJob extends Model {

	protected $table = 'local_job';
	protected $fillable = ['queue', 'description', 'status', 'submitted', 'start', 'end', 'user'];

	function __construct($queue = 'default') {
		$this->queue = $queue;

		$now = new Carbon('now');
		$this->submitted = $now;

		$this->status = 'Pending';
	}

	public function submitted()
    {
        return human_date_time($this->submitted);    
    }

	public function start()
    {
        return human_date_time($this->start);    
    }

	public function end()
    {
        return human_date_time($this->end);    
    }

    public function progress()
    {
    	if($this->status == 'Pending')
    	{
    		return 0;
    	}
    	else if ($this->status == 'Running')
    	{
    		return 50;
    	}
    	else {
    		return 100;
    	}
    }

	function setRunning() {
		$now = new Carbon('now');
		$this->start = $now;
		$this->status = 'Running';
		$this->save();
	}

	function setFinished() {
		$now = new Carbon('now');
		$this->end = $now;
		$this->status = 'Finished';		
		$this->save();
	}

	function setFailed() {
		$now = new Carbon('now');
		$this->end = $now;
		$this->status = 'Failed';		
		$this->save();
	}

    public static function findLast($queue) {
    	$l = static::where('queue', '=', $queue)->orderBy('submitted', 'desc')->limit(100)->get();
    	return $l;
	}

}