@extends('layouts.master')
@section('title') Aset Dihapus @endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Sarana Prasarana @endslot
    @slot('title') Aset Tidak Ditemukan @endslot
@endcomponent

<div class="card">
    <div class="card-body text-center py-5">
        <i class="mdi mdi-package-variant text-danger" style="font-size:64px;"></i>
        <h4 class="mt-3">Aset Dihapus atau Tidak Aktif</h4>
        <p class="text-muted">Aset ini telah dihapus atau tidak lagi aktif di sistem.</p>
        <a href="{{ route('sarpras.aset.index') }}" class="btn btn-outline-secondary">
            <i class="mdi mdi-format-list-bulleted"></i> Lihat Daftar Aset
        </a>
    </div>
</div>
@endsection