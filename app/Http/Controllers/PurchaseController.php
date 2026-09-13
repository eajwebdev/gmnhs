<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Setting;
use App\Models\Purchases;
use App\Models\Inventory;
use App\Models\EnduserProperty;
use App\Models\Office;
use App\Models\Accountable;
use App\Models\property;
use App\Models\Properties;
use App\Models\Unit;
use App\Models\Item;
use App\Models\School;
use App\Models\Category;
use App\Models\User;
use Carbon\Carbon;
use PDF;

class PurchaseController extends Controller
{
    public function getPurchase() {
        $data = Purchases::join('property', 'purchases.properties_id', '=', 'property.id')
                ->join('items', 'purchases.item_id', '=', 'items.id')
                ->select('purchases.*', 'property.abbreviation', 'items.item_name')
                ->get();
        return response()->json(['data' => $data]);
    }
    
    public function purchaseREAD(Request $request) {
        $setting = Setting::firstOrNew(['id' => 1]);
        $office = Office::where('office_code', '!=', '0000')->get();
        $accnt = Accountable::all();
        $item = Item::all();
        $unit = Unit::all();    
        $category = Category::all();
        $currentPrice = floatval(str_replace(',', '', $request->input('item_cost'))) ?? 0;
        $property = Property::whereIn('id', [1, 2, 3])->get();

        return view('purchases.listajax', compact('setting', 'office', 'accnt', 'item', 'unit', 'currentPrice','category', 'property'));
    }

    public function purchaseCreate(Request $request) {
        $setting = Setting::firstOrNew(['id' => 1]);
        $purchase = Purchases::all();
        $office = Office::where('id', $request->office_id)->first();
        $accnt = Accountable::all();
        $accnt1 = Accountable::find($request->person_accnt);
        $date = Carbon::now();
        $dateAcquired = $request->input('date_acquired');

        if ($dateAcquired) {
            $formattedDate = date('Y', strtotime($dateAcquired));
        } else {
            $formattedDate = $date->format('Y');
        }

        if ($request->input('item_cost') >= 50001) {
            $propertyCode = "06";
        } else {
            $propertyCode = "04";
        }
        $categoriesCode = $request->categories_id;
        $propertiesCode = $request->property_id;
        $accountID = $request->id;
        
        if ($request->isMethod('post')) {
            $request->validate([
                'item_id' => 'required',
                'item_descrip' => 'required',
                'item_model' => 'required',
                'unit_id' => 'required',
                'qty' => 'required',
                'item_cost' => 'required',
                'total_cost' => 'required',
                'properties_id' => 'required',
            ]);

            $serial_numbers = $request->input('serial_number');
            $delimiter = ($request->unit_id == 2) ? ':' : ';';
            $concatlabel = '-unrel' . $delimiter;
            $serial_numbers_array = explode($delimiter, $serial_numbers);
            
            $serial_numbers_array = array_map('trim', $serial_numbers_array);
            
            if (!empty($serial_numbers_array)) {
                $last_index = count($serial_numbers_array) - 1;
                if (substr($serial_numbers_array[$last_index], -6) !== '-unrel') {
                    $serial_numbers_array[$last_index] .= '-unrel';
                }
            }
            
            $concat_serial_number = (!empty($serial_numbers_array)) ? implode($concatlabel, $serial_numbers_array) : '';

            try {
                Purchases::create([
                    'po_number' => $request->input('po_number'),
                    'item_id' => $request->input('item_id'),
                    'item_descrip' => $request->input('item_descrip'),
                    'item_model' => $request->input('item_model'),
                    'serial_number' => ($concat_serial_number !== "-unrel") ? $concat_serial_number : null,
                    'date_acquired' => $request->input('date_acquired'),
                    'unit_id' => $request->input('unit_id'),
                    'qty' => $request->input('qty'),
                    'item_cost' => str_replace(',', '', $request->input('item_cost')),
                    'total_cost' => str_replace(',', '', $request->input('total_cost')),
                    'properties_id' => $request->input('properties_id'),
                    'categories_id' => $request->input('categories_id'),
                    'property_id' => $request->input('property_id'),
                    'selected_account_id' => $request->input('selected_account_id'),
                    'supply_type' => $request->input('supply_type'),
                ]);
            
                return redirect()->back()->with('success', 'Purchase Item  stored successfully!');
            } catch (\Exception $e) {
                return redirect()->back()->with('error', 'Failed to store Purchase Item!');
            }
        }
    }

