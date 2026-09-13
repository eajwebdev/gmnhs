<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ strtoupper('PAR REPORT') }}</title>

    <style>
        body { font-family: Arial, sans-serif; }

        .header-container {
            position: relative;
            width: 100%;
            margin-bottom: 10px;
        }

        .appendix {
            position: absolute;
            top: 0;
            right: 0;
            font-weight: bold;
            font-size: 10pt;
        }

        .header-image {
            display: block;
            margin: 0 auto;
        }

        .title {
            text-align: center;
            font-weight: bold;
            font-size: 12pt;
            margin-top: 10px;
        }

        .details {
            font-weight: bold;
            font-size: 10pt;
            margin-top: 15px;
        }

        .details p { margin: 3px 0; }

        table#rpcppe {
            font-family: Arial;
            border-collapse: collapse;
            width: 100%;
            font-size: 11pt;
        }

        #rpcppe td, #rpcppe th {
            border: 1px solid #000;
            padding: 3px;
        }

        #rpcppe tr:nth-child(even) { background-color: #f2f2f2; }

        #rpcppe tr:hover { background-color: #ddd; }

        #rpcppe th {
            text-align: center;
            background: #fff;
            font-size: 10pt;
            padding: 8px;
        }

        .footer-cell { width: 32%; padding: 5px; }

        .footer-cell-text {
            font-size: 8pt;
            margin-top: 5px;
        }

        .sign { height: 80px; }

        .page-break { page-break-after: always; }

    </style>
</head>

<body>

@php
    use Carbon\Carbon;

    // ✅ SAFE DATA INIT (FIX NO ITEMS ISSUE)
    $paritems = $items ?? collect();

    $filters = $filters ?? [];
    $selected_columns = $selected_columns ?? [];

    $locationcolumn = in_array('location', $selected_columns) 
        || in_array('itemlocated', $selected_columns) ? 1 : 0;

    $showEachpageHeader = $show_eachpage_header ?? false;
    $showEachpageFooter = $show_eachpage_footer ?? false;
    $needsPagination = $showEachpageHeader || $showEachpageFooter;
    $itemsPerPage = $showEachpageFooter ? 6 : 10;
    $paginatedItems = $needsPagination && $paritems->count() > 0
        ? $paritems->values()->chunk($itemsPerPage)
        : collect([$paritems]);

    $grandTotal = 0;
    $pageNumber = 1;
@endphp

@foreach($paginatedItems as $pageItems)
@php
    $isLastPage = $loop->last;
@endphp

<!-- HEADER -->
@if($showEachpageHeader || $loop->first)
<div class="header-container">
    <p class="appendix">Appendix 71</p>
    <header>
        <img src="{{ asset('logo.png') }}" width="60%" class="header-image">
    </header>
</div>

<p class="title">PROPERTY ACKNOWLEDGEMENT RECEIPT</p>
@endif

<!-- DETAILS -->
@if($loop->first)
<div class="details">
    @if (!$paritems->isEmpty())
        <p>Entity Name: {{ $paritems[0]->office_name }}</p>
    @else
        <p>Entity Name: ___________________________________________________</p>
    @endif

    <p>Fund Cluster: __________________________________________________  PAR No.: _______________________</p>
</div>
@endif

