<?php

namespace Tests\Feature;

use Tests\TestCase;
use Facades\App\Query;
use Facades\App\RestService;
use Illuminate\Support\Facades\Log;

class SequenceTest extends TestCase
{
    protected static $rs = [
        'id' => 1,
        'name' => 'Fake Rest Service',
        'display_name' => 'Fake Rest Service',
    ];

    protected static $query_params = [
        'ir_project_sample_id_list_3' => [0 => '299', 1 => '475'],
    ];

    protected static $repertoire_data = [];

    protected static $sequence_list = [];  

    public static function setUpBeforeClass()
    {
      // fwrite(STDERR, "test");
        self::$repertoire_data = array((object)array(
   'repertoire_id' => 8,
   'subject' => 
  (object)array(
     'sex' => 'Female',
     'diagnosis' => 
    (object)array(
       'disease_length' => NULL,
       'immunogen' => NULL,
       'medical_history' => NULL,
       'intervention' => NULL,
       'disease_diagnosis' => 'Multiple Sclerosis',
       'study_group_description' => 'Case',
       'disease_stage' => NULL,
       'prior_therapies' => NULL,
    ),
     'linked_subjects' => NULL,
     'strain_name' => NULL,
     'race' => NULL,
     'age' => '54 years',
     'subject_id' => '26712_CSF',
     'organism' => 
    (object)array(
       'value' => 'Homo sapiens',
    ),
     'link_type' => NULL,
     'ethnicity' => NULL,
     'ancestry_population' => NULL,
     'synthetic' => false,
     'age_event' => NULL,
  ),
   'sample' => 
  array (
    0 => 
    (object)array(
       'sequencing_kit' => NULL,
       'total_reads_passing_qc_filter' => 4854,
       'tissue' => 'Blood',
       'cell_phenotype' => 'CD19, IgD, CD27',
       'physical_linkage' => NULL,
       'pcr_target' => 
      array (
        0 => 
        (object)array(
           'forward_pcr_primer_target_location' => 'IgM-VH, IgG-VH',
           'reverse_pcr_primer_target_location' => 'IgG, IgM isotype specific reverse primers',
           'pcr_target_locus' => 'CDR3',
        ),
      ),
       'cell_processing_protocol' => NULL,
       'collection_time_point_relative' => 'Time 0',
       'sequencing_platform' => '454 GS FLX Titanium',
       'cell_number' => NULL,
       'library_generation_kit_version' => NULL,
       'disease_state_sample' => 'Multiple Sclerosis',
       'biomaterial_provider' => NULL,
       'template_amount' => NULL,
       'sequencing_run_date' => NULL,
       'cell_subset' => 'Peripheral blood mononuclear cells',
       'template_class' => 'RNA',
       'template_quality' => NULL,
       'sample_type' => NULL,
       'library_generation_protocol' => NULL,
       'cell_quality' => NULL,
       'single_cell' => false,
       'anatomic_site' => NULL,
       'cell_isolation' => NULL,
       'tissue_processing' => NULL,
       'complete_sequences' => 'complete ',
       'read_length' => NULL,
       'collection_time_point_reference' => 'Sample collection',
       'sample_id' => '26712_CSF',
       'cells_per_reaction' => '1*10^9',
       'library_generation_method' => 'RT-PCR',
       'sequencing_run_id' => NULL,
       'cell_storage' => NULL,
       'sequencing_facility' => NULL,
    ),
  ),
   'study' => 
  (object)array(
     'lab_address' => 'University of California San Francisco (UCSF)',
     'study_id' => 'PRJNA248411',
     'grants' => NULL,
     'study_description' => 'Multiple Sclerosis Study',
     'inclusion_exclusion_criteria' => NULL,
     'submitted_by' => 'Palanichamy, Apeltsin et al ',
     'pub_ids' => 'PMC4176763',
     'study_title' => 'Immunoglobulin class-switched B cells provide an active immune axis between CNS and periphery in multiple sclerosis',
     'lab_name' => 'Von Budingen Lab',
     'collected_by' => 'Hans-Christian.vonBuedingen@ucsf.edu',
  ),
   'data_processing' => 
  (object)array(
     'paired_reads_assembly' => NULL,
     'primer_match_cutoffs' => NULL,
     'software_versions' => 'SRA Toolkit 2.8.2-1, cutadapt 1.14, fastqc 0.11.5, pear 0.9.10, biopython 2.7.13, igblast 1.8.0',
     'collapsing_method' => NULL,
     'quality_thresholds' => 30,
     'data_processing_protocols' => 'Barcodes and primers removed and igblast/imgt annotation of quality filtered sequences',
  ),
   'real_rest_service_id' => 40,
   'ir_sequence_count' => 4846,
   'ir_filtered_sequence_count' => 4846
));

self::$sequence_list = array(array(
       'Info' => 
      (object)array(
         'Title' => 'AIRR Data Commons API',
         'description' => 'API response for repertoire query',
         'version' => 1.3,
         'contact' => 
        (object)array(
           'name' => 'AIRR Community',
           'url' => 'https://github.com/airr-community',
        ),
      ),
       'Rearrangement' => 
      array (
        0 => 
        (object)array(
           'd_call' => 'IGHD4-11*01',
           'junction_aa' => 'CARHLWTTTTFDYW',
           'j_call' => 'IGHJ4*02',
           'v_call' => 'IGHV4-39*01',
        ),
        1 => 
        (object)array(
           'd_call' => 'IGHD4-11*01',
           'junction_aa' => 'CARHLWTTTTFDYW',
           'j_call' => 'IGHJ4*02',
           'v_call' => 'IGHV4-39*05, or IGHV4-39*01',
        ),
        2 => 
        (object)array(
           'd_call' => 'IGHD4-11*01',
           'junction_aa' => 'CARHLWTTTTFDYW',
           'j_call' => 'IGHJ4*02',
           'v_call' => 'IGHV4-39*01',
        ),
        3 => 
        (object)array(
           'd_call' => 'IGHD4-11*01',
           'junction_aa' => 'CARHLWTTTTFDYW',
           'j_call' => 'IGHJ4*02',
           'v_call' => 'IGHV4-39*01',
        ),
        4 => 
        (object)array(
           'd_call' => 'IGHD4-11*01',
           'junction_aa' => 'CARHLWTTTTFDYW',
           'j_call' => 'IGHJ4*02',
           'v_call' => 'IGHV4-39*01',
        ),
        5 => 
        (object)array(
           'd_call' => 'IGHD4-11*01',
           'junction_aa' => 'CARHLWTTTTFDYW',
           'j_call' => 'IGHJ4*02',
           'v_call' => 'IGHV4-39*01',
        ),
        6 => 
        (object)array(
           'd_call' => 'IGHD4-11*01',
           'junction_aa' => 'CARHLWTTTTFDYW',
           'j_call' => 'IGHJ4*02',
           'v_call' => 'IGHV4-39*01',
        ),
        7 => 
        (object)array(
           'd_call' => 'IGHD4-11*01',
           'junction_aa' => 'CARHLWTTTTFDYW',
           'j_call' => 'IGHJ4*02',
           'v_call' => 'IGHV4-39*01',
        ),
        8 => 
        (object)array(
           'd_call' => 'IGHD4-11*01',
           'junction_aa' => 'CARHLWTTTTFDYW',
           'j_call' => 'IGHJ4*02',
           'v_call' => 'IGHV4-39*01',
        ),
        9 => 
        (object)array(
           'd_call' => 'IGHD4-11*01',
           'junction_aa' => 'CARHLWTTTTFDYW',
           'j_call' => 'IGHJ4*02',
           'v_call' => 'IGHV4-39*01',
        )
      )
    ));

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
            'query_log_id' => '5da77e26a98320062425ad8a'
          ],
        ];

        // // mock Query::getParams()
        // Query::shouldReceive('getParams')->andReturn(self::$query_params);

        // dd($sequence_list_response);

        // mock RestService::sequences_summary()
        RestService::shouldReceive('sequences_summary')->once()->andReturn($response_list);
        RestService::shouldReceive('sequence_list')->once()->andReturn($sequence_list_response);

        // generate fake user
        $u = factory(\App\User::class)->make();
        $this->actingAs($u)->get('/sequences?query_id=0')->assertOk();
    }

    // /** @test */
    // public function incomplete_sequence_data()
    // {
    //     // generate fake user
    //     $u = factory(\App\User::class)->make();

    //     // mock Query::getParams()
    //     Query::shouldReceive('getParams')->andReturn(self::$query_params);

    //     // get list of sample fields in random order
    //     $keys = array_keys(self::$sample_info);
    //     shuffle($keys);

    //     // remove one field at the time
    //     while ($key = array_pop($keys)) {
    //         unset(self::$sample_info[$key]);
    //         Log::debug('Removing sample_info field: ' . $key);

    //         $response_list = [
    //             [
    //                 'rs' => (object) self::$rs,
    //                 'status' => 'success',
    //                 'data' => (object) [
    //                     'summary' => [(object) self::$sample_info],
    //                     'items' => [(object) self::$sequence_item],
    //                 ],
    //             ],
    //         ];

    //         // mock RestService::sequences_summary()
    //         RestService::shouldReceive('sequences_summary')->once()->andReturn($response_list);

    //         self::$actingAs($u)->get('/sequences?query_id=0')->assertOk();
    //     }

    //     // get list of sequence fields in random order
    //     $keys = array_keys(self::$sequence_item);
    //     shuffle($keys);

    //     // remove one field at the time
    //     while ($key = array_pop($keys)) {
    //         unset(self::$sequence_item[$key]);
    //         Log::debug('Removing sequence field: ' . $key);

    //         $response_list = [
    //             [
    //                 'rs' => (object) self::$rs,
    //                 'status' => 'success',
    //                 'data' =>  [
    //                     (object) self::$sample_info]
    //                 ],
    //             ],
    //         ];

    //         // mock RestService::sequences_summary()
    //         RestService::shouldReceive('sequences_summary')->once()->andReturn($response_list);

    //         self::$actingAs($u)->get('/sequences?query_id=0')->assertOk();
    //     }
    // }
}
