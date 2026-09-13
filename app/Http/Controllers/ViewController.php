<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Setting;
use App\Models\Unit;
use App\Models\Item;
use App\Models\Property;
use App\Models\Category;
use App\Models\Office;

class ViewController extends Controller
{

    public function unit_list(Request $request) {
        $setting = Setting::firstOrNew(['id' => 1]);
        $unit = Unit::all();

        if ($request->isMethod('post')) {
            $request->validate([
                'unit_name' => 'required|string|max:255',
            ]);

            try {
                Unit::create([
                    'unit_name' => $request->input('unit_name'),
                ]);

                return redirect()->route('unit_list')->with('success', 'Unit stored successfully!');
            } catch (\Exception $e) {
                return redirect()->route('unit_list')->with('error', 'Failed to store unit!');
            }
        }
        return view('manage.units.list', compact('setting', 'unit'));
    }

    public function unit_edit() {
        $setting = Setting::firstOrNew(['id' => 1]);
        $unit = Unit::all();
        return view('manage.units.edit', compact('setting', 'unit'));
    }

    public function item_list(Request $request) {
        $setting = Setting::firstOrNew(['id' => 1]);
        $item = Item::all();

        if ($request->isMethod('post')) {
            $request->validate([
                'item_name' => 'required|string|max:255',
            ]);

            try {
                Item::create([
                    'item_name' => $request->input('item_name'),
                ]);

                return redirect()->route('item_list')->with('success', 'Item stored successfully!');
            } catch (\Exception $e) {
                return redirect()->route('item_list')->with('error', 'Failed to store item!');
            }
        }
        return view('manage.items.list', compact('setting', 'item'));
    }

    public function office_list() {
        $setting = Setting::firstOrNew(['id' => 1]);
        $office = Office::all();
        return view('manage.office.list', compact('setting', 'office'));
    }
}
