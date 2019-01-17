<?php

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
    }
}
