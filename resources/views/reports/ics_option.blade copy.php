@extends('layouts.master')

@section('body')

@php $cr = request()->route()->getName(); @endphp

<div class="container-fluid">
    <div class="row" style="padding-top: 100px;">
        <div class="col-lg-2">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title" style="font-size: 17pt"></h5>
                    @include('partials.control_reportsSidebar')
                </div>
            </div>
        </div>
        <div class="col-lg-10">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-file-pdf"></i> ICS Reports
                    </h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('icsOptionReportGen') }}" class="form-horizontal add-form" id="icsReport" method="POST" target="_blank">
                        @csrf
                        <div class="form-group">
                            <div class="form-row">
                                <div class="col-md-6" {{ auth()->user()->role !== 'School Admin' ? '' : 'hidden' }}>
                                    <label>School or Office:</label>
                                    <select class="form-control select2bs4" id="office_id" name="office_id" style="width: 100%;" onchange="allgenOption(this.value, 'SCHOOL')">
                                        <option disabled selected value=""> --- Select school/office --- </option>
                                        @foreach ($office as $data)
                                            @if($data->id != 1)
                                                <option value="{{ $data->id }}">{{ $data->office_abbr }} - {{ $data->office_name }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label>Location:</label>
                                    <select class="form-control select2bs4" id="location" name="location" style="width: 100%;">
                                        <option disabled selected value=""> ---Select School Location--- </option>
                                        {{-- <option value="All" selected>All</option>
                                        @foreach ($office as $data)
                                            @if ($data->office_code == '0000')
                                                <option value="{{ $data->id }}">{{ $data->office_abbr }} - {{ $data->office_name }}</option>
                                            @endif
                                        @endforeach --}}
                                    </select>
                                    <input type="hidden" name="file_type" value="PDF">
                                </div>
                            </div>
                        </div> 
                        <div class="form-group">
                            <div class="form-row">
                                <div class="col-md-{{ auth()->user()->role !== 'School Admin' ? '6' : '12' }}">
                                    <label>Category:</label>
                                    <select id="category_id" name="categories_id" onchange="categor(this.value)" data-placeholder="---Select Category---" class="form-control select2bs4" style="width: 100%;">
                                        <option></option>
                                        
                                        <option value="All">All</option>
                                        @foreach ($category as $data)
                                            <option value="{{ $data->cat_code }}">
                                                {{ $data->cat_code }} - {{ $data->cat_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6" id="account-div">
                                    <label>Account Title:</label>
                                    <select id="account_title" name="property_id" data-placeholder="---Select Account Title---" onchange="acctTitle()" class="form-control select2bs4" style="width: 100%;">
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="form-row">
                                <input type="hidden" id="selected_account_id" name="selected_account_id">
                                <div class="col-md-6">
                                    <label>Date Range:</label>
                                    <div class="row">
                                        <div class="sdate col-md-6">
                                            <input type="date" name="start_date_acquired" class="form-control" onchange="allgenOption(document.querySelector('[name=person_accnt]').value, 'person', this.value, document.querySelector('[name=end_date_acquired]').value)" placeholder="Start Date">
                                        </div>
                                        <div class="edate col-md-6">
                                            <input type="date" name="end_date_acquired" class="form-control" onchange="allgenOption(document.querySelector('[name=person_accnt]').value, 'person', this.value, document.querySelector('[name=end_date_acquired]').value)" placeholder="End Date">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label>Accountable:</label>
                                    <select class="form-control select2bs4" 
                                        id="person_accnt" 
                                        data-placeholder="Select Accountable" 
                                        onchange="allgenOption(
                                            this.value, 
                                            'user', 
                                            document.querySelector('input[name=start_date_acquired]').value, 
                                            document.querySelector('input[name=end_date_acquired]').value
                                        )"
                                        name="person_accnt" style="width: 100%;">
                                        <option></option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <div class="form-row">
                                <div class="col-md-12">
                                    <label>Item:</label>
                                    <select class="select2bs4" multiple="multiple" data-placeholder="Select Items" id="item_id" name="item_id[]" style="width: 100%;" required>
                                       
                                    </select>
                                </div>
                                {{-- <div class="col-md-1">
                                    <label>Columns:</label>
                                    <input type="checkbox" name="serial" class="form-control" value="1">
                                </div> --}}
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <div class="form-row">
                                <div class="col-md-12">
                                    <button type="reset" class="btn btn-danger" data-dismiss="modal">
                                        Reset
                                    </button>
                                    <button type="submit" name="btn-submit" class="btn btn-primary">
                                        <i class="fas fa-file-pdf"></i> Generate
                                    </button>
                                </div>
                            </div>
                        </div>   
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@if(auth()->user()->role == 'School Admin')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var selectElement = document.getElementById('office_id');
            selectElement.value = "{{ $uoffice->id }}"; // Set the value

            // Trigger the change event manually
            var event = new Event('change', { bubbles: true });
            selectElement.dispatchEvent(event);
        });
    </script>
@endif
<script>
function acctTitle(){
    $('#item_id').empty();
}

function categor(val) {
    var categoryId = val;
    var propertyId = $("#property_id").val();
    $("#selected_account_id").val('All');
    // $('#item_id').empty();
    var modeval = "1,2"; // Ensure it's a comma-separated string
    var urlTemplate = "{{ route('invCatIcsPar', [':id', ':mode']) }}";
    var url = urlTemplate.replace(':id', categoryId).replace(':mode', modeval);

    if (categoryId) {
        $.ajax({
            url: url,
            type: "GET",
            success: function(response) {
                console.log(response);
                $('#account_title').empty();
                $('#account_title').append(response.options);
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error: ", status, error);
            }
        });

        $("#account_title").on("change", function() {
            var selectedOption = $(this).find(':selected');
            var selectedAccountId = selectedOption.val();
            var selectedAccountCode = selectedOption.data('account-id');
            $("#selected_account_id").val(selectedAccountCode);
        });
    }
}
</script>
<script>
function allgenOption(val, type, startDate = null, endDate = null) {
    var category = $('#category_id').val();
    var accnt_title = $('#account_title').val();
    var properties_id = "ics";
    var selected_account_id = $('#selected_account_id').val();
    var val = val;

    // alert(val);

    var urlTemplate = "{{ route('allgenOption') }}";
    var csrfToken = '{{ csrf_token() }}';

    if (office_id) {
        $.ajax({
            url: urlTemplate,
            type: "POST",
            data: {
                category: category,
                accnt_title: accnt_title,
                selected_account_id: selected_account_id,
                properties_id: properties_id, 
                val: val,
                type: type,
                start_date_acquired: startDate,
                end_date_acquired: endDate,
            },
            headers: {
                'X-CSRF-TOKEN': csrfToken 
            },
            success: function(response) {
                console.log(response);

                if (type === 'SCHOOL') {
                    // Fill Person Account dropdown
                    $('#person_accnt').empty().append("<option value=''></option>");
                    $('#person_accnt').append(response.options);
                } else {
                    // Fill Item dropdown
                    $('#item_id').empty().append("<option value=''></option>");
                    $('#item_id').append(response.options);
                }
                $('#location').empty().append("<option value=''></option>");
                $('#location').append(response.location);
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error:", status, error);
            }
        });
    }
};
</script>


@endsection
