<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Category;
use App\Models\EnduserProperty;
use App\Models\Properties;
use App\Models\property;
use App\Models\Purchases;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Shared list/create/edit/update/delete flow for the four account title
 * screens (PPE, high value, low value, intangible). Each controller only
 * declares which property type it manages and which routes/views it uses.
 */
trait ManagesAccountTitles
{
    /**
     * @return array{property_id:int, view:string, edit_var:string, list_route:string, edit_route:string}
     */
    abstract protected function accountTitleType(): array;

    protected function accountTitleIndex(?int $selectedId = null)
    {
        $type = $this->accountTitleType();

        $setting = Setting::firstOrNew(['id' => 1]);
        $categories = Category::orderBy('cat_code')->get();
        $property = property::find($type['property_id']);

        $properties = Properties::leftJoin('categories', 'properties.category_id', '=', 'categories.cat_code')
            ->where('properties.property_id', $type['property_id'])
            ->orderBy('properties.account_number')
            ->select('properties.*', 'categories.cat_name')
            ->get();

        $data = compact('setting', 'categories', 'property', 'properties');

        if ($selectedId !== null) {
            $data[$type['edit_var']] = Properties::leftJoin('categories', 'properties.category_id', '=', 'categories.cat_code')
                ->where('properties.property_id', $type['property_id'])
                ->where('properties.id', $selectedId)
                ->select('properties.*', 'categories.cat_name')
                ->firstOrFail();
        }

        return view($type['view'], $data);
    }

    protected function accountTitleStore(Request $request)
    {
        $type = $this->accountTitleType();
        $validated = $this->validateAccountTitle($request);

        Properties::create($validated + [
            'property_id' => $type['property_id'],
            'code' => $this->accountCodeFrom($validated['account_number']),
        ]);

        return redirect()->route($type['list_route'])->with('success', 'Account title added.');
    }

    protected function accountTitleUpdate(Request $request)
    {
        $type = $this->accountTitleType();

        $row = Properties::where('property_id', $type['property_id'])->findOrFail($request->input('id'));
        $validated = $this->validateAccountTitle($request, $row->id);

        $row->update($validated + ['code' => $this->accountCodeFrom($validated['account_number'])]);

        return redirect()->route($type['edit_route'], ['id' => $row->id])->with('success', 'Account title updated.');
    }

    protected function accountTitleDestroy($id)
    {
        $type = $this->accountTitleType();
        $row = Properties::where('property_id', $type['property_id'])->find($id);

        if (!$row) {
            return response()->json(['status' => 404, 'message' => 'Account title not found.'], 404);
        }

        $inUse = EnduserProperty::where('properties_id', $row->id)->exists()
            || Purchases::where('properties_id', $row->id)->exists();

        if ($inUse) {
            return response()->json([
                'status' => 409,
                'message' => 'This account title is used by existing purchases or properties, so it cannot be deleted.',
            ], 409);
        }

        $row->delete();

        return response()->json(['status' => 200, 'message' => 'Account title deleted.']);
    }

    private function validateAccountTitle(Request $request, ?int $ignoreId = null): array
    {
        $type = $this->accountTitleType();

        $request->merge([
            'account_number' => trim((string) $request->input('account_number')),
            'account_title' => trim((string) $request->input('account_title')),
            'account_title_abbr' => strtoupper(trim((string) $request->input('account_title_abbr'))),
        ]);

        return $request->validate([
            'category_id' => ['required', 'string', Rule::exists('categories', 'cat_code')],
            'account_number' => [
                'required',
                'regex:/^\d-\d{2}-\d{2}-\d{3}$/',
                Rule::unique('properties', 'account_number')
                    ->where('property_id', $type['property_id'])
                    ->ignore($ignoreId),
            ],
            'account_title' => 'required|string|max:200',
            'account_title_abbr' => 'required|string|max:200',
        ], [
            'account_number.regex' => 'Account number must follow the 0-00-00-000 format.',
            'account_number.unique' => 'That account number is already registered for this property type.',
            'category_id.exists' => 'Select a valid category.',
        ]);
    }

    private function accountCodeFrom(string $accountNumber): string
    {
        $parts = explode('-', $accountNumber);

        return (string) end($parts);
    }
}
