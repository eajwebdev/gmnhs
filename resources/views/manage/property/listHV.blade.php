@extends('layouts.master')

@section('body')
    @include('manage.property.partials.account-titles', [
        'prefix' => 'hv',
        'typeLabel' => 'High value',
        'selected' => $hvProperties ?? null,
    ])
@endsection
