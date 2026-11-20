{{-- Menu Data GTK per satuan kerja — berlaku untuk semua role/satuan kerja.
     Satuan kerja diambil dari konteks route (asrama / satuan kerja), lalu
     fallback ke penempatan kerja utama user yang sedang login. --}}
@php
    $dataGtkRouteParams = request()->route()?->parameters() ?? [];

    $dataGtkWorkUnitId = $dataGtkRouteParams['workUnitUuid']
        ?? $dataGtkRouteParams['satuanKerja']
        ?? null;

    if (! $dataGtkWorkUnitId && ! empty($dataGtkRouteParams['asramaUuid'])) {
        $dataGtkWorkUnitId = \App\Models\Dormitory::query()
            ->whereKey($dataGtkRouteParams['asramaUuid'])
            ->value('work_unit_id');
    }

    if (! $dataGtkWorkUnitId && ! empty($userId)) {
        $dataGtkWorkUnitId = \App\Models\GtkWorkUnit::query()
            ->where('user_id', $userId)
            ->orderByDesc('is_primary')
            ->value('work_unit_id');
    }

    $dataGtkWorkUnit = $dataGtkWorkUnitId
        ? \App\Models\WorkUnit::query()->find($dataGtkWorkUnitId)
        : null;
@endphp

@if($dataGtkWorkUnit && menu_allowed('gtk'))
    <li class="menu-title"><span>Data Satuan Kerja</span></li>
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.gtk.by-work-unit']) ? ' active' : '' }}"
           href="{{ route('user.gtk.by-work-unit', ['userId' => $userId, 'satuanKerja' => $dataGtkWorkUnit->id]) }}">
            <i class="ri-team-line"></i>
            <span>Data GTK {{ $dataGtkWorkUnit->name }}</span>
        </a>
    </li>
@endif
