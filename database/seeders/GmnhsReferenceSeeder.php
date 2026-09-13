<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GmnhsReferenceSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('schools')->updateOrInsert(
            ['id' => 1],
            ['school_name' => 'GIL MONTILLA NATIONAL HIGH SCHOOL', 'school_abbr' => 'GMNHS', 'created_at' => $now, 'updated_at' => $now]
        );
        DB::table('schools')->where('id', '<>', 1)->delete();

        DB::table('settings')->updateOrInsert(
            ['id' => 1],
            ['system_name' => 'GMNHS PPEI', 'photo_filename' => 'logo.png', 'created_at' => $now, 'updated_at' => $now]
        );

        DB::table('inv_settings')->updateOrInsert(['id' => 1], ['switch' => 'Off', 'created_at' => $now, 'updated_at' => $now]);

        foreach (['Pcs', 'Box', 'Set', 'Unit'] as $unit) {
            DB::table('units')->updateOrInsert(['unit_name' => $unit], ['created_at' => $now, 'updated_at' => $now]);
        }

        foreach ($this->propertyTypes() as $row) {
            DB::table('property')->updateOrInsert(['id' => $row['id']], $row + ['created_at' => $now, 'updated_at' => $now]);
        }

        foreach ($this->categories() as $row) {
            DB::table('categories')->updateOrInsert(['cat_code' => $row['cat_code']], $row + ['created_at' => $now, 'updated_at' => $now]);
        }

        foreach ($this->accountTitles() as $row) {
            DB::table('properties')->updateOrInsert(['account_number' => $row['account_number']], $row + ['created_at' => $now, 'updated_at' => $now]);
        }
    }

    private function propertyTypes(): array
    {
        return [
            ['id' => 1, 'default_code' => '', 'property_code' => '', 'property_name' => 'Semi-Expendable Property High Value', 'abbreviation' => 'SPHV'],
            ['id' => 2, 'default_code' => '', 'property_code' => '', 'property_name' => 'Semi-Expendable Property Low Value', 'abbreviation' => 'SPLV'],
            ['id' => 3, 'default_code' => '1', 'property_code' => '06', 'property_name' => 'Property, Plant and Equipment', 'abbreviation' => 'PPE'],
        ];
    }

    private function categories(): array
    {
        return [
            ['property_id' => '3', 'cat_name' => 'Land', 'cat_code' => '01'],
            ['property_id' => '3', 'cat_name' => 'Buildings and Other Structures', 'cat_code' => '04'],
            ['property_id' => '3', 'cat_name' => 'Machinery and Equipment', 'cat_code' => '05'],
            ['property_id' => '3', 'cat_name' => 'Transportation Equipment', 'cat_code' => '06'],
            ['property_id' => '3', 'cat_name' => 'Furniture, Fixtures and Books', 'cat_code' => '07'],
        ];
    }

    private function accountTitles(): array
    {
        return [
            ['property_id' => '3', 'category_id' => '05', 'account_number' => '1-07-05-020', 'account_title' => 'Office Equipment', 'account_title_abbr' => 'OE', 'code' => '020'],
            ['property_id' => '3', 'category_id' => '05', 'account_number' => '1-07-05-030', 'account_title' => 'Information and Communication Technology Equipment', 'account_title_abbr' => 'ICTE', 'code' => '030'],
            ['property_id' => '3', 'category_id' => '07', 'account_number' => '1-07-07-010', 'account_title' => 'Furniture and Fixtures', 'account_title_abbr' => 'FF', 'code' => '010'],
        ];
    }
}

