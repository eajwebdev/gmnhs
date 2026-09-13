$(function () {
    $('#parReport').validate({
        rules: {
            school_id: {
                required: true,
            },
            item_id: {
                required: true,
            },
        },
        messages: {
            school_id: {
                required: "Select School",
            },
            item_id: {
                required: "Select Items",
            },
        },
        errorElement: 'span',
        errorPlacement: function (error, element) {
            error.addClass('invalid-feedback');
            element.closest('.col-md-12').append(error);        },
        highlight: function (element, errorClass, validClass) {
            $(element).addClass('is-invalid');
        },
        unhighlight: function (element, errorClass, validClass) {
            $(element).removeClass('is-invalid');
        },
    });
});

