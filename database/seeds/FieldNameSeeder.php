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

        // define default fields and their order
        $this->define_default_sample_fields();
        $this->define_default_sequence_fields();
    }

    public function define_default_sample_fields()
    {
        $l = [];
        $l[] = ['id' => 'lab_name', 'visible' => true];
        $l[] = ['id' => 'study_title', 'visible' => true];
        $l[] = ['id' => 'study_group_description', 'visible' => true];
        $l[] = ['id' => 'subject_id', 'visible' => true];
        $l[] = ['id' => 'ir_sequence_count', 'visible' => true];
        $l[] = ['id' => 'tissue', 'visible' => true];
        $l[] = ['id' => 'cell_subset', 'visible' => true];
        $l[] = ['id' => 'cell_phenotype', 'visible' => true];
        $l[] = ['id' => 'sample_id', 'visible' => true];
        $l[] = ['id' => 'template_class', 'visible' => true];
        $l[] = ['id' => 'study_id', 'visible' => true];
        $l[] = ['id' => 'pub_ids', 'visible' => true];
        $l[] = ['id' => 'sequencing_platform', 'visible' => true];

        foreach ($l as $i => $t) {
            FieldName::where('ir_id', $t['id'])->update(['default_order' => $i, 'default_visible' => $t['visible']]);
        }
    }

    public function define_default_sequence_fields()
    {
        $l = [];
        $l[] = ['id' => 'v_call', 'visible' => true];
        $l[] = ['id' => 'j_call', 'visible' => true];
        $l[] = ['id' => 'd_call', 'visible' => true];
        $l[] = ['id' => 'junction_aa', 'visible' => true];
        $l[] = ['id' => 'junction_length', 'visible' => true];

        foreach ($l as $i => $t) {
            FieldName::where('ir_id', $t['id'])->update(['default_order' => $i, 'default_visible' => $t['visible']]);
        }
    }
}
