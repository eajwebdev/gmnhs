@extends('layouts.master')

@section('body')
@php
    $editing = isset($selectedUser);
    $accessOptions = system_access_options();
    $editableAccessOptions = auth()->user()->role === 'Supply Officer'
        ? array_intersect_key($accessOptions, array_flip(user_access_list(auth()->user())))
        : $accessOptions;
    $selectedAccess = old('access', $editing ? user_access_list($selectedUser) : ['dashboard']);
    $roleTone = [
        'Administrator' => 'is-brass',
        'Supply Officer' => '',
        'School Admin' => '',
        'Supply Staff' => '',
        'Technician' => '',
    ];
@endphp

<div class="row crud-layout">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">User accounts</h3>
                <span class="count-pill">{{ $user->count() }} {{ \Illuminate\Support\Str::plural('account', $user->count()) }}</span>
            </div>
            <div class="card-body">
                <table id="example1" class="table table-hover">
                    <thead>
                        <tr>
                            <th class="cell-num">#</th>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>School</th>
                            <th class="cell-actions" data-orderable="false">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($user as $data)
                            @php
                                $fullName = trim($data->lname.', '.$data->fname.' '.($data->mname ? substr($data->mname, 0, 1).'.' : ''), ', ');
                                $isSelf = (int) $data->uid === (int) auth()->id();
                            @endphp
                            <tr id="tr-{{ $data->uid }}" class="{{ $editing && $data->uid == $selectedUser->uid ? 'bg-selectEdit' : '' }}">
                                <td class="cell-num">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</td>
                                <td>
                                    <span style="color: var(--ink); font-weight: 600;">{{ $fullName }}</span>
                                    @if($isSelf)
                                        <span class="tag">You</span>
                                    @endif
                                </td>
                                <td class="cell-code">{{ $data->username }}</td>
                                <td><span class="tag {{ $roleTone[$data->role] ?? '' }}">{{ display_role($data->role) }}</span></td>
                                <td class="text-muted">{{ $data->school_name ?? '—' }}</td>
                                <td class="cell-actions">
                                    <div class="row-actions">
                                        <a href="{{ route('userEdit', $data->uid) }}" class="btn btn-icon" title="Edit" aria-label="Edit {{ $data->username }}">
                                            <i class="fas fa-pen"></i>
                                        </a>
                                        @unless($isSelf)
                                            <button type="button" class="btn btn-icon is-danger" title="Delete" aria-label="Delete {{ $data->username }}"
                                                data-delete-url="{{ route('userDelete', $data->uid) }}"
                                                data-delete-label="the account “{{ $data->username }}”"
                                                @if($editing && $data->uid == $selectedUser->uid) data-after-delete="{{ route('userRead') }}" @endif>
                                                <i class="fas fa-trash-can"></i>
                                            </button>
                                        @endunless
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card crud-form">
            <div class="card-header">
                <h3 class="card-title">{{ $editing ? 'Edit account' : 'New account' }}</h3>
                <span class="mode-flag {{ $editing ? '' : 'is-new' }}">{{ $editing ? 'Editing' : 'Create' }}</span>
            </div>
            <div class="card-body">
                <form action="{{ $editing ? route('userUpdate') : route('userCreate') }}" method="POST" id="addUser" autocomplete="off">
                    @csrf
                    @if($editing)
                        <input type="hidden" name="id" value="{{ $selectedUser->uid }}">
                    @endif
                    <input type="hidden" name="school_id" value="1">

                    <div class="form-row">
                        <div class="form-group col-sm-6">
                            <label for="fname">First name</label>
                            <input type="text" id="fname" name="fname" class="form-control" value="{{ old('fname', $selectedUser->fname ?? '') }}" oninput="this.value = this.value.toUpperCase()">
                        </div>
                        <div class="form-group col-sm-6">
                            <label for="lname">Last name</label>
                            <input type="text" id="lname" name="lname" class="form-control" value="{{ old('lname', $selectedUser->lname ?? '') }}" oninput="this.value = this.value.toUpperCase()">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-sm-6">
                            <label for="mname">Middle name <span class="text-muted" style="font-weight: 400;">(optional)</span></label>
                            <input type="text" id="mname" name="mname" class="form-control" value="{{ old('mname', $selectedUser->mname ?? '') }}" oninput="this.value = this.value.toUpperCase()">
                        </div>
                        <div class="form-group col-sm-6">
                            <label for="gender">Gender</label>
                            <select id="gender" name="gender" class="form-control">
                                <option value="">Select</option>
                                @foreach(['Male', 'Female'] as $gender)
                                    <option value="{{ $gender }}" @selected(old('gender', $selectedUser->gender ?? '') === $gender)>{{ $gender }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" class="form-control mono" value="{{ old('username', $selectedUser->username ?? '') }}" autocomplete="off">
                    </div>

                    <div class="form-group">
                        <label for="password">{{ $editing ? 'New password' : 'Password' }}</label>
                        <input type="password" id="password" name="password" class="form-control" autocomplete="new-password"
                            placeholder="{{ $editing ? 'Leave blank to keep the current password' : 'At least 8 characters' }}">
                    </div>

                    <div class="form-group">
                        <label for="roleSelect">Role</label>
                        <select id="roleSelect" name="role" class="form-control">
                            <option value="">Select a role</option>
                            @foreach($assignableRoles as $assignableRole)
                                <option value="{{ $assignableRole }}" @selected(old('role', $selectedUser->role ?? '') === $assignableRole)>{{ display_role($assignableRole) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Module access</label>
                        <div class="access-checklist">
                            @foreach($editableAccessOptions as $accessKey => $accessLabel)
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input access-checkbox" id="access_{{ $accessKey }}"
                                        name="access[]" value="{{ $accessKey }}" @checked(in_array($accessKey, (array) $selectedAccess, true))>
                                    <label class="custom-control-label" for="access_{{ $accessKey }}">{{ $accessLabel }}</label>
                                </div>
                            @endforeach
                        </div>
                        <span class="form-hint">Choosing a role pre-fills its usual modules. Dashboard is always included.</span>
                    </div>

                    <div class="form-actions">
                        @if($editing)
                            <a href="{{ route('userRead') }}" class="btn btn-default">Cancel</a>
                        @endif
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-floppy-disk"></i> {{ $editing ? 'Save changes' : 'Create account' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    window.roleAccessDefaults = @json(collect($assignableRoles)->mapWithKeys(fn ($role) => [$role => role_default_access($role)]));
    window.isEditingUser = @json($editing);
</script>
@endsection
