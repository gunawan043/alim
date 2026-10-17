@extends('layouts.master')

@section('title', 'Dashboard Admin Kesehatan UKS')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <div>
                    <h4 class="mb-sm-0">Dashboard Admin Kesehatan (UKS)</h4>
                    <small class="text-muted">Kesehatan santri & pelaporan medis — {{ $today->isoFormat('dddd, D MMMM Y') }}</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card mini-stats-wid">
                <div class="card-body">
                    <p class="text-muted mb-1"><i class="ri-group-line"></i> Total Santri Aktif</p>
                    <h4>{{ $totalSantri }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card mini-stats-wid">
                <div class="card-body">
                    <p class="text-muted mb-1"><i class="ri-stethoscope-line"></i> Pelanggaran Medis (30 hari)</p>
                    <h4 class="text-warning">{{ $healthViolations }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card mini-stats-wid">
                <div class="card-body">
                    <p class="text-muted mb-1"><i class="ri-error-warning-line"></i> Total Pelanggaran (30 hari)</p>
                    <h4 class="text-danger">{{ $violationsMonth }}</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h4 class="card-title">Informasi Medis Santri</h4>
        </div>
        <div class="card-body">
            <p class="text-muted">Modul monitoring kesehatan santri akan segera tersedia. Gunakan halaman Laporan Asrama untuk laporan sementara.</p>
        </div>
    </div>
</div>
@endsection