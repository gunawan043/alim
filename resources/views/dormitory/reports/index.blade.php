@extends('layouts.master')
@section('title', 'Laporan Asrama')

@section('content')
    @php
        $reportQuery = [
            'userId' => $userId,
            'asramaUuid' => $asramaUuid,
            'month' => $period['month'],
            'year' => $period['year'],
        ];
        $periodLabel = \Carbon\Carbon::createFromDate($period['year'], $period['month'], 1)->locale('id')->isoFormat('MMMM YYYY');

        $reports = [
            ['category' => 'Periodik', 'title' => 'Rekap Presensi', 'desc' => 'Kehadiran santri per sesi dan status absensi.', 'icon' => 'ri-calendar-check-line', 'tone' => 'success', 'format' => 'CSV', 'route' => 'user.asrama.reports.attendance'],
            ['category' => 'Periodik', 'title' => 'Laporan Perizinan', 'desc' => 'Data izin, penjemput, QR, dan kepulangan aktual.', 'icon' => 'ri-pass-valid-line', 'tone' => 'primary', 'format' => 'CSV', 'route' => 'user.asrama.reports.permits', 'excel' => 'user.asrama.reports.permits.excel'],
            ['category' => 'Periodik', 'title' => 'Laporan Pelanggaran', 'desc' => 'Pelanggaran santri, poin, dan tindakan.', 'icon' => 'ri-error-warning-line', 'tone' => 'danger', 'format' => 'CSV', 'route' => 'user.asrama.reports.violations'],
            ['category' => 'Periodik', 'title' => 'Laporan Penghargaan', 'desc' => 'Penghargaan, kategori, level, dan pemberi.', 'icon' => 'ri-medal-line', 'tone' => 'warning', 'format' => 'CSV', 'route' => 'user.asrama.reports.rewards'],
            ['category' => 'Operasional', 'title' => 'Inventaris Kamar', 'desc' => 'Kondisi dan jumlah inventaris seluruh kamar.', 'icon' => 'ri-tools-line', 'tone' => 'secondary', 'format' => 'HTML', 'route' => 'user.asrama.reports.inventories'],
            ['category' => 'Operasional', 'title' => 'Kebersihan Asrama', 'desc' => 'Hasil inspeksi, skor, dan tindak lanjut kebersihan.', 'icon' => 'ri-broom-line', 'tone' => 'success', 'format' => 'HTML', 'route' => 'user.asrama.reports.sanitation'],
            ['category' => 'Operasional', 'title' => 'Penghuni & Kapasitas', 'desc' => 'Rekap penghuni dan kapasitas setiap kamar.', 'icon' => 'ri-building-line', 'tone' => 'info', 'format' => 'HTML', 'route' => 'user.asrama.reports.occupancy'],
            ['category' => 'Aktivitas', 'title' => 'Kunjungan', 'desc' => 'Kunjungan keluarga, status santri, dan waktu scan.', 'icon' => 'ri-user-voice-line', 'tone' => 'info', 'format' => 'CSV', 'route' => 'user.asrama.reports.visits'],
            ['category' => 'Aktivitas', 'title' => 'Mutasi Kamar', 'desc' => 'Perpindahan kamar dan status persetujuannya.', 'icon' => 'ri-arrow-left-right-line', 'tone' => 'primary', 'format' => 'CSV', 'route' => 'user.asrama.reports.room-moves'],
            ['category' => 'Aktivitas', 'title' => 'Keluar Asrama', 'desc' => 'Santri keluar asrama dan alasan check-out.', 'icon' => 'ri-logout-box-r-line', 'tone' => 'warning', 'format' => 'CSV', 'route' => 'user.asrama.reports.checkouts'],
            ['category' => 'Aktivitas', 'title' => 'Kegiatan Asrama', 'desc' => 'Rekap presensi dan pelaksanaan kegiatan.', 'icon' => 'ri-calendar-event-line', 'tone' => 'success', 'format' => 'CSV', 'route' => 'user.asrama.reports.activities'],
            ['category' => 'Audit', 'title' => 'Audit Log', 'desc' => 'Riwayat aksi izin, mutasi, kegiatan, dan broadcast.', 'icon' => 'ri-history-line', 'tone' => 'dark', 'format' => 'HTML', 'route' => 'user.asrama.reports.audit'],
        ];
    @endphp

    @component('components.breadcrumb')
        @slot('li_1') Asrama @endslot
        @slot('li_2') <a href="{{ route('user.asrama.show', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) }}">{{ $dormitory->name }}</a> @endslot
        @slot('title') Laporan @endslot
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ri-check-line me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-1"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    @php
        $totalAttendance = $stats['attendance'] ?? 0;
        $totalPermits = $stats['permits'] ?? 0;
        $totalViolations = $stats['violations'] ?? 0;
        $totalRewards = $stats['rewards'] ?? 0;
    @endphp

    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate stat-card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded"><i class="ri-calendar-check-line text-success"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="stat-label mb-0">Presensi</p>
                            <h3 class="stat-value">{{ number_format($totalAttendance) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-information-line me-1"></i>Catatan pada {{ $periodLabel }}</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate stat-card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded"><i class="ri-pass-valid-line text-primary"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="stat-label mb-0">Perizinan</p>
                            <h3 class="stat-value">{{ number_format($totalPermits) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-information-line me-1"></i>Pengajuan pada {{ $periodLabel }}</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate stat-card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-danger-subtle rounded"><i class="ri-error-warning-line text-danger"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="stat-label mb-0">Pelanggaran</p>
                            <h3 class="stat-value">{{ number_format($totalViolations) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-information-line me-1"></i>Tercatat pada {{ $periodLabel }}</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate stat-card h-90">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded"><i class="ri-medal-line text-warning"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <p class="stat-label mb-0">Penghargaan</p>
                            <h3 class="stat-value">{{ number_format($totalRewards) }}</h3>
                        </div>
                    </div>
                    <p class="stat-note mb-0"><i class="ri-information-line me-1"></i>Diberikan pada {{ $periodLabel }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header border-bottom-dashed">
            <div class="row g-3 align-items-center">
                <div class="col-sm">
                    <h5 class="card-title mb-0"><i class="ri-file-chart-line me-1 text-primary"></i>Pusat Laporan Asrama</h5>
                    <p class="text-muted mb-0 small">Unduh laporan periodik, operasional, aktivitas, dan audit dalam satu halaman.</p>
                </div>
                <div class="col-sm-auto">
                    <div class="toolbar-actions">
                        <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#reportFilterModal">
                            <i class="ri-filter-3-line me-1"></i> Filter Periode
                        </button>
                        <a href="{{ route('user.asrama.reports.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) }}"
                           class="btn btn-light btn-sm" title="Reset periode">
                            <i class="ri-refresh-line"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                <span class="filter-badge active">
                    <i class="ri-calendar-line"></i> Periode: {{ $periodLabel }}
                </span>
                <span class="text-muted small"><i class="ri-information-line me-1"></i>{{ count($reports) }} jenis laporan tersedia</span>
            </div>

            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <div class="d-flex gap-2" style="max-width:420px;width:100%;">
                    <input type="search" id="reportSearch" class="form-control form-control-sm" placeholder="Cari laporan...">
                    <button type="button" class="btn btn-primary btn-sm flex-shrink-0" onclick="filterReports()">
                        <i class="ri-search-line"></i> Cari
                    </button>
                </div>
                <span class="text-muted small" id="reportVisibleCount">Menampilkan {{ count($reports) }} laporan</span>
            </div>

            <div class="table-container">
                <table class="table table-hover align-middle table-freeze mb-0" id="reportTable">
                    <thead>
                        <tr>
                            <th style="width:180px;">Laporan</th>
                            <th>Keterangan</th>
                            <th class="text-center">Kategori</th>
                            <th class="text-center">Format</th>
                            <th class="text-center" style="width:220px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reports as $report)
                            <tr data-search-text="{{ strtolower($report['title'].' '.$report['desc'].' '.$report['category']) }}">
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="avatar-xs rounded-circle bg-{{ $report['tone'] }}-subtle d-flex align-items-center justify-content-center">
                                            <i class="{{ $report['icon'] }} text-{{ $report['tone'] }}"></i>
                                        </span>
                                        <span class="fw-semibold">{{ $report['title'] }}</span>
                                    </div>
                                </td>
                                <td class="text-muted small">{{ $report['desc'] }}</td>
                                <td class="text-center"><span class="badge bg-secondary-subtle text-secondary">{{ $report['category'] }}</span></td>
                                <td class="text-center"><span class="badge bg-info-subtle text-info">{{ $report['format'] }}</span></td>
                                <td class="text-center">
                                    <div class="d-flex gap-1 justify-content-center">
                                        <a href="{{ route($report['route'], $reportQuery) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="ri-download-2-line me-1"></i>Unduh
                                        </a>
                                        @if(!empty($report['excel']))
                                            <a href="{{ route($report['excel'], $reportQuery) }}" class="btn btn-sm btn-outline-success" title="Unduh Excel">
                                                <i class="ri-file-excel-2-line"></i>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="reportFilterModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="GET" action="{{ route('user.asrama.reports.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) }}">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="ri-filter-3-line me-2 text-primary"></i>Filter Periode Laporan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="filter-group mb-0">
                                    <div class="filter-group-title"><i class="ri-calendar-line"></i>Bulan</div>
                                    <select name="month" class="form-select">
                                        @for($m = 1; $m <= 12; $m++)
                                            <option value="{{ $m }}" {{ $period['month'] === $m ? 'selected' : '' }}>
                                                {{ \Carbon\Carbon::createFromDate($period['year'], $m, 1)->locale('id')->isoFormat('MMMM') }}
                                            </option>
                                        @endfor
                                    </select>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="filter-group mb-0">
                                    <div class="filter-group-title"><i class="ri-calendar-event-line"></i>Tahun</div>
                                    <select name="year" class="form-select">
                                        @for($y = 2024; $y <= date('Y') + 2; $y++)
                                            <option value="{{ $y }}" {{ $period['year'] === $y ? 'selected' : '' }}>{{ $y }}</option>
                                        @endfor
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-text mt-3 mb-0">Periode ini berlaku untuk laporan periodik dan rekap aktivitas.</div>
                    </div>
                    <div class="modal-footer">
                        <a href="{{ route('user.asrama.reports.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) }}" class="btn btn-light">Reset</a>
                        <button type="submit" class="btn btn-primary"><i class="ri-filter-3-line me-1"></i>Terapkan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    const search = document.getElementById('reportSearch');
    const rows = Array.from(document.querySelectorAll('#reportTable tbody tr[data-search-text]'));
    const count = document.getElementById('reportVisibleCount');
    if (!search || !rows.length) return;

    window.filterReports = function () {
        const query = (search.value || '').trim().toLowerCase();
        let visible = 0;

        rows.forEach(function (row) {
            const match = !query || (row.dataset.searchText || '').includes(query);
            row.style.display = match ? '' : 'none';
            if (match) visible++;
        });

        if (count) count.textContent = 'Menampilkan ' + visible + ' laporan';
    };

    search.addEventListener('input', window.filterReports);
})();
</script>
@endpush
