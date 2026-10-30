@php
    /**
     * Aksi lanjutan per jabatan.
     * Tautan hanya dirender bila route tersedia — tidak ada tombol mati.
     */
    $user = $user ?? auth()->user();
    $userId = $user?->id;
    $analytics = $dashboard['analytics'] ?? [];

    $catalog = match ($roleSlug) {
        'satuan-pendidikan' => [
            ['route' => 'user.laporan.akademik', 'label' => 'Analisis Akademik', 'icon' => 'ri-line-chart-line', 'color' => 'primary'],
            ['route' => 'user.laporan.populasi.mutasi', 'label' => 'Mutasi & Populasi', 'icon' => 'ri-swap-box-line', 'color' => 'info'],
            ['route' => 'user.study-groups.index', 'label' => 'Data Rombel', 'icon' => 'ri-building-2-line', 'color' => 'success'],
            ['route' => 'user.broadcasts.index', 'label' => 'Kirim Broadcast', 'icon' => 'ri-send-plane-2-line', 'color' => 'warning'],
        ],
        'asrama' => [
            ['route' => 'user.asrama.index', 'label' => 'Data Asrama', 'icon' => 'ri-home-smile-2-line', 'color' => 'primary'],
            ['route' => 'user.asrama.permits.verify', 'label' => 'Verifikasi Izin', 'icon' => 'ri-walk-line', 'color' => 'info'],
            ['route' => 'user.asrama.visits.verify', 'label' => 'Verifikasi Kunjungan', 'icon' => 'ri-calendar-2-line', 'color' => 'success'],
        ],
        'uks' => [
            ['route' => 'user.uks.dashboard', 'label' => 'Dashboard UKS', 'icon' => 'ri-heart-pulse-line', 'color' => 'primary'],
            ['route' => 'user.uks.health-metrics.dashboard', 'label' => 'Metrik Kesehatan', 'icon' => 'ri-bar-chart-line', 'color' => 'info'],
        ],
        'tahfidz' => [
            ['route' => 'user.tahfidz.setoran.create', 'label' => 'Input Setoran', 'icon' => 'ri-add-box-line', 'color' => 'primary'],
            ['route' => 'user.tahfidz.setoran.index', 'label' => 'Riwayat Setoran', 'icon' => 'ri-book-2-line', 'color' => 'info'],
        ],
        'keuangan' => [
            ['route' => 'user.payroll.index', 'label' => 'Payroll', 'icon' => 'ri-money-dollar-circle-line', 'color' => 'primary'],
            ['route' => 'user.laporan.index', 'label' => 'Laporan Keuangan', 'icon' => 'ri-file-list-3-line', 'color' => 'info'],
        ],
        'personalia' => [
            ['route' => 'user.laporan.gtk', 'label' => 'Laporan GTK', 'icon' => 'ri-team-line', 'color' => 'primary'],
            ['route' => 'user.humas.announcements.index', 'label' => 'Kelola Pengumuman', 'icon' => 'ri-megaphone-line', 'color' => 'info'],
            ['route' => 'user.humas.complaints.index', 'label' => 'Aduan', 'icon' => 'ri-customer-service-2-line', 'color' => 'warning'],
        ],
        'unit-rumah-tangga' => [
            ['route' => 'sarpras.dashboard', 'label' => 'Dashboard Sarpras', 'icon' => 'ri-home-gear-line', 'color' => 'primary'],
        ],
        'pimpinan' => [
            ['route' => 'user.laporan.index', 'label' => 'Semua Laporan', 'icon' => 'ri-file-list-2-line', 'color' => 'primary'],
            ['route' => 'user.laporan.akademik', 'label' => 'Analisis Akademik', 'icon' => 'ri-line-chart-line', 'color' => 'info'],
        ],
        default => [
            ['route' => 'user.laporan.index', 'label' => 'Semua Laporan', 'icon' => 'ri-file-list-2-line', 'color' => 'primary'],
        ],
    };

    $links = collect($catalog)
        ->filter(fn ($item) => $userId && \Illuminate\Support\Facades\Route::has($item['route']))
        ->map(fn ($item) => $item + ['url' => route($item['route'], ['userId' => $userId])])
        ->values();
@endphp

@if($links->isNotEmpty())
    <div class="card mb-3">
        <div class="card-header">
            <h5 class="card-title mb-0"><i class="ri-flashlight-line me-1 text-warning"></i>Tindak Lanjut</h5>
        </div>
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2">
                @foreach($links as $link)
                    <a href="{{ $link['url'] }}" class="btn btn-sm btn-{{ $link['color'] }}-subtle text-{{ $link['color'] }} border-0">
                        <i class="{{ $link['icon'] }} me-1"></i>{{ $link['label'] }}
                    </a>
                @endforeach
            </div>
        </div>
    </div>
@endif
