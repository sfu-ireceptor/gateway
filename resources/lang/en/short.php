<?php

use App\FieldName;

$data = [];

$l = FieldName::all(['ir_id', 'ir_short'])->toArray();
foreach ($l as $t) {
    $data[$t['ir_id']] = trim($t['ir_short']);
}

// var_dump($data);die();

return $data;
