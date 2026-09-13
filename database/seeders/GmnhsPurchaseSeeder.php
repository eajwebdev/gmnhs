<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GmnhsPurchaseSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $unitId = DB::table('units')->where('unit_name', 'Pcs')->value('id') ?? 1;
        $accountTitle = DB::table('properties')->where('account_number', '1-07-05-030')->first()
            ?: DB::table('properties')->first();
        $items = DB::table('items')->pluck('id', 'item_name');
        $offices = DB::table('offices')->whereIn('office_code', GmnhsOfficeSeeder::officeCodes())->pluck('id', 'office_code');
        $endUsers = DB::table('accountable')->pluck('id', 'person_accnt');

        foreach ($this->purchases() as $index => $purchase) {
            $itemId = $items[$purchase['item_name']] ?? DB::table('items')->value('id');
            $officeId = $offices[$purchase['office_code']] ?? 1;
            $endUserId = $endUsers[$purchase['person_accnt_name']] ?? null;
            $propertyNo = '2026-06-05-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT).'-'.str_pad((string) $officeId, 3, '0', STR_PAD_LEFT);

            DB::table('purchases')->updateOrInsert(
                ['property_no_generated' => $propertyNo],
                [
                    'po_number' => 'GMNHS-PO-2026-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                    'office_id' => (string) $officeId,
                    'item_id' => (string) $itemId,
                    'item_descrip' => $purchase['item_descrip'],
                    'item_model' => $purchase['item_model'],
                    'description' => $purchase['description'],
                    'serial_number' => $purchase['serial_number'],
                    'date_acquired' => $purchase['date_acquired'],
                    'unit_id' => (string) $unitId,
                    'qty' => '1',
                    'qty_release' => '0',
                    'item_cost' => (string) $purchase['item_cost'],
                    'total_cost' => (string) $purchase['item_cost'],
                    'properties_id' => (string) ($accountTitle->property_id ?? '3'),
                    'categories_id' => (string) ($accountTitle->category_id ?? '05'),
                    'property_id' => (string) ($accountTitle->code ?? '030'),
                    'item_number' => str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                    'selected_account_id' => $accountTitle ? (string) $accountTitle->id : null,
                    'status' => 'Good Condition',
                    'remarks' => 'N/A',
                    'person_accnt' => $endUserId ? (string) $endUserId : null,
                    'person_accnt_name' => $purchase['person_accnt_name'],
                    'print_stat' => '1',
                    'supply_type' => 'PPE',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    private function purchases(): array
    {
        return [
            ['item_name' => 'Laptop Computer', 'office_code' => '0101', 'person_accnt_name' => 'ALEXANDRA M. VILLAROSA', 'item_descrip' => 'Administrative laptop for school records', 'item_model' => 'Lenovo ThinkPad E14', 'description' => '14-inch business laptop', 'serial_number' => 'GMNHS-LPT-0001-unrel', 'item_cost' => '58500', 'date_acquired' => '2026-01-15'],
            ['item_name' => 'Desktop Computer', 'office_code' => '0102', 'person_accnt_name' => 'BENJAMIN R. DELA TORRE', 'item_descrip' => 'Desktop set for administrative encoding', 'item_model' => 'Acer Veriton S', 'description' => 'Desktop CPU, monitor, keyboard, and mouse', 'serial_number' => 'GMNHS-DTP-0002-unrel', 'item_cost' => '46000', 'date_acquired' => '2026-01-18'],
            ['item_name' => 'Printer', 'office_code' => '0103', 'person_accnt_name' => 'CAMILLE A. NAVARRETE', 'item_descrip' => 'Multifunction office printer', 'item_model' => 'Epson EcoTank L5290', 'description' => 'Print, scan, copy, and fax printer', 'serial_number' => 'GMNHS-PRN-0003-unrel', 'item_cost' => '18500', 'date_acquired' => '2026-02-03'],
            ['item_name' => 'Projector', 'office_code' => '0104', 'person_accnt_name' => 'ELEANOR V. SANTIAGO', 'item_descrip' => 'LCD projector for guidance seminars', 'item_model' => 'BenQ MS560', 'description' => 'Classroom projector', 'serial_number' => 'GMNHS-PRJ-0004-unrel', 'item_cost' => '32000', 'date_acquired' => '2026-02-12'],
            ['item_name' => 'Smart TV', 'office_code' => '0105', 'person_accnt_name' => 'DANIEL P. MONTECLARO', 'item_descrip' => 'Display for registrar queue and announcements', 'item_model' => 'Samsung 55-inch Smart TV', 'description' => 'LED smart television', 'serial_number' => 'GMNHS-TV-0005-unrel', 'item_cost' => '41000', 'date_acquired' => '2026-03-01'],
            ['item_name' => 'Teacher Table', 'office_code' => '0106', 'person_accnt_name' => 'FRANCIS L. MENDOZA', 'item_descrip' => 'Wood and steel teacher table', 'item_model' => 'Standard 48x24', 'description' => 'Office-grade teacher table', 'serial_number' => 'GMNHS-TBL-0006-unrel', 'item_cost' => '7200', 'date_acquired' => '2026-03-10'],
            ['item_name' => 'Student Armchair', 'office_code' => '0107', 'person_accnt_name' => 'ISABELLA F. LIMSON', 'item_descrip' => 'Classroom armchair set', 'item_model' => 'Molded Seat Type A', 'description' => 'Student chair with writing arm', 'serial_number' => 'GMNHS-CHR-0007-unrel', 'item_cost' => '5200', 'date_acquired' => '2026-04-05'],
            ['item_name' => 'Filing Cabinet', 'office_code' => '0108', 'person_accnt_name' => 'JULIAN O. ARAGON', 'item_descrip' => 'Four-drawer filing cabinet', 'item_model' => 'Steel Vertical Cabinet', 'description' => 'Lockable records cabinet', 'serial_number' => 'GMNHS-CAB-0008-unrel', 'item_cost' => '9800', 'date_acquired' => '2026-04-20'],
            ['item_name' => 'Science Laboratory Microscope', 'office_code' => '0109', 'person_accnt_name' => 'KATRINA S. RIVERA', 'item_descrip' => 'Compound microscope for science instruction', 'item_model' => 'Olympus CX23', 'description' => 'Student laboratory microscope', 'serial_number' => 'GMNHS-MIC-0009-unrel', 'item_cost' => '67500', 'date_acquired' => '2026-05-14'],
            ['item_name' => 'Network Switch', 'office_code' => '0110', 'person_accnt_name' => 'LEONARDO B. CASTANEDA', 'item_descrip' => 'Managed switch for ICT laboratory', 'item_model' => 'TP-Link JetStream 24-Port', 'description' => 'Gigabit network switch', 'serial_number' => 'GMNHS-NET-0010-unrel', 'item_cost' => '24500', 'date_acquired' => '2026-05-28'],
        ];
    }
}
