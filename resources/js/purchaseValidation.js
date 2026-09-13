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
            serial_number: {
                required: true
            },
            date_acquired: {
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
            serial_number: {
                required: "Please Enter Serial Number"
            },
            date_acquired: {
                required: "Please Select Date"
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
            element.closest('.form-group').append(error);
        },
        highlight: function (element, errorClass, validClass) {
            $(element).addClass('is-invalid');
        },
    });
});
