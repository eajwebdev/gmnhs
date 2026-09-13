@extends('layouts.master')

@section('body')
@php
    $editing = isset($selectedOffice);
    $isLocation = (int) $code === 2;
    $noun = $isLocation ? 'location' : 'office';
@endphp

<div class="row crud-layout">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ $isLocation ? 'Locations' : 'School offices' }}</h3>
                <span class="count-pill">{{ $office->count() }} {{ \Illuminate\Support\Str::plural('record', $office->count()) }}</span>
            </div>
            <div class="card-body">
                <table id="example1" class="table table-hover">
                    <thead>
                        <tr>
                            <th class="cell-num">#</th>
                            @unless($isLocation)
                                <th>Code</th>
                            @endunless
                            <th>{{ $isLocation ? 'Location' : 'Office' }}</th>
                            @unless($isLocation)
                                <th>Abbr.</th>
                                <th>Office head</th>
                            @endunless
                            <th class="cell-actions" data-orderable="false">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($office as $data)
                            <tr id="tr-{{ $data->id }}" class="{{ $editing && $data->id == $selectedOffice->id ? 'bg-selectEdit' : '' }}">
                                <td class="cell-num">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</td>
                                @unless($isLocation)
                                    <td class="cell-code">{{ $data->office_code }}</td>
                                @endunless
                                <td class="cell-strong">{{ $data->office_name }}</td>
                                @unless($isLocation)
                                    <td><span class="tag">{{ $data->office_abbr }}</span></td>
                                    <td>{{ $data->office_officer ?: '—' }}</td>
                                @endunless
                                <td class="cell-actions">
                                    <div class="row-actions">
                                        <a href="{{ route('officeEdit', ['id' => $data->id, 'code' => $code]) }}" class="btn btn-icon" title="Edit" aria-label="Edit {{ $data->office_name }}">
                                            <i class="fas fa-pen"></i>
                                        </a>
                                        <button type="button" class="btn btn-icon is-danger" title="Delete" aria-label="Delete {{ $data->office_name }}"
                                            data-delete-url="{{ route('officeDelete', $data->id) }}"
                                            data-delete-label="{{ $data->office_name }}"
                                            @if($editing && $data->id == $selectedOffice->id) data-after-delete="{{ route('officeRead', $code) }}" @endif>
                                            <i class="fas fa-trash-can"></i>
                                        </button>
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
                <form action="{{ $editing ? route('officeUpdate') : route('officeCreate') }}" method="POST" class="js-validate" id="addoffice">
                    @csrf
                    <input type="hidden" name="code" value="{{ $code }}">
                    @if($editing)
                        <input type="hidden" name="id" value="{{ $selectedOffice->id }}">
                    @endif

                    @unless($isLocation)
                        <div class="form-group">
                            <label for="office_code">Office code</label>
                            <input type="text" id="office_code" name="office_code" class="form-control mono"
                                value="{{ old('office_code', $selectedOffice->office_code ?? '') }}"
                                placeholder="0101" inputmode="numeric" maxlength="4" autocomplete="off"
                                oninput="this.value = this.value.replace(/\D/g, '')"
                                required data-msg-required="Enter the office code.">
                            <span class="form-hint">Up to 4 digits; shorter codes are padded, e.g. <code>12</code> → <code>0012</code>.</span>
                        </div>
                    @endunless

                    <div class="form-group">
                        <label for="office_name">{{ $isLocation ? 'Location name' : 'Office name' }}</label>
                        <input type="text" id="office_name" name="office_name" class="form-control"
                            value="{{ old('office_name', $selectedOffice->office_name ?? '') }}"
                            placeholder="{{ $isLocation ? 'e.g. ROOM 104' : 'e.g. GUIDANCE OFFICE' }}" maxlength="255" autocomplete="off"
                            oninput="this.value = this.value.toUpperCase()"
                            required data-msg-required="Enter the {{ $noun }} name.">
                    </div>

                    @unless($isLocation)
                        <div class="form-group">
                            <label for="office_abbr">Abbreviation</label>
                            <input type="text" id="office_abbr" name="office_abbr" class="form-control"
                                value="{{ old('office_abbr', $selectedOffice->office_abbr ?? '') }}"
                                placeholder="e.g. GO" maxlength="255" autocomplete="off"
                                oninput="this.value = this.value.toUpperCase()"
                                required data-msg-required="Enter the abbreviation.">
                        </div>

                        <div class="form-group">
                            <label for="office_officer">Office head</label>
                            <input type="text" id="office_officer" name="office_officer" class="form-control"
                                value="{{ old('office_officer', $selectedOffice->office_officer ?? '') }}"
                                placeholder="Full name" maxlength="255" autocomplete="off"
                                oninput="titleCaseInput(this)"
                                required data-msg-required="Enter the office head.">
                        </div>
                    @endunless

                    <div class="form-actions">
                        @if($editing)
                            <a href="{{ route('officeRead', $code) }}" class="btn btn-default">Cancel</a>
                        @endif
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-floppy-disk"></i> {{ $editing ? 'Save changes' : 'Add '.$noun }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
