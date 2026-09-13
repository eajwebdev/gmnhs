@extends('layouts.master')

@section('body')

<style>
.hidden {
    display: none;
}
.spinner {
    width: 40px;
    height: 40px;
    border: 5px solid #ccc;
    border-top: 5px solid #28a745;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                {{-- <form method="POST" class="m-4" action="{{ route('stickerReadPost') }}" id="officeForm">
                    @csrf
                    <div class="form-group">
                        <select name="office_id" id="office_id" class="form-control select2bs4" onchange="document.getElementById('officeForm').submit();">
                            <option value="">-- Select School/Office --</option>
                            @foreach ($offices as $office)
                                <option value="{{ $office->id }}" {{ isset($selectedOffice) && $selectedOffice->id == $office->id ? 'selected' : '' }}>
                                    {{ $office->office_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </form>
                <div class="card-body">
                    @if(isset($selectedOffice))
                        <iframe src="{{ route('stickerReadPdf', $selectedOffice->id) }}" width="100%" height="510"></iframe>
                    @endif
                </div> --}}

                <div class="form-row m-3">
                    <div class="form-group col-md-3">
                        <label for="rowRange">Select Range</label>
                        <select name="rowRange" id="rowRange" class="form-control select2bs4">
                            <option value="1-1000" selected>1-1000</option>
                            <option value="1001-2000">1001-2000</option>
                            <option value="2001-3000">2001-3000</option>
                            <option value="3001-4000">3001-4000</option>
                            <option value="4001-5000">4001-5000</option>
                            <option value="5001-6000">5001-6000</option>
                            <option value="6001-7000">6001-7000</option>
                            <option value="7001-8000">7001-8000</option>
                        </select>
                    </div>
                    <div class="form-group col-md-9">
                        <label for="office_id">School/Office</label>
                        <select name="office_id" id="office_id" class="form-control select2bs4">
                            <option value="">-- Select School/Office --</option>
                            @foreach ($offices as $office)
                                <option value="{{ $office->id }}">
                                    {{ $office->office_name }} - {{ number_format($office->property_count) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="card-body" style="margin-top: -30px;">
                    <div class="d-flex flex-wrap mb-3" style="gap: 8px;">
                        <button id="printBtn" class="btn btn-success" disabled><i class="fas fa-print"></i> Print Stickers</button>
                        <a id="downloadPdfBtn" href="#" class="btn btn-default disabled" aria-disabled="true"><i class="fas fa-file-pdf"></i> Download PDF</a>
                        <span id="stickerCount" class="count-pill align-self-center" hidden></span>
                    </div>
                    <div id="preloader" style="display: none; text-align: center; padding: 2rem;">
                        <div class="spinner"></div>
                        <div style="margin-top: 10px;">Loading stickers... Please wait.</div>
                    </div>
                    <div id="stickerPreviewIframe" style="width: 100%; height: 560px; border: 1px solid var(--line); border-radius: 8px; overflow-y: auto; background: #fff;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const stickerRouteTemplate = "{{ route('stickerReadJson', ['range' => '__RANGE__', 'office' => '__OFFICE__']) }}";
    const stickerPdfRoute = "{{ route('stickers.pdf') }}";
    const logoPath = "{{ asset('logo.png') }}";

    function esc(value) {
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };
        return String(value ?? '').replace(/[&<>"']/g, ch => map[ch]);
    }

    function setStickerButtons(enabled, officeId, rowRange, count) {
        $('#printBtn').prop('disabled', !enabled);
        $('#downloadPdfBtn')
            .toggleClass('disabled', !enabled)
            .attr('aria-disabled', String(!enabled))
            .attr('href', enabled ? stickerPdfRoute + '?office=' + encodeURIComponent(officeId) + '&range=' + encodeURIComponent(rowRange) : '#');
        $('#stickerCount').prop('hidden', !enabled).text(enabled ? count + ' sticker' + (count === 1 ? '' : 's') : '');
    }

    let generatedHtml = '';

    $(document).ready(function () {
        function loadStickers() {
            const officeId = $('#office_id').val();
            const rowRange = $('#rowRange').val() || '1-1000';

            // Show preloader and disable buttons.
            $('#preloader').show();
            setStickerButtons(false);
            $('#stickerPreviewIframe').empty();

            if (!officeId) {
                $('#preloader').hide();
                $('#stickerPreviewIframe').html('<div class="no-data">Please select a school/office.</div>');
                return;
            }

            const url = stickerRouteTemplate
                .replace('__RANGE__', encodeURIComponent(rowRange))
                .replace('__OFFICE__', encodeURIComponent(officeId));

            $.ajax({
                url: url,
                type: 'GET',
                success: function (response) {
                    const properties = response.stickers;

                    if (!properties || !properties.length) {
                        $('#preloader').hide();
                        $('#stickerPreviewIframe').html('<div class="no-data text-danger ml-2 mt-1">No sticker data available.</div>');
                        return;
                    }

                    let html = `
                    <div id="printableArea">
                       <style>
                            @media print {
                                @page {
                                    margin: 0;
                                    size: A4;
                                }

                                * {
                                    -webkit-print-color-adjust: exact !important;
                                    print-color-adjust: exact !important;
                                }

                                body {
                                    margin: 0;
                                    font-size: 8.35pt;
                                }

                                .page-break {
                                    page-break-after: always;
                                }
                            }

                            body {
                                margin: 0;
                                padding: 0;
                                font-family: 'Bookman Old Style', Georgia, serif;
                                font-size: 8.35pt;
                            }

                            .text-light-mod{
                                color: #FFFF !important;
                            }

                            .layout {
                                width: 100%;
                                border-collapse: collapse;
                                table-layout: fixed;
                                page-break-inside: avoid;
                            }

                            .sticker-cell {
                                width: 50%;
                                height: 280 px;
                                padding: 2px;
                                vertical-align: top;
                                box-sizing: border-box;
                            }

                            .sticker-wrapper {
                                height: 100%;
                                max-height: 280 px;
                                box-sizing: border-box;
                                overflow: hidden;
                                display: flex;
                                flex-direction: column;
                                justify-content: space-between;
                            }

                            #sticker {
                                width: 100%;
                                height: 100%;
                                border-collapse: collapse;
                                table-layout: fixed;
                                font-size: 8.37pt;
                                color: #000;
                            }

                            #sticker th, #sticker td {
                                border: 1px solid #000;
                                padding: 1px;
                                text-align: left;
                                vertical-align: top;
                                overflow: hidden;
                                white-space: nowrap;
                                text-overflow: ellipsis;
                            }

                            .logo-sticker {
                                width: 30px;
                                height: 30px;
                                text-align: center;
                            }

                            .label-inline {
                                display: flex;
                                align-items: center;
                                gap: 4px;
                                overflow: hidden;
                                white-space: nowrap;
                            }

                            .dataText-inline {
                                flex-grow: 1;
                                display: inline-block;
                                overflow: hidden;
                                text-overflow: ellipsis;
                                white-space: nowrap;
                            }

                            .sticker-text-label {
                                text-align: center;
                            }

                            .bg-yellow {
                                background-color: yellow;
                            }

                            .bg-green {
                                background-color: #008000;
                            }

                            .bg-lightgreen {
                                background-color: #8ceb8c;
                            }

                            .no-data {
                                padding: 1rem;
                                font-family: sans-serif;
                                color: red;
                            }
                        </style>
                    `;

                    const chunks = [];
                    for (let i = 0; i < properties.length; i += 10) {
                        chunks.push(properties.slice(i, i + 10));
                    }

                    chunks.forEach((group, chunkIndex) => {
                        html += '<table class="layout">';
                        for (let r = 0; r < group.length; r += 2) {
                            const row = group.slice(r, r + 2);
                            html += '<tr>';
                            row.forEach(inventory => {
                                const serial = (inventory.serial_number || 'N/A').split(';')[0].trim();
                                const cost = parseFloat(inventory.item_cost || 0);
                                const bgClass = cost <= 5000 ? 'bg-lightgreen' : (cost < 50000 ? 'bg-green text-light-mod' : 'bg-yellow');

                                html += `
                                <td class="sticker-cell">
                                    <div class="sticker-wrapper ${bgClass}">
                                        <table id="sticker">
                                            <thead>
                                                <tr>
                                                    <th class="sticker-text-label" style="width: 55px; text-align: center;">
                                                        <img src="${logoPath}" class="logo-sticker">
                                                    </th>
                                                    <th colspan="4" class="sticker-text-label ${(cost > 5000 && cost < 50000) ? 'text-light-mod' : ''}" style="font-size: 12px; text-align: center; vertical-align: middle;">Gil Montilla National High School</th>
                                                </tr>
                                                <tr>
                                                    <th rowspan="10" class="sticker-text-label" style="text-align: center; vertical-align: middle;"><img src="data:image/png;base64,${inventory.qr_base64}" width="55"></th>
                                                    <th colspan="4"><div class="label-inline"><span class="dataText-inline ${(cost > 5000 && cost < 50000) ? 'text-light-mod' : ''}">Property No.: <i>${esc(inventory.property_no_generated)}</i></span></div></th>
                                                </tr>
                                                <tr><th colspan="4"><div class="label-inline"><span class="dataText-inline ${(cost > 5000 && cost < 50000) ? 'text-light-mod' : ''}">Item: <i>${esc(titleCase(inventory.item_name))}</i></span></div></th></tr>
                                                <tr><th colspan="4"><div class="label-inline"><span class="dataText-inline ${(cost > 5000 && cost < 50000) ? 'text-light-mod' : ''}">Classification: <i>${esc(inventory.account_title_abbr)}</i></span></div></th></tr>
                                                <tr><th colspan="4"><div class="label-inline"><span class="dataText-inline ${(cost > 5000 && cost < 50000) ? 'text-light-mod' : ''}">Model/Brand: <i>${esc(inventory.item_model)}</i></span></div></th></tr>
                                                <tr><th colspan="4"><div class="label-inline"><span class="dataText-inline ${(cost > 5000 && cost < 50000) ? 'text-light-mod' : ''}">Serial No.: <i>${esc(serial)}</i></span></div></th></tr>
                                                <tr><th colspan="4"><div class="label-inline"><span class="dataText-inline ${(cost > 5000 && cost < 50000) ? 'text-light-mod' : ''}">Acquisition Cost: <i>${cost.toLocaleString('en-PH', { style: 'currency', currency: 'PHP' })}</i></span></div></th></tr>
                                                <tr><th colspan="4"><div class="label-inline"><span class="dataText-inline ${(cost > 5000 && cost < 50000) ? 'text-light-mod' : ''}">Acquisition Date: <i>${esc(inventory.date_acquired)}</i></span></div></th></tr>
                                                <tr><th colspan="4"><div class="label-inline"><span class="dataText-inline ${(cost > 5000 && cost < 50000) ? 'text-light-mod' : ''}">Person Accountable: <i>${esc(titleCase(inventory.person_accnt_fname2 || inventory.person_accnt_fname1 || ''))}</i></span></div></th></tr>
                                                <tr><th colspan="4"><div class="label-inline"><span class="dataText-inline ${(cost > 5000 && cost < 50000) ? 'text-light-mod' : ''}">Assignment: <i>${esc(titleCase(inventory.office_name))}</i></span></div></th></tr>
                                                <tr><th colspan="4"><div class="label-inline"><span class="dataText-inline ${(cost > 5000 && cost < 50000) ? 'text-light-mod' : ''}">Validation Sign:</span></div></th></tr>
                                            </thead>
                                            <tfoot>
                                                <tr><td colspan="5" class="sticker-text-label text-center ${(cost > 5000 && cost < 50000) ? 'text-light-mod' : ''}">*Removing or tampering of this sticker is punishable by Law*</td></tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </td>`;
                            });

                            if (row.length < 2) {
                                html += '<td class="sticker-cell"></td>';
                            }

                            html += '</tr>';
                        }
                        html += '</table>';
                        if (chunkIndex < chunks.length - 1) {
                            html += '<div class="page-break"></div>';
                        }
                    });

                    html += '</div>';

                    $('#stickerPreviewIframe').html(html);
                    generatedHtml = html;

                    // Re-enable buttons and hide preloader.
                    setStickerButtons(true, officeId, rowRange, properties.length);
                    $('#preloader').hide();
                },
                error: function (xhr) {
                    $('#preloader').hide();
                    setStickerButtons(false);
                    const message = xhr.responseJSON?.error || xhr.statusText || 'An error occurred while loading data.';
                    $('#stickerPreviewIframe').html(`<div class="no-data"><strong>Error ${xhr.status}:</strong> ${esc(message)}</div>`);
                }
            });
        }

        $('#office_id').on('change', loadStickers);
        $('#rowRange').on('change', function () {
            if ($('#office_id').val()) {
                loadStickers();
            }
        });

        $('#printBtn').on('click', function () {
            const win = window.open('', '_blank');
            if (!win) {
                Swal.fire({ titleText: 'Pop-up blocked', text: 'Allow pop-ups for this site to print stickers.', icon: 'warning' });
                return;
            }
            win.document.write(`<html><head><title>GMNHS Property Stickers</title></head><body>${generatedHtml}</body></html>`);
            win.document.close();

            // Wait for the logo and QR images, otherwise they print blank
            const images = Array.from(win.document.images);
            Promise.all(images.map(img => img.complete ? Promise.resolve() : new Promise(done => { img.onload = img.onerror = done; })))
                .then(() => { win.focus(); win.print(); });
        });

        function titleCase(str) {
            return str?.toLowerCase().replace(/\b(\w)/g, s => s.toUpperCase()) || '';
        }
    });
</script>



@endsection

