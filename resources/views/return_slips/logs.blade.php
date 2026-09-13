@extends('layouts.master')

@section('body')
<div class="container-fluid return-slip-page">
    <div class="return-slip-hero">
        <div>
            <span class="return-slip-kicker">Audit Trail</span>
            <h1>Return Item Logs</h1>
            <p>Every return, transfer, unserviceable tagging, cancellation, and deletion recorded against a property.</p>
        </div>
        <a href="{{ route('returnSlips.index') }}" class="btn btn-light return-slip-action">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    </div>

    <div class="card return-slip-card">
        <div class="card-header">
            <h3 class="card-title">Recorded Actions</h3>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-hover mb-0" id="returnSlipLogsTable">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Return No.</th>
                        <th>Property No.</th>
                        <th>Action</th>
                        <th>Details</th>
                        <th>Remarks Change</th>
                        <th>Performed By</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                        <tr>
                            <td>{{ optional($log->created_at)->format('M d, Y h:i A') }}</td>
                            <td>RS-{{ str_pad($log->return_slip_id, 5, '0', STR_PAD_LEFT) }}</td>
                            <td>{{ $log->property_no_generated }}</td>
                            <td>
                                <span class="return-slip-badge status-{{ str_replace(' ', '-', strtolower($log->action)) }}">
                                    {{ $log->action }}
                                </span>
                            </td>
                            <td>
                                {{ $log->description }}
                                @if($log->remarks)
                                    <div class="text-muted small">"{{ $log->remarks }}"</div>
                                @endif
                            </td>
                            <td>{{ $log->from_value ?: 'N/A' }} &rarr; {{ $log->to_value ?: 'N/A' }}</td>
                            <td>
                                {{ $log->user_name ?: 'Unknown user' }}
                                <div class="text-muted small">{{ $log->user_role }}</div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(function () {
        $('#returnSlipLogsTable').DataTable({
            responsive: true,
            lengthChange: true,
            autoWidth: false,
            pageLength: 25,
            order: [[0, 'desc']],
            language: {
                search: 'Search:',
                lengthMenu: 'Show _MENU_ entries',
                emptyTable: 'No log entries recorded yet.',
                info: 'Showing _START_ to _END_ of _TOTAL_ entries',
                infoEmpty: 'Showing 0 to 0 of 0 entries',
                infoFiltered: '(filtered from _MAX_ total entries)'
            },
            dom: '<"d-flex justify-content-between align-items-center mb-3"l<"dataTables_filter_wrapper"f>>rtip'
        });
    });
</script>
@endsection
