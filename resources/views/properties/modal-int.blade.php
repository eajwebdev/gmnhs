<style>
    .select2-container .select2-selection--single {
        overflow: hidden;
        border: 1px solid #ced4da;
    }
    </style>
    <div class="modal fade" id="modal-employee">
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
                                    <label>Office:</label>
                                    <select class="form-control select2bs4" name="office_id" data-placeholder=" ---Select Office--- " style="width: 100%;">
                                        <option value=""> --- Select Office Here --- </option>
                                        @foreach ($office as $data)
                                            <option value="{{ $data->id }}" data-office-id="{{ $data->id }}">{{ $data->office_name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6">
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
                                <div class="col-md-6">
                                    <label>Serial Number:</label>
                                    <input type="text" name="serial_number" oninput="this.value = this.value.toUpperCase()" placeholder="Enter Serial Number" class="form-control">
                                </div>

                                <div class="col-md-6">
                                    <label>Date Purchase:</label>
                                    <input type="date" name="date_acquired" class="form-control">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="form-row">
                                <div class="col-md-6">
                                    <label>Unit:</label>
                                    <select class="form-control select2bs4" name="unit_id" data-placeholder=" ---Select Unit--- " style="width: 100%;">
                                        <option value=""> </option>
                                        @foreach ($unit as $data)
                                            <option value="{{ $data->id }}">{{ $data->unit_name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label>Quantity:</label>
                                    <input type="number" name="qty"  class="form-control" onkeyup="calculateTotalCost()">
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
                                    <select class="form-control" name="properties_id" onchange="toggleSecondForm(this)">
                                            <option value="4">
                                                Intangible Asset (INTANGIBLE)
                                            </option>
                                    </select>
                                </div>

                                <div id="category-div" class="col-md-6 category-column mt-3">
                                    <label>Select Category</label>
                                    <select id="category_id" name="categories_id" onchange="categorint(this.value)" data-placeholder="Select Category" class="form-control select2bs4" style="width: 100%;">
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

                                <div class="col-md-12 mt-3">
                                    <label>Remarks:</label>
                                    <select class="form-control" name="remarks" id="remarks">
                                        <option value="Good Condition">Good Condition</option>
                                        <option value="Needing Repair">Needing Repair</option>
                                        <option value="Unserviceable">Unserviceable</option>
                                        <option value="Obsolete">Obsolete</option>
                                        <option value="No Longer Needed">No Longer Needed</option>
                                        <option value="Not used since purchase">Not used since purchase</option>
                                    </select>
                                </div>

                                <div class="col-md-12 mt-3">
                                    <label>Price Status:</label>
                                    <select class="form-control" name="price_stat" id="price_stat">
                                        <option value="Certain">Certain</option>
                                        <option value="Uncertain">Uncertain</option>
                                    </select>
                                </div>

                                <div class="col-md-12 mt-3">
                                    <label>Accountable Person:</label>
                                    <select class="form-control select2bs4" id="accountableSelect" name="person_accnt" data-placeholder=" ---Select Accountable Person--- " style="width: 100%;">
                                        <option value=""> </option>
                                        @foreach ($accnt as $data)
                                            <option value="{{ $data->id }}">{{ $data->person_accnt }}</option>
                                        @endforeach
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