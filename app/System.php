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

    public static function createDefaultSystemsForUser($username, $token)
    {
        $agave = new Agave;

        // create execution system
        $defaultExecutionSystemHost = config('services.agave.default_execution_system.host');
        $defaultExecutionSystemPort = config('services.agave.default_execution_system.port');
        $defaultExecutionSystemUsername = config('services.agave.default_execution_system.auth.username');
        $defaultExecutionSystemPublicKey = config('services.agave.default_execution_system.auth.public_key');
        $defaultExecutionSystemPrivateKey = config('services.agave.default_execution_system.auth.private_key');

        $systemExecutionName = config('services.agave.system_execution.name_prefix') . $username . '-' . $defaultExecutionSystemUsername . '-' . $defaultExecutionSystemHost;

        $config = $agave->getExcutionSystemConfig($systemExecutionName, $defaultExecutionSystemHost, $defaultExecutionSystemPort, $defaultExecutionSystemUsername, $defaultExecutionSystemPrivateKey, $defaultExecutionSystemPublicKey);
        $response = $agave->createSystem($token, $config);
        Log::info('execution system created: ' . $systemExecutionName);

        // add execution system to database
        $systemExecution = self::firstOrNew(['user_id' => auth()->user()->id, 'host' => $defaultExecutionSystemHost, 'username' => $defaultExecutionSystemUsername]);
        $systemExecution->name = $systemExecutionName;
        $systemExecution->public_key = $defaultExecutionSystemPublicKey;
        $systemExecution->private_key = $defaultExecutionSystemPrivateKey;
        $systemExecution->selected = false;
        $systemExecution->save();

        // select exec system
        self::select($systemExecution->id);

        // create deployment system (where the app originally is)
        $systemDeploymentName = config('services.agave.system_deploy.name_prefix') . $defaultExecutionSystemUsername;
        $systemDeploymentHost = $defaultExecutionSystemHost; 
        $systemDeploymentPort = $defaultExecutionSystemPort;
        $systemDeploymentUsername = $defaultExecutionSystemUsername;
        $systemDeploymentPublicKey = $defaultExecutionSystemPublicKey ;
        $systemDeploymentPrivateKey = $defaultExecutionSystemPrivateKey;
        $systemDeploymentRootDir = config('services.agave.system_deploy.rootdir');

        $config = $agave->getStorageSystemConfig($systemDeploymentName, $systemDeploymentHost, $systemDeploymentPort, $systemDeploymentUsername, $systemDeploymentPrivateKey, $systemDeploymentPublicKey, $systemDeploymentRootDir);
        $response = $agave->createSystem($token, $config);
        Log::info('deployment system created: ' . $systemDeploymentName);

        // create staging system (on this machine, where the data files will copied from)
        $systemStagingName = config('services.agave.system_staging.name_prefix') . $username;
        $systemStagingHost = config('services.agave.system_staging.host');
        $systemStagingPort = config('services.agave.system_staging.port');
        $systemStagingUsername = config('services.agave.system_staging.auth.username');
        $systemStagingPrivateKey = config('services.agave.system_staging.auth.private_key');
        $systemStagingPublicKey = config('services.agave.system_staging.auth.public_key');
        $systemStagingRootDir = config('services.agave.system_staging.rootdir');

        $config = $agave->getStorageSystemConfig($systemStagingName, $systemStagingHost, $systemStagingPort, $systemStagingUsername, $systemStagingPrivateKey, $systemStagingPublicKey, $systemStagingRootDir);
        $response = $agave->createSystem($token, $config);
        Log::info('staging system created: ' . $systemStagingName);

        return null;
    }
}
