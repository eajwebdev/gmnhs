@extends('layouts.master')

@php
    $isSupply = in_array(auth()->user()->role, ['Administrator', 'Supply Officer']);
@endphp

@section('body')
<div class="container-fluid return-slip-page">
    <div class="return-slip-hero">
        <div>
            <span class="return-slip-kicker">Property Accountability</span>
            <h1>Returned Items</h1>
            <p>
                Any property, in any condition, can be returned one at a time and is tagged
                <strong>Returned</strong> in the system.
                Administrators and Supply Officers can record returns too, then transfer each item to a
                specific end user or update its remarks to Unserviceable.
            </p>
        </div>
        <div class="return-slip-hero-actions">
            <a href="{{ route('returnSlips.slipReport.form') }}" class="btn btn-outline-light return-slip-action">
                <i class="fas fa-file-pdf"></i> Return Slip Report
            </a>
            @if($isSupply)
                <a href="{{ route('returnSlips.transferReport') }}" class="btn btn-outline-light return-slip-action">
                    <i class="fas fa-file-pdf"></i> Transfer Report
                </a>
                <a href="{{ route('returnSlips.iirupReport') }}" class="btn btn-outline-light return-slip-action">
                    <i class="fas fa-file-excel"></i> IIRUP Report
                </a>
                <a href="{{ route('returnSlips.logs') }}" class="btn btn-outline-light return-slip-action">
                    <i class="fas fa-history"></i> Item Logs
                </a>
            @endif
            @if($canRecordReturn)
                <button type="button" class="btn btn-light return-slip-action" data-toggle="modal" data-target="#newReturnModal">
                    <i class="fas fa-plus"></i> Return an Item
                </button>
            @else
                <button type="button" class="btn btn-light return-slip-action" disabled title="{{ $enduserBlockedMessage }}">
                    <i class="fas fa-lock"></i> Return an Item
                </button>
            @endif
        </div>
    </div>

    @if(!$canRecordReturn)
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i> {{ $enduserBlockedMessage }}
        </div>
    @endif

    <div class="row">
        <div class="col-6 col-lg">
            <div class="return-slip-stat">
                <span>Awaiting Action</span>
                <strong>{{ number_format($awaitingCount) }}</strong>
            </div>
        </div>
        <div class="col-6 col-lg">
            <div class="return-slip-stat">
                <span>Transferred</span>
                <strong>{{ number_format($transferredCount) }}</strong>
            </div>
        </div>
        <div class="col-6 col-lg">
            <div class="return-slip-stat">
                <span>Unserviceable</span>
                <strong>{{ number_format($unserviceableCount) }}</strong>
            </div>
        </div>
        <div class="col-6 col-lg">
            <div class="return-slip-stat">
                <span>Obsolete</span>
                <strong>{{ number_format($obsoleteCount) }}</strong>
            </div>
        </div>
        <div class="col-6 col-lg">
            <div class="return-slip-stat">
                <span>Total Returns</span>
                <strong>{{ number_format($returns->count()) }}</strong>
            </div>
        </div>
    </div>

    <div class="card return-slip-card">
        <div class="card-header">
            <h3 class="card-title">Return Records</h3>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-hover mb-0" id="returnSlipItemsTable">
                <thead>
                    <tr>
                        <th>Return No.</th>
                        <th>Property No.</th>
                        <th>Item</th>
                        <th>Returned By</th>
                        <th>From Office</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th style="min-width: 250px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($returns as $row)
                        @php
                            $itemStatus = $row->item_status ?: 'Returned';
                            $awaiting = in_array($itemStatus, ['Returned', 'Pending'], true);
                            $canDelete = $isSupply || $row->slip_user_id === auth()->id();
                        @endphp
                        <tr>
                            <td>RS-{{ str_pad($row->slip_id, 5, '0', STR_PAD_LEFT) }}</td>
                            <td>{{ $row->property_no_generated }}</td>
                            <td>
                                {{ $row->item_name }}
                                @if($row->serial_number)
                                    <div class="text-muted small">SN: {{ $row->serial_number }}</div>
                                @endif
                            </td>
                            <td>{{ $row->returned_by_display ?: 'Not specified' }}</td>
                            <td>{{ $row->current_office_name }}</td>
                            <td>
                                <span class="return-slip-badge status-{{ str_replace(' ', '-', strtolower($itemStatus)) }}">
                                    {{ $itemStatus }}
                                </span>
                                @if($itemStatus === 'Transferred' && $row->transferred_to_enduser_name)
                                    <div class="text-muted small mt-1">To: {{ $row->transferred_to_enduser_name }}</div>
                                @endif
                            </td>
                            <td>{{ $row->returned_at ? \Carbon\Carbon::parse($row->returned_at)->format('M d, Y') : \Carbon\Carbon::parse($row->slip_created_at)->format('M d, Y') }}</td>
                            <td>
                                <div class="return-slip-row-action">
                                    <a href="{{ route('returnSlips.show', $row->slip_id) }}" class="btn btn-sm btn-outline-success">
                                        <i class="fas fa-eye"></i> View
                                    </a>

                                    <a href="{{ route('returnSlips.slipReport.form', ['slip' => $row->slip_id]) }}"
                                       class="btn btn-sm btn-outline-primary" title="Preview and print the Property Return Slip">
                                        <i class="fas fa-file-pdf"></i> Slip
                                    </a>

                                    @if($awaiting && $isSupply)
                                        <button type="button"
                                                class="btn btn-sm btn-success js-transfer-item"
                                                data-item-id="{{ $row->item_id }}"
                                                data-property-no="{{ $row->property_no_generated }}"
                                                data-item-name="{{ $row->item_name }}"
                                                data-returned-by="{{ $row->returned_by_display }}">
                                            <i class="fas fa-user-check"></i> Transfer
                                        </button>
                                        <button type="button"
                                                class="btn btn-sm btn-danger js-unserviceable-item"
                                                data-item-id="{{ $row->item_id }}"
                                                data-property-no="{{ $row->property_no_generated }}"
                                                data-item-name="{{ $row->item_name }}"
                                                data-returned-by="{{ $row->returned_by_display }}">
                                            <i class="fas fa-ban"></i> Unserviceable
                                        </button>
                                        <button type="button"
                                                class="btn btn-sm btn-secondary js-obsolete-item"
                                                data-item-id="{{ $row->item_id }}"
                                                data-property-no="{{ $row->property_no_generated }}"
                                                data-item-name="{{ $row->item_name }}"
                                                data-returned-by="{{ $row->returned_by_display }}">
                                            <i class="fas fa-archive"></i> Obsolete
                                        </button>
                                    @endif

                                    @if($awaiting && $canDelete)
                                        <button type="button"
                                                class="btn btn-sm btn-outline-warning js-cancel-item"
                                                data-item-id="{{ $row->item_id }}"
                                                data-property-no="{{ $row->property_no_generated }}"
                                                data-item-name="{{ $row->item_name }}"
                                                data-returned-by="{{ $row->returned_by_display }}">
                                            <i class="fas fa-undo"></i> Cancel
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

@include('return_slips.partials.action_modals')
@if($canRecordReturn)
    @include('return_slips.partials.create_modal')
@endif
@endsection

@section('scripts')
<script>
    $(function () {
        $('#returnSlipItemsTable').DataTable({
            responsive: true,
            lengthChange: true,
            autoWidth: false,
            pageLength: 10,
            order: [[6, 'desc']],
            columnDefs: [
                { orderable: false, searchable: false, targets: 7 }
            ],
            language: {
                search: 'Search:',
                lengthMenu: 'Show _MENU_ returns',
                emptyTable: 'No returned items yet.',
                info: 'Showing _START_ to _END_ of _TOTAL_ returns',
                infoEmpty: 'Showing 0 to 0 of 0 returns',
                infoFiltered: '(filtered from _MAX_ total returns)'
            },
            dom: '<"d-flex justify-content-between align-items-center mb-3"l<"dataTables_filter_wrapper"f>>rtip'
        });
    });
</script>
@include('return_slips.partials.action_scripts')
@if($canRecordReturn)
    @include('return_slips.partials.create_scripts')
@endif
@endsection
