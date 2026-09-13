@extends('layouts.master')

@section('body')

<div class="container-fluid">

    <div class="row">
        <div class="col-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <b>INVENTORY LIST</b>
                    </h3>
                    <h3 class="card-title float-right">
                        @if(auth()->user()->role == "Supply Officer" || auth()->user()->role == "Supply Staff" || auth()->user()->role == "Administrator")
                            <button type="button" class="btn btn-success start-inventory btn-sm">
                                <i class="fas fa-warehouse"></i> START INVENTORY
                            </button>
                        @endif
                    </h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="example1" class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th width="50" class="text-center">#</th>
                                    <th class="text-center">STARTED</th>
                                    <th class="text-center">ENDED</th> 
                                    <th class="text-center">STATUS</th> 
                                    <th class="text-center"></th> 
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $no = 1;    
                                @endphp
                                @foreach ($yearinvs as $data)
                                    <tr>
                                        <td class="text-center">{{ $no++ }}</td>
                                        <td>{{ \Carbon\Carbon::parse($data->created_at)->format('F d, Y h:i A') }}</td>
                                        <td>{{ ($data->updated_at != NULL) ? \Carbon\Carbon::parse($data->updated_at)->format('F d, Y h:i A') : '' }}</td>
                                        <td class="text-center"><span class="badge badge-{{ ($data->inv_status == 'Ongoing') ? 'warning' : 'success'}}">{{ $data->inv_status }}</span></td>
                                        <td class="text-center" width="50"><a href="{{ route('inventoryView', $data->id) }}" class="btn btn-info btn-sm"><i class="fas fa-info-circle"></i></button></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>

<script>
    $(document).on('click', '.start-inventory', function(e){
        e.preventDefault();

        let button = $(this);
        button.prop("disabled", true); // Disable button to prevent multiple clicks

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
        });

        Swal.fire({
            title: 'Are you sure?',
            text: "Do you want to start the inventory?",
            icon: 'info',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, Start!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    type: "POST",
                    url: "{{ route('startInventory') }}",
                    data: {},
                    dataType: "json",
                    success: function (response) {  
                        Swal.fire({
                            title: 'Started!',
                            text: response.success || 'Inventory Started Successfully!',
                            icon: 'success',
                            showConfirmButton: false,
                            timer: 1500
                        });

                        // OPTIONAL: Update UI (e.g., disable button or reload page)
                        button.text("Inventory Started").prop("disabled", true);
                        setTimeout(() => location.reload(), 2000);
                    },
                    error: function(xhr) {
                        let errorMessage = "Failed to start inventory.";
                        if (xhr.responseJSON && xhr.responseJSON.error) {
                            errorMessage = xhr.responseJSON.error;
                        }

                        Swal.fire({
                            title: 'Error!',
                            text: errorMessage,
                            icon: 'error',
                            showConfirmButton: true
                        });

                        button.prop("disabled", false); // Re-enable button on error
                    }
                });
            } else {
                button.prop("disabled", false); // Re-enable button if user cancels
            }
        });
    });
</script>


@endsection