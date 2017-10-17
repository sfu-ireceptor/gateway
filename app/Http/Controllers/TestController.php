<?php

namespace App\Http\Controllers;

use App\Job;
use App\Agave;
use Carbon\Carbon;
use App\Sample;
use App\RestService;
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
    public function email()
    {
        Mail::send(['text' => 'emails.test'], [], function ($message) {
            $message->to('jlj7@sfu.ca')->subject('Test Email');
        });
        echo 'done';
    }

    public function getIndex()
    {
        $metadata = RestService::metadata2('titi');

        var_dump($metadata);
        die();

        foreach ($metadata['subject_code'] as $k => $v) {
            echo $v . "\n";
        }
        // var_dump($metadata);
        die();

        RestService::samples2('titi');
        die();

        $metadata_data = RestService::metadata2('titi');
        var_dump($metadata_data);
        die();

        Sample::create(['name' => 'John']);
        echo 'aa';
        die();

        echo base64_encode('706d5ebc88cbf4d69d85baecb83e78c2cf4ece7c9c08f5796f89dcb7afdb850b');
        die();

        $message = <<<'EOD'
{
  "ref": "refs/heads/master",
  "before": "bf13e1982e384a723f59dcc59087f65648d6badd",
  "after": "50c4ad0b5cf9b5048466a6886033742f7dd476ef",
  "created": false,
  "deleted": false,
  "forced": false,
  "base_ref": null,
  "compare": "https://github.com/sfu-ireceptor/gateway/compare/bf13e1982e38...50c4ad0b5cf9",
  "commits": [
    {
      "id": "50c4ad0b5cf9b5048466a6886033742f7dd476ef",
      "tree_id": "a30cf79781662cd2d0e67872912f1047b1d1a3d6",
      "distinct": true,
      "message": "minor: just for GitHub webhook testing",
      "timestamp": "2017-09-08T13:21:18-07:00",
      "url": "https://github.com/sfu-ireceptor/gateway/commit/50c4ad0b5cf9b5048466a6886033742f7dd476ef",
      "author": {
        "name": "Jerome Jaglale",
        "email": "jerome_jaglale@sfu.ca",
        "username": "jeromejaglale"
      },
      "committer": {
        "name": "Jerome Jaglale",
        "email": "jerome_jaglale@sfu.ca",
        "username": "jeromejaglale"
      },
      "added": [

      ],
      "removed": [

      ],
      "modified": [
        "app/Http/Controllers/UtilController.php"
      ]
    }
  ],
  "head_commit": {
    "id": "50c4ad0b5cf9b5048466a6886033742f7dd476ef",
    "tree_id": "a30cf79781662cd2d0e67872912f1047b1d1a3d6",
    "distinct": true,
    "message": "minor: just for GitHub webhook testing",
    "timestamp": "2017-09-08T13:21:18-07:00",
    "url": "https://github.com/sfu-ireceptor/gateway/commit/50c4ad0b5cf9b5048466a6886033742f7dd476ef",
    "author": {
      "name": "Jerome Jaglale",
      "email": "jerome_jaglale@sfu.ca",
      "username": "jeromejaglale"
    },
    "committer": {
      "name": "Jerome Jaglale",
      "email": "jerome_jaglale@sfu.ca",
      "username": "jeromejaglale"
    },
    "added": [

    ],
    "removed": [

    ],
    "modified": [
      "app/Http/Controllers/UtilController.php"
    ]
  },
  "repository": {
    "id": 95588395,
    "name": "gateway",
    "full_name": "sfu-ireceptor/gateway",
    "owner": {
      "name": "sfu-ireceptor",
      "email": null,
      "login": "sfu-ireceptor",
      "id": 29737820,
      "avatar_url": "https://avatars3.githubusercontent.com/u/29737820?v=4",
      "gravatar_id": "",
      "url": "https://api.github.com/users/sfu-ireceptor",
      "html_url": "https://github.com/sfu-ireceptor",
      "followers_url": "https://api.github.com/users/sfu-ireceptor/followers",
      "following_url": "https://api.github.com/users/sfu-ireceptor/following{/other_user}",
      "gists_url": "https://api.github.com/users/sfu-ireceptor/gists{/gist_id}",
      "starred_url": "https://api.github.com/users/sfu-ireceptor/starred{/owner}{/repo}",
      "subscriptions_url": "https://api.github.com/users/sfu-ireceptor/subscriptions",
      "organizations_url": "https://api.github.com/users/sfu-ireceptor/orgs",
      "repos_url": "https://api.github.com/users/sfu-ireceptor/repos",
      "events_url": "https://api.github.com/users/sfu-ireceptor/events{/privacy}",
      "received_events_url": "https://api.github.com/users/sfu-ireceptor/received_events",
      "type": "Organization",
      "site_admin": false
    },
    "private": false,
    "html_url": "https://github.com/sfu-ireceptor/gateway",
    "description": null,
    "fork": false,
    "url": "https://github.com/sfu-ireceptor/gateway",
    "forks_url": "https://api.github.com/repos/sfu-ireceptor/gateway/forks",
    "keys_url": "https://api.github.com/repos/sfu-ireceptor/gateway/keys{/key_id}",
    "collaborators_url": "https://api.github.com/repos/sfu-ireceptor/gateway/collaborators{/collaborator}",
    "teams_url": "https://api.github.com/repos/sfu-ireceptor/gateway/teams",
    "hooks_url": "https://api.github.com/repos/sfu-ireceptor/gateway/hooks",
    "issue_events_url": "https://api.github.com/repos/sfu-ireceptor/gateway/issues/events{/number}",
    "events_url": "https://api.github.com/repos/sfu-ireceptor/gateway/events",
    "assignees_url": "https://api.github.com/repos/sfu-ireceptor/gateway/assignees{/user}",
    "branches_url": "https://api.github.com/repos/sfu-ireceptor/gateway/branches{/branch}",
    "tags_url": "https://api.github.com/repos/sfu-ireceptor/gateway/tags",
    "blobs_url": "https://api.github.com/repos/sfu-ireceptor/gateway/git/blobs{/sha}",
    "git_tags_url": "https://api.github.com/repos/sfu-ireceptor/gateway/git/tags{/sha}",
    "git_refs_url": "https://api.github.com/repos/sfu-ireceptor/gateway/git/refs{/sha}",
    "trees_url": "https://api.github.com/repos/sfu-ireceptor/gateway/git/trees{/sha}",
    "statuses_url": "https://api.github.com/repos/sfu-ireceptor/gateway/statuses/{sha}",
    "languages_url": "https://api.github.com/repos/sfu-ireceptor/gateway/languages",
    "stargazers_url": "https://api.github.com/repos/sfu-ireceptor/gateway/stargazers",
    "contributors_url": "https://api.github.com/repos/sfu-ireceptor/gateway/contributors",
    "subscribers_url": "https://api.github.com/repos/sfu-ireceptor/gateway/subscribers",
    "subscription_url": "https://api.github.com/repos/sfu-ireceptor/gateway/subscription",
    "commits_url": "https://api.github.com/repos/sfu-ireceptor/gateway/commits{/sha}",
    "git_commits_url": "https://api.github.com/repos/sfu-ireceptor/gateway/git/commits{/sha}",
    "comments_url": "https://api.github.com/repos/sfu-ireceptor/gateway/comments{/number}",
    "issue_comment_url": "https://api.github.com/repos/sfu-ireceptor/gateway/issues/comments{/number}",
    "contents_url": "https://api.github.com/repos/sfu-ireceptor/gateway/contents/{+path}",
    "compare_url": "https://api.github.com/repos/sfu-ireceptor/gateway/compare/{base}...{head}",
    "merges_url": "https://api.github.com/repos/sfu-ireceptor/gateway/merges",
    "archive_url": "https://api.github.com/repos/sfu-ireceptor/gateway/{archive_format}{/ref}",
    "downloads_url": "https://api.github.com/repos/sfu-ireceptor/gateway/downloads",
    "issues_url": "https://api.github.com/repos/sfu-ireceptor/gateway/issues{/number}",
    "pulls_url": "https://api.github.com/repos/sfu-ireceptor/gateway/pulls{/number}",
    "milestones_url": "https://api.github.com/repos/sfu-ireceptor/gateway/milestones{/number}",
    "notifications_url": "https://api.github.com/repos/sfu-ireceptor/gateway/notifications{?since,all,participating}",
    "labels_url": "https://api.github.com/repos/sfu-ireceptor/gateway/labels{/name}",
    "releases_url": "https://api.github.com/repos/sfu-ireceptor/gateway/releases{/id}",
    "deployments_url": "https://api.github.com/repos/sfu-ireceptor/gateway/deployments",
    "created_at": 1498587957,
    "updated_at": "2017-08-30T03:15:19Z",
    "pushed_at": 1504902102,
    "git_url": "git://github.com/sfu-ireceptor/gateway.git",
    "ssh_url": "git@github.com:sfu-ireceptor/gateway.git",
    "clone_url": "https://github.com/sfu-ireceptor/gateway.git",
    "svn_url": "https://github.com/sfu-ireceptor/gateway",
    "homepage": "http://ireceptor.irmacs.sfu.ca/",
    "size": 873,
    "stargazers_count": 1,
    "watchers_count": 1,
    "language": "PHP",
    "has_issues": true,
    "has_projects": true,
    "has_downloads": true,
    "has_wiki": true,
    "has_pages": false,
    "forks_count": 0,
    "mirror_url": null,
    "open_issues_count": 0,
    "forks": 0,
    "open_issues": 0,
    "watchers": 1,
    "default_branch": "master",
    "stargazers": 1,
    "master_branch": "master",
    "organization": "sfu-ireceptor"
  },
  "pusher": {
    "name": "jeromejaglale",
    "email": "jerome.jaglale@gmail.com"
  },
  "organization": {
    "login": "sfu-ireceptor",
    "id": 29737820,
    "url": "https://api.github.com/orgs/sfu-ireceptor",
    "repos_url": "https://api.github.com/orgs/sfu-ireceptor/repos",
    "events_url": "https://api.github.com/orgs/sfu-ireceptor/events",
    "hooks_url": "https://api.github.com/orgs/sfu-ireceptor/hooks",
    "issues_url": "https://api.github.com/orgs/sfu-ireceptor/issues",
    "members_url": "https://api.github.com/orgs/sfu-ireceptor/members{/member}",
    "public_members_url": "https://api.github.com/orgs/sfu-ireceptor/public_members{/member}",
    "avatar_url": "https://avatars3.githubusercontent.com/u/29737820?v=4",
    "description": null
  },
  "sender": {
    "login": "jeromejaglale",
    "id": 3597814,
    "avatar_url": "https://avatars2.githubusercontent.com/u/3597814?v=4",
    "gravatar_id": "",
    "url": "https://api.github.com/users/jeromejaglale",
    "html_url": "https://github.com/jeromejaglale",
    "followers_url": "https://api.github.com/users/jeromejaglale/followers",
    "following_url": "https://api.github.com/users/jeromejaglale/following{/other_user}",
    "gists_url": "https://api.github.com/users/jeromejaglale/gists{/gist_id}",
    "starred_url": "https://api.github.com/users/jeromejaglale/starred{/owner}{/repo}",
    "subscriptions_url": "https://api.github.com/users/jeromejaglale/subscriptions",
    "organizations_url": "https://api.github.com/users/jeromejaglale/orgs",
    "repos_url": "https://api.github.com/users/jeromejaglale/repos",
    "events_url": "https://api.github.com/users/jeromejaglale/events{/privacy}",
    "received_events_url": "https://api.github.com/users/jeromejaglale/received_events",
    "type": "User",
    "site_admin": false
  }
}
EOD;

        $secret = 'CkG7nqY8Rs5haGk7hH6mFLz37CSnuesr';
        echo hash_hmac('SHA256', $message, $secret) . "\n";

        die();
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
