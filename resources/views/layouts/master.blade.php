<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>GMNHS || PPEI {{isset($title)?'| '.$title:''}}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500&family=Newsreader:ital,opsz,wght@0,6..72,400;0,6..72,500;0,6..72,600;1,6..72,400&family=Schibsted+Grotesk:wght@400;500;600;700&display=swap">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="{{ asset('template/plugins/fontawesome-free-v6/css/all.min.css') }}">
    <!-- Theme style -->
    <link rel="stylesheet" href="{{ asset('template/dist/css/adminlte.css') }}">
    <!-- Toastr -->
    <link rel="stylesheet" href="{{ asset('template/plugins/toastr/toastr.min.css') }}">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="{{ asset('template/plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css') }}">
    <!-- Select2 -->
    <link rel="stylesheet" href="{{ asset('template/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('template/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    <!-- Bootstrap4 Duallistbox -->
    <link rel="stylesheet" href="{{ asset('template/plugins/bootstrap4-duallistbox/bootstrap-duallistbox.min.css') }}">
    <!-- DataTables -->
    <link rel="stylesheet" href="{{ asset('template/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('template/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('template/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') }}">
    <!-- Logo  -->
    <link rel="shortcut icon" type="image/png" href="{{ asset('logo.png') }}">
    <!-- jQuery loads early: several screens run inline $(...) calls inside the page body -->
    <script src="{{ asset('template/plugins/jquery/jquery.min.js') }}"></script>

    <style>
        .select2-selection,
        .form-control1 {
            height: 38px !important;
        }
    </style>
    <link rel="stylesheet" href="{{ asset('template/css/custom-style.css') }}?v={{ @filemtime(public_path('template/css/custom-style.css')) }}">
</head>

@php
    $bareScreen = request()->is('technician/form*') || request()->is('technician/qr-scan*');
@endphp
<body class="hold-transition sidebar-mini layout-fixed text-sm">
    <div class="wrapper">
        @unless($bareScreen)
        @php
            $currentUser = auth()->user();
            $meta = page_meta();
            $systemName = $setting->system_name ?? null ?: 'GMNHS PPEI';
            $systemLogo = !empty($setting->photo_filename) && file_exists(public_path('uploads/'.$setting->photo_filename))
                ? asset('uploads/'.$setting->photo_filename)
                : asset('logo.png');
            $userInitial = strtoupper(substr($currentUser->fname ?? $currentUser->username ?? 'U', 0, 1));
            $userName = ucwords(strtolower(trim(($currentUser->fname ?? '').' '.($currentUser->lname ?? '')))) ?: $currentUser->username;
        @endphp
        <nav class="main-header navbar navbar-expand app-topbar" aria-label="Application header">
            <div class="container-fluid">
                <a class="app-menu-toggle" data-widget="pushmenu" href="#" role="button" title="Toggle sidebar" aria-label="Toggle sidebar">
                    <i class="fas fa-bars"></i>
                </a>

                <div class="app-crumbs">
                    <span class="crumb-section">{{ $meta['section'] }}</span>
                    <span class="sep">/</span>
                    <strong>{{ $meta['title'] }}</strong>
                </div>

                <div class="app-topbar-actions">
                    <span class="app-date">{{ now()->format('D · d M Y') }}</span>

                    <div class="dropdown">
                        <button class="app-user-btn" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <span class="app-avatar">{{ $userInitial }}</span>
                            <span class="app-user-name">{{ $userName }}</span>
                            <i class="fas fa-chevron-down caret"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right app-user-menu">
                            <div class="menu-head">
                                <strong>{{ $userName }}</strong>
                                <span>{{ display_role($currentUser->role) }}</span>
                            </div>
                            @if(user_has_access('settings', $currentUser))
                                <a class="dropdown-item" href="{{ route('user_settings') }}"><i class="fas fa-user-gear"></i> Account settings</a>
                            @endif
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="dropdown-item is-danger"><i class="fas fa-arrow-right-from-bracket"></i> Sign out</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <aside class="main-sidebar sidebar-dark-primary app-sidebar">
            <a href="{{ route('dashboard') }}" class="brand-link app-sidebar-brand">
                <img src="{{ $systemLogo }}" alt="{{ $systemName }} logo" class="brand-image">
                <span class="brand-text">
                    <b>{{ $systemName }}</b>
                    <small>Property Registry</small>
                </span>
            </a>
            <div class="sidebar">
                @include('partials.control')

                <div class="app-sidebar-foot">
                    <div class="user-panel">
                        <span class="app-avatar">{{ $userInitial }}</span>
                        <div class="info">
                            <strong>{{ $userName }}</strong>
                            <span>{{ display_role($currentUser->role) }}</span>
                        </div>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="app-signout" title="Sign out" aria-label="Sign out">
                                <i class="fas fa-arrow-right-from-bracket"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </aside>
        @endunless

        <div class="content-wrapper">
            <div class="content app-content">
                @if(!$bareScreen && !$meta['own_header'])
                    <header class="page-head">
                        <div>
                            <div class="page-head-eyebrow">{{ $meta['section'] }}</div>
                            <h1>{{ $meta['title'] }}</h1>
                            @if($meta['description'])
                                <p>{{ $meta['description'] }}</p>
                            @endif
                        </div>
                        @hasSection('page_actions')
                            <div class="page-head-actions">@yield('page_actions')</div>
                        @endif
                    </header>
                @endif

                @yield('body')
            </div>
        </div>

    @unless($bareScreen)
    <footer class="main-footer">
        <div class="float-right d-none d-sm-inline">PPEI Management System</div>
        Developed and maintained by the Management Information System Office.
    </footer>
    @endunless
