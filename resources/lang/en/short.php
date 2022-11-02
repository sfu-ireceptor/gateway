<?php

use App\FieldName;

$data = [];

$api_version = config('ireceptor.default_api_version');
$l = FieldName::all(['ir_id', 'ir_short', 'api_version'])->where('api_version', $api_version)->toArray();
foreach ($l as $t) {
    $str_short = trim($t['ir_short']);

    // if no "short" value is defined, return "ir_id" key
    if ($str_short == '') {
        $str_short = $t['ir_id'];
    }

    $data[$t['ir_id']] = $str_short;
}

return $data;
