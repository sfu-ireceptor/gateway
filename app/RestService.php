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
        'url', 'name', 'username', 'password', 'enabled',
    ];

    // Keep track of the total number of publicly available metadata
    // for the users.
    protected static $totalRepositories = 0;
    protected static $totalLabs = 0;
    protected static $totalStudies = 0;
    protected static $totalSamples = 0;
    protected static $totalSequences = 0;
    // Keep track of whether the repositories have changed or not. If
    // they have changed, then we need to refresh the above stats. If
    // not then we don't. Note: We don't have a mechanism to determine
    // if the repositories change. For now, we will just run the refresh
    // once. The other alternative is to do it every call, but it is an
    // expensive operation as it hits all of the metadata and sample API
    // entry points of all repositories.
    protected static $repositoriesChanged = true;

    protected static $v1Keys = [
        'sra_accession', 'project_name', 'project_type', 'lab_name', 'subject_code',
        'subject_gender', 'subject_ethnicity', 'subject_species', 'case_control_name',
        'sample_name', 'sample_source_name', 'disease_state_name', 'ireceptor_cell_subset_name',
        'lab_cell_subset_name', 'dna_type', "",
        'vgene_allele', 'dgene_allele', 'jgene_allele',
        'junction_sequence_aa', 'functionality'
    ];
    protected static $v2Keys = [
        'study_id', 'study_title', 'study_description', 'lab_name', 'subject_id',
        'sex', 'ethnicity', 'organism', 'study_group_description',
        'sample_id', 'tissue', 'disease_state_sample', 'cell_subset',
        'cell_phenotype', 'library_source', "platform",
        'v_gene', 'd_gene', 'j_gene',
        'junction_aa', 'functional'
    ];
    protected static $shortNamesAIRR = [
        'Study', 'Study title', 'Study type', 'Lab name', 'Subject ID',
        'Sex', 'Ethnicity', 'Organism', 'Study group',
        'Sample ID', 'Tissue', 'Disease state', 'Cell subset',
        'Lab Cell subset', 'Target substrate', "Platform",
        'V Gene', 'D Gene', 'J Gene',
        'Junction (AA)', 'Functional'
    ];
    protected static $longNamesAIRR = [
        'Study', 'Study title', 'Study type', 'Lab name', 'Subject ID',
        'Sex', 'Ethnicity', 'Organism', 'Study group description',
        'Biological sample ID', 'Tissue', 'Disease state of sample', 'Cell subset',
        'Cell subset phenotype', 'Target substrate', "Platform"
        'V Gene Allele', 'D Gene Allele', 'J Gene Allele',
        'Junction (CDR3 with conserved residues)(AA)', 'Functional'
    ];

    public static function convertAPIKey($fromVersion, $toVersion, $key)
    {
        // Build the correct conversion array based on the from/to variables
        $fromArray = [];
        $toArray = [];
        switch ($fromVersion) {
            case 'v1': $fromArray = self::$v1Keys; break;
            case 'v2': $fromArray = self::$v2Keys; break;
            case 'AIRR Short': $fromArray = self::$shortNamesAIRR; break;
            case 'AIRR Long': $fromArray = self::$longNamesAIRR; break;
        }
        switch ($toVersion) {
            case 'v1': $toArray = self::$v1Keys; break;
            case 'v2': $toArray = self::$v2Keys; break;
            case 'AIRR Short': $toArray = self::$shortNamesAIRR; break;
            case 'AIRR Long': $toArray = self::$longNamesAIRR; break;
        }
        if (count($fromArray) != count($toArray)) {
            Log::error('convertAPIKey: trying to compare API versions that are not compatible');
            Log::error('convertAPIKey: from = ' . $fromVersion . ', to = ' . $toVersion);

            return false;
        }
        $convertArray = array_combine($fromArray, $toArray);

        // Check to see if the key exists, it does return the conversion,
        // otherwise return false;
        if (array_key_exists($key, $convertArray)) {
            return $convertArray[$key];
        } else {
            return false;
        }
    }

    public static function findEnabled()
    {
        $l = static::where('enabled', '=', true)->orderBy('name', 'asc')->get();

        return $l;
    }

    public static function postRequest($rs, $path, $params, $filePath = '')
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
            $obj = json_decode($json);

            return $obj;
        }
    }

    // This is an expensive operation, but it should only need to occur
    // rarely as the totals will only change when the repositories change.
    // That is, when a new study/subject/sample is added to a repository,
    // these values will change, but they are independent of the query so
    // will typically stay consistent for long periods of time.
    public static function refreshCounts($username)
    {
        // If the repositories haven't changed, then do nothing.
        if (! self::$repositoriesChanged) {
            return;
        }

        self::$totalRepositories = 0;
        self::$totalLabs = 0;
        self::$totalStudies = 0;
        self::$totalSamples = 0;
        self::$totalSequences = 0;

        $repositories = self::findEnabled();
        foreach ($repositories as $repo) {
            try {
                // Get the metadata information from this repository service.
                $params = [];
                $params['username'] = $username;
                $metadata_obj = self::postRequest($repo, 'metadata', $params);

                // Get the lab and study information.
                $labs = $metadata_obj->labs_projects;
                $repo->labs = $labs;
                foreach ($labs as $lab) {
                    self::$totalLabs++;
                    self::$totalStudies += count($lab->projects);
                }

                // Get the summary sample information for this repository service.
                $params = [];
                $params['ajax'] = true;
                $params['username'] = $username;
                $samples_obj = self::postRequest($repo, 'samples', $params);

                // Get the subject, sample, and sequence information.
                self::$totalSamples += count($samples_obj);
                foreach ($samples_obj as $sample) {
                    self::$totalSequences += $sample->sequence_count;
                }
                // Keep  track of how many repositories...
                self::$totalRepositories++;
            } catch (GuzzleHttp\Exception\BadResponseException $e) {
                $response = $e->getResponse();
                Log::error($response);
                Log::error('Response');
                continue;
            } catch (\Exception $e) {
                $message = $e->getMessage();
                Log::error($message);
                Log::error('Exception');
                continue;
            }
        }
        // Set the flag so that we don't re-execute this function unless the
        // repositories have changed.
        self::$repositoriesChanged = false;
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
            // get data from json: everything else
            $rs->ethnicity_list = [];
            $rs->gender_list = [];
            $rs->casecontrol_list = [];
            $rs->dnainfo_list = [];
            $rs->source_list = [];
            $rs->cellsubsettypes_list = [];
            $rs->labs = [];
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
            } catch (GuzzleHttp\Exception\BadResponseException $e) {
                $response = $e->getResponse();
                Log::error($response);
                //continue;
            } catch (\Exception $e) {
                $message = $e->getMessage();
                Log::error($message);
                //continue;
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
        // Update global data to make sure it is current, and store the return total data counts.
        self::refreshCounts($username);
        $metadata['totalRepositories'] = self::$totalRepositories;
        $metadata['totalLabs'] = self::$totalLabs;
        $metadata['totalStudies'] = self::$totalStudies;
        $metadata['totalSamples'] = self::$totalSamples;
        $metadata['totalSequences'] = self::$totalSequences;

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

        // See if we need to update data, and store the return total data counts.
        self::refreshCounts($username);
        $data['totalRepositories'] = self::$totalRepositories;
        $data['totalLabs'] = self::$totalLabs;
        $data['totalStudies'] = self::$totalStudies;
        $data['totalSamples'] = self::$totalSamples;
        $data['totalSequences'] = self::$totalSequences;

        // no filters -> do nothing
        if (empty($filters)) {
            return $data;
        }

        // For each filter that is active, keep track of the filter field so
        // UI can display the filters that are active.
        foreach ($filters as $filterKey => $filterValue) {
            // Filters are sometimes given to the API without values, so we
            // have to detect this and only display if there are values.
            if (count($filterValue) > 0) {
                $filterAIRRName = self::convertAPIKey('v1', 'AIRR Short', $filterKey);
                if (! $filterAIRRName) {
                    if ($filterKey == 'project_id_list') {
                        $data['filters'][] = 'Repository/Lab/Study';
                    } else {
                        $data['filters'][] = $filterKey;
                    }
                } else {
                    $data['filters'][] = $filterAIRRName;
                }
            }
        }

        // Limit the number of results returned by the API.
        $filters['num_results'] = 500;

        foreach (self::findEnabled() as $rs) {
            // filters: customization for this specific REST service
            $params = $filters;
            $params['username'] = $username;
            unset($params['project_id_list']);
            unset($params['project_id']);

            if (isset($filters['ajax'])) {
                // do nothing??
            } else {
                // project list: convert string to array - ex: [1_1, 2_3, 2_4]
                $project_id_list = array_filter(explode(',', $filters['project_id_list']));

                // project list: break up array by rest service with actual project id
                // ex: [1_1, 2_3, 2_4] -> {1:[1], 2:[3,4]}
                $project_list_rs = [];
                foreach ($project_id_list as $project_id) {
                    $t = explode('_', $project_id);
                    if (! isset($project_list_rs[$t[0]])) {
                        $project_list_rs[$t[0]] = [];
                    }
                    $project_list_rs[$t[0]][] = $t[1];
                }

                // if no project of this service is selected, skip
                if (! isset($project_list_rs[$rs->id]) || empty($project_list_rs[$rs->id])) {
                    continue;
                }

                // project id list
                if (isset($project_list_rs[$rs->id])) {
                    $params['project_id'] = $project_list_rs[$rs->id];
                }
            }

            // get samples from REST service
            try {
                $obj = self::postRequest($rs, 'samples', $params);
            } catch (\Exception $e) {
                continue;
            }

            foreach ($obj as $s) {
                $s->rs_id = $rs->id;
                $s->rs_name = $rs->name;
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

        // See if we need to update data, and store the return total data counts.
        self::refreshCounts($username);
        $data['totalRepositories'] = self::$totalRepositories;
        $data['totalLabs'] = self::$totalLabs;
        $data['totalStudies'] = self::$totalStudies;
        $data['totalSamples'] = self::$totalSamples;
        $data['totalSequences'] = self::$totalSequences;

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
