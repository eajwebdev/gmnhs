@if(in_array($cur_viewSidebar_route, ['userRead', 'userEdit']))
<script>
    $(function () {
        var editing = !!window.isEditingUser;

        // New accounts start from the role's usual modules; when editing, the
        // saved selection is kept until the role is actually changed.
        $('#roleSelect').on('change', function () {
            var defaults = (window.roleAccessDefaults || {})[this.value] || ['dashboard'];

            $('.access-checkbox').each(function () {
                this.checked = defaults.indexOf(this.value) !== -1;
            });
        });

        $('#addUser').validate({
            rules: {
                fname: { required: true },
                lname: { required: true },
                gender: { required: true },
                username: { required: true, minlength: 5 },
                password: { required: !editing, minlength: 8 },
                role: { required: true }
            },
            messages: {
                fname: 'Enter the first name.',
                lname: 'Enter the last name.',
                gender: 'Select a gender.',
                username: {
                    required: 'Enter a username.',
                    minlength: 'Usernames need at least 5 characters.'
                },
                password: {
                    required: 'Enter a password.',
                    minlength: 'Passwords need at least 8 characters.'
                },
                role: 'Select a role.'
            },
            errorElement: 'span',
            errorPlacement: function (error, element) {
                error.addClass('invalid-feedback');
                element.closest('.form-group').append(error);
            },
            highlight: function (element) { $(element).addClass('is-invalid'); },
            unhighlight: function (element) { $(element).removeClass('is-invalid'); }
        });
    });
</script>
@endif
