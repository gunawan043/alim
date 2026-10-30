@php
    /**
     * @var array $findings  Daftar temuan:
     *   ['title' => string, 'description' => string, 'count' => int,
     *    'severity' => 'info'|'warning'|'danger', 'url' => ?string,
     *    'rule' => string]
     */
@endphp

<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0"><i class="ri-alert-line me-1 text-warning"></i>Temuan &amp; Perlu Perhatian</h5>
        <span class="small text-muted">{{ count($findings) }} temuan</span>
    </div>
    <div class="card-body">
        @forelse($findings as $finding)
            @php
                $severity = $finding['severity'] ?? 'warning';
                $color = match ($severity) {
                    'danger' => 'danger',
                    'info' => 'info',
                    default => 'warning',
                };
            @endphp
            <div class="d-flex align-items-start justify-content-between gap-3 border-bottom py-2">
                <div class="d-flex align-items-start gap-2">
                    <span class="avatar-xs flex-shrink-0">
                        <span class="avatar-title bg-{{ $color }}-subtle text-{{ $color }} rounded-circle">
                            <i class="ri-error-warning-line"></i>
                        </span>
                    </span>
                    <div>
                        <div class="fw-semibold">
                            @if(! empty($finding['url']))
                                <a href="{{ $finding['url'] }}">{{ $finding['title'] }}</a>
                            @else
                                {{ $finding['title'] }}
                            @endif
                        </div>
                        <div class="small text-muted">{{ $finding['description'] }}</div>
                        @if(! empty($finding['rule']))
                            <div class="small text-muted fst-italic">Aturan: {{ $finding['rule'] }}</div>
                        @endif
                    </div>
                </div>
                <span class="badge bg-{{ $color }}-subtle text-{{ $color }} flex-shrink-0">{{ number_format($finding['count'] ?? 0) }}</span>
            </div>
        @empty
            <div class="text-center text-muted py-4">
                <i class="ri-checkbox-circle-line fs-3 d-block mb-1 text-success"></i>
                Tidak ada temuan yang melewati ambang aturan pada periode ini.
            </div>
        @endforelse
    </div>
</div>
