<?php

use Carbon\Carbon;
use Illuminate\Support\Arr;

if (! function_exists('dir_to_array')) {
    function dir_to_array($dir)
    {
        $result = [];

        $cdir = scandir($dir);
        foreach ($cdir as $key => $value) {
            // don't show hidden files
            if (! in_array($value, ['.', '..']) && $value[0] != '.') {
                if (is_dir($dir . DIRECTORY_SEPARATOR . $value)) {
                    $result[$value] = dir_to_array($dir . DIRECTORY_SEPARATOR . $value);
                } else {
                    $result[] = $dir . '/' . $value;
                }
            }
        }

        return $result;
    }
}

if (! function_exists('array_to_html')) {
    function array_to_html($t)
    {
        $s = '<ul>';
        foreach ($t as $k => $v) {
            $s .= '<li>';
            if (is_array($v)) {
                $s .= $k;
                $s .= array_to_html($v);
            } else {
                $s .= '<a href="/' . $v . '">' . basename($v) . '</a>';
            }
            $s .= '</li>';
        }
        $s .= '</ul>';

        return $s;
    }
}

if (! function_exists('dir_to_html')) {
    function dir_to_html($dir)
    {
        $t = dir_to_array($dir);

        return array_to_html($t);
    }
}

if (! function_exists('human_date_time')) {
    function human_date_time($d, $format = 'D M j H:i')
    {
        if ($d == '0000-00-00 00:00:00') {
            return '';
        }

        return Carbon::parse($d)->format($format);
    }
}

// Convert an array's keys using a mapping array
//  $data = [project_id => 1, ...]
//  $mapping = [['v1' => 'project_id', 'airr' => 'study_id', ...], ...]
//  returns [study_id => 1, ...]
if (! function_exists('convert_array_keys')) {
    function convert_array_keys($data, $mapping, $from, $to)
    {
        $t = [];
        foreach ((array) $data as $key => $value) {
            $converted = false;

            foreach ($mapping as $m) {
                if (isset($m[$from]) && $m[$from] == $key) {
                    if (isset($m[$to])) {
                        $t[$m[$to]] = $value;
                        $converted = true;
                        break;
                    }
                }
            }

            // no mapping found for this field name
            if ($converted == false) {
                $t[$key] = $value;
            }
        }

        return $t;
    }
}

// ditto for a list of arrays
if (! function_exists('convert_arrays_keys')) {
    function convert_arrays_keys($data, $mapping, $from, $to)
    {
        $t = [];
        foreach ($data as $d) {
            $t[] = convert_array_keys($d, $mapping, $from, $to);
        }

        return $t;
    }
}

// convert to human-friendly number
// ex: 1706325 -> 1.7 million
if (! function_exists('human_number')) {
    // from https://stackoverflow.com/a/36365553/91225
    function human_number($num)
    {
        if (is_numeric($num)) {
            $x = round($num);
            if ($x != null) {
                $x_number_format = number_format($x);
                $x_array = explode(',', $x_number_format);
                $x_count_parts = count($x_array) - 1;
                $x_parts = ['thousand', 'million', 'billion', 'trillion'];

                $n = $x_array[0];
                $x_display = $n;
                if ($num > 1000000) {
                    $n2 = (int) $x_array[1][0] !== 0 ? '.' . $x_array[1][0] : '';
                    $x_display .= $n2;
                }

                // Catch the case where the number is less that 1000
                if ($x_count_parts > 0) {
                    $x_display .= ' ';
                    $x_display .= $x_parts[$x_count_parts - 1];
                }

                return $x_display;
            } else {
                return $num;
            }
        }
    }
}

// convert to human-friendly time duration
// ex: 65 -> 1min 5s
if (! function_exists('secondsToTime')) {
    function secondsToTime($inputSeconds)
    {
        $secondsInAMinute = 60;
        $secondsInAnHour = 60 * $secondsInAMinute;
        $secondsInADay = 24 * $secondsInAnHour;

        // Extract days
        $days = floor($inputSeconds / $secondsInADay);

        // Extract hours
        $hourSeconds = $inputSeconds % $secondsInADay;
        $hours = floor($hourSeconds / $secondsInAnHour);

        // Extract minutes
        $minuteSeconds = $hourSeconds % $secondsInAnHour;
        $minutes = floor($minuteSeconds / $secondsInAMinute);

        // Extract the remaining seconds
        $remainingSeconds = $minuteSeconds % $secondsInAMinute;
        $seconds = ceil($remainingSeconds);

        // Format and return
        $timeParts = [];
        $sections = [
            ' days' => (int) $days,
            'h' => (int) $hours,
            'min' => (int) $minutes,
            's' => (int) $seconds,
        ];

        foreach ($sections as $name => $value) {
            if ($value > 0) {
                $timeParts[] = $value . '' . $name;
            }
        }

        return implode(' ', $timeParts);
    }
}

