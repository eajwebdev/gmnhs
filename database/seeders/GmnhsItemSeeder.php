<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GmnhsItemSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        foreach ($this->items() as $item) {
            DB::table('items')->updateOrInsert(
                ['item_name' => $item['item_name']],
                $item + ['created_at' => $now, 'updated_at' => $now]
            );
        }
    }

    private function items(): array
    {
        return [
            ['item_name' => 'Laptop Computer', 'supply_type' => 'PPE', 'ct' => 'ICT'],
            ['item_name' => 'Desktop Computer', 'supply_type' => 'PPE', 'ct' => 'ICT'],
            ['item_name' => 'Printer', 'supply_type' => 'PPE', 'ct' => 'Office Equipment'],
            ['item_name' => 'Projector', 'supply_type' => 'PPE', 'ct' => 'Instructional Equipment'],
            ['item_name' => 'Smart TV', 'supply_type' => 'PPE', 'ct' => 'Instructional Equipment'],
            ['item_name' => 'Teacher Table', 'supply_type' => 'PPE', 'ct' => 'Furniture'],
            ['item_name' => 'Student Armchair', 'supply_type' => 'PPE', 'ct' => 'Furniture'],
            ['item_name' => 'Filing Cabinet', 'supply_type' => 'PPE', 'ct' => 'Furniture'],
            ['item_name' => 'Science Laboratory Microscope', 'supply_type' => 'PPE', 'ct' => 'Laboratory Equipment'],
            ['item_name' => 'Network Switch', 'supply_type' => 'PPE', 'ct' => 'ICT'],
        ];
    }
}
