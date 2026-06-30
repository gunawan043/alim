@extends('waka.master')
@section('title') Detail Surat Keluar @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Surat Menyurat @endslot
        @slot('li_2') <a href="{{ route('waka.surat-keluar.index') }}">Surat Keluar</a> @endslot
        @slot('title') {{ $surat->nomor_surat }} @endslot
    @endcomponent

    <div class="row">
        <div class="col-xl-10">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Detail Surat Keluar</h5>
                    <div>
                        <a href="{{ route('waka.surat-keluar.edit', $surat->id) }}" class="btn btn-warning btn-sm"><i class="ri-edit-line"></i> Edit</a>
                        <a href="{{ route('waka.surat-keluar.index') }}" class="btn btn-secondary btn-sm"><i class="ri-arrow-go-back-line"></i> Kembali</a>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table table-borderless mb-0">
                        <tr><th style="width:200px">Nomor Surat</th><td>{{ $surat->nomor_surat }}</td></tr>
                        <tr><th>Tanggal Surat</th><td>{{ $surat->tanggal_surat?->format('d F Y') }}</td></tr>
                        <tr><th>Tanggal Kirim</th><td>{{ $surat->tanggal_kirim?->format('d F Y') ?: '<span class="text-muted">-</span>' }}</td></tr>
                        <tr><th>Tujuan</th><td>{{ $surat->tujuan }}</td></tr>
                        <tr><th>Perihal</th><td>{{ $surat->perihal }}</td></tr>
                        <tr><th>Sifat</th><td>{{ ucfirst($surat->sifat) }}</td></tr>
                        <tr><th>Keterangan</th><td>{!! nl2br(e($surat->keterangan)) ?: '<span class="text-muted">-</span>' !!}</td></tr>
                        <tr><th>Dibuat</th><td>{{ $surat->created_at?->format('d/m/Y H:i') }}</td></tr>
                        <tr><th>Diperbarui</th><td>{{ $surat->updated_at?->format('d/m/Y H:i') }}</td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection