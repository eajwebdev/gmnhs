<script>
  $(function(){
    bsCustomFileInput.init();

    $('.select2').select2()

    //Initialize Select2 Elements
    $('.select2bs4').select2({
      theme: 'bootstrap4'
    })
  });

  $(function () {
    $("#example1").DataTable({
      "responsive": true, "lengthChange": false, "autoWidth": false
      //"buttons": ["csv", "excel", "colvis"]
    }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');
    $('#example2').DataTable({
      "paging": true,
      "lengthChange": false,
      "searching": false,
      "ordering": true,
      "info": true,
      "autoWidth": false,
      "responsive": true,
    });
  });
</script>

@if(!isset(Auth::user()->username))
  <script>window.location="{{ route('logout') }}"</script>
@endif
<script>
    window.history.forward();
    function noBack() {
        window.history.forward();
    }
</script>

<script>
  @if(Session::has('message'))
      toastr.options = {
        "closeButton":true,
        "progressBar":true,
        'positionClass': 'toast-bottom-right'
  }
  toastr.success("{{ session('message') }}")
  @endif
  @if(Session::has('error'))
      toastr.options = {
        "closeButton":true,
        "progressBar":true,
        'positionClass': 'toast-bottom-right'
  }
  toastr.error("{{ session('error') }}")
  @endif
</script>