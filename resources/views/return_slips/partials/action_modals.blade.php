{{-- Shared Transfer / Unserviceable / Cancel modals for returned items. --}}
<div class="modal fade" id="transferItemModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" id="transferItemForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-check text-success"></i> Transfer to End User</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="return-slip-selected-box mb-3" id="transferItemSummary"></div>

                    <div class="form-group">
                        <label>End User <span class="text-danger">*</span></label>
                        <select name="enduser_id" class="form-control" required>
                            <option value="">-- Select End User --</option>
                            @foreach($endusers as $enduser)
                                <option value="{{ $enduser->id }}">
                                    {{ $enduser->person_accnt }}{{ $enduser->office_name ? ' — '.$enduser->office_name : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Office</label>
                        <select name="office_id" class="form-control">
                            <option value="">Keep the end user's office</option>
                            @foreach($offices as $office)
                                <option value="{{ $office->id }}">{{ $office->office_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Location</label>
                        <select name="location_id" class="form-control">
                            <option value="">Keep current location</option>
                            @foreach($offices as $office)
                                <option value="{{ $office->id }}">{{ $office->office_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Release Condition <span class="text-danger">*</span></label>
                        <select name="condition" class="form-control" required>
                            @foreach($conditions as $condition)
                                <option value="{{ $condition }}" {{ $condition === 'Good Condition' ? 'selected' : '' }}>{{ $condition }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Replaces the item's "Returned" remarks once transferred.</small>
                    </div>

                    <div class="form-group mb-0">
                        <label>Remarks</label>
                        <textarea name="remarks" class="form-control" rows="3" placeholder="Optional note recorded in the item log"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-exchange-alt"></i> Transfer Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="unserviceableItemModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" id="unserviceableItemForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-ban text-danger"></i> Mark as Unserviceable</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="return-slip-selected-box mb-3" id="unserviceableItemSummary"></div>
                    <p class="text-muted">The property remarks will be updated to <strong>Unserviceable</strong> and the action is written to the item log.</p>
                    <div class="form-group mb-0">
                        <label>Remarks</label>
                        <textarea name="remarks" class="form-control" rows="3" placeholder="Reason the item is unserviceable"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-danger"><i class="fas fa-ban"></i> Mark Unserviceable</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="obsoleteItemModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" id="obsoleteItemForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-archive text-secondary"></i> Mark as Obsolete</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="return-slip-selected-box mb-3" id="obsoleteItemSummary"></div>
                    <p class="text-muted">The property remarks will be updated to <strong>Obsolete</strong> and the action is written to the item log.</p>
                    <div class="form-group mb-0">
                        <label>Remarks</label>
                        <textarea name="remarks" class="form-control" rows="3" placeholder="Reason the item is obsolete"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-secondary"><i class="fas fa-archive"></i> Mark Obsolete</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="cancelItemModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" id="cancelItemForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-undo text-warning"></i> Cancel Return</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="return-slip-selected-box mb-3" id="cancelItemSummary"></div>
                    <p class="text-muted">The property goes back to its condition before the return.</p>
                    <div class="form-group mb-0">
                        <label>Remarks</label>
                        <textarea name="remarks" class="form-control" rows="3" placeholder="Why is the return being cancelled?"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-warning"><i class="fas fa-undo"></i> Cancel Return</button>
                </div>
            </form>
        </div>
    </div>
</div>
