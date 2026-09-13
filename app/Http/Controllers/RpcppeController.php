<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;
use Carbon\Carbon;
use PDF;

class RpcppeController extends Controller
{
    public function inventory_RPCPPEreports() {
        $setting = Setting::firstOrNew(['id' => 1]);
        $pdf = PDF::loadView('inventory.printRPCPPE');
        //return $pdf->stream();
        return view('inventory.invRPCPPEreports', compact('setting'));
    }
    public function inventory_RPCPPEpdf() {
        $pdf = PDF::loadView('inventory.printRPCPPE')->setPaper('Legal', 'landscape');
        return $pdf->stream();
        //return view('inventory.printRPCPPE');
    }
}
