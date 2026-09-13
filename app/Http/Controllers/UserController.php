<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

use App\Models\User;
use App\Models\School;
use App\Models\Setting;

class UserController extends Controller
{
    private const DEFAULT_SCHOOL_ID = 1;

    private const ALL_ROLES = ['Administrator', 'Supply Officer', 'School Admin', 'Supply Staff', 'Technician'];

    /**
     * The only accounts a Supply Officer may see in the list, create, edit or
     * delete. Administrator and Technician accounts are hidden from them
     * entirely, so they can neither list nor assign those roles.
     */
    public const SUPPLY_OFFICER_MANAGED_ROLES = ['Supply Officer', 'School Admin', 'Supply Staff'];

    private function isSupplyOfficer(): bool
    {
        return auth()->user()->role === 'Supply Officer';
    }

    /**
     * Whether the signed-in user is allowed to touch an account with this role.
     * Anyone other than a Supply Officer keeps their existing reach.
     */
    private function canManageRole(?string $role): bool
    {
        if (!$this->isSupplyOfficer()) {
            return true;
        }

        return in_array($role, self::SUPPLY_OFFICER_MANAGED_ROLES, true);
    }

    private function assignableRoles(): array
    {
        return $this->isSupplyOfficer() ? self::SUPPLY_OFFICER_MANAGED_ROLES : self::ALL_ROLES;
    }

    private function normalizeAccess(Request $request, string $role): array
    {
        $allowedAccess = array_keys(system_access_options());
        $selectedAccess = $request->input('access', []);
        $selectedAccess = is_array($selectedAccess) ? $selectedAccess : [];
        $selectedAccess = array_values(array_intersect($selectedAccess, $allowedAccess));

        if ($selectedAccess === []) {
            $selectedAccess = role_default_access($role);
        }

        if (!in_array('dashboard', $selectedAccess, true)) {
            $selectedAccess[] = 'dashboard';
        }

        if ($this->isSupplyOfficer()) {
            $selectedAccess = array_values(array_intersect($selectedAccess, user_access_list(auth()->user())));
        }

        return array_values(array_unique($selectedAccess));
    }

    private function listQuery()
    {
        return User::leftJoin('schools', 'users.school_id', '=', 'schools.id')
            ->select('users.*', 'users.id as uid', 'schools.school_name')
            // Supply Officers only ever see the roles they manage
            ->when($this->isSupplyOfficer(), function ($q) {
                $q->whereIn('users.role', self::SUPPLY_OFFICER_MANAGED_ROLES);
            })
            ->orderBy('users.lname')
            ->orderBy('users.fname');
    }

    private function validateUser(Request $request, ?User $existing = null): array
    {
        $request->merge([
            'lname' => strtoupper(trim((string) $request->input('lname'))),
            'fname' => strtoupper(trim((string) $request->input('fname'))),
            'mname' => strtoupper(trim((string) $request->input('mname'))),
            'username' => trim((string) $request->input('username')),
        ]);

        return $request->validate([
            'lname' => 'required|string|max:255',
            'fname' => 'required|string|max:255',
            'mname' => 'nullable|string|max:255',
            'gender' => 'required|in:Male,Female',
            'username' => ['required', 'string', 'min:5', 'max:255', Rule::unique('users', 'username')->ignore($existing?->id)],
            // Leaving the password blank while editing keeps the current one
            'password' => [$existing ? 'nullable' : 'required', 'string', 'min:8'],
            'role' => ['required', Rule::in(self::ALL_ROLES)],
            'access' => 'array',
            'access.*' => 'string',
        ], [
            'username.unique' => 'That username is already taken.',
        ]);
    }

    public function userRead()
    {
        $school = School::all();
        $setting = Setting::firstOrNew(['id' => 1]);
        $user = $this->listQuery()->get();
        $assignableRoles = $this->assignableRoles();

        return view('users.list', compact('user', 'school', 'setting', 'assignableRoles'));
    }

    public function userCreate(Request $request)
    {
        $validated = $this->validateUser($request);

        // Supply Officers may only create the roles they manage
        if (!$this->canManageRole($validated['role'])) {
            return redirect()->route('userRead')
                ->with('error', 'You are only allowed to add '.implode(', ', self::SUPPLY_OFFICER_MANAGED_ROLES).' accounts!');
        }

        User::create([
            'lname' => $validated['lname'],
            'fname' => $validated['fname'],
            'mname' => $validated['mname'] ?? '',
            'gender' => $validated['gender'],
            'username' => $validated['username'],
            'password' => Hash::make($validated['password']),
            'school_id' => $request->input('school_id') ?: self::DEFAULT_SCHOOL_ID,
            'role' => $validated['role'],
            'access' => $this->normalizeAccess($request, $validated['role']),
        ]);

        return redirect()->route('userRead')->with('success', 'User account created.');
    }

    public function userEdit($id)
    {
        $setting = Setting::firstOrNew(['id' => 1]);
        $school = School::all();
        $user = $this->listQuery()->get();
        $assignableRoles = $this->assignableRoles();

        $selectedUser = User::findOrFail($id);
        $selectedUser->uid = $selectedUser->id;

        // Prevent Supply Officers from opening an account they do not manage
        if (!$this->canManageRole($selectedUser->role)) {
            return redirect()->route('userRead')->with('error', 'You are not allowed to edit a '.$selectedUser->role.' account!');
        }

        return view('users.list', compact('setting', 'user', 'school', 'selectedUser', 'assignableRoles'));
    }

    public function userUpdate(Request $request)
    {
        $user = User::findOrFail($request->input('id'));
        $validated = $this->validateUser($request, $user);

        // Supply Officers can neither edit an account outside the roles they
        // manage nor promote one into a role they do not manage
        if (!$this->canManageRole($validated['role']) || !$this->canManageRole($user->role)) {
            return redirect()->back()->with('error', 'You are not allowed to manage that account!');
        }

        $attributes = [
            'lname' => $validated['lname'],
            'fname' => $validated['fname'],
            'mname' => $validated['mname'] ?? '',
            'gender' => $validated['gender'],
            'username' => $validated['username'],
            'school_id' => $request->input('school_id') ?: self::DEFAULT_SCHOOL_ID,
            'role' => $validated['role'],
            'access' => $this->normalizeAccess($request, $validated['role']),
        ];

        if (!empty($validated['password'])) {
            $attributes['password'] = Hash::make($validated['password']);
        }

        $user->update($attributes);

        return redirect()->route('userEdit', ['id' => $user->id])->with('success', 'User account updated.');
    }

    public function userDelete($id)
    {
        $users = User::find($id);

        if (!$users) {
            return response()->json(['status' => 404, 'message' => 'User not found.'], 404);
        }

        if ((int) $users->id === (int) auth()->id()) {
            return response()->json(['status' => 409, 'message' => 'You cannot delete the account you are signed in with.'], 409);
        }

        // Supply Officers can only delete the roles they manage
        if (!$this->canManageRole($users->role)) {
            return response()->json([
                'status' => 403,
                'message' => 'You are not allowed to delete a '.$users->role.' account!',
            ], 403);
        }

        $users->delete();

        return response()->json([
            'status' => 200,
            'uid' => $id,
            'message' => 'User account deleted.',
        ]);
    }
}
