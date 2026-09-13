<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use App\Models\EnduserProperty;
use App\Models\Setting;
use App\Models\Repair;
use App\Models\Log;
use PDF;

class TechController extends Controller
{
    public function repairRead()
    {
        $setting = Setting::firstOrNew(['id' => 1]);
        $enduserall = EnduserProperty::select('enduser_property.id', 'enduser_property.property_no_generated', 'enduser_property.item_descrip')->get();

        $repairs = Repair::join('enduser_property', 'repairs.prop_id', '=', 'enduser_property.id')
            ->leftJoin('users as rcv', 'repairs.uid', '=', 'rcv.id')
            ->leftJoin('users as diag', 'repairs.diagnose_by', '=', 'diag.id')
            ->leftJoin('users as rel', 'repairs.release_by', '=', 'rel.id')
            ->select(
                'repairs.*',
                'repairs.repair_status',
                'enduser_property.property_no_generated',
                'repairs.id as rpid',
                'repairs.prop_id',
                'enduser_property.qty',
                'enduser_property.item_id',
                'enduser_property.item_descrip',
                DB::raw("CONCAT(COALESCE(rcv.fname,''), ' ', COALESCE(rcv.lname,'')) as received_by_name"),
                DB::raw("CONCAT(COALESCE(diag.fname,''), ' ', COALESCE(diag.lname,'')) as diagnose_by_name"),
                DB::raw("CONCAT(COALESCE(rel.fname,''), ' ', COALESCE(rel.lname,'')) as release_by_name")
            )
            ->get();

        return view('repair.repairtech', compact('setting', 'repairs', 'enduserall'));
    }
    
    public function repairCreate(Request $request)
    {
        $request->validate([
            'prop_id' => 'required',
            'findings' => 'required', 
            'urgency' => 'required'
        ]);

        // Check for existing unreleased or undiagnosed repair
        $existing = Repair::where('prop_id', $request->prop_id)
            ->where(function ($q) {
                $q->whereNull('diagnosis')
                ->orWhereNull('release_date');
            })
            ->first();

        if ($existing) {
            return redirect()->back()->with('error', 'A pending repair already exists for this property.');
        }

        $repair = Repair::create([
            'prop_id' => $request->prop_id,
            'uid' => auth()->user()->id,
            'findings' => $request->findings,
            'urgency' => $request->urgency,
            'repair_status' => 1, // Default to Pending
        ]);

        Log::create([
            'school_id' => auth()->user()->school_id,
            'user_id' => auth()->user()->id,
            'module_id' => $repair->id,
            'module' => 'repairs',
            'action' => 'create',
        ]);

        return redirect()->route('qr-scan')->with('success1', 'Save Successfully');
    }

    public function repairUpdate(Request $request)
    {
        $request->validate([
            'prop_id'   => 'required',
            'diagnosis' => 'required',
            'repair_status' => 'required|integer',
        ]);

        // Find the most recent repair for this property
        $repair = Repair::where('prop_id', $request->prop_id)
            ->orderBy('id', 'DESC')
            ->firstOrFail();

        $repair->update([
            'diagnosis'     => $request->diagnosis,
            'repair_status' => $request->repair_status,
            'date_diagnose' => now()->toDateTimeString(),
            'diagnose_by' => auth()->user()->id
        ]);

        Log::create([
            'school_id'   => auth()->user()->school_id,
            'user_id'   => auth()->user()->id,
            'module_id' => $repair->id,
            'module'    => 'repairs',
            'action'    => 'update_diagnosis',
        ]);

        return redirect()->route('qr-scan')->with('success1', 'Save Successfully');
    }
    
