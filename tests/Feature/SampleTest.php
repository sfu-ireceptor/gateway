<?php

namespace Tests\Feature;

use Tests\TestCase;
use Facades\App\RestService;

class SampleTest extends TestCase
{
    /** @test */
    public function samples()
    {
        $rs = ['id' => 1, 'name' => 'Fake Rest Service'];
        $sample = [
            '_id' => 4,
            'collection_time_point_relative' => 'Week 1',
            'sequencing_facility' => NULL,
            'cell_phenotype' => NULL,
            'paired_read_assembly' => 'pear -f ERR1278153_filtered_1.fastq -r ERR1278153_filtered_2.fastq -n 60 -t 60 -q 25 -o paired_ERR1278153.fastq',
            'ethnicity' => NULL,
            'sequencing_platform' => 'Illumina NextSeq',
            'cell_subset' => 'Naive B cell',
            'sequencing_kit' => NULL,
            'germline_database' => NULL,
            'ir_subject_age' => '35',
            'organism' => 'Homo sapiens',
            'tissue' => 'PBMC',
            'collected_by' => 'Chang, Y.H., Kuan, H.C.',
            'link_type' => NULL,
            'immunogen' => NULL,
            'cell_storage' => NULL,
            'age_event' => NULL,
            'medical_history' => NULL,
            'reverse_PCR_primer_target_location' => 'J gene',
            'disease_state_sample' => 'Healthy',
            'study_id' => 'PRJEB9332',
            'sequencing_run_date' => NULL,
            'grants' => NULL,
            'single_cell' => NULL,
            'software_versions' => NULL,
            'template_quality' => NULL,
            'race' => NULL,
            'cell_isolation' => NULL,
            'tissue_processing' => NULL,
            'quality_thresholds' => 25,
            'prior_therapies' => NULL,
            'fasta_file_name' => 'paired_ERR1278153.fasta',
            'library_construction_method' => 'PCR',
            'data_processing_protocols' => NULL,
            'library_generation_protocol' => NULL,
            'biomaterial_provider' => NULL,
            'disease_stage' => NULL,
            'library_source' => 'Transcriptomic',
            'study_group_description' => 'Control',
            'sample_id' => 'A2wk1',
            'lab_name' => 'Institute of Molecular and Genomic Medicine, National Health Research Institutes',
            'pcr_target_locus' => NULL,
            'lab_address' => NULL,
            'sequencing_run_id' => NULL,
            'primer_match_cutoffs' => NULL,
            'anatomic_site' => NULL,
            'imgt_file_name' => 'ERR1278153_aa.txz, ERR1278153_ab.txz, ERR1278153_ac.txz',
            'complete_sequences' => NULL,
            'strain_name' => NULL,
            'library_generation_kit_version' => NULL,
            'cell_quality' => NULL,
            'cell_processing_protocol' => NULL,
            'study_description' => 'Hepatitis B Study',
            'ir_sra_run_id' => 'ERR1278153',
            'template_amount' => NULL,
            'submitted_by' => 'Chang, Y.H., Kuan, H.C.',
            'mixcr_file_name' => NULL,
            'collection_time_event' => 'Vaccination',
            'read_length' => NULL,
            'ancestry_population' => NULL,
            'cells_per_reaction' => NULL,
            'subject_id' => 'Adult 2',
            'igblast_file_name' => 'ERR1278153.fmt7',
            'intervention' => NULL,
            'linked_subjects' => NULL,
            'total_reads_passing_qc_filter' => '1,463,721',
            'cell_number' => '2,369,736',
            'ir_sequence_count' => 1000000,
            'forward_PCR_primer_target_location' => 'V gene',
            'physical_linkage' => NULL,
            'sex' => 'F',
            'synthetic' => NULL,
            'inclusion_exclusion_criteria' => NULL,
            'sample_type' => NULL,
            'disease_length' => NULL,
            'template_class' => 'cDNA',
            'study_title' => 'Network Signatures of IgG Immune Repertoires in Hepatitis B Associated Chronic Infection and Vaccination Responses.',
            'pub_ids' => NULL,
            'disease_diagnosis' => 'Healthy Vaccinated with HBV',
            'ir_subject_age_min' => 35,
            'ir_subject_age_max' => 35,
            'ir_project_sample_id' => 4
        ];

        $response_list = [
            [
                'rs' => (object)$rs,
                'status' => 'success',
                'data' => [(object)$sample]
            ]
        ];

        RestService::shouldReceive('samples')->once()->andReturn($response_list);

        $u = factory(\App\User::class)->make();
        $this->actingAs($u)->get('/samples')->assertOk();
    }
}
