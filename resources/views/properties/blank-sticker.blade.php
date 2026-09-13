@extends('layouts.master')

@section('body')

<style>
.hidden {
    display: none;
}
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <iframe src="{{ route('propertiesStickerTemplatePDF') }}" width="100%" height="510"></iframe>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection