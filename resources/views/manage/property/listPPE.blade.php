@extends('layouts.master')

@section('body')
    @include('manage.property.partials.account-titles', [
        'prefix' => 'ppe',
        'typeLabel' => 'PPE',
        'selected' => $ppeProperties ?? null,
    ])
@endsection
