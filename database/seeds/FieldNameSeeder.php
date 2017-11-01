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
            3 => 'airr_full',
            4 => 'airr',
            10 => 'ir_v1',
            11 => 'ir_v1_sql',
            16 => 'ir_v2',
            34 => 'ir_full',
            35 => 'ir_short',
        ];

        parent::run();

        // update "ir_id" column using, in order of preference: airr, ir_v2, ir_v1
        DB::table($this->table)->whereNull('ir_id')->update(['ir_id' => DB::raw('ir_v2')]);
        DB::table($this->table)->whereNull('ir_id')->update(['ir_id' => DB::raw('airr')]);
        // DB::table($this->table)->whereNull('ir_id')->update(['ir_id' => DB::raw('ir_v1')]);

        // delete empty rows
        DB::table($this->table)->whereNull('ir_id')->delete();
    }
}
