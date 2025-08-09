<?php

namespace Database\Seeders;

use App\FieldName;
use Flynsarmy\CsvSeeder\CsvSeeder;
use Illuminate\Support\Facades\DB;

class FieldNameSeeder extends CsvSeeder
{
    public function __construct()
    {
        $this->table = 'field_name';
        $this->folder_path = base_path() . '/database/seeders/data/field_names';
        $this->offset_rows = 1;
        $this->csv_delimiter = "\t";
    }

    public function run()
    {
        // delete existing data
        DB::table($this->table)->truncate();

        $file_list = dir_to_array($this->folder_path);

        foreach ($file_list as $filename) {
            $api_version = pathinfo($filename)['filename'];

            if ($api_version == '1.0') {
                $this->mapping = [
                    0 => 'ir_id',
                    1 => 'ir_full',
                    2 => 'ir_short',
                    14 => 'airr',
                    15 => 'airr_full',
                    18 => 'ir_class',
                    19 => 'ir_subclass',
                    20 => 'ir_adc_api_query',
                    21 => 'ir_adc_api_response',
                    23 => 'airr_type',
                    24 => 'airr_description',
                    29 => 'airr_example',
                    41 => 'ir_api_input_type',
                ];
            } elseif ($api_version == '1.2') {
                $this->mapping = [
                    0 => 'ir_id',
                    1 => 'ir_full',
                    2 => 'ir_short',
                    24 => 'airr',
                    25 => 'airr_full',
                    28 => 'ir_class',
                    29 => 'ir_subclass',
                    30 => 'ir_adc_api_query',
                    31 => 'ir_adc_api_response',
                    33 => 'airr_type',
                    34 => 'airr_description',
                    39 => 'airr_example',
                    51 => 'ir_api_input_type',
                ];
            }

            $this->filename = $filename;
            echo 'Adding mapping file ' . $filename . "\n";

            parent::run();

            // delete any empty rows
            DB::table($this->table)->whereNull('ir_id')->delete();

            // add API version
            FieldName::whereNull('api_version')->update(['api_version' => $api_version]);

            // add extra fields (gateway specific)
            $this->add_gateway_specific_fields($api_version);

            // define default fields and their order
            $this->define_default_sample_fields();
            $this->define_default_sequence_fields();
            $this->define_default_clone_fields();
            $this->define_default_cell_fields();
        }
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

        // HACK: override ir_class from "IR_Repertoire" to "Repertoire", so this field is displayed by default
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
            'ir_id' => 'chain1',
            'ir_short' => 'Chain 1 (CDR3, V Gene)',
            'ir_class' => 'Cell',
            'ir_subclass' => 'Cell',
            'api_version' => $api_version,
        ];

        $l[] = [
            'ir_id' => 'chain2',
            'ir_short' => 'Chain 2 (CDR3, V Gene)',
            'ir_class' => 'Cell',
            'ir_subclass' => 'Cell',
            'api_version' => $api_version,
        ];

        $l[] = [
            'ir_id' => 'reactivity_list',
            'ir_short' => 'Reactivity',
            'ir_class' => 'Cell',
            'ir_subclass' => 'Cell',
            'api_version' => $api_version,
        ];

        $l[] = [
            'ir_id' => 'expression_label_list',
            'ir_short' => 'Expression (top 4)',
            'ir_class' => 'Cell',
            'ir_subclass' => 'Cell',
            'api_version' => $api_version,
        ];

        // HACK: override ir_class from from "IR_Cell" to "Cell", so this field is displayed by default
        $l[] = [
            'ir_id' => 'ir_cell_id_cell',
            'ir_short' => 'Tool Cell ID',
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

        $l[] = ['id' => 'study_title', 'visible' => true];
        $l[] = ['id' => 'study_id', 'visible' => true];
        $l[] = ['id' => 'study_group_description', 'visible' => true];
        $l[] = ['id' => 'disease_diagnosis', 'visible' => true];
        $l[] = ['id' => 'subject_id', 'visible' => true];
        $l[] = ['id' => 'sample_id', 'visible' => true];

        $l[] = ['id' => 'ir_sequence_count', 'visible' => true];
        $l[] = ['id' => 'ir_clone_count', 'visible' => true];
        $l[] = ['id' => 'ir_cell_count', 'visible' => true];

        $l[] = ['id' => 'tissue', 'visible' => true];
        $l[] = ['id' => 'pcr_target_locus', 'visible' => true];
        $l[] = ['id' => 'cell_subset', 'visible' => true];

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
        $l[] = ['id' => 'ir_cell_id_cell', 'visible' => true];
        $l[] = ['id' => 'chain1', 'visible' => true];
        $l[] = ['id' => 'chain2', 'visible' => true];
        $l[] = ['id' => 'reactivity_list', 'visible' => true];
        $l[] = ['id' => 'expression_label_list', 'visible' => true];

        foreach ($l as $i => $t) {
            FieldName::where('ir_id', $t['id'])->update(['default_order' => $i, 'default_visible' => $t['visible']]);
        }
    }

    public function define_default_clone_fields()
    {
        $l = [];
        $l[] = ['id' => 'ir_clone_id_clone', 'visible' => true];
        $l[] = ['id' => 'clone_abundance_clone', 'visible' => true];
        $l[] = ['id' => 'sequence_count_clone', 'visible' => true];
        $l[] = ['id' => 'v_call_clone', 'visible' => true];
        $l[] = ['id' => 'd_call_clone', 'visible' => true];
        $l[] = ['id' => 'j_call_clone', 'visible' => true];
        $l[] = ['id' => 'junction_aa_clone', 'visible' => true];
        $l[] = ['id' => 'clone_id_clone', 'visible' => true];

        foreach ($l as $i => $t) {
            FieldName::where('ir_id', $t['id'])->update(['default_order' => $i, 'default_visible' => $t['visible']]);
        }
    }
}
