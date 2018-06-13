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
}
