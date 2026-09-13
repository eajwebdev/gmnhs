<?php

namespace App\Http\Controllers;

use App\Models\Accountable;
use App\Models\EnduserProperty;
use App\Models\Office;
use App\Models\Purchases;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EnduserController extends Controller
{
    public function accountableRead()
    {
        return view('manage.enduser.accntlist', $this->listData());
    }

    public function accountableCreate(Request $request)
    {
        Accountable::create($this->validatePerson($request));

        return redirect()->route('accountableRead')->with('success', 'Person added.');
    }

    public function accountableEdit($id)
    {
        $data = $this->listData();
        $data['selectedAccnt'] = Accountable::findOrFail($id);

        return view('manage.enduser.accntlist', $data);
    }

    public function accountableUpdate(Request $request)
    {
        $person = Accountable::findOrFail($request->input('id'));
        $validated = $this->validatePerson($request, $person);

        $person->update($validated);

        return redirect()->route('accountableEdit', ['id' => $person->id])->with('success', 'Person updated.');
    }

    public function accountableDelete($id)
    {
        $person = Accountable::find($id);

        if (!$person) {
            return response()->json(['status' => 404, 'message' => 'Person not found.'], 404);
        }

        $inUse = EnduserProperty::where('person_accnt', $person->id)->orWhere('person_accnt1', $person->id)->exists()
            || Purchases::where('person_accnt', $person->id)->exists();

        if ($inUse) {
            return response()->json([
                'status' => 409,
                'message' => 'This person is still accountable for properties or purchases, so they cannot be deleted.',
            ], 409);
        }

        $person->delete();

        return response()->json(['status' => 200, 'message' => 'Person deleted.']);
    }

    private function listData(): array
    {
        $setting = Setting::firstOrNew(['id' => 1]);
        $office = Office::where('office_code', '!=', '0000')->orderBy('office_code')->get();
        $officeAbbr = Office::pluck('office_abbr', 'id');

        $accnt = Accountable::leftJoin('offices', 'accountable.off_id', '=', 'offices.id')
            ->select('accountable.*', 'offices.office_name', 'offices.office_abbr')
            ->when(auth()->user()->role === 'School Admin', fn ($q) => $q->where('offices.school_id', auth()->user()->school_id))
            ->orderByDesc('accountable.accnt_role')
            ->orderBy('accountable.person_accnt')
            ->get()
            ->each(function ($row) use ($officeAbbr) {
                $ids = json_decode((string) $row->desig_offid, true);
                $row->other_offices = collect(is_array($ids) ? $ids : [])
                    ->map(fn ($officeId) => $officeAbbr[$officeId] ?? null)
                    ->filter()
                    ->values();
            });

        return compact('setting', 'accnt', 'office');
    }

    private function validatePerson(Request $request, ?Accountable $existing = null): array
    {
        $request->merge(['person_accnt' => trim((string) $request->input('person_accnt'))]);
        $canAssignOffice = in_array(auth()->user()->role, ['Administrator', 'Supply Officer'], true);

        $validated = $request->validate([
            'person_accnt' => ['required', 'string', 'max:255', Rule::unique('accountable', 'person_accnt')->ignore($existing?->id)],
            'off_id' => [$canAssignOffice ? 'required' : 'nullable', 'integer', Rule::exists('offices', 'id')],
            'accnt_role' => 'nullable|in:0,1',
            'desig_offid' => 'nullable|array',
            'desig_offid.*' => ['integer', Rule::exists('offices', 'id')],
        ], [
            'person_accnt.required' => 'Enter the person\'s name.',
            'person_accnt.unique' => 'That person is already registered.',
            'off_id.required' => 'Select the school or office.',
        ]);

        if (!$canAssignOffice) {
            // Only the name is editable here; keep whatever office data exists
            return [
                'person_accnt' => $validated['person_accnt'],
                'off_id' => $existing->off_id ?? 1,
                'accnt_role' => $existing->accnt_role ?? 0,
                'desig_offid' => $existing->desig_offid ?? json_encode([]),
            ];
        }

        return [
            'person_accnt' => $validated['person_accnt'],
            'off_id' => (int) $validated['off_id'],
            'accnt_role' => (int) ($validated['accnt_role'] ?? 0),
            'desig_offid' => json_encode(array_values(array_map('intval', $validated['desig_offid'] ?? []))),
        ];
    }
}
