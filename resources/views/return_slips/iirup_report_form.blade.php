@extends('layouts.master')

@section('body')
<div class="container-fluid return-slip-page">
    <div class="return-slip-hero">
        <div>
            <span class="return-slip-kicker">Appendix 74</span>
            <h1>IIRUP Report</h1>
            <p>
                Built from the return-slip logs. Items tagged <strong>Unserviceable</strong>
                or <strong>Obsolete</strong> are listed on the official IIRUP Excel form.
            </p>
        </div>
        <a href="{{ route('returnSlips.index') }}" class="btn btn-light return-slip-action">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i> {{ $errors->first() }}
        </div>
    @endif

    <div class="row">
        <div class="col-lg-4">
            <div class="card return-slip-card">
                <div class="card-header">
                    <h3 class="card-title">Report Options</h3>
                </div>
                <form id="iirupForm" action="{{ route('returnSlips.iirupReport') }}" method="GET">
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group col-6">
                                <label>Became From</label>
                                <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] }}">
                            </div>
                            <div class="form-group col-6">
                                <label>Became To</label>
                                <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] }}">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" class="form-control">
                                <option value="all" @selected($filters['status'] === 'all')>Unserviceable and Obsolete</option>
                                @foreach($statuses as $status)
                                    <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ $status }} only</option>
                                @endforeach
                            </select>
                            <small class="text-muted">
                                Date filter is based on the log created_at date when the item became Unserviceable or Obsolete.
                            </small>
                        </div>

                        <div class="form-group">
                            <label>Fund Cluster</label>
                            <input type="text" name="fund_cluster" class="form-control"
                                   value="{{ $filters['fund_cluster'] }}" maxlength="100">
                        </div>

                        <div class="form-group">
                            <label>Name of Accountable Officer</label>
                            <input type="text" name="accountable_name" class="form-control"
                                   value="{{ $filters['accountable_name'] }}" maxlength="150">
                        </div>

                        <div class="form-group">
                            <label>Designation</label>
                            <input type="text" name="designation" class="form-control"
                                   value="{{ $filters['designation'] }}" maxlength="150">
                        </div>

                        <div class="form-group mb-0">
                            <label>Station</label>
                            <input type="text" name="station" class="form-control"
                                   value="{{ $filters['station'] }}" maxlength="150">
                        </div>
                    </div>
                    <div class="card-footer d-flex flex-wrap">
                        <button type="submit" class="btn btn-success mr-2 mb-1">
                            <i class="fas fa-filter"></i> Apply Filters
                        </button>
                        <button type="submit"
                                formaction="{{ route('returnSlips.iirupReport.generate') }}"
                                formtarget="_blank"
                                name="format"
                                value="pdf"
                                class="btn btn-outline-secondary mr-2 mb-1">
                            <i class="fas fa-external-link-alt"></i> Open PDF in New Tab
                        </button>
                        <button type="submit"
                                formaction="{{ route('returnSlips.iirupReport.generate') }}"
                                formtarget="_blank"
                                name="format"
                                value="excel"
                                class="btn btn-outline-success mb-1">
                            <i class="fas fa-file-excel"></i> Download Excel
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="row">
                <div class="col-md-4">
                    <div class="return-slip-stat">
                        <span>Items Covered</span>
                        <strong>{{ number_format($data['item_count']) }}</strong>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="return-slip-stat">
                        <span>Total Cost</span>
                        <strong>{{ number_format($data['total_cost'], 2) }}</strong>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="return-slip-stat">
                        <span>Period</span>
                        <strong style="font-size: 0.95rem;">{{ $data['period_label'] }}</strong>
                    </div>
                </div>
            </div>

            <div class="card return-slip-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">Rows for IIRUP</h3>
                    <span class="text-muted small">{{ $data['scope_label'] }}</span>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Date Acquired</th>
                                <th>Date</th>
                                <th>Particulars / Article</th>
                                <th>Property / Serial No.</th>
                                <th class="text-right">Qty</th>
                                <th class="text-right">Total Cost</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($data['items'] as $row)
                                <tr>
                                    <td>{{ $row['date_acquired'] ?: '-' }}</td>
                                    <td>{{ $row['log_date'] ?: '-' }}</td>
                                    <td>
                                        {{ $row['item_name'] }}
                                        @if($row['description'] && strcasecmp($row['description'], (string) $row['item_name']) !== 0)
                                            <div class="text-muted small">{{ $row['description'] }}</div>
                                        @endif
                                        @if($row['model'])
                                            <div class="text-muted small">Model: {{ $row['model'] }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $row['property_no'] }}
                                        @if($row['serial_number'])
                                            <div class="text-muted small">SN: {{ $row['serial_number'] }}</div>
                                        @endif
                                    </td>
                                    <td class="text-right">{{ $row['qty'] + 0 }}</td>
                                    <td class="text-right">{{ number_format($row['total_cost'], 2) }}</td>
                                    <td>
                                        <span class="return-slip-badge status-{{ str_replace(' ', '-', strtolower($row['status'])) }}">
                                            {{ $row['status'] }}
                                        </span>
                                        <div class="text-muted small mt-1">
                                            {{ $row['slip_no'] }}
                                            @if($row['office_name'])
                                                &middot; {{ $row['office_name'] }}
                                            @endif
                                        </div>
                                        @if($row['remarks'])
                                            <div class="text-muted small">Note: "{{ $row['remarks'] }}"</div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        No unserviceable or obsolete item logs match the selected filters.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer text-muted small">
                    <div class="d-flex flex-wrap justify-content-between align-items-center">
                        <div>
                            Showing {{ $data['items']->firstItem() ?: 0 }} to {{ $data['items']->lastItem() ?: 0 }}
                            of {{ number_format($data['items']->total()) }} rows. PDF and Excel include all matching rows.
                            Disposal, appraised value, and record-of-sales columns are left blank for inspection/disposal entries.
                        </div>
                        <div>
                            {{ $data['items']->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
