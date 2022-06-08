<?php

namespace App\Http\Controllers;

use App\Agave;
use App\Download;
use App\FieldName;
use App\Job;
use App\RestService;
use App\RestServiceGroup;
use App\Sample;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class TestController extends Controller
{
    public function getIndex(Request $request)
    {
        echo "aafs";    
    }

    public function index2(Request $request)
    {
      $defaults = [];
        $defaults['base_uri'] = config('app.url');
        $defaults['verify'] = false;    // accept self-signed SSL certificates
        $defaults['ssl_key'] = [storage_path() . '/config/domain.crt', 'password.key'];

        try {
            $client = new \GuzzleHttp\Client($defaults);

            $response = $client->get('test');
            $body = $response->getBody();

            echo $body;
        } catch (\Exception $e) {
            dd($e);
            echo 'error';
        }
    }


    public function email()
    {
        Mail::send(['text' => 'emails.test'], [], function ($message) {
            $message->to('jlj7@sfu.ca')->subject('Test Email');
        });
        echo 'done';
    }

    public function phpinfo()
    {
        phpinfo();
    }
}
