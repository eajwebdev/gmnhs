<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class GmnhsAdminSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $profile = [
            'school_id' => 1,
            'fname' => 'PEARL JOY',
            'mname' => '',
            'lname' => 'POSITAR',
            'role' => 'Administrator',
            'access' => json_encode(role_default_access('Administrator')),
            'updated_at' => $now,
        ];

        $existing = DB::table('users')->where('username', 'admin')->first();

        if ($existing) {
            // Keep the current password on an existing install
            DB::table('users')->where('id', $existing->id)->update($profile);

            return;
        }

        DB::table('users')->insert($profile + [
            'username' => 'admin',
            'password' => Hash::make('password'),
            'created_at' => $now,
        ]);
    }
}
