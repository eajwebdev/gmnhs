<script type="text/javascript">
    $(function () {
        $('#addpurchase').validate({
            rules: {
                office_id: {
                    required: true
                },
                item_id: {
                    required: true
                },
                item_descrip: {
                    required: true
                }, 
                item_model: {
                    required: true
                }, 
                serial_number: {
                    required: true
                },
                unit_id: {
                    required: true
                },
                qty: {
                    required: true
                },
                item_cost: {
                    required: true
                },
                total_cost: {
                    required: true
                },
                properties_id: {
                    required: true
                },
                categories_id: {
                    required: true
                },
                property_id: {
                    required: true
                },
            },
            messages: {
                office_id: {
                    required: "Please Select Ofice"
                },
                item_id: {
                    required: "Please Select Item"
                },
                item_descrip: {
                    required: "Please Enter Description"
                },
                item_model: {
                    required: "Please Enter Model"
                },
                serial_number: {
                    required: "Please Enter Serial Number"
                },
                unit_id: {
                    required: "Please Select Unit"
                },
                qty: {
                    required: "Please Enter Quantity"
                },
                item_cost: {
                    required: "Please Enter Item Cost"
                },
                total_cost: {
                    required: "Cannot be null"
                },
                properties_id: {
                    required: "Please Select Property type"
                },
                categories_id: {
                    required: "Please Select Category"
                },
                property_id: {
                    required: "Please Select Property"
                },

            },
            errorElement: 'span',
            errorPlacement: function (error, element) {
                error.addClass('invalid-feedback');
                element.closest('.col-md-6').append(error);
                element.closest('.col-md-12').append(error);
            },
            highlight: function (element, errorClass, validClass) {
                $(element).addClass('is-invalid');
            },
            unhighlight: function (element, errorClass, validClass) {
                $(element).removeClass('is-invalid');
            },
        });
    });
</script>

<script>
    $(document).on('click', '.purchase-delete', function(e){
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
                    url: "{{ route('purchaseDelete', ":id") }}".replace(':id', id),
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


