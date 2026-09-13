<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>PROPERTY STICKER</title>
    <style>
    @page {
        margin: 0;
    }

    body {
        margin: 0;
        padding: 0;
        font-family: Bookman Old Style, Georgia, serif;
    }

    .yellow {
        background-color: yellow;
    }
    .green {
        background-color: #008000;
    }
    .lightgreen{
        background-color: #8ceb8c;
    }

    table.layout {
        width: 100%;
        border-collapse: collapse;
        page-break-inside: avoid;
    }

    td.sticker-cell {
        width: 100%;
        height: 7%;
        padding: 0.5px;
        vertical-align: top;
    }

    table#sticker {
        width: 100%;
        border-collapse: collapse;
        font-size: 9pt;
        color: #000;
    }

    #sticker th, #sticker td {
        border: 1px solid #000;
        padding: 0.8px;
        text-align: left;
    }

    .logo-sticker {
        width: 40px;
    }

    .dataText {
        font-style: italic;
        font-size: 9pt;
    }

    .page-break {
        page-break-after: always;
    }

    .label-inline {
        display: flex;
        align-items: center;
        gap: 4px;
        overflow: hidden;
        white-space: nowrap;
    }

    .dataText-inline {
        font-size: 9pt;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        flex-grow: 1;
        display: inline-block;
    }

    .logo-sticker {
        width: 34.4px;
    }

    .sticker-text-label {
        text-align: center !important;
    }
    </style>
</head>
<body>

@php
    $properties = array_chunk($properties->all(), 10); // limit to 20 and 10 per page
@endphp

@foreach ($properties as $prop)
    <table class="layout">
        @foreach (array_chunk($prop, 2) as $row)
            <tr>
                @foreach ($row as $inventory)
                    <td class="sticker-cell">
                        @php
                            $serialNumbers = explode(';', $inventory->serial_number);
                            $serial = trim($serialNumbers[0] ?? 'N/A');
                            $cost = str_replace(',', '', $inventory->item_cost);
                            $backgroundColor = $cost < 5000 ? 'lightgreen' : ($cost < 50000 ? 'green' : 'yellow');
                        @endphp

                        <div class="{{ $backgroundColor }}">
                            <table id="sticker">
                                <thead>
                                    <tr>
                                        <th rowspan="1" class="sticker-text-label" style="width: 40px !important;">
                                            <img src="{{ public_path('logo.png') }}" class="logo-sticker">
                                        </th>
                                        <th colspan="4" class="sticker-text-label" style="font-size: 11pt">
                                            Gil Montilla National High School
                                        </th>
                                    </tr>
                                    <tr>
                                        <th rowspan="10" style="text-align: center;">
                                            <img src="data:image/png;base64,{{ $inventory->qr_base64 }}" width="55">
                                        </th>
                                        <th colspan="4">
                                            <div class="label-inline">
                                                <span class="dataText-inline">Property No.: <i>{{ $inventory->property_no_generated }}</i></span>
                                            </div>
                                        </th>
                                    </tr>
                                    <tr>
                                        <th colspan="4">
                                            <div class="label-inline">
                                                <span class="dataText-inline">Item: <i>{{ ucwords(strtolower($inventory->item_name)) }}</i></span>
                                            </div>
                                        </th>
                                    </tr>
                                    <tr>
                                        <th colspan="4">
                                            <div class="label-inline">
                                                <span class="dataText-inline">Classification: <i>{{ ucwords(strtolower($inventory->account_title_abbr)) }}</i></span>
                                            </div>
                                        </th>
                                    </tr>
                                    <tr>
                                        <th colspan="4">
                                            <div class="label-inline">
                                                <span class="dataText-inline">Model/Brand: <i>{{ $inventory->item_model }}</i></span>
                                            </div>
                                        </th>
                                    </tr>
                                    <tr>
                                        <th colspan="4">
                                            <div class="label-inline">
                                                <span class="dataText-inline">Serial No.: <i>{{ $serial }}</i></span>
                                            </div>
                                        </th>
                                    </tr>
                                    <tr>
                                        <th colspan="4">
                                            <div class="label-inline">
                                                <span class="dataText-inline">Acquisition Cost: <i>{{ number_format($inventory->item_cost, 2) }}</i></span>
                                            </div>
                                        </th>
                                    </tr>
                                    <tr>
                                        <th colspan="4">
                                            <div class="label-inline">
                                                <span class="dataText-inline">Acquisition Date: <i>{{ $inventory->date_acquired }}</i></span>
                                            </div>
                                        </th>
                                    </tr>
                                    <tr>
                                        <th colspan="4">
                                            <div class="label-inline">
                                                <span class="dataText-inline">
                                                    Person Accountable: <i>{{ !empty($inventory->person_accnt_fname2) ? ucwords(strtolower($inventory->person_accnt_fname2)) : ucwords(strtolower($inventory->person_accnt_fname1)) }}</i>
                                                </span>
                                            </div>
                                        </th>
                                    </tr>
                                    <tr>
                                        <th colspan="4">
                                            <div class="label-inline">
                                                <span class="dataText-inline">Assignment: <i>{{ ucwords(strtolower($inventory->office_name)) }}</i></span>
                                            </div>
                                        </th>
                                    </tr>
                                    <tr>
                                        <th colspan="4">
                                            <div class="label-inline">
                                                <span class="dataText-inline">Validation Sign:</span>
                                            </div>
                                        </th>
                                    </tr>
                                </thead>
                                <tfoot>
                                    <tr>
                                        <td colspan="5" class="sticker-text-label">*Removing or tampering of this sticker is punishable by Law*</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </td>
                @endforeach

                @if (count($row) < 2)
                    <td class="sticker-cell"></td>
                @endif
            </tr>
        @endforeach
    </table>

    @if (!$loop->last)
        <div class="page-break"></div>
    @endif
@endforeach

</body>
</html>
