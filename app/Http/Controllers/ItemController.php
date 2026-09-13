<?php

namespace App\Http\Controllers;

use App\Models\EnduserProperty;
use App\Models\Item;
use App\Models\Office;
use App\Models\Purchases;
use App\Models\School;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ItemController extends Controller
{
    public function itemRead(Request $request)
    {
        return view('manage.items.list', $this->listData($request));
    }

    public function itemCreate(Request $request)
    {
        $validated = $this->validateItem($request);

        Item::create($validated);

        return redirect()->route('itemRead')->with('success', 'Item added.');
    }

    public function itemEdit(Request $request, $id)
    {
        $data = $this->listData($request);
        $data['selectedItem'] = Item::findOrFail($id);

        return view('manage.items.list', $data);
    }

    public function itemUpdate(Request $request)
    {
        $item = Item::findOrFail($request->input('id'));
        $validated = $this->validateItem($request, $item->id);

        $item->update($validated);

        return redirect()->route('itemEdit', ['id' => $item->id])->with('success', 'Item updated.');
    }

    public function itemDelete($id)
    {
        $item = Item::find($id);

        if (!$item) {
            return response()->json(['status' => 404, 'message' => 'Item not found.'], 404);
        }

        if (EnduserProperty::where('item_id', $item->id)->exists() || Purchases::where('item_id', $item->id)->exists()) {
            return response()->json([
                'status' => 409,
                'message' => 'This item is used by existing purchases or properties, so it cannot be deleted.',
            ], 409);
        }

        $item->delete();

        return response()->json(['status' => 200, 'message' => 'Item deleted.']);
    }

    /**
     * Items plus the number of active property records per item, optionally
     * narrowed by office and description.
     */
    private function listData(Request $request): array
    {
        $setting = Setting::firstOrNew(['id' => 1]);
        $schools = School::all();
        $office = Office::where('office_code', '!=', '0000')->orderBy('office_name')->get();
        $item = Item::orderBy('item_name')->get();

        $counts = EnduserProperty::query()
            ->where('deleted', 0)
            ->when($request->filled('off'), fn ($q) => $q->where('office_id', $request->off))
            ->when($request->filled('descrip'), fn ($q) => $q->where('item_descrip', 'LIKE', '%'.$request->descrip.'%'))
            ->selectRaw('item_id, COUNT(*) as total')
            ->groupBy('item_id')
            ->pluck('total', 'item_id');

        $inventoryCount = $item->mapWithKeys(fn ($row) => [$row->id => (int) ($counts[$row->id] ?? 0)])->all();

        return compact('setting', 'item', 'inventoryCount', 'schools', 'office');
    }

    private function validateItem(Request $request, ?int $ignoreId = null): array
    {
        $request->merge(['item_name' => trim((string) $request->input('item_name'))]);

        return $request->validate([
            'item_name' => ['required', 'string', 'max:255', Rule::unique('items', 'item_name')->ignore($ignoreId)],
        ], [
            'item_name.unique' => 'That item already exists.',
        ]);
    }
}
