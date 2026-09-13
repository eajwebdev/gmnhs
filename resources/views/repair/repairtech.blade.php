@extends('layouts.master')

@section('body')
@php
$cr = request()->route()->getName();
@endphp

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                    </h3>
                </div>
                <div class="card-body">
                    <table id="example1" class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th class="align-middle">Property number</th>
                                <th class="align-middle">Findings</th>
                                <th class="align-middle">Urgency</th>
                                <th class="align-middle">Date Received</th>
                                <th class="align-middle">Date Diagnosed</th>
                                <th class="align-middle">Date Released</th>
                                <th class="align-middle">DB Diagnose</th>
                                <th class="align-middle">DB Release</th>
                                <th class="align-middle">DB Diagnosis</th>
                                <th class="align-middle">Repair Status</th>
                                <th class="align-middle">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($repairs as $repair)
                            <tr id="tr-{{ $repair->rpid }}">
                                <td class="align-middle">{{ $repair->property_no_generated }}</td>
                                <td class="align-middle">{{ $repair->findings }}</td>

                                <!-- Urgency Badge -->
                                <td class="align-middle">
                                    @switch($repair->urgency)
                                        @case('Low')
                                            <span class="badge bg-success">Low Priority</span>
                                            @break
                                        @case('Medium')
                                            <span class="badge bg-info text-dark">Medium Priority</span>
                                            @break
                                        @case('High')
                                            <span class="badge bg-warning text-dark">High Priority</span>
                                            @break
                                        @case('Urgent')
                                            <span class="badge bg-danger">Urgent / Critical</span>
                                            @break
                                        @default
                                            <span class="badge bg-secondary">{{ $repair->urgency }}</span>
                                    @endswitch
                                </td>

                                <td class="align-middle">
                                    @php 
                                        $formattedDate = strtoupper($repair->created_at->format('M')) . '. ' . $repair->created_at->format('d, Y g:i A');
                                    @endphp
                                    {{ $formattedDate }}<br>
                                    <b class="text-info">{{ $repair->received_by_name }}</b>
                                </td>
                                <td class="align-middle">
                                    @if($repair->date_diagnose)
                                        @php 
                                            $formattedDiagnoseDate = strtoupper($repair->date_diagnose->format('M')) . '. ' . $repair->date_diagnose->format('d, Y g:i A');
                                        @endphp
                                        {{ $formattedDiagnoseDate }}
                                    @else
                                        N/A
                                    @endif <br>
                                    <b class="text-info">{{ $repair->diagnose_by_name }}</b>
                                </td>
                                <td class="align-middle">
                                    @if($repair->release_date)
                                        @php 
                                            $formattedReleaseDate = strtoupper($repair->release_date->format('M')) . '. ' . $repair->release_date->format('d, Y g:i A');
                                        @endphp
                                        {{ $formattedReleaseDate }}
                                    @else
                                        N/A
                                    @endif<br>
                                    <b class="text-info">{{ $repair->release_by_name }}</b>
                                </td>
                                <td class="align-middle">
                                    @if($repair->date_diagnose)
                                        @php $totalDaysDiagnose = $repair->created_at->diffInDays($repair->date_diagnose); @endphp
                                        {{ $totalDaysDiagnose }} {{ $totalDaysDiagnose == 1 ? 'day' : 'days' }}
                                    @else
                                        @php $totalDaysDiagnose = $repair->created_at->diffInDays(now()); @endphp
                                        {{ $totalDaysDiagnose }} {{ $totalDaysDiagnose == 1 ? 'day' : 'days' }}
                                    @endif
                                </td>
                                <td class="align-middle">
                                    @if($repair->release_date)
                                        @php $totalDaysRelease = $repair->created_at->diffInDays($repair->release_date); @endphp
                                        {{ $totalDaysRelease }} {{ $totalDaysRelease == 1 ? 'day' : 'days' }}
                                    @else
                                        @php $totalDaysRelease = $repair->created_at->diffInDays(now()); @endphp
                                        {{ $totalDaysRelease }} {{ $totalDaysRelease == 1 ? 'day' : 'days' }}
                                    @endif
                                </td>

                                <td class="align-middle">{{ $repair->diagnosis }}</td>

                                <!-- Repair Status Badge -->
                                <td class="align-middle">
                                    @if($repair->repair_status == 1)
                                        <span class="badge bg-warning">Pending</span>

                                    @elseif($repair->repair_status == 2)
                                        <span class="badge bg-success">For Further Evaluation</span>

                                    @elseif($repair->repair_status == 3)
                                        <span class="badge bg-info text-dark">For Replacement</span>

                                    @elseif($repair->repair_status == 4)
                                        <span class="badge bg-primary">For Repair</span>

                                    @elseif($repair->repair_status == 5)
                                        <span class="badge bg-secondary">For Job Out</span>
                                    @elseif($repair->repair_status == 6)
                                        <span class="badge bg-danger">Unserviceable</span>
                                    @endif
                                </td>
                                <td class="text-center align-middle">
                                    <a href="{{ route('repairPDF', ['id' => $repair->rpid]) }}" target="_blank" class="btn btn-danger btn-xs" data-id="{{ $repair->rpid }}">
                                        <i class="fas fa-file-pdf"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection