<?php

namespace App\Http\Controllers;

use App\Job;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TestController extends Controller
{
    public function getIndex()
    {
        echo 'index';

        // try {
        //     $client = new \GuzzleHttp\Client();

        // $response = $client->request('POST', $url, $data);
        //     // $response = $client->request('POST', $url, $data);
        //     $contents = (string) $response->getBody();
        //     echo $contents;

        // } catch (\Exception $exception) {
        //     Log::error('----------------');
        //     // $response = $exception->getResponse()->getReasonPhrase();
        //     // Log::error($response);
        //     // Log::error($exception->getResponse()->getBody()->getContents());
        //     // ddd($e->getResponse()->getBody()->getContents());
        // }

// //         $to      = 'jlj7@sfu.ca';
// //      $subject = 'the subject';
// // $message = 'hello';
// // $headers = 'From: webmaster@example.com' . "\r\n" .
// //     'Reply-To: webmaster@example.com' . "\r\n" .
// //     'X-Mailer: PHP/' . phpversion();

// // mail($to, $subject, $message, $headers);

//         //         Mail::send(['text' => 'emails.auth.accountCreated'], $t, function ($message) use ($t) {
//         //     $message->to($t['email'])->subject('iReceptor account');
//         // });

//         Mail::send(array('text' => 'emails.test'), [], function($message)
//         {
//             $message->to('')->subject('just a test');
//             echo "ok";
//         });

        // $job = new Job;
        // $job->updateStatus('PENDING');
        // $job->save();

        // echo json_decode(config('services.agave.system_deploy.auth'));
        // var_dump(json_decode(config('services.agave.system_deploy.auth'), true));

        // $c = config('services.agave.system_deploy.auth');
        // $c = rtrim($c, "'");
        // $c = ltrim($c, "'");
        // var_dump(json_decode($c, true));

        // $tables = \DB::connection()->getDoctrineSchemaManager()->listTableNames();
        // foreach ($tables as $table) {
        //     echo $table."\n";
        //     // code...
        // }
    }

    public function index2(Request $request)
    {
        Log::info('ok!');
        echo $request->header('Content-Type') . "\n";
        echo $request->header('User-Agent') . "\n";
        echo $request->method() . "\n";
        var_dump($request->header()) . "\n";
        var_dump($request->file()) . "\n";
        var_dump($request->all());
    }
}