if (! function_exists('human_filesize')) {
    function human_filesize($bytes, $decimals = 1)
    {
        if (! is_numeric($bytes)) {
            $bytes = filesize($bytes);
        }

        if ($bytes == null) {
            return '';
        }
        $sz = 'BKMGTP';
        $factor = floor((strlen($bytes) - 1) / 3);

        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . ' ' . @$sz[$factor] . 'B';
    }
}

if (! function_exists('url_path')) {
    function url_path($url)
    {
        $t = parse_url($url);

        $s = $t['path'];
        if (isset($t['query'])) {
            $s .= '?' . $t['query'];
        }

        return $s;
    }
}

// takes an object
// returns the same object with any array property converted to a string (JSON)
if (! function_exists('convert_arrays_to_strings')) {
    function convert_arrays_to_strings($o)
    {
        foreach ($o as $k => $v) {
            if (is_array($v)) {
                $o->$k = json_encode($v);
            }
        }

        return $o;
    }
}

// test if string is URL
if (! function_exists('is_url')) {
    function is_url($str)
    {
        return filter_var($str, FILTER_VALIDATE_URL);
    }
}

// return URL hostname
// Ex: http://php.net/manual/en/function.parse-url.php -> php.net
if (! function_exists('url_hostname')) {
    function url_hostname($url)
    {
        return parse_url($url, PHP_URL_HOST);
    }
}

// remove "http://www." from an url
if (! function_exists('remove_url_prefix')) {
    function remove_url_prefix($url)
    {
        $t = parse_url($url);

        $host = $t['host'];
        $path = $t['path'];
        $str = $host . $path;

        if (isset($t['query'])) {
            $str .= '?' . $t['query'];
        }

        if (isset($t['fragment'])) {
            $str .= '#' . $t['fragment'];
        }

        if (starts_with($str, 'www.')) {
            $str = str_replace('www.', '', $str);
        }

        return $str;
    }
}

// get class name without namespace
if (! function_exists('get_class_name')) {
    function get_class_name($obj)
    {
        $classname = get_class($obj);
        if ($pos = strrpos($classname, '\\')) {
            return substr($classname, $pos + 1);
        }

        return $pos;
    }
}

// get list of fields of object/array using dot notation
// (recursive)
if (! function_exists('get_data_fields')) {
    function get_data_fields($o)
    {
        $keys = [];
        foreach ((array) $o as $k => $v) {
            if (is_array($v) || is_object($v)) {
                // find sub keys
                $l = get_data_fields($v);

                // append parent key to sub keys
                $l2 = [];
                foreach ($l as $k2 => $v2) {
                    $l2[] = $k . '.' . $v2;
                }

                $keys = array_merge($keys, $l2);
            }
            // add key
            $keys[] = $k;
        }

        return $keys;
    }
}

// add data to an object using dot notation.
// create objects from any intermediate structure (instead of arrays)
if (! function_exists('data_set_object')) {
    /**
     * Set an item on an object using dot notation.
     *
     * @param  mixed  $target
     * @param  string|array  $key
     * @param  mixed  $value
     * @param  bool  $overwrite
     * @return mixed
     */
    function data_set_object(&$target, $key, $value, $overwrite = true)
    {
        $segments = is_array($key) ? $key : explode('.', $key);

        if (($segment = array_shift($segments)) === '*') {
            if (! Arr::accessible($target)) {
                $target = [];
            }

            if ($segments) {
                foreach ($target as &$inner) {
                    data_set($inner, $segments, $value, $overwrite);
                }
            } elseif ($overwrite) {
                foreach ($target as &$inner) {
                    $inner = $value;
                }
            }
        } elseif (Arr::accessible($target)) {
            if ($segments) {
                if (! Arr::exists($target, $segment)) {
                    if (is_numeric($segment)) {
                        $target[$segment] = new \stdClass();
                    } else {
                        $target->{$segment} = new \stdClass();
                    }
                }

                data_set_object($target[$segment], $segments, $value, $overwrite);
            } elseif ($overwrite || ! Arr::exists($target, $segment)) {
                if (is_object($target)) {
                    $target->{$segment} = $value;
                } else {
                    $target[$segment] = $value;
                }
            }
        } elseif (is_object($target)) {
            if ($segments) {
                if (is_numeric($segment)) {
                    $target = [];
                    $target[$segment] = new \stdClass();
                    data_set_object($target[$segment], $segments, $value, $overwrite);

                    return $target;
                }

                if (! isset($target->{$segment})) {
                    $target->{$segment} = new \stdClass();
                }

                data_set_object($target->{$segment}, $segments, $value, $overwrite);
            } elseif ($overwrite || ! isset($target->{$segment})) {
                $target->{$segment} = $value;
            }
        } else {
            $target = new \stdClass();

            if ($segments) {
                data_set_object($target[$segment], $segments, $value, $overwrite);
            } elseif ($overwrite) {
                $target[$segment] = $value;
            }
        }

        return $target;
    }
}

// converts object to JSON string, encoding single and double quotes
// so the JSON can be put in an HTML attribute
if (! function_exists('object_to_json_for_html')) {
    function object_to_json_for_html($o)
    {
        return htmlentities(json_encode($o), ENT_QUOTES, 'UTF-8');
    }
}
