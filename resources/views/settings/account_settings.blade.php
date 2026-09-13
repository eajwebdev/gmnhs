@extends('layouts.master')

@section('body')
@php
    $me = auth()->user();
    $initial = strtoupper(substr($me->fname ?: $me->username, 0, 1));
@endphp

<div class="row crud-layout">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Profile</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('profileUpdate') }}" method="POST" id="addEmp">
                    @csrf
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="fname">First name</label>
                            <input type="text" id="fname" name="fname" class="form-control" value="{{ old('fname', $me->fname) }}" oninput="this.value = this.value.toUpperCase()" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="mname">Middle name <span class="text-muted" style="font-weight: 400;">(optional)</span></label>
                            <input type="text" id="mname" name="mname" class="form-control" value="{{ old('mname', $me->mname) }}" oninput="this.value = this.value.toUpperCase()">
                        </div>
                        <div class="form-group col-md-4">
                            <label for="lname">Last name</label>
                            <input type="text" id="lname" name="lname" class="form-control" value="{{ old('lname', $me->lname) }}" oninput="this.value = this.value.toUpperCase()" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" class="form-control mono" value="{{ old('username', $me->username) }}" required minlength="5" autocomplete="username">
                        </div>
                        <div class="form-group col-md-4">
                            <label for="gender">Gender</label>
                            <select id="gender" name="gender" class="form-control" required>
                                <option value="">Select</option>
                                @foreach(['Male', 'Female'] as $gender)
                                    <option value="{{ $gender }}" @selected(old('gender', $me->gender) === $gender)>{{ $gender }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="role">Role</label>
                            <input type="text" id="role" value="{{ display_role($me->role) }}" class="form-control" disabled>
                            <span class="form-hint">Roles are assigned by an administrator.</span>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end" style="border-top: 1px dashed var(--line); margin-top: 6px; padding-top: 16px;">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-floppy-disk"></i> Save profile</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Password</h3>
            </div>
            <div class="card-body">
                <p class="section-note">Use at least 8 characters. You stay signed in on this device after changing it.</p>
                <form action="{{ route('profilePassUpdate') }}" method="POST" id="updatePass">
                    @csrf
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="password">New password</label>
                            <input type="password" id="password" name="password" class="form-control" required minlength="8" autocomplete="new-password">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="password_confirmation">Confirm new password</label>
                            <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" required minlength="8" autocomplete="new-password">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end" style="border-top: 1px dashed var(--line); margin-top: 6px; padding-top: 16px;">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-lock"></i> Update password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card crud-form">
            <div class="card-body profile-card">
                <span class="profile-mark">{{ $initial }}</span>
                <h3>{{ ucwords(strtolower(trim($me->fname.' '.$me->lname))) }}</h3>
                <div class="role">{{ display_role($me->role) }}</div>

                <ul class="profile-facts">
                    <li><span>Username</span><strong class="mono">{{ $me->username }}</strong></li>
                    <li><span>Modules</span><strong>{{ count(user_access_list($me)) }} enabled</strong></li>
                    <li><span>Member since</span><strong>{{ optional($me->created_at)->format('M Y') ?? '—' }}</strong></li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
