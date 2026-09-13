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
        @php
            $grouped = $inventory->groupBy('office_name')->map(function($items, $name) {
                return [
                    'total' => $items->sum('total'),
                    'done' => $items->sum('done')
                ];
            });
        @endphp
        @if(auth()->user()->role !== 'School Admin')
            @foreach ($grouped as $office_name => $data)
                <div class="col-1">
                    <div class="small-box bg-muted">
                        <div class="inner text-center flex-grow-1 d-flex flex-column justify-content-center">
                            <h4 class="mb-1" style="font-size: 1.5rem;">{{ $data['done'] }} <span style="font-size:1.5rem;">/</span> {{ $data['total'] }}</h4>
                        </div>
                        <a href="#" class="small-box-footer text-truncate bg-secondary" style="height: 40px; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.95rem;">
                            {{ str_ireplace(['SCHOOL', 'extension', 'class'], '', $office_name) }}
                        </a>
                    </div>
                </div>
            @endforeach
        @endif
    </div>
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                      <b>INVENTORY LIST</b>
                    </h3>
                    @if(auth()->user()->role == 'School Admin')
                        @foreach ($grouped as $office_name => $data)
                            <div class="card-title" style="float: right;">
                            <b class="badge badge-success">{{ $data['done'] }} / {{ $data['total'] }}</b>
                            </div>
                        @endforeach
                    @endif
                </div>

                <!-- Modal -->
                {{-- @include('properties.modal')
                @include('properties.modal-prntSticker') --}}
                <!-- /End Modal -->
                
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="example" class="table table-bordered table-hover inventory-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th width="300">ITEM NAME</th>
                                    <th width="300">DETAILS</th>
                                    <th>PRICE</th>
                                    <th>QTY</th>
                                    <th>TOTAL COST</th>
                                    <th>DATE ACQ.</th>
                                    <th>STATUS</th>
                                    <th>REMARKS</th>
                                    <th></th>
                                    @if(auth()->user()->role !== 'School Admin')
                                        <th>ACTION</th>
                                    @endif
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

<div class="modal fade" id="modal-remark" role="dialog" aria-labelledby="modalRemarkLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
        <form id="invsaveForm" method="POST" action="{{ route('invSave') }}">
        @csrf
        <div class="modal-header">
            <h5 class="modal-title" id="item-name"></h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="pcode" name="pcode">

            <div class="form-row">
                <div class="form-group col-md-12">
                    <label for="person_accnt">Accountable Person</label>
                    <select class="form-control select2bs4" id="person_accnt" name="person_accnt" style="width: 100%;">
                    @foreach ($accnt as $data)
                        @if($data->accnt_role != 2)
                        <option value="{{ $data->id }}">
                        {{ $data->person_accnt }} {{ ($data->accnt_role == 1) ? '- HEAD' : '' }}
                        </option>
                        @endif
                    @endforeach
                    </select>
                </div>

                <div class="form-group col-md-12">
                    <label for="person_accnt1">End User</label>
                    <select class="form-control select2bs4" id="person_accnt1" name="person_accnt1" data-placeholder="--- Select Accountable Person 2 ---" style="width: 100%;">
                    <option value="">N/A</option>
                    @foreach ($accnt as $data)
                        @if($data->accnt_role != 2)
                            <option value="{{ $data->id }}">
                            {{ $data->person_accnt }} {{ ($data->accnt_role == 1) ? '- HEAD' : '' }}
                            </option>
                        @endif
                    @endforeach
                    </select>
                </div>
            </div>

            <div class="form-group">
            <label for="remarks">Status:</label>
            <select class="form-control" id="status" name="status">
                <option value="Good Condition">Good Condition</option>
                <option value="Needing Repair">Needing Repair</option>
                <option value="Unserviceable">Unserviceable</option>
                <option value="Obsolete">Obsolete</option>
                <option value="No Longer Needed">No Longer Needed</option>
                <option value="Not used since purchase">Not used since purchase</option>
            </select>
            </div>

            <div class="form-group">
            <label for="remarkText">Remarks</label>
            <textarea class="form-control" id="remarkText" name="remarks" rows="4"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Submit</button>
        </div>
        </form>
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
