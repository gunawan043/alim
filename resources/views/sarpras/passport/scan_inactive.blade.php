@extends('layouts.master')
@section('title') QR Non-Aktif @endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Sarana Prasarana @endslot
    @slot('title') QR Non-Aktif @endslot
@endcomponent

<div class="card">
    <div class="card-body text-center py-5">
        <i class="mdi mdi-alert-circle-outline text-warning" style="font-size:64px;"></i>
        <h4 class="mt-3">QR Tidak Aktif</h4>
        <p class="text-muted">Aset ini telah dinonaktifkan dan tidak dapat diakses melalui passport.</p>
        <a href="{{ route('sarpras.aset.index') }}" class="btn btn-outline-secondary">
            <i class="mdi mdi-format-list-bulleted"></i> Lihat Daftar Aset
        </a>
    </div>
</div>
@endsection