</div>
<!-- ./wrapper -->

<!-- REQUIRED SCRIPTS -->

<!-- Bootstrap 4 -->
<script src="{{ asset('template/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<!-- AdminLTE App -->
<script src="{{ asset('template/dist/js/adminlte.min.js') }}"></script>

<!-- Toastr -->
<script src="{{ asset('template/plugins/toastr/toastr.min.js') }}"></script>
<!-- SweetAlert2 -->
<script src="{{ asset('template/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
@if(!request()->is('technician/form/*') && !request()->is('technician/qr-scan*'))
<!-- Select2 -->
<script src="{{ asset('template/plugins/select2/js/select2.full.min.js') }}"></script>
<!-- Bootstrap4 Duallistbox -->
<script src="{{ asset('template/plugins/bootstrap4-duallistbox/jquery.bootstrap-duallistbox.min.js') }}"></script>

<!-- DataTables  & Plugins -->
<script src="{{ asset('template/plugins/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('template/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
<script src="{{ asset('template/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
<script src="{{ asset('template/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
<script src="{{ asset('template/plugins/datatables-buttons/js/dataTables.buttons.min.js') }}"></script>
<script src="{{ asset('template/plugins/datatables-buttons/js/buttons.bootstrap4.min.js') }}"></script> 
<script src="{{ asset('template/plugins/jszip/jszip.min.js') }}"></script>
<script src="{{ asset('template/plugins/pdfmake/pdfmake.min.js') }}"></script>
<script src="{{ asset('template/plugins/pdfmake/vfs_fonts.js') }}"></script>
<script src="{{ asset('template/plugins/datatables-buttons/js/buttons.html5.min.js') }}"></script>
<script src="{{ asset('template/plugins/datatables-buttons/js/buttons.print.min.js') }}"></script>
<script src="{{ asset('template/plugins/datatables-buttons/js/buttons.colVis.min.js') }}"></script>

<!-- ChartJS -->
<script src="{{ asset('template/plugins/chart.js/Chart.min.js') }}"></script>
<script src="{{ asset('js/chartjs/dashboardChart.js') }}"></script>
<script src="{{ asset('js/chartjs/Bar.js') }}"></script>
<script src="{{ asset('js/chartjs/MainBar.js') }}"></script>

<!-- jquery-validation -->
<script src="{{ asset('template/plugins/jquery-validation/jquery.validate.min.js') }}"></script>
<script src="{{ asset('template/plugins/jquery-validation/additional-methods.min.js') }}"></script>

<script src="{{ asset('js/validation/purchaseValidation.js') }}"></script>
<script src="{{ asset('js/validation/inventoryValidation.js') }}"></script>
<script src="{{ asset('js/validation/passValidation.js') }}"></script>
<script src="{{ asset('js/validation/rpcppeValidation.js') }}"></script>
<script src="{{ asset('js/validation/rpcsepValidation.js') }}"></script>
<script src="{{ asset('js/validation/icsValidation.js') }}"></script>
<script src="{{ asset('js/validation/parValidation.js') }}"></script>

<!-- Moment -->
<script src="{{ asset('template/plugins/moment/moment.min.js') }}"></script>

<!-- Date Range Picker -->
<script src="{{ asset('template/plugins/daterangepicker/daterangepicker.js') }}"></script>
<link rel="stylesheet" href="{{ asset('template/plugins/daterangepicker/daterangepicker.css') }}">

@yield('scripts')

@endif
<script>
    @if(Session::has('error'))
        toastr.options = {
            "closeButton":true,
            "progressBar":true,
            'positionClass': 'toast-bottom-right'
        }
        toastr.error("{{ session('error') }}")
    @endif
    @if($errors->any())
        @foreach($errors->all() as $error)
            toastr.options = {
                "closeButton": true,
                "progressBar": true,
                'positionClass': 'toast-bottom-center'
            }
            toastr.error("{{ $error }}");
        @endforeach
    @endif
</script>

<script>
    @if(Session::has('success'))
        toastr.options = {
            "closeButton":true,
            "progressBar":true,
            'positionClass': 'toast-bottom-right'
        }
        toastr.success("{{ session('success') }}")
    @endif
    @if(Session::has('success1'))
        toastr.options = {
            "closeButton":false,
            "progressBar":false,
            'positionClass': 'toast-bottom-center'
        }
        toastr.success("{{ session('success1') }}")
    @endif
</script>

