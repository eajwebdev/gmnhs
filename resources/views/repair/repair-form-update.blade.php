@extends('layouts.master')

@section('body')
@php
$cr = request()->route()->getName();
@endphp
<style>
    .footer {
        text-align: center;
        margin-bottom: 15px;
        font-size: 11px;
        font-weight: 300;
        color: var(--text-secondary);
        flex-shrink: 0;
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        margin: 0 auto 15px auto;
        max-width: 400px;
        z-index: 1000;
    }

    .footer img {
        width: 50px;
        margin-top: 8px;
        filter: drop-shadow(var(--shadow-light));
        cursor: pointer;
        transition: opacity 0.2s ease;
    }

    .footer img:hover {
        opacity: 0.8;
    }

    /* Custom Logout Popup Styles */
    .logout-popup {
        display: none;
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: white;
        border-radius: 15px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        z-index: 2000;
        width: 90%;
        max-width: 350px;
        overflow: hidden;
        animation: fadeInScale 0.3s ease-out;
    }

    .logout-popup.show {
        display: block;
    }

    @keyframes fadeInScale {
        from {
            opacity: 0;
            transform: translate(-50%, -50%) scale(0.8);
        }
        to {
            opacity: 1;
            transform: translate(-50%, -50%) scale(1);
        }
    }

    .logout-popup .header {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        color: white;
        padding: 20px;
        text-align: center;
        font-size: 18px;
        font-weight: 600;
    }

    .logout-popup .body {
        padding: 20px;
        text-align: center;
        font-size: 16px;
        color: #333;
        line-height: 1.5;
    }

    .logout-popup .footer {
        padding: 15px 20px;
        background: #f8f9fa;
        border-top: 1px solid #dee2e6;
        display: flex;
        justify-content: center;
        gap: 10px;
    }

    .logout-popup .btn {
        padding: 10px 20px;
        border: none;
        border-radius: 25px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s ease;
        min-width: 80px;
    }

    .logout-popup .btn-cancel {
        background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
        color: white;
    }

    .logout-popup .btn-logout {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        color: white;
    }

    .logout-popup .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(220, 53, 69, 0.4);
    }

    /* Backdrop */
    .backdrop {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1500;
    }

    .backdrop.show {
        display: block;
    }

    @media (max-width: 480px) {
        body {
            padding: 15px 5px;
        }

        #qr-reader { 
            width: 95%; 
            margin-bottom: 15px;
        }

        .tap-focus { 
            font-size: 12px; 
            margin-bottom: 10px;
        }

        .footer {
            font-size: 10px;
            margin-bottom: 10px;
        }

        .footer img {
            width: 60px;
        }

        .logout-popup {
            width: 95%;
            margin: 0;
        }

        .logout-popup .header {
            padding: 15px;
            font-size: 16px;
        }

        .logout-popup .body {
            padding: 15px;
            font-size: 14px;
        }

        .logout-popup .footer {
            padding: 10px 15px;
            gap: 5px;
        }

        .logout-popup .btn {
            padding: 8px 15px;
            font-size: 13px;
        }
    }
</style>
<div class="container-fluid d-flex justify-content-center align-items-center" style="margin-top: -60px; min-height: 80vh;">
    <div class="col-12 col-md-6 col-lg-4">
        <div class="card shadow ">
            <div class="card-body">
                
                <form action="{{ route('repairUpdate') }}" id="form-control" method="POST">
                    @csrf
                    <!-- Property -->
                    <div class="col-12 mb-2">
                        <label class="form-label">
                            <span class="badge badge-secondary">Property Number</span>
                        </label>
                        <input type="text" name="prop_id" value="{{ $enduserprop->id }}" hidden>
                        <input type="text" value="{{ $enduserprop->property_no_generated }}" name="property" class="form-control form-control-sm" readonly>
                    </div>

                    <div class="col-12 mb-2">
                        <label class="form-label">
                            <span class="badge badge-secondary">Model</span>
                        </label>
                        <input type="text" value="{{ $enduserprop->item_model }}" name="item_model" class="form-control form-control-sm" readonly>
                    </div>

                    <div class="col-12 mb-2">
                        <label class="form-label">
                            <span class="badge badge-secondary">Description</span>
                        </label>
                        <textarea name="item_descrip" class="form-control form-control-sm" rows="2" readonly>{{ $enduserprop->item_descrip }}</textarea>
                    </div>

                    <div class="col-12 mb-2">
                        <label class="form-label">
                            <span class="badge badge-secondary">Diagnoses</span>
                        </label>
                        <textarea name="diagnosis" class="form-control form-control-sm" rows="3"></textarea>
                    </div>
                
                    <div class="col-12">
                        <label for="repair_status" class="form-label">
                            <span class="badge badge-secondary">Repair Status</span>
                        </label>
                        <select name="repair_status" id="repair_status" class="select1 form-control form-control-sm">
                            <option value="1">Pending</option>
                            <option value="2">For Further Evaluation</option>
                            <option value="3">For Replacement</option>
                            <option value="4">For Repair</option>
                            <option value="5">For Job Out</option>
                            <option value="6">Unserviceable</option>
                        </select>
                    </div>

                    <div class="col-12 mt-3">
                        <button class="btn btn-success btn-sm w-100">
                            <i class="fas fa-save"></i> Save
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>
</div>

<!-- Footer with clickable logo -->
<div class="footer">
    Maintained and Managed by the Management Information System (MIS)<br>
    <img src="{{ asset('uploads/mislogo.png') }}" alt="MIS Logo" onclick="showLogoutPopup()" style="cursor: pointer;">
</div>

<!-- Custom Logout Popup -->
<div class="backdrop" id="backdrop" onclick="hideLogoutPopup()"></div>
<div class="logout-popup" id="logoutPopup">
    <div class="header">
        <i class="fas fa-sign-out-alt me-2"></i>Confirm Logout
    </div>
    <div class="body">
        Are you sure you want to logout? Any unsaved changes will be lost.
    </div>
    <div class="footer">
        <button class="btn btn-cancel" onclick="hideLogoutPopup()">
            <i class="fas fa-times me-1"></i>Cancel
        </button>
        <button class="btn btn-logout" onclick="performLogout()">
            <i class="fas fa-sign-out-alt me-1"></i>Logout
        </button>
    </div>
</div>

<script>
function showLogoutPopup() {
    document.getElementById('logoutPopup').classList.add('show');
    document.getElementById('backdrop').classList.add('show');
}

function hideLogoutPopup() {
    document.getElementById('logoutPopup').classList.remove('show');
    document.getElementById('backdrop').classList.remove('show');
}

function performLogout() {
    // Create and submit logout form
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ route("logout") }}'; // Adjust to your logout route if needed
    const csrf = document.createElement('input');
    csrf.type = 'hidden';
    csrf.name = '_token';
    csrf.value = '{{ csrf_token() }}';
    form.appendChild(csrf);
    document.body.appendChild(form);
    form.submit();
}
</script>

@endsection