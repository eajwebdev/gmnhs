<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title></title>

	<style>
		#sticker {
		  	font-family: Bookman Old Style, Georgia, serif;
		  	border-collapse: collapse;
		  	width: 100%;
		  	margin-top: 20px;
		  	color: #000;
		}

		#sticker td {
			border: 1px solid #000;
		  	padding: 4px;
		} 
		#sticker th {
		  	border: 1px solid #000;
		  	padding: 4px;
		}

		#sticker tfoot {
		  	border: 2px solid #000;
		  	padding: 4px;
		}

		#sticker th {
		  	padding-top: 1px;
		  	padding-bottom: 1px;
		  	font-size: 10pt;
		}
		.sticker-text-label {
			text-align: center;
		}
		.sticker-label {
			text-align: left;
		}
		.logo-sticker {
            width: 40px;
            float: center;
        }
        .colortableyellow {
        	background-color: yellow;
        }
        .colortablegreen {
        	background-color: #008000;
        }
        .dataText {
        	font-style: italic;
        	font-size: 9pt;
        	text-decoration: underline;
        }
        .dataText1 {
        	font-style: italic;
        	font-size: 9pt;
        }
	</style>
</head>
<body>
	@if (strpos($purchase->serial_number, ';') !== false)
	<div class="colortable" style="background-color: {{ $propertiesId == 3 ? 'yellow' : ($propertiesId == 2 ? 'lightgreen' : ($propertiesId == 1 ? 'green' : '')) }}">
		<table id="sticker">
			<thead>
				<tr>
					<th rowspan="1" class="sticker-text-label">
						<img src="{{ asset('logo.png') }}" class="logo-sticker brand-image img-circle elevation-3" alt="GMNHS logo">
					</th>
					<th colspan="3" class="sticker-text-label" style="font-size: 11pt">Gil Montilla National High School</th>
				</tr>
				<tr>
					<th rowspan="9" class="sticker-text-label"><canvas id="qrcode" class="elevation-3"></canvas></th>
					<th><b class="sticker-label">Property No.:</b> <span class="dataText">{{ $purchase->property_no_generated }}</span class=""></th>
				</tr>
				<tr>
					<th>Classification: <span class="dataText">{{ $purchase->account_title_abbr }}</span></th>
				</tr>
				<tr>
					<th>Item/Model/Brand: <span class="dataText">{{ $purchase->item_model }}</span></th>
				</tr>
				<tr>
					<th>Serial No.: <span class="dataText">{{ strtok($purchase->serial_number, ';') }}</span></th>
				</tr>
				<tr>
					<th>Acquisition Cost: <span class="dataText">{{ number_format($purchase->item_cost, 2) }}</span></th>
				</tr>
				<tr>
					<th>Acquisition Date: <span class="dataText">{{ $purchase->date_acquired }}</span></th>
				</tr>
				<tr>
					<th>Person Accountable: <span class="dataText">{{ $purchase->office_officer }}</span></th>
				</tr>
				<tr>
					<th>Assignment: <span class="dataText">{{ $purchase->office_name }}</span></th>
				</tr>
				<tr>
					<th>Validation Sign: <span class="dataText"></span></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td colspan="5" class="sticker-text-label">*Removing or tampering of this sticker is punishable by Law*</td>
				</tr>
			</tbody>
		</table>
	</div>

	<div class="colortable" style="background-color: {{ $propertiesId == 3 ? 'yellow' : ($propertiesId == 2 ? 'lightgreen' : ($propertiesId == 1 ? 'green' : '')) }}">
		<table id="sticker">
			<thead>
				<tr>
					<th rowspan="1" class="sticker-text-label">
						<img src="{{ asset('logo.png') }}" class="logo-sticker brand-image img-circle elevation-3" alt="GMNHS logo">
					</th>
					<th colspan="3" class="sticker-text-label" style="font-size: 11pt">Gil Montilla National High School</th>
				</tr>
				<tr>
					<th rowspan="9" class="sticker-text-label"><canvas id="qrcode1" class="elevation-3"></canvas></th>
					<th><b class="sticker-label">Property No.:</b> <span class="dataText">{{ $purchase->property_no_generated }}</span class=""></th>
				</tr>
				<tr>
					<th>Classification: <span class="dataText">{{ $purchase->account_title_abbr }}</span></th>
				</tr>
				<tr>
					<th>Item/Model/Brand: <span class="dataText">{{ $purchase->item_model }}</span></th>
				</tr>
				<tr>
					<th>Serial No.: <span class="dataText">{{ substr(strstr($purchase->serial_number, ';'), 1) }}</span></th>
				</tr>
				<tr>
					<th>Acquisition Cost: <span class="dataText">{{ number_format($purchase->item_cost, 2) }}</span></th>
				</tr>
				<tr>
					<th>Acquisition Date: <span class="dataText">{{ $purchase->date_acquired }}</span></th>
				</tr>
				<tr>
					<th>Person Accountable: <span class="dataText">{{ $purchase->office_officer }}</span></th>
				</tr>
				<tr>
					<th>Assignment: <span class="dataText">{{ $purchase->office_name }}</span></th>
				</tr>
				<tr>
					<th>Validation Sign: <span class="dataText"></span></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td colspan="5" class="sticker-text-label">*Removing or tampering of this sticker is punishable by Law*</td>
				</tr>
			</tbody>
		</table>
	</div>

	@else

	<div class="colortable" style="background-color: {{ $propertiesId == 3 ? 'yellow' : ($propertiesId == 2 ? 'lightgreen' : ($propertiesId == 1 ? 'green' : '')) }}">
		<table id="sticker">
			<thead>
				<tr>
					<th rowspan="1" class="sticker-text-label">
						<img src="{{ asset('logo.png') }}" class="logo-sticker brand-image img-circle elevation-3" alt="GMNHS logo">
					</th>
					<th colspan="3" class="sticker-text-label" style="font-size: 11pt">Gil Montilla National High School</th>
				</tr>
				<tr>
					<th rowspan="9" class="sticker-text-label"><canvas id="qrcode" class="elevation-3"></canvas></th>
					<th><b class="sticker-label">Property No.:</b> <span class="dataText">{{ $purchase->property_no_generated }}</span class=""></th>
				</tr>
				<tr>
					<th>Classification: <span class="dataText">{{ $purchase->account_title_abbr }}</span></th>
				</tr>
				<tr>
					<th>Item/Model/Brand: <span class="dataText">{{ $purchase->item_model }}</span></th>
				</tr>
				<tr>
					<th>Serial No.: <span class="dataText">{{ $purchase->serial_number }}</span></th>
				</tr>
				<tr>
					<th>Acquisition Cost: <span class="dataText">{{ number_format($purchase->item_cost, 2) }}</span></th>
				</tr>
				<tr>
					<th>Acquisition Date: <span class="dataText">{{ $purchase->date_acquired }}</span></th>
				</tr>
				<tr>
					<th>Person Accountable: <span class="dataText">{{ $purchase->office_officer }}</span></th>
				</tr>
				<tr>
					<th>Assignment: <span class="dataText">{{ $purchase->office_name }}</span></th>
				</tr>
				<tr>
					<th>Validation Sign: <span class="dataText"></span></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td colspan="5" class="sticker-text-label">*Removing or tampering of this sticker is punishable by Law*</td>
				</tr>
			</tbody>
		</table>
	</div>

	@endif

