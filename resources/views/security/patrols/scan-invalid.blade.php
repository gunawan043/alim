@extends('layouts.master')

@section('title', 'QR Tidak Dikenali')

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Satuan Keamanan @endslot
        @slot('title') QR Tidak Dikenali @endslot
    @endcomponent

    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body text-center py-5">
                    <lord-icon src="https://cdn.lordicon.com/gsqxdxog.json" trigger="loop"
                               colors="primary:#f7b84b,secondary:#f06548"
                               style="width:100px;height:100px"></lord-icon>
                    <h4 class="mt-3">QR checkpoint tidak dikenali</h4>
                    <p class="text-muted">
                        Kode ini tidak terdaftar sebagai checkpoint aktif. Pastikan Anda memindai QR
                        yang diterbitkan dari halaman Checkpoint Patroli.
                    </p>
                    <a href="{{ route('user.keamanan.patrol.checkpoints.index', ['userId' => $userId]) }}" class="btn btn-primary">
                        <i class="ri-map-pin-line me-1"></i> Kelola Checkpoint
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
