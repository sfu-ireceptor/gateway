<?php

use App\SampleField;

$data = [];

$l = SampleField::all(['ir_id', 'ir_short'])->toArray();
foreach ($l as $t) {
    $data[$t['ir_id']] = $t['ir_short'];
}

// var_dump($data);die();

return $data;
