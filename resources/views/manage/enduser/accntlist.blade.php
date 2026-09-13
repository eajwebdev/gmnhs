@extends('layouts.master')

@section('body')
@php
    $editing = isset($selectedAccnt);
    $role = auth()->user()->role;
    $canAssignOffice = in_array($role, ['Administrator', 'Supply Officer'], true);
    $showOffices = $role !== 'School Admin';
    $noun = $showOffices ? 'accountable person' : 'end user';
    $selectedDesignations = $editing ? (array) json_decode((string) $selectedAccnt->desig_offid, true) : [];
@endphp

<div class="row crud-layout">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ ucfirst(\Illuminate\Support\Str::plural($noun)) }}</h3>
                <span class="count-pill">{{ $accnt->count() }} {{ \Illuminate\Support\Str::plural('person', $accnt->count()) }}</span>
            </div>
            <div class="card-body">
                <table id="example1" class="table table-hover">
                    <thead>
                        <tr>
                            <th class="cell-num">#</th>
                            <th>Name</th>
                            @if($showOffices)
                                <th>School / Office</th>
                                <th>Other designations</th>
                            @endif
                            <th class="cell-actions" data-orderable="false">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($accnt as $data)
                            <tr id="tr-{{ $data->id }}" class="{{ $editing && $data->id == $selectedAccnt->id ? 'bg-selectEdit' : '' }}">
                                <td class="cell-num">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</td>
                                <td>
                                    <span class="cell-strong" style="color: var(--ink); font-weight: 600;">{{ $data->person_accnt }}</span>
                                    @if((int) $data->accnt_role === 1)
                                        <span class="tag is-brass">Head</span>
                                    @endif
                                </td>
                                @if($showOffices)
                                    <td>{{ $data->office_name ?? '—' }}</td>
                                    <td>
                                        @forelse($data->other_offices as $abbr)
                                            <span class="tag">{{ $abbr }}</span>
                                        @empty
                                            <span class="text-muted">—</span>
                                        @endforelse
                                    </td>
                                @endif
                                <td class="cell-actions">
                                    <div class="row-actions">
                                        <a href="{{ route('accountableEdit', $data->id) }}" class="btn btn-icon" title="Edit" aria-label="Edit {{ $data->person_accnt }}">
                                            <i class="fas fa-pen"></i>
                                        </a>
                                        @if($canAssignOffice)
                                            <button type="button" class="btn btn-icon is-danger" title="Delete" aria-label="Delete {{ $data->person_accnt }}"
                                                data-delete-url="{{ route('accountableDelete', $data->id) }}"
                                                data-delete-label="{{ $data->person_accnt }}"
                                                @if($editing && $data->id == $selectedAccnt->id) data-after-delete="{{ route('accountableRead') }}" @endif>
                                                <i class="fas fa-trash-can"></i>
                                            </button>
                                        @endif
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
                <h3 class="card-title">{{ $editing ? 'Edit '.$noun : 'New '.$noun }}</h3>
                <span class="mode-flag {{ $editing ? '' : 'is-new' }}">{{ $editing ? 'Editing' : 'Create' }}</span>
            </div>
            <div class="card-body">
                <form action="{{ $editing ? route('accountableUpdate') : route('accountableCreate') }}" method="POST" class="js-validate" id="addAccnt">
                    @csrf
                    @if($editing)
                        <input type="hidden" name="id" value="{{ $selectedAccnt->id }}">
                    @endif

                    <div class="form-group">
                        <label for="person_accnt">Full name</label>
                        <input type="text" id="person_accnt" name="person_accnt" class="form-control"
                            value="{{ old('person_accnt', $selectedAccnt->person_accnt ?? '') }}"
                            placeholder="e.g. Maria L. Santos" maxlength="255" autocomplete="off"
                            oninput="titleCaseInput(this)" required data-msg-required="Enter the person's name.">
                    </div>

                    @if($canAssignOffice)
                        <div class="form-group">
                            <label for="accnt_role">Role</label>
                            <select id="accnt_role" name="accnt_role" class="form-control select2bs4" style="width: 100%;">
                                <option value="0" @selected((string) old('accnt_role', $selectedAccnt->accnt_role ?? 0) === '0')>Staff</option>
                                <option value="1" @selected((string) old('accnt_role', $selectedAccnt->accnt_role ?? 0) === '1')>Office head</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="off_id">School / Office</label>
                            <select id="off_id" name="off_id" class="form-control select2bs4" style="width: 100%;" required data-msg-required="Select the school or office.">
                                <option value="">Select an office</option>
                                @foreach ($office as $data)
                                    <option value="{{ $data->id }}" @selected(old('off_id', $selectedAccnt->off_id ?? null) == $data->id)>{{ $data->office_abbr }} · {{ $data->office_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="desig_offid">Other designated offices <span class="text-muted" style="font-weight: 400;">(optional)</span></label>
                            <select id="desig_offid" name="desig_offid[]" class="form-control select2bs4" style="width: 100%;" multiple data-placeholder="Add offices">
                                @foreach ($office as $data)
                                    <option value="{{ $data->id }}" @selected(in_array($data->id, old('desig_offid', $selectedDesignations)))>{{ $data->office_abbr }} · {{ $data->office_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="form-actions">
                        @if($editing)
                            <a href="{{ route('accountableRead') }}" class="btn btn-default">Cancel</a>
                        @endif
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-floppy-disk"></i> {{ $editing ? 'Save changes' : 'Add person' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
