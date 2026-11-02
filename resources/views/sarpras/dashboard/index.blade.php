@extends('layouts.master')
@section('title') Dashboard Sarpras @endsection

@section('css')
<link href="{{ URL::asset('build/libs/apexcharts/apexcharts.css') }}" rel="stylesheet">
@endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Sarana Prasarana @endslot
    @slot('title') Dashboard @endslot
@endcomponent

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

{{-- PETA ALUR KERJA URT --}}
<div class="card mb-4 border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h5 class="card-title mb-0"><i class="ri-flow-chart me-2 text-primary"></i>Alur Kerja Unit Rumah Tangga</h5>
                <p class="text-muted small mb-0">Sembilan prosedur, dari satuan kerja sampai eksekusi dan verifikasi.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('sarpras.qr.scanner') }}" class="btn btn-sm btn-outline-danger"><i class="ri-qr-scan-2-line me-1"></i>Scan QR Aset</a>
                <a href="{{ route('sarpras.assignment-report.index') }}" class="btn btn-sm btn-outline-warning"><i class="ri-file-list-3-line me-1"></i>Laporan Penugasan</a>
                <a href="{{ route('sarpras.workflow.assignment') }}" class="btn btn-sm btn-outline-primary"><i class="ri-user-settings-line me-1"></i>Penugasan &amp; Area Kerja</a>
                <a href="{{ route('sarpras.workflow.index') }}" class="btn btn-sm btn-primary"><i class="ri-arrow-right-line me-1"></i>Buka Peta Alur</a>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @foreach([
                ['Pendataan Aset', 'ri-archive-2-line', 'sarpras.aset.index'],
                ['Pemeliharaan', 'ri-calendar-todo-line', 'sarpras.pemeliharaan.schedule.index'],
                ['Perbaikan', 'ri-tools-line', 'sarpras.repair.index'],
                ['Peminjaman', 'ri-hand-coin-line', 'sarpras.peminjaman.index'],
                ['Penghapusan', 'ri-delete-bin-6-line', 'sarpras.disposal.pending'],
                ['Pengadaan', 'ri-shopping-cart-2-line', 'sarpras.pengadaan.index'],
                ['Listrik Padam', 'ri-flashlight-line', 'sarpras.electricity.outages.index'],
                ['Kebersihan', 'ri-brush-line', 'sarpras.kebersihan.areas'],
                ['Pertamanan', 'ri-plant-line', 'sarpras.pertamanan.agenda.index'],
            ] as [$label, $icon, $route])
                <a href="{{ route($route) }}" class="btn btn-sm btn-light border"><i class="{{ $icon }} me-1 text-primary"></i>{{ $label }}</a>
            @endforeach
        </div>
    </div>
</div>

{{-- AGENDA MENDATANG & PRIORITAS --}}
<div class="mb-4">
    @include('dashboard.unit-rumah-tangga.widgets.agenda-prioritas-urt', ['data' => $agenda])
</div>

<div class="row">
    {{-- GEDUNG --}}
    <div class="col-xl-3 col-md-6">
        <div class="card card-h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <span class="text-muted mb-3">Total Gedung</span>
                        <h2 class="mb-0">{{ number_format($totalGedung) }}</h2>
                    </div>
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-soft-primary text-primary rounded fs-2"><i class="ri-community-line"></i></span>
                    </div>
                </div>
                <div class="mt-3">
                    <span class="badge bg-success-subtle text-success">{{ $gedungBaik }} Baik</span>
                    <span class="badge bg-warning-subtle text-warning">{{ $gedungRusakRingan }} Rusak Ringan</span>
                    <span class="badge bg-danger-subtle text-danger">{{ $gedungRusakBerat }} Rusak Berat</span>
                </div>
            </div>
        </div>
    </div>

    {{-- RUANGAN --}}
    <div class="col-xl-3 col-md-6">
        <div class="card card-h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <span class="text-muted mb-3">Total Ruangan</span>
                        <h2 class="mb-0">{{ number_format($totalRuangan) }}</h2>
                    </div>
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-soft-info text-info rounded fs-2"><i class="ri-door-open-line"></i></span>
                    </div>
                </div>
                <div class="mt-3">
                    <span class="badge bg-success-subtle text-success">{{ $ruanganBaik }} Baik</span>
                    <span class="badge bg-warning-subtle text-warning">{{ $ruanganRusak }} Perlu Perbaikan</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ASET --}}
    <div class="col-xl-3 col-md-6">
        <div class="card card-h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <span class="text-muted mb-3">Total Aset</span>
                        <h2 class="mb-0">{{ number_format($totalAset) }}</h2>
                    </div>
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-soft-warning text-warning rounded fs-2"><i class="ri-archive-line"></i></span>
                    </div>
                </div>
                <div class="mt-3">
                    <span class="badge bg-success-subtle text-success">{{ $asetBaik }} Baik</span>
                    <span class="badge bg-info-subtle text-info">{{ $asetDipinjam }} Dipinjam</span>
                </div>
            </div>
        </div>
    </div>

    {{-- NILAI ASET --}}
    <div class="col-xl-3 col-md-6">
        <div class="card card-h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <span class="text-muted mb-3">Total Nilai Aset</span>
                        <h2 class="mb-0">Rp {{ number_format($nilaiAset, 0, ',', '.') }}</h2>
                    </div>
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-soft-success text-success rounded fs-2"><i class="ri-money-dollar-circle-line"></i></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    {{-- PEMINJAMAN --}}
    <div class="col-xl-3 col-md-6">
        <div class="card card-h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <span class="text-muted mb-3">Peminjaman Aktif</span>
                        <h2 class="mb-0">{{ $pinjamanAktif }}</h2>
                    </div>
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-soft-purple text-purple rounded fs-2"><i class="ri-exchange-funds-line"></i></span>
                    </div>
                </div>
                <div class="mt-3">
                    <span class="badge bg-warning-subtle text-warning">{{ $pinjamanPending }} Pending</span>
                    <span class="badge bg-danger-subtle text-danger">{{ $pinjamanTerlambat }} Terlambat</span>
                </div>
            </div>
        </div>
    </div>

    {{-- JADWAL MAINTENANCE --}}
    <div class="col-xl-3 col-md-6">
        <div class="card card-h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <span class="text-muted mb-3">Jadwal Maintenance</span>
                        <h2 class="mb-0">{{ $overdueMaintenance }}</h2>
                    </div>
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-soft-orange text-orange rounded fs-2"><i class="ri-tools-line"></i></span>
                    </div>
                </div>
                <div class="mt-3">
                    <span class="badge bg-danger-subtle text-danger">Overdue</span>
                    <a href="{{ route('sarpras.pemeliharaan.schedule.index') }}" class="badge bg-soft-secondary text-secondary">Lihat Jadwal</a>
                </div>
            </div>
        </div>
    </div>

    {{-- BOOKING --}}
    <div class="col-xl-3 col-md-6">
        <div class="card card-h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <span class="text-muted mb-3">Booking Hari Ini</span>
                        <h2 class="mb-0">{{ $bookingHariIni }}</h2>
                    </div>
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-soft-teal text-teal rounded fs-2"><i class="ri-calendar-check-line"></i></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- PENGADAAN --}}
    <div class="col-xl-3 col-md-6">
        <div class="card card-h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <span class="text-muted mb-3">Pengadaan Pending</span>
                        <h2 class="mb-0">{{ $pengadaanPending }}</h2>
                    </div>
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-soft-pink text-pink rounded fs-2"><i class="ri-shopping-cart-line"></i></span>
                    </div>
                </div>
                <div class="mt-3">
                    <span class="badge bg-success-subtle text-success">{{ $pengadaanApproved }} Approved</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    {{-- CHART KONDISI ASET --}}
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Kondisi Aset</h5>
            </div>
            <div class="card-body">
                <canvas id="chartKondisi" height="200"></canvas>
            </div>
        </div>
    </div>

    {{-- CHART STATUS ASET --}}
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Status Aset</h5>
            </div>
            <div class="card-body">
                <canvas id="chartStatus" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row">
    {{-- JADWAL MAINTENANCE TERDEKAT --}}
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <h5 class="card-title mb-0">Jadwal Maintenance Mendatang</h5>
                <a href="{{ route('sarpras.pemeliharaan.schedule.index') }}" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
            </div>
            <div class="card-body">
                @if($jadwalMaintenance->isEmpty())
                    <p class="text-muted text-center">Tidak ada jadwal maintenance terdekat.</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-nowrap">
                            <thead>
                                <tr>
                                    <th>Jenis</th>
                                    <th>Target</th>
                                    <th>Tanggal</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($jadwalMaintenance as $j)
                                <tr>
                                    <td>{{ $j->maintenance_type }}</td>
                                    <td>{{ $j->asset?->asset_name ?? $j->room?->room_name ?? $j->building?->building_name ?? '-' }}</td>
                                    <td>{{ $j->next_maintenance_date->format('d/m/Y') }}</td>
                                    <td>
                                        @if($j->next_maintenance_date->isPast())
                                            <span class="badge bg-danger">Overdue</span>
                                        @elseif($j->next_maintenance_date->diffInDays(now()) <= 7)
                                            <span class="badge bg-warning">Soon</span>
                                        @else
                                            <span class="badge bg-success">OK</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- AKTIVITAS TERAKHIR --}}
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Aktivitas Terakhir</h5>
            </div>
            <div class="card-body">
                @if($recentLoans->isEmpty())
                    <p class="text-muted text-center">Belum ada aktivitas peminjaman.</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-nowrap">
                            <thead>
                                <tr>
                                    <th>Aset</th>
                                    <th>Peminjam</th>
                                    <th>Status</th>
                                    <th>Tanggal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentLoans as $l)
                                <tr>
                                    <td>{{ $l->asset?->asset_name ?? '-' }}</td>
                                    <td>{{ $l->borrower?->name ?? '-' }}</td>
                                    <td>
                                        @php $s = ['pending'=>'warning','approved'=>'info','dipinjam'=>'primary','dikembalikan'=>'success','terlambat'=>'danger','dibatalkan'=>'secondary']; @endphp
                                        <span class="badge bg-{{ $s[$l->status] ?? 'secondary' }}-subtle text-{{ $s[$l->status] ?? 'secondary' }}">
                                            {{ ucfirst(str_replace('_',' ',$l->status)) }}
                                        </span>
                                    </td>
                                    <td>{{ $l->created_at->format('d/m/Y') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx1 = document.getElementById('chartKondisi').getContext('2d');
new Chart(ctx1, {
    type: 'doughnut',
    data: {
        labels: {!! json_encode($chartKondisi['labels']) !!},
        datasets: [{ data: {!! json_encode($chartKondisi['data']) !!}, backgroundColor: {!! json_encode($chartKondisi['colors']) !!} }]
    }
});

const ctx2 = document.getElementById('chartStatus').getContext('2d');
new Chart(ctx2, {
    type: 'pie',
    data: {
        labels: {!! json_encode($chartStatus['labels']) !!},
        datasets: [{ data: {!! json_encode($chartStatus['data']) !!}, backgroundColor: {!! json_encode($chartStatus['colors']) !!} }]
    }
});
</script>
@endsection
