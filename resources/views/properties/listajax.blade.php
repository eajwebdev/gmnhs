@extends('layouts.master')

@section('body')

<style>
.hidden {
    display: none;
}
.dropdown-item:hover {
    background-color: #06601f;
    color: #fff;
}
.un-bg {
    background-color: #f8d7da;
}
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    @if(auth()->user()->role !=='School Admin')
                    <h3 class="card-title" style="float: right;">
                        <button type="button" class="btn btn-info btn-sm" data-toggle="modal" data-target="#modal-inventory">
                            <i class="fas fa-plus"></i> Add New
                        </button>
                    </h3>
                    @endif
                </div>
                
                <!-- Modal -->
                @include('properties.modal')
                @include('properties.modal-prntSticker')
                <!-- /End Modal -->
                
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="example" class="table table-bordered table-hover properties-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th width="250">ITEM NAME</th>
                                    <th width="400">DETAILS</th>
                                    <th>PRICE</th>
                                    <th>QTY</th>
                                    <th>TOTAL COST</th>
                                    <th>DATE ACQ.</th>
                                    <th>ITEM STATUS</th>
                                    <th>ACTION</th> 
                                </tr>
                            </thead>
                            <tbody>
                                
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Bootstrap Modal -->
<div class="modal fade" id="endUserModal" tabindex="-1" aria-labelledby="endUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body">
                <form action="{{ route('enduserUpdate') }}" class="form-horizontal add-form" id="addpurchase" method="POST">
                    @csrf
                    <input type="hidden" id="prop-id" name="prop_id" value="">
                    <div class="form-group">
                        <div class="form-row">
                            <div class="col-md-12 mt-3">
                                <label>END USER:</label>
                                <select class="form-control select2" name="person_accnt1" data-placeholder="--- Select End User ---" style="width: 100%;">
                                    <option value="0">N/A</option>
                                    @foreach ($accnt as $data)
                                        <option value="{{ $data->id }}">
                                            {{ $data->person_accnt }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-12 mt-3">
                                <label>LOCATION:</label>
                                <select class="form-control select2" name="location" data-placeholder="--- Select Location ---" style="width: 100%;">
                                    <option value="0">N/A</option>
                                    @foreach ($locations as $location)
                                        <option value="{{ $location->id }}">
                                            {{ $location->office_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-success mt-2 float-right"><i class="fas fa-save"></i> Save</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
               
            </div>
        </div>
    </div>
</div>
<script>
function formatNumber(input) {
    const value = input.value.replace(/[^\d.]/g, '');
    const formattedValue = value.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    input.value = formattedValue;
}
</script>
<script>
    function updateHiddenInput(selectElement) {
        var selectedValue = selectElement.value;
        document.getElementById('selected_category_id').value = selectedValue;
    }
</script>
<script>
function calculateTotalCost() {
    const qtyInput = document.getElementsByName('qty')[0];
    const itemCostInput = document.getElementsByName('item_cost')[0];
    const qty = parseFloat(qtyInput.value) || 0;
    const itemCost = parseFloat(itemCostInput.value.replace(/[^\d.]/g, '')) || 0;
    const totalCost = qty * itemCost;
    const formattedTotalCost = totalCost.toLocaleString();
    document.getElementsByName('total_cost')[0].value = formattedTotalCost;
}
</script>

<script>
function toggleSecondForm(selectElement) {
    const secondForm = document.getElementById('secondForm');
    const price = parseFloat(document.getElementsByName('item_cost')[0].value.replace(/[^\d.]/g, '')) || 0;
    const itemIdSelect = document.getElementById('item_id');

    if (price >= 10 && price <= 15000) {
        itemIdSelect.value = 2;
    } else if (price >= 15001 && price <= 49000) {
        itemIdSelect.value = 1;
    } else if (price >= 50000) {
        itemIdSelect.value = 3;
    }
    secondForm.style.display = 'block';
}
</script>

<script>
function categor(val) {
    var categoryId = val;
    var price = $("#item_cost").val().replace(/,/g, '');
    
    var modeval = (price <= 49000) ? 2 : 3;
    var urlTemplate = "{{ route('propertiesCat', [':id', ':mode']) }}";
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
        })
        $("#account_title").on("change", function() {
            var selectedOption = $(this).find(':selected');
            var selectedAccountId = selectedOption.val(); // Get the account ID directly from the selected option's value
            var selectedAccountCode = selectedOption.data('account-id'); // Get the account code from the data attribute
            $("#selected_account_id").val(selectedAccountCode); // Set account ID to input field
            // Optionally, do something with the selected account code
        });
    }
};
</script>

<script>
    function printSticker(purchase_id) {
        $.ajax({
            url: "{{ route('propertiesPrntSticker', ':id') }}".replace(':id', purchase_id),
            method: 'GET',
            success: function(response) {
                
                $('#modal-prntSticker .modal-body').html(response);
                
                $('#modal-prntSticker').modal('show');  

                console.log(response);

                $(".downloadStickerButton").each(function() {
                    $(this).val(purchase_id);
                });
            }
        });
    }
</script>

<script>
    $(document).ready(function() {
        $('#accountableSelect').change(function() {
            var selectedValue = $(this).val();
            if (!selectedValue) {
                var officeId = $('#officeSelect option:selected').data('office-id');
                $('#officeSelect').val(officeId).trigger('change.select2');
            }
        });
    });
</script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>

@endsection
