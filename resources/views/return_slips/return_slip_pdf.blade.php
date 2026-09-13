{{--
    Property Return Slip (Supply Form 10, revised for DRAR) — the print/preview
    twin of public/Supply-Form-10-PROPERTY-RETURN-SLIP-REVISED (for DRAR).xlsx.
    One slip per return; see ReturnSlipController::buildReturnSlipReportData().

    The item table flows across sheets with its heading repeated, matching the
    Excel export. The instructions and signatory blocks print once at the end,
    because one return slip is signed once.
--}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>PROPERTY RETURN SLIP — {{ $slip_no }}</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 8pt;
            margin: 0;
        }

        /*
            Declared once as a background so the inlined letterhead is carried in
            the stylesheet rather than repeated in the markup.
            The GMNHS logo is square, so the report constrains it to the header block.
        */
        .letterhead {
            width: 62%;
            height: 0;
            padding-bottom: 9.97%;
            margin: 0 auto;
            background-image: url('{{ $letterhead }}');
            background-repeat: no-repeat;
            background-position: center top;
            background-size: 100% auto;
        }

        .form-title {
            text-align: center;
            font-weight: bold;
            font-size: 17pt;
            margin: 8px 0 2px;
        }

        .slip-meta {
            font-size: 8pt;
            margin: 0 0 5px;
            overflow: hidden;
        }

        .slip-meta .left { float: left; }
        .slip-meta .right { float: right; }

        table.items {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        table.items th,
        table.items td {
            border: 1px solid #000;
            padding: 2px 3px;
            font-size: 7.5pt;
            line-height: 1.15;
            vertical-align: middle;
            word-wrap: break-word;
        }

        table.items th {
            text-align: center;
            font-size: 8pt;
            height: 34px;
        }

        table.items tr { page-break-inside: avoid; }

        /* Short, atomic values: quantity, unit, date and fund code. Keeping them on
           one line stops a date breaking after "02/09/201". */
        table.items td.num {
            text-align: center;
            white-space: nowrap;
        }

        /* Amounts must never break mid-number, so these columns are sized to hold
           a full formatted figure and are kept on one line. */
        table.items td.money {
            text-align: right;
            white-space: nowrap;
        }

        table.items tfoot td {
            font-weight: bold;
            font-size: 8pt;
        }

        /*
            Follows the template's column proportions (A..K), with the two value
            columns widened enough to hold a formatted amount on one line.
        */
        .c-no       { width: 5%; }
        .c-qty      { width: 4%; }
        .c-unit     { width: 5%; }
        .c-name     { width: 11%; }
        .c-descrip  { width: 18%; }
        .c-uval     { width: 8.5%; }
        .c-tval     { width: 8.5%; }
        .c-propno   { width: 12%; }
        .c-acquired { width: 8.5%; }
        .c-fund     { width: 5.5%; }
        .c-enduser  { width: 14%; }

        .descrip-meta {
            display: block;
            font-size: 6.5pt;
            color: #222;
        }

        .closing { page-break-inside: avoid; }

        table.footer {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        table.footer > tbody > td,
        table.footer td {
            vertical-align: top;
            font-size: 8pt;
        }

        .instructions { width: 58%; }
        .instructions ol {
            margin: 2px 0 0;
            padding-left: 18px;
        }
        .instructions li { margin-bottom: 1px; }

        .sign-col { width: 42%; }

        .sign-block { margin-bottom: 10px; }

        .sign-label { margin-bottom: 16px; }

        .sign-name {
            font-weight: bold;
            text-transform: uppercase;
            border-top: 1px solid #000;
            display: inline-block;
            padding: 1px 26px 0;
        }

        .sign-role { font-size: 7.5pt; }

        .approved {
            margin-top: 10px;
            text-align: center;
        }

        .reason {
            margin-top: 6px;
            font-size: 7.5pt;
        }

        .provenance {
            margin-top: 6px;
            font-size: 6.5pt;
            font-style: italic;
            color: #333;
        }
    </style>
</head>
<body>

@php
    $rows = collect($items ?? [])->values();
@endphp

<div class="letterhead"></div>

<p class="form-title">PROPERTY RETURN SLIP</p>

<div class="slip-meta">
    <span class="left"><strong>RS No.:</strong> {{ $slip_no }}@if($office_name) &nbsp;&nbsp;<strong>Office:</strong> {{ $office_name }}@endif</span>
    <span class="right"><strong>Date:</strong> {{ $submitted_at }}</span>
</div>

<table class="items">
    <thead>
        <tr>
            <th class="c-no">ITEM NO.</th>
            <th class="c-qty">Qty.</th>
            <th class="c-unit">Unit</th>
            <th class="c-name">NAME</th>
            <th class="c-descrip">DESCRIPTION</th>
            <th class="c-uval">Unit Value</th>
            <th class="c-tval">Total Value</th>
            <th class="c-propno">Property Number</th>
            <th class="c-acquired">Date Acquired</th>
            <th class="c-fund">FUND CODE</th>
            <th class="c-enduser">END-USER</th>
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $row)
            <tr>
                <td class="num">{{ $loop->iteration }}</td>
                <td class="num">{{ $row['qty'] + 0 }}</td>
                <td class="num">{{ $row['unit_name'] }}</td>
                <td>{{ $row['item_name'] }}</td>
                <td>
                    @if($row['descrip'] && strcasecmp($row['descrip'], (string) $row['item_name']) !== 0)
                        {{ $row['descrip'] }}
                    @endif
                    @if($row['model'])
                        <span class="descrip-meta">Model: {{ $row['model'] }}</span>
                    @endif
                    @if($row['serial_number'])
                        <span class="descrip-meta">SN: {{ str_replace(';', ' / ', $row['serial_number']) }}</span>
                    @endif
                </td>
                <td class="money">{{ number_format($row['unit_value'], 2) }}</td>
                <td class="money">{{ number_format($row['total_value'], 2) }}</td>
                <td>{{ $row['property_no'] }}</td>
                <td class="num">{{ $row['date_acquired'] }}</td>
                <td class="num">{{ $row['fund_code'] }}</td>
                <td>{{ $row['enduser_name'] }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="11" style="text-align:center; height: 34px;">
                    No items on this return slip.
                </td>
            </tr>
        @endforelse
    </tbody>

    @if($rows->isNotEmpty())
        <tfoot>
            <tr>
                <td colspan="6" style="text-align:right;">
                    TOTAL ({{ $rows->count() }} item{{ $rows->count() === 1 ? '' : 's' }})
                </td>
                <td class="money">{{ number_format($total_value, 2) }}</td>
                <td colspan="4"></td>
            </tr>
        </tfoot>
    @endif
</table>

<div class="closing">
    @if($reason)
        <div class="reason"><strong>Reason / Notes:</strong> {{ $reason }}</div>
    @endif

    <table class="footer">
        <tr>
            <td class="instructions">
                <strong>Instructions:</strong>
                <ol>
                    @foreach($instructions as $instruction)
                        <li>{{ $instruction }}</li>
                    @endforeach
                </ol>

                <div class="sign-block" style="margin-top: 14px;">
                    <div class="sign-label">Prepared and Submitted by:</div>
                    <div><span class="sign-name">{{ $end_user ?: '&nbsp;' }}</span></div>
                    <div class="sign-role">End user{{ $office_name ? ' — '.$office_name : '' }}</div>
                    <div style="margin-top: 12px;">
                        <span class="sign-name">{{ $submitted_at ?: '&nbsp;' }}</span>
                    </div>
                    <div class="sign-role">Date of submission</div>
                </div>
            </td>

            <td class="sign-col">
                <div class="sign-block">
                    <div class="sign-label">Reviewed and Received by:</div>
                    <div><span class="sign-name">{{ $received_by['name'] }}</span></div>
                    <div class="sign-role">{{ $received_by['designation'] }}</div>
                </div>

                <div class="sign-block">
                    <div class="sign-label">Recorded by:</div>
                    <div><span class="sign-name">{{ $recorded_by['name'] }}</span></div>
                    <div class="sign-role">{{ $recorded_by['designation'] }}</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="approved">
        <div style="text-align:left;">Approved:</div>
        <div style="margin-top: 14px;">
            <span class="sign-name">{{ $approved_by['name'] }}</span>
        </div>
        <div class="sign-role">{{ $approved_by['designation'] }}</div>
    </div>

    <div class="provenance">
        {{ $slip_no }} · Generated {{ $generated_at }} by {{ $generated_by }}
    </div>
</div>

</body>
</html>
