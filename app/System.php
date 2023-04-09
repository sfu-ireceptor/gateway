<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class System extends Model
{
    protected $table = 'user_system';
    protected $fillable = ['user_id', 'name', 'host', 'username'];

    public static function select($id)
    {
        $system = static::find($id);

        // unselect all systems (for that user)
        static::where('user_id', '=', $system->user_id)->update(['selected' => false]);

        // select system
        $system->selected = true;
        $system->save();
    }

    public static function getCurrentSystem($user_id)
    {
        $system = static::where('user_id', $user_id)->where('selected', true)->first();

        return $system;
    }

    public static function get($id, $user_id)
    {
        $system = static::where('user_id', $user_id)->where('id', $id)->first();

        return $system;
    }

    public static function createDefaultSystemsForUser($gw_username, $gw_userid, $token)
    {
        $tapis = new Tapis;

        // create execution system
        $defaultExecutionSystemHost = config('services.tapis.default_execution_system.host');
        $defaultExecutionSystemPort = config('services.tapis.default_execution_system.port');
        $defaultExecutionSystemUsername = config('services.tapis.default_execution_system.auth.username');
        //$defaultExecutionSystemPublicKey = config('services.tapis.default_execution_system.auth.public_key');
        //$defaultExecutionSystemPrivateKey = config('services.tapis.default_execution_system.auth.private_key');

        //$systemExecutionName = config('services.tapis.system_execution.name_prefix') . $gw_username . '-' . $defaultExecutionSystemUsername . '-' . $defaultExecutionSystemHost;
        //$systemExecutionName = config('services.tapis.system_execution.name_prefix') . str_replace('_', '-', $gw_username) . '-tapis3-test-' . $defaultExecutionSystemHost;
        $systemExecutionName = config('services.tapis.system_execution.name_prefix') . '-' . $defaultExecutionSystemHost . '-' . str_replace('_', '-', $gw_username);

        $config = $tapis->getExecutionSystemConfig($systemExecutionName, $defaultExecutionSystemHost, $defaultExecutionSystemPort, $defaultExecutionSystemUsername);
        $sysResponse = $tapis->getSystem($systemExecutionName, $token);
        if ($sysResponse->status == 'success') {
            $response = $tapis->updateSystem($token, $systemExecutionName, $config);
            Log::info('System::createDefaulySystemForUser - system updated: ' . $systemExecutionName);
        } else {
            $response = $tapis->createSystem($token, $config);
            Log::info('System::createDefaulySystemForUser - system created: ' . $systemExecutionName);
        }
        //$response = $tapis->createSystem($token, $config);
        //Log::info('execution system created: ' . $systemExecutionName);

        // add execution system to database
        $systemExecution = self::firstOrNew(['user_id' => $gw_userid, 'host' => $defaultExecutionSystemHost, 'username' => $defaultExecutionSystemUsername]);
        $systemExecution->name = $systemExecutionName;
        //$systemExecution->public_key = $defaultExecutionSystemPublicKey;
        //$systemExecution->private_key = $defaultExecutionSystemPrivateKey;
        $systemExecution->selected = false;
        $systemExecution->save();

        // select exec system
        self::select($systemExecution->id);

        // create deployment system (where the app originally is)
        $systemDeploymentName = config('services.tapis.system_deploy.name_prefix') . str_replace('_', '-', $gw_username) . '-tapis3-test-' . $defaultExecutionSystemUsername;
        $systemDeploymentHost = config('services.tapis.system_deploy.host');
        $systemDeploymentPort = config('services.tapis.system_deploy.port');
        $systemDeploymentUsername = config('services.tapis.system_deploy.auth.username');
        //$systemDeploymentPrivateKey = config('services.tapis.system_deploy.auth.private_key');
        //$systemDeploymentPublicKey = config('services.tapis.system_deploy.auth.public_key');
        $systemDeploymentRootDir = config('services.tapis.system_deploy.rootdir');

        $config = $tapis->getStorageSystemConfig($systemDeploymentName, $systemDeploymentHost, $systemDeploymentPort, $systemDeploymentUsername, $systemDeploymentRootDir);
        $response = $tapis->createSystem($token, $config);
        Log::info('deployment system created: ' . $systemDeploymentName);

        // create staging system (on this machine, where the data files will copied from)
        $systemStagingName = config('services.tapis.system_staging.name_prefix') . str_replace('_', '-tapis3-test-', $gw_username);
        $systemStagingHost = config('services.tapis.system_staging.host');
        $systemStagingPort = config('services.tapis.system_staging.port');
        $systemStagingUsername = config('services.tapis.system_staging.auth.username');
        //$systemStagingPrivateKey = config('services.tapis.system_staging.auth.private_key');
        //$systemStagingPublicKey = config('services.tapis.system_staging.auth.public_key');
        $systemStagingRootDir = config('services.tapis.system_staging.rootdir');

        $config = $tapis->getStorageSystemConfig($systemStagingName, $systemStagingHost, $systemStagingPort, $systemStagingUsername, $systemStagingRootDir);
        $response = $tapis->createSystem($token, $config);
        Log::info('staging system created: ' . $systemStagingName);

        return null;
    }
}
