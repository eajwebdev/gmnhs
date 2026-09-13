<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title ?? 'PROPERTY REPORT' }}</title>
    <style>
        /*.table-responsive {
            overflow-x: auto;
            max-width: 100%; 
        }*/
        .text-type {
            text-align: center;
            margin-top: -5px;
        }
        .header-container {
            position: relative;
            width: 100%;
            margin-bottom: 10px;
        }
        .header-image {
            display: block;
            margin: 0 auto;
        }
        .text1 {
            text-align: center;
        }
        .text2 {
            text-align: center;
        }
        .text3 {
            font-size: 11pt;
            margin-top: 10px;
        }
        .text4 {
            font-size: 11pt;
        }
        #rpcppe {
            font-family: Arial;
            border-collapse: collapse;
            width: 100%;
            font-size: 11pt;
        }
        #rpcppe td {
            border: 1px solid #000;
            padding: 3px;
        } 
        #rpcppe th {
            border: 1px solid #000;
            /*padding: 8px;*/
        }
        .icsno{
            text-align: right !important;
            font-size: 8pt;
        }
        .text-total {
            font-size: 12pt !important;
        }
        #rpcppe tfoot {
            border: 1px solid #000;
            padding: 8px;
        }
        #rpcppe tr:nth-child(even){background-color: #f2f2f2;}
        #rpcppe tr:hover {background-color: #ddd;}
        #rpcppe th {
            padding-top: 12px;
            padding-bottom: 12px;
            text-align: center;
            background-color: #fff;
            font-size: 10pt;
        }
        .footer-cell {
            width: 32%;
            padding: 5px; 
        }
        .footer-cell-title {
            font-weight: bold;
        }
        .footer-cell-sign {
            margin-top: 20px;
        }
        .footer-cell-text {
            font-size: 8pt;
            margin-top: 5px;
        }
        .sign {
            height: 80px;
        }
        .text-receivedby {
            font-size: 8pt;
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

    // Initialize all variables with defaults (same as previous report)
    $purchase = $items ?? collect();
    $filters = $filters ?? [];
    $selected_columns = $selected_columns ?? [];
    $locationcolumn = in_array('location', $selected_columns) || in_array('itemlocated', $selected_columns) ? 1 : 0;
    $serial = in_array('serial_number', $selected_columns) || in_array('serial', $selected_columns) ? 1 : 0;
    $acquired = in_array('date_acquired', $selected_columns) ? 1 : 0;
    $categoriesLabel = $filters['Category'] ?? 'All';
    
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
    // predictable PDF pages instead of relying on the PDF engine's automatic table split.
    $needsPagination = $showEachpageSubtotal || $showEachpageHeader || $showEachpageFooter;
    $originalPurchase = $purchase; // Keep reference to original for header info
    $paginatedItems = collect();
    
    if ($needsPagination && $originalPurchase->count() > 0) {
        $itemsArray = $originalPurchase->values()->toArray();
        $totalItems = count($itemsArray);
        $offset = 0;
        $pageNum = 1;
        
        while ($offset < $totalItems) {
            if ($pageNum == 1) {
                // First page: 14 items
                $itemsPerPage = 14;
            } else {
                // Check if remaining items will fit in last page (up to 12)
                $remainingItems = $totalItems - $offset;
                if ($remainingItems <= 12) {
                    // Last page: up to 12 items
                    $itemsPerPage = $remainingItems;
                } else {
                    // Middle page: 13 items
                    $itemsPerPage = 14;
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
        $rowCount = 0;
        $no = ($pageNumber - 1) * 13 + 1;
        if ($pageNumber == 1) $no = 1;
    @endphp

    {{-- Header Each Page checked: every page. Unchecked: first page only. --}}
    @if($showEachpageHeader || $loop->first)
    <header class="header-container"> 
        <img src="{{ asset('logo.png') }}" width="60%" class="header-image">
        <table id="rpcppe" class="table table-bordered">
            <thead>
                <tr>
                    <th colspan="8"><h3>INVENTORY CUSTODIAN SLIP</h3><p class="icsno" style="margin-bottom: -6px;">ICS No. _________________</p></th>
                </tr>
            </thead>
        </table>
    </header>
    @endif
    <div class="table-responsive">
        <table id="rpcppe" class="table table-bordered">
            <thead>
                <tr>
                    <th>NO</th>
                    <th>Qty</th>
                    <th>Unit</th>
                    <th>Description</th>
                    <th>Unit Cost</th>
                    <th>Total Cost</th>
                    <th>Date Acquired</th>
                    <th>Inventory Item No.</th>
                    @if($locationcolumn == 1)
                        <th class="">Location</th>
                    @endif
                    <th width="30">Estimated Useful Life</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($pageItems as $icsitem)
                    <tr>
                        <td>{{ $no++ }}</td>
                        <td>{{ data_get($icsitem, 'qty') }}</td>
                        <td>{{ data_get($icsitem, 'unit_name') }}</td>
                        <td>
                            <b>{{ data_get($icsitem, 'item_name') }}</b>
                            <br><i> {{ data_get($icsitem, 'item_descrip') }}</i><br>
                            <b>MODEL:</b>{{ data_get($icsitem, 'item_model') ? str_replace('Model:', '', data_get($icsitem, 'item_model')) : '' }}<br>
                            <b>SN : </b> <span style="font-size: 12px;">{!! str_replace(';', '<br>', data_get($icsitem, 'serial_number')) !!}</span>
                        </td>
                        <td>{{ number_format( str_replace(',', '', data_get($icsitem, 'item_cost')), 2) }}</td>
                        <td>{{ number_format( str_replace(',', '', data_get($icsitem, 'total_cost')), 2) }}</td>
                        <td>
                            @if(data_get($icsitem, 'date_acquired'))
                                {{ \Carbon\Carbon::parse(data_get($icsitem, 'date_acquired'))->format('M. j, Y') }}
                            @endif
                        </td>
                        <td>{{ data_get($icsitem, 'property_no_generated') }}</td>
                        @if($locationcolumn == 1)
                            <td>{{ data_get($icsitem, 'itemlocated') }}</td>
                        @endif
                        <td>
                            @php 
                                $itemCost = str_replace(',', '', data_get($icsitem, 'item_cost'));
                                $qty = data_get($icsitem, 'qty');
                                if (is_numeric($itemCost)) {
                                    $itemTotal = $qty * $itemCost;
                                    $pageSubtotal += $itemTotal;
                                }
                            @endphp
                        </td>
                    </tr>
                    @if (is_numeric(str_replace(',', '', data_get($icsitem, 'item_cost'))))
                        @php $rowCount++; @endphp
                    @endif
                @endforeach

                {{-- Page Subtotal Row - Show on each page ONLY if checkbox is checked --}}
                @if($showEachpageSubtotal && $needsPagination)
                <tr class="subtotal-row">
                    <th colspan="{{ $locationcolumn == 1 ? 5 : 4 }}" style="text-align: right"><b class="text-total">SUBTOTAL:</b></th>
                    <th colspan="4" style="text-align: left"><b class="text-total">{{ number_format($pageSubtotal, 2) }}</b></th>
                    <th></th>
                </tr>
                @endif

                <!-- TOTAL Row - Only show on last page -->
                @if($loop->last)
                <tr>
                    <th colspan="{{ $locationcolumn == 1 ? 5 : 4 }}" style="text-align: right"><b class="text-total">TOTAL:</b></th>
                    <th colspan="4" style="text-align: left"><b class="text-total">{{ number_format($totalCost, 2) }}</b></th>
                    <th></th>
                </tr>

                <!-- GRAND TOTAL Row - Only show if checkbox is checked -->
                @if($showGrandTotal)
                    @if($hasDateFilter && $bforward > 0 && $showBalanceForward)
                    <tr>
                        <th colspan="{{ $locationcolumn == 1 ? 5 : 4 }}" style="text-align: right"><b class="text-total">GRAND TOTAL:</b></th>
                        <th colspan="4" style="text-align: left"><b class="text-total">{{ number_format($grandTotal, 2) }}</b></th>
                        <th></th>
                    </tr>
                    @else
                    <tr>
                        <th colspan="{{ $locationcolumn == 1 ? 5 : 4 }}" style="text-align: right"><b class="text-total">GRAND TOTAL:</b></th>
                        <th colspan="4" style="text-align: left"><b class="text-total">{{ number_format($totalCost, 2) }}</b></th>
                        <th></th>
                    </tr>
                    @endif
                @endif
                @endif
            </tbody>
        </table>
        
        {{-- Footer Each Page checked: every page. --}}
        @if($showEachpageFooter)
            <table id="rpcppe" class="table table-bordered">
                <tfoot>
                    <tr>
                        <td colspan="4" class="sign" style="text-align: center;">
                            <span class="text-receivedby" style="float: left">Received by:</span><br>
                            <span class="footer-cell"> 
                                {{-- ICS FOOTER - UPDATED WITH PAR LOGIC (using person_accnt_name and end_user_name) --}}
                                <span class="footer-cell-sign">
                                    <b>{{ isset($originalPurchase->first()->person_accnt_name) ? strtoupper($originalPurchase->first()->person_accnt_name) : (isset($originalPurchase->first()->person_accnt) ? strtoupper($originalPurchase->first()->person_accnt) : '') }}</b>
                                </span><br>
                                <span class="footer-cell-text">Signature Over Printed Name</span><br><br>

                                @if($originalPurchase->isNotEmpty() && ($originalPurchase->first()->end_user_name ?? false))
                                    <span class="footer-cell-sign">
                                        <b>{{ strtoupper($originalPurchase->first()->end_user_name) }}</b>
                                    </span><br>
                                    <span class="footer-cell-text">End User</span><br><br>
                                @endif
                                
                                <span class="footer-cell-sign" style="text-decoration: underline;">
                                    <b>{{ isset($originalPurchase->first()->person_accnt) ? strtoupper($originalPurchase->first()->office_name) : strtoupper($originalPurchase->first()->office_name ?? '') }}</b>
                                </span><br>
                                <span class="footer-cell-text">Position / Office</span><br><br>
                                <span class="footer-cell-sign">____________________</span><br>
                                <span class="footer-cell-text">Date</span>
                            </span>
                        </td>
                        <td colspan="4" class="sign" style="text-align: center;">
                            <span class="text-receivedby" style="float: left">Issued by:</span><br>
                            <span class="footer-cell">
                                <span class="footer-cell-sign"><u><b>BENJAMIN R. DELA TORRE</b></u></span><br>
                                <span class="footer-cell-text">Signature Over Printed Name</span><br><br>
                                <span class="footer-cell-sign" style="text-decoration: underline;">
                                    <b>Supply Officer / SUPPLY OFFICE</b>
                                </span><br>
                                <span class="footer-cell-text">Position / Office</span><br><br>
                                <span class="footer-cell-sign"><u><b>{{ \Carbon\Carbon::now()->format('M. j, Y') }}</b></u></span><br>
                                <span class="footer-cell-text">Date</span>
                            </span>
                        </td>
                    </tr>
                </tfoot>
            </table>
        @elseif($loop->last)
            {{-- Footer Each Page unchecked: only once, after the final table. --}}
            <table id="rpcppe" class="table table-bordered">
                <tbody>
                    <tr>
                        <td colspan="4" class="sign" style="text-align: center;">
                            <span class="text-receivedby" style="float: left">Received by:</span><br>
                            <span class="footer-cell"> 
                                {{-- ICS FOOTER - UPDATED WITH PAR LOGIC (using person_accnt_name and end_user_name) --}}
                                <span class="footer-cell-sign">
                                    <b>{{ isset($originalPurchase->first()->person_accnt_name) ? strtoupper($originalPurchase->first()->person_accnt_name) : (isset($originalPurchase->first()->person_accnt) ? strtoupper($originalPurchase->first()->person_accnt) : '') }}</b>
                                </span><br>
                                <span class="footer-cell-text">Signature Over Printed Name</span><br><br>

                                @if($originalPurchase->isNotEmpty() && ($originalPurchase->first()->end_user_name ?? false))
                                    <span class="footer-cell-sign">
                                        <b>{{ strtoupper($originalPurchase->first()->end_user_name) }}</b>
                                    </span><br>
                                    <span class="footer-cell-text">End User</span><br><br>
                                @endif
                                
                                <span class="footer-cell-sign" style="text-decoration: underline;">
                                    <b>{{ isset($originalPurchase->first()->person_accnt) ? strtoupper($originalPurchase->first()->office_name) : strtoupper($originalPurchase->first()->office_name ?? '') }}</b>
                                </span><br>
                                <span class="footer-cell-text">Position / Office</span><br><br>
                                <span class="footer-cell-sign">____________________</span><br>
                                <span class="footer-cell-text">Date</span>
                            </span>
                        </td>
                        <td colspan="4" class="sign" style="text-align: center;">
                            <span class="text-receivedby" style="float: left">Issued by:</span><br>
                            <span class="footer-cell">
                                <span class="footer-cell-sign"><u><b>BENJAMIN R. DELA TORRE</b></u></span><br>
                                <span class="footer-cell-text">Signature Over Printed Name</span><br><br>
                                <span class="footer-cell-sign" style="text-decoration: underline;">
                                    <b>Supply Officer / SUPPLY OFFICE</b>
                                </span><br>
                                <span class="footer-cell-text">Position / Office</span><br><br>
                                <span class="footer-cell-sign"><u><b>{{ \Carbon\Carbon::now()->format('M. j, Y') }}</b></u></span><br>
                                <span class="footer-cell-text">Date</span>
                            </span>
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