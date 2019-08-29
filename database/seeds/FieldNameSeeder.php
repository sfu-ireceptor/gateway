<?php

use App\FieldName;
use Flynsarmy\CsvSeeder\CsvSeeder;

class FieldNameSeeder extends CsvSeeder
{
    public function __construct()
    {
        $this->table = 'field_name';
        $this->filename = base_path() . '/database/seeds/data/field_names.tsv';
        $this->offset_rows = 2;
        $this->csv_delimiter = "\t";
    }

    public function run()
    {
        // delete existing data
        DB::table($this->table)->truncate();

        $this->mapping = [
            1 => 'ir_id',
            2 => 'ir_v2',
            3 => 'ir_short',
            4 => 'ir_full',
            14 => 'ir_class',
            15 => 'ir_subclass',
            16 => 'airr',
            17 => 'ir_adc_api_query',
            18 => 'ir_adc_api_response',
            20 => 'airr_full',
            21 => 'airr_description',
            22 => 'airr_example',
        ];

        parent::run();

        // delete any empty rows
        DB::table($this->table)->whereNull('ir_id')->delete();

        // add extra fields (gateway specific)
        $this->add_gateway_specific_fields();

        // define default fields and their order
        $this->define_default_sample_fields();
        $this->define_default_sequence_fields();
    }

    public function add_gateway_specific_fields()
    {
        $l = [];
        $l[] = [
                    'ir_id' => 'rest_service_name',
                    'ir_short' => 'Repository',
                    'ir_class' => 'repertoire',
                    'ir_subclass' => 'other',
                ];

        $l[] = [
                    'ir_id' => 'full_text_search',
                    'ir_short' => 'Full-text search ',
                    'ir_class' => 'repertoire',
                    'ir_subclass' => 'other',
                    'airr_description' => 'Search across all metadata fields (case insensitive)',
                    'airr_example' => 'cancer tumor',
                ];

        // HACK: override ir_class from "ir_repertoire" to "repertoire", so this field is displayed by default
        $l[] = [
                    'ir_id' => 'ir_sequence_count',
                    'ir_short' => 'Sequences',
                    'ir_class' => 'repertoire',
                    'ir_subclass' => 'other',
                ];

        // HACK: override ir_class from from "ir_rearrangement" to "rearrangement", so this field is displayed by default
        $l[] = [
                    'ir_id' => 'ir_junction_aa_length',
                    'ir_short' => 'Junction Length (AA)',
                    'ir_class' => 'rearrangement',
                    'ir_subclass' => 'rearrangement',
                ];

        foreach ($l as $t) {
            FieldName::updateOrCreate(['ir_id' => $t['ir_id']], $t);
        }
    }

    public function define_default_sample_fields()
    {
        $l = [];

        $l[] = ['id' => 'rest_service_name', 'visible' => true];
        $l[] = ['id' => 'study_title', 'visible' => true];
        $l[] = ['id' => 'disease_state_sample', 'visible' => true];
        $l[] = ['id' => 'study_group_description', 'visible' => true];
        $l[] = ['id' => 'ir_sequence_count', 'visible' => true];
        $l[] = ['id' => 'lab_name', 'visible' => true];
        $l[] = ['id' => 'tissue', 'visible' => true];
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
}
