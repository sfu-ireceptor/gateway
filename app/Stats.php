<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class Stats extends Model {

	protected $table = 'stats';
	protected $fillable = array('start_date', 'end_date', 'nb_requests');

	function __construct() {
		$start_date = new Carbon('first day of this month');
		$this->start_date = $start_date;

		$end_date = new Carbon('last day of this month');
		$this->end_date = $end_date;

		$this->nb_requests = 0;
	}

    public function startDateIso8601()
    {
    	$d = new Carbon($this->start_date, 'UTC');
    	return $d->toDateString() . 'T'  . $d->toTimeString() . 'Z';
    }

	public static function currentStats()
	{
    	$start_date = new Carbon('first day of this month');
		$s = static::where(['start_date' => $start_date->toDateString()])->first();

		if ($s == NULL) {
			$s = new Stats;
			$s->save();
		}

		return $s;
	}

    public static function incrementNbRequests()
    {
    	$s = static::currentStats();

		$s->nb_requests++;
		$s->save();
	}

	public static function nbRequests()
	{
    	$s = static::currentStats();
		return $s->nb_requests;
	}


}