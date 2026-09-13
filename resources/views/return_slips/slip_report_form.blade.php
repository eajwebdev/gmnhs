{{--
    Property Return Slip report builder (Supply Form 10, revised for DRAR).

    The Property Transfer Report page, narrowed to one return: a return slip
    documents a single hand-back, so the only choice is which return to print.
    Preview renders the same PDF the print and Excel actions produce, in-page,
    from ReturnSlipController::returnSlipReport().
--}}
@extends('layouts.master')

@section('body')
<div class="container-fluid return-slip-page">
    <div class="return-slip-hero">
        <div>
            <span class="return-slip-kicker">Supply Form 10 · revised for DRAR</span>
            <h1>Property Return Slip</h1>
            <p>
                Pick a return and preview the official slip before printing it. The PDF and the
                Excel download are built from the same numbers, so what you see here is what the
                blank Supply Form 10 will carry.
            </p>
        </div>
        <a href="{{ route('returnSlips.index') }}" class="btn btn-light return-slip-action">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    </div>

    @if(session('error'))
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i> {{ session('error') }}
        </div>
    @endif

    <div class="row">
        <div class="col-lg-4">
            <div class="card return-slip-card">
                <div class="card-header">
                    <h3 class="card-title">Report Options</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Return to print <span class="text-danger">*</span></label>
                        <select id="prsSlip" class="form-control" style="width: 100%;">
                            @forelse($slips as $slip)
                                <option value="{{ $slip['id'] }}" @selected($slip['id'] === $selectedId)>
                                    {{ $slip['slip_no'] }} — {{ $slip['end_user'] }}{{ $slip['date'] ? ' · '.$slip['date'] : '' }}
                                </option>
                            @empty
                                <option value="">No returns have been recorded yet.</option>
                            @endforelse
                        </select>
                        <small class="text-muted">
                            Newest returns first. Cancelled items are left off the slip because
                            that return was undone.
                        </small>
                    </div>

                    <div class="alert alert-info py-2 px-3 mb-0" id="prsScope" style="display:none;">
                        <span id="prsScopeText"></span>
                    </div>
                </div>
                <div class="card-footer d-flex flex-wrap">
                    <button type="button" class="btn btn-success mr-2 mb-1" id="prsPreview"
                            @disabled($slips->isEmpty())>
                        <i class="fas fa-eye"></i> Preview PDF
                    </button>
                    <a href="#" target="_blank" class="btn btn-outline-secondary mr-2 mb-1" id="prsOpen"
                       @class(['disabled' => $slips->isEmpty()])>
                        <i class="fas fa-external-link-alt"></i> Open in New Tab
                    </a>
                    <a href="#" class="btn btn-outline-success mb-1" id="prsExcel"
                       @class(['disabled' => $slips->isEmpty()])>
                        <i class="fas fa-file-excel"></i> Download Excel
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card return-slip-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">Preview</h3>
                    <span class="text-muted small" id="prsPreviewMeta"></span>
                </div>
                <div class="card-body p-0">
                    <div id="prsPreviewEmpty" class="text-center text-muted py-5">
                        <i class="fas fa-file-pdf fa-3x mb-3 d-block"></i>
                        @if($slips->isEmpty())
                            Record a return first — there is nothing to print yet.
                        @else
                            Select a return, then press <strong>Preview PDF</strong>.
                        @endif
                    </div>
                    <iframe id="prsPreviewFrame" title="Property Return Slip preview"
                            style="display:none; width:100%; height:78vh; border:0;"></iframe>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(function () {
        var summaryUrl = '{{ route('returnSlips.slipReport.summary') }}';
        // The slip id sits in the path, so the route is generated once with a
        // placeholder and the selected id is substituted on each change.
        var reportUrlTemplate = '{{ route('returnSlips.slipReport', ['id' => '__ID__']) }}';
        var $slip = $('#prsSlip');

        function reportUrl(format) {
            var id = $slip.val();

            if (!id) {
                return null;
            }

            var url = reportUrlTemplate.replace('__ID__', encodeURIComponent(id));

            return format ? url + '?format=' + format : url;
        }

        // Queue behind the layout's global $('.select2').select2() call so this
        // field is not re-initialised on select2's own wrapper span.
        setTimeout(function () {
            if ($.fn.select2) {
                $slip.select2({
                    theme: 'bootstrap4',
                    width: '100%',
                    placeholder: 'Which return should be printed?'
                });
            }
        }, 0);

        function syncLinks() {
            var pdf = reportUrl(null);
            var excel = reportUrl('excel');

            $('#prsOpen').attr('href', pdf || '#').toggleClass('disabled', !pdf);
            $('#prsExcel').attr('href', excel || '#').toggleClass('disabled', !excel);
            $('#prsPreview').prop('disabled', !pdf);
        }

        function summarise() {
            if (!$slip.val()) {
                $('#prsScope').hide();
                return;
            }

            $.getJSON(summaryUrl, { slip: $slip.val() }).done(function (data) {
                if (!data || !data.ok) {
                    $('#prsScope').hide();
                    return;
                }

                var text = '<strong>' + data.slip_no + '</strong> — ' +
                    '<strong>' + data.item_count + '</strong> item(s), ' +
                    'PHP ' + data.total_value + ' total.' +
                    '<br><span class="small">End user: ' + data.end_user +
                    (data.office_name ? ' (' + data.office_name + ')' : '') +
                    '<br>Submitted: ' + (data.submitted_at || '—') + ' · Status: ' + data.status + '</span>';

                if (data.item_count === 0) {
                    text += '<br><span class="small text-danger">Every item on this return was ' +
                        'cancelled — the form will print blank.</span>';
                } else if (data.pages > 1) {
                    text += '<br><span class="small">Spills onto ' + data.pages +
                        ' sheets; the column headings repeat on each one.</span>';
                }

                $('#prsScopeText').html(text);
                $('#prsScope').show();
            }).fail(function () {
                $('#prsScope').hide();
            });
        }

        function render() {
            var pdf = reportUrl(null);

            if (!pdf) {
                toastr.error('Select the return you want to print.');
                return;
            }

            $('#prsPreviewEmpty').hide();
            $('#prsPreviewFrame').attr('src', pdf).show();
            $('#prsPreviewMeta').text('Rendered ' + new Date().toLocaleTimeString());
        }

        $slip.on('change', function () {
            syncLinks();
            summarise();

            // Once a preview is on screen, keep it in step with the selection
            // instead of leaving a stale slip showing.
            if ($('#prsPreviewFrame').is(':visible')) {
                render();
            }
        });

        $('#prsPreview').on('click', render);

        syncLinks();

        if ($slip.val()) {
            summarise();
            render();
        }
    });
</script>
@endsection
