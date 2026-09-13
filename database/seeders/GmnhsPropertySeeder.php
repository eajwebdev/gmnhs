<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Five default properties, released from the first five seeded purchases the
 * same way Purchases > Release does it: the property record is created, the
 * serial loses its "-unrel" marker and the purchase's released quantity goes
 * up. Purchases that already have a released property are left untouched, so
 * running the seeder again never duplicates records.
 */
class GmnhsPropertySeeder extends Seeder
{
    private const PURCHASES = [
        'GMNHS-PO-2026-0001',
        'GMNHS-PO-2026-0002',
        'GMNHS-PO-2026-0003',
        'GMNHS-PO-2026-0004',
        'GMNHS-PO-2026-0005',
    ];

    public function run(): void
    {
        $now = now();

        foreach (self::PURCHASES as $poNumber) {
            $purchase = DB::table('purchases')->where('po_number', $poNumber)->first();

            if (!$purchase || DB::table('enduser_property')->where('purch_id', $purchase->id)->exists()) {
                continue;
            }

            $office = DB::table('offices')->where('id', $purchase->office_id)->first();
            if (!$office) {
                continue;
            }

            $cost = (float) str_replace(',', '', $purchase->item_cost);
            $propCode = $cost > 50000 ? '06' : '04';
            $year = date('Y', strtotime($purchase->date_acquired ?: 'now'));
            $itemNumber = $this->nextItemNumber($propCode, $purchase->categories_id, $purchase->property_id, $office->office_code);
            $serials = $this->splitSerials($purchase);
            $serial = preg_replace('/-unrel$/', '', $serials[0] ?? '');

            DB::table('enduser_property')->insert([
                'purch_id' => $purchase->id,
                'office_id' => (string) $office->id,
                'item_id' => $purchase->item_id,
                'item_descrip' => $purchase->item_descrip,
                'item_model' => $purchase->item_model,
                'description' => $purchase->description,
                'serial_number' => $serial,
                'date_acquired' => $purchase->date_acquired,
                'unit_id' => $purchase->unit_id,
                'qty' => '1',
                'item_cost' => (string) $cost,
                'total_cost' => (string) $cost,
                'properties_id' => $purchase->properties_id,
                'prop_code' => $propCode,
                'categories_id' => $purchase->categories_id,
                'property_id' => $purchase->property_id,
                'item_number' => $itemNumber,
                'property_no_generated' => implode('-', [$year, $propCode, $purchase->categories_id, $purchase->property_id, $itemNumber, $office->office_code]),
                'selected_account_id' => $purchase->selected_account_id,
                'status' => 'Good Condition',
                'remarks' => 'Good Condition',
                'price_stat' => 'certain',
                'person_accnt' => $purchase->person_accnt,
                'person_accnt_name' => $purchase->person_accnt_name,
                'print_stat' => '1',
                'supply_type' => $purchase->supply_type,
                'deleted' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            if ($serial !== '') {
                $serials[0] = $serial;
            }

            DB::table('purchases')->where('id', $purchase->id)->update([
                'serial_number' => implode($purchase->unit_id == 2 ? ':' : ';', $serials),
                'qty_release' => (string) ((int) $purchase->qty_release + 1),
                'updated_at' => $now,
            ]);
        }
    }

    private function splitSerials(object $purchase): array
    {
        return array_values(array_filter(
            explode($purchase->unit_id == 2 ? ':' : ';', (string) $purchase->serial_number),
            'strlen'
        ));
    }

    /**
     * Same numbering as PurchaseController@checkNextNumber.
     */
    private function nextItemNumber(string $propCode, $categoryCode, $accountCode, string $officeCode): string
    {
        $latest = DB::table('enduser_property')
            ->join('offices', 'offices.id', '=', 'enduser_property.office_id')
            ->where('enduser_property.prop_code', $propCode)
            ->where('enduser_property.categories_id', $categoryCode)
            ->where('enduser_property.property_id', $accountCode)
            ->where('offices.office_code', $officeCode)
            ->orderByDesc('enduser_property.item_number')
            ->value('enduser_property.item_number');

        return str_pad((string) (is_numeric($latest) ? (int) $latest + 1 : 1), 3, '0', STR_PAD_LEFT);
    }
}
