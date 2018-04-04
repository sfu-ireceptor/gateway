<?php

use App\FieldName;

$data = [];

$l = FieldName::all(['ir_id', 'ir_v2'])->toArray();
foreach ($l as $t) {
    $data[$t['ir_id']] = trim($t['ir_v2']);
}

return $data;
