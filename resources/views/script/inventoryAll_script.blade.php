@if(isset($category) && request()->is('properties*') && isset($cat))
    <script>
        $(document).ready(function() {
            $('.properties-table').DataTable({
                "ajax": "{{ route('getProperties', $cat) }}",
                responsive: true,
                lengthChange: true,
                searching: true,
                paging: true,
                "columns": [
                    {data: 'id', name: 'id', className: 'align-middle', orderable: false, searchable: false},
                    { data: 'item_name', className: 'align-middle' },
                    {
                        data: null,
                        render: function(data, type, row) {
                            var SCHOOL = row.office_name ? '<b>OFFICE:</b> ' + row.office_name : '';
                            var type = row.abbreviation ? '<br><b>TYPE:</b> ' + row.abbreviation : '';
                            var poNumber = row.po_number ? '<br><b>PO NUMBER:</b> ' + row.po_number : '';
                            var propertyCode = row.property_no_generated ? '<br><b>PROPERTY CODE:</b> ' + row.property_no_generated : '';
                            var oldPropertyCode = row.property_no_generated_old ? '<br><b>OLD PROPERTY CODE:</b> ' + row.property_no_generated_old : '';
                            var itemmodel = row.item_model ? '<br><b>MODEL:</b> ' + row.item_model : '';
                            var serialNumber = row.serial_number ? '<br><b>SERIAL NUMBER:</b> ' + row.serial_number : '';
                            var location = row.location_name ? '<br><b>LOCATION:</b> ' + row.location_name : '';
                            var description = row.item_descrip ? '<br><br><b>DESCRIPTION:</b><br> ' + row.item_descrip : '';
                            var accountname = row.accountableName ? '<br><br><b>PERSON ACCOUNTABLE:</b><br> ' + row.accountableName : '';
                            var accountname1 = row.accountableNames ? '<br><br><b>END USER:</b><br> ' + row.accountableNames : '';

                            return SCHOOL + type + poNumber + propertyCode + oldPropertyCode + itemmodel + serialNumber + location + description + accountname + accountname1;
                        },
                        className: 'align-middle'
                    },
                    {
                        data: 'item_cost',
                        className: 'text-center align-middle',
                        render: function(data, type, row) {
                            const formatted = Number(data).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                            if (row.price_stat === 'Uncertain') {
                                return '<span style="color: red;">' + formatted + '</span>';
                            } else {
                                return '<span>' + formatted + '</span>';
                            }
                        }
                    },
                    {data: 'qty', className: 'text-center align-middle'},
                    {
                        data: 'total_cost',
                        className: 'text-center align-middle',
                        render: function(data, type, row) {
                            return Number(data).toLocaleString(undefined, {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            });
                        }
                    },
                    {data: 'date_acquired', className: 'text-center align-middle'},
                    {data: 'remarks', className: 'text-center align-middle'},
                    {data: 'id',
                        render: function(data, type, row) {
                            if (type === 'display') {
                                var editUrl = "{{ route('propertiesEdit', ['id' => ':id']) }}".replace(':id', data);
                                var returnSlipUrl = "{{ route('returnSlip', ['id' => ':id']) }}".replace(':id', data);
                                var urole = "{{ auth()->user()->role }}";
                                if(urole !== "School Admin"){
                                    return `
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-success dropdown-toggle dropdown-icon" data-toggle="dropdown"></button>
                                            <div class="dropdown-menu">
                                                <a href="${editUrl}" class="dropdown-item btn-edit" href="#"><i class="fas fa-exclamation-circle"></i> Edit</a>
                                                <button id="${data}" onclick="printSticker(${data})" class="dropdown-item btn-print" href="#"><i class="fas fa-print"></i> Sticker</button>
                                                <button value="${data}" class="dropdown-item inventory-delete" href="#"><i class="fas fa-trash"></i> Delete</button>
                                                ${row.remarks === "Unserviceable" ? `<a href="${returnSlipUrl}" class="dropdown-item"><i class="fas fa-file-alt"></i> Return Slip</a>` : ""}
                                            </div>
                                        </div>
                                    `;
                                }else{
                                    return `
                                        <div class="btn-group">
                                            <button class="btn btn-info end-user" onclick="endUser('${row.id}')" data-toggle="modal" data-target="#endUserModal">
                                                <i class="fas fa-exclamation-circle"></i>
                                            </button>
                                        </div>
                                    `;  
                                }
                            } else {
                                return data;
                            }
                        },
                        className: 'text-center align-middle'
                    }
                ],
                initComplete: function(settings, json) {
                    var api = this.api();
                    api.column(0, {search: 'applied', order: 'applied'}).nodes().each(function(cell, i) {
                        cell.innerHTML = i + 1;
                    });
                },
                "createdRow": function (row, data, dataIndex) {
                    $(row).attr('id', 'tr-' + data.id);
                    if (data.remarks === "Unserviceable") {
                        $('td', row).addClass('un-bg');
                    }
                }
            });
        });
    </script>
    <script>
        function endUser(id){
            document.getElementById('prop-id').value=id;
        }
    </script>
