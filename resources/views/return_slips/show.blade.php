@extends('layouts.master')

@php
    $isSupply = in_array(auth()->user()->role, ['Administrator', 'Supply Officer']);
@endphp

@section('body')
<div class="container-fluid return-slip-page">
    <div class="return-slip-hero">
        <div>
            <span class="return-slip-kicker">Return Details</span>
            <h1>RS-{{ str_pad($slip->id, 5, '0', STR_PAD_LEFT) }}</h1>
            <p>Returned by {{ $slip->returned_by_display ?: 'an unspecified person' }}, recorded by {{ $slip->recorder_name ?: $slip->requested_by }}.</p>
        </div>
        <div class="return-slip-hero-actions">
            <a href="{{ route('returnSlips.slipReport.form', ['slip' => $slip->id]) }}"
               class="btn btn-light return-slip-action">
                <i class="fas fa-eye"></i> Preview Return Slip
            </a>
            <a href="{{ route('returnSlips.slipReport', $slip->id) }}" target="_blank"
               class="btn btn-outline-light return-slip-action">
                <i class="fas fa-file-pdf"></i> Print
            </a>
            <a href="{{ route('returnSlips.slipReport', ['id' => $slip->id, 'format' => 'excel']) }}"
               class="btn btn-outline-light return-slip-action">
                <i class="fas fa-file-excel"></i> Excel
            </a>
            <a href="{{ route('returnSlips.index') }}" class="btn btn-outline-light return-slip-action">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4">
            <div class="card return-slip-card">
                <div class="card-header">
                    <h3 class="card-title">Return Summary</h3>
                </div>
                <div class="card-body">
                    <dl class="return-slip-summary">
                        <dt>Status</dt>
                        <dd><span class="return-slip-badge status-{{ str_replace(' ', '-', strtolower($slip->status)) }}">{{ $slip->status }}</span></dd>
                        <dt>Returned By</dt>
                        <dd>{{ $slip->returned_by_display ?: 'Not specified' }}</dd>
                        <dt>Received / Recorded By</dt>
                        <dd>{{ $slip->recorder_name ?: $slip->requested_by }}</dd>
                        <dt>Date Returned</dt>
                        <dd>{{ optional($slip->returned_at ?? $slip->created_at)->format('M d, Y h:i A') }}</dd>
                        <dt>Source School</dt>
                        <dd>{{ $slip->source_school_name ?? 'Main School / Office' }}</dd>
                        <dt>Reason</dt>
                        <dd>{{ $slip->reason ?: 'No reason provided' }}</dd>
                        @if($slip->target_office_name)
                            <dt>Transferred To Office</dt>
                            <dd>{{ $slip->target_office_name }}</dd>
                        @endif
                    </dl>
                    <div class="alert alert-light border mb-0">
                        Each returned item is handled on its own: transfer it to a specific end user, or update
                        its remarks to Unserviceable. Every action is written to the item log below.
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card return-slip-card">
                <div class="card-header">
                    <h3 class="card-title">Returned Item</h3>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Property No.</th>
                                <th>Item</th>
                                <th>From Office</th>
                                <th>Status</th>
                                <th style="min-width: 250px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($items as $item)
                                @php
                                    $itemStatus = $item->status ?: 'Returned';
                                    $awaiting = in_array($itemStatus, ['Returned', 'Pending'], true);
                                    $canDelete = $isSupply || $slip->user_id === auth()->id();
                                @endphp
                                <tr>
                                    <td>{{ $item->property_no_generated }}</td>
                                    <td>
                                        {{ $item->item_name }}
                                        @if($item->serial_number)
                                            <div class="text-muted small">SN: {{ $item->serial_number }}</div>
                                        @endif
                                    </td>
                                    <td>{{ $item->current_office_name }}</td>
                                    <td>
                                        <span class="return-slip-badge status-{{ str_replace(' ', '-', strtolower($itemStatus)) }}">
                                            {{ $itemStatus }}
                                        </span>
                                        <div class="text-muted small mt-1">Before return: {{ $item->previous_remarks ?: ($item->current_status ?: 'No Status') }}</div>
                                        @if($item->transferred_to_enduser_name)
                                            <div class="text-muted small mt-1">
                                                To: {{ $item->transferred_to_enduser_name }}
                                                @if($item->transferred_to_office_name)
                                                    ({{ $item->transferred_to_office_name }})
                                                @endif
                                            </div>
                                        @endif
                                        @if($item->action_remarks)
                                            <div class="text-muted small mt-1">"{{ $item->action_remarks }}"</div>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="return-slip-row-action">
                                            @if($awaiting && $isSupply)
                                                <button type="button"
                                                        class="btn btn-sm btn-success js-transfer-item"
                                                        data-item-id="{{ $item->id }}"
                                                        data-property-no="{{ $item->property_no_generated }}"
                                                        data-item-name="{{ $item->item_name }}"
                                                        data-returned-by="{{ $slip->returned_by_display }}">
                                                    <i class="fas fa-user-check"></i> Transfer to End User
                                                </button>
                                                <button type="button"
                                                        class="btn btn-sm btn-danger js-unserviceable-item"
                                                        data-item-id="{{ $item->id }}"
                                                        data-property-no="{{ $item->property_no_generated }}"
                                                        data-item-name="{{ $item->item_name }}"
                                                        data-returned-by="{{ $slip->returned_by_display }}">
                                                    <i class="fas fa-ban"></i> Unserviceable
                                                </button>
                                                <button type="button"
                                                        class="btn btn-sm btn-secondary js-obsolete-item"
                                                        data-item-id="{{ $item->id }}"
                                                        data-property-no="{{ $item->property_no_generated }}"
                                                        data-item-name="{{ $item->item_name }}"
                                                        data-returned-by="{{ $slip->returned_by_display }}">
                                                    <i class="fas fa-archive"></i> Obsolete
                                                </button>
                                            @endif

                                            @if($awaiting && $canDelete)
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-warning js-cancel-item"
                                                        data-item-id="{{ $item->id }}"
                                                        data-property-no="{{ $item->property_no_generated }}"
                                                        data-item-name="{{ $item->item_name }}"
                                                        data-returned-by="{{ $slip->returned_by_display }}">
                                                    <i class="fas fa-undo"></i> Cancel
                                                </button>
                                                <form action="{{ route('returnSlips.items.delete', $item->id) }}" method="POST" class="js-delete-return d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete return record">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            @endif

                                            @if(!$awaiting)
                                                <span class="text-muted small">Closed &mdash; no action available</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card return-slip-card">
                <div class="card-header">
                    <h3 class="card-title">Item Log</h3>
                </div>
                <div class="card-body">
                    @forelse($logs as $log)
                        <div class="return-slip-log">
                            <div class="return-slip-log-dot action-{{ strtolower($log->action) }}"></div>
                            <div class="return-slip-log-body">
                                <div class="return-slip-log-head">
                                    <strong>{{ $log->action }}</strong>
                                    <span class="text-muted small">{{ optional($log->created_at)->format('M d, Y h:i A') }}</span>
                                </div>
                                <div>{{ $log->description }}</div>
                                @if($log->from_value || $log->to_value)
                                    <div class="text-muted small">Remarks: {{ $log->from_value ?: 'N/A' }} &rarr; {{ $log->to_value ?: 'N/A' }}</div>
                                @endif
                                @if($log->remarks)
                                    <div class="text-muted small">Note: "{{ $log->remarks }}"</div>
                                @endif
                                <div class="text-muted small">By {{ $log->user_name ?: 'Unknown user' }} ({{ $log->user_role }}) &middot; {{ $log->property_no_generated }}</div>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted mb-0">No log entries recorded for this return yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

@include('return_slips.partials.action_modals')
@endsection

@section('scripts')
@include('return_slips.partials.action_scripts')
@endsection

