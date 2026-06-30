@extends('waka.master')
@section('title') Detail Surat Masuk @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Surat Menyurat @endslot
        @slot('li_2') <a href="{{ route('waka.surat-masuk.index') }}">Surat Masuk</a> @endslot
        @slot('title') {{ $surat->nomor_surat }} @endslot
    @endcomponent

    <div class="row">
        <div class="col-xl-10">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Detail Surat Masuk</h5>
                    <div>
                        <a href="{{ route('waka.surat-masuk.edit', $surat->id) }}" class="btn btn-warning btn-sm">
                            <i class="ri-edit-line"></i> Edit
                        </a>
                        <a href="{{ route('waka.surat-masuk.index') }}" class="btn btn-secondary btn-sm">
                            <i class="ri-arrow-go-back-line"></i> Kembali
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table table-borderless mb-0">
                        <tr><th style="width:200px">Nomor Surat</th><td>{{ $surat->nomor_surat }}</td></tr>
                        <tr><th>Tanggal Surat</th><td>{{ $surat->tanggal_surat?->format('d F Y') }}</td></tr>
                        <tr><th>Tanggal Diterima</th><td>{{ $surat->tanggal_terima?->format('d F Y') }}</td></tr>
                        <tr><th>Pengirim</th><td>{{ $surat->pengirim }}</td></tr>
                        <tr><th>Perihal</th><td>{{ $surat->perihal }}</td></tr>
                        <tr><th>Sifat</th><td>{{ ucfirst($surat->sifat) }}</td></tr>
                        <tr><th>Lampiran</th><td>{!! nl2br(e($surat->lampiran)) ?: '<span class="text-muted">-</span>' !!}</td></tr>
                        <tr><th>Dibuat</th><td>{{ $surat->created_at?->format('d/m/Y H:i') }}</td></tr>
                        <tr><th>Diperbarui</th><td>{{ $surat->updated_at?->format('d/m/Y H:i') }}</td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection