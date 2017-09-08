<?php

namespace App\Http\Controllers;

use App\Job;
use App\Agave;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class TestController extends Controller
{
    public function getIndex()
    {

// $process = new Process('ls -lsa');
        // $process->run();

        // // executes after the command finishes
        // if (!$process->isSuccessful()) {
//     throw new ProcessFailedException($process);
        // }

        // echo $process->getOutput();

        // $deploy_script_path = base_path('deploy.sh');

        // $process = new Process($deploy_script_path);
        // $process->run(function ($type, $buffer) {
//     echo $buffer;
        // });

        $root_path = base_path();

        $process = new Process('cd ' . $root_path . '; ./deploy.sh');
        $process->run(function ($type, $buffer) {
            echo $buffer;
        });
        // echo "aa";
        die();

        echo Hash::make('0bed3fd19bc087e03ca5e99f98f7e976d330fbf1b966e23de17b41534a942cb6');
        die();

        $agave = new Agave;
        $token = $agave->getAdminToken();

        $user = $agave->getUserWithEmail('jlj7@sfu.ca', $token);
        if ($user == null) {
            echo 'ok';
            die();
        }
        var_dump($user);

        die();

        // echo 'index';
        $email = 'jlj7@sfu.ca';

        $table = 'password_resets';

        $hashKey = config('app.key');
        $token = hash_hmac('sha256', Str::random(40), $hashKey);
        // echo $token;

        $hashedToken = Hash::make($token);
        // echo $hashedToken;

        DB::table($table)->where('email', 'jlj7@sfu.ca')->delete();
        DB::table($table)->insert([
            'email' => $email,
            'token' => $hashedToken,
            'created_at' => Carbon::now(),
        ]);

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