    public function repairRelease($propno)
    {
        $property = EnduserProperty::where('property_no_generated', $propno)->first();
       
        if (!$property) {
            return redirect()->route('qr-scan')->with('error', 'Property not found.');
        }

        $repair = Repair::where('prop_id', $property->id)
            ->where('release_by', null)
            ->orderBy('id', 'DESC')
            ->first();

        if (!$repair) {
            return redirect()->route('qr-scan')->with('error', 'No pending repair found for release.');
        }

        $repair->update([
            'release_by'    => auth()->user()->id,
            'release_date'  => now()->toDateTimeString(), // full timestamp
        ]);

        Log::create([
            'school_id'   => auth()->user()->school_id,
            'user_id'   => auth()->user()->id,
            'module_id' => $repair->id,
            'module'    => 'repairs',
            'action'    => 'release',
        ]);

        return redirect()->route('qr-scan')->with('success1', 'Released Successfully.');
    }
    
    public function qrScan()
    {
        $setting = Setting::firstOrNew(['id' => 1]);
        return view('repair.qr-scan', compact('setting'));
    }

    public function qrScanCheck($propno)
    {
        $property = EnduserProperty::where('property_no_generated', $propno)->first();
        if (!$property) {
            return response()->json([
                'status' => 'error',
                'message' => 'Property not found.'
            ]);
        }

        $repair = Repair::where('prop_id', $property->id)
            ->where('release_date', null)
            ->orderBy('id', 'DESC')
            ->first();

        if (!$repair) {
            // No repair exists → create new
            return response()->json([
                'status' => 'create',
                'url' => route('repairForm', ['propno' => $propno])
            ]);
        }

        if (empty($repair->diagnosis)) {
            // Repair exists but not diagnosed (null or empty) → go to diagnose
            return response()->json([
                'status' => 'diagnose',
                'url' => route('repairDiagnose', ['propno' => $propno])
            ]);
        }

        if (!empty($repair->diagnosis) && is_null($repair->release_date)) {
            return response()->json([
                'status' => 'release',
            ]);
        }

        // Already released
        return response()->json([
            'status' => 'released',
            'message' => 'This item has already been released.'
        ]);
    }

    public function repairForm($propno)
    { 
        $setting = Setting::firstOrNew(['id' => 1]);
        $enduserprop = EnduserProperty::select('id', 'property_no_generated', 'item_model', 'item_descrip')
                        ->where('property_no_generated', $propno)
                        ->firstOrFail();

        return view('repair.repair-form', compact('setting', 'enduserprop'));
    }

    public function repairDiagnose($propno)
    {
        $setting = Setting::firstOrNew(['id' => 1]);
        $enduserprop = EnduserProperty::select('id', 'property_no_generated', 'item_model', 'item_descrip')
                        ->where('property_no_generated', $propno)
                        ->firstOrFail();

        return view('repair.repair-form-update', compact('setting', 'enduserprop'));
    }

    public function repairPDF($id)
    {
        $repair = Repair::join('enduser_property', 'repairs.prop_id', '=', 'enduser_property.id')
            ->leftJoin('users as rcv', 'repairs.uid', '=', 'rcv.id')
            ->leftJoin('users as diag', 'repairs.diagnose_by', '=', 'diag.id')
            ->leftJoin('users as rel', 'repairs.release_by', '=', 'rel.id')
            ->where('repairs.id', $id)
            ->select(
            'repairs.*',
            'enduser_property.property_no_generated',
            'repairs.id as rpid',
            'repairs.prop_id',
            'enduser_property.qty',
            'enduser_property.item_id',
            'enduser_property.item_model',
            'enduser_property.item_descrip',
            DB::raw("CONCAT(COALESCE(rcv.fname,''), ' ', COALESCE(rcv.lname,'')) as received_by_name"),
            DB::raw("CONCAT(COALESCE(diag.fname,''), ' ', COALESCE(diag.lname,'')) as diagnose_by_name"),
            DB::raw("CONCAT(COALESCE(rel.fname,''), ' ', COALESCE(rel.lname,'')) as release_by_name"))
            ->firstOrFail();

        $pdf = PDF::loadView('repair.repair-pdf', compact('repair'))->setPaper('A4', 'portrait');
        return $pdf->stream('repair_' . $id . '.pdf');
    }
}
