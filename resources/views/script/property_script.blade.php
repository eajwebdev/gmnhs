@if(in_array($cur_viewSidebar_route, ['ppeRead', 'ppeEdit', 'lvRead', 'lvEdit', 'hvRead', 'hvEdit', 'intRead', 'intEdit']))
<script>
    $(function () {
        var $form = $('#accountTitleForm');
        var $category = $('#category');
        var $number = $('#account_number');
        var lastSuggestion = '';

        // Keep the account number in 0-00-00-000 shape while typing
        $number.on('input', function () {
            var digits = this.value.replace(/\D/g, '').slice(0, 8);
            var parts = [digits.slice(0, 1), digits.slice(1, 3), digits.slice(3, 5), digits.slice(5, 8)].filter(Boolean);
            this.value = parts.join('-');
        });

        // Suggest the leading segments from the property type and category,
        // but never overwrite a number the user has typed themselves
        $category.on('change', function () {
            var prefix = $category.data('prefix');
            var code = $category.val();
            var current = $number.val();

            if (!prefix || !code || (current && current !== lastSuggestion)) {
                return;
            }

            lastSuggestion = prefix + code + '-';
            $number.val(lastSuggestion).trigger('focus');
        });

        $.validator.addMethod('accountNumberFormat', function (value, element) {
            return this.optional(element) || /^\d-\d{2}-\d{2}-\d{3}$/.test(value);
        }, 'Use the 0-00-00-000 format.');

        $form.validate({
            ignore: [],
            rules: {
                category_id: { required: true },
                account_number: { required: true, accountNumberFormat: true },
                account_title: { required: true },
                account_title_abbr: { required: true }
            },
            messages: {
                category_id: 'Select a category.',
                account_number: { required: 'Enter the account number.' },
                account_title: 'Enter the account title.',
                account_title_abbr: 'Enter the title type or abbreviation.'
            },
            errorElement: 'span',
            errorPlacement: function (error, element) {
                error.addClass('invalid-feedback');
                element.closest('.form-group').append(error);
            },
            highlight: function (element) { $(element).addClass('is-invalid'); },
            unhighlight: function (element) { $(element).removeClass('is-invalid'); }
        });

        $category.on('change', function () { $(this).valid(); });
    });
</script>
@endif
