@extends('layouts.master')

@section('body')
@php
    $inventoryTotal = $reportableItemsCount;
    $latestReportableDate = $latestReportableItem ? date('M d, Y', strtotime($latestReportableItem)) : 'No acquired date yet';

    $propertyBreakdown = [
        ['label' => 'RPCPPE / PAR', 'value' => $rpcppeCount, 'amount' => $rpcppeValue, 'color' => '#d99a00'],
        ['label' => 'RPCSEP / ICS', 'value' => $rpcsepCount, 'amount' => $rpcsepValue, 'color' => '#137a4b'],
        ['label' => 'Unserviceable', 'value' => $unserviceableCount, 'amount' => $unserviceableValue, 'color' => '#b24a3b'],
    ];
@endphp

<div class="container-fluid dashboard-page">
    <div class="dashboard-hero">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <div class="page-head-eyebrow">Overview · {{ now()->format('F Y') }}</div>
                @if($isTechnician)
                    <h1>Technician Repair Dashboard</h1>
                    <p>Track pending diagnosis, items ready for release, and repair workload for your assigned school.</p>
                @elseif($isSchoolAdmin)
                    <h1>School Inventory Dashboard</h1>
                    <p>Review reportable school records using the same active rules used by report generation.</p>
                @else
                    <h1>{{ $isSupplyOfficer ? 'Supply Officer Dashboard' : 'Administrator Dashboard' }}</h1>
                    <p>Monitor system records, reportable inventory, and school activity from one operating view.</p>
                @endif
            </div>
            <div class="col-lg-4 text-lg-right mt-3 mt-lg-0">
                <span class="dashboard-chip">
                    <i class="fas fa-shield-alt"></i>
                    {{ display_role(auth()->user()->role) }}
                </span>
            </div>
        </div>
    </div>

    @if($isAdministrator || $isSupplyOfficer)
        <div class="row">
            <div class="col-xl-3 col-md-6">
                <div class="metric-card">
                    <div>
                        <div class="metric-label">Users</div>
                        <div class="metric-value">{{ number_format($userCount) }}</div>
                        <div class="metric-note">Registered system accounts</div>
                    </div>
                    <div class="metric-icon icon-blue"><i class="fas fa-users"></i></div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="metric-card">
                    <div>
                        <div class="metric-label">Offices</div>
                        <div class="metric-value">{{ number_format($offCount) }}</div>
                        <div class="metric-note">Managed offices and units</div>
                    </div>
                    <div class="metric-icon icon-gray"><i class="fas fa-building"></i></div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="metric-card">
                    <div>
                        <div class="metric-label">School</div>
                        <div class="metric-value">{{ number_format($schoolCount) }}</div>
                        <div class="metric-note">Standalone school profile</div>
                    </div>
                    <div class="metric-icon icon-green"><i class="fas fa-map-marker-alt"></i></div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="metric-card">
                    <div>
                        <div class="metric-label">Property Types</div>
                        <div class="metric-value">{{ number_format($propertyCount) }}</div>
                        <div class="metric-note">Configured classifications</div>
                    </div>
                    <div class="metric-icon icon-gold"><i class="fas fa-layer-group"></i></div>
                </div>
            </div>
        </div>
    @elseif($isTechnician)
        <div class="row">
            <div class="col-xl-3 col-md-6">
                <div class="metric-card">
                    <div>
                        <div class="metric-label">Repair Records</div>
                        <div class="metric-value">{{ number_format($repairTotalCount) }}</div>
                        <div class="metric-note">Total repair transactions in scope</div>
                    </div>
                    <div class="metric-icon icon-blue"><i class="fas fa-screwdriver-wrench"></i></div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="metric-card">
                    <div>
                        <div class="metric-label">For Diagnosis</div>
                        <div class="metric-value">{{ number_format($repairPendingCount) }}</div>
                        <div class="metric-note">Received items without diagnosis</div>
                    </div>
                    <div class="metric-icon icon-gold"><i class="fas fa-stethoscope"></i></div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="metric-card">
                    <div>
                        <div class="metric-label">For Release</div>
                        <div class="metric-value">{{ number_format($repairForReleaseCount) }}</div>
                        <div class="metric-note">Diagnosed items waiting release</div>
                    </div>
                    <div class="metric-icon icon-green"><i class="fas fa-box-open"></i></div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="metric-card">
                    <div>
                        <div class="metric-label">Released</div>
                        <div class="metric-value">{{ number_format($repairReleasedCount) }}</div>
                        <div class="metric-note">Completed repair releases</div>
                    </div>
                    <div class="metric-icon icon-gray"><i class="fas fa-check-circle"></i></div>
                </div>
            </div>
        </div>
    @else
        <div class="row">
            <div class="col-xl-3 col-md-6">
                <div class="metric-card">
                    <div>
                        <div class="metric-label">Reportable Records</div>
                        <div class="metric-value">{{ number_format($inventoryTotal) }}</div>
                        <div class="metric-note">{{ $isSchoolAdmin ? 'School scoped records' : 'Active report source records' }}</div>
                    </div>
                    <div class="metric-icon icon-green"><i class="fas fa-clipboard-list"></i></div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="metric-card">
                    <div>
                        <div class="metric-label">RPCPPE / PAR</div>
                        <div class="metric-value">{{ number_format($rpcppeCount) }}</div>
                        <div class="metric-note">Serviceable items worth 50,000 and above</div>
                    </div>
                    <div class="metric-icon icon-gold"><i class="fas fa-file-invoice"></i></div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="metric-card">
                    <div>
                        <div class="metric-label">RPCSEP / ICS</div>
                        <div class="metric-value">{{ number_format($rpcsepCount) }}</div>
                        <div class="metric-note">Serviceable items below 50,000</div>
                    </div>
                    <div class="metric-icon icon-blue"><i class="fas fa-receipt"></i></div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="metric-card">
                    <div>
                        <div class="metric-label">Unserviceable</div>
                        <div class="metric-value">{{ number_format($unserviceableCount) }}</div>
                        <div class="metric-note">Records marked unserviceable</div>
                    </div>
                    <div class="metric-icon icon-gray"><i class="fas fa-triangle-exclamation"></i></div>
                </div>
            </div>
        </div>
    @endif

    <div class="row">
        <div class="col-xl-8">
            <div class="card dashboard-card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col">
                            <h3 class="card-title">Office Report Readiness</h3><br>
                            <div class="card-subtitle">{{ number_format($extensionSchoolTotal) }} reportable records grouped by active report classification</div>
                        </div>
                        <div class="col-auto">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-frame">
                        <canvas id="sales-chart"
                                data-labels='@json($schoolReportLabels)'
                                data-rpcppe='@json($schoolReportRpcppe)'
                                data-rpcsep='@json($schoolReportRpcsep)'
                                data-unserviceable='@json($schoolReportUnserviceable)'>
                        </canvas>
                    </div>
                    <ul class="legend-list">
                        <li><span class="legend-dot" style="background:#d99a00"></span> RPCPPE / PAR</li>
                        <li><span class="legend-dot" style="background:#137a4b"></span> RPCSEP / ICS</li>
                        <li><span class="legend-dot" style="background:#b24a3b"></span> Unserviceable</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card dashboard-card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col">
                            <h3 class="card-title">{{ $isSchoolAdmin || $isTechnician ? 'School Summary' : 'School Report Readiness' }}</h3><br>
                            <div class="card-subtitle">{{ number_format($mainSchoolTotal) }} reportable records from school offices</div>
                        </div>
                        <div class="col-auto">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-frame chart-frame-sm">
                        <canvas id="sales-chartMain"
                                data-labels='@json($mainReportLabels)'
                                data-rpcppe='@json($mainReportRpcppe)'
                                data-rpcsep='@json($mainReportRpcsep)'
                                data-unserviceable='@json($mainReportUnserviceable)'>
                        </canvas>
                    </div>
                    <ul class="legend-list">
                        <li><span class="legend-dot" style="background:#d99a00"></span> RPCPPE / PAR</li>
                        <li><span class="legend-dot" style="background:#137a4b"></span> RPCSEP / ICS</li>
                        <li><span class="legend-dot" style="background:#b24a3b"></span> Unserviceable</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-4">
            <div class="card dashboard-card">
                <div class="card-header">
                    <h3 class="card-title">Report Composition</h3><br>
                    <div class="card-subtitle">{{ number_format($inventoryTotal) }} active reportable records</div>
                </div>
                <div class="card-body">
                    <div class="chart-frame chart-frame-sm">
                        <canvas id="pieChart"
                                data-rpcppe="{{ $rpcppeCount }}"
                                data-rpcsep="{{ $rpcsepCount }}"
                                data-unserviceable="{{ $unserviceableCount }}">
                        </canvas>
                    </div>

                    <div class="inventory-breakdown">
                        @foreach($propertyBreakdown as $item)
                            @php
                                $percentage = $inventoryTotal > 0 ? round(($item['value'] / $inventoryTotal) * 100) : 0;
                            @endphp
                            <div class="breakdown-row">
                                <div class="breakdown-label">{{ $item['label'] }}</div>
                                <div class="breakdown-value">{{ number_format($item['value']) }} <span class="text-muted">({{ $percentage }}%)</span></div>
                                <div class="breakdown-track">
                                    <div class="breakdown-bar" style="width: {{ $percentage }}%; background: {{ $item['color'] }}"></div>
                                </div>
                                <div class="text-muted small" style="grid-column: 1 / -1;">Estimated value: PHP {{ number_format($item['amount'], 2) }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card dashboard-card">
                <div class="card-header">
                    <h3 class="card-title">Operational Summary</h3><br>
                    <div class="card-subtitle">School coverage and classification health</div>
                </div>
                <div class="card-body">
                    <div class="metric-card mb-3">
                        <div>
                            <div class="metric-label">Reportable Records</div>
                            <div class="metric-value">{{ number_format($inventoryTotal) }}</div>
                            <div class="metric-note">Deleted records excluded, same as reports</div>
                        </div>
                        <div class="metric-icon icon-green"><i class="fas fa-chart-pie"></i></div>
                    </div>
                    <div class="metric-card mb-0">
                        <div>
                            <div class="metric-label">Asset Value</div>
                            <div class="metric-value">PHP {{ number_format($reportableAssetsValue, 0) }}</div>
                            <div class="metric-note">{{ number_format($reportableQuantity) }} total quantity, latest {{ $latestReportableDate }}</div>
                        </div>
                        <div class="metric-icon icon-gold"><i class="fas fa-chart-column"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card dashboard-card">
                <div class="card-header">
                    <h3 class="card-title">Quick Actions</h3><br>
                    <div class="card-subtitle">Common workflows for faster follow-through</div>
                </div>
                <div class="card-body">
                    @if($isTechnician)
                        <a href="{{ route('qr-scan') }}" class="quick-link-card">
                            <div>
                                <div class="quick-link-title">Scan Property QR</div>
                                <div class="quick-link-note">Receive, diagnose, or release repair items</div>
                            </div>
                            <i class="fas fa-arrow-right"></i>
                        </a>
                        <a href="{{ route('repairRead') }}" class="quick-link-card">
                            <div>
                                <div class="quick-link-title">Repair Records</div>
                                <div class="quick-link-note">Review repair transaction history</div>
                            </div>
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    @else
                        <a href="{{ route('inventoryRead') }}" class="quick-link-card">
                            <div>
                                <div class="quick-link-title">Open Inventory</div>
                                <div class="quick-link-note">Review and update inventory records</div>
                            </div>
                            <i class="fas fa-arrow-right"></i>
                        </a>
                        <a href="{{ route('propertiesRead', 4) }}" class="quick-link-card">
                            <div>
                                <div class="quick-link-title">Manage Properties</div>
                                <div class="quick-link-note">Add, classify, and track property items</div>
                            </div>
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    @endif
                    @if($isAdministrator || $isSupplyOfficer)
                        <a href="{{ route('reportForm') }}" class="quick-link-card">
                            <div>
                                <div class="quick-link-title">Generate Reports</div>
                                <div class="quick-link-note">Prepare printable inventory documents</div>
                            </div>
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    @endif
                    @if(!$isSupplyOfficer)
                        <a href="{{ route('user_settings') }}" class="quick-link-card">
                            <div>
                                <div class="quick-link-title">Account Settings</div>
                                <div class="quick-link-note">Update your username or password</div>
                            </div>
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

