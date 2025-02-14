<?php

namespace Tests\Feature;

use App\User;
use Facades\App\Query;
use Facades\App\RestService;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SequenceTest extends TestCase
{
    protected static $rs = [
        'id' => 1,
        'name' => 'Fake Rest Service',
        'display_name' => 'Fake Rest Service',
        'api_version' => '1.0',
    ];

    protected static $query_params = [
        'ir_project_sample_id_list_3' => [0 => '8', 1 => '475'],
    ];

    protected static $repertoire_data = [];
    protected static $sequence_list = [];

    public static function setUpBeforeClass(): void
    {
        self::$repertoire_data = [
            (object) [
                'repertoire_id' => 8,
                'subject' => (object) [
                    'sex' => 'Female',
                    'diagnosis' => (object) [
                        'disease_length' => null,
                        'immunogen' => null,
                        'medical_history' => null,
                        'intervention' => null,
                        'disease_diagnosis' => 'Multiple Sclerosis',
                        'study_group_description' => 'Case',
                        'disease_stage' => null,
                        'prior_therapies' => null,
                    ],
                    'linked_subjects' => null,
                    'strain_name' => null,
                    'race' => null,
                    'age' => '54 years',
                    'subject_id' => '26712_CSF',
                    'organism' => (object) [
                        'value' => 'Homo sapiens',
                    ],
                    'link_type' => null,
                    'ethnicity' => null,
                    'ancestry_population' => null,
                    'synthetic' => false,
                    'age_event' => null,
                ],
                'sample' => [
                    0 => (object) [
                        'sequencing_kit' => null,
                        'total_reads_passing_qc_filter' => 4854,
                        'tissue' => 'Blood',
                        'cell_phenotype' => 'CD19, IgD, CD27',
                        'physical_linkage' => null,
                        'pcr_target' => [
                            0 => (object) [
                                'forward_pcr_primer_target_location' => 'IgM-VH, IgG-VH',
                                'reverse_pcr_primer_target_location' => 'IgG, IgM isotype specific reverse primers',
                                'pcr_target_locus' => 'CDR3',
                            ],
                        ],
                        'cell_processing_protocol' => null,
                        'collection_time_point_relative' => 'Time 0',
                        'sequencing_platform' => '454 GS FLX Titanium',
                        'cell_number' => null,
                        'library_generation_kit_version' => null,
                        'disease_state_sample' => 'Multiple Sclerosis',
                        'biomaterial_provider' => null,
                        'template_amount' => null,
                        'sequencing_run_date' => null,
                        'cell_subset' => 'Peripheral blood mononuclear cells',
                        'template_class' => 'RNA',
                        'template_quality' => null,
                        'sample_type' => null,
                        'library_generation_protocol' => null,
                        'cell_quality' => null,
                        'single_cell' => false,
                        'anatomic_site' => null,
                        'cell_isolation' => null,
                        'tissue_processing' => null,
                        'complete_sequences' => 'complete ',
                        'read_length' => null,
                        'collection_time_point_reference' => 'Sample collection',
                        'sample_id' => '26712_CSF',
                        'cells_per_reaction' => '1*10^9',
                        'library_generation_method' => 'RT-PCR',
                        'sequencing_run_id' => null,
                        'cell_storage' => null,
                        'sequencing_facility' => null,
                    ],
                ],
                'study' => (object) [
                    'lab_address' => 'University of California San Francisco (UCSF)',
                    'study_id' => 'PRJNA248411',
                    'grants' => null,
                    'study_description' => 'Multiple Sclerosis Study',
                    'inclusion_exclusion_criteria' => null,
                    'submitted_by' => 'Palanichamy, Apeltsin et al ',
                    'pub_ids' => 'PMC4176763',
                    'study_title' => 'Immunoglobulin class-switched B cells provide an active immune axis between CNS and periphery in multiple sclerosis',
                    'lab_name' => 'Von Budingen Lab',
                    'collected_by' => 'Hans-Christian.vonBuedingen@ucsf.edu',
                ],
                'data_processing' => (object) [
                    'paired_reads_assembly' => null,
                    'primer_match_cutoffs' => null,
                    'software_versions' => 'SRA Toolkit 2.8.2-1, cutadapt 1.14, fastqc 0.11.5, pear 0.9.10, biopython 2.7.13, igblast 1.8.0',
                    'collapsing_method' => null,
                    'quality_thresholds' => 30,
                    'data_processing_protocols' => 'Barcodes and primers removed and igblast/imgt annotation of quality filtered sequences',
                ],
                'real_rest_service_id' => 40,
                'ir_sequence_count' => 4846,
                'ir_filtered_sequence_count' => 4846,
            ],
        ];

        self::$sequence_list = [
            [
                'Info' => (object) [
                    'Title' => 'AIRR Data Commons API',
                    'description' => 'API response for repertoire query',
                    'version' => 1.3,
                    'contact' => (object) [
                        'name' => 'AIRR Community',
                        'url' => 'https://github.com/airr-community',
                    ],
                ],
                'Rearrangement' => [
                    0 => (object) [
                        'd_call' => 'IGHD4-11*01',
                        'junction_aa' => 'CARHLWTTTTFDYW',
                        'j_call' => 'IGHJ4*02',
                        'v_call' => 'IGHV4-39*01',
                    ],
                    1 => (object) [
                        'd_call' => 'IGHD4-11*01',
                        'junction_aa' => 'CARHLWTTTTFDYW',
                        'j_call' => 'IGHJ4*02',
                        'v_call' => 'IGHV4-39*05, or IGHV4-39*01',
                    ],
                    2 => (object) [
                        'd_call' => 'IGHD4-11*01',
                        'junction_aa' => 'CARHLWTTTTFDYW',
                        'j_call' => 'IGHJ4*02',
                        'v_call' => 'IGHV4-39*01',
                    ],
                    3 => (object) [
                        'd_call' => 'IGHD4-11*01',
                        'junction_aa' => 'CARHLWTTTTFDYW',
                        'j_call' => 'IGHJ4*02',
                        'v_call' => 'IGHV4-39*01',
                    ],
                    4 => (object) [
                        'd_call' => 'IGHD4-11*01',
                        'junction_aa' => 'CARHLWTTTTFDYW',
                        'j_call' => 'IGHJ4*02',
                        'v_call' => 'IGHV4-39*01',
                    ],
                    5 => (object) [
                        'd_call' => 'IGHD4-11*01',
                        'junction_aa' => 'CARHLWTTTTFDYW',
                        'j_call' => 'IGHJ4*02',
                        'v_call' => 'IGHV4-39*01',
                    ],
                    6 => (object) [
                        'd_call' => 'IGHD4-11*01',
                        'junction_aa' => 'CARHLWTTTTFDYW',
                        'j_call' => 'IGHJ4*02',
                        'v_call' => 'IGHV4-39*01',
                    ],
                    7 => (object) [
                        'd_call' => 'IGHD4-11*01',
                        'junction_aa' => 'CARHLWTTTTFDYW',
                        'j_call' => 'IGHJ4*02',
                        'v_call' => 'IGHV4-39*01',
                    ],
                    8 => (object) [
                        'd_call' => 'IGHD4-11*01',
                        'junction_aa' => 'CARHLWTTTTFDYW',
                        'j_call' => 'IGHJ4*02',
                        'v_call' => 'IGHV4-39*01',
                    ],
                    9 => (object) [
                        'd_call' => 'IGHD4-11*01',
                        'junction_aa' => 'CARHLWTTTTFDYW',
                        'j_call' => 'IGHJ4*02',
                        'v_call' => 'IGHV4-39*01',
                    ],
                ],
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Simulate service returning sequence summary with 1 item,
    | to test that sequence page works even if fields are missing
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function full_sequence_summary()
    {
        $response_list = [
            [
                'rs' => (object) self::$rs,
                'status' => 'success',
                'data' => self::$repertoire_data,
            ],
        ];

        $sequence_list_response = [
            [
                'rs' => (object) self::$rs,
                'status' => 'success',
                'data' => self::$sequence_list,
                'query_log_id' => '5da77e26a98320062425ad8a',
            ],
        ];

        // mock Query::getParams()
        Query::shouldReceive('getParams')->andReturn(self::$query_params);

        // mock RestService::sequences_summary()
        RestService::shouldReceive('data_summary')->once()->andReturn($response_list);
        RestService::shouldReceive('data_subset')->once()->andReturn($sequence_list_response);

        // generate fake user
        $u = User::factory()->make();

        // test sequence page is working
        $this->actingAs($u)->get('/sequences?query_id=0')->assertOk();
    }

    /** @test */
    public function incomplete_sequence_data()
    {
        // generate fake user
        $u = User::factory()->make();

        $repertoire_data = self::$repertoire_data;

        // get list of fields in random order
        $keys = get_data_fields($repertoire_data);

        while ($key = array_shift($keys)) {
            // set element to null
            Log::debug('Set ' . $key . ' to null');
            data_set($repertoire_data, $key, null);
            // Log::debug($repertoire_data);

            $response_list = [
                [
                    'rs' => (object) self::$rs,
                    'status' => 'success',
                    'data' => $repertoire_data,
                ],
            ];

            $sequence_list_response = [
                [
                    'rs' => (object) self::$rs,
                    'status' => 'success',
                    'data' => self::$sequence_list,
                    'query_log_id' => '5da77e26a98320062425ad8a',
                ],
            ];

            // mock Query::getParams()
            Query::shouldReceive('getParams')->andReturn(self::$query_params);

            // mock RestService::sequences_summary()
            RestService::shouldReceive('data_summary')->once()->andReturn($response_list);
            RestService::shouldReceive('data_subset')->once()->andReturn($sequence_list_response);

            // test sequence page is working
            $this->actingAs($u)->get('/sequences?query_id=0')->assertOk();
        }
    }
}
