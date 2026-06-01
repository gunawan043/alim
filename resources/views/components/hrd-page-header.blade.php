{{-- Usage:
@component('components.hrd-page-header', [
    'title' => 'Daftar GTK',
    'subtitle' => 'Kelola data Guru dan Tenaga Kependidikan',
    'icon' => 'ri-contacts-book-2-line',
    'color' => 'blue',
    'stats' => [
        ['label' => 'Total GTK', 'value' => 0, 'icon' => 'ri-user-line', 'color' => 'primary'],
        ['label' => 'Aktif', 'value' => 0, 'icon' => 'ri-checkbox-circle-line', 'color' => 'success'],
    ],
    'actions' => [
        ['label' => 'Tambah', 'icon' => 'ri-add-line', 'route' => route(...), 'color' => 'primary'],
    ]
])
--}}

@php
$stats = $stats ?? [];
$actions = $actions ?? [];
$colorMap = [
    'blue'   => ['bg' => 'bg-primary-subtle',    'text' => 'text-primary',    'border' => 'border-primary'],
    'green'  => ['bg' => 'bg-success-subtle',     'text' => 'text-success',    'border' => 'border-success'],
    'red'    => ['bg' => 'bg-danger-subtle',     'text' => 'text-danger',     'border' => 'border-danger'],
    'amber'  => ['bg' => 'bg-warning-subtle',    'text' => 'text-warning',    'border' => 'border-warning'],
    'violet' => ['bg' => 'bg-info-subtle',        'text' => 'text-info',       'border' => 'border-info'],
    'pink'   => ['bg' => 'bg-pink-subtle',        'text' => 'text-pink',      'border' => 'border-pink'],
    'slate'  => ['bg' => 'bg-secondary-subtle',  'text' => 'text-secondary',  'border' => 'border-secondary'],
];
$color = $color ?? 'blue';
$c = $colorMap[$color] ?? $colorMap['blue'];
@endphp

@if($stats)
<div class="row g-3 mb-4">
    @foreach($stats as $stat)
    @php
        $sc = $colorMap[$stat['color'] ?? 'primary'] ?? $colorMap['primary'];
    @endphp
    <div class="col-sm-6 col-md-3">
        <div class="card stat-card h-100 border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title rounded-circle fs-4 {{ $sc['bg'] }}">
                            <i class="{{ $stat['icon'] ?? 'ri-bar-chart-line' }} {{ $sc['text'] }}"></i>
                        </span>
                    </div>
                    <div>
                        <p class="text-uppercase fw-medium text-muted mb-0" style="font-size:0.7rem; letter-spacing:0.05em;">{{ $stat['label'] }}</p>
                        <h3 class="fw-bold ff-secondary mb-0">{{ $stat['value'] ?? 0 }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif
