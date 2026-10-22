@extends('layouts.master')

@section('title', 'Edit Bed UKS')

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') UKS @endslot
        @slot('li_2') Rawat Inap @endslot
        @slot('li_3') Bed & Ruang Rawat @endslot
        @slot('title') Edit Bed @endslot
    @endcomponent

    @include('uks.beds.form', ['bed' => $bed])
@endsection
