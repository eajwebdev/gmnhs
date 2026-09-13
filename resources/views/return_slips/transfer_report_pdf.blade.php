{{--
    Property Transfer Report (GAM Appendix 76) — the print/preview twin of
    public/Supply-Form-11-Property-Transfer-Report.xlsx. Rows come from the
    return-slip audit trail; see ReturnSlipController::buildTransferReportData().

    The item table flows across sheets with its heading repeated, exactly like the
    Excel export (which grows the table and repeats rows 1-18 as print titles).
    One PTR is one document, so the reason and the signatories are printed once at
    the end rather than on every sheet.
--}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>PROPERTY TRANSFER REPORT</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 9pt;
            margin: 0;
        }

        .appendix {
            text-align: right;
            font-family: "Times New Roman", serif;
            font-size: 10pt;
        }

        /*
            Declared once as a background so the inlined letterhead is carried in
            the stylesheet rather than repeated in the markup.
            The GMNHS logo is square, so the report constrains it to the header block.
        */
        .letterhead {
            width: 72%;
            height: 0;
            padding-bottom: 11.57%;
            margin: 0 auto;
            background-image: url('{{ $letterhead }}');
            background-repeat: no-repeat;
            background-position: center top;
            background-size: 100% auto;
        }

        .form-title {
            text-align: center;
            font-weight: bold;
            font-size: 11pt;
            margin: 6px 0 8px;
        }

        table.meta {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
        }

        table.meta td {
            font-size: 9pt;
            padding: 2px 0;
            vertical-align: bottom;
        }

        table.meta td.right {
            width: 34%;
            text-align: left;
        }

        .filled {
            border-bottom: 1px solid #000;
            display: inline-block;
            min-width: 190px;
            padding: 0 4px;
        }

        .filled.short { min-width: 120px; }

        .box {
            border: 1px solid #000;
            padding: 3px 6px;
        }

        table.types {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2px;
        }

        table.types td {
            font-size: 9pt;
            padding: 1px 0;
            width: 50%;
        }

        .tick {
            display: inline-block;
            width: 11px;
            height: 11px;
            border: 1px solid #000;
            text-align: center;
            line-height: 11px;
            font-size: 9pt;
            font-weight: bold;
            margin-right: 5px;
            vertical-align: middle;
        }

        table.items {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        table.items th,
        table.items td {
            border: 1px solid #000;
            padding: 2px 4px;
            font-size: 8pt;
            line-height: 1.15;
            vertical-align: middle;
            word-wrap: break-word;
        }

        table.items th {
            text-align: center;
            font-size: 9pt;
            height: 28px;
        }

        table.items td.amount { text-align: right; }

        /* Never let a single item be split across two sheets. */
        table.items tr { page-break-inside: avoid; }

        table.items tfoot td {
            font-weight: bold;
            font-size: 9pt;
        }

        .col-date { width: 11%; }
        .col-prop { width: 18%; }
        .col-descrip { width: 40%; }
        .col-amount { width: 14%; }
        .col-condition { width: 17%; }

        .descrip-name { font-weight: bold; }

        /*
            The form gives one merged cell for DESCRIPTION, so the model, serial and
            audit-trail reference are folded onto two small lines beneath the name.
        */
        .descrip-meta {
            display: block;
            font-size: 6.5pt;
            line-height: 1.2;
            color: #222;
        }

        /* The closing blocks sign the report once, so keep them together. */
        .closing { page-break-inside: avoid; }

        .reason-box {
            border: 1px solid #000;
            border-top: none;
            padding: 4px 6px;
            min-height: 52px;
        }

        .reason-text {
            margin: 3px 0 0;
            font-size: 9pt;
        }

        table.signatories {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
            border-top: none;
        }

        table.signatories td {
            font-size: 9pt;
            padding: 3px 5px;
            vertical-align: bottom;
        }

        table.signatories td.label {
            width: 15%;
            white-space: nowrap;
        }

        table.signatories td.block {
            width: 28.33%;
            border-bottom: 1px solid #000;
            text-align: center;
        }

        table.signatories tr.heading td {
            font-weight: bold;
            font-size: 9pt;
            text-align: center;
            border-bottom: none;
            padding-top: 5px;
        }

        table.signatories tr.sig-line td.block { height: 24px; }

        .sig-name {
            font-weight: bold;
            text-transform: uppercase;
        }

        .sig-designation { font-size: 8pt; }

        .provenance {
            margin-top: 4px;
            font-size: 7pt;
            font-style: italic;
            color: #333;
        }
    </style>
</head>
<body>

@php
    // The table carries exactly the items on the trail, closed by the TOTAL row.
    // The blank write-in lines of the paper form are kept in the .xlsx export,
    // which is the official template file; padding them here only ever pushed the
    // signatories onto a stray extra sheet.
    $rows = collect($items ?? [])->values();
@endphp

<div class="appendix">GAM-Appendix 76</div>

<div class="letterhead"></div>

<p class="form-title">PROPERTY TRANSFER REPORT</p>

<table class="meta">
    <tr>
        <td></td>
        <td class="right">Fund Cluster : <span class="filled short">{{ $fund_cluster ?: '' }}</span></td>
    </tr>
    <tr>
        <td>
            From Accountable Officer/Agency/Fund Cluster :
            <span class="filled">{{ $from_officer['name'] }}</span>
        </td>
        <td class="right">PTR No. : <span class="filled short">{{ $ptr_no }}</span></td>
    </tr>
    <tr>
        <td>
            To Accountable Officer/Agency/Fund Cluster :
            <span class="filled">{{ $to_officer['name'] }}</span>
        </td>
        <td class="right">Date : <span class="filled short">{{ $ptr_date }}</span></td>
    </tr>
</table>

<div class="box">
    <span>Transfer Type: (check only one)</span>
    <table class="types">
        <tr>
            <td><span class="tick">{{ $transfer_type === 'Donation' ? '/' : '' }}</span>Donation</td>
            <td><span class="tick">{{ $transfer_type === 'Relocate' ? '/' : '' }}</span>Relocate</td>
        </tr>
        <tr>
            <td><span class="tick">{{ $transfer_type === 'Reassignment' ? '/' : '' }}</span>Reassignment</td>
            <td>
                <span class="tick">{{ $transfer_type === 'Others' ? '/' : '' }}</span>Others (Specify)
                <span class="filled short">{{ $transfer_type === 'Others' ? $transfer_type_other : '' }}</span>
            </td>
        </tr>
    </table>
</div>

<table class="items">
    <thead>
        <tr>
            <th class="col-date">DATE ACQUIRED</th>
            <th class="col-prop">PROPERTY NUMBER</th>
            <th class="col-descrip">DESCRIPTION</th>
            <th class="col-amount">AMOUNT</th>
            <th class="col-condition">CONDITION OF PPE</th>
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $row)
            @php
                $spec = [];
                if ($row['model']) { $spec[] = 'MODEL: '.$row['model']; }
                if ($row['serial_number']) { $spec[] = 'SN: '.str_replace(';', ' / ', $row['serial_number']); }

                // The audit trail behind the row: which return slip, when it was
                // handed back, when it was released, and between which offices.
                $trail = [$row['slip_no']];

                if ($row['returned_at']) { $trail[] = 'returned '.$row['returned_at']; }
                if ($row['released_at']) { $trail[] = 'released '.$row['released_at']; }

                if ($row['from_office_name'] || $row['to_office_name']) {
                    $trail[] = ($row['from_office_name'] ?: '—').' → '.($row['to_office_name'] ?: '—');
                }
            @endphp
            <tr>
                <td>{{ $row['date_acquired'] }}</td>
                <td>{{ $row['property_no'] }}</td>
                <td>
                    <span class="descrip-name">{{ $row['item_name'] }}</span>
                    @if($row['descrip'] && strcasecmp($row['descrip'], (string) $row['item_name']) !== 0)
                        — {{ $row['descrip'] }}
                    @endif
                    @if($spec)
                        <span class="descrip-meta">{{ implode(' · ', $spec) }}</span>
                    @endif
                    <span class="descrip-meta">{{ implode(' · ', $trail) }}</span>
                </td>
                <td class="amount">{{ number_format($row['amount'], 2) }}</td>
                <td>{{ $row['condition'] }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="5" style="text-align:center; height: 34px;">
                    No property was returned by {{ $from_officer['name'] }}
                    and released to {{ $to_officer['name'] }} in this period.
                </td>
            </tr>
        @endforelse
    </tbody>

    @if($rows->isNotEmpty())
        <tfoot>
            <tr>
                <td colspan="3" style="text-align:right;">
                    TOTAL ({{ $rows->count() }} item{{ $rows->count() === 1 ? '' : 's' }})
                </td>
                <td class="amount">{{ number_format($total_amount, 2) }}</td>
                <td></td>
            </tr>
        </tfoot>
    @endif
</table>

<div class="closing">
    {{-- The box carries the reason typed on the form (or the standard wording
         when it was left blank); the printed label is not repeated. --}}
    <div class="reason-box">
        <p class="reason-text">{{ $reason }}</p>
    </div>

    <table class="signatories">
        <tr class="heading">
            <td class="label"></td>
            <td>APPROVED BY:</td>
            <td>RELEASED/ISSUED BY:</td>
            <td>RECEIVED BY:</td>
        </tr>
        <tr class="sig-line">
            <td class="label">Signature :</td>
            <td class="block"></td>
            <td class="block"></td>
            <td class="block"></td>
        </tr>
        <tr>
            <td class="label">Printed Name :</td>
            <td class="block"><span class="sig-name">{{ $approved_by['name'] }}</span></td>
            <td class="block"><span class="sig-name">{{ $released_by['name'] }}</span></td>
            <td class="block"><span class="sig-name">{{ $received_by['name'] }}</span></td>
        </tr>
        <tr>
            <td class="label">Designation :</td>
            <td class="block"><span class="sig-designation">{{ $approved_by['designation'] }}</span></td>
            <td class="block"><span class="sig-designation">{{ $released_by['designation'] }}</span></td>
            <td class="block"><span class="sig-designation">{{ $received_by['designation'] }}</span></td>
        </tr>
        <tr>
            <td class="label">Date :</td>
            <td class="block"></td>
            <td class="block"></td>
            <td class="block"></td>
        </tr>
    </table>

    <div class="provenance">
        {{ $scope_label }} · Period: {{ $period_label }} · Generated {{ $generated_at }} by {{ $generated_by }}
    </div>
</div>

</body>
</html>
