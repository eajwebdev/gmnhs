<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title ?? 'PROPERTY REPORT' }}</title>

    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11.5pt;
        }

        .text-type {
            text-align: center;
            margin-top: -5px;
        }

        .text1 {
            text-align: center;
        }

        .text2 {
            text-align: center;
        }

        #table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 20px;
        }

        #table td {
            border: 1px solid #000;
            padding: 4px;
            font-weight: normal !important;
        }

        #table th {
            font-size: 11pt !important;
            border: 1px solid #000;
            padding: 4px;
            padding-bottom: 12px;
            text-align: center;
            background-color: #fff;
            font-weight: 600px !important;
            margin-top: 2px;
        }

        #table tfoot {
            padding: 8px;
        }

        #table tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        #table tr:hover {
            background-color: #ddd;
        }

        .footer-cell {
            width: 32%;
            float: left;
            text-align: left;
            padding: 5px;
        }

        .report-header {
            text-align: center;
            margin-top: -5px;
            width: 100%;
        }

        .report-header img {
            display: inline-block;
            width: 900px;
            max-width: 100%;
            height: auto;
        }

        .report-appendix {
            text-align: right;
            font-style: italic;
            font-size: 11pt;
        }

        .report-title {
            font-weight: bold;
            font-size: 13pt;
            margin-top: 4px;
        }

        .footer-cell-title {
            font-weight: bold;
        }

        .footer-cell-sign {
            margin-top: 20px;
            padding-top: 6px;
        }

        .footer-cell-text {
            margin-top: 5px;
            text-align: left;
        }

        .text-center {
            text-align: center;
        }

        .bold{
            font-weight: 800 !important;
        }
        
        .text-right {
            text-align: right;
        }
        
        .bg-light {
            background-color: #f8f9fa;
        }
        
        .bg-info {
            background-color: #d9edf7;
        }
        
        .bg-success {
            background-color: #dff0d8;
        }
        
        /* Page break handling */
        .page-break {
            page-break-after: always;
        }
        
        /* Subtotal row styling */
        .subtotal-row {
            background-color: #f0f0f0 !important;
            font-weight: bold;
        }
        
        .subtotal-row td, .subtotal-row th {
            border-top: 2px solid #000 !important;
            border-bottom: 2px solid #000 !important;
        }
    </style>
</head>
<body>

