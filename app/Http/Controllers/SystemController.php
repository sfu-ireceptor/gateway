<?php

namespace App\Http\Controllers;

use App\Agave;
use App\System;
use App\User;
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

        $token = auth()->user()->password;

            // create systems
            $agave = new Agave();

            // create execution system
            $sshKeys = $agave->generateSSHKeys();

        $systemExecutionName = 'system-exec--'.$systemHostStr.'-'.$username;
        $systemExecutionHost = $systemHost;
        $systemExecutionUsername = $username;
        $systemExecutionPublicKey = $sshKeys['public'];
        $systemExecutionPrivateKey = $sshKeys['private'];

        $config = $agave->getExcutionSystemConfig($systemExecutionName, $systemExecutionHost, $systemExecutionUsername, $systemExecutionPrivateKey, $systemExecutionPublicKey);
        $response = $agave->createSystem($token, $config);
        Log::info('execution system created: '.$systemExecutionName);

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
            $systemDeploymentName = config('services.agave.system_deploy.name_prefix').$username;
        $systemDeploymentHost = config('services.agave.system_deploy.host');
        $systemDeploymentAuth = json_decode(config('services.agave.system_deploy.auth'));
        $systemDeploymentRootDir = config('services.agave.system_deploy.rootdir');

        $config = $agave->getStorageSystemConfig($systemDeploymentName, $systemDeploymentHost, $systemDeploymentAuth, $systemDeploymentRootDir);
        $response = $agave->createSystem($token, $config);
        Log::info('deployment system created: '.$systemDeploymentName);

            // create staging system (on this machine, where the data files will copied from)
            $systemStagingName = config('services.agave.system_staging.name_prefix').$username;
        $systemStagingHost = config('services.agave.system_staging.host');
        $systemStagingAuth = json_decode(config('services.agave.system_staging.auth'));
        $systemStagingRootDir = config('services.agave.system_staging.rootdir');

        $config = $agave->getStorageSystemConfig($systemStagingName, $systemStagingHost, $systemStagingAuth, $systemStagingRootDir);
        $response = $agave->createSystem($token, $config);
        Log::info('staging system created: '.$systemStagingName);

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

            return redirect('systems')->with('notification', 'The system <strong>'.$system->username.'@'.$system->host.'</strong> was successfully deleted.');
        }

        return redirect('systems');
    }
}
