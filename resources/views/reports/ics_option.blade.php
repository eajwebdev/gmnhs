@extends('layouts.master') 
@section('body') 
@php $cr = request()->route()->getName(); @endphp 
<div class="container-fluid"> 
    <div class="row">
        <div class="col-lg-12"> 
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
                                    <select class="form-control select2bs4" id="office_id" name="office_id" style="width: 100%;" onchange="allgenOption(this.value, 'SCHOOL', this.options[this.selectedIndex].getAttribute('data-person-cat'))"> 
                                        <option disabled selected value=""> --- Select school/office --- </option> 
                                        @foreach ($office as $data) 
                                            @if($data->id != 1) 
                                                <option value="{{ $data->id }}" data-person-cat='none'>{{ $data->office_abbr }} - {{ $data->office_name }}</option> 
                                            @endif 
                                        @endforeach 
                                    </select> 
                                </div> 
                                <div class="col-md-6"> 
                                    <label>Location:</label> 
                                    <select class="form-control select2bs4" id="location" name="location" style="width: 100%;">
                                        <option disabled selected value=""> --- Select Location --- </option>
                                        <option value="All" selected>All</option>
                                        @foreach ($office as $data)
                                            @if ($data->office_code == '0000')
                                                 <option value="{{ $data->id }}">{{ $data->office_name }} ({{ strtoupper($data->school_abbr) }})</option>
                                            @endif
                                        @endforeach
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
                                            <option value="{{ $data->cat_code }}"> {{ $data->cat_code }} - {{ $data->cat_name }} </option> 
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
                                <div class="col-md-12"> 
                                    <label>Date Range:</label> 
                                    <div class="row"> 
                                        <div class="sdate col-md-6"> 
                                            <input type="date" 
                                                name="start_date_acquired" 
                                                onchange="allgenOption(
                                                    document.getElementById('person_accnt').value, 
                                                    'user', 
                                                    document.getElementById('person_accnt').options[document.getElementById('person_accnt').selectedIndex]?.getAttribute('data-person-cat'), 
                                                    this.value, 
                                                    document.querySelector('input[name=end_date_acquired]').value
                                                )" 
                                                class="form-control" 
                                                placeholder="Start Date"> 
                                        </div> 
                                        <div class="edate col-md-6"> 
                                            <input type="date" 
                                                name="end_date_acquired" 
                                                onchange="allgenOption(
                                                    document.getElementById('person_accnt').value, 
                                                    'user', 
                                                    document.getElementById('person_accnt').options[document.getElementById('person_accnt').selectedIndex]?.getAttribute('data-person-cat'), 
                                                    document.querySelector('input[name=start_date_acquired]').value, 
                                                    this.value
                                                )" 
                                                class="form-control" 
                                                placeholder="End Date"> 
                                        </div> 
                                    </div> 
                                </div>
                            </div> 
                        </div> 
                        <div class="form-group"> 
                            <div class="form-row">
                                <div class="col-md-6"> 
                                    <label>Accountable:</label> 
                                    <select class="form-control select2bs4" id="person_accnt" data-placeholder="Select Accountable" onchange="allgenOption(this.value, 'user', this.options[this.selectedIndex].getAttribute('data-person-cat'), document.querySelector('input[name=start_date_acquired]').value, document.querySelector('input[name=end_date_acquired]').value)" name="person_accnt" style="width: 100%;"> 
                                        <option></option> 
                                    </select> 
                                </div> 
                                <div class="col-md-6"> 
                                    <label>End User:</label> 
                                    {{-- <select class="form-control select2bs4" id="person_accnt1" name="person_accnt1" data-placeholder="Select End User" > 
                                        <option></option> 
                                    </select>  --}}
                                    <select class="form-control select2bs4" id="person_accnt1" name="person_accnt1" onchange="displayItem(this.value)"> 
                                        <option></option>
                                     
                                    </select>
                                </div> 
                            </div>
                        </div>
                        <div class="form-group"> 
                            <div class="form-row"> 
                                <div class="col-md-6"> 
                                    <label>Item:</label> 
                                    <select class="select2bs4" multiple="multiple" data-placeholder="Select Items" id="item_id" name="item_id[]" style="width: 100%;" required> 
                                    </select> 
                                </div> 
                                <div class="col-md-1 text-center">
                                    <label>Location Column:</label>
                                    <input type="checkbox" name="locationcolumn" class="form-control" value="1">
                                </div>
                            </div> 
                        </div> 
                        <div class="form-group"> 
                            <div class="form-row"> 
                                <div class="col-md-12"> 
                                    <button type="reset" class="btn btn-danger" data-dismiss="modal"> Reset </button> 
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
function allgenOption(val, type, pAccountable, startDate = null, endDate = null) { 
    var category = $('#category_id').val(); 
    var accnt_title = $('#account_title').val(); 
    var properties_id = "ics"; 
    var selected_account_id = $('#selected_account_id').val(); 
    var endUserID = val; 
    var urlTemplate = "{{ route('allgenOption') }}"; 
    var csrfToken = '{{ csrf_token() }}'; 

    if (endUserID) { 
        $.ajax({ 
            url: urlTemplate, 
            type: "POST", 
            data: { 
                'category': category, 
                'accnt_title': accnt_title, 
                'selected_account_id': selected_account_id, 
                'properties_id': properties_id, 
                'id': endUserID, 
                'type': type, 
                'pAccountable': pAccountable, 
                'start_date_acquired': startDate, 
                'end_date_acquired': endDate, 
            }, 
            headers: { 
                'X-CSRF-TOKEN': csrfToken 
            }, 
            success: function(response) { 
                console.log(response); 
                if (type == 'SCHOOL') { 
                    // Update accountable person dropdown
                    $('#person_accnt').empty().append(response.options); 
                    $('#person_accnt1').empty().append(response.endusers); 

                    $('#person_accnt1').empty();
                    $('#person_accnt1').append("<option value=''></option>");
                    $('#person_accnt1').append(response.options1);
                    
                    // Update location dropdown only if it's a SCHOOL selection
                    $('#location').empty().append(response.location);
                } else { 
                    // Update items dropdown
                    $('#item_id').empty().append(response.options); 
                } 
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error:", status, error);
            }
        }); 
    } 
};
</script> 
<script>
function displayItem(enduserId) {
    if (!enduserId) {
        // If no enduser is selected, just clear the select
        $('#item_id').empty().trigger('change');
        return;
    }

    // Generate URL from named route
    var url = "{{ route('displayItem', [':enduserId']) }}".replace(':enduserId', enduserId);

    $.ajax({
        url: url,
        type: "GET",
        dataType: "json",
        success: function(response) {
            var $select = $('#item_id');
            console.log(response);
            // Clear existing options
            $select.empty();

            // Append items from response
            $.each(response, function(index, item) {
                $select.append(
                    $('<option></option>').val(item.id).text(item.text)
                );
            });

            // Refresh Select2 to display new options
            $select.trigger('change');
        },
        error: function(xhr) {
            console.error("AJAX Error:", xhr.responseText || xhr.statusText);
        }
    });
}
</script>
@endsection
