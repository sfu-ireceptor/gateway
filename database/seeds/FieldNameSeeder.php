<?php

use App\FieldName;
use Flynsarmy\CsvSeeder\CsvSeeder;

class FieldNameSeeder extends CsvSeeder
{
    public function __construct()
    {
        $this->table = 'field_name';
        $this->filename = base_path() . '/database/seeds/csv/field_names.tsv';
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
            13 => 'ir_v1',
            14 => 'ir_v1_sql',
            15 => 'ir_class',
            16 => 'ir_subclass',
            17 => 'airr',
            19 => 'airr_full',
            20 => 'airr_description',
            21 => 'airr_example',
        ];

        parent::run();

        // delete any empty rows
        DB::table($this->table)->whereNull('ir_id')->delete();


        $this->define_default_sample_fields();
        $this->define_default_sequence_fields();
    }

    public function define_default_sample_fields() {
        // these sample fields are visible by default, in this order
        $l = [];
        $l[] = ['ir_id' => 'lab_name', 'default_visible' => true];
        $l[] = ['ir_id' => 'study_title', 'default_visible' => true];
        $l[] = ['ir_id' => 'study_group_description', 'default_visible' => true];
        $l[] = ['ir_id' => 'subject_id', 'default_visible' => true];
        $l[] = ['ir_id' => 'ir_sequence_count', 'default_visible' => true];
        $l[] = ['ir_id' => 'tissue', 'default_visible' => true];
        $l[] = ['ir_id' => 'cell_subset', 'default_visible' => true];
        $l[] = ['ir_id' => 'cell_phenotype', 'default_visible' => true];
        $l[] = ['ir_id' => 'sample_id', 'default_visible' => true];
        $l[] = ['ir_id' => 'template_class', 'default_visible' => true];
        $l[] = ['ir_id' => 'study_id', 'default_visible' => true];
        $l[] = ['ir_id' => 'pub_ids', 'default_visible' => true];
        $l[] = ['ir_id' => 'sequencing_platform', 'default_visible' => true];

        foreach ($l as $i => $t) {
            FieldName::where('ir_id', $t['ir_id'])->update(['default_order' => $i, 'default_visible' => $t['default_visible']]);
        }        
    }

    public function define_default_sequence_fields() {
        // these sequence fields are visible by default, in this order
        $l = [];
        $l[] = ['ir_id' => 'v_call', 'default_visible' => true];
        $l[] = ['ir_id' => 'j_call', 'default_visible' => true];
        $l[] = ['ir_id' => 'd_call', 'default_visible' => true];
        $l[] = ['ir_id' => 'junction_aa', 'default_visible' => true];
        $l[] = ['ir_id' => 'junction_length', 'default_visible' => true];

        foreach ($l as $i => $t) {
            FieldName::where('ir_id', $t['ir_id'])->update(['default_order' => $i, 'default_visible' => $t['default_visible']]);
        }       
    }
}
