<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>GMNHS Blank Property Stickers</title>
    <style>
        /* A4 portrait, 10 stickers per sheet (2 columns x 5 rows), sized for dompdf */
        @page { margin: 6mm 5mm; }

        body {
            margin: 0;
            font-family: "Times New Roman", Times, serif; /* dompdf core font: same metrics on every machine */
            font-size: 8pt;
            color: #000;
        }

        /* dompdf sizes cell content boxes: each slot is 94x53mm plus 1mm padding (2 cols = 192mm, 5 rows = 275mm), centred on A4 */
        table.sheet {
            border-collapse: collapse;
            page-break-inside: avoid;
            margin: 0 auto;
            table-layout: fixed;
            width: 192mm;
        }

        td.slot {
            height: 53mm;
            padding: 1mm;
            vertical-align: top;
            width: 94mm;
        }

        table.sticker {
            border-collapse: collapse;
            height: 53mm;
            width: 94mm;
        }

        .sticker th,
        .sticker td {
            border: 0.6pt solid #000;
            padding: 0 1.2mm;
            text-align: left;
            vertical-align: middle;
        }

        /* dompdf ignores column widths unless every cell in the column states one */
        .sticker .seal,
        .sticker .qr {
            text-align: center;
            width: 18%;
        }

        .sticker .school,
        .sticker .field {
            width: 82%;
        }

        .sticker .seal img {
            height: 8mm;
            width: 8mm;
        }

        .sticker .school {
            font-size: 9.5pt;
            font-weight: bold;
            height: 10mm;
            text-align: center;
            white-space: nowrap;
        }

        .sticker .qr span {
            border: 0.6pt dashed #000;
            display: inline-block;
            font-size: 5.5pt;
            height: 14mm;
            line-height: 14mm;
            width: 14mm;
        }

        .sticker .field {
            white-space: nowrap;
            font-size: 7.2pt;
            font-weight: bold;
            height: 3.7mm;
            line-height: 3.2mm;
        }

        .sticker .note {
            font-size: 6.4pt;
            font-style: italic;
            height: 3.4mm;
            line-height: 3.2mm;
            text-align: center;
        }

        .band-lightgreen { background: #8ceb8c; }
        .band-green { background: #008000; color: #fff; }
        .band-green th, .band-green td { border-color: #000; }
        .band-green .qr span { border-color: #fff; }
        .band-yellow { background: #ffff00; }

        .page-break { page-break-after: always; }
    </style>
</head>
<body>
@php
    // Same colour bands as the printed property stickers
    $bands = [
        'lightgreen' => 'Below 5,000',
        'green' => '5,000 to 49,999',
        'yellow' => '50,000 and above',
    ];
    $fields = ['Property No.', 'Item', 'Classification', 'Model/Brand', 'Serial No.', 'Acquisition Cost', 'Acquisition Date', 'Person Accountable', 'Assignment'];
    $seal = public_path('logo.png');
@endphp

@foreach ($bands as $band => $label)
    <table class="sheet">
        @for ($row = 0; $row < 5; $row++)
            <tr>
                @for ($col = 0; $col < 2; $col++)
                    <td class="slot">
                        <table class="sticker band-{{ $band }}">
                            <tr>
                                <th class="seal"><img src="{{ $seal }}" alt=""></th>
                                <th class="school">Gil Montilla National High School</th>
                            </tr>
                            @foreach ($fields as $field)
                                <tr>
                                    @if ($loop->first)
                                        <td class="qr" rowspan="{{ count($fields) + 1 }}"><span>QR</span></td>
                                    @endif
                                    <td class="field">{{ $field }}:</td>
                                </tr>
                            @endforeach
                            <tr>
                                <td class="field">Validation Sign:</td>
                            </tr>
                            <tr>
                                <td class="note" colspan="2">*Removing or tampering of this sticker is punishable by Law*</td>
                            </tr>
                        </table>
                    </td>
                @endfor
            </tr>
        @endfor
    </table>

    @unless ($loop->last)
        <div class="page-break"></div>
    @endunless
@endforeach
</body>
</html>
