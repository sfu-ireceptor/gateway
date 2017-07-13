<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use ZipArchive;

class RestService extends Model {
	
	protected $table = 'rest_service';
    
    protected $fillable = [
    	'url', 'name', 'username', 'password', 'enabled'
    ];
	
    public static function findEnabled()
    {
        $l = static::where('enabled', '=', true)->orderBy('name','asc')->get();
        return $l;
    }

	public static function postRequest($rs, $path, $params, $filePath = '')
	{
        $client = new \GuzzleHttp\Client([
            'base_url' => $rs->url,
            'defaults' => [
                'auth' => [$rs->username, $rs->password],
            ]    
        ]);

        // accept self-signed SSL certificates
        $client->setDefaultOption('verify', false);

        // build request
        $options = array();
        if($filePath != '')
        {
            $dirPath = dirname($filePath);
            if (!is_dir($dirPath))
            {
                Log::info('Creating directory ' . $dirPath);
                mkdir($dirPath, 0755, true);
            }

            $options['save_to'] = fopen($filePath, 'a');
            Log::info('Guzzle: saving to ' . $filePath);
        }
        $request = $client->createRequest('POST', $path, $options);
        
        // params
        $postBody = $request->getBody();
        $postBody->replaceFields($params);

        // execute request
        $response = $client->send($request);

        if($filePath == '')
        {
            // return object generated from json response        
            $json = $response->getBody();
            $obj = json_decode($json);

            return $obj;            
        }
	}

	public static function metadata($username)
    {    	
        $rest_service_list = array();
        $ethnicity_list = array('' => '');
        $gender_list = array('' => '');
        $case_control_list = array('' => '');
        $dna_type_list = array();
        $sample_source_list = array();
        $cell_type_list = array();
    
        // get metadata from each REST service    
		foreach (self::findEnabled() as $rs)
        {
            // perform query
            $params = array();
            $params['username'] = $username;
            try {
                $obj = self::postRequest($rs, 'metadata', $params);                

                // get data from json: labs and projects
                $labs = $obj->labs_projects;
                $rs->labs = $labs;
                foreach ($labs as $lab)
                {
                    foreach ($lab->projects as $project)
                    {
                        $project->id = $rs->id . '_' . $project->id;
                    }
                }

                // get data from json: everything else
                $rs->ethnicity_list = $obj->ethnicity;
                $rs->gender_list = $obj->gender;
                $rs->casecontrol_list = $obj->casecontrol;
                $rs->dnainfo_list = $obj->dnainfo;
                $rs->source_list = $obj->source;
                $rs->cellsubsettypes_list = $obj->cellsubsettypes;
            } catch (\Exception $e) {
                $response = $e->getResponse();
                Log::error($response);
                continue;            
            }

            // service response was successfully parsed -> show the service
            $rest_service_list[] = $rs;

            // add metadata from that service to global values
            // subject ethnicity
            foreach ($rs->ethnicity_list as $eth)
            {
                if(array_search($eth, $ethnicity_list) === false) // avoid duplicate values
                {
                    $ethnicity_list[$eth] = $eth;
                }
            }

            // subject gender
            foreach ($rs->gender_list  as $gender) 
            { 
                if(array_search($gender, $gender_list) === false) 
                { 
                    $gender_list[$gender] = $gender; 
                } 
            } 
            
            // case control
            foreach ($rs->casecontrol_list as $cc)
            {
                if(array_search($cc, $case_control_list) === false)
                {
                    $case_control_list[$cc] = $cc;
                }
            }

            // dna info
            foreach ($rs->dnainfo_list as $di)
            {
                if(array_search($di, $dna_type_list) === false)
                {
                    $dna_type_list[$di] = $di;
                }
            }

            // sample source
            foreach ($rs->source_list as $s)
            {
                if(array_search($s, $sample_source_list) === false)
                {
                    $sample_source_list[$s] = $s;
                }
            }

            // cell type
            foreach ($rs->cellsubsettypes_list as $c)
            {
                if(array_search($c, $cell_type_list) === false)
                {
                    $cell_type_list[$c] = $c;
                }
            }                
        }

        // build metadata array
        $metadata = array();
        $metadata['rest_service_list'] = $rest_service_list;

        $metadata['subject_ethnicity_list'] = $ethnicity_list;
        $metadata['subject_gender_list'] = $gender_list;
        $metadata['case_control_list'] = $case_control_list;
        $metadata['dna_type_list'] = $dna_type_list;
        $metadata['sample_source_list'] = $sample_source_list;
        $metadata['ireceptor_cell_subset_name_list'] = $cell_type_list;

        return $metadata;
    }

