<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title></title>
<style>
    /* General text alignment */
    .text-type, .text1, .text2 {
        text-align: center;
    }

    .text-type { margin-top: -5px; }
    .text3 { font-size: 11pt; margin-top: 10px; }
    .text4 { font-size: 11pt; }

    /* Table styling */
    #rpcppe {
        font-family: "Bookman Old Style", Georgia, serif;
        border-collapse: collapse;
        width: 100%;
        table-layout: fixed;        /* Fix column widths */
        margin-top: 20px;
        word-wrap: break-word;      /* Wrap long text */
    }

    #rpcppe th, #rpcppe td {
        border: 1px solid #000;
        padding: 4px 6px;           /* smaller padding for fitting */
        font-size: 9pt;             /* smaller font for wide tables */
    }

    #rpcppe th {
        text-align: center;
        background-color: #fff;
        padding-top: 8px;
        padding-bottom: 8px;
        font-weight: bold;
    }

    #rpcppe tr:nth-child(even) { background-color: #f2f2f2; }
    #rpcppe tr:hover { background-color: #ddd; }

    #rpcppe tfoot td {
        font-size: 9pt;
        padding: 4px 6px;
        border: 1px solid #000;
    }

    /* Footer signature cells */
    .footer-cell {
        width: 32%;
        float: left;
        text-align: left;
        padding: 5px; 
    }

    .footer-cell-title { font-weight: bold; }
    .footer-cell-sign { margin-top: 20px; }
    .footer-cell-text {
        font-size: 8pt;
        margin-top: 5px;
        text-align: left;
    }

    .sign { height: 80px; }

    /* Center helper */
    .text-center { text-align: center; }

    /* Optional: reduce table width if too many columns */
    @media print, screen {
        #rpcppe th, #rpcppe td {
            font-size: 8.5pt;
            padding: 3px 5px;
        }
    }
</style>

