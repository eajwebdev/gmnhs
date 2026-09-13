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
		}

		#rpcppe td {
			border: 1px solid #000;
		  	padding: 8px;
		} 
		#rpcppe th {
		  	border: 2px solid #000;
		  	padding: 8px;
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
		  	font-size: 10pt;
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
	</style>
</head>
<body>
	<header style="margin-top: -40px; margin-left: 250px;">
		<img src="{{ asset('template/img/rpcppe.png') }}">
	</header>

	<div class="text-type">
		@if ($inventory === 'All' || empty($categoriesId))
    		<u>ALL</u>
		@elseif ($inventory->isEmpty())
		    ____________________
		@else
		    {{ $inventory->first()->account_title_abbr }}
		@endif

	</div>
	<div class="text1">(Type of Property, Plant and Equipment)</div>
	<div class="text2">As at ________________________________</div>
	<div class="text3">Fund Cluster : ________________________________</div>
	<div class="text4">For which <u>ALEXANDRA M. VILLAROSA</u>,  <u>Gil Montilla National High School, Sipalay City</u>,  of <u>GIL MONTILLA NATIONAL HIGH SCHOOL</u>,  is accountable, having assumed such accountability on <u>{{ $startDate }} to {{ $endDate }}</u>.</div>

	<div class="table-responsive">
		<table id="rpcppe" class="table table-bordered">
			<thead>
				<tr>
					<th rowspan="2" width="10">ARTICLE</th>
					<th rowspan="2">DESCRIPTION</th>
					<th rowspan="2">PROPERTY NO.</th>
					<th rowspan="2" width="30">UNIT OF MEASURE</th>
					<th rowspan="2" width="30">UNIT VALUE</th>
					<th rowspan="2" width="30">QUANTITY <br>PER<br> PROPERTY CARD</th>
					<th rowspan="2" width="30">QUANTITY <br>PER<br> PHYSICAL COUNT</th>
					<th colspan="2">SHORTAGE<br>OVERAGE</th>
					<th rowspan="2">REMARKS</th>
					<th colspan="1">LOCATION</th>
				</tr>
				<tr>
					<th>Quantity</th>	
					<th>Value</th>
					<th>Whereabout</th>
				</tr>
			</thead>
			<tbody>
				@if ($inventory->isEmpty())
				<tr>
				    <td colspan="11" align="center">No purchase data available.</td></tr>
				@else
					@php $no = 1; @endphp
				    @foreach ($inventory as $inventoryData)
				        <tr>
				            <td>{{ $no++ }}</td>
				            <td>{{ $inventoryData->item_descrip }}</td>
				            <td>{{ $inventoryData->property_no_generated }}</td>
				            <td>{{ $inventoryData->unit_name }}</td>
				            <td>{{ $inventoryData->item_cost }}</td>
				            <td>{{ $inventoryData->qty }}</td>
				            <td></td>
				            <td>{{ $inventoryData->qty }}</td>
				            <td></td>
				            <td>{{ $inventoryData->remarks }}</td>
				            <td>{{ $inventoryData->office_name }}</td>
				        </tr>
				    @endforeach
				@endif
			</tbody>
			<tfoot>
				<tr>
					<td colspan="10" class="sign">
				        <div class="footer-cell">
							<div class="footer-cell-title">Certified Correct by:</div>
							<div class="footer-cell-sign">BENJAMIN R. DELA TORRE</div>
							<div class="footer-cell-text">Administrative Officer IV/Supply Officer designate</div>
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
					<td rowspan=""></td>
				</tr>
			</tfoot>
		</table>
	</div>
</body>
</html>