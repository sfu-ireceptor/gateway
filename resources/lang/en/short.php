<?php

use App\FieldName;

$data = [];

$l = FieldName::all(['ir_id', 'ir_short'])->toArray();
foreach ($l as $t) {
    $str_short = trim($t['ir_short']);

    // if no "short" value is defined, return "ir_id" key
    if ($str_short == '') {
        $str_short = $t['ir_id'];
    }

    $data[$t['ir_id']] = $str_short;
}

return $data;
