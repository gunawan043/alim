{{-- ============================================================
     Persona Page Header — HRIS-grade page header component
     Params:
       title        : string  — page title
       description  : string  — subtitle/description
       icon         : string  — Remix Icon class (e.g. "ri-file-paper-line")
       iconColor    : string  — CSS color for icon (default: brand primary)
       breadcrumbs  : array   — [{label, url?}]  (optional, auto-generated if omitted)
       stats        : array   — [{label, value, color, icon?}]  (optional)
       filters      : string  — HTML filter bar (optional)
       actions      : string  — HTML action buttons (optional)
       showBreadcrumb: bool  — force breadcrumb on/off
     Usage:
       @include('components.personalia-page-header', [
         'title' => 'Daftar Kontrak',
         'description' => 'Kelola kontrak kerja GTK',
         'icon' => 'ri-file-paper-line',
         'stats' => $stats,
         'filters' => $filterHtml,
         'actions' => $actionHtml
       ])
============================================================ --}}
@php
    $iconColor = $iconColor ?? '#405189';
    $showBreadcrumb = $showBreadcrumb ?? true;

    // Auto-generate breadcrumbs from route if not provided
    if (!isset($breadcrumbs)) {
        $breadcrumbs = [];
        if (isset($li_1)) {
            $breadcrumbs[] = ['label' => $li_1, 'url' => isset($li_1_url) ? $li_1_url : null];
        }
        if (isset($li_2)) {
            $breadcrumbs[] = ['label' => $li_2, 'url' => isset($li_2_url) ? $li_2_url : null];
        }
        if (isset($title)) {
            $breadcrumbs[] = ['label' => $title, 'url' => null];
        }
    }
@endphp

{{-- Page Header --}}
<div class="row mb-4">
    <div class="col-12">
        <div class="page-header-card rounded-3 border-0 shadow-sm">
            <div class="row align-items-center g-2">
                {{-- Icon + Title block --}}
                <div class="col">
                    @if($showBreadcrumb && count($breadcrumbs) > 0)
                    <div class="page-header-breadcrumb mb-2">
                        <ol class="breadcrumb mb-0">
                            @foreach($breadcrumbs as $i => $crumb)
                                @if(!$loop->last && isset($crumb['url']))
                                    <li class="breadcrumb-item"><a href="{{ $crumb['url'] }}">{{ $crumb['label'] }}</a></li>
                                @elseif(!$loop->last)
                                    <li class="breadcrumb-item">{{ $crumb['label'] }}</li>
                                @else
                                    <li class="breadcrumb-item active" aria-current="page">{{ $crumb['label'] }}</li>
                                @endif
                            @endforeach
                        </ol>
                    </div>
                    @endif
                    <div class="d-flex align-items-center gap-3">
                        @if(isset($icon))
                        <div class="page-header-icon" style="background: {{ $iconColor }}18; color: {{ $iconColor }};">
                            <i class="{{ $icon }} fs-4"></i>
                        </div>
                        @endif
                        <div>
                            <h2 class="page-header-title mb-0">{{ $title }}</h2>
                            @if(isset($description))
                                <p class="page-header-desc mb-0">{{ $description }}</p>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Action buttons block --}}
                @if(isset($actions))
                <div class="col-auto">
                    <div class="d-flex gap-2 flex-wrap">
                        @yield('page_actions')
                    </div>
                </div>
                @endif
            </div>

            {{-- Stats row --}}
            @if(isset($stats) && count($stats) > 0)
            <div class="row g-3 mt-2">
                @foreach($stats as $stat)
                <div class="col-sm-6 col-md-3">
                    <div class="stat-card rounded-2 p-3 text-center">
                        <div class="stat-icon mb-2" style="color: {{ $stat['color'] ?? $iconColor }};">
                            <i class="{{ $stat['icon'] ?? $icon }} fs-4"></i>
                        </div>
                        <div class="stat-value fw-bold fs-3" style="color: {{ $stat['color'] ?? $iconColor }};">{{ $stat['value'] }}</div>
                        <div class="stat-label text-muted small">{{ $stat['label'] }}</div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Filter bar --}}
@if(isset($filters))
<div class="row mb-3">
    <div class="col-12">
        <div class="filter-bar rounded-2 border p-3">
            {!! $filters !!}
        </div>
    </div>
</div>
@endif

<style>
.page-header-card {
    background: linear-gradient(135deg, #f8f9ff 0%, #f0f2ff 100%);
    padding: 1.25rem 1.5rem;
    border: 1px solid #e4e7f5 !important;
}
[data-bs-theme="dark"] .page-header-card {
    background: linear-gradient(135deg, #1a1f3a 0%, #1e2445 100%);
    border-color: #2a3055 !important;
}
.page-header-icon {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.page-header-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: #212529;
    line-height: 1.3;
}
[data-bs-theme="dark"] .page-header-title { color: #f8f9ff; }
.page-header-desc {
    font-size: 0.825rem;
    color: #6c757d;
    line-height: 1.4;
}
[data-bs-theme="dark"] .page-header-desc { color: #a0a8c0; }
.page-header-breadcrumb .breadcrumb {
    margin-bottom: 0;
    font-size: 0.775rem;
}
.page-header-breadcrumb .breadcrumb-item a { color: #6c757d; text-decoration: none; }
.page-header-breadcrumb .breadcrumb-item a:hover { color: #405189; }
.page-header-breadcrumb .breadcrumb-item + .breadcrumb-item::before {
    font-size: 0.7rem;
    vertical-align: middle;
}
.stat-card {
    background: rgba(255,255,255,0.7);
    border: 1px solid #e4e7f5;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
[data-bs-theme="dark"] .stat-card {
    background: rgba(255,255,255,0.04);
    border-color: #2a3055;
}
.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}
.stat-value { line-height: 1.1; }
.stat-label { line-height: 1.2; }
.filter-bar { background: #fff; border-color: #e4e7f5 !important; }
[data-bs-theme="dark"] .filter-bar { background: #1a1f3a; border-color: #2a3055 !important; }
</style>
