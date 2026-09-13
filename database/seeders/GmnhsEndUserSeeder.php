<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GmnhsEndUserSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $officeIds = DB::table('offices')->whereIn('office_code', GmnhsOfficeSeeder::officeCodes())->pluck('id', 'office_code');

        foreach ($this->endUsers() as $index => $endUser) {
            $officeId = $officeIds[$endUser['office_code']] ?? 1;

            DB::table('accountable')->updateOrInsert(
                ['person_accnt' => $endUser['person_accnt']],
                [
                    'off_id' => $officeId,
                    'desig_offid' => json_encode([$officeId]),
                    'accnt_role' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    private function endUsers(): array
    {
        return [
            ['person_accnt' => 'ALEXANDRA M. VILLAROSA', 'office_code' => '0101'],
            ['person_accnt' => 'BENJAMIN R. DELA TORRE', 'office_code' => '0102'],
            ['person_accnt' => 'CAMILLE A. NAVARRETE', 'office_code' => '0103'],
            ['person_accnt' => 'ELEANOR V. SANTIAGO', 'office_code' => '0104'],
            ['person_accnt' => 'DANIEL P. MONTECLARO', 'office_code' => '0105'],
            ['person_accnt' => 'FRANCIS L. MENDOZA', 'office_code' => '0106'],
            ['person_accnt' => 'ISABELLA F. LIMSON', 'office_code' => '0107'],
            ['person_accnt' => 'JULIAN O. ARAGON', 'office_code' => '0108'],
            ['person_accnt' => 'KATRINA S. RIVERA', 'office_code' => '0109'],
            ['person_accnt' => 'LEONARDO B. CASTANEDA', 'office_code' => '0110'],
        ];
    }
}
