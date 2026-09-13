@extends('layouts.master')

@section('body')
    @include('manage.property.partials.account-titles', [
        'prefix' => 'int',
        'typeLabel' => 'Intangible',
        'selected' => $intProperties ?? null,
    ])
@endsection
