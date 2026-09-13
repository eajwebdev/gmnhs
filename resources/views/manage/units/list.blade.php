@extends('layouts.master')

@section('body')
@php
    $editing = isset($selectedUnit);
@endphp

<div class="row crud-layout">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Units of measure</h3>
                <span class="count-pill">{{ $unit->count() }} {{ \Illuminate\Support\Str::plural('record', $unit->count()) }}</span>
            </div>
            <div class="card-body">
                <table id="example1" class="table table-hover">
                    <thead>
                        <tr>
                            <th class="cell-num">#</th>
                            <th>Unit</th>
                            <th>Added</th>
                            <th class="cell-actions" data-orderable="false">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($unit as $data)
                            <tr id="tr-{{ $data->id }}" class="{{ $editing && $data->id == $selectedUnit->id ? 'bg-selectEdit' : '' }}">
                                <td class="cell-num">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</td>
                                <td class="cell-strong">{{ $data->unit_name }}</td>
                                <td class="text-muted">{{ optional($data->created_at)->format('M d, Y') ?? '—' }}</td>
                                <td class="cell-actions">
                                    <div class="row-actions">
                                        <a href="{{ route('unitEdit', $data->id) }}" class="btn btn-icon" title="Edit" aria-label="Edit {{ $data->unit_name }}">
                                            <i class="fas fa-pen"></i>
                                        </a>
                                        <button type="button" class="btn btn-icon is-danger" title="Delete" aria-label="Delete {{ $data->unit_name }}"
                                            data-delete-url="{{ route('unitDelete', $data->id) }}"
                                            data-delete-label="{{ $data->unit_name }}"
                                            @if($editing && $data->id == $selectedUnit->id) data-after-delete="{{ route('unitRead') }}" @endif>
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
                <h3 class="card-title">{{ $editing ? 'Edit unit' : 'New unit' }}</h3>
                <span class="mode-flag {{ $editing ? '' : 'is-new' }}">{{ $editing ? 'Editing' : 'Create' }}</span>
            </div>
            <div class="card-body">
                <form action="{{ $editing ? route('unitUpdate') : route('unitCreate') }}" method="POST" class="js-validate" id="addUnit">
                    @csrf
                    @if($editing)
                        <input type="hidden" name="id" value="{{ $selectedUnit->id }}">
                    @endif

                    <div class="form-group">
                        <label for="unit_name">Unit name</label>
                        <input type="text" id="unit_name" name="unit_name" class="form-control"
                            value="{{ old('unit_name', $selectedUnit->unit_name ?? '') }}"
                            placeholder="e.g. Piece, Set, Box" maxlength="255" autocomplete="off"
                            oninput="titleCaseInput(this)" required data-msg-required="Enter the unit name.">
                    </div>

                    <div class="form-actions">
                        @if($editing)
                            <a href="{{ route('unitRead') }}" class="btn btn-default">Cancel</a>
                        @endif
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-floppy-disk"></i> {{ $editing ? 'Save changes' : 'Add unit' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
