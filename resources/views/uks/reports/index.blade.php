@extends('layouts.master')
@section('title', 'Laporan UKS')
@section('css')
    @include('uks._ui-styles')
@endsection
@section('content')
    @php $userId = request()->route('userId') ?? auth()->id(); @endphp
    @component('components.breadcrumb')
        @slot('li_1') UKS @endslot
        @slot('li_2') Laporan @endslot
        @slot('title') Laporan UKS @endslot
    @endcomponent

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6"><div class="card h-100"><div class="card-body"><p class="text-muted mb-1">Total Pasien</p><h3 class="mb-0">{{ number_format($stats['patients']) }}</h3></div></div></div>
        <div class="col-xl-3 col-md-6"><div class="card h-100"><div class="card-body"><p class="text-muted mb-1">Pasien Aktif</p><h3 class="mb-0">{{ number_format($stats['active']) }}</h3></div></div></div>
        <div class="col-xl-3 col-md-6"><div class="card h-100"><div class="card-body"><p class="text-muted mb-1">Rawat UKS</p><h3 class="mb-0">{{ number_format($stats['inpatient']) }}</h3></div></div></div>
        <div class="col-xl-3 col-md-6"><div class="card h-100"><div class="card-body"><p class="text-muted mb-1">Bed Tersedia</p><h3 class="mb-0">{{ number_format($stats['beds_available']) }}</h3></div></div></div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <a href="{{ route('user.uks.reports.occupancy', ['userId' => $userId]) }}" class="card h-100 text-decoration-none">
                <div class="card-body"><h5><i class="ri-group-line me-1"></i>Laporan Penghuni</h5><p class="text-muted mb-0">Pasien yang masih dalam perawatan dan lokasi perawatannya.</p></div>
            </a>
        </div>
        <div class="col-lg-4">
            <a href="{{ route('user.uks.reports.permits', ['userId' => $userId]) }}" class="card h-100 text-decoration-none">
                <div class="card-body"><h5><i class="ri-file-list-3-line me-1"></i>Laporan Perizinan</h5><p class="text-muted mb-0">Riwayat perizinan santri yang tercatat di sistem.</p></div>
            </a>
        </div>
        <div class="col-lg-4">
            <a href="{{ route('user.uks.reports.sanitation', ['userId' => $userId]) }}" class="card h-100 text-decoration-none">
                <div class="card-body"><h5><i class="ri-leaf-line me-1"></i>Laporan Kebersihan</h5><p class="text-muted mb-0">Hasil inspeksi sanitasi dan rekomendasi tindak lanjut.</p></div>
            </a>
        </div>
        <div class="col-lg-4">
            <a href="{{ route('user.uks.reports.attendance', ['userId' => $userId]) }}" class="card h-100 text-decoration-none">
                <div class="card-body"><h5><i class="ri-calendar-check-line me-1"></i>Laporan Presensi</h5><p class="text-muted mb-0">Kunjungan dan status pasien UKS per bulan.</p></div>
            </a>
        </div>
        <div class="col-lg-4">
            <a href="{{ route('user.uks.reports.violations', ['userId' => $userId]) }}" class="card h-100 text-decoration-none">
                <div class="card-body"><h5><i class="ri-error-warning-line me-1"></i>Laporan Pelanggaran</h5><p class="text-muted mb-0">Catatan pelanggaran santri lintas unit.</p></div>
            </a>
        </div>
        <div class="col-lg-4">
            <a href="{{ route('user.uks.reports.rewards', ['userId' => $userId]) }}" class="card h-100 text-decoration-none">
                <div class="card-body"><h5><i class="ri-medal-line me-1"></i>Laporan Penghargaan</h5><p class="text-muted mb-0">Penghargaan dan prestasi santri.</p></div>
            </a>
        </div>
        <div class="col-lg-4">
            <a href="{{ route('user.uks.reports.inventories', ['userId' => $userId]) }}" class="card h-100 text-decoration-none">
                <div class="card-body"><h5><i class="ri-medicine-bottle-line me-1"></i>Laporan Inventaris</h5><p class="text-muted mb-0">Stok obat dan alat kesehatan UKS.</p></div>
            </a>
        </div>
    </div>
@endsection