@endif
<script>
    var urole = "{{ auth()->user()->role }}";
    var allowedRoles = ['Supply Officer', 'Administrator', 'Supply Staff'];

    $(document).ready(function() {
        // Base columns without the Action column
        var columns = [
            {data: 'id', name: 'id', orderable: false, searchable: false, className: 'text-center align-middle'},
            {data: 'item_name', className: 'align-middle'},
            {
                data: null,
                render: function(data, type, row) {
                    return '<b>OFFICE:</b> ' + (row.office_name ?? '') +
                        ' <br><b>TYPE:</b> ' + (row.abbreviation ?? '') +
                        ' <br><b>PO NUMBER:</b> ' + (row.po_number ?? '') +
                        ' <br><b>PROPERTY CODE:</b> ' + (row.property_no_generated ?? '') +
                        ' <br><b>OLD PROPERTY CODE:</b> ' + (row.property_no_generated_old ?? '') +
                        ' <br><b>MODEL:</b> ' + (row.item_model ?? '') +
                        ' <br><b>SERIAL NUMBER:</b> ' + (row.serial_number ?? '') +
                        ' <br><b>LOCATION:</b> ' + (row.location_name ?? '') +
                        ' <br><br><b>DESCRIPTION</b><br>' + (row.item_descrip ?? '') +
                        ' <br><br><b>PERSON ACCOUNTABLE:</b> ' + (row.person_accnt_name ?? '') +
                        ' <br><b>END USER:</b> ' + (row.person_accnt_name1 ?? '');
                },
                className: 'align-middle'
            },
            {
                data: 'item_cost',
                className: 'text-center align-middle',
                render: function(data, type, row) {
                    const formatted = Number(data).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    if (row.price_stat === 'Uncertain') {
                        return '<span style="color: red;">' + formatted + '</span>';
                    } else {
                        return '<span>' + formatted + '</span>';
                    }
                }
            },
            {data: 'qty', className: 'text-center align-middle'},
            {
                data: 'total_cost',
                className: 'text-center align-middle',
                render: function(data, type, row) {
                    return Number(data).toLocaleString(undefined, {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }
            },
            {data: 'date_acquired', 
                render: function(data, type, row) {
                    if (type === 'display') {
                        var date = new Date(data);
                        var options = { year: 'numeric', month: 'long', day: 'numeric' };
                        return date.toLocaleDateString('en-US', options);
                    } else {
                        return data;
                    }
                },
                className: 'text-center align-middle'
            },
            {
                data: 'remarks',
                className: 'text-center align-middle',
                render: function(data, type, row) {
                    let displayValue = data ? data : ''; // avoid showing null
                    return '<span class="item-status-' + row.property_no_generated + '">' + displayValue + '</span>';
                }
            },
            {
                data: 'his_remarks',
                className: 'text-center align-middle',
                render: function(data, type, row) {
                    let displayValue = data ? data : ''; // if null, show empty
                    return '<span class="remarks-' + row.property_no_generated + '">' + displayValue + '</span>';
                }
            },
            {
                data: 'inv_status',
                className: 'text-center align-middle',
                render: function(data, type, row) {
                    var badgeClass = data == 1 ? 'badge-warning' : 'badge-success';
                    var statusText = data == 1 ? 'Ongoing' : 'Done';
                    return '<span class="invstatus-' + row.property_no_generated + ' badge ' + badgeClass + '">' + statusText + '</span>';
                }
            }
        ];
        // Add action column only if user role is allowed
        if (allowedRoles.includes(urole)) {
            columns.push({
                data: null,
                orderable: false,
                searchable: false,
                className: 'text-center align-middle',
                render: function(data, type, row) {
                    return '<button class="btn btn-info btn-sm action-btn" ' +
                        'data-toggle="modal" data-target="#modal-remark" ' +
                        'data-pcode="' + row.property_no_generated  + '" ' +
                        'data-person_accnt="' + (row.person_accnt ?? '') + '" ' +
                        'data-person_accnt1="' + (row.person_accnt1 ?? '') + '" ' +
                        'data-remarks="' + (row.remarks ?? '') + '" ' +
                        'data-item_name="' + (row.item_name ?? '') + '">' +
                        '<i class="fas fa-exclamation-circle"></i></button>';
                }
            });
        }

        $('.inventory-table').DataTable({
            ajax: "{{ route('getInventory') }}",
            responsive: true,
            lengthChange: true,
            searching: true,
            paging: true,
            columns: columns,
            initComplete: function(settings, json) {
                var api = this.api();
                api.column(0, {search: 'applied', order: 'applied'}).nodes().each(function(cell, i) {
                    cell.innerHTML = i + 1;
                });
            },
            createdRow: function (row, data, dataIndex) {
                $(row).attr('id', 'tr-' + data.id);
                if (data.remarks === "Unserviceable") {
                    $('td', row).addClass('un-bg');
                }
            }
        });
    });
</script>

<script>
    $(document).on('click', '.inventory-delete', function(e){
        var id = $(this).val();
        $.ajaxSetup({
            headers: {
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
        });
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    type: "GET",
                    url: "{{ route('propertiesDelete', ":id") }}".replace(':id', id),
                        success: function (response) {  
                        $("#tr-"+id).delay(1000).fadeOut();
                        Swal.fire({
                            title:'Deleted!',
                            text:'Successfully Deleted!',
                            type:'success',
                            icon: 'warning',
                            showConfirmButton: false,
                            timer: 1000
                        })
                    }
                });
            }
        })
    });
