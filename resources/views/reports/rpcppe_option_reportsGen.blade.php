<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title></title>
	<style>
		/*.table-responsive {
		  	overflow-x: auto;
		  	max-width: 100%; 
		}*/
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
		.text3 {
			font-size: 11pt;
			margin-top: 10px;
		}
		.text4 {
			font-size: 11pt;
		}

		#rpcppe {
		  	font-family: Bookman Old Style, Georgia, serif;
		  	border-collapse: collapse;
		  	width: 100%;
		  	margin-top: 20px;
		  	margin-left: -10px;
		}

		#rpcppe td {
			border: 1px solid #000;
			padding: 2px;
		  	font-size: 8pt;
		} 
		#rpcppe th {
		  	border: 2px solid #000;
		}

		#rpcppe tfoot {
		  	border: 2px solid #000;
		  	padding: 8px;
		}

		#rpcppe tr:nth-child(even){background-color: #f2f2f2;}

		#rpcppe tr:hover {background-color: #ddd;}

		#rpcppe th {
		  	padding-top: 12px;
		  	padding-bottom: 12px;
		  	text-align: center;
		  	background-color: #fff;
		  	font-size: 8pt;
		}
		.footer-cell {
			width: 32%;
			float: left;
			text-align: left;
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
		  	text-align: left;
		}
		.sign {
			height: 80px;
		}
		.text-center{
			text-align: center;
		}
	</style>
</head>
<body>
	<header style="margin-top: -40px; margin-left: 250px;">
		<img src="{{ asset('template/img/rpcppe.png') }}">
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
	{{-- <div class="text2">As of <u>{{ $startDate }} to {{ $endDate }}</u>.</div> --}}
	<div class="text2">As of <u>{{ $endDate }}</u>.</div>
	<div class="text3">Fund Cluster : ________________________________</div>
	<div class="text4">For which <u>ALEXANDRA M. VILLAROSA</u>,  <u>Gil Montilla National High School, Sipalay City</u>,  of <u>GIL MONTILLA NATIONAL HIGH SCHOOL</u>,  is accountable, having assumed such accountability on August 16, 2018.</div>

	<div class="table-responsive">
		<table id="rpcppe" class="table table-bordered">
			<thead>
				<tr style="padding: 2px">
					<th rowspan="2" width="100">ARTICLE</th>
					<th rowspan="2" >DESCRIPTION</th>
					<th rowspan="2" width="100">PROPERTY NO.</th>
					<th rowspan="2" width="50">UNIT <br>OF MEASURE</th>
					<th rowspan="2" width="50">UNIT VALUE</th>
					<th rowspan="2" width="50">QUANTITY <br>PER<br> PROPERTY CARD</th>
					<th rowspan="2" width="50">Total Cost</th>
					<th rowspan="2" width="50">QUANTITY <br>PER<br> PHYSICAL COUNT</th>
					<th colspan="2">SHORTAGE<br>OVERAGE</th>
					<th rowspan="2">REMARKS</th>
					<th rowspan="2" width="100">Whereabout</th>
					{{-- <th colspan="1" width="@if($serial == 1 && $acquired == 1) 12 @elseif($serial == 1 || $acquired == 1) 11 @else 10 @endif">LOCATION</th> --}}
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
				<tr style="padding: 2px">
					<th>Quantity</th>	
					<th>Value</th>
					<th width="100">Whereabout</th>
				</tr>
			</thead>
			<tr>
				<th colspan="6" style="text-align: right">Balance Brought Forwarded - {{ number_format($countBforward, 2) }}</th>
				<th colspan="6" style="text-align: left"> {{ number_format($bforward, 2) }}</th>
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
			<tbody>
				@if ($purchase->isEmpty())
				<tr>
				    <td colspan="12" style="text-align:center;">No purchase data available.</td>
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
				            <td class="text-center">{{ $purchaseData->property_no_generated }}</td>
				            <td class="text-center">{{ $purchaseData->unit_name }}</td>
				            <td class="text-center">{{ $purchaseData->item_cost }}</td>
				            <td class="text-center">{{ $purchaseData->qty }}</td>
				            <td class="text-center">{{ $purchaseData->total_cost }}</td>
				            <td></td>
				            <td class="text-center">{{ $purchaseData->qty }}</td>
				            <td></td>
				            <td class="text-center">{{ $purchaseData->remarks }}</td>
				            <td class="text-center">{{ $purchaseData->office_name }}</td>
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
			        	<td colspan="6" style="text-align: right"><strong>Grand Total</strong></td>
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
							<div class="footer-cell-title">Verified by</div>
							<div class="footer-cell-sign">DANIEL P. MONTECLARO</div>
							<div class="footer-cell-text">State Auditor IV</div>
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