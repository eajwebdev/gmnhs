<?php

namespace App\Http\Controllers;

use App\Models\EnduserProperty;
use App\Models\Purchases;
use App\Models\Setting;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UnitController extends Controller
{
    public function unitRead()
    {
        $setting = Setting::firstOrNew(['id' => 1]);
        $unit = Unit::orderBy('unit_name')->get();

        return view('manage.units.list', compact('setting', 'unit'));
    }

    public function unitCreate(Request $request)
    {
        $validated = $this->validateUnit($request);

        Unit::create($validated);

        return redirect()->route('unitRead')->with('success', 'Unit added.');
    }

    public function unitEdit($id)
    {
        $setting = Setting::firstOrNew(['id' => 1]);
        $unit = Unit::orderBy('unit_name')->get();
        $selectedUnit = Unit::findOrFail($id);

        return view('manage.units.list', compact('setting', 'unit', 'selectedUnit'));
    }

    public function unitUpdate(Request $request)
    {
        $unit = Unit::findOrFail($request->input('id'));
        $validated = $this->validateUnit($request, $unit->id);

        $unit->update($validated);

        return redirect()->route('unitEdit', ['id' => $unit->id])->with('success', 'Unit updated.');
    }

    public function unitDelete($id)
    {
        $unit = Unit::find($id);

        if (!$unit) {
            return response()->json(['status' => 404, 'message' => 'Unit not found.'], 404);
        }

        if (EnduserProperty::where('unit_id', $unit->id)->exists() || Purchases::where('unit_id', $unit->id)->exists()) {
            return response()->json([
                'status' => 409,
                'message' => 'This unit is used by existing purchases or properties, so it cannot be deleted.',
            ], 409);
        }

        $unit->delete();

        return response()->json(['status' => 200, 'message' => 'Unit deleted.']);
    }

    private function validateUnit(Request $request, ?int $ignoreId = null): array
    {
        $request->merge(['unit_name' => trim((string) $request->input('unit_name'))]);

        return $request->validate([
            'unit_name' => ['required', 'string', 'max:255', Rule::unique('units', 'unit_name')->ignore($ignoreId)],
        ], [
            'unit_name.unique' => 'That unit already exists.',
        ]);
    }
}
