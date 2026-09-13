{{--
    Record a return without leaving the list. The end user is chosen first, which
    scopes the property picker to what that person is accountable for (or, for a
    selected end user. Several properties can be
    handed in on one slip.
--}}
<div class="modal fade" id="newReturnModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="{{ route('returnSlips.store') }}" method="POST" id="newReturnForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-undo-alt text-success"></i> Return Items</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>1. Returned By (End User) <span class="text-danger">*</span></label>
                        @if($canChooseEnduser)
                            <select name="returned_by_id" id="newReturnEnduser" class="form-control" style="width: 100%;" required>
                                <option value=""></option>
                                @foreach($endusers as $enduser)
                                    <option value="{{ $enduser->id }}"
                                            data-custodian="0"
                                            {{ optional($defaultEnduser)->id == $enduser->id ? 'selected' : '' }}>
                                        {{ $enduser->person_accnt }}{{ $enduser->office_name ? ' - '.$enduser->office_name : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">
                                Pick the person handing the items in. Choosing a
                                Only the selected end user's assigned properties are listed.
                            </small>
                        @else
                            <input type="text" class="form-control" value="{{ optional($defaultEnduser)->person_accnt }}" readonly>
                            <small class="text-muted">
                                <i class="fas fa-lock"></i>
                                Fixed to your signed-in account. Only an Administrator or Supply Officer can record a return under another name.
                            </small>
                        @endif
                    </div>

                    <div class="form-group">
                        <label>2. Properties <span class="text-danger">*</span></label>
                        <select name="property_ids[]" id="newReturnProperty" class="form-control"
                                multiple="multiple" style="width: 100%;" required>
                        </select>
                        <small class="text-muted d-block" id="newReturnPropertyHint">
                            @if($canChooseEnduser)
                                Select the end user above first.
                            @else
                                Type to search your items by property no., description, serial no., model, or office.
                            @endif
                        </small>
                        <small class="text-muted d-block">
                            Pick as many items as are being returned together - each one is listed
                            separately on the slip and can be transferred or tagged on its own.
                        </small>
                    </div>

                    <div class="return-slip-selected-box mb-3" id="newReturnPropertyDetail">
                        <span class="text-muted">No items selected yet.</span>
                    </div>

                    <div class="form-group mb-0">
                        <label>Reason / Notes</label>
                        <textarea name="reason" class="form-control" rows="3" placeholder="Why are the items being returned?"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-success" id="newReturnSubmit"><i class="fas fa-paper-plane"></i> Submit Return</button>
                </div>
            </form>
        </div>
    </div>
</div>
