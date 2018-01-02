<?php


use Carbon\Carbon;

if (! function_exists('dir_to_array')) {
    function dir_to_array($dir)
    {
        $result = [];

        $cdir = scandir($dir);
        foreach ($cdir as $key => $value) {
            if (! in_array($value, ['.', '..'])) {
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
    function human_date_time($d)
    {
        if ($d == '0000-00-00 00:00:00') {
            return '';
        }

        return Carbon::parse($d)->format('D M j H:i');
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
