@extends('layouts.master')

@section('body')
@php
    $logo = $setting->photo_filename && file_exists(public_path('uploads/'.$setting->photo_filename))
        ? asset('uploads/'.$setting->photo_filename)
        : asset('logo.png');
@endphp

<div class="row crud-layout">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Identity</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('upload') }}" method="POST" enctype="multipart/form-data" id="systemSettingsForm">
                    @csrf
                    <div class="form-group">
                        <label for="system_name">System name</label>
                        <input type="text" id="system_name" name="system_name" class="form-control"
                            value="{{ old('system_name', $setting->system_name) }}" placeholder="e.g. GMNHS PPEI"
                            oninput="this.value = this.value.toUpperCase()" required maxlength="255">
                        <span class="form-hint">Shown in the sidebar and browser tab.</span>
                    </div>

                    <div class="form-group">
                        <label for="photo">Logo</label>
                        <input type="file" id="photo" name="photo" class="form-control" accept="image/png,image/jpeg,image/gif">
                        <span class="form-hint">PNG, JPG or GIF up to 2 MB. Leave empty to keep the current logo.</span>
                    </div>

                    <div class="d-flex justify-content-end" style="border-top: 1px dashed var(--line); margin-top: 6px; padding-top: 16px;">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-floppy-disk"></i> Save settings</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card crud-form">
            <div class="card-body profile-card">
                <img src="{{ $logo }}" id="logoPreview" class="profile-mark" alt="Current system logo">
                <h3 id="namePreview">{{ $setting->system_name ?: 'GMNHS PPEI' }}</h3>
                <div class="role">Current identity</div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var name = document.getElementById('system_name');
        var file = document.getElementById('photo');

        name.addEventListener('input', function () {
            document.getElementById('namePreview').textContent = name.value || 'GMNHS PPEI';
        });

        file.addEventListener('change', function () {
            if (file.files && file.files[0]) {
                document.getElementById('logoPreview').src = URL.createObjectURL(file.files[0]);
            }
        });
    });
</script>
@endsection
