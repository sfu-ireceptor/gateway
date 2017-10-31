<?php

use Flynsarmy\CsvSeeder\CsvSeeder;

class FieldNameSeeder extends CsvSeeder
{
    public function __construct()
    {
        $this->table = 'field_name';
        $this->filename = base_path() . '/database/seeds/csv/field_names.csv';
        $this->offset_rows = 7;
    }

    public function run()
    {
        DB::table($this->table)->truncate();

        $this->mapping = [
            1 => 'airr_full',
            2 => 'airr',
            6 => 'ir_v1',
            14 => 'ir_v2',
            28 => 'ir_full',
            29 => 'ir_short',
        ];

        parent::run();

        // update "ir_id" column using, in order of preference: airr, ir_v2, ir_v1
        DB::table($this->table)->whereNull('ir_id')->update(['ir_id' => DB::raw('airr')]);
        DB::table($this->table)->whereNull('ir_id')->update(['ir_id' => DB::raw('ir_v2')]);
        // DB::table($this->table)->whereNull('ir_id')->update(['ir_id' => DB::raw('ir_v1')]);

        // delete empty rows
        DB::table($this->table)->whereNull('ir_id')->delete();
    }
}
