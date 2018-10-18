<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RestServiceGroup extends Model
{
    protected $table = 'rest_service_group';

    protected $fillable = [
        'code', 'name',
    ];

    public static function nameForCode($code)
    {
        if ($code != null) {
            $rsg = self::where('code', $code)->first();

            if ($rsg != null) {
                return $rsg['name'];
            }
        }

        return '';
    }
}
