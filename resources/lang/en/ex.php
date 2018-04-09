<?php

use App\FieldName;

$data = [];

$l = FieldName::all(['ir_id', 'airr_example'])->toArray();
foreach ($l as $t) {
    $data[$t['ir_id']] = trim($t['airr_example']);
}

// var_dump($data);die();

return $data;
