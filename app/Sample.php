<?php

namespace App;

class Sample
{
    public static function public_samples()
    {
        return CachedSample::cached();
    }

    public static function metadata()
    {
        return CachedSample::metadata();
    }

    public static function find($filters, $username, $query_log_id = null)
    {
        return RestService::samples($filters, $username, $query_log_id);
    }
}