@if(!request()->is('technician/form/*') && !request()->is('technician/qr-scan*'))
@if(Session::has('successcopy'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var downloadLink = document.createElement('a');
            downloadLink.href = '{{ asset("Downloaded Form/" . session('download')) }}';
            downloadLink.download = '{{ session('download') }}';
            downloadLink.style.display = 'none';
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
        });
    </script>
@endif

<script>
    $(function () {
        if ($.fn.dataTable) {
            $.extend(true, $.fn.dataTable.defaults, {
                pageLength: 10,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
                language: {
                    search: '',
                    searchPlaceholder: 'Search records...',
                    lengthMenu: 'Show _MENU_ rows',
                    info: 'Showing _START_ to _END_ of _TOTAL_ records',
                    infoEmpty: 'No records available',
                    zeroRecords: 'No matching records found',
                    paginate: {
                        previous: '<i class="fas fa-chevron-left"></i>',
                        next: '<i class="fas fa-chevron-right"></i>'
                    }
                },
                dom: "<'datatable-toolbar'<'datatable-length'l><'datatable-filter'f>>" +
                     "<'datatable-scroll'tr>" +
                     "<'datatable-footer'<'datatable-info'i><'datatable-pagination'p>>"
            });
        }

        $("#example1").DataTable({
            "responsive": false,
            "lengthChange": true, 
            "autoWidth": true,
            //"buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"]

        }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');


        $("#example3").DataTable({
            "responsive": true,
            "lengthChange": true, 
            "autoWidth": false,
            "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"]

        }).buttons().container().appendTo('#example3_wrapper .col-md-6:eq(0)');
        @if(!request()->is('report'))
            $('.select2').select2()

            //Initialize Select2 Elements
            $('.select2bs4').select2({
            theme: 'bootstrap4'
            
            })
        @endif
        //Bootstrap Duallistbox
        $('.duallistbox').bootstrapDualListbox()

        @if(!request()->is('report'))
            $('#date_range').daterangepicker({
                autoUpdateInput: false,
                locale: {
                    cancelLabel: 'Clear',
                    format: 'YYYY-MM-DD'
                }
            });

            // Apply event
            $('#date_range').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(
                    picker.startDate.format('YYYY-MM-DD') + ' - ' +
                    picker.endDate.format('YYYY-MM-DD')
                );

                console.log("Start:", picker.startDate.format('YYYY-MM-DD'));
                console.log("End:", picker.endDate.format('YYYY-MM-DD'));
            });

            // Clear event
            $('#date_range').on('cancel.daterangepicker', function(ev, picker) {
                $(this).val('');
            });
        @endif
    });
</script>

<script>
    $(document).ready(function() {
        $('#file_type').on('change', function() {
            var fileType = $(this).val();
            var form = $('#rpcppeReport');

            if (fileType === 'EXCEL') {
                form.attr('target', '');
            } else {
                form.attr('target', '_blank');
            }
        });
    });
</script>

<script>
    $(document).ready(function() {
        $('#file_type').on('change', function() {
            var fileType = $(this).val();
            var form = $('#rpcsepReport');

            if (fileType === 'EXCEL') {
                form.attr('target', '');
            } else {
                form.attr('target', '_blank');
            }
        });
    });
</script>

<script>
    // Shared delete flow for list screens: any element with data-delete-url
    // asks for confirmation, calls the endpoint and removes its table row.
    $(document).on('click', '[data-delete-url]', function (e) {
        e.preventDefault();

        var $btn = $(this);
        var $row = $btn.closest('tr');
        var label = $btn.data('delete-label') || 'this record';

        Swal.fire({
            titleText: 'Delete ' + label + '?',
            text: 'This permanently removes the record and cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Delete',
            cancelButtonText: 'Keep it',
            reverseButtons: true,
            focusCancel: true
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }

            $btn.prop('disabled', true);

            $.ajax({
                type: 'GET',
                url: $btn.data('delete-url'),
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
            }).done(function (response) {
                var $table = $row.closest('table');

                if ($.fn.dataTable && $.fn.dataTable.isDataTable($table)) {
                    $table.DataTable().row($row).remove().draw(false);
                } else {
                    $row.fadeOut(200, function () { $(this).remove(); });
                }

                toastr.options = { closeButton: true, progressBar: true, positionClass: 'toast-bottom-right' };
                toastr.success((response && response.message) || 'Record deleted.');

                if ($btn.data('after-delete')) {
                    window.location.href = $btn.data('after-delete');
                }
            }).fail(function (xhr) {
                $btn.prop('disabled', false);
                Swal.fire({
                    titleText: 'Not deleted',
                    text: (xhr.responseJSON && xhr.responseJSON.message) || 'The record could not be deleted. Please try again.',
                    icon: 'error'
                });
            });
        });
    });
</script>

@php
    $cur_viewSidebar_route=request()->route()->getName();
@endphp

@include('script.admin_user_script')
@include('script.property_script')
@include('script.unit_script')

@include('script.inventoryAll_script')
@include('script.purchaseAll_script')

@endif
</body>
</html>