    public static function samples($filters, $username)
    {
        $data = array();
        $data['items'] = array();
        $data['rs_list'] = array();
        $data['total'] = 0;

    	// no filters -> do nothing
    	if(empty($filters))
        {
            return $data;
        }

        $filters['num_results'] = 500;

        foreach (RestService::findEnabled() as $rs)
        {
	        // project list: convert string to array - ex: [1_1, 2_3, 2_4]
            $project_id_list = array_filter(explode(',', $filters['project_id_list']));

        	// project list: break up array by rest service with actual project id
            // ex: [1_1, 2_3, 2_4] -> {1:[1], 2:[3,4]}
            $project_list_rs = array();
            foreach ($project_id_list as $project_id)
            {
                $t = explode('_', $project_id);
                if( ! isset($project_list_rs[$t[0]]))
                {
                    $project_list_rs[$t[0]] = array();    
                }
                $project_list_rs[$t[0]][] = $t[1];
            }

            // if no project of this service is selected, skip
            if( ! isset($project_list_rs[$rs->id]) || empty($project_list_rs[$rs->id]))
            {
                continue;
            }

            // filters: customization for this specific REST service
            $params = $filters;
            $params['username'] = $username;
            
            // project id list
            unset($params['project_id_list']);
            unset($params['project_id']);
            if(isset($project_list_rs[$rs->id]))
            {
                $params['project_id'] = $project_list_rs[$rs->id];
            }

        	// get samples from REST service   
            try {
                $obj = self::postRequest($rs, 'samples', $params);                
            } catch (\Exception $e) {
                continue;            
            } 

            foreach ($obj as $s)
            {
                $s->rs_id = $rs->id;
                $s->rs_name = $rs->name;
            }

            $rs_data = array();
            $rs_data['rs'] = $rs;
            $rs_data['total_samples'] = count($obj);
            $data['rs_list'][] = $rs_data;
            $data['total'] += $rs_data['total_samples'];

            $data['items'] = array_merge($obj, $data['items']);

        }

        return $data;
    }

    public static function sequences($filters, $username)
    {
        $data = array();
        $data['items'] = array();
        $data['rs_list'] = array();
        $data['total'] = 0;

        // no filters -> do nothing
        if(empty($filters))
        {
            return $data;
        }

        // add username to filters
        $filters['username'] = $username;

        // remove gateway filters from filters
        unset($filters['cols']);
        unset($filters['filters_order']);
        
        foreach (RestService::findEnabled() as $rs)
        {
            $sample_id_list_key = 'project_sample_id_list_' . $rs->id;
            if(array_key_exists($sample_id_list_key, $filters) && ! empty($filters[$sample_id_list_key])) {
                // remove REST service id
                // project_sample_id_list_2 -> project_sample_id_list
                unset($filters['project_sample_id_list']);
                $filters['project_sample_id_list'] = $filters[$sample_id_list_key];
            }
            else {
                // if no sample id for this REST service, don't query it.
                continue;
            }

            try {
                $obj = self::postRequest($rs, 'sequences', $filters);                
            } catch (\Exception $e) {
                continue;            
            } 


            $data['items'] = array_merge($obj->items, $data['items']);
            
            $rs_data = array();
            $rs_data['rs'] = $rs;
            $rs_data['total_sequences'] = $obj->total;
            $data['rs_list'][] = $rs_data;

            $data['total'] += $obj->total;
        }

        return $data;
    }

    public static function sequencesCSV($filters, $username)
    {
        // directory
        $time_str = date("Y-m-d_G-i-s", time());
        $directory_path = $time_str . '_' .  uniqid();
        $old = umask(0);
        File::makeDirectory(public_path() . '/data/' . $directory_path, 0777, true, true);
        umask($old);

        // file
        $filePath = '/data/' . $directory_path . '/data.csv';
        $systemFilePath = public_path() . $filePath;

        // add username to filters
        $filters['output'] = 'csv';
        $filters['username'] = $username;

        // get csv data and write it to file
        $csv_header_written = false;
        foreach (RestService::findEnabled() as $rs)
        {
            Log::info('RS: ' . $rs->id);

            $filters['csv_header'] = false;
            if( ! $csv_header_written)
            {
                $filters['csv_header'] = true;
            }

            $sample_id_list_key = 'project_sample_id_list_' . $rs->id;
            if(array_key_exists($sample_id_list_key, $filters) && ! empty($filters[$sample_id_list_key])) {
                // remove REST service id
                // project_sample_id_list_2 -> project_sample_id_list
                unset($filters['project_sample_id_list']);
                $filters['project_sample_id_list'] = $filters[$sample_id_list_key];
            }
            else {
                // if no sample id for this REST service, don't query it.
                continue;
            }

            Log::info('doing req to RS with params:');
            Log::info($filters);

            try {
                self::postRequest($rs, 'sequences', $filters, $systemFilePath);
            } catch (\Exception $e) {
                $message = $e->getMessage();
                Log::error($message);
                continue;            
            }
            
            $csv_header_written = true;
        }

        // zip file
        $zipSystemFilePath = $systemFilePath . '.zip';
        $zip = new ZipArchive();
        $zip->open($zipSystemFilePath, ZipArchive::CREATE);
        $zip->addFile($systemFilePath, 'data.csv');
        $zip->close();
        $zipFilePath = $filePath . '.zip';

        // delete original file
        File::delete($systemFilePath);

        Log::info('$zipFilePath=' . $zipFilePath);
        return $zipFilePath;
    }


}