<div class="table-responsive">
<table id="rpcppe">
    <thead>
        <tr>
            <th>No</th>
            <th>Qty</th>
            <th>Unit</th>
            <th>Description</th>
            <th>Property No.</th>

            @if($locationcolumn == 1)
                <th>Location</th>
            @endif

            <th>Date Acquired</th>
            <th>Amount</th>
        </tr>
    </thead>

    <tbody>

    @forelse ($pageItems as $paritem)

        @php
            $qty = $paritem->qty ?? 0;
            $cost = str_replace(',', '', $paritem->item_cost ?? 0);
            $itemTotal = is_numeric($cost) ? $qty * $cost : 0;
            $grandTotal += $itemTotal;
            $itemNumber = (($pageNumber - 1) * $itemsPerPage) + $loop->iteration;
        @endphp

        <tr>
            <td>{{ $itemNumber }}</td>
            <td style="text-align:center;">{{ $qty }}</td>
            <td>{{ $paritem->unit_name }}</td>

            <td>
                <b>{{ $paritem->item_name }}</b><br>
                <i>{{ $paritem->item_descrip }}</i><br>
                <b>MODEL:</b> {{ str_replace('Model:', '', $paritem->item_model ?? '') }}<br>
                <b>SN:</b> {!! str_replace(';', '<br>', $paritem->serial_number ?? '') !!}
            </td>

            <td>{{ $paritem->property_no_generated }}</td>

            @if($locationcolumn == 1)
                <td>{{ $paritem->itemlocated }}</td>
            @endif

            <td>{{ $paritem->date_acquired }}</td>

            <td style="text-align:right;">
                <b>{{ number_format($paritem->item_cost, 2) }}</b>
            </td>
        </tr>

    @empty

        <tr>
            <td colspan="{{ $locationcolumn == 1 ? 8 : 7 }}" style="text-align:center;">
                No items found
            </td>
        </tr>

    @endforelse

    <!-- GRAND TOTAL -->
    @if($isLastPage)
    <tr>
        <td></td><td></td><td></td><td></td>

        @if($locationcolumn == 1)
            <td></td>
        @endif

        <td></td>
        <td style="text-align:right;"><b>Grand Total:</b></td>
        <td style="text-align:right;"><b>{{ number_format($grandTotal, 2) }}</b></td>
    </tr>

    <tr>
        <td colspan="{{ $locationcolumn == 1 ? 4 : 3 }}" style="text-align:right;">
            <b>Supplier:</b>
        </td>
        <td colspan="4"></td>
    </tr>
    @endif

    </tbody>

    <!-- FOOTER EACH PAGE -->
    @if($showEachpageFooter)
    <tfoot>
        <tr>
            <td colspan="4" class="sign" style="text-align:center;">
                <span style="float:left;">Received by:</span><br>

                <span class="footer-cell">

                    <span class="footer-cell-sign">
                        <b>{{ strtoupper($paritems[0]->person_accnt_name ?? '') }}</b>
                    </span><br>

                    <span class="footer-cell-text">Signature Over Printed Name</span><br><br>

                    @if($paritems->isNotEmpty() && $paritems->first()->end_user_name)
                        <span class="footer-cell-sign">
                            <b>{{ strtoupper($paritems->first()->end_user_name) }}</b>
                        </span><br>
                        <span class="footer-cell-text">End User</span><br><br>
                    @endif

                    <span class="footer-cell-sign">
                        <b>{{ strtoupper($paritems[0]->office_name ?? '') }}</b>
                    </span><br>

                    <span class="footer-cell-text">Position / Office</span><br><br>

                    <span>____________________</span><br>
                    <span class="footer-cell-text">Date</span>

                </span>
            </td>

            <td colspan="4" class="sign" style="text-align:center;">
                <span style="float:left;">Issued by:</span><br>

                <span class="footer-cell">
                    <span class="footer-cell-sign"><b>BENJAMIN R. DELA TORRE</b></span><br>
                    <span class="footer-cell-text">Signature Over Printed Name</span><br><br>

                    <span class="footer-cell-sign"><b>Supply Officer / SUPPLY OFFICE</b></span><br>
                    <span class="footer-cell-text">Position / Office</span><br><br>

                    <span class="footer-cell-sign"><b>{{ \Carbon\Carbon::now()->format('M. j, Y') }}</b></span><br>
                    <span class="footer-cell-text">Date</span>
                </span>
            </td>
        </tr>
    </tfoot>
    @endif

</table>

@if(!$showEachpageFooter && $isLastPage)
<table id="rpcppe">
    <tbody>
        <tr>
            <td colspan="4" class="sign" style="text-align:center;">
                <span style="float:left;">Received by:</span><br>

                <span class="footer-cell">

                    <span class="footer-cell-sign">
                        <b>{{ strtoupper($paritems[0]->person_accnt_name ?? '') }}</b>
                    </span><br>

                    <span class="footer-cell-text">Signature Over Printed Name</span><br><br>

                    @if($paritems->isNotEmpty() && $paritems->first()->end_user_name)
                        <span class="footer-cell-sign">
                            <b>{{ strtoupper($paritems->first()->end_user_name) }}</b>
                        </span><br>
                        <span class="footer-cell-text">End User</span><br><br>
                    @endif

                    <span class="footer-cell-sign">
                        <b>{{ strtoupper($paritems[0]->office_name ?? '') }}</b>
                    </span><br>

                    <span class="footer-cell-text">Position / Office</span><br><br>

                    <span>____________________</span><br>
                    <span class="footer-cell-text">Date</span>

                </span>
            </td>

            <td colspan="4" class="sign" style="text-align:center;">
                <span style="float:left;">Issued by:</span><br>

                <span class="footer-cell">
                    <span class="footer-cell-sign"><b>BENJAMIN R. DELA TORRE</b></span><br>
                    <span class="footer-cell-text">Signature Over Printed Name</span><br><br>

                    <span class="footer-cell-sign"><b>Supply Officer / SUPPLY OFFICE</b></span><br>
                    <span class="footer-cell-text">Position / Office</span><br><br>

                    <span class="footer-cell-sign"><b>{{ \Carbon\Carbon::now()->format('M. j, Y') }}</b></span><br>
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
