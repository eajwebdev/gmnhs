$(function () {
    $('#icsReport').validate({
        rules: {
            school_id: {
                required: true,
            },
            person_accnt: {
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
            person_accnt: {
                required: "Select End User",
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
