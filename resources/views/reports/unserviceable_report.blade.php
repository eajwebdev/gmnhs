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
			font-size: 12pt;
		}
		.text2 {
			text-align: center;
		}
		.text3 {
			font-size: 12pt;
			margin-top: 10px;
		}
		.text4 {
			font-size: 12pt;
		}

		#unserve {
		  	font-family: Bookman Old Style, Georgia, serif;
		  	border-collapse: collapse;
		  	width: 100%;
		  	margin-top: 20px;
		  	margin-left: -10px;
		}

		#unserve td {
			border: 1px solid #000;
			padding: 2px;
		  	font-size: 8pt;
		} 
		#unserve th {
		  	border: 2px solid #000;
		}

		#unserve tfoot {
		  	border: 2px solid #000;
		  	padding: 8px;
		}
		
		#unserve tr:hover {background-color: #ddd;}

		#unserve th {
			padding: 2px;
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
@php
    // generateReport passes $items / $selected_columns; this template was written for $unservitems / $serial
    $unservitems = $unservitems ?? ($items ?? collect());
    $selectedColumns = (array) ($selected_columns ?? []);
    $serial = $serial ?? ((in_array('serial_number', $selectedColumns, true) || in_array('serial', $selectedColumns, true)) ? 1 : 0);
@endphp
	<header style="margin-top: -40px; margin-left: 250px;">
		<img src="{{ asset('logo.png') }}">
	</header>
	<div style="text-align: center; font-size: 12px;" ><b>As of: {{ \Carbon\Carbon::now()->format('F d, Y') }}</b></div>
	<table style="width: 100%; font-size: 12px;">
		<tr>
			<th><b style="float: left; margin-left: -13px;">Entity Name : <span style="display: inline-block; margin-bottom: -3px; width: 260px; text-align:left; border-bottom: 1px solid black;"> GIL MONTILLA NATIONAL HIGH SCHOOL </span></th>
			<th><b style="float: right; margin-right: 10px;">Fund Cluster : <span style="display: inline-block; margin-bottom: -3px; width: 250px; text-align:left; border-bottom: 1px solid black;"> </span></th>
		</tr>
	</table>
	<table style="width: 100%; font-size: 12px; margin-top: 35px;">
		<tr>
			<th style="text-align: center;">
				<span style="display: inline-block; margin-bottom: -3px; width: 160px; "></b>ELEANOR V. SANTIAGO</span>
				<br>
				<i>School Principal</i>
			</th>
			<th>
				<span style="display: inline-block; margin-bottom: -3px; width: 160px; "></b>SCHOOL PRINCIPAL</span>
				<br>
				<i>(Designation)</i>
			</th>
			<th>
				<span style="display: inline-block; margin-bottom: -3px; width: 160px; "><b>GIL MONTILLA NATIONAL HIGH SCHOOL</span>
				<br>
				<i>Station</i>
			</th>
		</tr>
	</table>
	<div class="table-responsive">
		<table id="unserve" class="table">
			<thead>
				<tr>
					<th colspan="10">INVENTORY</th>
					<th colspan="@if($serial == 1) 10 @else 8 @endif">INSPECTION and DISPOSAL</th>
				</tr> 
				<tr style="padding: 2px">
					<th rowspan="2" width="50">Date Acquired</th>
					<th rowspan="2" width="100">Particulars / Article</th>
					<th rowspan="2" >Property No. / Serial Number</th>
					<th rowspan="2" >Qty</th>
					<th rowspan="2" >Unit Cost</th>
					<th rowspan="2" >Total Cost</th>
					<th rowspan="2" >Accumulated Depreciation</th>
					<th rowspan="2"> Accumulated Impairment Losses</th>
					<th rowspan="2"> Carrying Amount</th>
					<th rowspan="2"> Remarks</th>
					<th colspan="5" style="height: 10px !important;"> DISPOSAL</th>
					<th rowspan="2"> Appraised Value</th>
					<th colspan="2"> RECORDS OF SALE</th>
					@if($serial == 1) 
						<th colspan="2"></th>
					@endif
				</tr>
				<tr>
					<th >Sale</th>
					<th >Transfer</th>
					<th >Destruction</th>
					<th >Other (Specisfy)</th>
					<th >Total</th>

					<th >OR No. </th>
					<th >Amount </th>
					@if($serial == 1) 
					<th width="100">Serial Number</th>
					<th width="100">Accountable Person</th>
					@endif
				</tr>
				<tr>
					@for($i = 1; $i <= 18; $i++)
					<th>{{ $i }}</th>
					@endfor
					@if($serial == 1) 
					<th>19</th>
					<th>20</th>
					@endif
				</tr>				
			</thead>
			<tbody>
				@foreach ($unservitems as $unservitem)
				<tr>
					<td>{{$unservitem->date_acquired }}</td>
					<td>
						<b>{{ $unservitem->item_name }}</b>
						<br><i> {{ $unservitem->item_descrip }}</i><br>
						<b>MODEL:</b>{{ $unservitem->item_model ? str_replace('Model:', '', $unservitem->item_model) : '' }}<br>
						{{-- <b>SN : </b> {{ $unservitem->serial_number }} --}}
					</td>
					<td class="text-center">{{ $unservitem->property_no_generated }}</td>
					<td class="text-center">{{ $unservitem->qty }}</td>
					<td class="text-center">{{ $unservitem->item_cost }}</td>
					<td class="text-center">{{ number_format(str_replace(',', '', $unservitem->item_cost) * str_replace(',', '', $unservitem->qty)) }}</td>
					<td></td>
					<td></td>
					<td></td>
					<td></td>
					<td></td>
					<td></td>
					<td></td>
					<td></td>
					<td></td>
					<td></td>
					<td></td>
					<td></td>
					@if($serial == 1) 
						<td>{{ $unservitem->serial_number }}</td>
						<td class="text-center">{{ ($unservitem->person_accnt1 !== '') ? $unservitem->person_accnt_name : '' }}</td>
					@endif
				</tr>
				@endforeach
			</tbody>
			<tfoot>
				<tr>
					<td colspan="10" style="border-bottom: none !important;">
						<div><b>I HEREBY request inspection and disposition, pursuant to Section  79 of PD 1445, of the property enumerated above</div><br<br><br>
					</td>
					<td colspan="4" style="border-bottom: none !important;">
						I CERTIFY that I have inspected each and every article enumerated in this report, and that the disposition made thereof was, in my judgment, the best for the public interest.  													
						<br>
					</td>
					<td colspan="@if($serial == 1) 6 @else 4 @endif" style="border-bottom: none !important;">
						I CERTIFY that I have witnessed the disposition of the articles enumerated on this report this <u>{{ \Carbon\Carbon::now()->format('jS') }}</u> of <u>{{ \Carbon\Carbon::now()->format('F') }}</u>, <u>{{ \Carbon\Carbon::now()->format('Y') }}</u>.
					</td>
				</tr>

				<tr>
					<td colspan="5" class="text1"  style="border-top: none !important; border-right: none !important;">
						<div style="text-align: left !important;"><b>Requested by:</div><br><br>
						<span style="display: inline-block; margin-bottom: -3px; width: 160px; "><b>ELEANOR V. SANTIAGO </span>
						<br>
						<i>School Principal</i>
					</td>
					<td colspan="5" class="text1"  style="border-top: none !important; border-left: none !important;">
						<div style="text-align: left !important;"><b>Approved by:</div><br><br>
						<span style="display: inline-block; margin-bottom: -3px; width: 160px; "><b>ALEXANDRA M. VILLAROSA</span>
						<br>
						<i>School Principal</i>
					</td>
					<td colspan="4" class="text1"  style="border-top: none !important;"><br><br>
						<span style="display: inline-block; margin-bottom: -3px; width: 160px;"><b>DANIEL P. MONTECLARO </span>
						<br>
						<i>Signature over Printed Name of Inspection Officer</i>
					</td>
					<td colspan="@if($serial == 1) 6 @else 4 @endif" class="text1"  style="border-top: none !important;"><br><br>
						<span style="display: inline-block; margin-bottom: -3px; width: 160px; "><b>DANIEL P. MONTECLARO </span>
						<br>
						<i>Signature over Printed Name of Inspection Officer</i>
					</td>
				</tr>
			</tfoot>
		</table>
	</div>
</body>
</html>