</head>
<body>
	<header style="margin-top: -40px; margin-left: 250px;">
		<img src="{{ asset('template/img/rpcsep.png') }}">
	</header>

	<div class="text-type">
		@if (request('categories_id') === 'All' || empty($categoriesId))
		    @if (request('categories_id') === 'All')
		        <u>ALL</u>
		    @else
		        <u>{{ isset($purchase->first()->account_title_abbr) ? $purchase->first()->account_title_abbr : '' }}</u>
		    @endif
		@elseif ($purchase->isEmpty())
		    ____________________
		@endif

	</div>
	<div class="text1">(Type of Property, Plant and Equipment)</div>
	<div class="text2">As at <u>{{ $startDate }} to {{ $endDate }}</u>.</div>
	<div class="text3">Fund Cluster : ________________________________</div>
	<div class="text4">For which <u>ALEXANDRA M. VILLAROSA</u>,  <u>Gil Montilla National High School, Sipalay City</u>,  of <u>GIL MONTILLA NATIONAL HIGH SCHOOL</u>,  is accountable, having assumed such accountability on August 16, 2018.</div>

	<div class="table-responsive">
		<table id="rpcppe" class="table table-bordered">
			<thead>
				<tr>
					<th rowspan="2">ARTICLE</th>
					<th rowspan="2">DESCRIPTION</th>
					<th rowspan="2">PROPERTY NO.</th>
					<th rowspan="2" width="80">UNIT OF MEASURE</th>
					<th rowspan="2" width="80">UNIT VALUE</th>
					<th rowspan="2" width="80">QUANTITY <br>PER<br> PROPERTY CARD</th>
					<th rowspan="2" width="80">Total Cost</th>
					<th rowspan="2" width="80">QUANTITY <br>PER<br> PHYSICAL COUNT</th>
					<th colspan="2">SHORTAGE<br>OVERAGE</th>
					<th rowspan="2">REMARKS</th>
					<th rowspan="2" width="100">Whereabout</th>
					@if($locationcolumn == 1)
					<th class="" rowspan="2	">LOCATION</th>
					@endif
					@if($serial == 1)
					<th class="" rowspan="2	">SERIAL</th>
					@endif
					@if($acquired == 1)
					<th class="" rowspan="2	">DATE ACQUIRED</th>
					@endif
				</tr>
				<tr>
					<th>Quantity</th>	
					<th>Value</th>
				</tr>
			</thead>
			<tr>
				<th colspan="6" style="text-align: right">Balance Brought Forwarded</th>
				<th colspan="6" style="text-align: left">{{ number_format($bforward, 2) }}</th>
				@if($locationcolumn == 1)
					<td></td>
				@endif
				@if($serial == 1)
					<td></td>
				@endif
				@if($acquired == 1)
					<td></td>
				@endif
			</tr>
			{{-- <tbody>
				@if ($purchase->isEmpty())
				<tr>
				    <td colspan="11" align="center">No purchase data available.</td>
					@if($locationcolumn == 1)
						<td></td>
					@endif
					@if($serial == 1)
						<td></td>
					@endif
					@if($acquired == 1)
						<td></td>
					@endif
				</tr>
				@else 
					@php $no = 1; $overallTotal = 0; @endphp
				    @foreach ($purchase as $purchaseData)
				        <tr>
				            <td>{{ $purchaseData->item_name }}</td>
				            <td>{{ $purchaseData->item_descrip }}</td>
				            <td>{{ $purchaseData->property_no_generated }}</td>
				            <td>{{ $purchaseData->unit_name }}</td>
				            <td>{{ number_format(str_replace(',', '', $purchaseData->item_cost), 2) }}</td>
				            <td class="text-center">{{ $purchaseData->qty }}</td>
				            <td>{{ number_format(str_replace(',', '', $purchaseData->total_cost), 2) }}</td>
				            <td></td>
				            <td class="text-center">{{ $purchaseData->qty }}</td>
				            <td></td>
				            <td>{{ $purchaseData->remarks }}</td>
				            <td>{{ $purchaseData->office_name }}</td>
							@if($locationcolumn == 1)
								<td>{{ $purchaseData->itemlocated }}</td>
							@endif
							@if($serial == 1)
								<td>{{ $purchaseData->serial_number }}</td>
							@endif
							@if($acquired == 1)
								<td width="65" style="text-align: center;">{{ strtoupper(\Carbon\Carbon::parse($purchaseData->date_acquired)->format('M. d, Y')) }}</td>
							@endif
				        </tr>
				        @if (is_numeric(str_replace(',', '', $purchaseData->total_cost)))
					        @php $overallTotal += str_replace(',', '', $purchaseData->total_cost); @endphp
					    @endif
				    @endforeach
				    <tr>
			        	<td colspan="6" style="text-align: right"><strong>Total</strong></td>
			        	<td colspan="6"><strong>{{ number_format($overallTotal, 2) }}</strong></td>
						@if($locationcolumn == 1)
							<td></td>
						@endif
						@if($serial == 1)
							<td></td>
						@endif
						@if($acquired == 1)
							<td></td>
						@endif
			        </tr>
			        <tr>
			        	<td colspan="6" style="text-align: right"><strong>Grand Total </strong></td>
			        	<td colspan="6"><strong>{{ number_format($overallTotal + $bforward, 2) }}</strong></td>
						@if($locationcolumn == 1)
							<td></td>
						@endif
						@if($serial == 1)
							<td></td>
						@endif
						@if($acquired == 1)
							<td></td>
						@endif
			        </tr>
				@endif
			</tbody> --}}
			<tbody>
				@if ($purchase->isEmpty())
					<tr>
						<td colspan="11" align="center">No purchase data available.</td>
						@if($locationcolumn == 1)<td></td>@endif
						@if($serial == 1)<td></td>@endif
						@if($acquired == 1)<td></td>@endif
					</tr>
				@else
					@php
						$overallTotal = 0;
						$chunkSize    = 500;           // 300–800 is usually good balance
					@endphp

					@foreach ($purchase->chunk($chunkSize) as $chunk)
						@foreach ($chunk as $purchaseData)
							<tr>
								<td>{{ $purchaseData->item_name ?? '' }}</td>
								<td>{{ $purchaseData->item_descrip ?? '' }}</td>
								<td>{{ $purchaseData->property_no_generated ?? '' }}</td>
								<td class="text-center">{{ $purchaseData->unit_name ?? '' }}</td>
								<td>{{ number_format((float) str_replace(',', '', $purchaseData->item_cost ?? '0'), 2) }}</td>
								<td class="text-center">{{ $purchaseData->qty ?? '' }}</td>
								<td>{{ number_format((float) str_replace(',', '', $purchaseData->total_cost ?? '0'), 2) }}</td>
								<td></td>
								<td class="text-center">{{ $purchaseData->qty ?? '' }}</td>
								<td></td>
								<td>{{ $purchaseData->remarks ?? '' }}</td>
								<td>{{ $purchaseData->office_name ?? '' }}</td>

								@if($locationcolumn == 1)
									<td>{{ $purchaseData->itemlocated ?? '' }}</td>
								@endif
								@if($serial == 1)
									<td>{{ $purchaseData->serial_number ?? '' }}</td>
								@endif
								@if($acquired == 1)
									<td width="65" style="text-align: center;">
										@if($purchaseData->date_acquired)
											{{ strtoupper(\Carbon\Carbon::parse($purchaseData->date_acquired)->format('M. d, Y')) }}
										@else
											—
										@endif
									</td>
								@endif
							</tr>

							@php
								$cost = (float) str_replace(',', '', $purchaseData->total_cost ?? '0');
								if (is_numeric($cost) && $cost > 0) {
									$overallTotal += $cost;
								}

								// Force garbage collection periodically
								if ($loop->parent->iteration % 4 === 0) {   // every 4 chunks (~2000 rows)
									gc_collect_cycles();
								}
							@endphp
						@endforeach
					@endforeach

					<!-- Totals – now safe to display -->
					<tr>
						<td colspan="6" style="text-align: right"><strong>Total</strong></td>
						<td colspan="6"><strong>{{ number_format($overallTotal, 2) }}</strong></td>
						@if($locationcolumn == 1)<td></td>@endif
						@if($serial == 1)<td></td>@endif
						@if($acquired == 1)<td></td>@endif
					</tr>
					<tr>
						<td colspan="6" style="text-align: right"><strong>Grand Total</strong></td>
						<td colspan="6"><strong>{{ number_format($overallTotal + ($bforward ?? 0), 2) }}</strong></td>
						@if($locationcolumn == 1)<td></td>@endif
						@if($serial == 1)<td></td>@endif
						@if($acquired == 1)<td></td>@endif
					</tr>
				@endif
			</tbody>
			<tfoot>
				<tr>
					<td colspan="12" class="sign">
				        <div class="footer-cell">
							<div class="footer-cell-title">Certified Correct by:</div>
							<div class="footer-cell-sign">BENJAMIN R. DELA TORRE</div>
							<div class="footer-cell-text">Administrative Officer / Supply Officer</div>
						</div>

						<div class="footer-cell">
							<div class="footer-cell-title">Approved by:</div>
							<div class="footer-cell-sign">ALEXANDRA M. VILLAROSA</div>
							<div class="footer-cell-text">School Principal</div>
						</div>

						<div class="footer-cell">
							<div class="footer-cell-title">Verify by:</div>
							<div class="footer-cell-sign">&nbsp;</div>
							<div class="footer-cell-text">Signature over Printed Name of COA Representative</div>
						</div>
					</td>
					@if($locationcolumn == 1)
						<td></td>
					@endif
					@if($serial == 1)
						<td></td>
					@endif
					@if($acquired == 1)
						<td></td>
					@endif
					{{-- <td rowspan=""></td> --}}
				</tr>
			</tfoot>
		</table>
	</div>
</body>
</html>