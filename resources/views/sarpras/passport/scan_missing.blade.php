@extends('layouts.master')
@section('title') QR Not Found @endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Sarana Prasarana @endslot
    @slot('title') QR Tidak Dikenali @endslot
@endcomponent

<div class="card">
    <div class="card-body text-center py-5">
        <i class="mdi mdi-qrcode-remove text-muted" style="font-size:64px;"></i>
        <h4 class="mt-3">QR Tidak Dikenali</h4>
        <p class="text-muted">Kode yang dipindai: <code>{{ $code }}</code></p>
        <p class="text-muted">Aset tidak ditemukan. Pastikan QR ditempel dengan benar atau coba lagi.</p>
        <a href="{{ route('sarpras.qr.scanner') }}" class="btn btn-primary">
            <i class="mdi mdi-qrcode-scan"></i> Scan Lagi
        </a>
        <a href="{{ route('sarpras.aset.index') }}" class="btn btn-outline-secondary">
            <i class="mdi mdi-format-list-bulleted"></i> Lihat Daftar Aset
        </a>
    </div>
</div>
@endsection