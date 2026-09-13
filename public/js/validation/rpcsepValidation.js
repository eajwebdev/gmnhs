$(function () {
    $('#rpcsepReport').validate({
        rules: {
            office_id: {
                required: true,
            },
            properties_id: {
                required: true,
            },
            categories_id: {
                required: true,
            },
            start_date_acquired: {
                required: true,
            },
            end_date_acquired: {
                required: true,
            },
        },
        messages: {
            office_id: {
                required: "Select School/Office",
            },
            properties_id: {
                required: "Select Property Type",
            },
            categories_id: {
                required: "Select Category",
            },
            start_date_acquired: {
                required: "Select Date",
            },
            end_date_acquired: {
                required: "Select Date",
            },
        },
        errorElement: 'span',
        errorPlacement: function (error, element) {
            error.addClass('invalid-feedback');
            element.closest('.col-md-6, .col-md-12, .sdate, .edate').append(error);        
        },
        highlight: function (element, errorClass, validClass) {
            $(element).addClass('is-invalid');
        },
        unhighlight: function (element, errorClass, validClass) {
            $(element).removeClass('is-invalid');
        },
    });
});