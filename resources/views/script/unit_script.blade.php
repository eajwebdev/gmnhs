@if(in_array($cur_viewSidebar_route, ['unitRead', 'unitEdit', 'itemRead', 'itemEdit', 'accountableRead', 'accountableEdit', 'officeRead', 'officeEdit']))
<script>
    // Validation for the single-form master data screens (units, items,
    // offices/locations, accountable persons). Rules come from the inputs'
    // own data attributes so each form only declares what it needs.
    $(function () {
        $('form.js-validate').each(function () {
            $(this).validate({
                ignore: ':hidden:not(select)',
                errorElement: 'span',
                errorPlacement: function (error, element) {
                    error.addClass('invalid-feedback');
                    element.closest('.form-group').append(error);
                },
                highlight: function (element) { $(element).addClass('is-invalid'); },
                unhighlight: function (element) { $(element).removeClass('is-invalid'); }
            });
        });

        $('form.js-validate select').on('change', function () { $(this).valid(); });
    });

    function titleCaseInput(input) {
        var start = input.selectionStart;
        input.value = input.value.replace(/(^|\s)(\S)/g, function (m, space, ch) { return space + ch.toUpperCase(); });
        input.setSelectionRange(start, start);
    }
</script>
@endif
