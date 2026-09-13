<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ strtoupper('PAR REPORT') }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
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
        .details p {
            margin: 3px 0;
        }
        table#rpcppe {
            font-family: Arial, sans-serif;
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
            padding: 8px;
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
        .text-center {
            text-align: center;
        }
    </style>
</head>
<body>

    <div class="header-container">
        <p class="appendix">Appendix 71</p>
        <header>
            <img src="{{ asset('logo.png') }}" width="60%" class="header-image">
        </header>
    </div>

    <p class="title">PROPERTY ACKNOWLEDGEMENT RECEIPT</p>

    <div class="details">
        @if (!$paritems->isEmpty())
            <p>Entity Name: {{ $paritems[0]->office_name }}</p>
        @else
            <p>Entity Name: ___________________________________________________</p>
        @endif
        <p>Fund Cluster: __________________________________________________  PAR No.: _______________________</p>
    </div>
	<div class="table-responsive">
		<table id="rpcppe" class="table table-bordered">
			<thead>
				<tr>
					<th width="30">No</th>
					<th width="30">Qty</th>
					<th width="8%">Unit</th>
					<th width="30%">Description</th>
					<th>Property Number</th>
					@if($locationcolumn == 1)
					<th class="">Location</th>
					@endif
					<th width="13%">Date Acquired</th>
					<th width="13%">Amount</th>
				</tr>
			</thead>
			<tbody>
				@php
			        $maxRows = 3;
			        $rowCount = 0;
					$no = 1;
			        $overallTotal = 0;
			        $grandTotal = 0;
			    @endphp

			    @foreach ($paritems as $paritem)
			        <tr>
						<td style="text-align: center;">{{ $no++ }}</td>
			            <td style="text-align: center;">{{ $paritem->qty }}</td>
			            <td class="text-center">{{ $paritem->unit_name }}</td>
						<td>
							<b>{{ $paritem->item_name }}</b>
							<br><i> {{ $paritem->item_descrip }}</i><br>
							<b>MODEL:</b>{{ $paritem->item_model ? str_replace('Model:', '', $paritem->item_model) : '' }}<br>
							<b>SN : </b> <span style="font-size: 12px;">{!!  str_replace(';', '<br>', $paritem->serial_number) !!}</span>
						</td>
			            <td>{{ $paritem->property_no_generated }}</td>
						@if($locationcolumn == 1)
							<td>{{ $paritem->itemlocated }}</td>
						@endif
			            <td>{{ $paritem->date_acquired }}</td>
			            <td align="right"><b>{{ number_format($paritem->item_cost, 2) }}</b></td>

			            @if (is_numeric(str_replace(',', '', $paritem->item_cost)))
			                {{-- @php $overallTotal += str_replace(',', '', $paritem->item_cost); @endphp --}}
			                @php 
				                $itemTotal = $paritem->qty * str_replace(',', '', $paritem->item_cost);
				                $overallTotal += $itemTotal;
				                $grandTotal += $itemTotal; // Add to grand total
				            @endphp
			            @endif
			        </tr>

			        @if (is_numeric(str_replace(',', '', $paritem->item_cost)))
			            @php $rowCount++; @endphp
			        @endif
			    @endforeach
					<tr>
						<td height="13"></td>
						<td></td>
						<td></td>
						<td></td>
						@if($locationcolumn == 1)
							<td></td>
						@endif
						<td></td>
						<td></td>
						<td></td>
					</tr>
					<tr>
						<td height="13"></td>
						<td></td>
						<td></td>
						<td></td>
						@if($locationcolumn == 1)
							<td></td>
						@endif
						<td></td>
						<td></td>
						<td></td>
					</tr>
			    	<tr>
			            <td height="13"></td>
			            <td></td>
			            <td></td>
			            <td></td>
						@if($locationcolumn == 1)
							<td></td>
						@endif
						<td></td>
			            <td style="text-align: right">Grand Total:</td>
			            <td style="text-align: right"><strong>{{ number_format($grandTotal, 2) }}</strong></td>
			        </tr>
				<tr>
			    	<td colspan="{{ $locationcolumn == 1 ? 4 : 3 }}" style="text-align: right"><b class="text-total">Supplier:</b></td>
			    	<td colspan="4" style="text-align: left"><b class="text-total"></b></td>
			    </tr>
			</tbody>
			<tfoot>
				<tr>
					<td colspan="4" class="sign" style="text-align: center;">
						<span class="text-receivedby" style="float: left">Received by:</span><br>
						 <span class="footer-cell">
							<span class="footer-cell-sign" style="text-decoration: underline;">
								<b>{{ (isset($paritems[0]->person_accnt_id)) ? strtoupper($paritems[0]->person_accnt) : strtoupper($paritems[0]->person_accnt_name); }}
							</span><br>
							<span class="footer-cell-text">Signature Over Printed Name</span><br><br>

							@if($paritems->isNotEmpty() && $paritems->first()?->enduser_name)
								<span class="footer-cell-sign" style="text-decoration: underline;">
									<b>{{ strtoupper($paritems->first()->enduser_name) }}</b>
								</span><br>
								<span class="footer-cell-text">End User</span><br><br>
							@endif

							<span class="footer-cell-sign" style="text-decoration: underline;">
								<b>{{ isset($paritems[0]->person_accnt_id)  ? strtoupper($paritems[0]->office_name) : strtoupper($paritems[0]->office_name); }}
							</span><br>
							<span class="footer-cell-text">Positon / Office</span><br><br>

							<span class="footer-cell-sign">____________________</span><br>
							<span class="footer-cell-text">Date</span>
						</span>
					</td>
					<td colspan="{{ $locationcolumn == 1 ? 4 : 3 }}" class="sign" style="text-align: center;">
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