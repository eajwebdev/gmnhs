<style>
    .select2-container .select2-selection--single {
        overflow: hidden;
        border: 1px solid #ced4da;
    }
    </style>
    <div class="modal fade" id="modal-purchase">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">
                        <i class="fas fa-plus"></i> Add New Purchase
                    </h6>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                
                <div class="modal-body">
                    <form action="{{ route('purchaseCreate') }}" class="form-horizontal add-form" id="addpurchase" method="POST">
                        @csrf
                        <div class="form-group">
                            <div class="form-row">
                                <div class="col-md-6">
                                    <label>Supply Type</label>
                                    <select class="form-control" name="supply_type">
                                        <option value="1">Property Equipment</option>
                                        <option value="2">Common Supplies</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label>PO Number:</label>
                                    <input type="text" name="po_number" oninput="this.value = this.value.toUpperCase()" placeholder="PO Number" autocomplete="off" class="form-control" >
                                </div>

                                <div class="col-md-12">
                                    <label>Item:</label>
                                    <select class="form-control select2bs4" name="item_id" data-placeholder=" ---Select Item--- " style="width: 100%;">
                                        <option value=""> </option>
                                        @foreach ($item as $data)
                                            <option value="{{ $data->id }}">{{ $data->item_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="form-row">
                                <div class="col-md-12">
                                    <label>Item Description:</label>
                                    <textarea class="form-control" rows="2" name="item_descrip"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="form-row">
                                <div class="col-md-12">
                                    <label>Model:</label>
                                    <textarea class="form-control" rows="2" name="item_model"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="form-row">
                                <div class="col-md-4">
                                    <label>Date Purchase:</label>
                                    <input type="date" name="date_acquired" value="{{ \Carbon\Carbon::now()->format('Y-m-d') }}" class="form-control">
                                </div>

                                <div class="col-md-4">
                                    <label>Unit:</label>
                                    <select class="form-control select2bs4" id="add-unit" name="unit_id" onchange="UnitAdd(this.value)" data-placeholder=" ---Select Unit--- " style="width: 100%;">
                                        <option value=""> </option>
                                        @foreach ($unit as $data)
                                            <option value="{{ $data->id }}">{{ $data->unit_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                

                                <div class="col-md-4">
                                    <label>Quantity:</label>
                                    <input type="number" id="add-qty" name="qty"  class="form-control" onkeyup="calculateTotalCost()" readonly>
                                </div>
                            </div>
                        </div>

                        
                        <div class="form-group">
                            <div class="form-row">
                                <div class="col-md-12">
                                    <label for="serial_number">Serial Number:</label>
                                    <textarea class="form-control" rows="2" id="add-serial-number" oninput="formatSerialNumber(this)" onkeydown="handleKeyDown(event, this)" onkeypress="handleKeyPress(event, this)" name="serial_number" readonly></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="form-row">
                                <div class="col-md-6">
                                    <label>Price:</label>
                                    <input type="text" id="item_cost" name="item_cost" onkeyup="formatNumber(this); calculateTotalCost(); toggleSecondForm(this) "  class="form-control">
                                </div>

                                <div class="col-md-6">
                                    <label>Total Cost:</label>
                                    <input type="text" name="total_cost" class="form-control" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="form-group" id="secondForm" style="display: none;">
                            <div class="form-row">
                                <div class="col-md-12">
                                    <label>Property Type:</label>
                                    <select class="form-control" name="properties_id" id="item_id" style="pointer-events: none;" onchange="toggleSecondForm(this)">
                                        @foreach ($property as $data)
                                            <option value="{{ $data->id }}"
                                                @if ($currentPrice >= 10 && $currentPrice <= 15000 && $data->id == 2)
                                                    selected
                                                @elseif ($currentPrice >= 15001 && $currentPrice <= 49000 && $data->id == 1)
                                                    selected
                                                @elseif ($currentPrice >= 50000 && $data->id == 3)
                                                    selected
                                                @endif
                                            >
                                                {{ $data->property_name }} ({{ $data->abbreviation }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div id="category-div" class="col-md-6 category-column mt-3">
                                    <label>Select Category</label>
                                    <select id="category_id" name="categories_id" onchange="categor(this.value)" data-placeholder="Select Category" class="form-control select2bs4" style="width: 100%;">
                                        <option></option>
                                        @foreach ($category as $data)
                                            <option value="{{ $data->cat_code }}">
                                                {{ $data->cat_code }} - {{ $data->cat_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div id="account-div" class="col-md-6 desc-column mt-3">
                                    <label>Select Account Title</label>
                                    <select id="account_title" name="property_id" data-placeholder="Select Account Title" class="form-control select2bs4" style="width: 100%;">
                                    </select>
                                </div>
                                <input type="hidden" id="selected_account_id" name="selected_account_id">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <div class="form-row">
                                <div class="col-md-12">
                                    <button type="button" class="btn btn-danger" data-dismiss="modal">
                                        Close
                                    </button>
                                    <button type="submit" name="btn-submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Save
                                    </button>
                                </div>
                            </div>
                        </div>   
                    </form>
                </div>
                
                <div class="modal-footer justify-content-between">
                    <!-- <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary">Save changes</button> -->
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="modal-release">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">
                        <i class="fas fa-plus"></i> Release Item
                    </h6>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                
                <div class="modal-body">
                    <form action="{{ route('purchaseReleasePost') }}" class="form-horizontal add-form" id="addrelease" method="POST">
                        @csrf
                        
                        <div class="form-group">
                            <div class="form-row">
                                <div class="col-md-6">
                                    <label>PO Number</label>
                                    <input type="text" id="rel_po_number" name="po_number" class="form-control" autocomplete="off" readonly>
                                </div>
                                
                                <div class="col-md-6">
                                    <label>Item Name</label>
                                    <input type="text" id="rel_item_name" name="item_name" class="form-control" autocomplete="off" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="form-row">
                                <div class="col-md-6">
                                    <label>Property No.</label>
                                    <input type="text" name="purchase_id" id="purchase_id" hidden>
                                    <input type="text" id="png" hidden>
                                    <input type="text" name="itemnum" id="itemnum" hidden>
                                    <input type="text" id="property_no_generated" name="property_no_generated" autocomplete="off" class="form-control" readonly>
                                </div>

                                <div class="col-md-6">
                                    <label>Office:</label>
                                    <select class="form-control select2bs4" name="office_id" onchange="releasOffice(this)" data-placeholder=" ---Select Office--- " style="width: 100%;">
                                        <option value=""> --- Select Office Here --- </option>
                                        @foreach ($office as $data)
                                            <option value="{{ $data->id }}" data-officecode="{{ $data->office_code }}">{{ $data->office_name }}</option>
                                        @endforeach
                                    </select>                                    
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="form-row">
                                <div class="col-md">
                                    <label>Serial Number:</label>
                                    <select class="form-control select2bs4 resize-select" onchange="updateQuantity(this)" id="unrel_serial" name="serial_number[]" multiple data-placeholder=" ---Select Serial Numbers--- " style="width: 100%;">
                                        <option value=""> --- Select Serial --- </option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="form-row">

                                <div class="col-md-2">
                                    <label>Quantity (<span id="qty-left" class="text-success" style="font-size: 12px;"> </span>)</label>
                                    <input type="text" id="rel_qty" min="1" max="" name="qty" onkeyup="formatNumber(this); calculateTotalCost(); toggleSecondForm(this) "  class="form-control" autocomplete="off">
                                </div>

                                <div class="col-md-5">
                                    <label for="date_acquired">Date Acquired:</label>
                                    <input type="date" id="date_acquired" value="{{ \Carbon\Carbon::now()->format('Y-m-d') }}" name="date_acquired" autocomplete="off" class="form-control">
                                </div>
                                
                                <div class="col-md-5">
                                    <label>Accountable Person: <span class="text-danger">*</span></label>
                                    <select class="form-control select2bs4" id="accountableSelect" name="person_accnt" data-placeholder=" ---Select Accountable Person--- " style="width: 100%;" required aria-required="true">
                                        <option value=""> ---Select Accountable Person--- </option>
                                        <!-- Options are dynamically filled by JS -->
                                    </select>
                                </div>
                            </div>
                        </div>
                                       
                        <div class="form-group">
                            <div class="form-row">
                                <div class="col-md-12">
                                    <button type="button" class="btn btn-danger" data-dismiss="modal">
                                        Close
                                    </button>
                                    <button type="submit" name="btn-submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Release
                                    </button>
                                </div>
                            </div>
                        </div>   
                    </form>
                </div>
                
                <div class="modal-footer justify-content-between">
                    <!-- <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary">Save changes</button> -->
                </div>
            </div>
        </div>
    </div>
    <script>
        function UnitAdd(val){
            if(val == 2){
                $('#add-qty').val('');
                $('#add-serial-number').val('');
                $('#add-qty').attr('readonly', 'readonly');
            }else{
                $('#add-qty').val('');
                $('#add-serial-number').val('');
                $('#add-qty').val('').removeAttr('readonly');
            }
            
            $('#add-serial-number').val('').removeAttr('readonly');
        }
    </script>
    <script>
        let isShiftPressed = false;
    
        function formatSerialNumber(element) {
            element.value = element.value.toUpperCase();
        }
    
        function handleKeyDown(event, element) {
            if (event.keyCode === 16) {
                isShiftPressed = true;
            }
        }
    
        function handleKeyPress(event, element) {
            var unit = $('#add-unit').val();

            if (unit == 2) {
                if (event.keyCode === 13) {
                    element.value += ':\n';
                    event.preventDefault(); 
                    $('#add-qty').val(colonCount);
                } else if (event.keyCode === 32 && !isShiftPressed) {
                    element.value += '';
                    event.preventDefault(); 
                }
            }

        }
    
        window.addEventListener('keyup', function(event) {
            if (event.keyCode === 16) {
                isShiftPressed = false;
            }
        });
    </script>

    <script>
        setInterval(function() {
            var serialNumber = $('#add-serial-number').val();

            if(serialNumber !== ''){
                var unit = $('#add-unit').val();
                var separator = (unit == 2) ? ':' : ';';

                var count = (serialNumber === '') ? 0 : 1;
                if (serialNumber.indexOf(separator) !== -1) {
                    count = serialNumber.split(separator).length;
                }

                $('#add-qty').val(count);
            }
        }, 1000);
    </script>
