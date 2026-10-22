@extends('layouts.master')

@section('title', 'Tambah Bed UKS')

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') UKS @endslot
        @slot('li_2') Rawat Inap @endslot
        @slot('li_3') Bed & Ruang Rawat @endslot
        @slot('title') Tambah Bed @endslot
    @endcomponent

    @include('uks.beds.form', ['bed' => null])
@endsection
