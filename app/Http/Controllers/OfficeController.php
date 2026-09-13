<?php

namespace App\Http\Controllers;

use App\Models\Accountable;
use App\Models\EnduserProperty;
use App\Models\Log;
use App\Models\Office;
use App\Models\Purchases;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Offices and locations share the offices table: locations are the rows whose
 * office_code is 0000. Route parameter $code is 1 for offices, 2 for locations.
 */
class OfficeController extends Controller
{
    private const LOCATION_CODE = '0000';

    public function officeRead($code)
    {
        return view('manage.office.list', $this->listData((int) $code));
    }

    public function officeCreate(Request $request)
    {
        $code = (int) $request->input('code');
        $validated = $this->validateOffice($request, $code);

        Office::create($validated);

        return redirect()->route('officeRead', $code)
            ->with('success', $code === 2 ? 'Location added.' : 'Office added.');
    }

    public function officeEdit($id, $code)
    {
        $code = (int) $code;
        $data = $this->listData($code);
        $data['selectedOffice'] = $this->scopedQuery($code)->findOrFail($id);

        return view('manage.office.list', $data);
    }

    public function officeUpdate(Request $request)
    {
        $code = (int) $request->input('code');
        $office = $this->scopedQuery($code)->findOrFail($request->input('id'));
        $validated = $this->validateOffice($request, $code, $office->id);

        $office->update($validated);

        Log::create([
            'school_id' => auth()->user()->school_id,
            'user_id' => auth()->id(),
            'module_id' => $office->id,
            'module' => 'offices',
            'action' => 'update',
        ]);

        return redirect()->route('officeEdit', ['id' => $office->id, 'code' => $code])
            ->with('success', $code === 2 ? 'Location updated.' : 'Office updated.');
    }

    public function officeDelete($id)
    {
        $office = Office::find($id);

        if (!$office) {
            return response()->json(['status' => 404, 'message' => 'Record not found.'], 404);
        }

        $inUse = EnduserProperty::where('office_id', $office->id)->orWhere('location', $office->id)->exists()
            || Purchases::where('office_id', $office->id)->exists()
            || Accountable::where('off_id', $office->id)->exists();

        if ($inUse) {
            return response()->json([
                'status' => 409,
                'message' => 'This record is linked to properties, purchases or accountable persons, so it cannot be deleted.',
            ], 409);
        }

        $office->delete();

        Log::create([
            'school_id' => auth()->user()->school_id,
            'user_id' => auth()->id(),
            'module_id' => $id,
            'module' => 'offices',
            'action' => 'delete',
        ]);

        return response()->json(['status' => 200, 'message' => 'Record deleted.']);
    }

    private function scopedQuery(int $code)
    {
        $query = Office::query();

        if ($code === 2) {
            $query->where('offices.office_code', self::LOCATION_CODE);

            if (auth()->user()->role === 'School Admin') {
                $query->where('offices.school_id', auth()->user()->school_id);
            }
        } else {
            $query->where('offices.office_code', '!=', self::LOCATION_CODE);
        }

        return $query;
    }

    private function listData(int $code): array
    {
        abort_unless(in_array($code, [1, 2], true), 404);

        $setting = Setting::firstOrNew(['id' => 1]);
        $office = $this->scopedQuery($code)
            ->orderBy($code === 1 ? 'office_code' : 'office_name')
            ->get();

        return compact('setting', 'office', 'code');
    }

    private function validateOffice(Request $request, int $code, ?int $ignoreId = null): array
    {
        abort_unless(in_array($code, [1, 2], true), 404);

        $request->merge([
            'office_name' => strtoupper(trim((string) $request->input('office_name'))),
            'office_abbr' => strtoupper(trim((string) $request->input('office_abbr'))),
            'office_officer' => trim((string) $request->input('office_officer')),
        ]);

        $sameType = fn ($q) => $code === 2
            ? $q->where('office_code', self::LOCATION_CODE)
            : $q->where('office_code', '!=', self::LOCATION_CODE);

        if ($code === 2) {
            $validated = $request->validate([
                'office_name' => ['required', 'string', 'max:255', Rule::unique('offices', 'office_name')->where($sameType)->ignore($ignoreId)],
            ], [
                'office_name.required' => 'Enter the location name.',
                'office_name.unique' => 'That location already exists.',
            ]);

            return $validated + [
                'office_code' => self::LOCATION_CODE,
                'office_abbr' => '',
                'office_officer' => '',
                'school_id' => auth()->user()->school_id ?: 1,
            ];
        }

        $digits = preg_replace('/\D/', '', (string) $request->input('office_code'));
        $request->merge(['office_code' => $digits === '' ? null : str_pad($digits, 4, '0', STR_PAD_LEFT)]);

        return $request->validate([
            'office_code' => ['required', 'digits:4', 'not_in:'.self::LOCATION_CODE, Rule::unique('offices', 'office_code')->ignore($ignoreId)],
            'office_name' => ['required', 'string', 'max:255', Rule::unique('offices', 'office_name')->where($sameType)->ignore($ignoreId)],
            'office_abbr' => 'required|string|max:255',
            'office_officer' => 'required|string|max:255',
        ], [
            'office_code.not_in' => 'Office code 0000 is reserved for locations.',
            'office_code.unique' => 'That office code is already in use.',
            'office_name.unique' => 'That office already exists.',
        ]) + ['school_id' => 1];
    }
}
