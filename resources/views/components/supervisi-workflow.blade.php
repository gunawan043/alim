@props(['workflow' => null, 'current' => null, 'yearId' => null, 'semester' => 1, 'admin' => true])

@php
    $steps = $workflow['steps'] ?? [
        ['key' => 'team', 'label' => 'Tim & SK', 'done' => false, 'detail' => 'Belum dicek', 'route' => 'waka.supervisi-sk.index'],
        ['key' => 'instrument', 'label' => 'Instrumen', 'done' => false, 'detail' => 'Belum dicek', 'route' => 'waka.supervisi-instrumen.index'],
        ['key' => 'setting', 'label' => 'Pengaturan Supervisor', 'done' => false, 'detail' => 'Belum dicek', 'route' => 'waka.supervisi-pengaturan.index'],
        ['key' => 'schedule', 'label' => 'Jadwal', 'done' => false, 'detail' => 'Belum dicek', 'route' => 'waka.supervisi.index'],
        ['key' => 'assessment', 'label' => 'Penilaian', 'done' => false, 'detail' => 'Belum dicek', 'route' => 'waka.supervisi.index'],
        ['key' => 'report', 'label' => 'Laporan & Refleksi', 'done' => false, 'detail' => 'Belum dicek', 'route' => 'waka.supervisi.index'],
    ];
@endphp

<div class="card border-0 bg-light mb-3">
    <div class="card-body py-2 px-3">
        <div class="d-flex flex-wrap align-items-center gap-2 small">
            <span class="fw-semibold text-muted me-1"><i class="ri-route-line me-1"></i>Alur Supervisi:</span>
            @foreach($steps as $step)
                @php
                    $isAdminStep = in_array($step['key'], ['team', 'setting'], true);
                    $url = route($step['route'], array_filter([
                        'academic_year_id' => $yearId,
                        'semester' => in_array($step['key'], ['team', 'setting'], true) ? $semester : null,
                    ]));
                @endphp
                @if($isAdminStep && ! $admin)
                    <span class="badge bg-white text-muted border" title="{{ $step['detail'] }}">
                        {{ $loop->iteration }}. {{ $step['label'] }}
                    </span>
                @else
                    <a href="{{ $url }}" class="text-decoration-none badge {{ $current === $step['key'] ? 'bg-primary text-white' : ($step['done'] ? 'bg-success-subtle text-success' : 'bg-white text-muted border') }}"
                       title="{{ $step['detail'] }}">
                        @if($step['done'] && $current !== $step['key'])<i class="ri-check-line me-1"></i>@endif
                        {{ $loop->iteration }}. {{ $step['label'] }}
                    </a>
                @endif
                @if(! $loop->last)<i class="ri-arrow-right-s-line text-muted"></i>@endif
            @endforeach
        </div>
        @if($workflow)
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-2">
                <div class="text-muted" style="font-size:.72rem">
                    Semester {{ $semester }} · {{ $workflow['schedule_count'] }} jadwal · {{ $workflow['assessed_count'] }} dinilai · {{ $workflow['reported_count'] }} dilaporkan.
                </div>
                @if($workflow['next_step'])
                    @php $next = $workflow['next_step']; @endphp
                    <a href="{{ route($next['route'], array_filter(['academic_year_id' => $yearId, 'semester' => $semester])) }}" class="btn btn-sm btn-primary">
                        <i class="ri-arrow-right-line me-1"></i>Lanjut: {{ $next['label'] }}
                    </a>
                @endif
            </div>
        @endif
    </div>
</div>
