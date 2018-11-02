<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\TrimStrings as BaseTrimmer;

class TrimStrings extends BaseTrimmer
{
    /**
     * The names of the attributes that should not be trimmed.
     *
     * @var array
     */
    protected $except = [
        'password',
        'password_confirmation',
        'sex',
        'organism',
        'ethnicity',
        'cell_subset',
        'tissue',
        'template_class',
    ];

    // override cleanValue() and cleanArray() from TransformsRequest so array attributes can also be excluded
    protected function cleanValue($key, $value)
    {
        if (is_array($value)) {
            return $this->cleanArray($value, $key);
        }

        return $this->transform($key, $value);
    }

    protected function cleanArray(array $data, $parentKey = '')
    {
        return collect($data)->map(function ($value, $key) use ($parentKey) {
            if (is_numeric($key) && $parentKey != '') {
                $key = $parentKey;
            }

            return $this->cleanValue($key, $value);
        })->all();
    }
}
