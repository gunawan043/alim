@php
    $osippUserId = $userId ?? auth()->id();
    $osippCurrentPeriodId = $period?->id;
    $osippUrl = fn (string $name, array $extra = []) => route($name, array_merge([
        'userId' => $osippUserId,
        'period_id' => $osippCurrentPeriodId,
    ], $extra));
    $osippNav = [
        ['route' => 'osipp.dashboard', 'label' => 'Ringkasan', 'icon' => 'ri-dashboard-3-line'],
        ['route' => 'osipp.periode.index', 'label' => 'Kepengurusan', 'icon' => 'ri-calendar-event-line'],
        ['route' => 'osipp.anggota.index', 'label' => 'Anggota', 'icon' => 'ri-group-line'],
        ['route' => 'osipp.qism.index', 'label' => 'Qism', 'icon' => 'ri-node-tree'],
        ['route' => 'osipp.program.index', 'label' => 'Program Kerja', 'icon' => 'ri-list-check-2'],
        ['route' => 'osipp.kegiatan.index', 'label' => 'Kegiatan', 'icon' => 'ri-flag-line'],
        ['route' => 'osipp.administrasi.index', 'label' => 'Administrasi', 'icon' => 'ri-folder-shield-2-line'],
        ['route' => 'osipp.pembinaan.index', 'label' => 'Pembinaan', 'icon' => 'ri-user-heart-line'],
        ['route' => 'osipp.evaluasi.index', 'label' => 'Evaluasi', 'icon' => 'ri-award-line'],
    ];
@endphp

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
    <div>
        <h4 class="mb-1">Ruang Kerja OSIPP</h4>
        <p class="text-muted small mb-0">
            Pembinaan, administrasi, program kerja, verifikasi kegiatan, dan evaluasi kepengurusan.
            <span class="text-muted">Tanpa pengelolaan keuangan.</span>
        </p>
    </div>
    <div class="d-flex flex-wrap align-items-center gap-2">
        @if($periods->isNotEmpty())
            <form method="GET" action="{{ url()->current() }}" class="d-flex align-items-center gap-2">
                @foreach(request()->except(['period_id', 'page']) as $key => $value)
                    @if(is_scalar($value) && $value !== '')
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach
                <label class="text-muted small mb-0" for="osippPeriodSelect">Periode</label>
                <select id="osippPeriodSelect" name="period_id" class="form-select form-select-sm osipp-period-select" onchange="this.form.submit()">
                    @foreach($periods as $periodOption)
                        <option value="{{ $periodOption->id }}" @selected($period?->id === $periodOption->id)>
                            {{ $periodOption->name }} · {{ $periodOption->status_label }}
                        </option>
                    @endforeach
                </select>
            </form>
        @endif
        @if($canManage)
            <a href="{{ route('osipp.periode.index', ['userId' => $osippUserId]) }}" class="btn btn-sm btn-outline-primary">
                <i class="ri-settings-3-line me-1"></i>Kelola Periode
            </a>
        @endif
    </div>
</div>

<div class="card osipp-shell mb-3">
    <div class="card-body py-2 px-2 px-md-3">
        <ul class="nav nav-tabs osipp-nav border-0 flex-nowrap overflow-auto">
            @foreach($osippNav as $item)
                @if($period || $item['route'] === 'osipp.dashboard' || $item['route'] === 'osipp.periode.index')
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs($item['route'].'*') ? 'active' : '' }}"
                           href="{{ $osippUrl($item['route']) }}">
                            <i class="{{ $item['icon'] }} me-1"></i>{{ $item['label'] }}
                        </a>
                    </li>
                @endif
            @endforeach
        </ul>
    </div>
</div>

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Periksa kembali data:</strong>
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
@endif
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
@endif
