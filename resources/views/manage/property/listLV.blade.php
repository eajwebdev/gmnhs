@extends('layouts.master')

@section('body')
    @include('manage.property.partials.account-titles', [
        'prefix' => 'lv',
        'typeLabel' => 'Low value',
        'selected' => $lvProperties ?? null,
    ])
@endsection
