<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class TestController extends Controller
{
    public function getIndex(Request $request)
    {
        echo 'aafs';
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
