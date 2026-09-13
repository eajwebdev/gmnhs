@if($nav == "items")
<script>
    $(function () {
    // $.validator.setDefaults({
    //   submitHandler: function () {
    //     alert( "Form successful submitted!" );
    //   }
    // });
    $('#itemsForm').validate({
      rules: {
        item_description: {
          required: true,
        },
      },
      messages: {
        item_description: {
          required: "Please enter item description",
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
      unhighlight: function (element, errorClass, validClass) {
        $(element).removeClass('is-invalid');
      }
    });
  });
</script>
<script>
  $(function () {
  // $.validator.setDefaults({
  //   submitHandler: function () {
  //     alert( "Form successful submitted!" );
  //   }
  // });
  $('#itemsFormEdit').validate({
    rules: {
      item_description: {
        required: true,
      },
    },
    messages: {
      item_description: {
        required: "Please enter item description",
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
    unhighlight: function (element, errorClass, validClass) {
      $(element).removeClass('is-invalid');
    }
  });
});
</script>
<script>
$(document).on('click', '#items_edit', function(){ 
    var id = $(this).val();
    $('#edit-items-form').modal('show');
    $.ajax({
        type: "GET",
        url: "{{ route('items-edit', ':id') }}".replace(':id', id),
        success: function(response){
            $("#items_id").val(response.items.id);
            $("#items_desc").val(response.items.item_description);
        }
    });
});
</script>
@endif