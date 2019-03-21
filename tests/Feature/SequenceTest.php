<?php

namespace Tests\Feature;

use Tests\TestCase;
use Facades\App\Query;
use Facades\App\RestService;
use Illuminate\Support\Facades\Log;

class SequenceTest extends TestCase
{
    private $rs = [
        'id' => 1,
        'name' => 'Fake Rest Service',
    ];

    private $query_params = [
        'ir_project_sample_id_list_3' => [0 => '299', 1 => '475'],
    ];

    private $sample_info = [
          '_id' => 299,
          'collapsing_method' => null,
          'collection_time_point_relative' => 'one',
          'sequencing_facility' => null,
          'cell_phenotype' => null,
          'paired_read_assembly' => null,
          'ethnicity' => null,
          'sequencing_platform' => '454 GS FLX Titanium',
          'cell_subset' => 'Mature B cell',
          'sequencing_kit' => null,
          'germline_database' => null,
          'ir_subject_age' => '24',
          'organism' => 'Homo sapiens',
          'tissue' => 'PBMC',
          'collected_by' => 'Bashford-Rogers, Palser',
          'link_type' => null,
          'immunogen' => null,
          'cell_storage' => null,
          'age_event' => null,
          'medical_history' => null,
          'reverse_PCR_primer_target_location' => 'J gene',
          'disease_state_sample' => 'Healthy',
          'study_id' => 'PRJEB1289',
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
          'fasta_file_name' => 'filtered_ERR220445.barcode13.fasta',
          'library_construction_method' => 'Random',
          'data_processing_protocols' => null,
          'library_generation_protocol' => null,
          'biomaterial_provider' => null,
          'disease_stage' => null,
          'library_source' => 'Genomic',
          'study_group_description' => 'Control',
          'sample_id' => ' Healthy_12_barcode13',
          'lab_name' => 'The Wellcome Trust Sanger Institute',
          'pcr_target_locus' => null,
          'lab_address' => null,
          'sequencing_run_id' => null,
          'primer_match_cutoffs' => null,
          'anatomic_site' => null,
          'imgt_file_name' => 'ERR220445.barcode13.txz',
          'complete_sequences' => null,
          'strain_name' => null,
          'library_generation_kit_version' => null,
          'cell_quality' => null,
          'cell_processing_protocol' => null,
          'study_description' => 'Cancer Study',
          'ir_sra_run_id' => 'ERR220445',
          'template_amount' => null,
          'submitted_by' => 'Bashford-Rogers, Palser',
          'mixcr_file_name' => null,
          'collection_time_event' => null,
          'read_length' => null,
          'ancestry_population' => null,
          'cells_per_reaction' => '1*10^6',
          'subject_id' => ' Healthy_12_barcode13',
          'igblast_file_name' => 'filtered_ERR220445.barcode13.fmt7',
          'intervention' => null,
          'linked_subjects' => null,
          'total_reads_passing_qc_filter' => '37,532',
          'cell_number' => '32,631',
          'ir_sequence_count' => 21861,
          'forward_PCR_primer_target_location' => 'V gene',
          'physical_linkage' => null,
          'sex' => 'F',
          'synthetic' => null,
          'inclusion_exclusion_criteria' => null,
          'sample_type' => null,
          'disease_length' => null,
          'template_class' => 'cDNA',
          'study_title' => 'Network properties derived from deep sequencing of human B-cell receptor repertoires delineate B-cell populations',
          'pub_ids' => null,
          'disease_diagnosis' => 'Healthy',
          'ir_subject_age_min' => 24,
          'ir_subject_age_max' => 24,
          'ir_filtered_sequence_count' => 21861,
    ];

    private $sequence_item = [
        'ir_junction_aa_length' => 16,
        'vjregion_sequence_aa' => '',
        'v_call' => 'IGHV3-48*03, or IGHV3-7*02, or IGHV3-11*06, or IGHV3-7*01, or IGHV3-48*04, or IGHV3-69-1*01, or IGHV3-69-1*02, or IGHV3-21*01, or IGHV3-21*02',
        'fr2region_mutation_string' => '',
        'fr1region_sequence_aa_gapped' => '..........................',
        'fr1region_sequence_nt_gapped' => '..............................................................................',
        'vdjregion_sequence_nt_gapped' => '.......................................................................................................................................................................................................................................atctccagagacaacgccaacaacttattgtttctgcaattgaacagcctgagagccgaggacacggctgtatattactgtgcgagagatttctattattacgatcgtagtgcttttggcttctggggccagggaaccctggtcaccgtctcctcag',
        'vdjregion_sequence_nt' => 'atctccagagacaacgccaacaacttattgtttctgcaattgaacagcctgagagccgaggacacggctgtatattactgtgcgagagatttctattattacgatcgtagtgcttttggcttctggggccagggaaccctggtcaccgtctcctcag',
        'fr3region_mutation_string' => 'g252>c,K84>N(+ - -); K84 aag 250-252>N aac|c257>t,S86>L(- - -); S86 tca 256-258>L tta|c259>t, L87; L87 ctg 259-261>L ttg|a263>t,Y88>F(- + -); Y88 tat 262-264 [ta 262-263]>F ttt|a271>t,M91>L(+ + -); M91 atg 271-273 [aa 270-271]>L ttg|g303>a, V101; V101 gtg 301-303>V gta|',
        'junction_aa' => 'CARDFYYYDRSAFGFW',
        'fwr4_start' => 124,
        'fr4region_sequence_aa_gapped' => 'WGQGTLVTVSS',
        'cdr3region_sequence_aa' => 'ARDFYYYDRSAFGF',
        'functionality' => 'productive',
        'no_nucleotide_to_exclude' => ' 0',
        'fwr1_end' => '',
        'cdr3_length' => 14,
        'reference_version' => '201728-2',
        'cdr1region_sequence_nt' => '',
        'djregion_sequence_nt' => 'tattattacgatcgtagtgcttttggcttctggggccagggaaccctggtcaccgtctcctcag',
        'cdr2_length' => 0,
        'cdr1_length' => 0,
        'fwr1_start' => '',
        'cdr1_end' => '',
        'v_string' => 'Homsap IGHV3-11*06 F, or Homsap IGHV3-21*01 F or Homsap IGHV3-21*02 F or Homsap IGHV3-48*03 F or Homsap IGHV3-48*04 F or Homsap IGHV3-69-1*01 P or Homsap IGHV3-69-1*02 P or Homsap IGHV3-7*01 F or Homsap IGHV3-7*02 F',
        'dregion_reading_frame' => 2,
        'cdr3_start' => 82,
        'vregion_mutation_string' => 'g252>c,K84>N(+ - -); K84 aag 250-252>N aac|c257>t,S86>L(- - -); S86 tca 256-258>L tta|c259>t, L87; L87 ctg 259-261>L ttg|a263>t,Y88>F(- + -); Y88 tat 262-264 [ta 262-263]>F ttt|a271>t,M91>L(+ + -); M91 atg 271-273 [aa 270-271]>L ttg|g303>a, V101; V101 gtg 301-303>V gta|',
        'cdr2_start' => '',
        'fr3region_sequence_aa' => 'ISRDNANNLLFLQLNSLRAEDTAVYYC',
        'v_start' => 1,
        'cdr1region_sequence_aa_gapped' => '............',
        'cdr1region_sequence_aa' => '',
        'fr3region_sequence_nt' => 'atctccagagacaacgccaacaacttattgtttctgcaattgaacagcctgagagccgaggacacggctgtatattactgt',
        'functional' => true,
        'vjregion_sequence_nt_gapped' => '',
        'd_string' => 'Homsap IGHD3-22*01 F',
        'vdjregion_sequence_aa_gapped' => '.............................................................................ISRDNANNLLFLQLNSLRAEDTAVYYCARDFYYYDRSAFGFWGQGTLVTVSS',
        'vregion_sequence_aa_gapped' => '.............................................................................ISRDNANNLLFLQLNSLRAEDTAVYYCAR',
        'vregion_sequence_nt' => 'atctccagagacaacgccaacaacttattgtttctgcaattgaacagcctgagagccgaggacacggctgtatattactgtgcgagaga',
        'v_score' => 346,
        'd_call' => 'IGHD3-22*01',
        'rev_comp' => '-',
        'j_score' => 177,
        'dregion_sequence_nt' => 'tattattacgatcgtagtg',
        'seq_name' => 'ERR220445.171258 GZVATEZ02HOW92 length=162',
        'annotation_date' => 'Fri Aug 11 22:28:35 CEST 2017',
        'fr4region_sequence_nt_gapped' => 'tggggccagggaaccctggtcaccgtctcctcag',
        'jregion_sequence_aa_gapped' => 'FGFWGQGTLVTVSS',
        'fr4region_sequence_aa' => 'WGQGTLVTVSS',
        'junction_nt' => 'tgtgcgagagatttctattattacgatcgtagtgcttttggcttctgg',
        'vregion_sequence_aa' => 'ISRDNANNLLFLQLNSLRAEDTAVYYCAR',
        'cdr1_start' => '',
        'junction_sequence_nt_gapped' => 'tgtgcgagagatttctattattacgatcgtagtgcttttggcttctgg',
        'receptor_type' => 'IGH',
        'fr1region_sequence_aa' => '',
        'fr2region_sequence_aa_gapped' => '.................',
        'cdr3region_sequence_nt' => 'gcgagagatttctattattacgatcgtagtgcttttggcttc',
        'jregion_sequence_nt_gapped' => 'tttggcttctggggccagggaaccctggtcaccgtctcctcag',
        'vjregion_sequence_aa_gapped' => '',
        'fr1region_sequence_nt' => '',
        'functionality_comment' => '',
        'fr2region_sequence_aa' => '',
        'vregion_sequence_nt_gapped' => '.......................................................................................................................................................................................................................................atctccagagacaacgccaacaacttattgtttctgcaattgaacagcctgagagccgaggacacggctgtatattactgtgcgagaga',
        'fr2region_sequence_nt' => '',
        'cdr1region_sequence_nt_gapped' => '....................................',
        'vgene_probability' => 92.59,
        'vdjregion_sequence_aa' => 'ISRDNANNLLFLQLNSLRAEDTAVYYCARDFYYYDRSAFGFWGQGTLVTVSS',
        'j_start' => 115,
        'no_nucleotide_to_add' => '0',
        'cdr3region_sequence_aa_gapped' => 'ARDFYYYDRSAFGF',
        'vgene_probablity' => 92.59,
        'vdjregion_start' => 1,
        'vdjregion_end' => 157,
        'jregion_sequence_aa' => 'FGFWGQGTLVTVSS',
        'fr4region_sequence_nt' => 'tggggccagggaaccctggtcaccgtctcctcag',
        'cdr2region_sequence_nt_gapped' => '..............................',
        'fr3region_sequence_nt_gapped' => '....................................atctccagagacaacgccaacaacttattgtttctgcaattgaacagcctgagagccgaggacacggctgtatattactgt',
        'cdr2region_sequence_aa' => '',
        'vjregion_start' => '',
        'tool_version' => '3.4.7',
        'jregion_sequence_nt' => 'tttggcttctggggccagggaaccctggtcaccgtctcctcag',
        'j_call' => 'IGHJ4*02',
        'search_insert_delete' => ' yes',
        'v_end' => 89,
        'vjregion_end' => '',
        'species' => 'Homo sapiens',
        'junction_length' => 48,
        'cdr1region_mutation_string' => '',
        'vjregion_sequence_nt' => '',
        'junction_sequence_aa_gapped' => 'CARDFYYYDRSAFGFW',
        'j_string' => 'Homsap IGHJ4*02 F',
        'j_end' => 157,
        'fwr4_end' => 157,
        'fr1region_mutation_string' => '',
        'cdr3region_mutation_string' => '',
        'fwr3_start' => 1,
        'junction_start' => 79,
        'fwr2_start' => '',
        'cdr2region_sequence_aa_gapped' => '..........',
        'cdr2_end' => '',
        'd_end' => 112,
        'reference_directory_set' => ' F+ORF+in-frame P',
        'fr3region_sequence_aa_gapped' => '............ISRDNANNLLFLQLNSLRAEDTAVYYC',
        'fwr3_end' => 81,
        'cdr3_end' => 123,
        'cdr3region_sequence_nt_gapped' => 'gcgagagatttctattattacgatcgtagtgcttttggcttc',
        'junction_end' => 126,
        'djregion_end' => 157,
        'fwr2_end' => '',
        'd_start' => 94,
        'cdr2region_sequence_nt' => '',
        'ir_project_sample_id' => 299,
        'fr2region_sequence_nt_gapped' => '...................................................',
        'djregion_start' => 94,
        'cdr2region_mutation_string' => '',
        'annotation_tool' => 'V-Quest',
        'junction' => 'tgtgcgagagatttctattattacgatcgtagtgcttttggcttctgg',
        'vgene_family' => [
            'IGHV3',
        ],
        'vgene_gene' => [
            'IGHV3-48',
            'IGHV3-7',
            'IGHV3-11',
            'IGHV3-69-1',
            'IGHV3-21',
        ],
        'jgene_family' => [
            'IGHJ4',
        ],
        'jgene_gene' => [
            'IGHJ4',
        ],
        'dgene_family' => [
            'IGHD3',
        ],
            'dgene_gene' => [
            'IGHD3-22',
        ],
        'ir_annotation_tool' => 'V-Quest',
    ];

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
                'rs' => (object) $this->rs,
                'status' => 'success',
                'data' => (object) [
                    'summary' => [(object) $this->sample_info],
                    'items' => [(object) $this->sequence_item],
                ],
            ],
        ];

        // mock Query::getParams()
        Query::shouldReceive('getParams')->andReturn($this->query_params);

        // mock RestService::sequences_summary()
        RestService::shouldReceive('sequences_summary')->once()->andReturn($response_list);

        // generate fake user
        $u = factory(\App\User::class)->make();

        $this->actingAs($u)->get('/sequences?query_id=0')->assertOk();
    }

    /** @test */
    public function incomplete_sequence_data()
    {
        // generate fake user
        $u = factory(\App\User::class)->make();

        // mock Query::getParams()
        Query::shouldReceive('getParams')->andReturn($this->query_params);

        // get list of sample fields in random order
        $keys = array_keys($this->sample_info);
        shuffle($keys);

        // remove one field at the time
        while ($key = array_pop($keys)) {
            unset($this->sample_info[$key]);
            Log::debug('Removing sample_info field: ' . $key);

            $response_list = [
                [
                    'rs' => (object) $this->rs,
                    'status' => 'success',
                    'data' => (object) [
                        'summary' => [(object) $this->sample_info],
                        'items' => [(object) $this->sequence_item],
                    ],
                ],
            ];

            // mock RestService::sequences_summary()
            RestService::shouldReceive('sequences_summary')->once()->andReturn($response_list);

            $this->actingAs($u)->get('/sequences?query_id=0')->assertOk();
        }

        // get list of sequence fields in random order
        $keys = array_keys($this->sequence_item);
        shuffle($keys);

        // remove one field at the time
        while ($key = array_pop($keys)) {
            unset($this->sequence_item[$key]);
            Log::debug('Removing sequence field: ' . $key);

            $response_list = [
                [
                    'rs' => (object) $this->rs,
                    'status' => 'success',
                    'data' => (object) [
                        'summary' => [(object) $this->sample_info],
                        'items' => [(object) $this->sequence_item],
                    ],
                ],
            ];

            // mock RestService::sequences_summary()
            RestService::shouldReceive('sequences_summary')->once()->andReturn($response_list);

            $this->actingAs($u)->get('/sequences?query_id=0')->assertOk();
        }
    }
}
