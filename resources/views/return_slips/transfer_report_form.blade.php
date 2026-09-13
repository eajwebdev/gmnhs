@extends('layouts.master')

@section('body')
<div class="container-fluid return-slip-page">
    <div class="return-slip-hero">
        <div>
            <span class="return-slip-kicker">Supply Form 11 · GAM Appendix 76</span>
            <h1>Property Transfer Report</h1>
            <p>
                Built from the return-slip audit trail. Pick the end user who <strong>returned</strong>
                the property and the end user who <strong>received</strong> it - the report lists every
                item logged as travelling between them.
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
                <form id="ptrForm" action="{{ route('returnSlips.transferReport.generate') }}" method="GET" target="_blank">
                    <div class="card-body">
                        <div class="form-group">
                            <label>From - End User who returned the property <span class="text-danger">*</span></label>
                            <select name="from_enduser_id" id="ptrFromEnduser" class="form-control" style="width: 100%;" required>
                                <option value=""></option>
                                @include('return_slips.partials.enduser_options', ['custodians' => $custodians, 'others' => $others])
                            </select>
                            <small class="text-muted">
                                The previous accountable end user, taken from the <strong>Returned</strong>
                                entries on the return-slip trail.
                            </small>
                        </div>

                        <div class="form-group">
                            <label>To - End User who received the property <span class="text-danger">*</span></label>
                            <select name="to_enduser_id" id="ptrToEnduser" class="form-control" style="width: 100%;" required>
                                <option value=""></option>
                                @include('return_slips.partials.enduser_options', ['custodians' => $custodians, 'others' => $others])
                            </select>
                            <small class="text-muted">
                                The current accountable end user, taken from the <strong>Transferred</strong>
                                entries on the same trail.
                            </small>
                        </div>

                        <div class="alert alert-info py-2 px-3 mb-3" id="ptrScope" style="display:none;">
                            <span id="ptrScopeText"></span>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-6">
                                <label>Released From</label>
                                <input type="date" name="date_from" id="ptrDateFrom" class="form-control">
                            </div>
                            <div class="form-group col-6">
                                <label>Released To</label>
                                <input type="date" name="date_to" id="ptrDateTo" class="form-control">
                            </div>
                        </div>
                        <small class="text-muted d-block mb-3">
                            Filters on the date the release was logged. Leave blank for all records.
                        </small>

                        <div class="form-row">
                            <div class="form-group col-6">
                                <label>PTR No. <span class="text-danger">*</span></label>
                                <input type="text" name="ptr_no" id="ptrNo" class="form-control"
                                       placeholder="e.g. PTR-2026-0001" maxlength="100" required>
                            </div>
                            <div class="form-group col-6">
                                <label>Date</label>
                                <input type="date" name="ptr_date" class="form-control" value="{{ now()->toDateString() }}">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Fund Cluster</label>
                            <input type="text" name="fund_cluster" class="form-control" placeholder="e.g. 01 - Regular Agency Fund">
                        </div>

                        <div class="form-group">
                            <label>Transfer Type <small class="text-muted">(check only one)</small></label>
                            <select name="transfer_type" id="ptrTransferType" class="form-control">
                                <option value="">Auto (Relocate for a school office, Reassignment for a person)</option>
                                @foreach($transferTypes as $type)
                                    <option value="{{ $type }}">{{ $type }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group" id="ptrOtherWrap" style="display:none;">
                            <label>Others (Specify)</label>
                            <input type="text" name="transfer_type_other" class="form-control" maxlength="150">
                        </div>

                        <div class="form-group">
                            <label>Released/Issued By</label>
                            <select name="released_by" id="ptrReleasedBy" class="form-control">
                                <option value="">Use the end user who returned the property</option>
                                @foreach($releasedByOptions as $key => $signatory)
                                    <option value="{{ $key }}">{{ $signatory['name'] }} - {{ $signatory['designation'] }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">
                                Who signs the report under <strong>RELEASED/ISSUED BY</strong>.
                            </small>
                        </div>

                        <div class="form-group mb-0">
                            <label>Reason for Transfer</label>
                            <textarea name="reason" class="form-control" rows="3"
                                      placeholder="Leave blank to use the standard wording for the selected scope."></textarea>
                        </div>
                    </div>
                    <div class="card-footer d-flex flex-wrap">
                        <button type="button" class="btn btn-success mr-2 mb-1" id="ptrPreview">
                            <i class="fas fa-eye"></i> Preview PDF
                        </button>
                        <button type="submit" name="format" value="pdf" class="btn btn-outline-secondary mr-2 mb-1">
                            <i class="fas fa-external-link-alt"></i> Open in New Tab
                        </button>
                        <button type="submit" name="format" value="excel" class="btn btn-outline-success mb-1">
                            <i class="fas fa-file-excel"></i> Download Excel
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card return-slip-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">Preview</h3>
                    <span class="text-muted small" id="ptrPreviewMeta"></span>
                </div>
                <div class="card-body p-0">
                    <div id="ptrPreviewEmpty" class="text-center text-muted py-5">
                        <i class="fas fa-file-pdf fa-3x mb-3 d-block"></i>
                        Select an end user, then press <strong>Preview PDF</strong>.
                    </div>
                    <iframe id="ptrPreviewFrame" title="Property Transfer Report preview"
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
        var summaryUrl = '{{ route('returnSlips.transferReport.summary') }}';
        var generateUrl = '{{ route('returnSlips.transferReport.generate') }}';
        var $form = $('#ptrForm');
        var $from = $('#ptrFromEnduser');
        var $to = $('#ptrToEnduser');

        // Queue behind the layout's global $('.select2').select2() call so these
        // fields are not re-initialised on select2's own wrapper span.
        setTimeout(function () {
            if (!$.fn.select2) {
                return;
            }

            $from.select2({
                theme: 'bootstrap4',
                width: '100%',
                placeholder: 'Who returned the property?'
            });

            $to.select2({
                theme: 'bootstrap4',
                width: '100%',
                placeholder: 'Who received the property?'
            });
        }, 0);

        $('#ptrTransferType').on('change', function () {
            $('#ptrOtherWrap').toggle($(this).val() === 'Others');
        });

        function summarise() {
            if (!$from.val() || !$to.val()) {
                $('#ptrScope').hide();
                return;
            }

            $.getJSON(summaryUrl, {
                from_enduser_id: $from.val(),
                to_enduser_id: $to.val(),
                date_from: $('#ptrDateFrom').val(),
                date_to: $('#ptrDateTo').val()
            }).done(function (data) {
                if (!data || !data.ok) {
                    $('#ptrScope').hide();
                    return;
                }

                var text = data.scope + ' - <strong>' + data.item_count + '</strong> item(s), ' +
                    'PHP ' + data.total_amount + ' total.' +
                    '<br><span class="small">From: ' + data.from + ' (' + data.from_designation + ')' +
                    '<br>To: ' + data.to + ' (' + data.to_designation + ')</span>';

                if (data.same_person) {
                    text += '<br><span class="small text-danger">The two end users must be different.</span>';
                } else if (data.item_count === 0) {
                    text += '<br><span class="small text-danger">No property has travelled this route on the ' +
                        'return-slip trail yet - the form will print blank.</span>';
                }

                $('#ptrScopeText').html(text);
                $('#ptrScope').show();
            }).fail(function () {
                $('#ptrScope').hide();
            });
        }

        $from.add($to).on('change', summarise);
        $('#ptrDateFrom, #ptrDateTo').on('change', summarise);

        // Preview is not a submit, so the browser never runs its own required-field
        // checks for it. Both paths share one guard that mirrors the server rules.
        function readyToGenerate() {
            if (!$from.val()) {
                toastr.error('Select the end user who returned the property.');
                return false;
            }

            if (!$to.val()) {
                toastr.error('Select the end user who received the property.');
                return false;
            }

            if ($from.val() === $to.val()) {
                toastr.error('The receiving end user must be different from the one who returned the property.');
                return false;
            }

            if (!$.trim($('#ptrNo').val())) {
                toastr.error('Enter the PTR No. the report will be filed under.');
                $('#ptrNo').focus();
                return false;
            }

            return true;
        }

        $('#ptrPreview').on('click', function () {
            if (!readyToGenerate()) {
                return;
            }

            var params = $form.serialize() + '&format=pdf';

            $('#ptrPreviewEmpty').hide();
            $('#ptrPreviewFrame').attr('src', generateUrl + '?' + params).show();
            $('#ptrPreviewMeta').text('Rendered ' + new Date().toLocaleTimeString());
        });

        $form.on('submit', function (e) {
            if (!readyToGenerate()) {
                e.preventDefault();
            }
        });
    });
</script>
@endsection
