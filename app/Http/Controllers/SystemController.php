<?php

namespace App\Http\Controllers;

use App\System;
use App\Tapis;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SystemController extends Controller
{
    public function getIndex()
    {
        $userId = auth()->user()->id;

        $data = [];
        $data['system_list'] = System::where('user_id', '=', $userId)->orderBy('id', 'desc')->get();

        $data['system_selected'] = System::where(['user_id' => $userId, 'selected' => true])->first();

        $data['notification'] = session()->get('notification');

        return view('systemList', $data);
    }

    public function postAdd(Request $request)
    {
        // get user input
        $systemHost = $request->get('host');
        $systemHost = parse_url($systemHost, PHP_URL_PATH);
        $systemHostStr = str_replace('.', '-', $systemHost);

        $username = $request->get('username');

        // create systems
        $tapis = new Tapis;

        // create execution system
        $sshKeys = $tapis->generateSSHKeys();

        $systemExecutionName = config('services.tapis.system_execution.name_prefix') . '-' . $systemHostStr;
        $systemExecutionHost = $systemHost;
        $systemExecutionUsername = $username;
        $systemExecutionPort = 22;

        $config = $tapis->getExecutionSystemConfig($systemExecutionName, $systemExecutionHost, $systemExecutionPort, $systemExecutionUsername);
        $response = $tapis->createSystem($config);
        Log::info('execution system created: ' . $systemExecutionName);

        // add exec system to DB
        $systemExecution = System::firstOrNew(['user_id' => auth()->user()->id, 'host' => $systemExecutionHost, 'username' => $username]);
        $systemExecution->name = $systemExecutionName;
        $systemExecution->public_key = $systemExecutionPublicKey;
        $systemExecution->private_key = $systemExecutionPrivateKey;
        $systemExecution->selected = false;
        $systemExecution->save();

        // select exec system
        System::select($systemExecution->id);

        // create deployment system (where the app originally is)
        $systemDeploymentName = config('services.tapis.system_deploy.name_prefix');
        $systemDeploymentHost = config('services.tapis.system_deploy.host');
        $systemDeploymentPort = config('services.tapis.system_deploy.port');
        $systemDeploymentUsername = config('services.tapis.system_deploy.auth.username');
        $systemDeploymentRootDir = config('services.tapis.system_deploy.rootdir');

        $config = $tapis->getStorageSystemConfig($systemDeploymentName, $systemDeploymentHost, $systemDeploymentPort, $systemDeploymentUsername, $systemDeploymentRootDir);
        $response = $tapis->createSystem($config);
        Log::info('deployment system created: ' . $systemDeploymentName);

        // create staging system (on this machine, where the data files will copied from)
        $systemStagingName = config('services.tapis.system_staging.name_prefix');
        $systemStagingHost = config('services.tapis.system_staging.host');
        $systemStagingPort = config('services.tapis.system_staging.port');
        $systemStagingUsername = config('services.tapis.system_staging.auth.username');
        $systemStagingRootDir = config('services.tapis.system_staging.rootdir');

        $config = $tapis->getStorageSystemConfig($systemStagingName, $systemStagingHost, $systemStagingPort, $systemStagingUsername, $systemStagingRootDir);
        $response = $tapis->createSystem($config);
        Log::info('staging system created: ' . $systemStagingName);

        return redirect('systems')->with('notification', 'The system was successfully created and selected. Add the SSH key in ~/.ssh/authorized_keys');
    }

    public function postSelect(Request $request)
    {
        $id = $request->get('id');
        System::select($id);
    }

    public function getDelete($id)
    {
        $userId = auth()->user()->id;
        $system = System::get($id, $userId);
        if ($system != null) {
            $system->delete();

            return redirect('systems')->with('notification', 'The system <strong>' . $system->username . '@' . $system->host . '</strong> was successfully deleted.');
        }

        return redirect('systems');
    }
}