<script>
    var inputText= "{{ $purchase->property_no_generated }}";
    var qr = new QRious({
        element: document.getElementById("qrcode"),
        value: inputText,
        size: 90,
    });
</script>

<script>
    var inputText= "{{ $purchase->property_no_generated }}";
    var qr = new QRious({
        element: document.getElementById("qrcode1"),
        value: inputText,
        size: 90,
    });
</script>


<script>
    function downloadStickers() {
        const colortables = document.querySelectorAll('.colortable');
        colortables.forEach((colortable, index) => {
            downloadSticker(colortable, index);
        });
    }

    function downloadSticker(colortable, index) {
        const contentToCapture = colortable;
        const originalBackgroundColor = getBackgroundColor();
        const backgroundColor = originalBackgroundColor;

        contentToCapture.style.backgroundColor = backgroundColor;

        html2canvas(contentToCapture).then(function(canvas) {
            const imageData = canvas.toDataURL('image/png');
            contentToCapture.style.backgroundColor = originalBackgroundColor;

            const downloadLink = document.createElement('a');
            downloadLink.href = imageData;
            downloadLink.download = 'sticker_' + (index + 1) + '.png';
            triggerDownload(downloadLink);
        });
    }

    function triggerDownload(link) {
        const event = new MouseEvent('click', {
            view: window,
            bubbles: true,
            cancelable: true
        });
        link.dispatchEvent(event);
    }

    function getBackgroundColor() {
        return "{{ $propertiesId == 3 ? 'yellow' : ($propertiesId == 2 ? 'lightgreen' : ($propertiesId == 1 ? 'green' : '')) }}";
    }

    // Call the function to initiate the download
    $('#downloadStickerButton').click(function() {
        downloadStickers();
    });
</script>



</body>
</html>
