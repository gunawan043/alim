@php
    $populationTabs = [
        ['route' => 'user.laporan.populasi.mutasi', 'label' => 'Mutasi Santri', 'icon' => 'ri-swap-box-line'],
        ['route' => 'user.laporan.populasi.pertumbuhan', 'label' => 'Pertumbuhan', 'icon' => 'ri-line-chart-line'],
        ['route' => 'user.laporan.populasi.profil', 'label' => 'Profil & Sebaran', 'icon' => 'ri-pie-chart-line'],
        ['route' => 'user.laporan.populasi.keberlanjutan', 'label' => 'Keberlanjutan', 'icon' => 'ri-route-line'],
        ['route' => 'user.laporan.populasi.kenaikan-kelulusan', 'label' => 'Kenaikan & Kelulusan', 'icon' => 'ri-graduation-cap-line'],
    ];
    $populationQuery = request()->query();
    unset($populationQuery['page']);
@endphp

<div class="d-flex flex-wrap gap-2 mb-3">
    @foreach($populationTabs as $tab)
        <a href="{{ route($tab['route'], array_merge(['userId' => $userId], $populationQuery)) }}"
           class="btn btn-sm {{ request()->routeIs($tab['route']) ? 'btn-primary' : 'btn-light border' }}">
            <i class="{{ $tab['icon'] }} me-1"></i>{{ $tab['label'] }}
        </a>
    @endforeach
</div>
