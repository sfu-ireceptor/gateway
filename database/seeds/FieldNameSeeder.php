<?php

use App\FieldName;
use Flynsarmy\CsvSeeder\CsvSeeder;
use Illuminate\Support\Facades\DB;

class FieldNameSeeder extends CsvSeeder
{
    public function __construct()
    {
        $this->table = 'field_name';
        $this->folder_path = base_path() . '/database/seeds/data/field_names';
        $this->offset_rows = 1;
        $this->csv_delimiter = "\t";
    }

    public function run()
    {
        DB::transaction(function () {
            // delete existing data
            DB::table($this->table)->truncate();

            $this->mapping = [
                0 => 'ir_id',
                1 => 'ir_full',
                2 => 'ir_short',
                15 => 'airr',
                16 => 'airr_full',
                19  => 'ir_class',
                20 => 'ir_subclass',
                21 => 'ir_adc_api_query',
                22 => 'ir_adc_api_response',
                24 => 'airr_type',
                25 => 'airr_description',
                30 => 'airr_example',
                42 => 'ir_api_input_type',
            ];

            $file_list = dir_to_array($this->folder_path);

            foreach ($file_list as $filename) {
                $this->filename = $filename;
                echo 'Adding mapping file ' . $filename . "\n";

                parent::run();

                // delete any empty rows
                DB::table($this->table)->whereNull('ir_id')->delete();

                // add API version
                $api_version = pathinfo($filename)['filename'];
                FieldName::whereNull('api_version')->update(['api_version' => $api_version]);

                // add extra fields (gateway specific)
                $this->add_gateway_specific_fields($api_version);

                // define default fields and their order
                $this->define_default_sample_fields();
                $this->define_default_sequence_fields();
                $this->define_default_clone_fields();
                $this->define_default_cell_fields();
            }
        });
    }

    public function add_gateway_specific_fields($api_version)
    {
        $l = [];
        $l[] = [
            'ir_id' => 'rest_service_name',
            'ir_short' => 'Repository',
            'ir_class' => 'Repertoire',
            'ir_subclass' => 'other',
            'api_version' => $api_version,
        ];

        $l[] = [
            'ir_id' => 'full_text_search',
            'ir_short' => 'Full-text search ',
            'ir_class' => 'Hidden',
            'ir_subclass' => 'other',
            'airr_description' => 'Search across all metadata fields (case insensitive)',
            'airr_example' => 'cancer tumor',
            'api_version' => $api_version,
        ];

        // HACK: override ir_class from "ir_repertoire" to "repertoire", so this field is displayed by default
        $l[] = [
            'ir_id' => 'ir_sequence_count',
            'ir_short' => 'Sequences',
            'ir_class' => 'Repertoire',
            'ir_subclass' => 'other',
            'api_version' => $api_version,
        ];

        $l[] = [
            'ir_id' => 'ir_clone_count',
            'ir_short' => 'Clones',
            'ir_class' => 'Repertoire',
            'ir_subclass' => 'other',
            'api_version' => $api_version,
        ];

        $l[] = [
            'ir_id' => 'ir_cell_count',
            'ir_short' => 'Cells',
            'ir_class' => 'Repertoire',
            'ir_subclass' => 'other',
            'api_version' => $api_version,
        ];

        // HACK: override ir_class from from "ir_rearrangement" to "rearrangement", so this field is displayed by default
        $l[] = [
            'ir_id' => 'ir_junction_aa_length',
            'ir_short' => 'Junction Length (AA)',
            'ir_class' => 'Rearrangement',
            'ir_subclass' => 'Rearrangement',
            'api_version' => $api_version,
        ];

        // cell fields
        $l[] = [
            'ir_id' => 'v_call_1',
            'ir_short' => 'V Gene With Allele (Chain 1)',
            'ir_class' => 'Cell',
            'ir_subclass' => 'Cell',
            'api_version' => $api_version,
        ];

        $l[] = [
            'ir_id' => 'junction_aa_1',
            'ir_short' => 'Junction/CDR3 AA (Chain 1)',
            'ir_class' => 'Cell',
            'ir_subclass' => 'Cell',
            'api_version' => $api_version,
        ];
        $l[] = [
            'ir_id' => 'v_call_2',
            'ir_short' => 'V Gene With Allele (Chain 2)',
            'ir_class' => 'Cell',
            'ir_subclass' => 'Cell',
            'api_version' => $api_version,
        ];

        $l[] = [
            'ir_id' => 'junction_aa_2',
            'ir_short' => 'Junction/CDR3 AA (Chain 2)',
            'ir_class' => 'Cell',
            'ir_subclass' => 'Cell',
            'api_version' => $api_version,
        ];

        $l[] = [
            'ir_id' => 'expression_label_list',
            'ir_short' => 'Properties (top 4)',
            'ir_class' => 'Cell',
            'ir_subclass' => 'Cell',
            'api_version' => $api_version,
        ];

        foreach ($l as $t) {
            FieldName::updateOrCreate(['ir_id' => $t['ir_id'], 'api_version' => $api_version], $t);
        }
    }