    public function purchaseReleaseGet(Request $request, $id){
        $purchase = Purchases::join('items', 'purchases.item_id', '=', 'items.id')
        ->find($id);

        $date = Carbon::now();

        $dateAcquired = $request->input('date_acquired');

        if ($dateAcquired) {
            $formattedDate = date('Y', strtotime($dateAcquired));
        } else {
            $formattedDate = $date->format('Y');
        }

        if ($purchase->item_cost > 50000) {
            $propertyCode = "06";
        } else {
            $propertyCode = "04";
        }
        
        $categoriesCode = $purchase->categories_id;
        $propertiesCode = $purchase->property_id;

        $lastItemNumber = EnduserProperty::where([
            'office_id' => $request->input('office_id'),
            'item_id' => $purchase->item_id,
            'property_id' => $purchase->property_id,
        ])->max('item_number');

        $propertyCodeGen = $formattedDate.'-'.$propertyCode.'-'.$categoriesCode.'-'.$propertiesCode;

        $unrel_serial = $purchase->serial_number;
        $array_serial = ($purchase->unit_id == 2) ? explode(':', $unrel_serial) : explode(';', $unrel_serial);
        
        $unrel_serial_filtered = array_filter($array_serial, function($serial) {
            return strpos($serial, '-unrel') !== false;
        });

        $options_serial = '<option value="">Select a serial</option>';

        foreach ($unrel_serial_filtered as $serial) {
            $cleaned_serial = str_replace('-unrel', '', $serial);
            
            $options_serial .= "<option value='" . htmlspecialchars($cleaned_serial) . "'>" . htmlspecialchars($cleaned_serial) . "</option>";
        }
        
        $data = [
            'purchase' => $purchase,
            'pcode' => $propertyCodeGen,
            'unrel_serial' => $options_serial,
            'qty_left' =>  $purchase->qty -  $purchase->qty_release,
        ];

        return response()->json($data);
    }

    public function purchaseReleasePost(Request $request) {
        $validated = $request->validate([
            'purchase_id' => 'required|integer|exists:purchases,id',
            'office_id' => 'required|integer|exists:offices,id',
            'person_accnt' => 'required|integer|exists:accountable,id',
        ], [
            'person_accnt.required' => 'Please select accountable person.',
            'person_accnt.exists' => 'Selected accountable person is invalid.',
        ]);

        // Retrieve necessary data
        $purchase = Purchases::findOrFail($validated['purchase_id']);
        $accnt = Accountable::findOrFail($validated['person_accnt']);

        //dd($accnt);
        $dateAcquired = $request->date_acquired;

        $total_cost = number_format($request->qty * floatval(str_replace(',', '', $purchase->item_cost)), 2);
    
        $serials = $request->serial_number;
    
        $accountablePer = $accnt->person_accnt;
        
        $newItemNum = intval($request->itemnum);
        
        if(!empty($serials)){

            $purchase_serials =  ($purchase->unit_id == 2) ? explode(':', $purchase->serial_number) : explode(';', $purchase->serial_number);
            // Unreleased serials carry an "-unrel" suffix; drop it for the ones
            // released now. Serials may contain hyphens themselves.
            foreach ($purchase_serials as $i => $purchase_serial) {
                $cleaned = preg_replace('/-unrel$/', '', $purchase_serial);
                if (in_array($cleaned, $serials, true)) {
                    $purchase_serials[$i] = $cleaned;
                }
            }
            $purchase->serial_number = ($purchase->unit_id == 2) ? implode(':', $purchase_serials) : implode(';', $purchase_serials);
            
            foreach($serials as $index => $serial) {
            
                $parts = explode('-', $request->property_no_generated);
                $part_to_increment = $parts[4]; 
                $new_part = intval($part_to_increment) + $index;
                $parts[4] = str_pad($new_part, strlen($part_to_increment), '0', STR_PAD_LEFT);
                
                $newPropertyNoGenerated = implode('-', $parts);
                
                $prop_code = $parts[1];
                
                $newItemNum = str_pad($new_part, strlen($part_to_increment), '0', STR_PAD_LEFT);

                EnduserProperty::create([
                    'purch_id' => $request->purchase_id,
                    'office_id' => $request->office_id,
                    'item_id' => $purchase->item_id,
                    'item_descrip' => $purchase->item_descrip,
                    'item_model' => $purchase->item_model,
                    'serial_number' => $serial,
                    'date_acquired' => $dateAcquired,
                    'unit_id' => $purchase->unit_id,
                    'qty' => 1,
                    'item_cost' => str_replace(',', '', $purchase->item_cost),
                    'total_cost' => str_replace(',', '', $purchase->item_cost),
                    'properties_id' => $purchase->properties_id,
                    'prop_code' => $prop_code,
                    'categories_id' => $purchase->categories_id,
                    'property_id' => $purchase->property_id,
                    'item_number' => $newItemNum,
                    'property_no_generated' => $newPropertyNoGenerated,
                    'selected_account_id' => $purchase->selected_account_id,
                    'remarks' => 'Good Condition',
                    'price_stat' => 'certain',
                    'person_accnt' => $validated['person_accnt'],
                    'person_accnt_name' => $accountablePer,
                    'supply_type' => $purchase->supply_type,
                ]);
            }
        }else{
            $parts = explode('-', $request->property_no_generated);
            $prop_code = $parts[1];
            EnduserProperty::create([
                'purch_id' => $request->purchase_id,
                'office_id' => $request->office_id,
                'item_id' => $purchase->item_id,
                'item_descrip' => $purchase->item_descrip,
                'item_model' => $purchase->item_model,
                'serial_number' => '',
                'date_acquired' => $dateAcquired,
                'unit_id' => $purchase->unit_id,
                'qty' => $request->qty,
                'item_cost' => $purchase->item_cost,
                'total_cost' => $total_cost,
                'properties_id' => $purchase->properties_id,
                'prop_code' => $prop_code,
                'categories_id' => $purchase->categories_id,
                'property_id' => $purchase->property_id,
                'item_number' => $request->itemnum,
                'property_no_generated' => $request->property_no_generated,
                'selected_account_id' => $purchase->selected_account_id,
                'remarks' => 'Good Condition',
                'price_stat' => 'certain',
                'person_accnt' => $validated['person_accnt'],
                'person_accnt_name' => $accountablePer,
                'supply_type' => $purchase->supply_type,
            ]);
        }
    
        $purchase->qty_release += $request->qty;
        $purchase->save();
        
        // Redirect back with success message
        return redirect()->back()->with('success', 'Purchase Item added successfully!');
    }

    public function checkNextNumber($propertyno, $officeCode)
    {
        $parts = explode('-', $propertyno);
        $code  = $parts[0];
        $code1 = $parts[1]; 
        $code2 = $parts[2];

        $office = Office::where('office_code', $officeCode)->first();

        if (!$office) {
            return response()->json([]);
        }

        $accountables = Accountable::where(function ($query) use ($office) {
                $query->where('off_id', $office->id)
                    ->orWhereJsonContains('desig_offid', (string) $office->id);
            })
            ->whereIn('accnt_role', [0, 1])
            ->get();

        $latest = EnduserProperty::join('offices', 'offices.id', '=', 'enduser_property.office_id')
            ->where('prop_code', $code)
            ->where('categories_id', $code1)
            ->where('property_id', $code2)
            ->where('offices.office_code', $officeCode)
            ->orderByDesc('item_number')
            ->select('enduser_property.item_number')
            ->first();

        $nextNumber = 1;
        if ($latest && is_numeric($latest->item_number)) {
            $nextNumber = (int)$latest->item_number + 1;
        }

        $nextFormatted = str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

        return response()->json([
            'next_item_number' => $nextFormatted,
            'accountables' => $accountables
        ]);
    }
    
    public function purchaseRelDel($id){
        $puchase = Purchases::find($id);
        $puchase->delete();

        return response()->json([
            'status'=>200,
            'message'=>'Deleted Successfully',
        ]);
    }

}


