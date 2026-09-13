
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ strtoupper('ICS REPORT ' . $datereport) }}</title>
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
    </style>
</head>
<body>
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
                @php
                    $rowCount = 0;
                    $overallTotal = 0;
                    $grandTotal = 0;
                    $no = 1;
                @endphp
                 @foreach ($icsitems as $icsitem)
                    <tr>
                        <td>{{ $no++ }}</td>
                        <td>{{ $icsitem->qty }}</td>
                        <td>{{ $icsitem->unit_name }}</td>
                        <td>
                            <b>{{ $icsitem->item_name }}</b>
                            <br><i> {{ $icsitem->item_descrip }}</i><br>
                            <b>MODEL:</b>{{ $icsitem->item_model ? str_replace('Model:', '', $icsitem->item_model) : '' }}<br>
                            <b>SN : </b> <span style="font-size: 12px;">{!!  str_replace(';', '<br>', $icsitem->serial_number) !!}</span>
                        </td>
                        <td>{{ number_format( str_replace(',', '', $icsitem->item_cost), 2) }}</td>
                        <td>{{ number_format( str_replace(',', '', $icsitem->total_cost), 2) }}</td>
                        <td>
                            @if($icsitem->date_acquired)
                                {{ \Carbon\Carbon::parse($icsitem->date_acquired)->format('M. j, Y') }}
                            @endif
                        </td>
                        <td>{{ $icsitem->property_no_generated }}</td>
                        @if($locationcolumn == 1)
                            <td>{{ $icsitem->itemlocated }}</td>
                        @endif
                        <td></td>
                            @if (is_numeric(str_replace(',', '', $icsitem->item_cost)))
                            {{-- @php $overallTotal += str_replace(',', '', $icsitem->item_cost); @endphp --}}
                            @php 
                                $itemTotal = $icsitem->qty * str_replace(',', '', $icsitem->item_cost);
                                $overallTotal += $itemTotal;
                                $grandTotal += $itemTotal; // Add to grand total
                            @endphp
                        @endif
                    </tr>
                    @if (is_numeric(str_replace(',', '', $icsitem->item_cost)))
                        @php $rowCount++; @endphp
                    @endif
                @endforeach
                <tr>
                    <td height="13"></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    @if($locationcolumn == 1)
						<td></td>
					@endif
                    <td></td>
                </tr>
                <tr>
                    <td height="13"></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    @if($locationcolumn == 1)
						<td></td>
					@endif
                    <td></td>
                </tr>
                <tr>
                    <th colspan="{{ $locationcolumn == 1 ? 5 : 4 }}" style="text-align: right"><b class="text-total">Total:</b></th>
                    <th colspan="4" style="text-align: left"><b class="text-total">{{ number_format($grandTotal, 2) }}</b></th>
                    <th></th>
                </tr>
            </tbody>
        </table>
        <table id="rpcppe" class="table table-bordered">
        <tfoot>
            <tr>
                <td colspan="4" class="sign" style="text-align: center;">
                    <span class="text-receivedby" style="float: left">Received by:</span><br>
                     <span class="footer-cell"> 
                        <span class="footer-cell-sign" style="text-decoration: underline;">
                            @if($pAccountable == 'officeAccountable')
                                <b>{{ isset($icsitems->first()->office_officer) ? strtoupper($icsitems->first()->office_officer) : '' }}
                            @else
                                <b>{{ isset($icsitems->first()->person_accnt_name) ? strtoupper($icsitems->first()->person_accnt_name) : '' }}
                            @endif          
                        </span><br>                                                                                                                                                                                                                              
                        <span class="footer-cell-text">Signature Over Printed Name</span><br><br>

                        @if($pAccountable2 != 0)
                            <span class="footer-cell-sign" style="text-decoration: underline;">
                                <b>{{ strtoupper($pAccountable2) }}</b>
                            </span><br>                                                                                                                                                                                                                              
                            <span class="footer-cell-text">End User</span><br><br>
                        @endif
                        
                        <span class="footer-cell-sign" style="text-decoration: underline;">
                            <b>{{ isset($icsitems->first()->person_accnt)  ? strtoupper($icsitems->first()->office_name) : strtoupper($icsitems->first()->office_name); }}
                        </span><br>
                        <span class="footer-cell-text">Positon / Office</span><br><br>
                        <span class="footer-cell-sign">____________________</span><br>
                        <span class="footer-cell-text">Date</span>
                    </span>
                </td>
                <td colspan="4" class="sign" style="text-align: center;">
                    <span class="text-receivedby" style="float: left">Issued by:</span><br>
                    <span class="footer-cell">
                        <span class="footer-cell-sign"><u><b>BENJAMIN R. DELA TORRE</u></span><br>
                       <span class="footer-cell-text">Signature Over Printed Name</span><br><br>
                       <span class="footer-cell-sign" style="text-decoration: underline;">
                           <b>Supply Officer / SUPPLY OFFICE
                       </span><br>
                       <span class="footer-cell-text">Positon / Office</span><br><br>
                       <span class="footer-cell-sign"><u><b>{{ \Carbon\Carbon::now()->format('M. j, Y') }}</u></span><br>
                       <span class="footer-cell-text">Date</span>
                   </span>
                </td>
            </tr>
        </tfoot>
    </table>
    </div>
</body>
</html>