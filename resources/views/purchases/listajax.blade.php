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
                        <button type="button" class="btn btn-info btn-sm" data-toggle="modal" data-target="#modal-purchase">
                            <i class="fas fa-plus"></i> Add New
                        </button>
                    </h3>
                </div>
                
                <!-- Modal -->
                @include('purchases.modal')
                <!-- /End Modal -->
                
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="example" class="table table-bordered table-hover purchase-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Property Type</th>
                                    <th>PO Number</th>
                                    <th>Model</th>
                                    <th>Item</th>
                                    <th>Description</th>
                                    <th>Serial #</th>
                                    <th>Price</th>
                                    <th>Qty</th>
                                    <th>Qty Released</th>
                                    <th>Total</th>
                                    <th>Date Purchase</th>
                                    <th>Action</th> 
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
    function toggleSecondForm(selectElement) {2024-04-05-020-011-01
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
                    var optionsWithoutAll = response.options.replace("<option value='All' data-account-id='All' selected>All</option>", '');

                    $('#account_title').append(optionsWithoutAll);
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

    <script>
        function updateQuantity(selectElement) {
            var selectedOptions = selectElement.selectedOptions;
            var qtyInput = document.getElementById('rel_qty');

            if (selectedOptions.length > 0) {
                qtyInput.value = selectedOptions.length;
                qtyInput.readOnly  = true;
            } else {
                qtyInput.value = '';
                qtyInput.readOnly  = false;
            }
        }
    </script>  
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>

@endsection