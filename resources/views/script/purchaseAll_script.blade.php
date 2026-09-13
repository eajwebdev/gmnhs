<script>
    $(document).ready(function() {
        $('.purchase-table').DataTable({
            "ajax": "{{ route('getPurchase') }}",
            responsive: true,
            lengthChange: true,
            searching: true,
            paging: true,
            "columns": [
                {data: 'id', name: 'id', orderable: false, searchable: false},
                {data: 'abbreviation'},
                {data: 'po_number'},
                {data: 'item_model'},
                {data: 'item_name'},
                {data: 'item_descrip',
                    render: function(data, type, row) {
                        if (type === 'display') {
                            return '<div style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 8ch;">' + data + '</div>';
                        } else {
                            return data;
                        }
                    }
                },
                {data: 'serial_number',
                    render: function(data, type, row) {
                        if (type === 'display' && data !== null) {
                            return '<div style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 8ch;">' + data + '</div>';
                        } else {
                            return data;
                        }
                    }
                },
                {
                    data: 'item_cost',
                    render: function(data, type, row) {
                        const formatted = Number(data).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                        if (row.price_stat === 'Uncertain') {
                            return '<span style="color: red;">' + formatted + '</span>';
                        } else {
                            return '<span>' + formatted + '</span>';
                        }
                    }
                },
                {data: 'qty'},
                {data: 'qty_release'},
                { 
                    data: 'total_cost',
                    render: function (data, type, row) {
                        return Number(data).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    }
                },
                {data: 'date_acquired'},
                {data: 'id',
                    render: function(data, type, row) {
                        if (type === 'display') {
                            var editUrl = "{{ route('propertiesEdit', ['id' => ':id']) }}".replace(':id', data);
                            return `
                                <div class="btn-group">
                                    <button type="button" class="btn btn-success dropdown-toggle dropdown-icon" data-toggle="dropdown"></button>
                                    <div class="dropdown-menu">
                                        <button id="${data}" class="dropdown-item" onclick="purchaseReleaseGet(${data})" data-toggle="modal" data-target="#modal-release"><i class="fas fa-paper-plane"></i> Release</button>
                                        <button value="${data}" class="dropdown-item purchaserel-delete" href="#"><i class="fas fa-trash"></i> Delete</button>
                                    </div>
                                </div>
                            `;
                        } else {
                            return data;
                        }
                    }
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
            }
        });
    });
</script>

<script>
    $(function () {
        $('#addrelease').validate({
            ignore: [],
            rules: {
                person_accnt: {
                    required: true
                }
            },
            messages: {
                person_accnt: {
                    required: "Please select accountable person"
                }
            },
            errorElement: 'span',
            errorPlacement: function (error, element) {
                error.addClass('invalid-feedback d-block');

                if (element.hasClass('select2bs4')) {
                    element.closest('.col-md-5').append(error);
                    return;
                }

                element.closest('.form-group').append(error);
            },
            highlight: function (element) {
                $(element).addClass('is-invalid');
                if ($(element).hasClass('select2bs4')) {
                    $(element).next('.select2-container').find('.select2-selection').addClass('is-invalid');
                }
            },
            unhighlight: function (element) {
                $(element).removeClass('is-invalid');
                if ($(element).hasClass('select2bs4')) {
                    $(element).next('.select2-container').find('.select2-selection').removeClass('is-invalid');
                }
            }
        });
    });
</script>

<script>
    function resetReleaseOfficeFields() {
        $('#itemnum').val('');
        $('#property_no_generated').val($('#png').val());
        $('#accountableSelect').empty()
            .append('<option value=""> ---Select Accountable Person--- </option>')
            .trigger('change.select2');
    }

    function purchaseReleaseGet(dataid) {
        var $form = $('#addrelease');

        // Clear the previous release first. The office select is reset while no
        // purchase is loaded, so releasOffice() must not look anything up yet.
        $form[0].reset();
        $form.validate().resetForm();
        $form.find('.is-invalid').removeClass('is-invalid');
        $('#png, #purchase_id').val('');
        $form.find('select[name="office_id"]').val('').trigger('change.select2');
        $('#unrel_serial').empty().trigger('change.select2');
        resetReleaseOfficeFields();

        $.ajax({
            type: 'GET',
            url: "{{ route('purchaseReleaseGet', ':id') }}".replace(':id', dataid)
        }).done(function (data) {
            $('#rel_item_name').val(data.purchase.item_name);
            $('#rel_po_number').val(data.purchase.po_number);
            $('#purchase_id').val(dataid);
            $('#rel_qty').attr('max', data.qty_left);
            $('#qty-left').html('LEFT :' + data.qty_left);
            $('#png').val(data.pcode);
            $('#property_no_generated').val(data.pcode);
            $('#unrel_serial').html(data.unrel_serial).trigger('change.select2');
        }).fail(function () {
            Swal.fire({ titleText: 'Could not load purchase', text: 'Close the window and try again.', icon: 'error' });
        });
    }
</script>

<script>
    const routeTemplate = "{{ route('checkNextNumber', ['propertyno' => 'PROPERTYNO_PLACEHOLDER', 'officeCode' => 'OFFICECODE_PLACEHOLDER']) }}";

    function releasOffice(selectElement) {
        var png = $('#png').val();
        var selectedOption = selectElement.options[selectElement.selectedIndex];
        var officeCode = selectedOption ? selectedOption.getAttribute('data-officecode') : null;

        // Nothing to look up until both a purchase and an office are chosen
        if (!png || !selectElement.value || !officeCode) {
            resetReleaseOfficeFields();
            return;
        }

        var propertyno = png.replace(/^[^-]+-/, '');
        var url = routeTemplate
            .replace('PROPERTYNO_PLACEHOLDER', encodeURIComponent(propertyno))
            .replace('OFFICECODE_PLACEHOLDER', encodeURIComponent(officeCode));

        $.ajax({
            type: 'GET',
            url: url
        }).done(function (data) {
            var $select = $('#accountableSelect');
            $select.empty().append('<option value=""> ---Select Accountable Person--- </option>');

            if (data.next_item_number) {
                $('#itemnum').val(data.next_item_number);
                $('#property_no_generated').val(png + '-' + data.next_item_number + '-' + officeCode);
            }

            (data.accountables || []).forEach(function (item) {
                $select.append($('<option>', { value: item.id, text: item.person_accnt }));
            });

            $select.trigger('change.select2');
        }).fail(function () {
            resetReleaseOfficeFields();
            Swal.fire({ titleText: 'Could not load office details', text: 'Select the office again or reload the page.', icon: 'error' });
        });
    }
</script>

<script>
    $(document).on('click', '.purchaserel-delete', function(e){
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
                    url: "{{ route('purchaseRelDel', ":id") }}".replace(':id', id),
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
