<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\User;
use App\Models\School;
use App\Models\Setting;

use Exception;

class SettingsController extends Controller
{
    //
    public function user_settings() {
        $setting = Setting::firstOrNew(['id' => 1]);
        return view('settings.account_settings', compact('setting'));
    }

    public function setting_list() {
        $setting = Setting::firstOrNew(['id' => 1]);
        return view('settings.system_name', compact('setting'));
    }

    public function profileUpdate(Request $request) {
        $user = Auth::user();

        $validated = $request->validate([
            'lname' => 'required|string|max:255',
            'fname' => 'required|string|max:255',
            'mname' => 'nullable|string|max:255',
            'username' => [
                'required',
                'string',
                'min:5',
                'max:255',
                Rule::unique('users', 'username')->ignore($user->id),
            ],
            'gender' => 'required|in:Male,Female',
        ]);

        $user->update([
            'lname' => strtoupper($validated['lname']),
            'fname' => strtoupper($validated['fname']),
            'mname' => strtoupper($validated['mname'] ?? ''),
            'username' => $validated['username'],
            'gender' => $validated['gender'],
        ]);

        return redirect()->route('user_settings')->with('success', 'Profile updated successfully');
    }

    public function profilePassUpdate(Request $request) {
        $validated = $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        Auth::user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('user_settings')->with('success', 'Password updated successfully');
    }

    public function upload(Request $request) {
        $validated = $request->validate([
            'system_name' => 'required|string|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $setting = Setting::firstOrNew(['id' => 1]);
        $setting->system_name = strtoupper(trim($validated['system_name']));

        if ($request->hasFile('photo')) {
            $photo = $request->file('photo');
            $filename = time().'_'.preg_replace('/[^A-Za-z0-9._-]/', '_', $photo->getClientOriginalName());
            $photo->move(public_path('uploads'), $filename);

            if ($setting->photo_filename && file_exists(public_path('uploads/'.$setting->photo_filename))) {
                unlink(public_path('uploads/'.$setting->photo_filename));
            }

            $setting->photo_filename = $filename;
        }

        $setting->save();

        return redirect()->route('setting_list')->with('success', 'System settings saved.');
    }
}
