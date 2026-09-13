<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\EnduserProperty;
use App\Models\Purchases;
use App\Models\Office;
use App\Models\Accountable;
use App\Models\property;
use App\Models\Properties;
use App\Models\Unit;
use App\Models\Item;
use App\Models\School;
use App\Models\Category;
use App\Models\InvQR;
use App\Models\User;
use Carbon\Carbon;
use App\Models\Setting;
use App\Models\InvSetting;
use App\Models\YearlyInventory;
use App\Models\InventoryHistory;
use PDF;

class InventoryController extends Controller
{
    public function inventoryRead(Request $request) {
        $setting = Setting::firstOrNew(['id' => 1]);
        $yearinvs = YearlyInventory::all();
        $countInv = YearlyInventory::where('inv_status', 'Ongoing')->count();
        
        return view('inventory.invlist', compact('setting', 'yearinvs', 'countInv'));
    }

    public function inventoryView(Request $request) {
        $setting = Setting::firstOrNew(['id' => 1]);
        $yearinvs = YearlyInventory::all();
        $countInv = YearlyInventory::where('inv_status', 'Ongoing')->count();
        $accnt = Accountable::orderBy('accnt_role', 'desc')->get();
        $schoolId = auth()->user()->school_id;
        $exists = Office::whereNotNull('school_id') 
            ->where('school_id', $schoolId)
            ->exists();

        $inventoryQuery = InventoryHistory::join('enduser_property', 'inventory_histories.prop_id', '=', 'enduser_property.id')
            ->join('offices', 'enduser_property.office_id', '=', 'offices.id')
            ->select(
            'offices.school_id',
            \DB::raw("COALESCE(offices.office_name, 'GMNHS') as office_name"),
            \DB::raw("SUM(CASE WHEN inventory_histories.inv_status = 1 THEN 1 ELSE 0 END) as total"),
            \DB::raw("SUM(CASE WHEN inventory_histories.inv_status = 2 THEN 1 ELSE 0 END) as done")
            )
            ->groupBy('offices.school_id', 'offices.office_name');

        if ($exists) {
            $inventoryQuery->where('offices.school_id', $schoolId);
        }

        $inventory = $inventoryQuery->get()->map(function($row) {
            if (is_null($row->school_id)) {
            $row->office_name = 'GMNHS';
            }
            return $row;
        });
        
        
        return view('inventory.listajax', compact('setting', 'yearinvs', 'countInv', 'inventory', 'accnt'));
    }

    public function getInventory()
    {
        $schoolId = auth()->user()->school_id;

        $exists = Office::whereNotNull('school_id')
            ->where('school_id', $schoolId)
            ->first();

        $inventory = InventoryHistory::join('enduser_property', 'inventory_histories.prop_id', '=', 'enduser_property.id')
            ->join('offices', 'enduser_property.office_id', '=', 'offices.id')
            ->join('property', 'enduser_property.properties_id', '=', 'property.id')
            ->join('items', 'enduser_property.item_id', '=', 'items.id')
            ->leftJoin('purchases', 'enduser_property.purch_id', '=', 'purchases.id')
            ->leftJoin('accountable as acc1', 'enduser_property.person_accnt', '=', 'acc1.id')      // join for person_accnt
            ->leftJoin('accountable as acc2', 'enduser_property.person_accnt1', '=', 'acc2.id')     // join for person_accnt1
            ->leftJoin('offices as location', 'enduser_property.location', '=', 'location.id')
            ->select(
                'inventory_histories.*',
                'inventory_histories.remarks as his_remarks',
                'enduser_property.*',
                'offices.office_name',
                'property.abbreviation',
                'items.item_name',
                'purchases.po_number',
                'acc1.person_accnt as person_accnt_name',
                'acc2.person_accnt as person_accnt_name1',
                'location.office_name as location_name'
            );
            
        if ($exists){
            $inventory->where('offices.school_id', $schoolId);
        }
         
        $data = $inventory->where('deleted', 0)->get();

        return response()->json(['data' => $data]);
    }
    
    public function startInventory(Request $request)
    {
        // Check if there is any ongoing inventory
        $ongoingInventory = YearlyInventory::where('inv_status', 'Ongoing')->exists();
        
        if ($ongoingInventory) {
            return response()->json(['error' => 'There is already an ongoing inventory.'], 400);
        }

        // Create new yearly inventory
        $yearinve = YearlyInventory::create([
            'inv_status' => 'Ongoing', 
        ]);

        $invId = $yearinve->id;

        $properties = EnduserProperty::leftJoin('offices', 'enduser_property.office_id', '=', 'offices.id')
            ->select(
                'enduser_property.id', 
                'enduser_property.person_accnt', 
                'enduser_property.person_accnt_name', 
                'enduser_property.remarks', 
                'enduser_property.office_id', 
                'offices.office_officer'
            )
            ->where('enduser_property.remarks', '!=', 'Unserviceable')
            ->get();

        $inventoryData = [];
        foreach ($properties as $property) {
            $inventoryData[] = [
                'prop_id'       => $property->id,
                'inv_id'        => $invId,
                'office_id'     => $property->office_id,
                'accnt_type'    => !is_null($property->person_accnt) ? 1 : 2,
                'person_accnt'  => !empty($property->person_accnt_name) ? $property->person_accnt_name : $property->office_officer,
                'item_status'   => $property->remarks,
                'created_at'    => now(),
                'updated_at'    => now()
            ];
        }

        if (!empty($inventoryData)) {
            foreach (array_chunk($inventoryData, 500) as $batch) {
                InventoryHistory::insert($batch);
            }
        }

        return response()->json(['success' => 'Inventory created successfully!'], 200);
    }

    public function invSave(Request $request)
    {
        $uid = $request->input('uid');
        $pcode = $request->input('pcode');           // property code (QR)
        $status = $request->input('status');
        $remarks = $request->input('remarks');
        $person_accnt = $request->input('person_accnt');
        $person_accnt1 = $request->input('person_accnt1');
        $isInv = (int) $request->input('isInv');

        // Validate accountable person
        $accountable = Accountable::find($person_accnt);
        if (!$accountable) {
            return response()->json(['success' => false, 'error' => 'Accountable person not found'], 404);
        }
        $accountable_name = $accountable->person_accnt;

        // Validate property by property code
        $property = EnduserProperty::where('property_no_generated', $pcode)->first();
        if (!$property) {
            return response()->json(['success' => false, 'error' => 'Property not found'], 404);
        }

        // Prepare update data for property
        $updateData = [
            'person_accnt' => $person_accnt,
            'person_accnt1' => $person_accnt1,
            'person_accnt_name' => $accountable_name,
            'remarks' => $status,
        ];

        // Update inventory history if applicable

        $data = InventoryHistory::where('prop_id', $property->id)->update([
            'uid' => $uid,
            'person_accnt' => $accountable_name,
            'item_status' => $status,
            'remarks' => $remarks,
            'inv_status' => 2, // Mark as Done?
        ]);

        // Update property record
        $updated = EnduserProperty::where('id', $property->id)->update($updateData);
        if (!$updated) {
            return response()->json(['success' => false, 'error' => 'Failed to update property record'], 500);
        }

        // Finalize yearly inventory if needed
        if ($isInv === 1) {
            // Check if there are any InventoryHistory records NOT done (inv_status != 2)
            $pendingCount = InventoryHistory::where('inv_status', '!=', '2')->count();
            if ($pendingCount === 0) {
                YearlyInventory::where('inv_status', 'Ongoing')->update(['inv_status' => 'Done']);
            }
        }

        return response()->json(['success' => true], 200);
    }

}


