@props(['workflow' => null, 'current' => null, 'yearId' => null, 'semester' => 1, 'admin' => true])

@php
    $steps = $workflow['steps'] ?? [
        ['key' => 'team', 'label' => 'Tim & SK', 'done' => false, 'detail' => 'Belum dicek', 'route' => 'waka.supervisi-sk.index'],
        ['key' => 'instrument', 'label' => 'Instrumen', 'done' => false, 'detail' => 'Belum dicek', 'route' => 'waka.supervisi-instrumen.index'],
        ['key' => 'schedule', 'label' => 'Jadwal & Supervisor', 'done' => false, 'detail' => 'Belum dicek', 'route' => 'waka.supervisi.index'],
        ['key' => 'distribution', 'label' => 'Distribusi', 'done' => false, 'detail' => 'Belum dicek', 'route' => 'waka.supervisi.index'],
        ['key' => 'assessment', 'label' => 'Penilaian', 'done' => false, 'detail' => 'Belum dicek', 'route' => 'waka.supervisi.index'],
        ['key' => 'report', 'label' => 'Laporan & Refleksi', 'done' => false, 'detail' => 'Belum dicek', 'route' => 'waka.supervisi.index'],
    ];
@endphp

<style>
    .alim-workflow-track { display:flex; gap:8px; overflow-x:auto; padding:4px 0 2px; }
    .alim-workflow-step { flex:1 0 155px; display:flex; align-items:flex-start; gap:9px; padding:10px 11px; border:1px solid var(--bs-border-color); border-radius:12px; background:var(--bs-body-bg); color:var(--bs-body-color); text-decoration:none; min-width:155px; }
    .alim-workflow-step:hover { border-color:var(--bs-primary); color:var(--bs-primary); }
    .alim-workflow-step.is-current { border-color:var(--bs-primary); box-shadow:0 0 0 2px rgba(var(--bs-primary-rgb),.18); }
    .alim-workflow-step.is-done { background:rgba(25,135,84,.12); border-color:rgba(25,135,84,.45); }
    .alim-workflow-step.is-disabled { opacity:.62; cursor:not-allowed; }
    .alim-workflow-dot { width:25px; height:25px; flex:0 0 25px; display:flex; align-items:center; justify-content:center; border-radius:50%; background:var(--bs-secondary-bg); color:var(--bs-body-color); font-size:11px; font-weight:800; }
    .alim-workflow-step.is-current .alim-workflow-dot { background:var(--bs-primary); color:#fff; }
    .alim-workflow-step.is-done .alim-workflow-dot { background:#198754; color:#fff; }
    .alim-workflow-copy { min-width:0; }
    .alim-workflow-label { display:block; font-size:12px; font-weight:700; line-height:1.25; }
    .alim-workflow-detail { display:block; font-size:10px; color:var(--bs-secondary-color); margin-top:2px; white-space:normal; line-height:1.25; }
    @media (max-width: 767.98px) { .alim-workflow-track { flex-wrap:nowrap; } .alim-workflow-step { flex-basis:145px; } }
</style>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-3">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
            <div>
                <div class="fw-bold"><i class="ri-route-line me-1 text-primary"></i>Alur Supervisi</div>
                <div class="text-muted" style="font-size:.72rem">Ikuti langkah dari kiri ke kanan. Status tercentang berarti langkah sudah lengkap.</div>
            </div>
            @if($workflow)
                @php
                    $next = collect($workflow['steps'])->first(function ($step) use ($admin) {
                        return ! $step['done'] && (($step['key'] !== 'team') || $admin);
                    });
                @endphp
                @if($next)
                    <a href="{{ route($next['route'], array_filter(['academic_year_id' => $yearId, 'semester' => $next['key'] === 'team' ? $semester : null])) }}" class="btn btn-sm btn-primary">
                        <i class="ri-arrow-right-line me-1"></i>Lanjut: {{ $next['label'] }}
                    </a>
                @endif
            @endif
        </div>

        <div class="alim-workflow-track">
            @foreach($steps as $step)
                @php
                    $isAdminStep = in_array($step['key'], ['team'], true);
                    $isCurrent = $current === $step['key'];
                    $url = route($step['route'], array_filter([
                        'academic_year_id' => $yearId,
                        'semester' => $isAdminStep ? $semester : null,
                    ]));
                    $class = 'alim-workflow-step'.($isCurrent ? ' is-current' : '').($step['done'] && ! $isCurrent ? ' is-done' : '').(($isAdminStep && ! $admin) ? ' is-disabled' : '');
                @endphp

                @if($isAdminStep && ! $admin)
                    <span class="{{ $class }}" title="{{ $step['detail'] }}">
                        <span class="alim-workflow-dot">{{ $loop->iteration }}</span>
                        <span class="alim-workflow-copy">
                            <span class="alim-workflow-label">{{ $step['label'] }}</span>
                            <span class="alim-workflow-detail">{{ $step['detail'] }}</span>
                        </span>
                    </span>
                @else
                    <a href="{{ $url }}" class="{{ $class }}" title="{{ $step['detail'] }}">
                        <span class="alim-workflow-dot">
                            @if($step['done'] && ! $isCurrent)<i class="ri-check-line"></i>@else{{ $loop->iteration }}@endif
                        </span>
                        <span class="alim-workflow-copy">
                            <span class="alim-workflow-label">{{ $step['label'] }}</span>
                            <span class="alim-workflow-detail">{{ $step['detail'] }}</span>
                        </span>
                    </a>
                @endif
            @endforeach
        </div>

        @if($workflow)
            <div class="text-muted mt-2" style="font-size:.72rem">
                Semester {{ $semester }} · {{ $workflow['schedule_count'] }} jadwal · {{ $workflow['distributed_count'] }} didistribusikan · {{ $workflow['assessed_count'] }} dinilai · {{ $workflow['reported_count'] }} dilaporkan.
            </div>
        @endif
    </div>
</div>
