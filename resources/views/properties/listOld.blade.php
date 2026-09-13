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
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <button type="button" class="btn btn-info btn-sm" data-toggle="modal" data-target="#modal-employee">
                            <i class="fas fa-plus"></i> Add New
                        </button>
                    </h3>
                </div>

                <!-- Modal -->
                @include('purchases.modal')
                @include('purchases.modal-prntSticker')
                <!-- /End Modal -->

                <div class="card-body">
                    <div class="table-responsive">
                        <table id="example1" class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Property Type</th>
                                    <th>Property No.</th>
                                    <th>Office</th>
                                    {{-- <th>Item No.</th> --}}
                                    <th>Item</th>
                                    <th>Item Desc.</th>
                                    <th>Serial #.</th>
                                    <th>Item Price</th>
                                    <th>Qty</th>
                                    <th>Total Cost</th>
                                    <th>Date Acq.</th>
                                    <th>Remarks</th>
                                    <th>Action</th> 
                                </tr>
                            </thead>
                            <tbody>
                                @php $no = 1; @endphp
                                @foreach($inventory as $data)
                                <tr id="tr-{{ $data->id }}" class="">
                                    <td>{{ $no++ }}</td>
                                    <td>{{ $data->abbreviation }}</td>
                                    <td>{{ $data->property_no_generated }}</td>
                                    <td>{{ $data->office_abbr }}</td>
                                    <td>{{ $data->item_number }}</td>
                                    <td>{{ $data->item_name }}</td>
                                    <td style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 8ch;">{{ $data->item_descrip }}</td>
                                    <td style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 8ch;">{{ $data->serial_number }}</td>
                                    <td>
                                        @if($data->price_stat === 'Uncertain')
                                            <span style="color: red;">{{ $data->item_cost }}</span>
                                        @else
                                            <span>{{ $data->item_cost }}</span>
                                        @endif
                                    </td>

                                    <td>{{ $data->qty }}</td>
                                    <td>{{ $data->total_cost }}</td>
                                    <td>{{ $data->date_acquired }}</td>
                                    <td>{{ $data->remarks }}</td>
                                    <td>
                                        <div class="btn-group">
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-success dropdown-toggle dropdown-icon" data-toggle="dropdown">
                                                </button>
                                                <div class="dropdown-menu">
                                                    <a href="{{ route('purchaseEdit', ['id' => $data->id] ) }}" class="dropdown-item btn-edit" href="#"><i class="fas fa-exclamation-circle"></i> Edit</a>
                                                    <button id="{{ $data->id }}" onclick="printSticker(this.id)" class="dropdown-item btn-print" href="#"><i class="fas fa-print"></i> Sticker</button>
                                                    <button value="{{ $data->id }}" class="dropdown-item inventory-delete" href="#"><i class="fas fa-trash"></i> Delete</button>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
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
            url: "{{ route('purchasePrntSticker', ':id') }}".replace(':id', purchase_id),
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