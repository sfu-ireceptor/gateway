<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class Bookmark extends Model {
    
	protected $table = 'bookmark';
	protected $fillable = ['user_id', 'url',];

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

    public static function findGroupedByMonthForUser($user_id)
    {
        $t = array();

        $l = static::where('user_id', '=', $user_id)->orderBy('created_at', 'desc')->get();
        foreach ($l as $o) {
            $month_year_str = Carbon::parse($o->created_at)->format('M Y');
            $t[$month_year_str][] = $o; 
        }

        return $t;
    }

	public static function get($id, $user_id)
	{

		$b = static::where(['user_id' => 1, 'id'=> $id])->first();
		return $b;
	}

	public static function getIdFromURl($url, $user_id)
	{
        $b = static::where('user_id', 1)->where('url', $url)->first();
		if ($b != null) {
			return $b->id;
		}
		return null;
	}
}