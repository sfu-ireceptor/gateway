<?php

namespace App\Http\Controllers;

use App\Agave;
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
    public function email()
    {
        Mail::send(['text' => 'emails.test'], [], function ($message) {
            $message->to('jlj7@sfu.ca')->subject('Test Email');
        });
        echo 'done';
    }

    public function printBody()
    {
        echo 'hi there 3';
        // trigger_error('var must be numeric');

        flush();
        ob_flush();
        sleep(10);
        exit(1);
        // header('HTTP/1.1 500 Internal Server Error');
        // die();
        // abort(500, 'yoooooo');
        // header('X-Error-Message: Incorrect username', true, 500);
        // die('Incorrect username');

        echo 'hi there 40';
    }

    public function getIndex(Request $request)
    {
        // dd(1 < 2);

        dd(strcmp('a', 'b'));

        // $s = "5";
        // $t = [$s];
        // dd($t);
        // die();
        $username = auth()->user()->username;
        Sample::cache_sequence_counts($username);

        die();
        $s = 'V Gene and Allele (paired)';
        $s = snake_case($s);
        echo $s;
        die();

        // header("Content-type: text/csv");
        // header("Content-Disposition: attachment; filename=file.csv");

        $columns = ['titi', 'tata'];

        //$f = fopen($file_path, 'w');
        $file = fopen('php://output', 'w');
        fputcsv($file, $columns, "\t");
        exit();

        $data_to_pass = ['a' => 'test'];

        return response(view('about')->with(compact('data_to_pass')), 200, [
            'Content-Type' => 'application/json', // use your required mime type
            'Content-Disposition' => 'attachment; filename="filename.xml"',
        ]);

        echo 'aa';
        die();

        $f = FieldName::getFieldType('sequencing_platform');
        dd($f);

        echo $request->route()->uri;
        die();

        dd(RestService::sequence_count([], 37, '7'));
        die();

        echo App::environment();
        die();
        // throw new \Exception('Yet another error: why??');

        echo starts_with('This is my name', 'Thisf');
        die();

        $str = 'pmid: 25100740"';
        if (! (stripos($str, 'PMID') === false)) {
            echo 'found';
        } else {
            echo 'not found';
        }

        // if (File::exists('/vagrant/ireceptor_gateway/app')) {
        //     echo 'exists';
        // } else {
        //     echo 'doest not exist';
        // }

        die();

        // return response($this->printBody())
        //     ->header('Content-Type', 'text/tsv')
        //     ->header('Content-Disposition', 'attachment;filename="data.tsv"');

        // return response(Sequence::airr_data($params))->header('Content-Type', 'text/tsv')->header('Content-Disposition', 'attachment;filename="data.tsv"');

        die();

        // echo "fdsafads";

        // die();

        // $middleware = new class extends \Illuminate\Foundation\Http\Middleware\TrimStrings {
        //     protected $except = ['field2'];
        // };

        $middleware = new class extends \App\Http\Middleware\TrimStrings {
            protected $except = ['field2'];
        };

        // $t = [
//             'nested' => [
//                 [
//                     'field1' => ' trimmed ',
//                     'field2' => ' not trimmed ',
//                 ],
//             ],
//         ];

        // $t =             [
//                 'field1' => 'value1',
//                 'field2' => 'value2 ',
//                 'field3' => ' value3',
//                 'field4' => ' value4 ',
//                 'field5' => '  value5  ',
//             ];

        $t = [
            'field1' => ['value1', 'value2'],

            'field2' => ['value1', 'value2 '],
            'field3' => ['value1', ' value2'],
            'field4' => ['value1', ' value2 '],
            'field5' => ['value1', '  value2  '],

            'field6' => ['value1 ', 'value2'],
            'field7' => [' value1', 'value2'],
            'field8' => [' value1 ', 'value2'],
            'field9' => ['  value1  ', 'value2'],

            'field10' => ['  value1  ', '  value2  '],
            'nested' => [
                [
                    'field1' => ' trimmed ',
                    'field2' => ' not trimmed ',
                ],
            ],
        ];

        $request = new \Illuminate\Http\Request($t);

        $middleware->handle($request, function ($request) {
            dump($request->input());
        });

        die();

        echo RestServiceGroup::nameForCode('ipa');

        die();

        $o = new \stdClass();
        $o->titi = 'aa';
        $o->tata = ['fsdfsd', 'fsdfa'];
        $o->ttoto = ['fsdfsd', ['fsdfa', 'fsdfds']];
        $o->ttotofds = 5;
        $o->dfs = null;
        // dd($o);

        convert_arrays_to_strings($o);
        // foreach ($o as $k => $v) {
        //     if(is_array($v)) {
        //         $o->$k = json_encode($v);
        //     }
        // }
        dd($o);

        die();

        echo human_filesize('/var/www/ireceptor_gateway/storage/app/public/ir_2018-04-03_2239_5ac402badc061/scott-lab.tsv');

        die();

        foreach (RestService::all() as $rs) {
            echo str_slug($rs->name) . ' - ';
        }
        die();

        // create receiving folder
        $storage_folder = storage_path() . '/app/public/';
        $now = time();
        $time_str = date('Y-m-d_Hi', $now);
        $folder_name = 'ir_' . $time_str . '_' . uniqid();
        $folder_path = $storage_folder . $folder_name;
        File::makeDirectory($folder_path, 0777, true, true);

        $date_str_human = date('M j, Y', $now);
        $time_str_human = date('H:i T', $now);
        $s = 'Downloaded by toto on ' . $date_str_human . ' at ' . $time_str_human;
        echo $s;

        $file = $folder_path . '/info.txt';
        // file_put_contents($file, "test");

        die();

        // // Initialize the Client
        // $client = new \GuzzleHttp\Client(['base_uri' => 'http://gw.local/test2']);

        // $projects = ['aa', 'bb'];
        // $requests = [];
        // foreach ($projects as $project) {
        //     $requests[] = new \GuzzleHttp\Psr7\Request('GET', '');
        // }

        // // Perform the actual requests
        // $responses = \GuzzleHttp\Pool::batch($client, $requests, ['concurrency' => 5]);

        // foreach ($responses as $response) {
        //     var_dump($response);
        // }

        // die();

        $client = new \GuzzleHttp\Client(['timeout' => 20]);

        $iterator = function () use ($client) {
            $index = 0;
            while (true) {
                if ($index === 2) {
                    break;
                }
                $index++;

                $wait = $index + 2;
                $url = 'http://gw.local/wait/' . $wait;
                // $request = new \GuzzleHttp\Request('GET', $url, []);

                Log::info('starting query to $url');

                yield $client
                    ->requestAsync('GET', $url, [])
                    ->then(function ($response) {
                        Log::info('query is done.');
                        // echo $response->getBody();
                        echo $response->getBody();

                        return ['aa', 'bb'];
                        // return [$response];
                    });
            }
        };

        // $promise = \GuzzleHttp\Promise\each_limit(
        //     $iterator(),
        //     10,  // concurrency,
        //     function ($result, $index) {
        //         /** @var GuzzleHttp\Psr7\Request $request */
        //         // list($request, $response) = $result;
        //         // echo (string)$request->getUri() . 'request completed ' . PHP_EOL;
        //         Log::info('query is done 2.');
        //          echo $result[0]->getBody();
        //         // var_dump($result);
        //         // var_dump($index);
        //     },
        //     function ($result, $index) {
        //         /** @var GuzzleHttp\Psr7\Request $request */
        //         // list($request, $response) = $result;
        //         // echo (string)$request->getUri() . 'request completed ' . PHP_EOL;
        //         Log::info('query is done 3.');
        //     }
        // );

        // $promise = \GuzzleHttp\Promise\each_limit(
        //     $iterator(),
        //     10, // concurrency
        //     function($result, $index) {
        //         // dd($result);
        //     }
        // );

        $results = [];
        $promise = \GuzzleHttp\Promise\each_limit(
            $iterator(),
            10, // concurrency
            function ($result, $index) use (&$results) {
                $results[$index] = $result;
            }
        );

        // $promise = Promise\each_limit($promiseGenerator(), 2, function($value, $idx) use (&$result) {$result[$idx] = $value;});

        $promise->wait();

        var_dump($results);
        echo 'all done';

//         // echo config('app.url');die();
//         $s = 'https://gw.dev/sequences?query_id=1064';

//         // echo str_after($s, config('app.url'));
//         // die();

//         echo url_path($s);
//         die();

//         // $t = parse_url($s);

//         // $s2 = $t['path'];
//         // if(isset($t['query'])) {
//         //     $s2 .= '?' . $t['query']
//         // }
//         // if
//         // $s2 = implode('?', [$t['path'], $t['query']]);
//         //     echo $s2;die();
//         // dd($t);
//         // die();

//         echo human_filesize(filesize('/var/www/ireceptor_gateway/storage/app/public/ir_2018-01-12_0004_5a57fb8312b90.zip'));
//         die();

//         // echo base_path();
//         $s = '/var/www/ireceptor_gateway/storage/app/public/2018-01-10_20-56-11_5a567debd178f/3.csv';
//         echo str_after($s, storage_path('app/public'));
//         die();

//         echo storage_path();
//         die();

//         $client = new \GuzzleHttp\Client();

//         $promise1 = $client->getAsync('http://loripsum.net/api')->then(
//     function ($response) {
//         return $response->getBody();
//     }, function ($exception) {
//         return $exception->getMessage();
//     }
// );

//         $promise2 = $client->getAsync('http://loripsum.net/api')->then(
//     function ($response) {
//         return $response->getBody();
//     }, function ($exception) {
//         return $exception->getMessage();
//     }
// );

//         $response1 = $promise1->wait();
//         $response2 = $promise2->wait();

//         echo $response1;
//         echo $response2;

        // $client = new Client();
        // $v = 'o';

        // $p1 = $client->getAsync('http://loripsum.net/api')->then(
        //     function (ResponseInterface $res) use ($v) {
        //         echo $v . '01';
        //         $v = 'x';
        //         echo $res->getStatusCode() . "\n";
        //         echo $res->getBody();

        //         return 'jjjjjjjj';
        //     }, function (RequestException $e) {
        //         echo $e->getMessage() . "\n";
        //         echo $e->getRequest()->getMethod();
        //     }
        // );

        // $p2 = $client->getAsync('http://loripsum.net/api')->then(
        //     function (ResponseInterface $res) use ($v) {
        //         echo $v . '02';
        //         $v = 'y';
        //         echo $res->getStatusCode() . "\n";
        //     }, function (RequestException $e) {
        //         echo $e->getMessage() . "\n";
        //         echo $e->getRequest()->getMethod();
        //     }
        // );

        // echo 'aaA';
        // echo $p1->wait();
        // $p2->wait();
        // echo 'bb';

//         // $data = ['project_id' => 2, 'subject_id' => 3, 'test' => 4, 'test2' => ''];

//         // echo http_build_query($data, '', '&');
//         // die();

//         // // echo __('sp.sex');

//         // // // echo 'uuuu';
//         // // die();
//         // $data = [];
//         // $data[] = ['project_id' => 1, 'subject_id' => 2];
//         // $data[] = ['project_id' => 2, 'subject_id' => 3, 'test' => 4];

//         // var_dump($data);

//         // echo "\n-------------------------------------\n\n";

//         // $t = FieldName::convertList($data, 'ir_v1', 'ir_v2');
//         // var_dump($t);

//         // die();

//         // // SampleField::init();
//         // // die();

//         // $metadata = Sample::metadata('titi');

//         // var_dump($metadata);
//         // die();

//         // foreach ($metadata['subject_code'] as $k => $v) {
//         //     echo $v . "\n";
//         // }
//         // // var_dump($metadata);
//         // die();

//         // RestService::cacheSamples('titi');
//         // die();

//         // $metadata_data = Sample::metadata2('titi');
//         // var_dump($metadata_data);
//         // die();

//         // Sample::create(['name' => 'John']);
//         // echo 'aa';
//         // die();

//         // echo base64_encode('706d5ebc88cbf4d69d85baecb83e78c2cf4ece7c9c08f5796f89dcb7afdb850b');
//         // die();

//         $message = <<<'EOD'
// {
//   "ref": "refs/heads/master",
//   "before": "bf13e1982e384a723f59dcc59087f65648d6badd",
//   "after": "50c4ad0b5cf9b5048466a6886033742f7dd476ef",
//   "created": false,
//   "deleted": false,
//   "forced": false,
//   "base_ref": null,
//   "compare": "https://github.com/sfu-ireceptor/gateway/compare/bf13e1982e38...50c4ad0b5cf9",
//   "commits": [
//     {
//       "id": "50c4ad0b5cf9b5048466a6886033742f7dd476ef",
//       "tree_id": "a30cf79781662cd2d0e67872912f1047b1d1a3d6",
//       "distinct": true,
//       "message": "minor: just for GitHub webhook testing",
//       "timestamp": "2017-09-08T13:21:18-07:00",
//       "url": "https://github.com/sfu-ireceptor/gateway/commit/50c4ad0b5cf9b5048466a6886033742f7dd476ef",
//       "author": {
//         "name": "Jerome Jaglale",
//         "email": "jerome_jaglale@sfu.ca",
//         "username": "jeromejaglale"
//       },
//       "committer": {
//         "name": "Jerome Jaglale",
//         "email": "jerome_jaglale@sfu.ca",
//         "username": "jeromejaglale"
//       },
//       "added": [

//       ],
//       "removed": [

//       ],
//       "modified": [
//         "app/Http/Controllers/UtilController.php"
//       ]
//     }
//   ],
//   "head_commit": {
//     "id": "50c4ad0b5cf9b5048466a6886033742f7dd476ef",
//     "tree_id": "a30cf79781662cd2d0e67872912f1047b1d1a3d6",
//     "distinct": true,
//     "message": "minor: just for GitHub webhook testing",
//     "timestamp": "2017-09-08T13:21:18-07:00",
//     "url": "https://github.com/sfu-ireceptor/gateway/commit/50c4ad0b5cf9b5048466a6886033742f7dd476ef",
//     "author": {
//       "name": "Jerome Jaglale",
//       "email": "jerome_jaglale@sfu.ca",
//       "username": "jeromejaglale"
//     },
//     "committer": {
//       "name": "Jerome Jaglale",
//       "email": "jerome_jaglale@sfu.ca",
//       "username": "jeromejaglale"
//     },
//     "added": [

//     ],
//     "removed": [

//     ],
//     "modified": [
//       "app/Http/Controllers/UtilController.php"
//     ]
//   },
//   "repository": {
//     "id": 95588395,
//     "name": "gateway",
//     "full_name": "sfu-ireceptor/gateway",
//     "owner": {
//       "name": "sfu-ireceptor",
//       "email": null,
//       "login": "sfu-ireceptor",
//       "id": 29737820,
//       "avatar_url": "https://avatars3.githubusercontent.com/u/29737820?v=4",
//       "gravatar_id": "",
//       "url": "https://api.github.com/users/sfu-ireceptor",
//       "html_url": "https://github.com/sfu-ireceptor",
//       "followers_url": "https://api.github.com/users/sfu-ireceptor/followers",
//       "following_url": "https://api.github.com/users/sfu-ireceptor/following{/other_user}",
//       "gists_url": "https://api.github.com/users/sfu-ireceptor/gists{/gist_id}",
//       "starred_url": "https://api.github.com/users/sfu-ireceptor/starred{/owner}{/repo}",
//       "subscriptions_url": "https://api.github.com/users/sfu-ireceptor/subscriptions",
//       "organizations_url": "https://api.github.com/users/sfu-ireceptor/orgs",
//       "repos_url": "https://api.github.com/users/sfu-ireceptor/repos",
//       "events_url": "https://api.github.com/users/sfu-ireceptor/events{/privacy}",
//       "received_events_url": "https://api.github.com/users/sfu-ireceptor/received_events",
//       "type": "Organization",
//       "site_admin": false
//     },
//     "private": false,
//     "html_url": "https://github.com/sfu-ireceptor/gateway",
//     "description": null,
//     "fork": false,
//     "url": "https://github.com/sfu-ireceptor/gateway",
//     "forks_url": "https://api.github.com/repos/sfu-ireceptor/gateway/forks",
//     "keys_url": "https://api.github.com/repos/sfu-ireceptor/gateway/keys{/key_id}",
//     "collaborators_url": "https://api.github.com/repos/sfu-ireceptor/gateway/collaborators{/collaborator}",
//     "teams_url": "https://api.github.com/repos/sfu-ireceptor/gateway/teams",
//     "hooks_url": "https://api.github.com/repos/sfu-ireceptor/gateway/hooks",
//     "issue_events_url": "https://api.github.com/repos/sfu-ireceptor/gateway/issues/events{/number}",
//     "events_url": "https://api.github.com/repos/sfu-ireceptor/gateway/events",
//     "assignees_url": "https://api.github.com/repos/sfu-ireceptor/gateway/assignees{/user}",
//     "branches_url": "https://api.github.com/repos/sfu-ireceptor/gateway/branches{/branch}",
//     "tags_url": "https://api.github.com/repos/sfu-ireceptor/gateway/tags",
//     "blobs_url": "https://api.github.com/repos/sfu-ireceptor/gateway/git/blobs{/sha}",
//     "git_tags_url": "https://api.github.com/repos/sfu-ireceptor/gateway/git/tags{/sha}",
//     "git_refs_url": "https://api.github.com/repos/sfu-ireceptor/gateway/git/refs{/sha}",
//     "trees_url": "https://api.github.com/repos/sfu-ireceptor/gateway/git/trees{/sha}",
//     "statuses_url": "https://api.github.com/repos/sfu-ireceptor/gateway/statuses/{sha}",
//     "languages_url": "https://api.github.com/repos/sfu-ireceptor/gateway/languages",
//     "stargazers_url": "https://api.github.com/repos/sfu-ireceptor/gateway/stargazers",
//     "contributors_url": "https://api.github.com/repos/sfu-ireceptor/gateway/contributors",
//     "subscribers_url": "https://api.github.com/repos/sfu-ireceptor/gateway/subscribers",
//     "subscription_url": "https://api.github.com/repos/sfu-ireceptor/gateway/subscription",
//     "commits_url": "https://api.github.com/repos/sfu-ireceptor/gateway/commits{/sha}",
//     "git_commits_url": "https://api.github.com/repos/sfu-ireceptor/gateway/git/commits{/sha}",
//     "comments_url": "https://api.github.com/repos/sfu-ireceptor/gateway/comments{/number}",
//     "issue_comment_url": "https://api.github.com/repos/sfu-ireceptor/gateway/issues/comments{/number}",
//     "contents_url": "https://api.github.com/repos/sfu-ireceptor/gateway/contents/{+path}",
//     "compare_url": "https://api.github.com/repos/sfu-ireceptor/gateway/compare/{base}...{head}",
//     "merges_url": "https://api.github.com/repos/sfu-ireceptor/gateway/merges",
//     "archive_url": "https://api.github.com/repos/sfu-ireceptor/gateway/{archive_format}{/ref}",
//     "downloads_url": "https://api.github.com/repos/sfu-ireceptor/gateway/downloads",
//     "issues_url": "https://api.github.com/repos/sfu-ireceptor/gateway/issues{/number}",
//     "pulls_url": "https://api.github.com/repos/sfu-ireceptor/gateway/pulls{/number}",
//     "milestones_url": "https://api.github.com/repos/sfu-ireceptor/gateway/milestones{/number}",
//     "notifications_url": "https://api.github.com/repos/sfu-ireceptor/gateway/notifications{?since,all,participating}",
//     "labels_url": "https://api.github.com/repos/sfu-ireceptor/gateway/labels{/name}",
//     "releases_url": "https://api.github.com/repos/sfu-ireceptor/gateway/releases{/id}",
//     "deployments_url": "https://api.github.com/repos/sfu-ireceptor/gateway/deployments",
//     "created_at": 1498587957,
//     "updated_at": "2017-08-30T03:15:19Z",
//     "pushed_at": 1504902102,
//     "git_url": "git://github.com/sfu-ireceptor/gateway.git",
//     "ssh_url": "git@github.com:sfu-ireceptor/gateway.git",
//     "clone_url": "https://github.com/sfu-ireceptor/gateway.git",
//     "svn_url": "https://github.com/sfu-ireceptor/gateway",
//     "homepage": "http://ireceptor.irmacs.sfu.ca/",
//     "size": 873,
//     "stargazers_count": 1,
//     "watchers_count": 1,
//     "language": "PHP",
//     "has_issues": true,
//     "has_projects": true,
//     "has_downloads": true,
//     "has_wiki": true,
//     "has_pages": false,
//     "forks_count": 0,
//     "mirror_url": null,
//     "open_issues_count": 0,
//     "forks": 0,
//     "open_issues": 0,
//     "watchers": 1,
//     "default_branch": "master",
//     "stargazers": 1,
//     "master_branch": "master",
//     "organization": "sfu-ireceptor"
//   },
//   "pusher": {
//     "name": "jeromejaglale",
//     "email": "jerome.jaglale@gmail.com"
//   },
//   "organization": {
//     "login": "sfu-ireceptor",
//     "id": 29737820,
//     "url": "https://api.github.com/orgs/sfu-ireceptor",
//     "repos_url": "https://api.github.com/orgs/sfu-ireceptor/repos",
//     "events_url": "https://api.github.com/orgs/sfu-ireceptor/events",
//     "hooks_url": "https://api.github.com/orgs/sfu-ireceptor/hooks",
//     "issues_url": "https://api.github.com/orgs/sfu-ireceptor/issues",
//     "members_url": "https://api.github.com/orgs/sfu-ireceptor/members{/member}",
//     "public_members_url": "https://api.github.com/orgs/sfu-ireceptor/public_members{/member}",
//     "avatar_url": "https://avatars3.githubusercontent.com/u/29737820?v=4",
//     "description": null
//   },
//   "sender": {
//     "login": "jeromejaglale",
//     "id": 3597814,
//     "avatar_url": "https://avatars2.githubusercontent.com/u/3597814?v=4",
//     "gravatar_id": "",
//     "url": "https://api.github.com/users/jeromejaglale",
//     "html_url": "https://github.com/jeromejaglale",
//     "followers_url": "https://api.github.com/users/jeromejaglale/followers",
//     "following_url": "https://api.github.com/users/jeromejaglale/following{/other_user}",
//     "gists_url": "https://api.github.com/users/jeromejaglale/gists{/gist_id}",
//     "starred_url": "https://api.github.com/users/jeromejaglale/starred{/owner}{/repo}",
//     "subscriptions_url": "https://api.github.com/users/jeromejaglale/subscriptions",
//     "organizations_url": "https://api.github.com/users/jeromejaglale/orgs",
//     "repos_url": "https://api.github.com/users/jeromejaglale/repos",
//     "events_url": "https://api.github.com/users/jeromejaglale/events{/privacy}",
//     "received_events_url": "https://api.github.com/users/jeromejaglale/received_events",
//     "type": "User",
//     "site_admin": false
//   }
// }
// EOD;

//         $secret = 'CkG7nqY8Rs5haGk7hH6mFLz37CSnuesr';
//         echo hash_hmac('SHA256', $message, $secret) . "\n";

//         die();
//         // $process = new Process('ls -lsa');
//         // $process->run();

//         // // executes after the command finishes
//         // if (!$process->isSuccessful()) {
// //     throw new ProcessFailedException($process);
//         // }

//         // echo $process->getOutput();

//         // $deploy_script_path = base_path('deploy.sh');

//         // $process = new Process($deploy_script_path);
//         // $process->run(function ($type, $buffer) {
// //     echo $buffer;
//         // });

//         $root_path = base_path();

//         $process = new Process('cd ' . $root_path . '; ./deploy.sh');
//         $process->run(function ($type, $buffer) {
//             echo $buffer;
//         });
//         // echo "aa";
//         die();

//         echo Hash::make('0bed3fd19bc087e03ca5e99f98f7e976d330fbf1b966e23de17b41534a942cb6');
//         die();

//         $agave = new Agave;
//         $token = $agave->getAdminToken();

//         $user = $agave->getUserWithEmail('jlj7@sfu.ca', $token);
//         if ($user == null) {
//             echo 'ok';
//             die();
//         }
//         var_dump($user);

//         die();

//         // echo 'index';
//         $email = 'jlj7@sfu.ca';

//         $table = 'password_resets';

//         $hashKey = config('app.key');
//         $token = hash_hmac('sha256', Str::random(40), $hashKey);
//         // echo $token;

//         $hashedToken = Hash::make($token);
//         // echo $hashedToken;

//         DB::table($table)->where('email', 'jlj7@sfu.ca')->delete();
//         DB::table($table)->insert([
//             'email' => $email,
//             'token' => $hashedToken,
//             'created_at' => Carbon::now(),
//         ]);

//         // try {
//         //     $client = new \GuzzleHttp\Client();

//         // $response = $client->request('POST', $url, $data);
//         //     // $response = $client->request('POST', $url, $data);
//         //     $contents = (string) $response->getBody();
//         //     echo $contents;

//         // } catch (\Exception $exception) {
//         //     Log::error('----------------');
//         //     // $response = $exception->getResponse()->getReasonPhrase();
//         //     // Log::error($response);
//         //     // Log::error($exception->getResponse()->getBody()->getContents());
//         //     // ddd($e->getResponse()->getBody()->getContents());
//         // }

// // //         $to      = 'jlj7@sfu.ca';
// // //      $subject = 'the subject';
// // // $message = 'hello';
// // // $headers = 'From: webmaster@example.com' . "\r\n" .
// // //     'Reply-To: webmaster@example.com' . "\r\n" .
// // //     'X-Mailer: PHP/' . phpversion();

// // // mail($to, $subject, $message, $headers);

// //         //         Mail::send(['text' => 'emails.auth.accountCreated'], $t, function ($message) use ($t) {
// //         //     $message->to($t['email'])->subject('iReceptor account');
// //         // });

// //         Mail::send(array('text' => 'emails.test'), [], function($message)
// //         {
// //             $message->to('')->subject('just a test');
// //             echo "ok";
// //         });

//         // $job = new Job;
//         // $job->updateStatus('PENDING');
//         // $job->save();

//         // echo json_decode(config('services.agave.system_deploy.auth'));
//         // var_dump(json_decode(config('services.agave.system_deploy.auth'), true));

//         // $c = config('services.agave.system_deploy.auth');
//         // $c = rtrim($c, "'");
//         // $c = ltrim($c, "'");
//         // var_dump(json_decode($c, true));

//         // $tables = \DB::connection()->getDoctrineSchemaManager()->listTableNames();
//         // foreach ($tables as $table) {
//         //     echo $table."\n";
//         //     // code...
//         // }
    }

    public function index2(Request $request)
    {
        sleep(2);
        echo 'aa';
        // // Log::info('ok!');
        // Log::info($request->header('Content-Type'));
        // Log::info($request->header('User-Agent'));
        // Log::info($request->method());
        // Log::info($request->header());
        // Log::info($request->file());
        // Log::info($request->all());
        // $content = $request->getContent();
        // Log::info($content);
    }

    public function wait($seconds)
    {
        sleep($seconds);
        echo "I waited $seconds sec!";
    }
}
