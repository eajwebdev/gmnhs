@extends('layouts.master')

@section('body')

@php $cr = request()->route()->getName(); @endphp

<style>
    #parReport .select2-container--bootstrap4 .select2-selection--multiple {
        height: auto !important;
        min-height: 38px;
        max-height: 120px;
        overflow-y: auto;
        overflow-x: hidden;
    }

    #parReport .select2-container--bootstrap4 .select2-selection--multiple .select2-selection__rendered {
        display: block;
        max-height: 110px;
        overflow-y: auto;
        overflow-x: hidden;
        padding-bottom: 4px;
    }

    #parReport .select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice {
        max-width: calc(100% - 8px);
        white-space: normal;
        word-break: break-word;
    }

    #parReport .report-show-options,
    #parReport .report-action-buttons {
        clear: both;
        position: relative;
        z-index: 2;
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-file-pdf"></i> Reports
                    </h3>
                </div>
                <div class="card-body" style="background-color: #cfd6df;">

                <form action="{{ route('generateReport') }}" class="form-horizontal add-form" id="parReport" method="POST" target="_blank">
                    @csrf
                    <div class="form-group">
                        <div class="form-row">

                            <!-- Type -->
                            <div class="col-md-3">
                                <label>Type:</label>
                                <select class="form-control select2bs4" name="report_type" id="report_type" style="width: 100%;">
                                    @php $presetType = (string) request('type'); @endphp
                                    <option disabled @selected(!in_array($presetType, ['1', '2', '3', '4', '5'], true)) value=""> --- select --- </option>
                                    <option value="1" @selected($presetType === '1')>RPCPPE</option>
                                    <option value="2" @selected($presetType === '2')>RPCSEP</option>
                                    <option value="3" @selected($presetType === '3')>ICS</option>
                                    <option value="4" @selected($presetType === '4')>PAR</option>
                                    <option value="5" @selected($presetType === '5')>IIRUP</option>
                                </select>
                            </div>

                            <!-- Property Type -->
                            <div class="col-md-6">
                                <label>Property Type:</label>
                                <select class="form-control select2bs4" id="properties_id" name="properties_id[]" style="width: 100%;" multiple>
                                </select>
                            </div>

                            <!-- School or Office -->
                            <div class="col-md-3">
                                <label>School or Office:</label>
                                @php $canSelectMultipleOffice = in_array(auth()->user()->role, ['Administrator', 'Supply Officer']); @endphp
                                <select class="form-control select2bs4" id="office_id" name="office_id{{ $canSelectMultipleOffice ? '[]' : '' }}" style="width: 100%;" @if($canSelectMultipleOffice) multiple @endif>
                                    {{-- SCHOOL-scoped users report on their own SCHOOL only, so "All"
                                         would be misleading - their SCHOOL is pre-selected instead. --}}
                                    @if($canSelectMultipleOffice)
                                        <option value="All" selected>All School Offices</option>
                                    @endif
                                    @foreach ($office as $data)
                                        @if($data->office_code != '0000')
                                            <option value="{{ $data->id }}" @if(!$canSelectMultipleOffice && $loop->first) selected @endif>{{ $data->office_abbr }} - {{ $data->office_name }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>

                            <!-- Location -->
                            <div class="col-md-3 mt-2">
                                <label>Location:</label>
                                <select class="form-control select2bs4" id="location" name="location" style="width: 100%;">
                                    <option disabled selected value=""> --- select --- </option>
                                    <option value="All">All</option>
                                </select>
                            </div>

                            <!-- Category -->
                            <div class="col-md-3 mt-2">
                                <label>Category:</label>
                                <select id="category_id" name="categories_id" onchange="categor(this.value)"
                                        class="form-control select2bs4" style="width: 100%;">
                                    <option disabled selected value=""> --- select --- </option>
                                    <option value="All">All</option>
                                    @foreach ($category as $data)
                                        <option value="{{ $data->cat_code }}">{{ $data->cat_code }} - {{ $data->cat_name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Account Title -->
                            <div class="col-md-3 mt-2">
                                <label>Account Title:</label>
                                <select id="property_id" name="property_id" class="form-control select2bs4" style="width: 100%;">
                                    <option disabled selected value=""> --- select --- </option>
                                    <option value="All">All</option>
                                </select>
                            </div>

                            <!-- Date Range -->
                            <div class="col-md-3 mt-2">
                                <label>Date Range:</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                                    </div>
                                    <input type="text" name="date_range" id="date_range" class="form-control" placeholder="All Dates">
                                </div>
                            </div>

                            <!-- Person Accountable -->
                            <div class="col-md-6 mt-2">
                                <label>Person Accountable:</label>
                                <select class="form-control select2bs4" id="person_accntable" name="person_accnt" style="width: 100%;">
                                    <option disabled selected value=""> --- select --- </option>
                                </select>
                            </div>

                            <!-- End User -->
                            <div class="col-md-6 mt-2">
                                <label>End User:</label>
                                <select class="form-control select2bs4" id="enduser" name="person_accnt1" style="width: 100%;">
                                    <option disabled selected value=""> --- select --- </option>
                                </select>
                            </div>

                            <!-- Items -->
                            <div class="col-md-12 mt-2">
                                <label>Item:</label>
                                <select class="form-control select2bs4" multiple="multiple" id="item_id" name="item_id[]" style="width: 100%;">
                                    <option disabled selected value=""> --- select --- </option>
                                    <option value="All">All Items</option>
                                </select>
                            </div>

                            <!-- Show Columns -->
                            <div class="col-md-5 mt-3 report-show-options">
                                <label class="fw-bold">SHOW:</label>

                                <div class="row mt-2">
                                    
                                    {{-- COLUMN 1 --}}
                                    <div class="col-md-4">
                                        <strong>Table Columns</strong>

                                        <div class="form-check">
                                            <input type="checkbox" name="columns[]" value="location" class="form-check-input" id="col_location">
                                            <label class="form-check-label" for="col_location">Location</label>
                                        </div>

                                        <div class="form-check">
                                            <input type="checkbox" name="columns[]" value="serial_number" class="form-check-input" id="col_serial">
                                            <label class="form-check-label" for="col_serial">Serial</label>
                                        </div>

                                        <div class="form-check">
                                            <input type="checkbox" name="columns[]" value="date_acquired" class="form-check-input" id="col_date_acquired">
                                            <label class="form-check-label" for="col_date_acquired">Date Acquired</label>
                                        </div>
                                    </div>

                                    {{-- COLUMN 2 --}}
                                    <div class="col-md-4">
                                        <strong>Amount Options</strong>

                                        <div class="form-check">
                                            <input type="checkbox" name="balance_bforward" value="1" class="form-check-input" id="col_bforward">
                                            <label class="form-check-label" for="col_bforward">Balance Brought Forward</label>
                                        </div>

                                        <div class="form-check">
                                            <input type="checkbox" name="eachpage_subtotal" value="1" class="form-check-input" id="col_eachpage_subtotal">
                                            <label class="form-check-label" for="col_eachpage_subtotal">Subtotal</label>
                                        </div>

                                        <div class="form-check">
                                            <input type="checkbox" name="grand_total" value="1" class="form-check-input" id="col_grand_total">
                                            <label class="form-check-label" for="col_grand_total">Grand Total</label>
                                        </div>
                                    </div>

                                    {{-- COLUMN 3 --}}
                                    <div class="col-md-4">
                                        <strong>Page Options</strong>
                                        <div class="form-check">
                                            <input type="checkbox" name="eachpage_header" value="1" class="form-check-input" id="col_eachpage_header">
                                            <label class="form-check-label" for="col_eachpage_header">Header Each Page</label>
                                        </div>

                                        <div class="form-check">
                                            <input type="checkbox" name="eachpage_footer" value="1" class="form-check-input" id="col_eachpage_footer">
                                            <label class="form-check-label" for="col_eachpage_footer">Footer Each Page</label>
                                        </div>
                                    </div>

                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="form-group mt-4 report-action-buttons">
                        <button type="reset" class="btn btn-danger">Reset</button>
                        <button type="submit" name="format" value="pdf" class="btn btn-success">
                            <i class="fas fa-file-pdf"></i> Generate PDF
                        </button>
                        <button type="submit" name="format" value="excel" class="btn btn-primary">
                            <i class="fas fa-file-excel"></i> Generate Excel
                        </button>
                    </div>
                </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(function() {
    $('.select2bs4').select2({
        theme: 'bootstrap4',
        placeholder: '--- Select ---',
        allowClear: true
    });

    const currentMonthStart = moment().startOf('month');
    const currentMonthEnd = moment().endOf('month');
    const currentMonthRange = currentMonthStart.format('YYYY-MM-DD') + ' - ' + currentMonthEnd.format('YYYY-MM-DD');

    // Date Range Picker
    $('#date_range').daterangepicker({
        startDate: currentMonthStart,
        endDate: currentMonthEnd,
        autoUpdateInput: true,
        locale: { cancelLabel: 'Clear', format: 'YYYY-MM-DD' }
    });
    $('#date_range').val(currentMonthRange);

    $('#date_range').on('apply.daterangepicker', function(ev, picker) {
        const dateString = picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD');
        $(this).val(dateString);
        loadItems();
    });

    $('#date_range').on('cancel.daterangepicker', function() {
        $(this).val('');
        loadItems();
    });

    // Report Type Change
    $('#report_type').on('change', function() {
        const reportType = $(this).val();
        const $propSelect = $('#properties_id');

        $propSelect.html('<option value="" disabled selected>Loading...</option>').trigger('change.select2');

        if (!reportType) return;

        $.get("{{ route('genPropType', 0) }}".replace('0', reportType), function(response) {
            $propSelect.html(response).trigger('change.select2');
            refreshAccountTitles();
        });
    });

    // Office Change → generateMix
    $('#office_id').on('change', function() {
        let officeId = $(this).val();

        // "All" (all schools & offices) is mutually exclusive with specific offices
        if (Array.isArray(officeId) && officeId.length > 1 && officeId.includes('All')) {
            // If "All" was just added, keep only "All"; otherwise drop "All" in favor of specific offices
            officeId = officeId[officeId.length - 1] === 'All'
                ? ['All']
                : officeId.filter(id => id !== 'All');
            $(this).val(officeId).trigger('change.select2');
        }

        // Supports single (string) or multiple (array) office selection
        const officeIds = Array.isArray(officeId)
            ? officeId.filter(id => id !== null && id !== '')
            : (officeId ? [officeId] : []);

        $('#location, #person_accntable, #enduser, #item_id').each(function() {
            $(this).html('<option value="" disabled selected>Loading...</option>').trigger('change.select2');
        });

        if (officeIds.length === 0) {
            resetSelects();
            return;
        }

        $.get("{{ route('generateMix', 0) }}".replace('0', officeIds.join(',')), function(response) {
            // Keep "All" and append real locations from backend
            let locationHtml = '<option disabled value=""> --- select --- </option>';
            locationHtml += '<option value="All">All</option>';
            locationHtml += response.selectlocation.replace('<option disabled value=""> --- select --- </option>', '');

            $('#location').html(locationHtml).trigger('change.select2');
            let accountableOptions = (response.selectedaccntable || '')
                .replace('<option disabled value=""> --- select --- </option>', '')
                .replace('<option value="All" selected>All</option>', '')
                .replace('<option value="All">All</option>', '');
            let accountableHtml = '<option disabled value=""> --- select --- </option><option value="All" selected>All</option>';
            accountableHtml += accountableOptions;

            $('#person_accntable').html(accountableHtml).trigger('change.select2');
            $('#enduser').html(response.selectedenduser).trigger('change.select2');
            $('#item_id').html(response.selectitems).trigger('change.select2');

            loadItems();
        });
    });

    $('#parReport').on('reset', function() {
        setTimeout(function() {
            $('#date_range').data('daterangepicker').setStartDate(currentMonthStart);
            $('#date_range').data('daterangepicker').setEndDate(currentMonthEnd);
            $('#date_range').val(currentMonthRange);
            $('.select2bs4').trigger('change.select2');
            loadItems();
        }, 0);
    });

    // Trigger loadItems on field change
    $('#report_type, #properties_id, #location, #category_id, #property_id, #person_accntable, #enduser, #date_range')
        .on('change', loadItems);

    // Changing the property types changes which account titles exist.
    $('#properties_id').on('change', refreshAccountTitles);

    // Initial load
    if ($('#report_type').val()) $('#report_type').trigger('change');
    if ($('#office_id').val()) $('#office_id').trigger('change');
});

// ====================== CATEGOR FUNCTION ======================
// PPE, semi-expendable high/low value and intangible account titles all live in
// the same table and differ only by property type, so the account list has to
// follow whatever property types the report is being generated for.
function selectedPropertyTypeMode() {
    const clean = ids => (Array.isArray(ids) ? ids : [ids])
        .filter(id => id !== null && id !== '' && id !== 'All');

    let ids = clean($('#properties_id').val() || []);

    if (!ids.length) {
        // Nothing picked yet - fall back to every type this report allows.
        ids = clean($('#properties_id option').map(function () { return this.value; }).get());
    }

    return ids.length ? ids.join(',') : '0';
}

// Re-runs the account lookup for the category already chosen.
function refreshAccountTitles() {
    const category = $('#category_id').val();

    if (category && category !== 'All') {
        categor(category);
    }
}

function categor(val) {
    const $account = $('#property_id');
    const emptyOptions = '<option disabled selected value=""> --- select --- </option><option value="All">All</option>';

    if (!val || val === "All") {
        $account.html(emptyOptions).trigger('change.select2');
        loadItems();
        return;
    }

    // Keep the current choice when the list is only being refreshed.
    const previous = $account.val();

    const url = "{{ route('invCatIcsPar', [':id', ':mode']) }}"
        .replace(':id', val)
        .replace(':mode', selectedPropertyTypeMode()) + '?unique_code=1';

    $account.html('<option disabled selected value="">Loading...</option>').trigger('change.select2');

    $.get(url, function(response) {
        $account.html(response.options || emptyOptions);

        if (previous && $account.find('option[value="' + previous + '"]').length) {
            $account.val(previous);
        }

        $account.trigger('change.select2');
        loadItems();
    });
}

// ====================== LOAD ITEMS WITH CONSOLE LOGGING ======================
let loadTimeout = null;

function loadItems() {
    clearTimeout(loadTimeout);

    loadTimeout = setTimeout(() => {
        const $itemSelect = $('#item_id');
        $itemSelect.html('<option value="">Loading items...</option>').trigger('change.select2');

        const requestData = {
            report_type:   $('#report_type').val() || '',
            office_id:     $('#office_id').val() || '',
            location:      $('#location').val() || '',
            person_accnt:  $('#person_accntable').val() || '',
            person_accnt1: $('#enduser').val() || '',
            categories_id: $('#category_id').val() || '',
            properties_id: $('#properties_id').val() || [],
            property_id:   $('#property_id').val() || '',
            date_range:    $('#date_range').val() || ''
        };

        console.group('%c🚀 loadItems() Called', 'color: #d63384; font-weight: bold; font-size: 14px;');
        console.table(requestData);
        console.log('Request URL:', "{{ route('generateItems') }}?" + $.param(requestData));
        console.groupEnd();

        $.ajax({
            url: "{{ route('generateItems') }}",
            type: 'GET',
            data: requestData,
            dataType: 'json',
            success: function(response) {
                console.group('%c✅ Items Loaded Successfully', 'color: green; font-weight: bold;');

                // Parse the HTML to show actual items nicely in console
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = response.selectitems;

                const options = tempDiv.querySelectorAll('option');
                const itemsList = [];

                options.forEach(option => {
                    if (option.value) {  // skip empty option
                        itemsList.push({
                            value: option.value,
                            text: option.textContent.trim()
                        });
                    }
                });

                console.log(`Total Items Returned: ${itemsList.length}`);
                console.table(itemsList);   // Beautiful table of all items

                // Also show raw HTML for reference
                console.log('%cRaw selectitems HTML:', 'color: gray;', response.selectitems);

                console.groupEnd();

                // Update the dropdown
                $itemSelect.html(response.selectitems || 
                    '<option value=""> --- select items --- </option><option value="">All</option>')
                    .trigger('change.select2');
            },
            error: function(xhr, status, error) {
                console.group('%c❌ loadItems AJAX Failed', 'color: red; font-weight: bold;');
                console.error('Status:', status);
                console.error('Error:', error);
                console.error('Response:', xhr.responseText);
                console.groupEnd();

                $itemSelect.html('<option value=""> --- select items --- </option><option value="All">All Items</option>')
                           .trigger('change.select2');
            }
        });
    }, 300);
}

function resetSelects() {
    $('#location').html('<option value="" disabled selected> --- select --- </option><option value="All">All</option>')
                  .trigger('change.select2');
    $('#person_accntable').html('<option value="" disabled selected>--- select --- </option>').trigger('change.select2');
    $('#enduser').html('<option value="" disabled selected>--- select --- </option>').trigger('change.select2');
    $('#item_id').html('<option value=""> --- select items --- </option><option value="All">All Items</option>').trigger('change.select2');
}
</script>
@endsection

