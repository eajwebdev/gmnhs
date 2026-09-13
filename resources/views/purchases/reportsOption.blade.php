@extends('layouts.master')

@section('body')

<style>
.hidden {
    display: none;
}
.pba {
    color: #007bff;
}

</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <div class="row">
                        <div class="col-md-4"></div>
                        <div class="col-md-8">
                            <button type="button" class="btn btn-default btn-sm float-md-right"> Reports</button>                            
                            <a href="{{ route('purchaseREAD') }}" class="btn btn-default btn-sm float-md-right pba"> Purchases</a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div class="card card-gray card-tabs">
                        <div class="card-header p-0 pt-1">
                            <ul class="nav nav-tabs" id="custom-tabs-two-tab" role="tablist">
                                <li class="pt-2 px-3"><h3 class="card-title"></h3></li>
                                <li class="nav-item">
                                    <a class="nav-link active" id="custom-tabs-two-home-tab" data-toggle="pill" href="#custom-tabs-two-home" role="tab" aria-controls="custom-tabs-two-home" aria-selected="true">RPCPPE Reports</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="custom-tabs-two-par-tab" data-toggle="pill" href="#custom-tabs-two-par" role="tab" aria-controls="custom-tabs-two-par" aria-selected="false">PAR Reports</a>
                                </li>
                            </ul>
                        </div>
                        <div class="card-body">
                            <div class="tab-content" id="custom-tabs-two-tabContent">
                                <div class="tab-pane fade show active" id="custom-tabs-two-home" role="tabpanel" aria-labelledby="custom-tabs-two-home-tab">
                                    <form action="{{ route('purchaseReportsOtptionGen') }}" class="form-horizontal add-form" id="" method="GET" target="_blank">
                                        @csrf
                                        <div class="form-group">
                                            <div class="form-row">
                                                <div class="col-md-6">
                                                    <label>Property Type:</label>
                                                    <select class="form-control select2bs4" id="property_id" name="properties_id" style="width: 100%;">
                                                        <option value=""> ---Select Property Type--- </option>
                                                        @foreach ($property as $data)
                                                            <option value="{{ $data->id }}">{{ $data->abbreviation }} - {{ $data->property_name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div class="col-md-6">
                                                    <label>Category:</label>
                                                    <select id="category_id" name="categories_id" onchange="categor(this.value)" data-placeholder="---Select Category---" class="form-control select2bs4" style="width: 100%;">
                                                        <option></option>
                                                        @foreach ($category as $data)
                                                            <option value="{{ $data->cat_code }}">
                                                                {{ $data->cat_code }} - {{ $data->cat_name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <div class="form-row">
                                                <div class="col-md-6" id="account-div">
                                                    <label>Account Title:</label>
                                                    <select id="account_title" name="property_id" data-placeholder="---Select Account Title---" class="form-control select2bs4" style="width: 100%;">
                                                    </select>
                                                </div>
                                                <input type="hidden" id="selected_account_id" name="selected_account_id">
                                                <div class="col-md-6">
                                                    <label>Date Range:</label>
                                                    <div class="input-group">
                                                        <input type="date" name="start_date_acquired" class="form-control" placeholder="Start Date">
                                                        <div class="input-group-prepend input-group-append">
                                                            <span class="input-group-text">to</span>
                                                        </div>
                                                        <input type="date" name="end_date_acquired" class="form-control" placeholder="End Date">
                                                    </div>
                                                </div>
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

                                <div class="tab-pane fade" id="custom-tabs-two-par" role="tabpanel" aria-labelledby="custom-tabs-two-par-tab">
                                    Empty
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function categor(val) {
    var categoryId = val;
    var propertyId = $("#property_id").val();
    
    var modeval;
    if (propertyId === '2') {
        modeval = 2;
    } else if (propertyId === '3') {
        modeval = 3;
    } else if (propertyId === '1') {
        modeval = 1;
    } else {
        modeval = 3; 
    }

    var urlTemplate = "{{ route('purchaseCat', [':id', ':mode']) }}";
    var url = urlTemplate.replace(':id', categoryId).replace(':mode', modeval);
    
    if (categoryId) {
        $.ajax({
            url: url,
            type: "GET",
            success: function(response) {
                console.log(response);
                $('#account_title').empty();
                $('#account_title').append("<option value=''></option>");
                $('#account_title').append(response.options);
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
    document.getElementById('generate-btn').addEventListener('click', function (event) {
        event.preventDefault(); // Prevent the form from submitting

        // Get the form's action URL
        //var formAction = '{{ route('purchaseReportsOtptionGen') }}';

        // Open the URL in a new tab
        window.open(formAction, '_blank');
    });
</script>


@endsection