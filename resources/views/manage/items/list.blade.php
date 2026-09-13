@extends('layouts.master')

@section('body')
@php
    $editing = isset($selectedItem);
    $filterQuery = array_filter(request()->only('off', 'descrip'), 'strlen');
    $filtered = !empty($filterQuery);
@endphp

<div class="row crud-layout">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Item catalogue</h3>
                <span class="count-pill">{{ $item->count() }} {{ \Illuminate\Support\Str::plural('item', $item->count()) }}</span>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ $editing ? route('itemEdit', $selectedItem->id) : route('itemRead') }}" class="filter-bar">
                    <div class="form-group">
                        <label for="filter_descrip">Description contains</label>
                        <input type="text" id="filter_descrip" name="descrip" class="form-control form-control-sm" value="{{ request('descrip') }}" placeholder="e.g. laptop">
                    </div>
                    <div class="form-group">
                        <label for="filter_off">Office</label>
                        <select id="filter_off" name="off" class="form-control form-control-sm">
                            <option value="">All offices</option>
                            @foreach($office as $off)
                                <option value="{{ $off->id }}" @selected(request('off') == $off->id)>{{ $off->office_abbr ?: $off->office_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="d-flex" style="gap: 6px;">
                        <button type="submit" class="btn btn-default btn-sm"><i class="fas fa-filter"></i> Apply</button>
                        @if($filtered)
                            <a href="{{ $editing ? route('itemEdit', $selectedItem->id) : route('itemRead') }}" class="btn btn-default btn-sm">Clear</a>
                        @endif
                    </div>
                </form>

                <table id="example1" class="table table-hover">
                    <thead>
                        <tr>
                            <th class="cell-num">#</th>
                            <th>Item name</th>
                            <th>{{ $filtered ? 'Matching records' : 'Property records' }}</th>
                            <th class="cell-actions" data-orderable="false">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($item as $data)
                            @php $count = $inventoryCount[$data->id] ?? 0; @endphp
                            <tr id="tr-{{ $data->id }}" class="{{ $editing && $data->id == $selectedItem->id ? 'bg-selectEdit' : '' }}">
                                <td class="cell-num">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</td>
                                <td class="cell-strong">{{ $data->item_name }}</td>
                                <td data-order="{{ $count }}">
                                    <span class="mono {{ $count ? '' : 'text-muted' }}">{{ number_format($count) }}</span>
                                </td>
                                <td class="cell-actions">
                                    <div class="row-actions">
                                        <a href="{{ route('itemEdit', array_merge(['id' => $data->id], $filterQuery)) }}" class="btn btn-icon" title="Edit" aria-label="Edit {{ $data->item_name }}">
                                            <i class="fas fa-pen"></i>
                                        </a>
                                        <button type="button" class="btn btn-icon is-danger" title="Delete" aria-label="Delete {{ $data->item_name }}"
                                            data-delete-url="{{ route('itemDelete', $data->id) }}"
                                            data-delete-label="{{ $data->item_name }}"
                                            @if($editing && $data->id == $selectedItem->id) data-after-delete="{{ route('itemRead') }}" @endif>
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
                <h3 class="card-title">{{ $editing ? 'Edit item' : 'New item' }}</h3>
                <span class="mode-flag {{ $editing ? '' : 'is-new' }}">{{ $editing ? 'Editing' : 'Create' }}</span>
            </div>
            <div class="card-body">
                <form action="{{ $editing ? route('itemUpdate') : route('itemCreate') }}" method="POST" class="js-validate" id="addItem">
                    @csrf
                    @if($editing)
                        <input type="hidden" name="id" value="{{ $selectedItem->id }}">
                    @endif

                    <div class="form-group">
                        <label for="item_name">Item name</label>
                        <input type="text" id="item_name" name="item_name" class="form-control"
                            value="{{ old('item_name', $selectedItem->item_name ?? '') }}"
                            placeholder="e.g. Laptop Computer" maxlength="255" autocomplete="off"
                            oninput="titleCaseInput(this)" required data-msg-required="Enter the item name.">
                        <span class="form-hint">Items in use by purchases or properties can be renamed but not deleted.</span>
                    </div>

                    <div class="form-actions">
                        @if($editing)
                            <a href="{{ route('itemRead') }}" class="btn btn-default">Cancel</a>
                        @endif
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-floppy-disk"></i> {{ $editing ? 'Save changes' : 'Add item' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
