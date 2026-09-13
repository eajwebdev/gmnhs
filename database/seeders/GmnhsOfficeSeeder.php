<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GmnhsOfficeSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('offices')->updateOrInsert(
            ['id' => 1],
            [
                'office_code' => '0001',
                'office_name' => 'GIL MONTILLA NATIONAL HIGH SCHOOL',
                'office_abbr' => 'GMNHS',
                'office_officer' => 'ALEXANDRA M. VILLAROSA',
                'school_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        foreach ($this->offices() as $office) {
            DB::table('offices')->updateOrInsert(
                ['office_code' => $office['office_code']],
                $office + ['school_id' => 1, 'created_at' => $now, 'updated_at' => $now]
            );
        }
    }

    public static function officeCodes(): array
    {
        return ['0101', '0102', '0103', '0104', '0105', '0106', '0107', '0108', '0109', '0110'];
    }

    private function offices(): array
    {
        return [
            ['office_code' => '0101', 'office_name' => 'Office of the Principal', 'office_abbr' => 'OP', 'office_officer' => 'ALEXANDRA M. VILLAROSA'],
            ['office_code' => '0102', 'office_name' => 'Administrative Office', 'office_abbr' => 'ADMIN', 'office_officer' => 'BENJAMIN R. DELA TORRE'],
            ['office_code' => '0103', 'office_name' => 'Property and Supply Office', 'office_abbr' => 'PSO', 'office_officer' => 'CAMILLE A. NAVARRETE'],
            ['office_code' => '0104', 'office_name' => 'Guidance Office', 'office_abbr' => 'GUID', 'office_officer' => 'ELEANOR V. SANTIAGO'],
            ['office_code' => '0105', 'office_name' => 'Registrar Office', 'office_abbr' => 'REG', 'office_officer' => 'DANIEL P. MONTECLARO'],
            ['office_code' => '0106', 'office_name' => 'Library', 'office_abbr' => 'LIB', 'office_officer' => 'FRANCIS L. MENDOZA'],
            ['office_code' => '0107', 'office_name' => 'ICT Laboratory', 'office_abbr' => 'ICT', 'office_officer' => 'ISABELLA F. LIMSON'],
            ['office_code' => '0108', 'office_name' => 'Science Department', 'office_abbr' => 'SCI', 'office_officer' => 'JULIAN O. ARAGON'],
            ['office_code' => '0109', 'office_name' => 'Mathematics Department', 'office_abbr' => 'MATH', 'office_officer' => 'KATRINA S. RIVERA'],
            ['office_code' => '0110', 'office_name' => 'Technical-Vocational Department', 'office_abbr' => 'TVL', 'office_officer' => 'LEONARDO B. CASTANEDA'],
        ];
    }
}

