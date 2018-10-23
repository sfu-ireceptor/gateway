<?php

namespace Tests\Feature;

use Tests\TestCase;
use Facades\App\RestService;
use Illuminate\Support\Facades\Log;

class SampleTest extends TestCase
{
    private $rs = [
        'id' => 1,
        'name' => 'Fake Rest Service',
        'display_name' => 'Fake Rest Service',
    ];

    private $sample = [
        '_id' => 4,
        'collection_time_point_relative' => 'Week 1',
        'sequencing_facility' => null,
        'cell_phenotype' => null,
        'paired_read_assembly' => 'pear -f ERR1278153_filtered_1.fastq -r ERR1278153_filtered_2.fastq -n 60 -t 60 -q 25 -o paired_ERR1278153.fastq',
        'ethnicity' => null,
        'sequencing_platform' => 'Illumina NextSeq',
        'cell_subset' => 'Naive B cell',
        'sequencing_kit' => null,
        'germline_database' => null,
        'ir_subject_age' => '35',
        'organism' => 'Homo sapiens',
        'tissue' => 'PBMC',
        'collected_by' => 'Chang, Y.H., Kuan, H.C.',
        'link_type' => null,
        'immunogen' => null,
        'cell_storage' => null,
        'age_event' => null,
        'medical_history' => null,
        'reverse_PCR_primer_target_location' => 'J gene',
        'disease_state_sample' => 'Healthy',
        'study_id' => 'PRJEB9332',
        'sequencing_run_date' => null,
        'grants' => null,
        'single_cell' => null,
        'software_versions' => null,
        'template_quality' => null,
        'race' => null,
        'cell_isolation' => null,
        'tissue_processing' => null,
        'quality_thresholds' => 25,
        'prior_therapies' => null,
        'fasta_file_name' => 'paired_ERR1278153.fasta',
        'library_construction_method' => 'PCR',
        'data_processing_protocols' => null,
        'library_generation_protocol' => null,
        'biomaterial_provider' => null,
        'disease_stage' => null,
        'library_source' => 'Transcriptomic',
        'study_group_description' => 'Control',
        'sample_id' => 'A2wk1',
        'lab_name' => 'Institute of Molecular and Genomic Medicine, National Health Research Institutes',
        'pcr_target_locus' => null,
        'lab_address' => null,
        'sequencing_run_id' => null,
        'primer_match_cutoffs' => null,
        'anatomic_site' => null,
        'imgt_file_name' => 'ERR1278153_aa.txz, ERR1278153_ab.txz, ERR1278153_ac.txz',
        'complete_sequences' => null,
        'strain_name' => null,
        'library_generation_kit_version' => null,
        'cell_quality' => null,
        'cell_processing_protocol' => null,
        'study_description' => 'Hepatitis B Study',
        'ir_sra_run_id' => 'ERR1278153',
        'template_amount' => null,
        'submitted_by' => 'Chang, Y.H., Kuan, H.C.',
        'mixcr_file_name' => null,
        'collection_time_event' => 'Vaccination',
        'read_length' => null,
        'ancestry_population' => null,
        'cells_per_reaction' => null,
        'subject_id' => 'Adult 2',
        'igblast_file_name' => 'ERR1278153.fmt7',
        'intervention' => null,
        'linked_subjects' => null,
        'total_reads_passing_qc_filter' => '1,463,721',
        'cell_number' => '2,369,736',
        'ir_sequence_count' => 1000000,
        'forward_PCR_primer_target_location' => 'V gene',
        'physical_linkage' => null,
        'sex' => 'F',
        'synthetic' => null,
        'inclusion_exclusion_criteria' => null,
        'sample_type' => null,
        'disease_length' => null,
        'template_class' => 'cDNA',
        'study_title' => 'Network Signatures of IgG Immune Repertoires in Hepatitis B Associated Chronic Infection and Vaccination Responses.',
        'pub_ids' => null,
        'disease_diagnosis' => 'Healthy Vaccinated with HBV',
        'ir_subject_age_min' => 35,
        'ir_subject_age_max' => 35,
        'ir_project_sample_id' => 4,
        'real_rest_service_id' => 1,
    ];

    /*
    |--------------------------------------------------------------------------
    | Simulate service returning 1 sample,
    | to test that sample page works even if fields are missing
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function full_sample()
    {
        $response_list = [
            [
                'rs' => (object) $this->rs,
                'status' => 'success',
                'data' => [(object) $this->sample],
            ],
        ];

        // mock RestService::samples()
        RestService::shouldReceive('samples')->andReturn($response_list);

        // generate fake user
        $u = factory(\App\User::class)->make();

        $this->actingAs($u)->get('/samples')->assertOk();
    }

    /** @test */
    public function incomplete_sample()
    {
        // generate fake user
        $u = factory(\App\User::class)->make();

        // get list of sample fields in random order
        $keys = array_keys($this->sample);
        shuffle($keys);

        // remove one field at the time
        while ($key = array_pop($keys)) {
            unset($this->sample[$key]);
            Log::debug('Removing sample field: ' . $key);

            $response_list = [
                [
                    'rs' => (object) $this->rs,
                    'status' => 'success',
                    'data' => [(object) $this->sample],
                ],
            ];

            // mock RestService::samples()
            RestService::shouldReceive('samples')->andReturn($response_list);

            $this->actingAs($u)->get('/samples')->assertOk();
        }
    }
}
