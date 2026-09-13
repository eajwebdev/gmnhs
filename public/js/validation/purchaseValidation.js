$(function () {
    $('#addpurchase').validate({
        rules: {
            po_number: {
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
            po_number: {
                required: "Please Enter PO Number"
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

    $('#addrelease').validate({
        rules: {
            property_no_generated: {
                required: true
            },
            office_id: {
                required: true
            },
            serial_number: {
                required: true
            },
            qty: {
                required: true
            },
        },
        messages: {
            property_no_generated: {
                required: "Please Enter PO Number"
            },
            office_id:{
                required: "Please Select Office",
            },
            serial_number:{
                required: "Please Select Serial Number",
            },
            qty:{
                required: "Please Enter Qty",
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
