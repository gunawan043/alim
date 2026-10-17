@extends('layouts.master')

@section('title', $jabatanLabel . ' — Dashboard')

@section('css')
    @include('dashboard._shared._css')
@endsection

@section('content')
    @include('dashboard._shared.content')
@endsection

@push('scripts')
    @include('dashboard._shared._script')
@endpush
