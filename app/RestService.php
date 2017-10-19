<?php

namespace App;

use ZipArchive;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Database\Eloquent\Model;

class RestService extends Model
{
    protected $table = 'rest_service';

    protected $fillable = [
        'url', 'name', 'username', 'password', 'enabled', 'version',
    ];

    public static function findEnabled($fieldList = null)
    {
        $l = static::where('enabled', '=', true)->orderBy('name', 'asc')->get($fieldList);

        return $l;
    }

    public static function postRequest($rs, $path, $params, $filePath = '', $returnArray = false)
    {
        $defaults = [];
        $defaults['base_uri'] = $rs->url;
        $defaults['verify'] = false;    // accept self-signed SSL certificates
        $client = new \GuzzleHttp\Client($defaults);

        // build request
        $options = [];
        $options['auth'] = [$rs->username, $rs->password];
        $options['form_params'] = $params;

        if ($filePath != '') {
            $dirPath = dirname($filePath);
            if (! is_dir($dirPath)) {
                Log::info('Creating directory ' . $dirPath);
                mkdir($dirPath, 0755, true);
            }

            $options['sink'] = fopen($filePath, 'a');
            Log::info('Guzzle: saving to ' . $filePath);
        }

        // execute request
        $response = $client->request('POST', $path, $options);

        if ($filePath == '') {
            // return object generated from json response
            $json = $response->getBody();
            $obj = json_decode($json, $returnArray);

            return $obj;
        }
    }

    public static function metadata2($username)
    {
        return Sample::metadata();
    }

    public static function metadata($username)
    {
        $rest_service_list = [];
        $ethnicity_list = ['' => ''];
        $gender_list = ['' => ''];
        $case_control_list = ['' => ''];
        $dna_type_list = [];
        $sample_source_list = [];
        $cell_type_list = [];

        $numLabs = 0;
        $numProjects = 0;

        // get metadata from each REST service
        foreach (self::findEnabled() as $rs) {
            // perform query
            $params = [];
            $params['username'] = $username;
            try {
                $obj = self::postRequest($rs, 'metadata', $params);

                // get data from json: labs and projects
                $labs = $obj->labs_projects;
                $rs->labs = $labs;
                foreach ($labs as $lab) {
                    $numLabs++;
                    foreach ($lab->projects as $project) {
                        $numProjects++;
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
            foreach ($rs->ethnicity_list as $eth) {
                if (array_search($eth, $ethnicity_list) === false) { // avoid duplicate values
                    $ethnicity_list[$eth] = $eth;
                }
            }

            // subject gender
            foreach ($rs->gender_list  as $gender) {
                if (array_search($gender, $gender_list) === false) {
                    $gender_list[$gender] = $gender;
                }
            }

            // case control
            foreach ($rs->casecontrol_list as $cc) {
                if (array_search($cc, $case_control_list) === false) {
                    $case_control_list[$cc] = $cc;
                }
            }

            // dna info
            foreach ($rs->dnainfo_list as $di) {
                if (array_search($di, $dna_type_list) === false) {
                    $dna_type_list[$di] = $di;
                }
            }

            // sample source
            foreach ($rs->source_list as $s) {
                if (array_search($s, $sample_source_list) === false) {
                    $sample_source_list[$s] = $s;
                }
            }

            // cell type
            foreach ($rs->cellsubsettypes_list as $c) {
                if (array_search($c, $cell_type_list) === false) {
                    $cell_type_list[$c] = $c;
                }
            }
        }

        // build metadata array
        $metadata = [];

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
        // Initialize the return data structure
        $data = [];
        $data['items'] = [];
        $data['rs_list'] = [];
        $data['total'] = 0;

        // Initialize the set of filters being used.
        $data['filters'] = [];

        // Limit the number of results returned by the API.
        //$filters['num_results'] = 500;
        $filters['username'] = $username;

        // get samples from each REST service
        foreach (self::findEnabled() as $rs) {
            try {
                $obj = self::postRequest($rs, 'samples', $filters);
            } catch (\Exception $e) {
                $message = $e->getMessage();
                Log::error($message);
                continue;
            }

            foreach ($obj as $s) {
                $s->rest_service_id = $rs->id;
                $s->rest_service_name = $rs->name;
            }

            $rs_data = [];
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
        // Initialize the return data structure
        $data = [];
        $data['items'] = [];
        $data['rs_list'] = [];
        $data['total'] = 0;

        // Initialize the set of filters being used.
        $data['filters'] = [];

        // no filters -> do nothing
        if (empty($filters)) {
            return $data;
        }

        // For each filter that is active, keep track of the filter field so
        // UI can display the active filters.
        foreach ($filters as $filterKey => $filterValue) {
            // If the filterValue has some data in it (we ignore empty filters)
            if (count($filterValue) > 0) {
                // If the filter isn't one of our internal web page filters
                if ($filterKey != 'cols' && $filterKey != 'filters_order' && $filterKey != 'add_field') {
                    // Get the short form of the AIRR description for the keyword.
                    $filterAIRRName = self::convertAPIKey('v1', 'AIRR Short', $filterKey);
                    if (! $filterAIRRName) {
                        // Special case to detect if we have some samples set from the previous
                        // samples call. Otherwise, just map the key.
                        if (strpos($filterKey, 'project_sample_id_list_') !== false) {
                            $data['filters'][] = 'Samples: Repository/Lab/Study/Sample';
                        } else {
                            $data['filters'][] = $filterKey;
                        }
                    } else {
                        $data['filters'][] = $filterAIRRName;
                    }
                }
            }
        }

        // add username to filters
        $filters['username'] = $username;

        // remove gateway filters from filters
        unset($filters['cols']);
        unset($filters['filters_order']);

        foreach (self::findEnabled() as $rs) {
            $sample_id_list_key = 'project_sample_id_list_' . $rs->id;
            if (array_key_exists($sample_id_list_key, $filters) && ! empty($filters[$sample_id_list_key])) {
                // remove REST service id
                // project_sample_id_list_2 -> project_sample_id_list
                unset($filters['project_sample_id_list']);
                $filters['project_sample_id_list'] = $filters[$sample_id_list_key];
            } else {
                // if no sample id for this REST service, don't query it.
                continue;
            }

            try {
                $obj = self::postRequest($rs, 'sequences', $filters);
            } catch (\Exception $e) {
                continue;
            }

            $data['items'] = array_merge($obj->items, $data['items']);

            $rs_data = [];
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
        $time_str = date('Y-m-d_G-i-s', time());
        $directory_path = $time_str . '_' . uniqid();
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
        foreach (self::findEnabled() as $rs) {
            Log::info('RS: ' . $rs->id);

            $filters['csv_header'] = false;
            if (! $csv_header_written) {
                $filters['csv_header'] = true;
            }

            $sample_id_list_key = 'project_sample_id_list_' . $rs->id;
            if (array_key_exists($sample_id_list_key, $filters) && ! empty($filters[$sample_id_list_key])) {
                // remove REST service id
                // project_sample_id_list_2 -> project_sample_id_list
                unset($filters['project_sample_id_list']);
                $filters['project_sample_id_list'] = $filters[$sample_id_list_key];
            } else {
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
