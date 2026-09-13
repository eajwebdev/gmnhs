<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>IIRUP Report</title>

    <style>
        @page {
            size: 330.2mm 215.9mm;
            margin: 8mm 8mm 17mm;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 7pt;
            margin: 0;
        }

        table.form {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        thead {
            display: table-header-group;
        }

        tfoot {
            display: table-row-group;
        }

        tr {
            page-break-inside: avoid;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 2px 3px;
            line-height: 1.1;
            vertical-align: middle;
            word-wrap: break-word;
        }

        th {
            text-align: center;
            font-weight: bold;
        }

        .no-border {
            border: none;
        }

        .appendix {
            text-align: right;
            font-family: "Times New Roman", serif;
            font-size: 9pt;
        }

        .letterhead {
            width: 417px;
            height: 65px;
            margin: 0 auto 2px;
            background-image: url('{{ $letterhead }}');
            background-repeat: no-repeat;
            background-position: center;
            background-size: 417px 65px;
        }

        .title {
            text-align: center;
            font-weight: bold;
            font-size: 10pt;
            padding: 2px 0;
        }

        .center {
            text-align: center;
        }

        .right {
            text-align: right;
        }

        .top {
            vertical-align: top;
            white-space: pre-line;
        }

        .money {
            text-align: right;
            white-space: nowrap;
        }

        .small {
            font-size: 6pt;
        }

        .sig-name {
            font-weight: bold;
            font-size: 10.5pt;
            line-height: 1;
        }

        .sig-title {
            font-size: 8.5pt;
            line-height: 1;
        }

        .statement {
            font-family: Arial, sans-serif;
            font-size: 10.5pt;
            line-height: 1.1;
            text-align: left;
            padding: 3px 5px;
        }

        tfoot td {
            border: none;
            padding: 0;
            vertical-align: top;
        }

        .footer-row {
            height: 15.75pt;
        }

        .footer-total {
            height: 16.5pt;
        }

        .footer-frame-left {
            border-left: 2px solid #000 !important;
        }

        .footer-frame-right {
            border-right: 2px solid #000 !important;
        }

        .footer-statement-left {
            border-left: 2px solid #000 !important;
            border-right: 2px solid #000 !important;
            vertical-align: middle !important;
        }

        .footer-statement-center {
            border-left: 2px solid #000 !important;
        }

        .footer-statement-right {
            border-right: 2px solid #000 !important;
        }

        .footer-label {
            font-family: Arial, sans-serif;
            font-size: 10.5pt;
            text-align: left;
            vertical-align: bottom !important;
            padding-left: 1px !important;
            white-space: nowrap;
        }

        .footer-name {
            text-align: center;
            vertical-align: bottom !important;
            white-space: nowrap;
        }

        .footer-name-underlined {
            text-decoration: underline;
        }

        .footer-name-auditor {
            border-left: 2px solid #000 !important;
            border-bottom: 1px solid #000 !important;
        }

        .footer-name-right {
            border-right: 2px solid #000 !important;
            border-bottom: 1px solid #000 !important;
        }

        .footer-title {
            text-align: center;
            vertical-align: top !important;
        }

        .footer-title-auditor {
            border-left: 2px solid #000 !important;
            border-top: 1px solid #000 !important;
            font-family: "Times New Roman", serif;
        }

        .footer-title-right {
            border-right: 2px solid #000 !important;
            border-top: 1px solid #000 !important;
            font-family: "Times New Roman", serif;
        }

        .footer-bottom {
            border-bottom: 2px solid #000 !important;
        }

        /* The proportions follow columns A:R in public/IIRUP - FORM.xlsx. */
        .c-date { width: 3.55%; }
        .c-particular { width: 14.47%; }
        .c-property { width: 13.2%; }
        .c-qty { width: 1.73%; }
        .c-unit-cost { width: 5.28%; }
        .c-total-cost { width: 5.64%; }
        .c-depreciation { width: 5.69%; }
        .c-impairment { width: 4.62%; }
        .c-carrying { width: 4.67%; }
        .c-remarks { width: 4.21%; }
        .c-disposal { width: 4.62%; }
        .c-appraised { width: 4.62%; }
        .c-or { width: 4.62%; }
        .c-sale-amount { width: 4.62%; }
    </style>
</head>
<body>
@php
    $rows = collect($items ?? [])->values();
@endphp

<table class="form">
    <colgroup>
        <col class="c-date">
        <col class="c-particular">
        <col class="c-property">
        <col class="c-qty">
        <col class="c-unit-cost">
        <col class="c-total-cost">
        <col class="c-depreciation">
        <col class="c-impairment">
        <col class="c-carrying">
        <col class="c-remarks">
        <col class="c-disposal">
        <col class="c-disposal">
        <col class="c-disposal">
        <col class="c-disposal">
        <col class="c-disposal">
        <col class="c-appraised">
        <col class="c-or">
        <col class="c-sale-amount">
    </colgroup>

    <thead>
        <tr>
            <td colspan="18" class="no-border appendix">Appendix 74</td>
        </tr>
        <tr>
            <td colspan="18" class="no-border"><div class="letterhead"></div></td>
        </tr>
        <tr>
            <td colspan="18" class="no-border title">INVENTORY AND INSPECTION REPORT OF UNSERVICEABLE PROPERTY</td>
        </tr>
        <tr>
            <td colspan="18" class="no-border center">for the period {{ $period_label ?: '______________________' }}</td>
        </tr>
        <tr>
            <td colspan="14" class="no-border"><strong>Entity Name:</strong> Gil Montilla National High School</td>
            <td colspan="4" class="no-border"><strong>Fund Cluster</strong> {{ $fund_cluster ?: '_____________________' }}</td>
        </tr>
        <tr>
            <td colspan="4" class="no-border center">{{ strtoupper($accountable_name ?: '_____________________________') }}</td>
            <td colspan="4" class="no-border center">{{ $designation ?: '______________________________' }}</td>
            <td class="no-border"></td>
            <td colspan="4" class="no-border center">{{ $station ?: '_____________________________' }}</td>
            <td colspan="5" class="no-border"></td>
        </tr>
        <tr>
            <td colspan="4" class="no-border center small">(Name of Accountable Officer)</td>
            <td colspan="4" class="no-border center small">Designation</td>
            <td class="no-border"></td>
            <td colspan="4" class="no-border center small">(Station)</td>
            <td colspan="5" class="no-border"></td>
        </tr>
        <tr>
            <th colspan="10">INVENTORY</th>
            <th colspan="8">INSPECTION and DISPOSAL</th>
        </tr>
        <tr>
            <th rowspan="2">Date Acquired</th>
            <th rowspan="2">Particulars/ Articles</th>
            <th rowspan="2">Property No./ Serial Number</th>
            <th rowspan="2">Qty</th>
            <th rowspan="2">Unit Cost</th>
            <th rowspan="2">Total Cost</th>
            <th rowspan="2">Accumulated Depreciation</th>
            <th rowspan="2">Accumulated Impairment Losses</th>
            <th rowspan="2">Carrying Amount</th>
            <th rowspan="2">Remarks</th>
            <th colspan="5">DISPOSAL</th>
            <th rowspan="2">Appraised Value</th>
            <th colspan="2">RECORD OF SALES</th>
        </tr>
        <tr>
            <th>Sale</th>
            <th>Transfer</th>
            <th>Destruction</th>
            <th>Others (Specify)</th>
            <th>Total</th>
            <th>OR No.</th>
            <th>Amount</th>
        </tr>
        <tr>
            @for($i = 1; $i <= 18; $i++)
                <th>({{ $i }})</th>
            @endfor
        </tr>
    </thead>

    <tbody>
        @forelse($rows as $row)
            @php
                $particulars = [];
                if ($row['item_name']) { $particulars[] = $row['item_name']; }
                if ($row['description'] && strcasecmp($row['description'], (string) $row['item_name']) !== 0) { $particulars[] = $row['description']; }
                if ($row['model']) { $particulars[] = 'Model: '.$row['model']; }

                $property = [];
                if ($row['property_no']) { $property[] = $row['property_no']; }
                if ($row['serial_number']) { $property[] = 'SN: '.str_replace(';', ' / ', $row['serial_number']); }

                // Remarks carries the school/office instead of the logged remark,
                // which is why Particulars no longer repeats it.
                $remarks = array_filter([
                    $row['status'],
                    $row['office_name'] ? 'SCHOOL/OFFICE : '.$row['office_name'] : null,
                ]);
            @endphp
            <tr>
                <td class="center">{{ $row['date_acquired'] }}</td>
                <td class="top">{{ implode("\n", $particulars) }}</td>
                <td class="top">{{ implode("\n", $property) }}</td>
                <td class="center">{{ $row['qty'] + 0 }}</td>
                <td class="money">{{ number_format($row['unit_cost'], 2) }}</td>
                <td class="money">{{ number_format($row['total_cost'], 2) }}</td>
                <td class="money"></td>
                <td class="money"></td>
                <td class="money">{{ number_format($row['total_cost'], 2) }}</td>
                <td class="top">{{ implode(' - ', $remarks) }}</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        @empty
            <tr>
                <td colspan="18" class="center">No unserviceable or obsolete item logs match the selected filters.</td>
            </tr>
        @endforelse
    </tbody>

    <tfoot>
        <tr class="footer-total">
            <td colspan="3" class="center footer-frame-left footer-bottom"><strong>GRAND TOTAL</strong></td>
            <td class="footer-bottom"></td>
            <td class="footer-bottom"></td>
            <td class="money footer-bottom"><strong>{{ number_format($total_cost, 2) }}</strong></td>
            <td class="footer-bottom"></td>
            <td class="footer-bottom"></td>
            <td class="money footer-bottom"><strong>{{ number_format($total_cost, 2) }}</strong></td>
            <td class="footer-frame-right footer-bottom"></td>
            <td class="footer-frame-left footer-bottom"></td>
            <td class="footer-bottom"></td>
            <td class="footer-bottom"></td>
            <td class="footer-bottom"></td>
            <td class="footer-bottom"></td>
            <td class="footer-bottom"></td>
            <td class="footer-bottom"></td>
            <td class="footer-frame-right footer-bottom"></td>
        </tr>
        <tr class="footer-row">
            <td colspan="18" class="footer-frame-left footer-frame-right" style="border-top: 2px solid #000 !important;"></td>
        </tr>
        <tr class="footer-row">
            <td rowspan="3" colspan="10" class="statement footer-statement-left">
                I HEREBY request inspection and disposition, pursuant to Section 79 of PD 1445, of the property enumerated above.
            </td>
            <td rowspan="6" colspan="4" class="statement footer-statement-center">
                I CERTIFY that I have inspected each and every article enumerated in this report, and that the disposition made thereof was, in my judgment, the best for the public interest.
            </td>
            <td class="footer-blank"></td>
            <td rowspan="5" colspan="3" class="statement footer-statement-right">
                I CERTIFY that I have witnessed the disposition of the articles enumerated on this report this ____day of _____________, _____.
            </td>
        </tr>
        <tr class="footer-row">
            <td class="footer-blank"></td>
        </tr>
        <tr class="footer-row">
            <td class="footer-blank"></td>
        </tr>
        <tr class="footer-row">
            <td class="footer-frame-left footer-label">Requested by</td>
            <td colspan="4" class="footer-blank"></td>
            <td class="footer-label">Approved by</td>
            <td colspan="4" class="footer-frame-right footer-blank"></td>
            <td class="footer-blank"></td>
        </tr>
        <tr class="footer-row">
            <td colspan="10" class="footer-frame-left footer-frame-right footer-blank"></td>
            <td class="footer-blank"></td>
        </tr>
        <tr class="footer-row">
            <td colspan="10" class="footer-frame-left footer-frame-right footer-blank"></td>
            <td class="footer-blank"></td>
            <td colspan="3" class="footer-frame-right footer-blank"></td>
        </tr>
        <tr class="footer-row">
            <td class="footer-frame-left footer-blank"></td>
            <td colspan="3" class="footer-name sig-name footer-name-underlined">BENJAMIN R. DELA TORRE</td>
            <td class="footer-blank"></td>
            <td colspan="5" class="footer-name sig-name footer-name-underlined">ALEXANDRA M. VILLAROSA</td>
            <td colspan="4" class="footer-name sig-name footer-name-auditor">CAMILLE A. NAVARRETE</td>
            <td class="footer-blank"></td>
            <td colspan="3" class="footer-name sig-name footer-name-right"></td>
        </tr>
        <tr class="footer-row">
            <td class="footer-frame-left footer-blank"></td>
            <td colspan="3" class="footer-title sig-title">Administrative Officer V/ Head Supply Unit</td>
            <td class="footer-blank"></td>
            <td colspan="5" class="footer-title sig-title">School Principal</td>
            <td colspan="4" class="footer-title sig-title footer-title-auditor">State Auditor I/OIC- Audit Team Leader</td>
            <td class="footer-blank"></td>
            <td colspan="3" class="footer-title sig-title footer-title-right">State Auditor I/OIC- Audit Team Leader</td>
        </tr>
        <tr class="footer-row">
            <td class="footer-frame-left footer-blank"></td>
            <td colspan="3" class="footer-blank"></td>
            <td class="footer-blank"></td>
            <td colspan="5" class="footer-blank"></td>
            <td colspan="4" class="footer-frame-left footer-blank"></td>
            <td class="footer-blank"></td>
            <td colspan="3" class="footer-frame-right footer-blank"></td>
        </tr>
        <tr class="footer-row">
            <td colspan="10" class="footer-frame-left footer-frame-right footer-bottom"></td>
            <td colspan="4" class="footer-frame-left footer-bottom"></td>
            <td class="footer-bottom"></td>
            <td colspan="3" class="footer-frame-right footer-bottom"></td>
        </tr>
    </tfoot>
</table>
</body>
</html>