</script>

<script>
    $('#modal-remark').on('show.bs.modal', function(event) {
        var button = $(event.relatedTarget); // Button that triggered the modal

        // Read data attributes
        var pcode = button.data('pcode');
        var personAccnt = button.data('person_accnt');
        var personAccnt1 = button.data('person_accnt1');
        var remarks = button.data('remarks');
        var itemName = button.data('item_name');

        // Set modal title
        $('#item-name').text(itemName);

        // Set hidden input id
        $('#pcode').val(pcode);

        // Set selects values and trigger change for Select2
        $('#person_accnt').val(personAccnt).trigger('change');
        $('#person_accnt1').val(personAccnt1).trigger('change');

        // Set status select value
        $('#remarks').val(remarks);
    });
</script>
<script>
$(document).ready(function() {
    $('#invsaveForm').on('submit', function(e) {
        e.preventDefault();

        // Get values directly from the form
        let pcode = $('#pcode').val();        // property code
        let status = $('#status').val();      // status select value
        let remarks = $('#remarkText').val(); // remarks textarea

        // Serialize the form for AJAX
        let formData = $(this).serialize();

        $.ajax({
            url: $(this).attr('action'), // Route::post('/save', ...)
            method: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    // Update remarks cell dynamically
                    $('.item-status-' + pcode).text(status);
                    $('.remarks-' + pcode).text(remarks);

                    // Update inv_status cell dynamically
                    // Example mapping: "Good Condition" -> Done, others -> Ongoing
                    let invClass = (status === 'Good Condition') ? 'badge-success' : 'badge-warning';
                    let invText = (status === 'Good Condition') ? 'Done' : 'Ongoing';

                    $('.invstatus-' + pcode)
                        .removeClass('badge-success badge-warning')
                        .addClass(invClass)
                        .text(invText);

                    // Close the modal
                    $('#modal-remark').modal('hide');

                    toastr.success("Record updated successfully!");
                } else {
                    toastr.error(response.error || "Something went wrong.");
                }
            },
            error: function(xhr) {
                toastr.error("An error occurred while saving.");
            }
        });
    });
});
</script>

