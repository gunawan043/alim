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
            ['route' => 'user.laporan.akademik', 'label' => 'Analisis akademik', 'icon' => 'ri-line-chart-line'],
            ['route' => 'user.laporan.populasi.mutasi', 'label' => 'Mutasi & populasi', 'icon' => 'ri-swap-box-line'],
            ['route' => 'user.study-groups.index', 'label' => 'Data rombel', 'icon' => 'ri-building-2-line'],
            ['route' => 'user.broadcasts.index', 'label' => 'Kirim broadcast', 'icon' => 'ri-send-plane-2-line'],
        ],
        'asrama' => [
            ['route' => 'user.asrama.index', 'label' => 'Data asrama', 'icon' => 'ri-home-smile-2-line'],
            ['route' => 'user.asrama.permits.verify', 'label' => 'Verifikasi izin', 'icon' => 'ri-walk-line'],
            ['route' => 'user.asrama.visits.verify', 'label' => 'Verifikasi kunjungan', 'icon' => 'ri-calendar-2-line'],
        ],
        'uks' => [
            ['route' => 'user.uks.dashboard', 'label' => 'Dashboard UKS', 'icon' => 'ri-heart-pulse-line'],
            ['route' => 'user.uks.health-metrics.dashboard', 'label' => 'Metrik kesehatan', 'icon' => 'ri-bar-chart-line'],
        ],
        'tahfidz' => [
            ['route' => 'user.tahfidz.setoran.create', 'label' => 'Input setoran', 'icon' => 'ri-add-box-line'],
            ['route' => 'user.tahfidz.setoran.index', 'label' => 'Riwayat setoran', 'icon' => 'ri-book-2-line'],
        ],
        'keuangan' => [
            ['route' => 'user.payroll.index', 'label' => 'Payroll', 'icon' => 'ri-money-dollar-circle-line'],
            ['route' => 'user.laporan.index', 'label' => 'Laporan keuangan', 'icon' => 'ri-file-list-3-line'],
        ],
        'personalia' => [
            ['route' => 'user.laporan.gtk', 'label' => 'Laporan GTK', 'icon' => 'ri-team-line'],
            ['route' => 'user.humas.announcements.index', 'label' => 'Kelola pengumuman', 'icon' => 'ri-megaphone-line'],
            ['route' => 'user.humas.complaints.index', 'label' => 'Aduan', 'icon' => 'ri-customer-service-2-line'],
        ],
        'unit-rumah-tangga' => [
            ['route' => 'sarpras.dashboard', 'label' => 'Dashboard Sarpras', 'icon' => 'ri-home-gear-line'],
        ],
        'pimpinan' => [
            ['route' => 'user.laporan.index', 'label' => 'Semua laporan', 'icon' => 'ri-file-list-2-line'],
            ['route' => 'user.laporan.akademik', 'label' => 'Analisis akademik', 'icon' => 'ri-line-chart-line'],
        ],
        default => [
            ['route' => 'user.laporan.index', 'label' => 'Semua laporan', 'icon' => 'ri-file-list-2-line'],
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
            <h5 class="card-title">Tindak lanjut</h5>
        </div>
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2">
                @foreach($links as $link)
                    <a href="{{ $link['url'] }}" class="dash-action {{ $loop->first ? 'is-primary' : '' }}">
                        <i class="{{ $link['icon'] }}"></i>{{ $link['label'] }}
                    </a>
                @endforeach
            </div>
        </div>
    </div>
@endif
