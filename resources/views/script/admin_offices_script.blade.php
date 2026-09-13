@if($nav == "offices")
<script>
    $(function () {
    // $.validator.setDefaults({
    //   submitHandler: function () {
    //     alert( "Form successful submitted!" );
    //   }
    // });
    $('#officesForm').validate({
      rules: {
        office_code: {
          required: true,
        },
        office_name: {
          required: true,
        },
        abbreviation: {
          required: true,
        },
        office_officer: {
          required: true,
        },
      },
      messages: {
        item_description: {
          required: "Please enter office code",
        },
        office_name: {
          required: "Please enter office name",
        },
        abbreviation: {
          required: "Please enter office name",
        },
        office_officer: {
          required: "Please enter accountable officer",
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
  $('#officesFormEdit').validate({
    rules: {
      office_code: {
        required: true,
      },
      office_name: {
        required: true,
      },
      office_officer: {
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
$(document).on('click', '#offices_edit', function(){ 
    var id = $(this).val();
    $('#edit-offices-form').modal('show');
    $.ajax({
        type: "GET",
        url: "{{ route('offices-edit', ':id') }}".replace(':id', id),
        success: function(response){
            $("#office_id").val(response.encrypted_id);
            $("#office_code").val(response.offices.office_code);
            $("#office_name").val(response.offices.office_name);
            $("#abbreviation").val(response.offices.abbreviation);
            $("#office_officer").val(response.offices.office_officer);
        }
    });
});
</script>

<script>
  $(document).on('click', '.offices-delete', function(e){
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
              url: "{{ route('offices-delete', ":id") }}".replace(':id', id),
              success: function (response) {  
                  $("#"+response.id).delay(1000).fadeOut();
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
@endif