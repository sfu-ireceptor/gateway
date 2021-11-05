<?php

use App\FieldName;
use Flynsarmy\CsvSeeder\CsvSeeder;
use Illuminate\Support\Facades\DB;

class FieldNameSeeder extends CsvSeeder
{
    public function __construct()
    {
        $this->table = 'field_name';
        $this->filename = base_path() . '/database/seeds/data/field_names.tsv';
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

            parent::run();

            // delete any empty rows
            DB::table($this->table)->whereNull('ir_id')->delete();

            // add extra fields (gateway specific)
            $this->add_gateway_specific_fields();

            // define default fields and their order
            $this->define_default_sample_fields();
            $this->define_default_sequence_fields();
        });
    }

    public function add_gateway_specific_fields()
    {
        $l = [];
        $l[] = [
            'ir_id' => 'rest_service_name',
            'ir_short' => 'Repository',
            'ir_class' => 'Repertoire',
            'ir_subclass' => 'other',
        ];

        $l[] = [
            'ir_id' => 'full_text_search',
            'ir_short' => 'Full-text search ',
            'ir_class' => 'Hidden',
            'ir_subclass' => 'other',
            'airr_description' => 'Search across all metadata fields (case insensitive)',
            'airr_example' => 'cancer tumor',
        ];

        // HACK: override ir_class from "ir_repertoire" to "repertoire", so this field is displayed by default
        $l[] = [
            'ir_id' => 'ir_sequence_count',
            'ir_short' => 'Sequences',
            'ir_class' => 'Repertoire',
            'ir_subclass' => 'other',
        ];

        $l[] = [
            'ir_id' => 'ir_clone_count',
            'ir_short' => 'Clones',
            'ir_class' => 'Repertoire',
            'ir_subclass' => 'other',
        ];

        // HACK: override ir_class from from "ir_rearrangement" to "rearrangement", so this field is displayed by default
        $l[] = [
            'ir_id' => 'ir_junction_aa_length',
            'ir_short' => 'Junction Length (AA)',
            'ir_class' => 'Rearrangement',
            'ir_subclass' => 'Rearrangement',
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
        $l[] = ['id' => 'disease_diagnosis', 'visible' => true];
        $l[] = ['id' => 'study_group_description', 'visible' => true];
        $l[] = ['id' => 'ir_sequence_count', 'visible' => true];
        $l[] = ['id' => 'ir_clone_count', 'visible' => true];
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
}
