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

    public static function createDefaultSystemsForUser($gw_username, $gw_userid)
    {
        $tapis = new Tapis;

        // create execution system
        $defaultExecutionSystemHost = config('services.tapis.default_execution_system.host');
        $defaultExecutionSystemPort = config('services.tapis.default_execution_system.port');
        $defaultExecutionSystemUsername = config('services.tapis.default_execution_system.auth.username');

        $systemExecutionName = config('services.tapis.system_execution.name_prefix') . '-' . $defaultExecutionSystemHost;

        $config = $tapis->getExecutionSystemConfig($systemExecutionName, $defaultExecutionSystemHost,
            $defaultExecutionSystemPort, $defaultExecutionSystemUsername);
        $sysResponse = $tapis->getSystem($systemExecutionName);
        if (property_exists($sysResponse, 'status') && $sysResponse->status == 'success') {
            $response = $tapis->updateSystem($systemExecutionName, $config);
            $tapis->raiseExceptionIfTapisError($response);
            Log::info('System::createDefaulySystemForUser - system updated: ' . $systemExecutionName);
        } else {
            $response = $tapis->createSystem($config);
            $tapis->raiseExceptionIfTapisError($response);
            Log::info('System::createDefaulySystemForUser - system created: ' . $systemExecutionName);
        }

        // add execution system to database
        $systemExecution = self::firstOrNew(['user_id' => $gw_userid, 'host' => $defaultExecutionSystemHost, 'username' => $defaultExecutionSystemUsername]);
        $systemExecution->name = $systemExecutionName;
        // Public/private key no longer stored in DB for Tapis 3.
        $systemExecution->public_key = '';
        $systemExecution->private_key = '';
        $systemExecution->selected = false;
        $systemExecution->save();

        // select exec system
        self::select($systemExecution->id);

        // create deployment system (where the app originally is)
        $systemDeploymentName = config('services.tapis.system_deploy.name_prefix');
        $systemDeploymentHost = config('services.tapis.system_deploy.host');
        $systemDeploymentPort = config('services.tapis.system_deploy.port');
        $systemDeploymentUsername = config('services.tapis.system_deploy.auth.username');
        //$systemDeploymentPrivateKey = config('services.tapis.system_deploy.auth.private_key');
        //$systemDeploymentPublicKey = config('services.tapis.system_deploy.auth.public_key');
        $systemDeploymentRootDir = config('services.tapis.system_deploy.rootdir');

        $config = $tapis->getStorageSystemConfig($systemDeploymentName, $systemDeploymentHost, $systemDeploymentPort, $systemDeploymentUsername, $systemDeploymentRootDir);
        $sysResponse = $tapis->getSystem($systemDeploymentName);
        if ($sysResponse->status == 'success') {
            Log::info('System::createDefaulySystemForUser - updating system: ' . $systemDeploymentName);
            $response = $tapis->updateSystem($systemDeploymentName, $config);
            $tapis->raiseExceptionIfTapisError($response);
            Log::info('System::createDefaulySystemForUser - system updated: ' . $systemDeploymentName);
        } else {
            Log::info('System::createDefaulySystemForUser - creating system: ' . $systemDeploymentName);
            $response = $tapis->createSystem($config);
            $tapis->raiseExceptionIfTapisError($response);
            Log::info('System::createDefaulySystemForUser - system created: ' . $systemDeploymentName);
        }

        // create staging system (on this machine, where the data files will copied from)
        $systemStagingName = config('services.tapis.system_staging.name_prefix');
        $systemStagingHost = config('services.tapis.system_staging.host');
        $systemStagingPort = config('services.tapis.system_staging.port');
        $systemStagingUsername = config('services.tapis.system_staging.auth.username');
        //$systemStagingPrivateKey = config('services.tapis.system_staging.auth.private_key');
        //$systemStagingPublicKey = config('services.tapis.system_staging.auth.public_key');
        $systemStagingRootDir = config('services.tapis.system_staging.rootdir');

        $config = $tapis->getStorageSystemConfig($systemStagingName, $systemStagingHost, $systemStagingPort, $systemStagingUsername, $systemStagingRootDir);
        $sysResponse = $tapis->getSystem($systemStagingName);
        if ($sysResponse->status == 'success') {
            Log::info('System::createDefaulySystemForUser - updating system: ' . $systemStagingName);
            Log::info('System::createDefaulySystemForUser - rootDir: ' . $systemStagingRootDir);
            $response = $tapis->updateSystem($systemStagingName, $config);
            $tapis->raiseExceptionIfTapisError($response);
            Log::info('System::createDefaulySystemForUser - system updated: ' . $systemStagingName);
        } else {
            Log::info('System::createDefaulySystemForUser - creating system: ' . $systemStagingName);
            $response = $tapis->createSystem($config);
            $tapis->raiseExceptionIfTapisError($response);
            Log::info('System::createDefaulySystemForUser - system created: ' . $systemStagingName);
        }
        //$response = $tapis->createSystem($config);
        Log::info('staging system created: ' . $systemStagingName);

        return null;
    }
}