@php
    use Carbon\Carbon;

    // Initialize all variables with defaults
    $originalPurchase = $items ?? collect();
    $purchase = $originalPurchase; // Keep reference to original for header info
    $filters = $filters ?? [];
    $selected_columns = $selected_columns ?? [];
    $locationcolumn = in_array('location', $selected_columns) || in_array('itemlocated', $selected_columns) ? 1 : 0;
    $serial = in_array('serial_number', $selected_columns) || in_array('serial', $selected_columns) ? 1 : 0;
    $acquired = in_array('date_acquired', $selected_columns) ? 1 : 0;
    $categoriesLabel = $filters['Category'] ?? 'All';
    // Set by the controller only when a specific value was picked (never for "All")
    $accountTitleLabel = $filters['Account Title'] ?? '';
    $schoolOfficeLabel = $filters['School or Office'] ?? '';
    $locationLabel = $filters['Location'] ?? '';

    // Check options from form
    $showBalanceForward = $show_balance_forward ?? false;
    $showGrandTotal = $show_grand_total ?? false;
    $showEachpageSubtotal = $show_eachpage_subtotal ?? false;
    $showEachpageHeader = $show_eachpage_header ?? false;
    $showEachpageFooter = $show_eachpage_footer ?? false;
    
    // Use the formatted date range from controller if available
    $displayDate = $date_range_display ?? '';
    
    // Fallback to raw date range or current date if needed
    if (empty($displayDate)) {
        $rawDateRange = $filters['Date Range'] ?? '';
        if (($has_date_filter ?? false) && !empty($rawDateRange)) {
            // Try to format the raw date range
            try {
                if (str_contains($rawDateRange, 'to') || str_contains($rawDateRange, '-')) {
                    $parts = preg_split('/\s*(to|-)\s*/', $rawDateRange);
                    if (count($parts) === 2) {
                        $start = Carbon::parse(trim($parts[0]));
                        $end = Carbon::parse(trim($parts[1]));
                        if ($start->isSameMonth($end) && $start->isSameYear($end)) {
                            $displayDate = strtoupper($start->format('M j') . ' - ' . $end->format('j, Y'));
                        } elseif ($start->isSameYear($end)) {
                            $displayDate = strtoupper($start->format('M j') . ' - ' . $end->format('M j, Y'));
                        } else {
                            $displayDate = strtoupper($start->format('M j, Y') . ' - ' . $end->format('M j, Y'));
                        }
                    } else {
                        $displayDate = strtoupper($rawDateRange);
                    }
                } else {
                    $displayDate = strtoupper(Carbon::parse($rawDateRange)->format('M j, Y'));
                }
            } catch (\Exception $e) {
                $displayDate = strtoupper($rawDateRange);
            }
        } else {
            $displayDate = strtoupper(Carbon::now()->format('M j, Y'));
        }
    }
    
    $bforward = $bforward ?? 0;
    $totalCost = $total_cost ?? 0;
    $grandTotal = $grand_total ?? 0;
    $hasDateFilter = $has_date_filter ?? false;
    
    // Calculate column span
    $baseColumns = 12;
    $extraColumns = $locationcolumn + $serial + $acquired;
    $totalColumns = $baseColumns + $extraColumns;
    
    // DYNAMIC PAGINATION LOGIC: paginate whenever a per-page option needs
    // predictable PDF pages instead of relying on DomPDF's automatic table split.
    $needsPagination = $showEachpageSubtotal || $showEachpageHeader || $showEachpageFooter;
    $paginatedItems = collect();
    
    if ($needsPagination && $originalPurchase->count() > 0) {
        $itemsArray = $originalPurchase->values()->toArray();
        $totalItems = count($itemsArray);
        $offset = 0;
        $pageNum = 1;
        
        while ($offset < $totalItems) {
            if ($pageNum == 1) {
                // First page: 9 items
                $itemsPerPage = 9;
                if($showEachpageFooter){
                    $itemsPerPage = 9;
                }else{
                    $itemsPerPage = 13;
                }
            } else {
                // Check if remaining items will fit in last page (up to 12)
                $remainingItems = $totalItems - $offset;
                if ($remainingItems <= 12) {
                    // Last page: up to 12 items
                    if($showEachpageFooter){
                        $itemsPerPage = $remainingItems;
                    }else{
                        $itemsPerPage = $remainingItems + 2;
                    }
                } else {
                    // Middle page: 13 items
                    if($showEachpageFooter){
                        $itemsPerPage = 12;
                    }else{
                        $itemsPerPage = 18;
                    }
                }
            }
            
            $pageItems = array_slice($itemsArray, $offset, $itemsPerPage);
            $paginatedItems->push(collect($pageItems));
            $offset += $itemsPerPage;
            $pageNum++;
        }
    } else {
        $paginatedItems = collect([$originalPurchase]);
    }
    
    $pageNumber = 1;
    $totalPages = $needsPagination ? $paginatedItems->count() : 1;
@endphp