    public function define_default_sample_fields()
    {
        $l = [];

        $l[] = ['id' => 'rest_service_name', 'visible' => true];
        $l[] = ['id' => 'study_title', 'visible' => true];
        $l[] = ['id' => 'disease_diagnosis', 'visible' => true];
        $l[] = ['id' => 'study_group_description', 'visible' => true];
        $l[] = ['id' => 'ir_sequence_count', 'visible' => true];
        $l[] = ['id' => 'ir_clone_count', 'visible' => true];
        $l[] = ['id' => 'ir_cell_count', 'visible' => true];
        $l[] = ['id' => 'lab_name', 'visible' => true];
        $l[] = ['id' => 'tissue', 'visible' => true];
        $l[] = ['id' => 'pcr_target_locus', 'visible' => true];
        $l[] = ['id' => 'cell_subset', 'visible' => true];
        $l[] = ['id' => 'cell_phenotype', 'visible' => true];
        $l[] = ['id' => 'pub_ids', 'visible' => true];
        $l[] = ['id' => 'study_id', 'visible' => true];
        $l[] = ['id' => 'subject_id', 'visible' => true];
        $l[] = ['id' => 'sample_id', 'visible' => true];
        $l[] = ['id' => 'template_class', 'visible' => true];
        $l[] = ['id' => 'sequencing_platform', 'visible' => true];

        foreach ($l as $i => $t) {
            FieldName::where('ir_id', $t['id'])->update(['default_order' => $i, 'default_visible' => $t['visible']]);
        }
    }

    public function define_default_sequence_fields()
    {
        $l = [];
        $l[] = ['id' => 'v_call', 'visible' => true];
        $l[] = ['id' => 'd_call', 'visible' => true];
        $l[] = ['id' => 'j_call', 'visible' => true];
        $l[] = ['id' => 'junction_aa', 'visible' => true];
        $l[] = ['id' => 'ir_junction_aa_length', 'visible' => true];

        foreach ($l as $i => $t) {
            FieldName::where('ir_id', $t['id'])->update(['default_order' => $i, 'default_visible' => $t['visible']]);
        }
    }

    public function define_default_cell_fields()
    {
        $l = [];
        $l[] = ['id' => 'cell_id', 'visible' => true];
        $l[] = ['id' => 'cell_id_cell', 'visible' => true];
        $l[] = ['id' => 'expression_study_method_cell', 'visible' => true];
        $l[] = ['id' => 'virtual_pairing_cell', 'visible' => true];
        $l[] = ['id' => 'v_call_1', 'visible' => true];
        $l[] = ['id' => 'junction_aa_1', 'visible' => true];
        $l[] = ['id' => 'v_call_2', 'visible' => true];
        $l[] = ['id' => 'junction_aa_2', 'visible' => true];
        $l[] = ['id' => 'expression_label_list', 'visible' => true];

        foreach ($l as $i => $t) {
            FieldName::where('ir_id', $t['id'])->update(['default_order' => $i, 'default_visible' => $t['visible']]);
        }
    }

    public function define_default_clone_fields()
    {
        $l = [];
        $l[] = ['id' => 'v_call_clone', 'visible' => true];
        $l[] = ['id' => 'd_call_clone', 'visible' => true];
        $l[] = ['id' => 'j_call_clone', 'visible' => true];
        $l[] = ['id' => 'junction_aa_clone', 'visible' => true];

        foreach ($l as $i => $t) {
            FieldName::where('ir_id', $t['id'])->update(['default_order' => $i, 'default_visible' => $t['visible']]);
        }
    }
}
