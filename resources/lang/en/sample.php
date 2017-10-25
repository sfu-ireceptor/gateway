<?php

use App\SampleField;

$data = [];

$l = SampleField::all(['ir_id', 'ir_full'])->toArray();
foreach ($l as $t) {
	$data[$t['ir_id']] = $t['ir_full'];
}

return $data;