@foreach($paginatedItems as $pageItems)
    @php
        $pageSubtotal = 0;
    @endphp

    @php
        $showHeaderOnThisPage = $showEachpageHeader || $loop->first;
    @endphp

    {{-- Header Each Page checked: every page. Unchecked: first page only. --}}
    @if($showHeaderOnThisPage)
        <header class="report-header">
            <div class="report-appendix">Appendix 73</div>
            <img src="{{ asset('logo.png') }}">
            <div class="report-title">REPORT ON THE PHYSICAL COUNT OF PROPERTY, PLANT AND EQUIPMENT</div>
        </header>
    @endif

    {{-- Main Report Information - only on first page --}}
    @if($loop->first)
    <div class="text1">{{ $originalPurchase->first()->office_name ?? ''; }}</div>
    <div class="text-type">
        @if ($categoriesLabel === 'All' || empty($categoriesLabel))
            @if ($categoriesLabel === 'All')
                <u>ALL CATEGORIES</u>
            @else
                <u>{{ isset($originalPurchase->first()->cat_code) ? $originalPurchase->first()->cat_code : '' }}</u>
            @endif
        @elseif ($originalPurchase->isEmpty())
            ____________________
        @else
            <u>{{ $categoriesLabel }}</u>
        @endif
    </div>
    <div class="text1">(Type of Property, Plant and Equipment)</div>
    {{-- Each line only shows when that filter was narrowed; the controller omits it for "All". --}}
    @if(!empty($accountTitleLabel))
    <div class="text2">Account Title: <u>{{ $accountTitleLabel }}</u></div>
    @endif
    @if(!empty($schoolOfficeLabel))
    <div class="text2"><u>{{ $schoolOfficeLabel }}</u></div>
    @endif
    @if(!empty($locationLabel))
    <div class="text2"><u>{{ $locationLabel }}</u></div>
    @endif
    <div class="text2">As at <u>{{ $displayDate }}</u>.</div>
    <div class="text3">Fund Cluster : ________________________________</div>

    <div class="text4">
        For which 
        <u>ALEXANDRA M. VILLAROSA, Gil Montilla National High School, Sipalay City</u>
        of <u>GIL MONTILLA NATIONAL HIGH SCHOOL</u>,
        is accountable, having assumed such accountability on August 16, 2018.
    </div>
    @endif

    <div class="table-responsive">
        <table id="table" class="table table-bordered">
            <thead>
                <tr style="padding: 2px">
                    <th rowspan="2" width="200">ARTICLE</th>
                    <th rowspan="2" width="330">DESCRIPTION</th>
                    <th rowspan="2" width="200">PROPERTY NO.</th>
                    <th rowspan="2" width="50">UNIT <br>OF MEASURE</th>
                    <th rowspan="2" width="50">UNIT VALUE</th>
                    <th rowspan="2" width="50">QUANTITY <br>PER<br> PROPERTY CARD</th>
                    <th rowspan="2" width="50">Total Cost</th>
                    <th rowspan="2" width="50">QUANTITY <br>PER<br> PHYSICAL COUNT</th>
                    <th colspan="2" width="150">SHORTAGE OVERAGE</th>
                    <th rowspan="2" width="50">REMARKS</th>
                    <th rowspan="2" width="100">Whereabout</th>

                    @if($locationcolumn == 1)
                        <th rowspan="2">LOCATION</th>
                    @endif

                    @if($serial == 1)
                        <th rowspan="2">SERIAL</th>
                    @endif

                    @if($acquired == 1)
                        <th rowspan="2">DATE ACQUIRED</th>
                    @endif
                </tr>
                <tr style="padding: 2px">
                    <th>Quantity</th>
                    <th>Value</th>
                </tr>
            </thead>

            <!-- Balance Brought Forward Row - Only show if checkbox is checked on first page -->
            @if($loop->first && $showBalanceForward && $hasDateFilter)
            <tr class="bg-light">
                <th colspan="6" class="bold" style="text-align: right;">
                    Balance Brought Forward:
                </th>
                <th style="text-align: right; font-weight: bold;">
                    {{ number_format($bforward ?? 0, 2) }}
                </th>
                <th colspan="{{ max(1, $totalColumns - 7) }}" style="text-align: left;">
                    {{-- <em>Total value of items acquired before {{ $displayDate }}</em> --}}
                </th>
            </tr>
            @endif

            <tbody>
                @if ($originalPurchase->isEmpty())
                    <tr>
                        <td colspan="{{ $totalColumns }}" style="text-align:center;">
                            No data available for the selected filters.
                        </td>
                    </tr>
                @else
                    @foreach ($pageItems as $purchaseData)
                        @php
                            // item_cost/qty are varchar and some rows carry thousands
                            // separators, so parse before multiplying.
                            $quantity = \App\Http\Controllers\ReportsController::toAmount(data_get($purchaseData, 'quantity') ?? data_get($purchaseData, 'qty') ?? 1);
                            $unitCost = \App\Http\Controllers\ReportsController::toAmount(data_get($purchaseData, 'item_cost') ?? 0);
                            $itemTotalCost = $quantity * $unitCost;
                            $pageSubtotal += $itemTotalCost;
                        @endphp
                        <tr>
                            <td>{{ strtoupper(data_get($purchaseData, 'item_name') ?? '') }}</td>

                            <td class="text-left">
                                {{ strtoupper(data_get($purchaseData, 'item_descrip') ?? '') }}
                            </td>

                            <td class="text-center">
                                {{ strtoupper(
                                    data_get($purchaseData, 'property_no_generated')
                                    ?? data_get($purchaseData, 'property_no')
                                    ?? ''
                                ) }}
                            </td>

                            <td class="text-center">
                                {{ strtoupper(
                                    data_get($purchaseData, 'unit_of_measure')
                                    ?? data_get($purchaseData, 'unit_name')
                                    ?? ''
                                ) }}
                            </td>
                            <td class="text-right">{{ number_format($unitCost, 2) }}</td>
                            <td class="text-center">{{ $quantity }}</td>
                            <td class="text-right">{{ number_format($itemTotalCost, 2) }}</td>
                            <td class="text-center">{{ $quantity }}</td>
                            <td class="text-center">{{ $quantity }}</td>
                            <td class="text-right">{{ number_format($itemTotalCost, 2) }}</td>
                            <td class="text-center">{{ strtoupper(data_get($purchaseData, 'remarks') ?? '') }}</td>
                            <td class="text-center">{{ data_get($purchaseData, 'office_name') ?? data_get($purchaseData, 'whereabout') ?? '' }}</td>

                            @if($locationcolumn == 1)
                                <td class="text-center">{{ data_get($purchaseData, 'itemlocated') ?? '' }}</td>
                            @endif

                            @if($serial == 1)
                                <td class="text-center">{{ data_get($purchaseData, 'serial_number') ?? '' }}</td>
                            @endif

                            @if($acquired == 1)
                                <td class="text-center">
                                    @php
                                        $dateAcquired = data_get($purchaseData, 'date_acquired') ?? '';
                                    @endphp
                                    {{ !empty($dateAcquired) ? strtoupper(Carbon::parse($dateAcquired)->format('M j, Y')) : '' }}
                                </td>
                            @endif
                        </tr>
                    @endforeach

                    {{-- Page Subtotal Row - Show on each page ONLY if checkbox is checked --}}
                    @if($showEachpageSubtotal && $needsPagination)
                    <tr class="subtotal-row">
                        <th colspan="6" class="bold" style="text-align: right;">
                            SUBTOTAL:
                        </th>
                        <th style="text-align: right;">
                            {{ number_format($pageSubtotal, 2) }}
                        </th>
                        <th colspan="{{ max(1, $totalColumns - 7) }}" style="text-align: left;">
                            {{-- <em>Page subtotal</em> --}}
                        </th>
                    </tr>
                    @endif

                    <!-- TOTAL Row - Only show on last page -->
                    @if($loop->last)
                    <tr>
                        <th colspan="6" class="bold" style="text-align: right;">
                            TOTAL:
                        </th>
                        <th style="text-align: right;">
                            {{ number_format($totalCost, 2) }}
                        </th>
                        <th colspan="5"></th>
                    </tr>

                    <!-- GRAND TOTAL Row - Only show if checkbox is checked -->
                    @if($showGrandTotal)
                        @if($hasDateFilter && $bforward > 0 && $showBalanceForward)
                        <tr>
                            <th colspan="6" class="bold" style="text-align: right;">
                                GRAND TOTAL:
                            </th>
                            <th style="text-align: right;">
                                {{ number_format($grandTotal, 2) }}
                            </th>
                            <th colspan="5"></th>
                        </tr>
                        @else
                        <tr>
                            <th colspan="6" class="bold" style="text-align: right;">
                                GRAND TOTAL:
                            </th>
                            <th style="text-align: right;">
                                {{ number_format($totalCost, 2) }}
                            </th>
                            <th colspan="5"></th>
                        </tr>
                        @endif
                    @endif
                    @endif
                @endif
            </tbody>

            {{-- Footer Each Page checked: every page. --}}
            @if($showEachpageFooter)
                <tfoot>
                    <tr>
                        <td colspan="{{ $totalColumns }}" class="sign">
                            <div class="footer-cell">
                                <div>Certified Correct by:</div>
                                <div class="footer-cell-sign bold">BENJAMIN R. DELA TORRE</div>
                                <div class="footer-cell-text">Administrative Officer / Supply Officer</div>
                            </div>

                            <div class="footer-cell">
                                <div>Approved by:</div>
                                <div class="footer-cell-sign bold">ALEXANDRA M. VILLAROSA</div>
                                <div class="footer-cell-text">School Principal</div>
                            </div>

                            <div class="footer-cell">
                                <div>Verified by</div>
                                <div class="footer-cell-sign bold">CAMILLE A. NAVARRETE</div>
                                <div class="footer-cell-text">Internal Audit Representative</div>
                            </div>
                        </td>
                    </tr>
                </tfoot>
            @endif
        </table>

        {{-- Footer Each Page unchecked: only once, after the final table. --}}
        @if(!$showEachpageFooter && $loop->last)
            <table id="table" class="table table-bordered">
                <tbody>
                    <tr>
                        <td colspan="{{ $totalColumns }}" class="sign">
                            <div class="footer-cell">
                                <div>Certified Correct by:</div>
                                <div class="footer-cell-sign bold">BENJAMIN R. DELA TORRE</div>
                                <div class="footer-cell-text">Administrative Officer / Supply Officer</div>
                            </div>

                            <div class="footer-cell">
                                <div>Approved by:</div>
                                <div class="footer-cell-sign bold">ALEXANDRA M. VILLAROSA</div>
                                <div class="footer-cell-text">School Principal</div>
                            </div>

                            <div class="footer-cell">
                                <div>Verified by</div>
                                <div class="footer-cell-sign bold">CAMILLE A. NAVARRETE</div>
                                <div class="footer-cell-text">Internal Audit Representative</div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        @endif
    </div>

    @if($needsPagination && !$loop->last)
    <div class="page-break"></div>
    @endif
    
    @php $pageNumber++; @endphp
@endforeach

</body>
</html>

