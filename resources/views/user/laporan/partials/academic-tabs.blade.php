@php
    $academicTabs = [
        ['route' => 'user.laporan.akademik', 'label' => 'Analisis STS/SAS', 'icon' => 'ri-line-chart-line'],
        ['route' => 'user.laporan.akademik.ketuntasan', 'label' => 'Ketuntasan', 'icon' => 'ri-checkbox-circle-line'],
        ['route' => 'user.laporan.akademik.kehadiran', 'label' => 'Kehadiran Santri', 'icon' => 'ri-calendar-check-line'],
        ['route' => 'user.laporan.akademik.keterlaksanaan', 'label' => 'Keterlaksanaan', 'icon' => 'ri-book-open-line'],
        ['route' => 'user.laporan.akademik.administrasi', 'label' => 'Administrasi', 'icon' => 'ri-folder-check-line'],
    ];
    $academicQuery = request()->query();
    unset($academicQuery['page']);
@endphp

<div class="d-flex flex-wrap gap-2 mb-3">
    @foreach($academicTabs as $tab)
        <a href="{{ route($tab['route'], array_merge(['userId' => $userId], $academicQuery)) }}"
           class="btn btn-sm {{ request()->routeIs($tab['route']) ? 'btn-primary' : 'btn-light border' }}">
            <i class="{{ $tab['icon'] }} me-1"></i>{{ $tab['label'] }}
        </a>
    @endforeach
</div>
